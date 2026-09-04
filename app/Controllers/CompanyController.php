<?php
// app/Controllers/CompanyController.php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/Database.php';

class CompanyController extends Controller
{
    public function showRegisterForm(): void
    {
        $this->view('auth/register', ['error' => null]);
    }

    public function handleRegister(): void
    {
        $db = Database::getConnection();

        $companyName = trim($_POST['company_name'] ?? '');
        $adminName   = trim($_POST['admin_name'] ?? '');
        $email       = trim($_POST['email'] ?? '');
        $password    = $_POST['password'] ?? '';

        if ($companyName === '' || $adminName === '' || $email === '' || $password === '') {
            $this->view('auth/register', ['error' => 'All fields are required.']);
            return;
        }

        if (strlen($password) < 8) {
            $this->view('auth/register', ['error' => 'Password must be at least 8 characters.']);
            return;
        }

        $check = $db->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$email]);
        if ($check->fetch()) {
            $this->view('auth/register', ['error' => 'This email is already registered.']);
            return;
        }

        try {
            $db->beginTransaction();

            $stmt = $db->prepare("INSERT INTO companies (name, status) VALUES (?, 'active')");
            $stmt->execute([$companyName]);
            $companyId = (int) $db->lastInsertId();

            $roleIds = [];
            foreach (['Admin', 'Manager', 'Sales', 'Warehouse'] as $roleName) {
                $stmt = $db->prepare("INSERT INTO roles (company_id, name) VALUES (?, ?)");
                $stmt->execute([$companyId, $roleName]);
                $roleIds[$roleName] = (int) $db->lastInsertId();
            }

            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare(
                "INSERT INTO users (company_id, role_id, name, email, password_hash, status)
                 VALUES (?, ?, ?, ?, ?, 'active')"
            );
            $stmt->execute([$companyId, $roleIds['Admin'], $adminName, $email, $hash]);

            $db->commit();

            $this->redirect('/login?registered=1');
        } catch (PDOException $e) {
            $db->rollBack();
            $this->view('auth/register', ['error' => 'Something went wrong. Please try again.']);
        }
    }
}