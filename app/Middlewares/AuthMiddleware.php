<?php
require_once __DIR__ . '/../Models/User.php';

class AuthMiddleware
{
    public static function handle(): void
    {
        if (!isset($_SESSION['user']) || !is_array($_SESSION['user'])) {
            self::redirectInvalidUser('Please login first.');
        }

        $userId = (int) ($_SESSION['user']['id'] ?? 0);

        if ($userId <= 0) {
            self::redirectInvalidUser('Invalid login session.');
        }

        $userModel = new User();
        $user = $userModel->findValidLoggedInUser($userId);

        if (!$user) {
            self::redirectInvalidUser('Your account no longer has access.');
        }

        $role = strtolower(trim($user['role'] ?? ''));
        $allowedRoles = ['admin', 'manager', 'sales staff', 'warehouse staff'];

        if (!in_array($role, $allowedRoles, true)) {
            self::redirectInvalidUser('You do not have permission to access this system.');
        }

        $_SESSION['user'] = [
            'id' => (int) $user['id'],
            'role_id' => (int) $user['role_id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $role,
        ];
    }

    private static function redirectInvalidUser(string $message): void
    {
        unset($_SESSION['user'], $_SESSION['otp'], $_SESSION['otp_user'], $_SESSION['success'], $_SESSION['error']);
        $_SESSION['error'] = $message;
        header('Location: /login');
        exit;
    }
}