<?php

$router->get('/dashboard', ['DashboardController', 'index']);


$router->get('/employees', ['EmployeeController', 'index']);

$router->post('/employees/store', ['EmployeeController', 'store']);

$router->post('/employees/update', ['EmployeeController', 'update']);

$router->post('/employees/delete', ['EmployeeController', 'delete']);


$router->get('/orders', ['OrderController', 'index']);

$router->get('/orders/create', ['OrderController', 'showCreateForm']);

$router->post('/orders/create', ['OrderController', 'handleCreate']);

$router->post('/api/orders', ['OrderController', 'apiCreate']);