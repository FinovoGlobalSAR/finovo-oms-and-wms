<?php
// routes/web.php
$router->get('/register', ['CompanyController', 'showRegisterForm']);
$router->post('/register', ['CompanyController', 'handleRegister']);