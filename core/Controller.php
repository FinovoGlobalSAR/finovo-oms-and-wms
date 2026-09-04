<?php

require_once __DIR__ . '/../config/app.php';

abstract class Controller
{
    protected function view(string $viewPath, array $data = []): void
    {
        extract($data);
        require __DIR__ . "/../app/Views/{$viewPath}.php";
    }

    protected function redirect(string $path): void
    {
        header("Location: {$path}");
        exit;
    }

    protected function requireSuperAdmin(): void
    {
        $validUser = env('SUPER_ADMIN_USER', 'superadmin');
        $validPass = env('SUPER_ADMIN_PASSWORD', '');

        $providedUser = $_SERVER['PHP_AUTH_USER'] ?? '';
        $providedPass = $_SERVER['PHP_AUTH_PW'] ?? '';

        if ($validPass === '' || $providedUser !== $validUser || $providedPass !== $validPass) {
            header('WWW-Authenticate: Basic realm="Super Admin Area"');
            http_response_code(401);
            echo "Access denied. Super Admin credentials required.";
            exit;
        }
    }
}