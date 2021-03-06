<?php

/*
 * This file is part of PHP CS Fixer.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *     Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace TweetProxyBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use TweetProxyBundle\Entity\User;
use TweetProxyBundle\Helper\Config;
use TweetProxyBundle\Helper\Twitter;

class DefaultController extends Controller
{
    /**
     * Show all users.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction()
    {
        $repository = $this->getDoctrine()->getRepository('TweetProxyBundle:User');
        $query = $repository->findAllPagination();

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query,
            $this->container->get('request_stack')->getCurrentRequest()->query->get('page', 1),
            20
        );

        //get users for a dropdown
        $dropDownUsers = $repository->findAllCustom();

        // parameters to template
        return $this->render('TweetProxyBundle:Default:index.html.twig', array('pagination' => $pagination, 'dropDownUsers' => $dropDownUsers));
    }

    /**
     * Add new user via API.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function addUserAction(Request $request)
    {
        $Twitter = new Twitter();
        $connection = $Twitter->init();
        $statuses = $connection->get('users/show', array('screen_name' => $request->request->get('username')));

        if (isset($statuses->errors)) {
            $message = array();
            foreach ($statuses->errors as $key => $value) {
                $message[] = $value->message;
            }
            $message = implode('<br>', $message);
            $this->addFlash(
                'warning',
                $message
            );

            return $this->redirectToRoute('tweet_proxy_homepage');
        }
            //check if user exists
            $result = $this->getDoctrine()->getRepository('TweetProxyBundle:User')->findByUsername($statuses->screen_name);
        if (!empty($result)) {
            $this->addFlash(
                    'warning',
                    'This user already exists'
                );

            return $this->redirectToRoute('tweet_proxy_homepage');
        }

        $User = new User();
        $User->setName($statuses->name);
        $User->setUsername($statuses->screen_name);
        $User->setUrl($statuses->url);
        $User->setDescription($statuses->description);
        $User->setProfileImage($statuses->profile_image_url);
        $em = $this->getDoctrine()->getManager();
            // tells Doctrine you want to (eventually) save the Product (no queries yet)
            $em->persist($User);
            // actually executes the queries (i.e. the INSERT query)
            $em->flush();

        $this->addFlash(
                'success',
                'User has been added'
            );

        return $this->redirectToRoute('tweet_proxy_homepage');
    }

    /**
     * Get single user profile.
     *
     * @param $username
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function profileAction($username)
    {
        $result = $this->getDoctrine()->getRepository('TweetProxyBundle:User')->findByUsername($username);
        if (empty($result)) {
            $this->addFlash(
                'warning',
                'User doesn\'t exists'
            );

            return $this->redirectToRoute('tweet_proxy_homepage');
        }

        //get tweets
        $tweets = $this->getDoctrine()->getRepository('TweetProxyBundle:Tweets')->getTweetsPerUser($result->getId(), Config::TWEETS_PER_USER);

        return $this->render('TweetProxyBundle:Default:profile.html.twig', array('result' => $result, 'tweets' => $tweets));
    }

    /**
     * Search tweets.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function searchAction(Request $request)
    {
        $term = $request->query->get('term');
        $user_id = $request->query->get('user_id');

        $doctrine = $this->getDoctrine();
        $results = $doctrine->getRepository('TweetProxyBundle:Tweets')->searchTweets($doctrine, $term, $user_id);

        //pagination
        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $results,
            $this->container->get('request_stack')->getCurrentRequest()->query->get('page', 1),
            Config::SEARCH_RESULTS_PER_PAGE
        );

        return $this->render('TweetProxyBundle:Default:search.html.twig', array('pagination' => $pagination));
    }
}
