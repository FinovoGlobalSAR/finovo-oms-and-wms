<?php

require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/Paginator.php';
require_once __DIR__ . '/../Models/User.php';
require_once __DIR__ . '/../Models/Role.php';

/**
 * Employees page — Admin aur Manager employees banate / badalte / delete karte hain.
 *
 * Hifazat:
 *   - Admin account sirf Admin bana, badal ya delete kar sakta hai. Manager ko Admin role dropdown
 *     mein dikhta hi nahi, aur kisi Admin ke saamne Edit / Delete nahi hota (server pe bhi check hai).
 *   - Koi apna hi account delete nahi kar sakta, aur apna role / status nahi badal sakta
 *     (warna galti se khud ko bahar kar deta).
 */
class EmployeeController extends Controller
{
    private User $userModel;
    private Role $roleModel;

    public function __construct()
    {
        parent::__construct();

        $this->requireRole(['admin', 'manager']);

        $this->userModel = new User();
        $this->roleModel = new Role();
    }

    private function isAdmin(): bool
    {
        return $this->currentRole() === 'admin';
    }

    /** Kya current user is employee (role) ko chhed sakta hai? Manager kisi Admin ko nahi. */
    private function canManageRole(string $roleSlug): bool
    {
        return $this->isAdmin() || $roleSlug !== 'admin';
    }

    public function index(): void
    {
        $this->roleModel->ensureDefaultSetup();

        $allEmployees = $this->userModel->allByCompany();
        $roles = $this->roleModel->all();

        // Upar ke numbers POORI list se
        $stats = ['total' => count($allEmployees), 'active' => 0];
        foreach ($allEmployees as $emp) {
            if (strtolower($emp['status'] ?? 'inactive') === 'active') {
                $stats['active']++;
            }
        }
        $stats['inactive'] = $stats['total'] - $stats['active'];

        // Search / role / status filter server pe (har page ka result sahi)
        $search = trim((string) ($_GET['q'] ?? ''));
        $roleFilter = strtolower(trim((string) ($_GET['role'] ?? '')));
        $statusFilter = in_array($_GET['status'] ?? '', ['active', 'inactive'], true) ? $_GET['status'] : '';
        $needle = mb_strtolower($search);

        $employees = array_filter($allEmployees, function ($e) use ($needle, $roleFilter, $statusFilter) {
            if ($needle !== '' && !str_contains(mb_strtolower($e['name'] . ' ' . $e['email']), $needle)) {
                return false;
            }
            if ($roleFilter !== '' && strtolower((string) ($e['role_name'] ?? '')) !== $roleFilter) {
                return false;
            }
            if ($statusFilter !== '' && strtolower((string) ($e['status'] ?? '')) !== $statusFilter) {
                return false;
            }
            return true;
        });

        $pager = Paginator::make($employees);

        // Har row pe: kya yeh user is employee ko Edit / Delete kar sakta hai?
        $currentUserId = (int) ($_SESSION['user']['id'] ?? 0);
        $rows = [];
        foreach ($pager['items'] as $e) {
            $slug = strtolower(trim((string) ($e['role_name'] ?? '')));
            $slug = $slug === 'company admin' ? 'admin' : $slug;
            $e['can_edit'] = $this->canManageRole($slug);
            $e['can_delete'] = $this->canManageRole($slug) && (int) $e['id'] !== $currentUserId;
            $e['is_self'] = (int) $e['id'] === $currentUserId;
            $rows[] = $e;
        }

        // Manager ke dropdown mein "Admin" role hi nahi
        $assignableRoles = array_values(array_filter($roles, fn($r) => $this->isAdmin() || strtolower($r['name']) !== 'admin'));

        $success = $_SESSION['employee_success'] ?? null;
        $error = $_SESSION['employee_error'] ?? null;
        $old = $_SESSION['employee_old'] ?? [];

        unset($_SESSION['employee_success'], $_SESSION['employee_error'], $_SESSION['employee_old']);

        $this->view('employees/index', [
            'employees' => $rows,
            'pager' => $pager,
            'stats' => $stats,
            'roles' => $roles,
            'assignableRoles' => $assignableRoles,
            'search' => $search,
            'roleFilter' => $roleFilter,
            'statusFilter' => $statusFilter,
            'success' => $success,
            'error' => $error,
            'old' => $old
        ]);
    }

