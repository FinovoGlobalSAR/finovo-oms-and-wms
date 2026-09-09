```php
<?php

class Middleware
{
    /*
    |--------------------------------------------------------------------------
    | Check Authentication
    |--------------------------------------------------------------------------
    */
    public static function auth(): void
    {
        if (!isset($_SESSION['user'])) {
            header('Location: /finovo-oms-and-wms/login');
            exit;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Check Role
    |--------------------------------------------------------------------------
    */
    public static function role(array $allowedRoles): void
    {
        self::auth();

        $userRole = strtolower($_SESSION['user']['role'] ?? '');

        $allowedRoles = array_map(
            'strtolower',
            $allowedRoles
        );

        if (!in_array($userRole, $allowedRoles, true)) {

            http_response_code(403);
-
            echo '<h1>403 Forbidden</h1>';
            echo '<p>You do not have permission to access this page.</p>';

            exit;
        }
    }
}
```
