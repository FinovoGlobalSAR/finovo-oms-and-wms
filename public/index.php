<?php
session_start();

require_once __DIR__ . '/../core/Router.php';
require_once __DIR__ . '/../core/Controller.php';

$router = new Router();
require __DIR__ . '/../routes/web.php';

$router->dispatch($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);

