<?php

$app->router->any('/', fn () => 'Hello World!');

$app->router->any('/test', fn () => bin2hex(random_bytes(10)));

$app->router->get('/product', ['ProductController', 'index']);

$app->router->post('/auth/login', ['AuthController', 'login']);
$app->router->post('/auth/register', ['AuthController', 'register']);

$app->router->get('/user/balance', ['UserController', 'balance'], ['before' => 'Authenticated']);
$app->router->get('/user/what-can-i-buy', ['UserController', 'whatCanIBuyWithMyCredit'], ['before' => 'Authenticated']);
$app->router->get('/user/fill-my-cart-randomly', ['UserController', 'fillMyCartRandomly'], ['before' => 'Authenticated']);

$app->router->get('/cart', ['CartController', 'listItems'], ['before' => 'Authenticated']);
$app->router->post('/cart/item', ['CartController', 'addItem'], ['before' => 'Authenticated']);
$app->router->delete('/cart/item', ['CartController', 'removeItem'], ['before' => 'Authenticated']);
