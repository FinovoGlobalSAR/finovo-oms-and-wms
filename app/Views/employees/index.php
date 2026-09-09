<?php

$title = 'Employee Management';

$employees = $employees ?? [];
$roles = $roles ?? [];
$success = $success ?? null;
$error = $error ?? null;
$old = $old ?? [];

$totalEmployees = count($employees);

$baseUrl = '/finovo-oms-and-wms/public/index.php';

function employeeInitials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name));

    $initials = '';

    foreach (array_slice($parts, 0, 2) as $part) {

        if ($part !== '') {
            $initials .= strtoupper(
                substr($part, 0, 1)
            );
        }
    }

    return $initials ?: 'U';
}
?>

<div class="space-y-6">

    <!-- Header -->
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
                Create employees, assign roles and manage your team.
            </p>

        </div>

        <button
            type="button"
            onclick="openEmployeeModal()"
            class="inline-flex items-center justify-center gap-2
                   bg-blue-600 hover:bg-blue-700
                   text-white px-4 py-2.5
                   rounded-lg text-sm font-medium">

            <span class="text-lg">+</span>

            Add Employee

        </button>

    </div>


    <!-- Success Message -->

    <?php if ($success): ?>

        <div class="bg-green-50 border border-green-200
                    text-green-700 px-4 py-3 rounded-lg text-sm">

            <?= htmlspecialchars($success) ?>

        </div>

    <?php endif; ?>


    <!-- Error Message -->

    <?php if ($error): ?>

        <div class="bg-red-50 border border-red-200
                    text-red-700 px-4 py-3 rounded-lg text-sm">

            <?= htmlspecialchars($error) ?>

        </div>

    <?php endif; ?>


    <!-- Employee Table -->

    <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">

        <!-- Search + Filters -->

        <div class="p-4 border-b border-gray-200">

            <div class="flex flex-col lg:flex-row
                        lg:items-center lg:justify-between
                        gap-3">

                <div class="w-full lg:w-80">

                    <input
                        id="employeeSearch"
                        type="text"
                        placeholder="Search employee..."
                        class="w-full px-4 py-2
                               border border-gray-200
                               rounded-lg text-sm
                               focus:outline-none
                               focus:ring-2
                               focus:ring-blue-500">

                </div>


                <div class="flex gap-2">

                    <select
                        id="roleFilter"
                        class="px-3 py-2
                               border border-gray-200
                               rounded-lg text-sm">

                        <option value="">
                            All Roles
                        </option>

                        <?php foreach ($roles as $role): ?>

                            <option value="<?= htmlspecialchars(
                                strtolower($role['name'])
                            ) ?>">

                                <?= htmlspecialchars($role['name']) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>


                    <select
                        id="statusFilter"
                        class="px-3 py-2
                               border border-gray-200
                               rounded-lg text-sm">

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


        <!-- Table -->

        <div class="overflow-x-auto">

            <table class="w-full text-sm">

                <thead class="bg-gray-50 border-b border-gray-200">

                    <tr>

                        <th class="text-left px-5 py-3 text-gray-500">
                            Employee
                        </th>

                        <th class="text-left px-5 py-3 text-gray-500">
                            Email
                        </th>

                        <th class="text-left px-5 py-3 text-gray-500">
                            Role
                        </th>

                        <th class="text-left px-5 py-3 text-gray-500">
                            Status
                        </th>

                        <th class="text-left px-5 py-3 text-gray-500">
                            Joined
                        </th>

                        <th class="text-right px-5 py-3 text-gray-500">
                            Actions
                        </th>

                    </tr>

                </thead>


                <tbody class="divide-y divide-gray-100">


                <?php if (empty($employees)): ?>

                    <tr>

                        <td
                            colspan="6"
                            class="px-5 py-12 text-center text-gray-500">

                            No employees found.

                        </td>

                    </tr>


                <?php else: ?>


                    <?php foreach ($employees as $employee): ?>


                        <?php

                        $status = strtolower(
                            $employee['status'] ?? 'inactive'
                        );

                        $roleName =
                            $employee['role_name']
                            ?? 'No Role';

                        $isActive =
                            $status === 'active';

                        ?>


                        <tr
                            class="employee-row hover:bg-gray-50"
                            data-name="<?= htmlspecialchars(
                                strtolower($employee['name'])
                            ) ?>"
                            data-email="<?= htmlspecialchars(
                                strtolower($employee['email'])
                            ) ?>"
                            data-role="<?= htmlspecialchars(
                                strtolower($roleName)
                            ) ?>"
                            data-status="<?= htmlspecialchars($status) ?>">


                            <!-- Employee -->

                            <td class="px-5 py-4">

                                <div class="flex items-center gap-3">

                                    <div class="w-10 h-10
                                                bg-blue-100
                                                text-blue-600
                                                rounded-full
                                                flex items-center
                                                justify-center
                                                font-semibold">

                                        <?= htmlspecialchars(
                                            employeeInitials(
                                                $employee['name']
                                            )
                                        ) ?>

                                    </div>

                                    <div>

                                        <p class="font-medium text-gray-900">

                                            <?= htmlspecialchars(
                                                $employee['name']
                                            ) ?>

                                        </p>

                                    </div>

                                </div>

                            </td>


                            <!-- Email -->

                            <td class="px-5 py-4 text-gray-500">

                                <?= htmlspecialchars(
                                    $employee['email']
                                ) ?>

                            </td>


                            <!-- Role -->

                            <td class="px-5 py-4">

                                <span class="px-2.5 py-1
                                             rounded-md text-xs
                                             font-medium
                                             bg-purple-50
                                             text-purple-700">

                                    <?= htmlspecialchars($roleName) ?>

                                </span>

                            </td>


                            <!-- Status -->

                            <td class="px-5 py-4">

                                <?php if ($isActive): ?>

                                    <span class="text-green-600 text-xs font-medium">
                                        ● Active
                                    </span>

                                <?php else: ?>

                                    <span class="text-red-600 text-xs font-medium">
                                        ● Inactive
                                    </span>

                                <?php endif; ?>

                            </td>


                            <!-- Created At -->

                            <td class="px-5 py-4 text-gray-500">

                                <?= !empty($employee['created_at'])
                                    ? htmlspecialchars(
                                        date(
                                            'd M Y',
                                            strtotime(
                                                $employee['created_at']
                                            )
                                        )
                                    )
                                    : '-'
                                ?>

                            </td>


                            <!-- Actions -->

                            <td class="px-5 py-4">

                                <div class="flex justify-end gap-2">


                                    <!-- Edit -->

                                    <button
                                        type="button"

                                        onclick='openEditModal(
                                            <?= json_encode(
                                                [
                                                    'id' =>
                                                        (int) $employee['id'],

                                                    'name' =>
                                                        $employee['name'],

                                                    'email' =>
                                                        $employee['email'],

                                                    'role_id' =>
                                                        (int) $employee['role_id'],

                                                    'status' =>
                                                        $status
                                                ],
                                                JSON_HEX_APOS |
                                                JSON_HEX_QUOT
                                            ) ?>
                                        )'

                                        class="px-3 py-1.5
                                               border border-gray-200
                                               rounded-md text-xs
                                               hover:bg-gray-50">

                                        Edit

                                    </button>


                                    <!-- Delete -->

                                    <form
                                        method="POST"
                                        action="<?= $baseUrl ?>/employees/delete"

                                        onsubmit="
                                            return confirm(
                                                'Are you sure you want to delete this employee?'
                                            );
                                        ">

                                        <input
                                            type="hidden"
                                            name="id"
                                            value="<?= (int) $employee['id'] ?>">

                                        <button
                                            type="submit"
                                            class="px-3 py-1.5
                                                   border border-red-200
                                                   text-red-600
                                                   rounded-md text-xs
                                                   hover:bg-red-50">

                                            Delete

                                        </button>

                                    </form>

                                </div>

                            </td>

                        </tr>


                    <?php endforeach; ?>


                <?php endif; ?>


                </tbody>

            </table>

        </div>

    </div>

