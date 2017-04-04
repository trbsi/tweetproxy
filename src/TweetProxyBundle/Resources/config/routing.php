<?php

use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;

$collection = new RouteCollection();

$collection->add('tweet_proxy_homepage', new Route('/', array(
    '_controller' => 'TweetProxyBundle:Default:index',
)));

$collection->add('add_user', new Route('/add_user', array(
    '_controller' => 'TweetProxyBundle:Default:addUser',
)));


$collection->add('profile', new Route('/{username}', array(
    '_controller' => 'TweetProxyBundle:Default:profile',
)));

$collection->add('search', new Route('/search/{term}', array(
    '_controller' => 'TweetProxyBundle:Default:search',
)));


//CRON
$collection->add('search', new Route('/cron/fetch-tweet', array(
    '_controller' => 'TweetProxyBundle:Cron:fetchTweet',
)));

return $collection;
