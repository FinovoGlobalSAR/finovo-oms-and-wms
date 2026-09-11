<?php

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



$router->get(
    '/dashboard',
    ['DashboardController', 'index'],
    ['AuthMiddleware']
);


$router->get(
    '/employees',
    ['EmployeeController', 'index']
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

$router->get(
    '/orders/invoice',
    ['InvoiceController', 'download'],
    ['AuthMiddleware']
);

