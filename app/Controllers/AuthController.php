<?php
require_once __DIR__ . '/../Models/User.php';
require_once __DIR__ . '/../Services/MailService.php';

class AuthController
{
    public function showLogin(): void
    {
        if (isset($_SESSION['user']) && is_array($_SESSION['user'])) {
            $userId = (int) ($_SESSION['user']['id'] ?? 0);

            if ($userId > 0) {
                $userModel = new User();
                $validUser = $userModel->findValidLoggedInUser($userId);

                if ($validUser) {
                    header('Location: /dashboard');
                    exit;
                }
            }

            unset($_SESSION['user']);
        }

        require __DIR__ . '/../Views/auth/login.php';
    }

    public function login(): void
    {
        unset($_SESSION['user'], $_SESSION['error'], $_SESSION['login_alert']);

        $email = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';

        if ($email === '' || $password === '') {
            $_SESSION['error'] = 'Email and password are required.';
            $this->redirectToLogin();
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = 'Please enter a valid email address.';
            $this->redirectToLogin();
        }

        $userModel = new User();
        $user = $userModel->findByEmail($email);

        if (!$user) {
            $_SESSION['error'] = 'User not found.';
            $this->redirectToLogin();
        }

        if (!password_verify($password, $user['password_hash'])) {
            $_SESSION['error'] = 'Invalid password.';
            $this->redirectToLogin();
        }

        if (strtolower(trim($user['status'] ?? '')) !== 'active') {
            $_SESSION['login_alert'] = 'This account is inactive.';
            $this->redirectToLogin();
        }

        $role = strtolower(trim($user['role'] ?? ''));
        $allowedRoles = ['admin', 'manager', 'sales staff', 'warehouse staff'];

        if (!in_array($role, $allowedRoles, true)) {
            $_SESSION['error'] = 'Your account does not have a valid role.';
            $this->redirectToLogin();
        }

        session_regenerate_id(true);

        $_SESSION['user'] = [
            'id' => (int) $user['id'],
            'role_id' => (int) $user['role_id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $role,
        ];

        header('Location: /dashboard');
        exit;
    }

    public function logout(): void
    {
        $this->destroySession();
        header('Location: /login');
        exit;
    }

    // ---------- Forgot Password: Step 1 — Request Code ----------

    public function showForgotPassword(): void
    {
        require __DIR__ . '/../Views/auth/forgot-password.php';
    }

    public function sendResetOtp(): void
    {
        $email = strtolower(trim($_POST['email'] ?? ''));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = 'Please enter a valid email address.';
            header('Location: /forgot-password');
            exit;
        }

        $userModel = new User();
        $user = $userModel->findByEmail($email);

        if (!$user) {
            $_SESSION['success'] = 'If that email exists, a reset code has been sent.';
            header('Location: /forgot-password');
            exit;
        }

        $otp = (string) random_int(100000, 999999);
        $expiresAt = date('Y-m-d H:i:s', time() + 600); // 10 minutes from now
        $userModel->saveResetOtp((int) $user['id'], $otp, $expiresAt);

        $mailService = new MailService();
        $sent = $mailService->sendOtp($user['email'], $user['name'], $otp);

        if (!$sent) {
            $_SESSION['error'] = 'Could not send reset email. Please check SMTP settings or try again later.';
            header('Location: /forgot-password');
            exit;
        }

        unset($_SESSION['reset_otp_verified']);
        $_SESSION['reset_user_id'] = (int) $user['id'];
        $_SESSION['reset_email'] = $user['email'];
        $_SESSION['success'] = 'A reset code has been sent to your email.';

        header('Location: /reset-password');
        exit;
    }

    // ---------- Forgot Password: Step 2 — Enter OTP ----------

    public function showResetPassword(): void
    {
        if (empty($_SESSION['reset_user_id'])) {
            $_SESSION['error'] = 'Please request a reset code first.';
            header('Location: /forgot-password');
            exit;
        }

        // Agar OTP abhi verify nahi hua, sirf OTP form dikhao.
        // Verify ho chuka hai to new password form dikhao.
        $otpVerified = !empty($_SESSION['reset_otp_verified']);

        require __DIR__ . '/../Views/auth/reset-password.php';
    }

    public function verifyResetOtp(): void
    {
        if (empty($_SESSION['reset_user_id'])) {
            $_SESSION['error'] = 'Please request a reset code first.';
            header('Location: /forgot-password');
            exit;
        }

        $otp = trim($_POST['otp'] ?? '');

        if ($otp === '') {
            $_SESSION['error'] = 'Please enter the code.';
            header('Location: /reset-password');
            exit;
        }

        $userModel = new User();
        $userId = (int) $_SESSION['reset_user_id'];

        if (!$userModel->verifyResetOtp($userId, $otp)) {
            $_SESSION['error'] = 'Invalid or expired code.';
            header('Location: /reset-password');
            exit;
        }

        // OTP sahi hai — ab naya password set karne ka page dikhega.
        $_SESSION['reset_otp_verified'] = true;
        header('Location: /reset-password');
        exit;
    }

    // ---------- Forgot Password: Step 3 — Set New Password ----------

    public function setNewPassword(): void
    {
        if (empty($_SESSION['reset_user_id']) || empty($_SESSION['reset_otp_verified'])) {
            $_SESSION['error'] = 'Please verify your code first.';
            header('Location: /reset-password');
            exit;
        }

        $newPassword = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if ($newPassword === '' || $confirmPassword === '') {
            $_SESSION['error'] = 'All fields are required.';
            header('Location: /reset-password');
            exit;
        }

        if (strlen($newPassword) < 6) {
            $_SESSION['error'] = 'Password must be at least 6 characters.';
            header('Location: /reset-password');
            exit;
        }

        if ($newPassword !== $confirmPassword) {
            $_SESSION['error'] = 'Passwords do not match.';
            header('Location: /reset-password');
            exit;
        }

        $userModel = new User();
        $userId = (int) $_SESSION['reset_user_id'];

        $userModel->resetPassword($userId, password_hash($newPassword, PASSWORD_DEFAULT));

        unset($_SESSION['reset_user_id'], $_SESSION['reset_email'], $_SESSION['reset_otp_verified']);
        $_SESSION['success'] = 'Password reset successfully. Please login with your new password.';

        header('Location: /login');
        exit;
    }

    private function redirectToLogin(): void
    {
        header('Location: /login');
        exit;
    }

    private function destroySession(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }

        session_destroy();
    }
}