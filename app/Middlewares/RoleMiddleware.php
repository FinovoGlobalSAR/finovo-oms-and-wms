<?php


class RoleMiddleware
{
    public static function handle(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Get Logged-In User Role
        |--------------------------------------------------------------------------
        */

        $role =
            strtolower(
                trim(
                    $_SESSION['user']['role']
                    ?? ''
                )
            );


        /*
        |--------------------------------------------------------------------------
        | Roles Currently Allowed In CRM
        |--------------------------------------------------------------------------
        */

        $allowedRoles = [
            'admin',
            'manager',
            'warehouse'
        ];


        /*
        |--------------------------------------------------------------------------
        | Invalid Role
        |--------------------------------------------------------------------------
        */

        if (
            !in_array(
                $role,
                $allowedRoles,
                true
            )
        ) {

            $_SESSION = [];


            if (
                ini_get(
                    'session.use_cookies'
                )
            ) {

                $params =
                    session_get_cookie_params();


                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params['path'],
                    $params['domain'],
                    $params['secure'],
                    $params['httponly']
                );
            }


            session_destroy();


            session_start();


            $_SESSION['error'] =
                'You do not have permission to access this page.';


            header(
                'Location: /finovo-oms-and-wms/public/index.php/login'
            );

            exit;
        }


        /*
        |--------------------------------------------------------------------------
        | For Now
        |--------------------------------------------------------------------------
        |
        | Admin, Manager and Warehouse:
        |
        | sab Employee Management page access kar sakte hain.
        |
        | Later hum isi role system ko page-specific karenge.
        |--------------------------------------------------------------------------
        */
    }
}