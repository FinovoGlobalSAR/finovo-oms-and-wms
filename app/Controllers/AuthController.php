
<?php

// class AuthController
// {
//     // Show Login Page
//     public function showLogin()
//     {
//         require __DIR__ . '/../Views/auth/login.php';
//     }

//     // Login
//     public function login()
//     {
//         $email = trim($_POST['email'] ?? '');
//         $password = $_POST['password'] ?? '';

//         // Validate input
//         if (empty($email) || empty($password)) {
//             $_SESSION['error'] = 'Email and password are required.';
//             header('Location: /finovo-oms-and-wms/public/login');
//             exit;
//         }

//         // Find user from database
//         $userModel = new User();
//         $user = $userModel->findByEmail($email);

//         // Check user and password
//         if (
//             !$user ||
//             !password_verify($password, $user['password'])
//         ) {
//             $_SESSION['error'] = 'Invalid email or password.';
//             header('Location: /finovo-oms-and-wms/public/login');
//             exit;
//         }

//         // Check account status
//         if ($user['status'] !== 'active') {
//             $_SESSION['error'] = 'Your account is inactive.';
//             header('Location: /finovo-oms-and-wms/public/login');
//             exit;
//         }

//         /*
//         |--------------------------------------------------------------------------
//         | Role comes from database
//         |--------------------------------------------------------------------------
//         |
//         | User.php joins:
//         | users -> roles
//         |
//         | So $user['role'] contains:
//         | Admin / Manager / Sales / Warehouse
//         |
//         */

//         // Generate OTP
//         $otp = random_int(100000, 999999);

//         // Store temporary authentication data
//         $_SESSION['otp'] = (string) $otp;
//         $_SESSION['otp_user'] = $user;

//         // Temporary testing
//         $_SESSION['success'] = "Your OTP is: $otp";

//         // Go to OTP page
//         header('Location: /finovo-oms-and-wms/public/otp');
//         exit;
//     }

//     // Show OTP Page
//     public function showOtp()
//     {
//         require __DIR__ . '/../Views/auth/otp.php';
//     }

//     // Verify OTP
//     public function verifyOtp()
//     {
//         $enteredOtp = trim($_POST['otp'] ?? '');

//         // Check temporary login session
//         if (
//             !isset($_SESSION['otp']) ||
//             !isset($_SESSION['otp_user'])
//         ) {
//             $_SESSION['error'] = 'Please login again.';
//             header('Location: /finovo-oms-and-wms/public/login');
//             exit;
//         }

//         // Verify OTP
//         if (!hash_equals($_SESSION['otp'], $enteredOtp)) {
//             $_SESSION['error'] = 'Invalid OTP.';
//             header('Location: /finovo-oms-and-wms/public/otp');
//             exit;
//         }

//         // Get logged-in user
//         $user = $_SESSION['otp_user'];

//         // Regenerate session ID after successful authentication
//         session_regenerate_id(true);

//         // Create authenticated user session
//         $_SESSION['user'] = [
//             'id'         => $user['id'],
//             'company_id' => $user['company_id'],
//             'role_id'    => $user['role_id'],
//             'name'       => $user['name'],
//             'email'      => $user['email'],
//             'role'       => strtolower($user['role'])
//         ];

//         // Remove temporary authentication data
//         unset(
//             $_SESSION['otp'],
//             $_SESSION['otp_user']
//         );

//         /*
//         |--------------------------------------------------------------------------
//         | Redirect according to database role
//         |--------------------------------------------------------------------------
//         */

//         $role = strtolower($user['role']);

//         switch ($role) {

//             case 'admin':
//                 header('Location: /finovo-oms-and-wms/public/admin/dashboard');
//                 break;

//             case 'manager':
//                 header('Location: /finovo-oms-and-wms/public/manager/dashboard');
//                 break;

//             case 'sales':
//                 header('Location: /finovo-oms-and-wms/public/sales/dashboard');
//                 break;

//             case 'warehouse':
//                 header('Location: /finovo-oms-and-wms/public/warehouse/dashboard');
//                 break;

//             default:
//                 // Unknown/unconfigured role
//                 $_SESSION = [];
//                 session_destroy();

//                 session_start();
//                 $_SESSION['error'] = 'Your account has an invalid role.';

//                 header('Location: /finovo-oms-and-wms/public/login');
//                 break;
//         }

//         exit;
//     }

//     // Logout
//     public function logout()
//     {
//         $_SESSION = [];

