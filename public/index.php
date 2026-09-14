<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

require_once __DIR__ . '/../core/Router.php';
require_once __DIR__ . '/../core/Database.php';

$router = new Router();
require_once __DIR__ . '/../routes/web.php';

$requestUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

$basePath = '/finovo-oms-and-wms/public';

if (str_starts_with($requestUri, $basePath)) {
    $requestUri = substr($requestUri, strlen($basePath));
}

$requestUri = preg_replace('#^/index\.php#', '', $requestUri);

$requestUri = $requestUri ?: '/';

$router->dispatch($requestUri, $_SERVER['REQUEST_METHOD'] ?? 'GET');