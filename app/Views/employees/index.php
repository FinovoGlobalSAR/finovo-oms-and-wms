<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
        <script src="https://cdn.tailwindcss.com"></script>

</head>
<body>
    <?php
$title = 'Employee Management';
?>

<div class="space-y-5">

    <!-- Heading -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">

        <div>
            <div class="flex items-center gap-2">

                <h1 class="text-2xl font-bold text-gray-900">
                    Employee Management
                </h1>

                <span class="text-sm bg-gray-100 text-gray-600 px-2 py-1 rounded-md">
                    24
                </span>

            </div>

            <p class="text-sm text-gray-500 mt-1">
                Manage your team members and their roles.
            </p>
        </div>


        <button
            onclick="openEmployeeModal()"
            class="inline-flex items-center justify-center gap-2
                   bg-blue-600 hover:bg-blue-700 text-white
                   px-4 py-2.5 rounded-lg text-sm font-medium transition"
        >

            <svg class="w-4 h-4"
                 fill="none"
                 stroke="currentColor"
                 viewBox="0 0 24 24">

                <path stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M12 5v14M5 12h14"/>
            </svg>

            Add Employee

        </button>

    </div>


    <!-- Main table card -->
    <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">

        <!-- Toolbar -->
        <div class="p-4 border-b border-gray-200">

            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">

                <!-- Search -->
                <div class="relative w-full lg:w-80">

                    <svg
                        class="absolute left-3 top-1/2 -translate-y-1/2
                               w-4 h-4 text-gray-400"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24">

                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              stroke-width="2"
                              d="M21 21l-4.35-4.35m2.35-5.65a8 8 0 11-16 0 8 8 0 0116 0z"/>

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


        <!-- Table -->
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


                    <!-- Employee 1 -->
                    <tr class="hover:bg-gray-50 transition">

                        <td class="px-5 py-4">

                            <div class="flex items-center gap-3">

                                <div class="w-9 h-9 rounded-full bg-blue-100
                                            flex items-center justify-center">

                                    <span class="text-sm font-semibold text-blue-600">
                                        LS
                                    </span>

                                </div>

                                <span class="font-medium text-gray-800">
                                    Liam Smith
                                </span>

                            </div>

                        </td>


                        <td class="px-5 py-4 text-gray-500">
                            liam@example.com
                        </td>


                        <td class="px-5 py-4">

                            <span class="px-2.5 py-1 rounded-md
                                         bg-purple-50 text-purple-700 text-xs font-medium">

                                Manager

                            </span>

                        </td>


                        <td class="px-5 py-4">

                            <span class="inline-flex items-center gap-1.5
                                         text-xs font-medium text-green-600">

                                <span class="w-2 h-2 bg-green-500 rounded-full"></span>

                                Active

                            </span>

                        </td>


                        <td class="px-5 py-4 text-gray-500">
                            24 Jun 2024
                        </td>


                        <td class="px-5 py-4">

                            <div class="flex items-center justify-end gap-2">

                                <button
                                    onclick="openEditModal('Liam Smith')"
                                    class="px-3 py-1.5 border border-gray-200
                                           rounded-md text-xs font-medium
                                           text-gray-600 hover:bg-gray-50"
                                >
                                    Edit
                                </button>

                                <button
                                    class="px-3 py-1.5 border border-red-200
                                           rounded-md text-xs font-medium
                                           text-red-600 hover:bg-red-50"
                                >
                                    Delete
                                </button>

                            </div>

                        </td>

                    </tr>


                    <!-- Employee 2 -->
                    <tr class="hover:bg-gray-50 transition">

                        <td class="px-5 py-4">

                            <div class="flex items-center gap-3">

                                <div class="w-9 h-9 rounded-full bg-green-100
                                            flex items-center justify-center">

                                    <span class="text-sm font-semibold text-green-600">
                                        NA
                                    </span>

                                </div>

                                <span class="font-medium text-gray-800">
                                    Noah Anderson
                                </span>

                            </div>

                        </td>

                        <td class="px-5 py-4 text-gray-500">
                            noah@example.com
                        </td>

                        <td class="px-5 py-4">

                            <span class="px-2.5 py-1 rounded-md
                                         bg-blue-50 text-blue-700 text-xs font-medium">

                                Sales

                            </span>

                        </td>

                        <td class="px-5 py-4">

                            <span class="inline-flex items-center gap-1.5
                                         text-xs font-medium text-green-600">

                                <span class="w-2 h-2 bg-green-500 rounded-full"></span>

                                Active

                            </span>

                        </td>

                        <td class="px-5 py-4 text-gray-500">
                            15 Mar 2024
                        </td>

                        <td class="px-5 py-4">

                            <div class="flex items-center justify-end gap-2">

                                <button
                                    onclick="openEditModal('Noah Anderson')"
                                    class="px-3 py-1.5 border border-gray-200
                                           rounded-md text-xs font-medium
                                           text-gray-600 hover:bg-gray-50"
                                >
                                    Edit
                                </button>

                                <button
                                    class="px-3 py-1.5 border border-red-200
                                           rounded-md text-xs font-medium
                                           text-red-600 hover:bg-red-50"
                                >
                                    Delete
                                </button>

                            </div>

                        </td>

                    </tr>


                    <!-- Employee 3 -->
                    <tr class="hover:bg-gray-50 transition">

                        <td class="px-5 py-4">

                            <div class="flex items-center gap-3">

                                <div class="w-9 h-9 rounded-full bg-orange-100
                                            flex items-center justify-center">

                                    <span class="text-sm font-semibold text-orange-600">
                                        SA
                                    </span>

                                </div>

                                <span class="font-medium text-gray-800">
                                    Sophia Adams
                                </span>

                            </div>

                        </td>

                        <td class="px-5 py-4 text-gray-500">
                            sophia@example.com
                        </td>

                        <td class="px-5 py-4">

                            <span class="px-2.5 py-1 rounded-md
                                         bg-orange-50 text-orange-700 text-xs font-medium">

                                Warehouse

                            </span>

                        </td>

                        <td class="px-5 py-4">

                            <span class="inline-flex items-center gap-1.5
                                         text-xs font-medium text-red-600">

                                <span class="w-2 h-2 bg-red-500 rounded-full"></span>

                                Inactive

                            </span>

                        </td>

                        <td class="px-5 py-4 text-gray-500">
                            10 Apr 2024
                        </td>

                        <td class="px-5 py-4">

                            <div class="flex items-center justify-end gap-2">

                                <button
                                    onclick="openEditModal('Sophia Adams')"
                                    class="px-3 py-1.5 border border-gray-200
                                           rounded-md text-xs font-medium
                                           text-gray-600 hover:bg-gray-50"
                                >
                                    Edit
                                </button>

                                <button
                                    class="px-3 py-1.5 border border-red-200
                                           rounded-md text-xs font-medium
                                           text-red-600 hover:bg-red-50"
                                >
                                    Delete
                                </button>

                            </div>

                        </td>

                    </tr>


                    <!-- Employee 4 -->
                    <tr class="hover:bg-gray-50 transition">

                        <td class="px-5 py-4">

                            <div class="flex items-center gap-3">

                                <div class="w-9 h-9 rounded-full bg-pink-100
                                            flex items-center justify-center">

                                    <span class="text-sm font-semibold text-pink-600">
                                        WC
                                    </span>

                                </div>

                                <span class="font-medium text-gray-800">
                                    William Clark
                                </span>

                            </div>

                        </td>

                        <td class="px-5 py-4 text-gray-500">
                            william@example.com
                        </td>

                        <td class="px-5 py-4">

                            <span class="px-2.5 py-1 rounded-md
                                         bg-indigo-50 text-indigo-700 text-xs font-medium">

                                Admin

                            </span>

                        </td>

                        <td class="px-5 py-4">

                            <span class="inline-flex items-center gap-1.5
                                         text-xs font-medium text-green-600">

                                <span class="w-2 h-2 bg-green-500 rounded-full"></span>

                                Active

                            </span>

                        </td>

                        <td class="px-5 py-4 text-gray-500">
                            28 Feb 2024
                        </td>

                        <td class="px-5 py-4">

                            <div class="flex items-center justify-end gap-2">

                                <button
                                    onclick="openEditModal('William Clark')"
                                    class="px-3 py-1.5 border border-gray-200
                                           rounded-md text-xs font-medium
                                           text-gray-600 hover:bg-gray-50"
                                >
                                    Edit
                                </button>

                                <button
                                    class="px-3 py-1.5 border border-red-200
                                           rounded-md text-xs font-medium
                                           text-red-600 hover:bg-red-50"
                                >
                                    Delete
                                </button>

                            </div>

                        </td>

                    </tr>


                </tbody>

            </table>

        </div>


        <!-- Pagination -->
        <div class="px-5 py-4 border-t border-gray-200
                    flex flex-col sm:flex-row sm:items-center
                    sm:justify-between gap-3">

            <p class="text-sm text-gray-500">
                Showing
                <span class="font-medium text-gray-700">1</span>
                to
                <span class="font-medium text-gray-700">4</span>
                of
                <span class="font-medium text-gray-700">24</span>
                employees
            </p>


            <div class="flex items-center gap-1">

                <button
                    class="px-3 py-1.5 border border-gray-200 rounded-md
                           text-sm text-gray-400"
                >
                    Previous
                </button>

                <button
                    class="px-3 py-1.5 rounded-md bg-blue-600 text-white text-sm"
                >
                    1
                </button>

                <button
                    class="px-3 py-1.5 border border-gray-200 rounded-md
                           text-sm text-gray-600 hover:bg-gray-50"
                >
                    2
                </button>

                <button
                    class="px-3 py-1.5 border border-gray-200 rounded-md
                           text-sm text-gray-600 hover:bg-gray-50"
                >
                    3
                </button>

                <button
                    class="px-3 py-1.5 border border-gray-200 rounded-md
                           text-sm text-gray-600 hover:bg-gray-50"
                >
                    Next
                </button>

            </div>

        </div>

    </div>

