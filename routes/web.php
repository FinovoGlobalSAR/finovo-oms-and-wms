<?php

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

$router->get(
    '/employees',
    ['EmployeeController', 'index']
);

$router->post(
    '/employees/create',
    ['EmployeeController', 'create']
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