//         if (ini_get('session.use_cookies')) {
//             $params = session_get_cookie_params();

//             setcookie(
//                 session_name(),
//                 '',
//                 time() - 42000,
//                 $params['path'],
//                 $params['domain'],
//                 $params['secure'],
//                 $params['httponly']
//             );
//         }

//         session_destroy();

//         header('Location: /finovo-oms-and-wms/public/login');
//         exit;
//     }
// }





class AuthController
{
    // Show Login Page
    public function showLogin()
    {
        require __DIR__ . '/../Views/auth/login.php';
    }


    // Login
    public function login()
    {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        // Validate input
        if (empty($email) || empty($password)) {

            $_SESSION['error'] =
                'Email and password are required.';

            header(
                'Location: /finovo-oms-and-wms/public/login'
            );

            exit;
        }


        // Find user from database
        $userModel = new User();

        $user = $userModel->findByEmail($email);


        // Check user and password
        if (
            !$user ||
            !password_verify($password, $user['password'])
        ) {

            $_SESSION['error'] =
                'Invalid email or password.';

            header(
                'Location: /finovo-oms-and-wms/public/login'
            );

            exit;
        }


        // Check account status
        if ($user['status'] !== 'active') {

            $_SESSION['error'] =
                'Your account is inactive.';

            header(
                'Location: /finovo-oms-and-wms/public/login'
            );

            exit;
        }


        // Generate OTP
        $otp = random_int(100000, 999999);


        // Store temporary authentication data
        $_SESSION['otp'] = (string) $otp;
        $_SESSION['otp_user'] = $user;


        // Temporary testing
        $_SESSION['success'] =
            "Your OTP is: $otp";


        // Go to OTP page
        header(
            'Location: /finovo-oms-and-wms/public/otp'
        );

        exit;
    }


    // Show OTP Page
    public function showOtp()
    {
        if (
            !isset($_SESSION['otp']) ||
            !isset($_SESSION['otp_user'])
        ) {

            $_SESSION['error'] =
                'Please login first.';

            header(
                'Location: /finovo-oms-and-wms/public/login'
            );

            exit;
        }

        require __DIR__ . '/../Views/auth/otp.php';
    }


    // Verify OTP
    public function verifyOtp()
    {
        $enteredOtp = trim($_POST['otp'] ?? '');


        // Check temporary login session
        if (
            !isset($_SESSION['otp']) ||
            !isset($_SESSION['otp_user'])
        ) {

            $_SESSION['error'] =
                'Please login again.';

            header(
                'Location: /finovo-oms-and-wms/public/login'
            );

            exit;
        }


        // Verify OTP
        if (
            !hash_equals(
                $_SESSION['otp'],
                $enteredOtp
            )
        ) {

            $_SESSION['error'] =
                'Invalid OTP.';

            header(
                'Location: /finovo-oms-and-wms/public/otp'
            );

            exit;
        }


        // Get logged-in user
        $user = $_SESSION['otp_user'];


        // Regenerate session ID
        session_regenerate_id(true);


        // Create authenticated session
        $_SESSION['user'] = [
            'id'         => $user['id'],
            'company_id' => $user['company_id'],
            'role_id'    => $user['role_id'],
            'name'       => $user['name'],
            'email'      => $user['email'],
            'role'       => strtolower($user['role'])
        ];


        // Remove temporary OTP data
        unset(
            $_SESSION['otp'],
            $_SESSION['otp_user'],
            $_SESSION['success']
        );


        // Get role
        $role = strtolower($user['role']);


        // Redirect according to role
        switch ($role) {

            case 'admin':
                header(
                    'Location: /finovo-oms-and-wms/public/admin/dashboard'
                );
                break;

            case 'manager':
                header(
                    'Location: /finovo-oms-and-wms/public/manager/dashboard'
                );
                break;

            case 'sales':
                header('Location: /finovo-oms-and-wms/sales/dashboard');
                break;

            case 'warehouse':
                header(
                    'Location: /finovo-oms-and-wms/public/warehouse/dashboard'
                );
                break;

            default:

                $_SESSION = [];

                session_destroy();

                session_start();

                $_SESSION['error'] =
                    'Your account has an invalid role.';

                header(
                    'Location: /finovo-oms-and-wms/public/login'
                );

                break;
        }

        exit;
    }


    // Logout
    public function logout()
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {

            $params = session_get_cookie_params();

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

        header(
            'Location: /finovo-oms-and-wms/public/login'
        );

        exit;
    }
}