<?php

$router->get('/orders', ['OrderController', 'index']);
$router->get('/orders/create', ['OrderController', 'showCreateForm']);
$router->post('/orders/create', ['OrderController', 'handleCreate']);
$router->post('/api/orders', ['OrderController', 'apiCreate']);

