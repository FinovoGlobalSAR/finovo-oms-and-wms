<?php


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



/*
|--------------------------------------------------------------------------
| Dashboard
|--------------------------------------------------------------------------
*/

$router->get(
    '/dashboard',
    ['DashboardController', 'index']
);



/*
|--------------------------------------------------------------------------
| Employees
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| Employees
|--------------------------------------------------------------------------
*/

$router->get(
    '/employees',
    ['EmployeeController', 'index']
);

$router->post(
    '/employees/store',
    ['EmployeeController', 'store']
);

$router->post(
    '/employees/update',
    ['EmployeeController', 'update']
);

$router->post(
    '/employees/delete',
    ['EmployeeController', 'delete']
);



/*
|--------------------------------------------------------------------------
| Orders
|--------------------------------------------------------------------------
*/

$router->get(
    '/orders',
    ['OrderController', 'index']
);

$router->get(
    '/orders/create',
    ['OrderController', 'showCreateForm']
);

$router->post(
    '/orders/create',
    ['OrderController', 'handleCreate']
);

$router->post(
    '/api/orders',
    ['OrderController', 'apiCreate']
);