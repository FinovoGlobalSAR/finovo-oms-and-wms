<?php

require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../Models/User.php';
require_once __DIR__ . '/../Models/Role.php';

class EmployeeController extends Controller
{
    private User $userModel;
    private Role $roleModel;

    public function __construct()
    {
        $this->userModel = new User();
        $this->roleModel = new Role();
    }

    public function index(): void
    {
        $companyId = $this->currentCompanyId();

        // Temporary bootstrap until real company login/session is connected.
        $this->roleModel->ensureDefaultSetup($companyId);

        $employees = $this->userModel->allByCompany($companyId);
        $roles = $this->roleModel->allByCompany($companyId);

        $this->view('employees/index', [
            'employees' => $employees,
            'roles' => $roles,
            'success' => $_SESSION['employee_success'] ?? null,
            'error' => $_SESSION['employee_error'] ?? null,
            'old' => $_SESSION['employee_old'] ?? [],
        ]);

        unset(
            $_SESSION['employee_success'],
            $_SESSION['employee_error'],
            $_SESSION['employee_old']
        );
    }

    public function store(): void
    {
        $companyId = $this->currentCompanyId();
        $this->roleModel->ensureDefaultSetup($companyId);

        $name = trim($_POST['name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';
        $roleId = (int) ($_POST['role_id'] ?? 0);
        $status = $_POST['status'] ?? 'active';

        $_SESSION['employee_old'] = [
            'name' => $name,
            'email' => $email,
            'role_id' => $roleId,
            'status' => $status,
        ];

        if ($name === '' || $email === '' || $password === '' || $roleId <= 0) {
            $this->backWithError('Please fill in all required fields.');
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->backWithError('Please enter a valid email address.');
            return;
        }

        if (strlen($password) < 6) {
            $this->backWithError('Password must be at least 6 characters long.');
            return;
        }

        if (!in_array($status, ['active', 'inactive'], true)) {
            $this->backWithError('Invalid employee status.');
            return;
        }

        if (!$this->roleModel->belongsToCompany($roleId, $companyId)) {
            $this->backWithError('Please select a valid role.');
            return;
        }

        if ($this->userModel->emailExists($email, $companyId)) {
            $this->backWithError('An employee with this email already exists.');
            return;
        }

        try {
            $this->userModel->create([
                'company_id' => $companyId,
                'role_id' => $roleId,
                'name' => $name,
                'email' => $email,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'status' => $status,
            ]);

            unset($_SESSION['employee_old']);
            $_SESSION['employee_success'] = 'Employee created successfully.';
        } catch (PDOException $e) {
            $_SESSION['employee_error'] = 'Employee could not be created. Please try again.';
        }

        $this->redirect($this->employeesUrl());
    }

    public function update(): void
    {
        $companyId = $this->currentCompanyId();
        $id = (int) ($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';
        $roleId = (int) ($_POST['role_id'] ?? 0);
        $status = $_POST['status'] ?? 'active';

        if ($id <= 0 || !$this->userModel->findByIdAndCompany($id, $companyId)) {
            $this->backWithError('Employee not found.');
            return;
        }

        if ($name === '' || $email === '' || $roleId <= 0) {
            $this->backWithError('Name, email and role are required.');
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->backWithError('Please enter a valid email address.');
            return;
        }

        if ($password !== '' && strlen($password) < 6) {
            $this->backWithError('New password must be at least 6 characters long.');
            return;
        }

        if (!in_array($status, ['active', 'inactive'], true)) {
            $this->backWithError('Invalid employee status.');
            return;
        }

        if (!$this->roleModel->belongsToCompany($roleId, $companyId)) {
            $this->backWithError('Please select a valid role.');
            return;
        }

        if ($this->userModel->emailExists($email, $companyId, $id)) {
            $this->backWithError('Another employee already uses this email.');
            return;
        }

        try {
            $this->userModel->updateEmployee($id, $companyId, [
                'role_id' => $roleId,
                'name' => $name,
                'email' => $email,
                'status' => $status,
                'password_hash' => $password !== ''
                    ? password_hash($password, PASSWORD_DEFAULT)
                    : null,
            ]);

            $_SESSION['employee_success'] = 'Employee updated successfully.';
        } catch (PDOException $e) {
            $_SESSION['employee_error'] = 'Employee could not be updated. Please try again.';
        }

        $this->redirect($this->employeesUrl());
    }

    public function delete(): void
    {
        $companyId = $this->currentCompanyId();
        $id = (int) ($_POST['id'] ?? 0);

        if ($id <= 0 || !$this->userModel->findByIdAndCompany($id, $companyId)) {
            $this->backWithError('Employee not found.');
            return;
        }

        try {
            $deleted = $this->userModel->deleteEmployee($id, $companyId);
            $_SESSION['employee_success'] = $deleted
                ? 'Employee deleted successfully.'
                : 'Employee was not deleted.';
        } catch (PDOException $e) {
            $_SESSION['employee_error'] = 'Employee could not be deleted. It may be linked to other records.';
        }

        $this->redirect($this->employeesUrl());
    }

    private function currentCompanyId(): int
    {
        return isset($_SESSION['company_id']) && (int) $_SESSION['company_id'] > 0
            ? (int) $_SESSION['company_id']
            : 1;
    }

    private function employeesUrl(): string
    {
        return '/finovo-oms-and-wms/public/index.php/employees';
    }

    private function backWithError(string $message): void
    {
        $_SESSION['employee_error'] = $message;
        $this->redirect($this->employeesUrl());
    }
}
