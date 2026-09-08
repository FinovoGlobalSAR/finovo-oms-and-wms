<?php

require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../Models/Employee.php';

class EmployeeController extends Controller
{

    public function index(): void
    {
        $employeeModel = new Employee();

        $employees = $employeeModel->all($this->companyId);

        $this->view('employees/index', [
            'employees' => $employees
        ]);
    }

    public function create(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectToEmployees();
        }

        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $roleName = trim($_POST['role'] ?? '');
        $status = $_POST['status'] ?? 'active';

        /*
         * Validate required fields
         */
        if (
            $name === '' ||
            $email === '' ||
            $password === '' ||
            $roleName === ''
        ) {
            die('Please fill all required fields.');
        }

        /*
         * Validate email
         */
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            die('Invalid email address.');
        }

        /*
         * Validate status
         */
        if (!in_array($status, ['active', 'inactive'], true)) {
            $status = 'active';
        }

        $employeeModel = new Employee();

        /*
         * Check duplicate email
         */
        if (
            $employeeModel->emailExists(
                $email,
                $this->companyId
            )
        ) {
            die('An employee with this email already exists.');
        }

        /*
         * Get role ID
         *
         * IMPORTANT:
         * getRoleId() ka result $roleId mein store karna zaroori hai.
         */
        $roleId = $employeeModel->getRoleId(
            $roleName,
            $this->companyId
        );

        if ($roleId === null) {
            die('Selected role does not exist.');
        }

        /*
         * Create employee
         */
        $created = $employeeModel->create(
            $this->companyId,
            $name,
            $email,
            $password,
            $roleId,
            $status
        );

        if (!$created) {
            die('Employee could not be created.');
        }

        /*
         * Back to employee page
         */
        $this->redirectToEmployees();
    }

    /**
     * Update employee
     */
    public function update(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectToEmployees();
        }

        $id = (int) ($_POST['employee_id'] ?? 0);

        $name = trim($_POST['name'] ?? '');

        $email = trim($_POST['email'] ?? '');

        $roleName = trim($_POST['role'] ?? '');

        $status = $_POST['status'] ?? 'active';

        /*
         * Password is optional during edit
         */
        $password = $_POST['password'] ?? '';

        /*
         * Validate
         */
        if (
            $id <= 0 ||
            $name === '' ||
            $email === '' ||
            $roleName === ''
        ) {
            die('Invalid employee data.');
        }

        /*
         * Validate email
         */
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            die('Invalid email address.');
        }

        /*
         * Validate status
         */
        if (!in_array($status, ['active', 'inactive'], true)) {
            $status = 'active';
        }

        $employeeModel = new Employee();

        /*
         * Check employee exists
         */
        $employee = $employeeModel->find(
            $id,
            $this->companyId
        );

        if (!$employee) {
            die('Employee not found.');
        }

        /*
         * Check duplicate email
         */
        if (
            $employeeModel->emailExists(
                $email,
                $this->companyId,
                $id
            )
        ) {
            die('Another employee already uses this email.');
        }

        /*
         * Get role ID
         *
         * IMPORTANT:
         * getRoleId() ka result $roleId mein store karna zaroori hai.
         */
        $roleId = $employeeModel->getRoleId(
            $roleName,
            $this->companyId
        );

        if ($roleId === null) {
            die('Selected role does not exist.');
        }

        /*
         * Update employee information
         */
        $updated = $employeeModel->update(
            $id,
            $this->companyId,
            $name,
            $email,
            $roleId,
            $status
        );

        if (!$updated) {
            die('Employee could not be updated.');
        }

        /*
         * Update password only if entered
         */
        if ($password !== '') {
            $employeeModel->updatePassword(
                $id,
                $this->companyId,
                $password
            );
        }

        /*
         * Back to employee page
         */
        $this->redirectToEmployees();
    }

    /**
     * Delete employee
     */
    public function delete(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectToEmployees();
        }

        $id = (int) ($_POST['employee_id'] ?? 0);

        if ($id <= 0) {
            die('Invalid employee ID.');
        }

        $employeeModel = new Employee();

        $deleted = $employeeModel->delete(
            $id,
            $this->companyId
        );

        if (!$deleted) {
            die('Employee could not be deleted or does not exist.');
        }

        /*
         * Back to employee page
         */
        $this->redirectToEmployees();
    }

    /**
     * Redirect helper
     */
    private function redirectToEmployees(): void
    {
        header(
            'Location: /finovo-oms-and-wms/public/index.php/employees'
        );

        exit;
    }
    
}