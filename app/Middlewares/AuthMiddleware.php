<?php

require_once __DIR__
    . '/../Models/User.php';


class AuthMiddleware
{
    public static function handle(): void
    {
        $loginUrl =
            '/finovo-oms-and-wms/public/login';


        /*
        |--------------------------------------------------------------------------
        | Session Must Exist
        |--------------------------------------------------------------------------
        */

        if (
            !isset($_SESSION['user'])
            ||
            !is_array(
                $_SESSION['user']
            )
        ) {

            self::redirectInvalidUser(
                'Please login first.'
            );
        }


        $userId =
            (int) (
                $_SESSION['user']['id']
                ?? 0
            );


        $companyId =
            (int) (
                $_SESSION['user']['company_id']
                ?? 0
            );


        /*
        |--------------------------------------------------------------------------
        | Basic Session Validation
        |--------------------------------------------------------------------------
        */

        if (
            $userId <= 0
            ||
            $companyId <= 0
        ) {

            self::redirectInvalidUser(
                'Invalid login session.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | CHECK DATABASE
        |--------------------------------------------------------------------------
        |
        | Session hona alone enough nahi hai.
        |
        | User abhi bhi users table mein hona chahiye.
        |--------------------------------------------------------------------------
        */

        $userModel =
            new User();


        $user =
            $userModel
                ->findValidLoggedInUser(
                    $userId,
                    $companyId
                );


        /*
        |--------------------------------------------------------------------------
        | User Removed / Inactive / Invalid
        |--------------------------------------------------------------------------
        */

        if (!$user) {

            self::redirectInvalidUser(
                'Your account no longer has access.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Role Must Be Valid
        |--------------------------------------------------------------------------
        */

        $role =
            strtolower(
                trim(
                    $user['role']
                    ?? ''
                )
            );


        $allowedRoles = [
            'admin',
            'manager',
            'warehouse'
        ];


        if (
            !in_array(
                $role,
                $allowedRoles,
                true
            )
        ) {

            self::redirectInvalidUser(
                'You do not have permission to access this system.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Refresh Session From DB
        |--------------------------------------------------------------------------
        */

        $_SESSION['user'] = [

            'id' =>
                (int) $user['id'],

            'company_id' =>
                (int) $user['company_id'],

            'role_id' =>
                (int) $user['role_id'],

            'name' =>
                $user['name'],

            'email' =>
                $user['email'],

            'role' =>
                $role
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Reject Invalid User
    |--------------------------------------------------------------------------
    */

    private static function redirectInvalidUser(
        string $message
    ): void {

        /*
        | Remove ALL auth-related stale values.
        */

        unset(
            $_SESSION['user'],
            $_SESSION['otp'],
            $_SESSION['otp_user'],
            $_SESSION['success'],
            $_SESSION['error']
        );


        $_SESSION['error'] =
            $message;


        header(
            'Location: /finovo-oms-and-wms/public/login'
        );

        exit;
    }
}