    public function store(): void
    {
        $this->roleModel->ensureDefaultSetup();

        $name = trim($_POST['name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';
        $roleId = (int) ($_POST['role_id'] ?? 0);
        $status = $_POST['status'] ?? 'active';

        $_SESSION['employee_old'] = compact('name', 'email', 'roleId', 'status');

        if ($name === '' || $email === '' || $password === '' || $roleId <= 0) {
            $this->backWithError('Please fill in all required fields.');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->backWithError('Please enter a valid email address.');
        }
        if (strlen($password) < 6) {
            $this->backWithError('Password must be at least 6 characters long.');
        }
        if (!in_array($status, ['active', 'inactive'], true)) {
            $this->backWithError('Invalid employee status.');
        }
        if (!$this->roleModel->exists($roleId)) {
            $this->backWithError('Please select a valid role.');
        }
        if (!$this->canManageRole($this->roleModel->slugOf($roleId))) {
            $this->backWithError('Only an Admin can create another Admin account.');
        }
        if ($this->userModel->emailExists($email)) {
            $this->backWithError('An employee with this email already exists.');
        }

        try {
            $created = $this->userModel->create([
                'role_id' => $roleId,
                'name' => $name,
                'email' => $email,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'status' => $status
            ]);

            if ($created) {
                unset($_SESSION['employee_old']);
                $_SESSION['employee_success'] = 'Employee created successfully.';
            } else {
                $_SESSION['employee_error'] = 'Employee could not be created.';
            }
        } catch (PDOException $e) {
            error_log('Employee create failed: ' . $e->getMessage());
            $_SESSION['employee_error'] = 'Employee could not be created. Please try again.';
        }

        $this->redirect($this->employeesUrl());
    }

    public function update(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';
        $roleId = (int) ($_POST['role_id'] ?? 0);
        $status = $_POST['status'] ?? 'active';

        if ($id <= 0) {
            $this->backWithError('Invalid employee.');
        }

        $employee = $this->userModel->findByIdAndCompany($id);
        if (!$employee) {
            $this->backWithError('Employee not found.');
        }
        if ($name === '' || $email === '' || $roleId <= 0) {
            $this->backWithError('Name, email and role are required.');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->backWithError('Please enter a valid email address.');
        }
        if ($password !== '' && strlen($password) < 6) {
            $this->backWithError('New password must be at least 6 characters long.');
        }
        if (!in_array($status, ['active', 'inactive'], true)) {
            $this->backWithError('Invalid employee status.');
        }
        if (!$this->roleModel->exists($roleId)) {
            $this->backWithError('Please select a valid role.');
        }

        // Manager: na kisi Admin ko badal sakta hai, na kisi ko Admin bana sakta hai
        if (!$this->canManageRole($this->roleModel->slugOf((int) $employee['role_id']))) {
            $this->backWithError('Only an Admin can edit an Admin account.');
        }
        if (!$this->canManageRole($this->roleModel->slugOf($roleId))) {
            $this->backWithError('Only an Admin can give the Admin role.');
        }

        // Apna role / status khud nahi badal sakte (galti se khud ko bahar na kar do)
        if ($id === (int) ($_SESSION['user']['id'] ?? 0)) {
            if ($roleId !== (int) $employee['role_id']) {
                $this->backWithError('You cannot change your own role.');
            }
            if ($status !== 'active') {
                $this->backWithError('You cannot make your own account inactive.');
            }
        }

        if ($this->userModel->emailExists($email, $id)) {
            $this->backWithError('Another employee already uses this email.');
        }

        try {
            $updated = $this->userModel->updateEmployee($id, [
                'role_id' => $roleId,
                'name' => $name,
                'email' => $email,
                'status' => $status,
                'password_hash' => $password !== '' ? password_hash($password, PASSWORD_DEFAULT) : null
            ]);

            $_SESSION['employee_success'] = $updated ? 'Employee updated successfully.' : 'Employee was not updated.';
        } catch (PDOException $e) {
            error_log('Employee update failed: ' . $e->getMessage());
            $_SESSION['employee_error'] = 'Employee could not be updated. Please try again.';
        }

        $this->redirect($this->employeesUrl());
    }

    public function delete(): void
    {
        $id = (int) ($_POST['id'] ?? 0);

        if ($id <= 0) {
            $this->backWithError('Invalid employee.');
        }
        // Galti se apna hi account delete na ho
        if ($id === (int) ($_SESSION['user']['id'] ?? 0)) {
            $this->backWithError('You cannot delete your own account.');
        }

        $employee = $this->userModel->findByIdAndCompany($id);
        if (!$employee) {
            $this->backWithError('Employee not found.');
        }
        if (!$this->canManageRole($this->roleModel->slugOf((int) $employee['role_id']))) {
            $this->backWithError('Only an Admin can delete an Admin account.');
        }

        try {
            $deleted = $this->userModel->deleteEmployee($id);
            $_SESSION['employee_success'] = $deleted ? 'Employee deleted successfully.' : 'Employee could not be deleted.';
        } catch (PDOException $e) {
            error_log('Employee delete failed: ' . $e->getMessage());
            $_SESSION['employee_error'] = 'Employee could not be deleted. It may be linked to other records.';
        }

        $this->redirect($this->employeesUrl());
    }

    private function employeesUrl(): string
    {
        return '/employees';
    }

    private function backWithError(string $message): void
    {
        $_SESSION['employee_error'] = $message;
        $this->redirect($this->employeesUrl());
    }
}