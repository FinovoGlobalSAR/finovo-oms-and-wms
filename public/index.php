<?php

session_start();

require_once __DIR__ . '/../core/Router.php';
require_once __DIR__ . '/../core/Database.php';

require_once __DIR__ . '/../app/Models/User.php';
require_once __DIR__ . '/../app/Controllers/AuthController.php';

$router = new Router();

require_once __DIR__ . '/../routes/web.php';


/*
|--------------------------------------------------------------------------
| Request URI
|--------------------------------------------------------------------------
*/

$requestUri = parse_url(
    $_SERVER['REQUEST_URI'] ?? '/',
    PHP_URL_PATH
);


/*
|--------------------------------------------------------------------------
| Project Base Path
|--------------------------------------------------------------------------
|
| Browser URL:
| http://localhost/finovo-oms-and-wms/public/login
|
| Router ko sirf:
| /login
|
| milega.
|--------------------------------------------------------------------------
*/

$basePath = '/finovo-oms-and-wms/public';

if (str_starts_with($requestUri, $basePath)) {
    $requestUri = substr($requestUri, strlen($basePath));
}


/*
|--------------------------------------------------------------------------
| Remove index.php
|--------------------------------------------------------------------------
*/

$requestUri = preg_replace(
    '#^/index\.php#',
    '',
    $requestUri
);


/*
|--------------------------------------------------------------------------
| Empty URI
|--------------------------------------------------------------------------
*/

$requestUri = $requestUri ?: '/';


/*
|--------------------------------------------------------------------------
| Dispatch Route
|--------------------------------------------------------------------------
*/

$router->dispatch(
    $requestUri,
    $_SERVER['REQUEST_METHOD'] ?? 'GET'
);