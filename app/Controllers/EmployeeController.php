
<?php

require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../Models/Employee.php';

class EmployeeController extends Controller
{
    public function index(): void
    {
        $employeeModel = new Employee();

        $employees = $employeeModel->all(1);

        $this->view('employees/index', [
            'employees' => $employees
        ]);
    }
}

