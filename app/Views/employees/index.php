<?php

$title = 'Employee Management';

$employees = [
    [
        'name' => 'Liam Smith',
        'email' => 'liam@example.com',
        'role' => 'Manager',
        'status' => 'Active',
        'date' => '24 Jun 2024',
        'initials' => 'LS',
        'avatar' => 'blue',
        'role_color' => 'purple',
    ],
   
];  

$avatarClasses = [
    'blue' => 'bg-blue-100 text-blue-600',
    'green' => 'bg-green-100 text-green-600',
    'orange' => 'bg-orange-100 text-orange-600',
    'pink' => 'bg-pink-100 text-pink-600',
];

$roleClasses = [
    'purple' => 'bg-purple-50 text-purple-700',
    'blue' => 'bg-blue-50 text-blue-700',
    'orange' => 'bg-orange-50 text-orange-700',
    'indigo' => 'bg-indigo-50 text-indigo-700',
];

$totalEmployees = 24;

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
            onclick="openEmployeeModal()"
            type="button"
            class="inline-flex items-center justify-center gap-2
                   bg-blue-600 hover:bg-blue-700 text-white
                   px-4 py-2.5 rounded-lg text-sm font-medium transition"
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
                        class="absolute left-3 top-1/2 -translate-y-1/2
                               w-4 h-4 text-gray-400"
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
                        placeholder="Search employees..."
                        class="w-full pl-9 pr-4 py-2 border border-gray-200
                               rounded-lg text-sm focus:outline-none
                               focus:ring-2 focus:ring-blue-500"
                    >

                </div>


                <!-- Filters -->

                <div class="flex flex-wrap gap-2">

                    <select
                        class="px-3 py-2 border border-gray-200 rounded-lg
                               text-sm text-gray-600 focus:outline-none
                               focus:ring-2 focus:ring-blue-500"
                    >
                        <option>All Roles</option>
                        <option>Admin</option>
                        <option>Manager</option>
                        <option>Sales</option>
                        <option>Warehouse</option>
                    </select>


                    <select
                        class="px-3 py-2 border border-gray-200 rounded-lg
                               text-sm text-gray-600 focus:outline-none
                               focus:ring-2 focus:ring-blue-500"
                    >
                        <option>All Status</option>
                        <option>Active</option>
                        <option>Inactive</option>
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


                <tbody class="divide-y divide-gray-100">


                    <?php foreach ($employees as $employee): ?>

                        <?php
                        $avatarClass = $avatarClasses[$employee['avatar']] ?? 'bg-gray-100 text-gray-600';
                        $roleClass = $roleClasses[$employee['role_color']] ?? 'bg-gray-50 text-gray-700';

                        $isActive = $employee['status'] === 'Active';

                        $statusTextClass = $isActive
                            ? 'text-green-600'
                            : 'text-red-600';

                        $statusDotClass = $isActive
                            ? 'bg-green-500'
                            : 'bg-red-500';
                        ?>



                        <tr class="hover:bg-gray-50 transition">



                            <td class="px-5 py-4">

                                <div class="flex items-center gap-3">

                                    <div
                                        class="w-9 h-9 rounded-full
                                               flex items-center justify-center
                                               <?= $avatarClass ?>"
                                    >

                                        <span class="text-sm font-semibold">
                                            <?= htmlspecialchars($employee['initials']) ?>
                                        </span>

                                    </div>


                                    <span class="font-medium text-gray-800">
                                        <?= htmlspecialchars($employee['name']) ?>
                                    </span>

                                </div>

                            </td>



                            <td class="px-5 py-4 text-gray-500">

                                <?= htmlspecialchars($employee['email']) ?>

                            </td>



                            <td class="px-5 py-4">

                                <span
                                    class="px-2.5 py-1 rounded-md
                                           text-xs font-medium
                                           <?= $roleClass ?>"
                                >

                                    <?= htmlspecialchars($employee['role']) ?>

                                </span>

                            </td>



                            <td class="px-5 py-4">

                                <span
                                    class="inline-flex items-center gap-1.5
                                           text-xs font-medium
                                           <?= $statusTextClass ?>"
                                >

                                    <span
                                        class="w-2 h-2 rounded-full
                                               <?= $statusDotClass ?>"
                                    ></span>

                                    <?= htmlspecialchars($employee['status']) ?>

                                </span>

                            </td>



                            <td class="px-5 py-4 text-gray-500">

                                <?= htmlspecialchars($employee['date']) ?>

                            </td>



                            <td class="px-5 py-4">

                                <div class="flex items-center justify-end gap-2">

                                    <button
                                        type="button"
                                        onclick="openEditModal('<?= htmlspecialchars($employee['name'], ENT_QUOTES) ?>')"
                                        class="px-3 py-1.5 border border-gray-200
                                               rounded-md text-xs font-medium
                                               text-gray-600 hover:bg-gray-50"
                                    >
                                        Edit
                                    </button>


                                    <button
                                        type="button"
                                        class="px-3 py-1.5 border border-red-200
                                               rounded-md text-xs font-medium
                                               text-red-600 hover:bg-red-50"
                                    >
                                        Delete
                                    </button>

                                </div>

                            </td>

                        </tr>


                    <?php endforeach; ?>


                </tbody>

            </table>

        </div>

        <div
            class="px-5 py-4 border-t border-gray-200
                   flex flex-col sm:flex-row sm:items-center
                   sm:justify-between gap-3"
        >

            <p class="text-sm text-gray-500">

                Showing

                <span class="font-medium text-gray-700">
                    1
                </span>

                to

                <span class="font-medium text-gray-700">
                    <?= count($employees) ?>
                </span>

                of

                <span class="font-medium text-gray-700">
                    <?= $totalEmployees ?>
                </span>

                employees

            </p>


            <div class="flex items-center gap-1">

                <button
                    type="button"
                    class="px-3 py-1.5 border border-gray-200
                           rounded-md text-sm text-gray-400"
                >
                    Previous
                </button>


                <button
                    type="button"
                    class="px-3 py-1.5 rounded-md
                           bg-blue-600 text-white text-sm"
                >
                    1
                </button>


                <button
                    type="button"
                    class="px-3 py-1.5 border border-gray-200
                           rounded-md text-sm text-gray-600 hover:bg-gray-50"
                >
                    2
                </button>


                <button
                    type="button"
                    class="px-3 py-1.5 border border-gray-200
                           rounded-md text-sm text-gray-600 hover:bg-gray-50"
                >
                    3
                </button>


                <button
                    type="button"
                    class="px-3 py-1.5 border border-gray-200
                           rounded-md text-sm text-gray-600 hover:bg-gray-50"
                >
                    Next
                </button>

            </div>

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
        class="relative bg-white rounded-2xl shadow-xl
               w-full max-w-lg max-h-[90vh] overflow-y-auto"
    >



        <div
            class="flex items-center justify-between
                   px-6 py-5 border-b border-gray-200"
        >

            <div>

                <h2 class="text-lg font-bold text-gray-900">
                    Create Employee
                </h2>

                <p class="text-sm text-gray-500 mt-1">
                    Add a new employee to your company.
                </p>

            </div>


            <button
                type="button"
                onclick="closeEmployeeModal()"
                class="w-8 h-8 rounded-lg hover:bg-gray-100
                       flex items-center justify-center"
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
                        d="M6 18L18 6M6 6l12-12"
                    />
                </svg>

            </button>

        </div>



        <form class="p-6 space-y-5">



            <div>

                <label class="block text-sm font-medium text-gray-700 mb-1.5">
                    Full Name
                </label>

                <input
                    type="text"
                    name="name"
                    placeholder="Enter employee name"
                    class="w-full px-3.5 py-2.5 border border-gray-200
                           rounded-lg text-sm focus:outline-none
                           focus:ring-2 focus:ring-blue-500"
                >

            </div>



            <div>

                <label class="block text-sm font-medium text-gray-700 mb-1.5">
                    Email Address
                </label>

                <input
                    type="email"
                    name="email"
                    placeholder="employee@example.com"
                    class="w-full px-3.5 py-2.5 border border-gray-200
                           rounded-lg text-sm focus:outline-none
                           focus:ring-2 focus:ring-blue-500"
                >

            </div>



            <div>

                <label class="block text-sm font-medium text-gray-700 mb-1.5">
                    Password
                </label>

                <input
                    type="password"
                    name="password"
                    placeholder="Enter temporary password"
                    class="w-full px-3.5 py-2.5 border border-gray-200
                           rounded-lg text-sm focus:outline-none
                           focus:ring-2 focus:ring-blue-500"
                >

                <p class="text-xs text-gray-400 mt-1.5">
                    The employee will use this password to login.
                </p>

            </div>



            <div>

                <label class="block text-sm font-medium text-gray-700 mb-1.5">
                    Role
                </label>

                <select
                    name="role"
                    class="w-full px-3.5 py-2.5 border border-gray-200
                           rounded-lg text-sm focus:outline-none
                           focus:ring-2 focus:ring-blue-500"
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

                    <option value="Sales">
                        Sales
                    </option>

                    <option value="Warehouse">
                        Warehouse
                    </option>

                </select>

            </div>



            <div class="bg-blue-50 border border-blue-100 rounded-lg p-4">

                <div class="flex gap-3">

                    <svg
                        class="w-5 h-5 text-blue-600 mt-0.5"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.8"
                            d="M13 16h-1v-4h-1m1-8h.01M12 20a8 8 0 100-16 8 8 0 000 16z"
                        />
                    </svg>

                    <div>

                        <p class="text-sm font-medium text-blue-800">
                            Role-based permissions
                        </p>

                        <p class="text-xs text-blue-600 mt-1 leading-relaxed">
                            The selected role determines which features
                            and actions this employee can access.
                        </p>

                    </div>

                </div>

            </div>



            <div>

                <label class="block text-sm font-medium text-gray-700 mb-2">
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



            <div
                class="flex justify-end gap-3 pt-3 border-t border-gray-100"
            >

                <button
                    type="button"
                    onclick="closeEmployeeModal()"
                    class="px-4 py-2.5 border border-gray-200
                           rounded-lg text-sm font-medium
                           text-gray-600 hover:bg-gray-50"
                >
                    Cancel
                </button>


                <button
                    type="submit"
                    class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700
                           text-white rounded-lg text-sm font-medium"
                >
                    Create Employee
                </button>

            </div>

        </form>

    </div>

</div>


<script>

function openEmployeeModal() {

    const modal = document.getElementById('employeeModal');

    modal.classList.remove('hidden');
    modal.classList.add('flex');

    document.body.classList.add('overflow-hidden');
}


function closeEmployeeModal() {

    const modal = document.getElementById('employeeModal');

    modal.classList.add('hidden');
    modal.classList.remove('flex');

    document.body.classList.remove('overflow-hidden');
}


function openEditModal(name) {

    alert('Edit employee: ' + name);

}

</script>