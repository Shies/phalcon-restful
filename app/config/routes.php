<?php

$routes[] = [
    'method' => 'get',
    'route' => '/ping',
    'handler' => ['Controllers\ExampleController', 'pingAction']
];

$routes[] = [
    'method' => 'get',
    'route' => '/menu',
    'handler' => ['Controllers\ExampleController', 'menuAction']
];

$routes[] = [
    'method' => 'get',
    'route' => '/content/{id}',
    'handler' => ['Controllers\ExampleController', 'contentAction']
];

$routes[] = [
    'method' => 'get',
    'route' => '/audio/{id}',
    'handler' => ['Controllers\ExampleController', 'audioAction']
];
$routes[] = [
    'method' => 'get',
    'route' => '/audio/{id}/page/{page}',
    'handler' => ['Controllers\ExampleController', 'audioAction']
];
$routes[] = [
    'method' => 'get',
    'route' => '/index',
    'handler' => ['Controllers\ExampleController', 'indexAction'],
];
$routes[] = [
    'method' => 'get',
    'route' => '/category/{id}',
    'handler' => ['Controllers\ExampleController', 'categoryAction'],
];
$routes[] = [
    'method' => 'get',
    'route' => '/category/{id}/page/{page}',
    'handler' => ['Controllers\ExampleController', 'categoryAction'],
];
$routes[] = [
    'method' => 'get',
    'route' => '/category/{id}/{hot}/page/{page}',
    'handler' => ['Controllers\ExampleController', 'categoryHotAction'],
];
$routes[] = [
    'method' => 'get',
    'route' => '/videoList/{id}/page/{page}',
    'handler' => ['Controllers\ExampleController', 'videoListAction'],
];
$routes[] = [
    'method' => 'get',
    'route' => '/picture/{id}',
    'handler' => ['Controllers\ExampleController', 'pictureAction']
];
$routes[] = [
    'method' => 'get',
    'route' => '/video/{id}',
    'handler' => ['Controllers\ExampleController', 'videoAction']
];
$routes[] = [
    'method' => 'get',
    'route' => '/states/{userid}-{id}-{cid}-{mid}',
    'handler' => ['Controllers\ExampleController', 'statesAction'],
];
$routes[] = [
    'method' => 'post',
    'route' => '/user/register1',
    'handler' => ['Controllers\UserController', 'register1Action'],
];
$routes[] = [
    'method' => 'post',
    'route' => '/user/register2',
    'handler' => ['Controllers\UserController', 'register2Action'],
];
$routes[] = [
    'method' => 'post',
    'route' => '/user/login',
    'handler' => ['Controllers\UserController', 'loginAction'],
];
$routes[] = [
    'method' => 'post',
    'route' => '/user/logout',
    'handler' => ['Controllers\UserController', 'logoutAction'],
];
$routes[] = [
    'method' => 'post',
    'route' => '/user/password',
    'handler' => ['Controllers\UserController', 'passwordAction'],
];
$routes[] = [
    'method' => 'get',
    'route' => '/user/favorite/{userid}/page/{page}',
    'handler' => ['Controllers\UserController', 'favoriteAction'],
];

$routes[] = [
    'method' => 'post',
    'route' => '/user/favorite/add',
    'handler' => ['Controllers\UserController', 'addfavoriteAction'],
];
$routes[] = [
    'method' => 'post',
    'route' => '/user/favorite/del',
    'handler' => ['Controllers\UserController', 'delfavoriteAction'],
];

$routes[] = [
    'method' => 'get',
    'route' => '/user/follow/{userid}/page/{page}',
    'handler' => ['Controllers\UserController', 'followAction'],
];
$routes[] = [
    'method' => 'post',
    'route' => '/user/follow/add',
    'handler' => ['Controllers\UserController', 'addFollowAction'],
];
$routes[] = [
    'method' => 'post',
    'route' => '/user/follow/del',
    'handler' => ['Controllers\UserController', 'delFollowAction'],
];

$routes[] = [
    'method' => 'post',
    'route' => '/user/forget',
    'handler' => ['Controllers\UserController', 'forgetAction'],
];
$routes[] = [
    'method' => 'get',
    'route' => '/user/{type}/sendCode/{mobile}',
    'handler' => ['Controllers\UserController', 'sendCodeAction'],
];
$routes[] = [
    'method' => 'get',
    'route' => '/praise/{id}/mid/{mid}',
    'handler' => ['Controllers\PraiseController', 'listAction'],
];
$routes[] = [
    'method' => 'post',
    'route' => '/praise/add',
    'handler' => ['Controllers\PraiseController', 'addAction'],
];
$routes[] = [
    'method' => 'post',
    'route' => '/praise/del',
    'handler' => ['Controllers\PraiseController', 'delAction'],
];

# ----------------------------------------------------------------------- #


$routes[] = [
    'method'  => 'get',
    'route'   => '/comment/pingok',
    'handler' => ['Controllers\CommentController', 'pingokAction']
];


$routes[] = [
    'method'  => 'get',
    'route'   => '/comment/article/{id}',
    'handler' => ['Controllers\CommentController', 'articleAction']
];


$routes[] = [
    'method'  => 'get',
    'route'   => '/comment/praise/{id}',
    'handler' => ['Controllers\CommentController', 'praiseAction']
];


$routes[] = [
    'method'  => 'post',
    'route'   => '/comment/comment',
    'handler' => ['Controllers\CommentController', 'commentAction']
];


$routes[] = [
    'method'  => 'get',
    'route'   => '/comment/reply/{id}',
    'handler' => ['Controllers\CommentController', 'replyAction']
];

$routes[] = [
    'method'  => 'get',
    'route'   => '/comment/{commentid}/loop/page/{page}',
    'handler' => ['Controllers\CommentController', 'loopAction']
];


return $routes;
