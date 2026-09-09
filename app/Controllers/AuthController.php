<?php

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
        $role = $_POST['role'] ?? '';

        // Validate input
        if (empty($email) || empty($password) || empty($role)) {
            $_SESSION['error'] = 'All fields are required.';
            header('Location: /finovo-oms-and-wms/login');
            exit;
        }

        // Find user
        $userModel = new User();
        $user = $userModel->findByEmail($email);

        // Check credentials and role
        if (
            !$user ||
            !password_verify($password, $user['password']) ||
            $user['role'] !== $role
        ) {
            $_SESSION['error'] = 'Invalid email, password or role.';
            header('Location: /finovo-oms-and-wms/login');
            exit;
        }

        // Generate OTP
        $otp = random_int(100000, 999999);

        // Store OTP and user temporarily
        $_SESSION['otp'] = (string) $otp;
        $_SESSION['otp_user'] = $user;

        // Temporary for testing
        $_SESSION['success'] = "Your OTP is: $otp";

        // Go to OTP page
        header('Location: /finovo-oms-and-wms/otp');
        exit;
    }

    // Show OTP Page
    public function showOtp()
    {
       require __DIR__ . '/../Views/auth/otp.php';
    }

    // Verify OTP
    public function verifyOtp()
    {
        $enteredOtp = trim($_POST['otp'] ?? '');

        if (!isset($_SESSION['otp'], $_SESSION['otp_user'])) {
            $_SESSION['error'] = 'Please login again.';
            header('Location: /finovo-oms-and-wms/login');
            exit;
        }

        // Check OTP
        if (!hash_equals($_SESSION['otp'], $enteredOtp)) {
            $_SESSION['error'] = 'Invalid OTP.';
            header('Location: /finovo-oms-and-wms/otp');
            exit;
        }

        $user = $_SESSION['otp_user'];

        // Create authenticated session
        session_regenerate_id(true);

        $_SESSION['user'] = [
            'id' => $user['id'],
            'company_id' => $user['company_id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role']
        ];

        // Remove temporary OTP data
        unset($_SESSION['otp'], $_SESSION['otp_user']);

        // Redirect according to role
        switch ($user['role']) {

            case 'admin':
                header('Location: /finovo-oms-and-wms/admin/dashboard');
                break;

            case 'manager':
                header('Location: /finovo-oms-and-wms/manager/dashboard');
                break;

            case 'warehouse':
                header('Location: /finovo-oms-and-wms/warehouse/dashboard');
                break;

            default:
                $_SESSION['error'] = 'Invalid role.';
                header('Location: /finovo-oms-and-wms/login');
                break;
        }

        exit;
    }

    // Logout
    public function logout()
    {
        $_SESSION = [];
        session_destroy();

        header('Location: /finovo-oms-and-wms/login');
        exit;
    }
}