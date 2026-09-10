<?php

class AuthController
{
    private string $baseUrl =
    '/finovo-oms-and-wms/public';

    public function showLogin(): void
    {
        if (
            isset($_SESSION['user'])
            &&
            is_array($_SESSION['user'])
        ) {

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


            if (
                $userId > 0
                &&
                $companyId > 0
            ) {

                $userModel =
                    new User();


                $validUser =
                    $userModel
                    ->findValidLoggedInUser(
                        $userId,
                        $companyId
                    );


                if ($validUser) {

                    header(
                        "Location: {$this->baseUrl}/dashboard"
                    );

                    exit;
                }
            }



            unset(
                $_SESSION['user']
            );
        }


        require __DIR__
            . '/../Views/auth/login.php';
    }



    public function login(): void
    {

        unset(
            $_SESSION['user'],
            $_SESSION['otp'],
            $_SESSION['otp_user'],
            $_SESSION['success'],
            $_SESSION['error'],
            $_SESSION['login_alert']
        );


        $email =
            strtolower(
                trim(
                    $_POST['email']
                        ?? ''
                )
            );


        $password =
            $_POST['password']
            ?? '';



        if (
            $email === ''
            ||
            $password === ''
        ) {

            $_SESSION['error'] =
                'Email and password are required.';


            $this->redirectToLogin();
        }



        if (
            !filter_var(
                $email,
                FILTER_VALIDATE_EMAIL
            )
        ) {

            $_SESSION['error'] =
                'Please enter a valid email address.';


            $this->redirectToLogin();
        }


        $userModel =
            new User();


        $user =
            $userModel
            ->findByEmail(
                $email
            );


        if (!$user) {

            $_SESSION['error'] =
                'User not found.';


            $this->redirectToLogin();
        }


        if (
            !password_verify(
                $password,
                $user['password_hash']
            )
        ) {

            $_SESSION['error'] =
                'Invalid password.';


            $this->redirectToLogin();
        }


        if (
            strtolower(
                trim(
                    $user['status']
                        ?? ''
                )
            )
            !==
            'active'
        ) {

            $_SESSION['login_alert'] =
                'User not found.';


            $this->redirectToLogin();
        }


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

            $_SESSION['error'] =
                'Your account does not have a valid role.';


            $this->redirectToLogin();
        }


        $otp =
            random_int(
                100000,
                999999
            );


        $_SESSION['otp'] =
            (string) $otp;

        $_SESSION['otp_user'] = [

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

            'status' =>
            $user['status'],

            'role' =>
            $role
        ];


        $_SESSION['success'] =
            "Your OTP is: {$otp}";


        header(
            "Location: {$this->baseUrl}/otp"
        );

        exit;
    }


    public function showOtp(): void
    {
        if (
            !isset($_SESSION['otp'])
            ||
            !isset($_SESSION['otp_user'])
            ||
            !is_array(
                $_SESSION['otp_user']
            )
        ) {

            unset(
                $_SESSION['otp'],
                $_SESSION['otp_user'],
                $_SESSION['success']
            );


            $_SESSION['error'] =
                'Please login first.';


            $this->redirectToLogin();
        }


        require __DIR__
            . '/../Views/auth/otp.php';
    }



    public function verifyOtp(): void
    {
        $enteredOtp =
            trim(
                $_POST['otp']
                    ?? ''
            );


        if (
            !isset($_SESSION['otp'])
            ||
            !isset($_SESSION['otp_user'])
            ||
            !is_array(
                $_SESSION['otp_user']
            )
        ) {

            unset(
                $_SESSION['otp'],
                $_SESSION['otp_user'],
                $_SESSION['success']
            );


            $_SESSION['error'] =
                'Please login again.';


            $this->redirectToLogin();
        }


        if (
            !hash_equals(
                (string) $_SESSION['otp'],
                (string) $enteredOtp
            )
        ) {

            $_SESSION['error'] =
                'Invalid OTP.';


            header(
                "Location: {$this->baseUrl}/otp"
            );

            exit;
        }

        $temporaryUser =
            $_SESSION['otp_user'];

        $userModel =
            new User();


        $user =
            $userModel
            ->findValidLoggedInUser(

                (int)
                $temporaryUser['id'],

                (int)
                $temporaryUser['company_id']
            );

        if (!$user) {

            $this->clearAuthenticationData();


            $_SESSION['error'] =
                'Your account no longer has access.';


            $this->redirectToLogin();
        }

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

            $this->clearAuthenticationData();


            $_SESSION['error'] =
                'Your account does not have a valid role.';


            $this->redirectToLogin();
        }

        session_regenerate_id(
            true
        );

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

        unset(
            $_SESSION['otp'],
            $_SESSION['otp_user'],
            $_SESSION['success'],
            $_SESSION['error'],
            $_SESSION['login_alert']
        );

        header(
            "Location: {$this->baseUrl}/dashboard"
        );

        exit;
    }
    public function logout(): void
    {
        $this->destroySession();


        header(
            "Location: {$this->baseUrl}/login"
        );

        exit;
    }

    private function redirectToLogin(): void
    {
        header(
            "Location: {$this->baseUrl}/login"
        );

        exit;
    }

    private function clearAuthenticationData(): void
    {
        unset(
            $_SESSION['user'],
            $_SESSION['otp'],
            $_SESSION['otp_user'],
            $_SESSION['success']
        );
    }

    private function destroySession(): void
    {
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
    }
}
