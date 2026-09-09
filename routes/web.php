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


/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*//*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/

$router->get('/login', ['AuthController', 'showLogin']);
$router->post('/login', ['AuthController', 'login']);

$router->get('/otp', ['AuthController', 'showOtp']);
$router->post('/otp', ['AuthController', 'verifyOtp']);

$router->get('/logout', ['AuthController', 'logout']);

$router->get(
    '/dashboard',
    ['DashboardController', 'index'],
    ['AuthMiddleware']
);





/*
|--------------------------------------------------------------------------
| Dashboard
|--------------------------------------------------------------------------
*/

$router->get(
    '/dashboard',
    ['DashboardController', 'index'],
    ['AuthMiddleware']
);


/*
|--------------------------------------------------------------------------
| Employees
|--------------------------------------------------------------------------
*/

$router->get(
    '/employees',
    ['EmployeeController', 'index'],
    ['AuthMiddleware']
);

$router->post(
    '/employees/store',
    ['EmployeeController', 'store'],
    ['AuthMiddleware']
);

$router->post(
    '/employees/update',
    ['EmployeeController', 'update'],
    ['AuthMiddleware']
);

$router->post(
    '/employees/delete',
    ['EmployeeController', 'delete'],
    ['AuthMiddleware']
);


/*
|--------------------------------------------------------------------------
| Orders
|--------------------------------------------------------------------------
*/

$router->get(
    '/orders',
    ['OrderController', 'index'],
    ['AuthMiddleware']
);

$router->get(
    '/orders/create',
    ['OrderController', 'showCreateForm'],
    ['AuthMiddleware']
);

$router->post(
    '/orders/create',
    ['OrderController', 'handleCreate'],
    ['AuthMiddleware']
);

$router->post(
    '/api/orders',
    ['OrderController', 'apiCreate'],
    ['AuthMiddleware']
);


/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/

$router->get(
    '/login',
    ['AuthController', 'showLogin']
);

$router->post(
    '/login',
    ['AuthController', 'login']
);

$router->get(
    '/otp',
    ['AuthController', 'showOtp']
);

$router->post(
    '/otp',
    ['AuthController', 'verifyOtp']
);

$router->get(
    '/logout',
    ['AuthController', 'logout'],
    ['AuthMiddleware']
);