</div>


<!-- ========================================================= -->
<!-- CREATE EMPLOYEE MODAL -->
<!-- ========================================================= -->

<div
    id="employeeModal"
    class="fixed inset-0 z-50 hidden items-center justify-center p-4"
>

    <!-- Overlay -->
    <div
        onclick="closeEmployeeModal()"
        class="absolute inset-0 bg-black/40 backdrop-blur-sm"
    ></div>


    <!-- Modal -->
    <div
        class="relative bg-white rounded-2xl shadow-xl
               w-full max-w-lg max-h-[90vh] overflow-y-auto"
    >

        <!-- Header -->
        <div class="flex items-center justify-between
                    px-6 py-5 border-b border-gray-200">

            <div>

                <h2 class="text-lg font-bold text-gray-900">
                    Create Employee
                </h2>

                <p class="text-sm text-gray-500 mt-1">
                    Add a new employee to your company.
                </p>

            </div>

            <button
                onclick="closeEmployeeModal()"
                class="w-8 h-8 rounded-lg hover:bg-gray-100
                       flex items-center justify-center"
            >

                <svg class="w-5 h-5 text-gray-500"
                     fill="none"
                     stroke="currentColor"
                     viewBox="0 0 24 24">

                    <path stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M6 18L18 6M6 6l12 12"/>

                </svg>

            </button>

        </div>


        <!-- Form -->
        <form class="p-6 space-y-5">


            <!-- Full name -->
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


            <!-- Email -->
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


            <!-- Password -->
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


            <!-- Role -->
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


            <!-- Role information -->
            <div class="bg-blue-50 border border-blue-100 rounded-lg p-4">

                <div class="flex gap-3">

                    <div class="mt-0.5">

                        <svg class="w-5 h-5 text-blue-600"
                             fill="none"
                             stroke="currentColor"
                             viewBox="0 0 24 24">

                            <path stroke-linecap="round"
                                  stroke-linejoin="round"
                                  stroke-width="1.8"
                                  d="M13 16h-1v-4h-1m1-8h.01M12 20a8 8 0 100-16 8 8 0 000 16z"/>

                        </svg>

                    </div>

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


            <!-- Status -->
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


            <!-- Buttons -->
            <div class="flex justify-end gap-3 pt-3 border-t border-gray-100">

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
</body>
</html>