</div>



<!-- ============================================ -->
<!-- ADD EMPLOYEE MODAL -->
<!-- ============================================ -->

<div
    id="employeeModal"
    class="fixed inset-0 z-50
           hidden items-center justify-center
           p-4">


    <div
        onclick="closeEmployeeModal()"
        class="absolute inset-0 bg-black/40">
    </div>


    <div
        class="relative
               bg-white
               rounded-2xl
               shadow-xl
               w-full
               max-w-lg">


        <!-- Modal Header -->

        <div class="flex items-center justify-between
                    px-6 py-5
                    border-b border-gray-200">

            <div>

                <h2 class="text-lg font-bold text-gray-900">
                    Add Employee
                </h2>

                <p class="text-sm text-gray-500">
                    Create a new employee account.
                </p>

            </div>


            <button
                type="button"
                onclick="closeEmployeeModal()"
                class="text-xl text-gray-500">

                &times;

            </button>

        </div>


        <!-- Add Form -->

        <form
            method="POST"
            action="<?= $baseUrl ?>/employees/store"
            class="p-6 space-y-5">


            <!-- Name -->

            <div>

                <label class="block text-sm font-medium mb-1">
                    Full Name *
                </label>

                <input
                    required
                    type="text"
                    name="name"

                    value="<?= htmlspecialchars(
                        $old['name'] ?? ''
                    ) ?>"

                    class="w-full px-3.5 py-2.5
                           border border-gray-200
                           rounded-lg">

            </div>


            <!-- Email -->

            <div>

                <label class="block text-sm font-medium mb-1">
                    Email *
                </label>

                <input
                    required
                    type="email"
                    name="email"

                    value="<?= htmlspecialchars(
                        $old['email'] ?? ''
                    ) ?>"

                    class="w-full px-3.5 py-2.5
                           border border-gray-200
                           rounded-lg">

            </div>


            <!-- Password -->

            <div>

                <label class="block text-sm font-medium mb-1">
                    Password *
                </label>

                <input
                    required
                    minlength="6"
                    type="password"
                    name="password"

                    class="w-full px-3.5 py-2.5
                           border border-gray-200
                           rounded-lg">

            </div>


            <!-- Role -->

            <div>

                <label class="block text-sm font-medium mb-1">
                    Role *
                </label>

                <select
                    required
                    name="role_id"

                    class="w-full px-3.5 py-2.5
                           border border-gray-200
                           rounded-lg">

                    <option value="">
                        Select Role
                    </option>


                    <?php foreach ($roles as $role): ?>

                        <option

                            value="<?= (int) $role['id'] ?>"

                            <?= (
                                (int) ($old['role_id'] ?? 0)
                                ===
                                (int) $role['id']
                            )
                                ? 'selected'
                                : ''
                            ?>>

                            <?= htmlspecialchars(
                                $role['name']
                            ) ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <!-- Status -->

            <div>

                <label class="block text-sm font-medium mb-2">
                    Status
                </label>

                <div class="flex gap-5">


                    <label class="flex gap-2 items-center">

                        <input
                            type="radio"
                            name="status"
                            value="active"

                            <?= (
                                ($old['status'] ?? 'active')
                                ===
                                'active'
                            )
                                ? 'checked'
                                : ''
                            ?>>

                        Active

                    </label>


                    <label class="flex gap-2 items-center">

                        <input
                            type="radio"
                            name="status"
                            value="inactive"

                            <?= (
                                ($old['status'] ?? '')
                                ===
                                'inactive'
                            )
                                ? 'checked'
                                : ''
                            ?>>

                        Inactive

                    </label>

                </div>

            </div>


            <!-- Buttons -->

            <div
                class="flex justify-end gap-3
                       pt-4 border-t">


                <button
                    type="button"
                    onclick="closeEmployeeModal()"
                    class="px-4 py-2
                           border rounded-lg">

                    Cancel

                </button>


                <button
                    type="submit"
                    class="px-5 py-2
                           bg-blue-600
                           text-white
                           rounded-lg">

                    Create Employee

                </button>

            </div>

        </form>

    </div>

