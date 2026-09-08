<?php

$title = 'Employee Management';

$avatarClasses = [
    'blue' => 'bg-blue-100 text-blue-600',
    'green' => 'bg-green-100 text-green-600',
    'orange' => 'bg-orange-100 text-orange-600',
    'pink' => 'bg-pink-100 text-pink-600',
];

$roleClasses = [
    'Admin' => 'bg-purple-50 text-purple-700',
    'Manager' => 'bg-blue-50 text-blue-700',
    'Warehouse Staff' => 'bg-orange-50 text-orange-700',
];

$totalEmployees = count($employees);

?>

<div class="space-y-5">

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">

        <div>

            <div class="flex items-center gap-2">

                <h1 class="text-2xl font-bold text-gray-900">
                    Employee Management
                </h1>

                <span class="text-sm bg-gray-100 text-gray-600 px-2 py-1 rounded-md">
                    <?= $totalEmployees ?>
                </span>

            </div>

            <p class="text-sm text-gray-500 mt-1">
                Manage your team members and their roles.
            </p>

        </div>

        <button
            type="button"
            onclick="openEmployeeModal()"
            class="inline-flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-lg text-sm font-medium transition"
        >

            <svg
                class="w-4 h-4"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M12 5v14M5 12h14"
                />
            </svg>

            Add Employee

        </button>

    </div>


    <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">

        <div class="p-4 border-b border-gray-200">

            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">

                <div class="relative w-full lg:w-80">

                    <svg
                        class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M21 21l-4.35-4.35m2.35-5.65a8 8 0 11-16 0 8 8 0 0116 0z"
                        />
                    </svg>

                    <input
                        type="text"
                        id="employeeSearch"
                        placeholder="Search employees..."
                        class="w-full pl-9 pr-4 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >

                </div>


                <div class="flex flex-wrap gap-2">

                    <select
                        id="roleFilter"
                        class="px-3 py-2 border border-gray-200 rounded-lg text-sm text-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >

                        <option value="">
                            All Roles
                        </option>

                        <option value="Admin">
                            Admin
                        </option>

                        <option value="Manager">
                            Manager
                        </option>

                        <option value="Warehouse Staff">
                            Warehouse Staff
                        </option>

                    </select>


                    <select
                        id="statusFilter"
                        class="px-3 py-2 border border-gray-200 rounded-lg text-sm text-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >

                        <option value="">
                            All Status
                        </option>

                        <option value="active">
                            Active
                        </option>

                        <option value="inactive">
                            Inactive
                        </option>

                    </select>

                </div>

            </div>

        </div>


        <div class="overflow-x-auto">

            <table class="w-full text-sm">

                <thead class="bg-gray-50 border-b border-gray-200">

                    <tr>

                        <th class="text-left px-5 py-3 font-semibold text-gray-500">
                            Employee
                        </th>

                        <th class="text-left px-5 py-3 font-semibold text-gray-500">
                            Email
                        </th>

                        <th class="text-left px-5 py-3 font-semibold text-gray-500">
                            Role
                        </th>

                        <th class="text-left px-5 py-3 font-semibold text-gray-500">
                            Status
                        </th>

                        <th class="text-left px-5 py-3 font-semibold text-gray-500">
                            Joined Date
                        </th>

                        <th class="text-right px-5 py-3 font-semibold text-gray-500">
                            Actions
                        </th>

                    </tr>

                </thead>


                <tbody
                    id="employeeTableBody"
                    class="divide-y divide-gray-100"
                >

                    <?php foreach ($employees as $employee): ?>

                        <?php

                        $name = $employee['name'] ?? '';

                        $email = $employee['email'] ?? '';

                        $role = $employee['role'] ?? '';

                        $status = strtolower(
                            $employee['status'] ?? 'inactive'
                        );

                        $createdAt = $employee['created_at'] ?? '';

                        $date = !empty($createdAt)
                            ? date(
                                'd M Y',
                                strtotime($createdAt)
                            )
                            : '-';

                      
                        $nameParts = preg_split(
                            '/\s+/',
                            trim($name)
                        );

                        $initials = '';

                        foreach ($nameParts as $part) {

                            if ($part !== '') {

                                $initials .= strtoupper(
                                    substr($part, 0, 1)
                                );

                            }

                        }

                        $initials = substr(
                            $initials,
                            0,
                            2
                        );

                        $avatarKeys = [
                            'blue',
                            'green',
                            'orange',
                            'pink'
                        ];

                        $avatarKey =
                            $avatarKeys[
                                ((int) $employee['id']) % 4
                            ] ?? 'blue';

                        $avatarClass =
                            $avatarClasses[$avatarKey];

                        $roleClass =
                            $roleClasses[$role]
                            ?? 'bg-gray-50 text-gray-700';

                        $isActive =
                            $status === 'active';

                        $statusTextClass =
                            $isActive
                            ? 'text-green-600'
                            : 'text-red-600';

                        $statusDotClass =
                            $isActive
                            ? 'bg-green-500'
                            : 'bg-red-500';

                        ?>

                        <tr
                            class="employee-row hover:bg-gray-50 transition"
                            data-id="<?= (int) $employee['id'] ?>"
                            data-name="<?= htmlspecialchars(
                                strtolower($name),
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                            data-email="<?= htmlspecialchars(
                                strtolower($email),
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                            data-role="<?= htmlspecialchars(
                                $role,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                            data-status="<?= htmlspecialchars(
                                $status,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                        >

                            <td class="px-5 py-4">

                                <div class="flex items-center gap-3">

                                    <div
                                        class="w-9 h-9 rounded-full flex items-center justify-center <?= $avatarClass ?>"
                                    >

                                        <span class="text-sm font-semibold">
                                            <?= htmlspecialchars(
                                                $initials,
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </span>

                                    </div>

                                    <span class="font-medium text-gray-800">

                                        <?= htmlspecialchars(
                                            $name,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>

                                    </span>

                                </div>

                            </td>


                            <td class="px-5 py-4 text-gray-500">

                                <?= htmlspecialchars(
                                    $email,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                            </td>


                            <td class="px-5 py-4">

                                <span
                                    class="px-2.5 py-1 rounded-md text-xs font-medium <?= $roleClass ?>"
                                >

                                    <?= htmlspecialchars(
                                        $role,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </span>

                            </td>


                            <td class="px-5 py-4">

                                <span
                                    class="inline-flex items-center gap-1.5 text-xs font-medium <?= $statusTextClass ?>"
                                >

                                    <span
                                        class="w-2 h-2 rounded-full <?= $statusDotClass ?>"
                                    ></span>

                                    <?= ucfirst(
                                        htmlspecialchars(
                                            $status,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        )
                                    ) ?>

                                </span>

                            </td>


                            <td class="px-5 py-4 text-gray-500">

                                <?= htmlspecialchars(
                                    $date,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                            </td>


                            <td class="px-5 py-4">

                                <div class="flex items-center justify-end gap-2">

                                    <button
                                        type="button"
                                        onclick="openEditModal(<?= (int) $employee['id'] ?>)"
                                        class="px-3 py-1.5 border border-gray-200 rounded-md text-xs font-medium text-gray-600 hover:bg-gray-50"
                                    >
                                        Edit
                                    </button>

                                    <button
                                        type="button"
                                        onclick="deleteEmployee(<?= (int) $employee['id'] ?>)"
                                        class="px-3 py-1.5 border border-red-200 rounded-md text-xs font-medium text-red-600 hover:bg-red-50"
                                    >
                                        Delete
                                    </button>

                                </div>

                            </td>

                        </tr>

                    <?php endforeach; ?>


                    <?php if (empty($employees)): ?>

                        <tr>

                            <td
                                colspan="6"
                                class="px-5 py-12 text-center text-gray-500"
                            >
                                No employees found.
                            </td>

                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>


        <div class="px-5 py-4 border-t border-gray-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">

            <p class="text-sm text-gray-500">

                Showing

                <span
                    id="visibleEmployeeCount"
                    class="font-medium text-gray-700"
                >
                    <?= $totalEmployees ?>
                </span>

                employees

            </p>

        </div>

    </div>

</div>


<div
    id="employeeModal"
    class="fixed inset-0 z-50 hidden items-center justify-center p-4"
>

    <div
        onclick="closeEmployeeModal()"
        class="absolute inset-0 bg-black/40 backdrop-blur-sm"
    ></div>


    <div
        class="relative bg-white rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto"
    >

        <div class="flex items-center justify-between px-6 py-5 border-b border-gray-200">

            <div>

                <h2
                    id="modalTitle"
                    class="text-lg font-bold text-gray-900"
                >
                    Create Employee
                </h2>

                <p
                    class="text-sm text-gray-500 mt-1"
                >
                    Add a new employee to your company.
                </p>

            </div>


            <button
                type="button"
                onclick="closeEmployeeModal()"
                class="w-8 h-8 rounded-lg hover:bg-gray-100 flex items-center justify-center"
            >

                <svg
                    class="w-5 h-5 text-gray-500"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >

                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M6 18L18 6M6 6l12 12"
                    />

                </svg>

            </button>

        </div>


        <form
            id="employeeForm"
            method="POST"
            action="/finovo-oms-and-wms/public/index.php/employees/create"
            class="p-6 space-y-5"
        >

            <input
                type="hidden"
                name="employee_id"
                id="employeeId"
                value=""
            >


            <div>

                <label
                    class="block text-sm font-medium text-gray-700 mb-1.5"
                >
                    Full Name
                </label>

                <input
                    type="text"
                    name="name"
                    id="employeeName"
                    required
                    placeholder="Enter employee name"
                    class="w-full px-3.5 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                >

            </div>


            <div>

                <label
                    class="block text-sm font-medium text-gray-700 mb-1.5"
                >
                    Email Address
                </label>

                <input
                    type="email"
                    name="email"
                    id="employeeEmail"
                    required
                    placeholder="employee@example.com"
                    class="w-full px-3.5 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                >

            </div>


            <div id="passwordField">

                <label
                    class="block text-sm font-medium text-gray-700 mb-1.5"
                >
                    Password
                </label>

                <input
                    type="password"
                    name="password"
                    id="employeePassword"
                    placeholder="Enter temporary password"
                    class="w-full px-3.5 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                >

                <p class="text-xs text-gray-400 mt-1.5">
                    The employee will use this password to login.
                </p>

            </div>


            <div>

                <label
                    class="block text-sm font-medium text-gray-700 mb-1.5"
                >
                    Role
                </label>

                <select
    name="role"
    id="employeeRole"
    required
    onchange="updatePermissions()"
    class="w-full px-3.5 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
>
    <option value="">
        Select employee role
    </option>

    <option value="Admin">
        Admin
    </option>

    <option value="Manager">
        Manager
    </option>

    <option value="Warehouse Staff">
        Warehouse Staff
    </option>
</select>

            </div>


            <div>

                <div class="flex items-center justify-between mb-2">

                    <label
                        class="block text-sm font-medium text-gray-700"
                    >
                        Permissions
                    </label>

                    <button
                        type="button"
                        onclick="togglePermissions()"
                        id="permissionsToggleButton"
                        class="text-xs text-blue-600 hover:text-blue-700 font-medium"
                    >
                        Select all
                    </button>

                </div>


                <div class="border border-gray-200 rounded-lg overflow-hidden">

                    <div class="px-4 py-3 bg-gray-50 border-b border-gray-200">

                        <p class="text-xs text-gray-500">
                            Select the permissions this employee should have.
                        </p>

                    </div>


                    <div
                        id="permissionsList"
                        class="p-4 grid grid-cols-1 sm:grid-cols-2 gap-3"
                    >

                        <label class="flex items-center gap-2 cursor-pointer">

                            <input
                                type="checkbox"
                                name="permissions[]"
                                value="manage_employees"
                                class="permission-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                            >

                            <span class="text-sm text-gray-700">
                                Manage Employees
                            </span>

                        </label>


                        <label class="flex items-center gap-2 cursor-pointer">

                            <input
                                type="checkbox"
                                name="permissions[]"
                                value="manage_products"
                                class="permission-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                            >

                            <span class="text-sm text-gray-700">
                                Manage Products
                            </span>

                        </label>


                        <label class="flex items-center gap-2 cursor-pointer">

                            <input
                                type="checkbox"
                                name="permissions[]"
                                value="manage_inventory"
                                class="permission-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                            >

                            <span class="text-sm text-gray-700">
                                Manage Inventory
                            </span>

                        </label>


                        <label class="flex items-center gap-2 cursor-pointer">

                            <input
                                type="checkbox"
                                name="permissions[]"
                                value="create_orders"
                                class="permission-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                            >

                            <span class="text-sm text-gray-700">
                                Create Orders
                            </span>

                        </label>


                        <label class="flex items-center gap-2 cursor-pointer">

                            <input
                                type="checkbox"
                                name="permissions[]"
                                value="confirm_orders"
                                class="permission-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                            >

                            <span class="text-sm text-gray-700">
                                Confirm Orders
                            </span>

                        </label>


                        <label class="flex items-center gap-2 cursor-pointer">

                            <input
                                type="checkbox"
                                name="permissions[]"
                                value="pick_pack"
                                class="permission-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                            >

                            <span class="text-sm text-gray-700">
                                Pick & Pack
                            </span>

                        </label>


                        <label class="flex items-center gap-2 cursor-pointer">

                            <input
                                type="checkbox"
                                name="permissions[]"
                                value="dispatch_orders"
                                class="permission-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                            >

                            <span class="text-sm text-gray-700">
                                Dispatch Orders
                            </span>

                        </label>


                        <label class="flex items-center gap-2 cursor-pointer">

                            <input
                                type="checkbox"
                                name="permissions[]"
                                value="manage_invoices"
                                class="permission-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                            >

                            <span class="text-sm text-gray-700">
                                Manage Invoices
                            </span>

                        </label>


                        <label class="flex items-center gap-2 cursor-pointer">

                            <input
                                type="checkbox"
                                name="permissions[]"
                                value="manage_payments"
                                class="permission-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                            >

                            <span class="text-sm text-gray-700">
                                Manage Payments
                            </span>

                        </label>


                        <label class="flex items-center gap-2 cursor-pointer">

                            <input
                                type="checkbox"
                                name="permissions[]"
                                value="view_reports"
                                class="permission-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                            >

                            <span class="text-sm text-gray-700">
                                View Reports
                            </span>

                        </label>

                    </div>

                </div>

            </div>


            <div>

                <label
                    class="block text-sm font-medium text-gray-700 mb-2"
                >
                    Account Status
                </label>

                <div class="flex gap-4">

                    <label class="flex items-center gap-2 cursor-pointer">

                        <input
                            type="radio"
                            name="status"
                            value="active"
                            checked
                            class="text-blue-600 focus:ring-blue-500"
                        >

                        <span class="text-sm text-gray-700">
                            Active
                        </span>

                    </label>


                    <label class="flex items-center gap-2 cursor-pointer">

                        <input
                            type="radio"
                            name="status"
                            value="inactive"
                            class="text-blue-600 focus:ring-blue-500"
                        >

                        <span class="text-sm text-gray-700">
                            Inactive
                        </span>

                    </label>

                </div>

            </div>


            <div class="flex justify-end gap-3 pt-3 border-t border-gray-100">

                <button
                    type="button"
                    onclick="closeEmployeeModal()"
                    class="px-4 py-2.5 border border-gray-200 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-50"
                >
                    Cancel
                </button>


                <button
                    id="submitEmployeeButton"
                    type="submit"
                    class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium"
                >
                    Create Employee
                </button>

            </div>

        </form>

    </div>

</div>


<script>

const rolePermissions = {

    Admin: [
        'manage_employees',
        'manage_products',
        'manage_inventory',
        'create_orders',
        'confirm_orders',
        'pick_pack',
        'dispatch_orders',
        'manage_invoices',
        'manage_payments',
        'view_reports'
    ],

    Manager: [
        'manage_products',
        'manage_inventory',
        'create_orders',
        'confirm_orders',
        'pick_pack',
        'dispatch_orders',
        'manage_invoices',
        'manage_payments',
        'view_reports'
    ],

    'Warehouse Staff': [
        'manage_inventory',
        'pick_pack',
        'dispatch_orders',
        'view_reports'
    ]

};



function openEmployeeModal()
{
    const modal =
        document.getElementById('employeeModal');

    const form =
        document.getElementById('employeeForm');

    form.reset();

    document.getElementById('modalTitle').textContent =
        'Create Employee';

    document.getElementById('submitEmployeeButton').textContent =
        'Create Employee';

    form.action =
        '/finovo-oms-and-wms/public/index.php/employees/create';

    document.getElementById('employeeId').value = '';


    const passwordField =
        document.getElementById('passwordField');

    passwordField.classList.remove('hidden');

    document
        .querySelectorAll('.permission-checkbox')
        .forEach(checkbox => {
            checkbox.checked = false;
        });

    document.getElementById(
        'permissionsToggleButton'
    ).textContent = 'Select all';

    modal.classList.remove('hidden');

    modal.classList.add('flex');

    document.body.classList.add('overflow-hidden');
}

function closeEmployeeModal()
{
    const modal =
        document.getElementById('employeeModal');

    modal.classList.add('hidden');

    modal.classList.remove('flex');

    document.body.classList.remove('overflow-hidden');
}

function updatePermissions()
{
    const roleElement =
        document.getElementById('employeeRole');

    const role =
        roleElement.value;

    const permissions =
        rolePermissions[role] || [];

    document
        .querySelectorAll('.permission-checkbox')
        .forEach(checkbox => {

            checkbox.checked =
                permissions.includes(
                    checkbox.value
                );

        });

    updateSelectAllButton();
}


/*
|--------------------------------------------------------------------------
| Select / Unselect All Permissions
|--------------------------------------------------------------------------
*/
function togglePermissions()
{
    const checkboxes =
        document.querySelectorAll(
            '.permission-checkbox'
        );

    if (checkboxes.length === 0) {
        return;
    }

    const allChecked =
        [...checkboxes].every(
            checkbox => checkbox.checked
        );

    checkboxes.forEach(checkbox => {

        checkbox.checked = !allChecked;

    });

    updateSelectAllButton();
}


/*
|--------------------------------------------------------------------------
| Update Select All Button Text
|--------------------------------------------------------------------------
*/
function updateSelectAllButton()
{
    const checkboxes =
        document.querySelectorAll(
            '.permission-checkbox'
        );

    const button =
        document.getElementById(
            'permissionsToggleButton'
        );

    if (!button || checkboxes.length === 0) {
        return;
    }

    const allChecked =
        [...checkboxes].every(
            checkbox => checkbox.checked
        );

    button.textContent =
        allChecked
            ? 'Unselect all'
            : 'Select all';
}


/*
|--------------------------------------------------------------------------
| Open Edit Modal
|--------------------------------------------------------------------------
*/
function openEditModal(id)
{
    const row =
        document.querySelector(
            `.employee-row[data-id="${id}"]`
        );

    if (!row) {
        alert('Employee not found.');
        return;
    }

    /*
     * Get employee data from table row
     */
    const name =
        row.dataset.name || '';

    const email =
        row.dataset.email || '';

    const role =
        row.dataset.role || '';

    const status =
        row.dataset.status || 'active';


    const modal =
        document.getElementById(
            'employeeModal'
        );

    const form =
        document.getElementById(
            'employeeForm'
        );


    /*
     * Edit mode
     */
    document.getElementById(
        'modalTitle'
    ).textContent =
        'Edit Employee';


    document.getElementById(
        'submitEmployeeButton'
    ).textContent =
        'Update Employee';


    /*
     * Update form action
     */
    form.action =
        '/finovo-oms-and-wms/public/index.php/employees/update';


    /*
     * Set employee ID
     */
    document.getElementById(
        'employeeId'
    ).value = id;


    /*
     * Fill employee information
     */
    document.getElementById(
        'employeeName'
    ).value = name;


    document.getElementById(
        'employeeEmail'
    ).value = email;


    document.getElementById(
        'employeeRole'
    ).value = role;


    /*
     * Set status
     */
    document
        .querySelectorAll(
            'input[name="status"]'
        )
        .forEach(radio => {

            radio.checked =
                radio.value === status;

        });


    /*
     * Password is optional in edit
     */
    const passwordField =
        document.getElementById(
            'passwordField'
        );

    passwordField.classList.add(
        'hidden'
    );


    /*
     * Update role permissions
     */
    updatePermissions();


    /*
     * Show modal
     */
    modal.classList.remove(
        'hidden'
    );

    modal.classList.add(
        'flex'
    );

    document.body.classList.add(
        'overflow-hidden'
    );
}


/*
|--------------------------------------------------------------------------
| Delete Employee
|--------------------------------------------------------------------------
*/
function deleteEmployee(id)
{
    const confirmed =
        confirm(
            'Are you sure you want to delete this employee?'
        );

    if (!confirmed) {
        return;
    }


    /*
     * Create POST form dynamically
     */
    const form =
        document.createElement(
            'form'
        );

    form.method = 'POST';

    form.action =
        '/finovo-oms-and-wms/public/index.php/employees/delete';


    /*
     * Employee ID
     */
    const input =
        document.createElement(
            'input'
        );

    input.type = 'hidden';

    input.name = 'employee_id';

    input.value = id;


    form.appendChild(input);

    document.body.appendChild(form);

    form.submit();
}


/*
|--------------------------------------------------------------------------
| Search / Filters
|--------------------------------------------------------------------------
*/
const searchInput =
    document.getElementById(
        'employeeSearch'
    );

const roleFilter =
    document.getElementById(
        'roleFilter'
    );

const statusFilter =
    document.getElementById(
        'statusFilter'
    );


function filterEmployees()
{
    const search =
        searchInput.value.toLowerCase().trim();

    const role =
        roleFilter.value;

    const status =
        statusFilter.value;


    const rows =
        document.querySelectorAll(
            '.employee-row'
        );


    let visibleCount = 0;


    rows.forEach(row => {

        const name =
            row.dataset.name || '';

        const email =
            row.dataset.email || '';

        const rowRole =
            row.dataset.role || '';

        const rowStatus =
            row.dataset.status || '';


        const matchesSearch =
            name.includes(search) ||
            email.includes(search);


        const matchesRole =
            role === '' ||
            rowRole === role;


        const matchesStatus =
            status === '' ||
            rowStatus === status;


        const visible =
            matchesSearch &&
            matchesRole &&
            matchesStatus;


        row.classList.toggle(
            'hidden',
            !visible
        );


        if (visible) {
            visibleCount++;
        }

    });


    document.getElementById(
        'visibleEmployeeCount'
    ).textContent = visibleCount;
}


/*
|--------------------------------------------------------------------------
| Search Events
|--------------------------------------------------------------------------
*/
if (searchInput) {

    searchInput.addEventListener(
        'input',
        filterEmployees
    );

}


if (roleFilter) {

    roleFilter.addEventListener(
        'change',
        filterEmployees
    );

}


if (statusFilter) {

    statusFilter.addEventListener(
        'change',
        filterEmployees
    );

}


/*
|--------------------------------------------------------------------------
| Close Modal When Clicking Outside
|--------------------------------------------------------------------------
*/
const employeeModal =
    document.getElementById(
        'employeeModal'
    );

if (employeeModal) {

    employeeModal.addEventListener(
        'click',
        function(event) {

            if (event.target === this) {

                closeEmployeeModal();

            }

        }
    );

}


/*
|--------------------------------------------------------------------------
| Permission Checkbox Events
|--------------------------------------------------------------------------
*/
document
    .querySelectorAll('.permission-checkbox')
    .forEach(checkbox => {

        checkbox.addEventListener(
            'change',
            updateSelectAllButton
        );

    });

</script>