</div>



<!-- ============================================ -->
<!-- EDIT EMPLOYEE MODAL -->
<!-- ============================================ -->

<div
    id="editEmployeeModal"
    class="fixed inset-0 z-50
           hidden items-center justify-center
           p-4">


    <div
        onclick="closeEditModal()"
        class="absolute inset-0 bg-black/40">
    </div>


    <div
        class="relative
               bg-white
               rounded-2xl
               shadow-xl
               w-full
               max-w-lg">


        <div
            class="flex items-center justify-between
                   px-6 py-5 border-b">


            <h2 class="text-lg font-bold">
                Edit Employee
            </h2>


            <button
                onclick="closeEditModal()"
                type="button"
                class="text-xl">

                &times;

            </button>

        </div>


        <form
            method="POST"
            action="<?= $baseUrl ?>/employees/update"
            class="p-6 space-y-5">


            <input
                type="hidden"
                id="edit_id"
                name="id">


            <!-- Name -->

            <div>

                <label class="block text-sm font-medium mb-1">
                    Full Name *
                </label>

                <input
                    required
                    id="edit_name"
                    type="text"
                    name="name"

                    class="w-full px-3.5 py-2.5
                           border rounded-lg">

            </div>


            <!-- Email -->

            <div>

                <label class="block text-sm font-medium mb-1">
                    Email *
                </label>

                <input
                    required
                    id="edit_email"
                    type="email"
                    name="email"

                    class="w-full px-3.5 py-2.5
                           border rounded-lg">

            </div>


            <!-- Password -->

            <div>

                <label class="block text-sm font-medium mb-1">
                    New Password
                </label>

                <input
                    minlength="6"
                    type="password"
                    name="password"

                    placeholder="Leave blank to keep existing password"

                    class="w-full px-3.5 py-2.5
                           border rounded-lg">

            </div>


            <!-- Role -->

            <div>

                <label class="block text-sm font-medium mb-1">
                    Role *
                </label>

                <select
                    required
                    id="edit_role_id"
                    name="role_id"

                    class="w-full px-3.5 py-2.5
                           border rounded-lg">


                    <?php foreach ($roles as $role): ?>

                        <option
                            value="<?= (int) $role['id'] ?>">

                            <?= htmlspecialchars(
                                $role['name']
                            ) ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <!-- Status -->

            <div>

                <label class="block text-sm font-medium mb-2">
                    Status
                </label>

                <div class="flex gap-5">


                    <label class="flex gap-2 items-center">

                        <input
                            id="edit_status_active"
                            type="radio"
                            name="status"
                            value="active">

                        Active

                    </label>


                    <label class="flex gap-2 items-center">

                        <input
                            id="edit_status_inactive"
                            type="radio"
                            name="status"
                            value="inactive">

                        Inactive

                    </label>

                </div>

            </div>


            <!-- Buttons -->

            <div
                class="flex justify-end gap-3
                       pt-4 border-t">


                <button
                    type="button"
                    onclick="closeEditModal()"
                    class="px-4 py-2 border rounded-lg">

                    Cancel

                </button>


                <button
                    type="submit"
                    class="px-5 py-2
                           bg-blue-600
                           text-white
                           rounded-lg">

                    Save Changes

                </button>

            </div>

        </form>

    </div>

</div>



<script>

/*
|--------------------------------------------------------------------------
| Add Employee Modal
|--------------------------------------------------------------------------
*/

function openEmployeeModal()
{
    const modal =
        document.getElementById('employeeModal');

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



/*
|--------------------------------------------------------------------------
| Edit Employee Modal
|--------------------------------------------------------------------------
*/

function openEditModal(employee)
{
    document.getElementById(
        'edit_id'
    ).value = employee.id;

    document.getElementById(
        'edit_name'
    ).value = employee.name;

    document.getElementById(
        'edit_email'
    ).value = employee.email;

    document.getElementById(
        'edit_role_id'
    ).value = employee.role_id;


    document.getElementById(
        'edit_status_active'
    ).checked =
        employee.status === 'active';


    document.getElementById(
        'edit_status_inactive'
    ).checked =
        employee.status === 'inactive';


    const modal =
        document.getElementById(
            'editEmployeeModal'
        );

    modal.classList.remove('hidden');
    modal.classList.add('flex');

    document.body.classList.add(
        'overflow-hidden'
    );
}


function closeEditModal()
{
    const modal =
        document.getElementById(
            'editEmployeeModal'
        );

    modal.classList.add('hidden');
    modal.classList.remove('flex');

    document.body.classList.remove(
        'overflow-hidden'
    );
}



/*
|--------------------------------------------------------------------------
| Employee Search / Filters
|--------------------------------------------------------------------------
*/

function filterEmployees()
{
    const search =
        document
            .getElementById('employeeSearch')
            .value
            .toLowerCase()
            .trim();


    const role =
        document
            .getElementById('roleFilter')
            .value;


    const status =
        document
            .getElementById('statusFilter')
            .value;


    const rows =
        document.querySelectorAll(
            '.employee-row'
        );


    rows.forEach(row => {

        const matchesSearch =
            !search ||
            row.dataset.name.includes(search) ||
            row.dataset.email.includes(search);


        const matchesRole =
            !role ||
            row.dataset.role === role;


        const matchesStatus =
            !status ||
            row.dataset.status === status;


        row.style.display =
            matchesSearch &&
            matchesRole &&
            matchesStatus
                ? ''
                : 'none';

    });
}


document
    .getElementById('employeeSearch')
    ?.addEventListener(
        'input',
        filterEmployees
    );


document
    .getElementById('roleFilter')
    ?.addEventListener(
        'change',
        filterEmployees
    );


document
    .getElementById('statusFilter')
    ?.addEventListener(
        'change',
        filterEmployees
    );


<?php if ($error && !empty($old)): ?>

openEmployeeModal();

<?php endif; ?>

</script>