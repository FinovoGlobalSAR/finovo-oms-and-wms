<?php

$title = 'Employee Management';

$employees = $employees ?? [];
$roles = $roles ?? [];
$success = $success ?? null;
$error = $error ?? null;
$old = $old ?? [];

$baseUrl = '/finovo-oms-and-wms/public/index.php';

function employeeInitials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name));

    $initials = '';

    foreach (array_slice($parts, 0, 2) as $part) {
        if ($part !== '') {
            $initials .= strtoupper(substr($part, 0, 1));
        }
    }

    return $initials ?: 'U';
}

?>


<div class="bg-white border border-gray-200 rounded-xl overflow-hidden">


    <!-- =========================================================
         TOP VIEW TOOLBAR
    ========================================================== -->

    <div
        class="min-h-[54px]
               border-b border-gray-200
               px-4
               flex flex-col xl:flex-row
               xl:items-center
               xl:justify-between
               gap-3"
    >


        <!-- LEFT -->
        <div class="flex items-center gap-1 py-2">

            <!-- Table -->
            <button
                type="button"
                class="h-9 px-3
                       flex items-center gap-2
                       rounded-md
                       text-[13px]
                       font-medium
                       text-gray-800
                       bg-gray-100"
            >

                <svg
                    class="w-4 h-4 text-gray-500"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <rect
                        x="4"
                        y="4"
                        width="16"
                        height="16"
                        rx="2"
                        stroke-width="1.7"
                    />

                    <path
                        stroke-width="1.7"
                        d="M4 10h16M10 4v16"
                    />
                </svg>

                Table

            </button>


            <!-- Board -->
            <button
                type="button"
                class="h-9 px-3
                       flex items-center gap-2
                       rounded-md
                       text-[13px]
                       text-gray-500
                       hover:bg-gray-50
                       hover:text-gray-800"
            >

                <svg
                    class="w-4 h-4"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <rect
                        x="4"
                        y="4"
                        width="6"
                        height="16"
                        rx="1"
                        stroke-width="1.7"
                    />

                    <rect
                        x="14"
                        y="4"
                        width="6"
                        height="16"
                        rx="1"
                        stroke-width="1.7"
                    />
                </svg>

                Board

            </button>


            <!-- List -->
            <button
                type="button"
                class="h-9 px-3
                       flex items-center gap-2
                       rounded-md
                       text-[13px]
                       text-gray-500
                       hover:bg-gray-50
                       hover:text-gray-800"
            >

                <svg
                    class="w-4 h-4"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-width="1.7"
                        d="M9 6h11M9 12h11M9 18h11"
                    />

                    <circle cx="4.5" cy="6" r="1" fill="currentColor"/>
                    <circle cx="4.5" cy="12" r="1" fill="currentColor"/>
                    <circle cx="4.5" cy="18" r="1" fill="currentColor"/>
                </svg>

                List

            </button>

        </div>



        <!-- RIGHT -->
        <div class="flex flex-wrap items-center gap-1 py-2">


            <!-- Search -->
            <div class="relative">

                <svg
                    class="absolute
                           left-3 top-1/2
                           -translate-y-1/2
                           w-4 h-4
                           text-gray-400"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <circle
                        cx="11"
                        cy="11"
                        r="7"
                        stroke-width="1.7"
                    />

                    <path
                        stroke-width="1.7"
                        stroke-linecap="round"
                        d="m20 20-4-4"
                    />
                </svg>


                <input
                    id="employeeSearch"
                    type="text"
                    placeholder="Search"
                    class="w-[145px]
                           h-9
                           pl-9 pr-3
                           border-0
                           text-[13px]
                           text-gray-700
                           placeholder-gray-500
                           outline-none
                           focus:ring-0"
                >

            </div>



            <!-- Hide -->
            <button
                type="button"
                class="h-9 px-3
                       flex items-center gap-2
                       rounded-md
                       text-[13px]
                       text-gray-600
                       hover:bg-gray-50"
            >

                <svg
                    class="w-4 h-4 text-gray-500"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-width="1.7"
                        d="M4 6h16M7 12h10M10 18h4"
                    />
                </svg>

                Hide

            </button>



            <!-- Customize -->
            <button
                type="button"
                class="h-9 px-3
                       flex items-center gap-2
                       rounded-md
                       text-[13px]
                       text-gray-600
                       hover:bg-gray-50"
            >

                <svg
                    class="w-4 h-4"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <circle
                        cx="12"
                        cy="12"
                        r="8"
                        stroke-width="1.7"
                    />

                    <circle
                        cx="12"
                        cy="12"
                        r="2.5"
                        stroke-width="1.7"
                    />
                </svg>

                Customize

            </button>



            <button
                type="button"
                class="w-9 h-9
                       flex items-center justify-center
                       rounded-md
                       text-gray-500
                       hover:bg-gray-50"
            >
                •••
            </button>



            <!-- Export -->
            <button
                type="button"
                class="h-9 px-4
                       border border-gray-200
                       rounded-md
                       text-[13px]
                       font-medium
                       text-gray-600
                       bg-white
                       hover:bg-gray-50"
            >
                Export
            </button>



            <!-- Add User -->
            <button
                type="button"
                onclick="openEmployeeModal()"
                class="h-9 px-4
                       flex items-center gap-2
                       border border-gray-200
                       rounded-md
                       bg-white
                       text-[13px]
                       font-medium
                       text-gray-700
                       hover:bg-gray-50"
            >

                Add User

                <svg
                    class="w-3.5 h-3.5"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="1.8"
                        d="m6 9 6 6 6-6"
                    />
                </svg>

            </button>

        </div>

    </div>



    <!-- =========================================================
         FILTER BAR
    ========================================================== -->

    <div
        class="min-h-[50px]
               border-b border-gray-200
               px-4
               flex items-center
               gap-2"
    >


        <!-- Role -->
        <div class="relative">

            <select
                id="roleFilter"
                class="appearance-none
                       h-8
                       pl-9 pr-8
                       border border-gray-200
                       rounded-full
                       bg-white
                       text-[12px]
                       font-medium
                       text-gray-600
                       outline-none
                       cursor-pointer"
            >

                <option value="">
                    Role
                </option>

                <?php foreach ($roles as $role): ?>

                    <option
                        value="<?= htmlspecialchars(
                            strtolower($role['name'])
                        ) ?>"
                    >
                        <?= htmlspecialchars($role['name']) ?>
                    </option>

                <?php endforeach; ?>

            </select>


            <svg
                class="absolute
                       left-3 top-1/2
                       -translate-y-1/2
                       w-4 h-4
                       text-gray-400
                       pointer-events-none"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <circle
                    cx="12"
                    cy="8"
                    r="3"
                    stroke-width="1.7"
                />

                <path
                    stroke-linecap="round"
                    stroke-width="1.7"
                    d="M6 19c.7-3 2.7-5 6-5s5.3 2 6 5"
                />
            </svg>

        </div>



        <!-- Status -->
        <div class="relative">

            <select
                id="statusFilter"
                class="appearance-none
                       h-8
                       pl-9 pr-8
                       border border-gray-200
                       rounded-full
                       bg-white
                       text-[12px]
                       font-medium
                       text-gray-600
                       outline-none
                       cursor-pointer"
            >

                <option value="">
                    Status
                </option>

                <option value="active">
                    Active
                </option>

                <option value="inactive">
                    Inactive
                </option>

            </select>


            <svg
                class="absolute
                       left-3 top-1/2
                       -translate-y-1/2
                       w-4 h-4
                       text-gray-400
                       pointer-events-none"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <circle
                    cx="12"
                    cy="12"
                    r="8"
                    stroke-width="1.7"
                />

                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="1.7"
                    d="m9 12 2 2 4-4"
                />
            </svg>

        </div>



        <!-- Add filter -->
        <button
            type="button"
            class="h-8 px-2
                   flex items-center gap-1.5
                   text-[12px]
                   font-medium
                   text-gray-500
                   hover:text-gray-800"
        >

            <span class="text-lg leading-none">
                +
            </span>

            Add filter

        </button>


    </div>



    <!-- MESSAGES -->

    <?php if ($success): ?>

        <div
            class="mx-4 mt-4
                   px-4 py-3
                   rounded-lg
                   border border-green-200
                   bg-green-50
                   text-sm
                   text-green-700"
        >
            <?= htmlspecialchars($success) ?>
        </div>

    <?php endif; ?>


    <?php if ($error): ?>

        <div
            class="mx-4 mt-4
                   px-4 py-3
                   rounded-lg
                   border border-red-200
                   bg-red-50
                   text-sm
                   text-red-700"
        >
            <?= htmlspecialchars($error) ?>
        </div>

    <?php endif; ?>



    <!-- =========================================================
         TABLE
    ========================================================== -->

    <div class="overflow-x-auto">

        <table
            class="w-full
                   min-w-[1050px]
                   border-collapse
                   text-[13px]"
        >


            <!-- TABLE HEAD -->
            <thead>

                <tr
                    class="bg-gray-50/70
                           border-b border-gray-200
                           text-gray-500"
                >


                    <th
                        class="w-10
                               px-4 py-3
                               text-left
                               font-medium"
                    >

                        <input
                            type="checkbox"
                            class="w-4 h-4
                                   rounded
                                   border-gray-300"
                        >

                    </th>



                    <th
                        class="px-2 py-3
                               text-left
                               font-medium"
                    >

                        <div class="flex items-center gap-2">

                            <span
                                class="w-4 h-4
                                       rounded-full
                                       border border-gray-300
                                       flex items-center
                                       justify-center
                                       text-[9px]
                                       text-gray-400"
                            >
                                ◎
                            </span>

                            Full name

                        </div>

                    </th>



                    <th
                        class="px-3 py-3
                               text-left
                               font-medium"
                    >

                        <div class="flex items-center gap-1.5">

                            <span class="text-gray-400">
                                @
                            </span>

                            Email

                        </div>

                    </th>



                    <th
                        class="px-3 py-3
                               text-left
                               font-medium"
                    >

                        <div class="flex items-center gap-1.5">

                            <svg
                                class="w-4 h-4"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <circle
                                    cx="12"
                                    cy="8"
                                    r="3"
                                    stroke-width="1.7"
                                />

                                <path
                                    stroke-linecap="round"
                                    stroke-width="1.7"
                                    d="M6 19c.7-3 2.7-5 6-5s5.3 2 6 5"
                                />
                            </svg>

                            Role

                        </div>

                    </th>



                    <th
                        class="px-3 py-3
                               text-left
                               font-medium"
                    >

                        <div class="flex items-center gap-1.5">

                            <span>
                                ◈
                            </span>

                            Status

                        </div>

                    </th>



                    <th
                        class="px-3 py-3
                               text-left
                               font-medium"
                    >

                        <div class="flex items-center gap-1.5">

                            <svg
                                class="w-4 h-4"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <rect
                                    x="4"
                                    y="5"
                                    width="16"
                                    height="15"
                                    rx="2"
                                    stroke-width="1.7"
                                />

                                <path
                                    stroke-width="1.7"
                                    d="M8 3v4M16 3v4M4 10h16"
                                />
                            </svg>

                            Joined date

                        </div>

                    </th>



                    <th
                        class="px-3 py-3
                               text-left
                               font-medium"
                    >

                        <div class="flex items-center gap-1.5">

                            <span>
                                ◎
                            </span>

                            2F Auth

                        </div>

                    </th>



                    <th
                        class="px-3 py-3
                               text-left
                               font-medium"
                    >

                        <div class="flex items-center gap-1.5">

                            <span>
                                ◎
                            </span>

                            Actions

                        </div>

                    </th>


                </tr>

            </thead>



            <!-- TABLE BODY -->
            <tbody class="divide-y divide-gray-100">


                <?php if (empty($employees)): ?>


                    <tr>

                        <td
                            colspan="8"
                            class="py-16 text-center"
                        >

                            <div
                                class="w-11 h-11
                                       mx-auto
                                       rounded-full
                                       bg-gray-100
                                       flex items-center
                                       justify-center
                                       mb-3"
                            >

                                <svg
                                    class="w-5 h-5 text-gray-400"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="1.7"
                                        d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2"
                                    />

                                    <circle
                                        cx="9"
                                        cy="7"
                                        r="4"
                                        stroke-width="1.7"
                                    />
                                </svg>

                            </div>


                            <p
                                class="text-sm
                                       font-medium
                                       text-gray-700"
                            >
                                No employees found
                            </p>


                            <p
                                class="text-xs
                                       text-gray-400
                                       mt-1"
                            >
                                Add your first employee to get started.
                            </p>

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
                            ?? $employee['role']
                            ?? 'No Role';

                        $isActive =
                            $status === 'active';

                        ?>


                        <tr
                            class="employee-row
                                   hover:bg-gray-50/70
                                   transition"

                            data-name="<?= htmlspecialchars(
                                strtolower($employee['name'])
                            ) ?>"

                            data-email="<?= htmlspecialchars(
                                strtolower($employee['email'])
                            ) ?>"

                            data-role="<?= htmlspecialchars(
                                strtolower($roleName)
                            ) ?>"

                            data-status="<?= htmlspecialchars($status) ?>"
                        >


                            <!-- CHECKBOX -->
                            <td class="px-4 py-2.5">

                                <input
                                    type="checkbox"
                                    class="w-4 h-4
                                           rounded
                                           border-gray-300"
                                >

                            </td>



                            <!-- NAME -->
                            <td class="px-2 py-2.5">

                                <div
                                    class="flex items-center
                                           gap-2.5"
                                >


                                    <!-- Avatar -->
                                    <div
                                        class="w-7 h-7
                                               rounded-full
                                               bg-blue-100
                                               flex items-center
                                               justify-center
                                               shrink-0"
                                    >

                                        <span
                                            class="text-[10px]
                                                   font-semibold
                                                   text-blue-600"
                                        >

                                            <?= htmlspecialchars(
                                                employeeInitials(
                                                    $employee['name']
                                                )
                                            ) ?>

                                        </span>

                                    </div>


                                    <span
                                        class="font-medium
                                               text-gray-800
                                               whitespace-nowrap"
                                    >

                                        <?= htmlspecialchars(
                                            $employee['name']
                                        ) ?>

                                    </span>

                                </div>

                            </td>



                            <!-- EMAIL -->
                            <td
                                class="px-3 py-2.5
                                       text-gray-600"
                            >

                                <span
                                    class="border-b
                                           border-gray-400
                                           leading-5"
                                >

                                    <?= htmlspecialchars(
                                        $employee['email']
                                    ) ?>

                                </span>

                            </td>



                            <!-- ROLE -->
                            <td
                                class="px-3 py-2.5
                                       text-gray-600
                                       whitespace-nowrap"
                            >

                                <?= htmlspecialchars($roleName) ?>

                            </td>



                            <!-- STATUS -->
                            <td class="px-3 py-2.5">

                                <?php if ($isActive): ?>

                                    <span
                                        class="inline-flex
                                               items-center
                                               gap-1.5
                                               px-2 py-1
                                               rounded-md
                                               bg-gray-50
                                               border border-gray-200
                                               text-[12px]
                                               text-gray-600"
                                    >

                                        <span
                                            class="w-1.5 h-1.5
                                                   rounded-full
                                                   bg-emerald-500"
                                        ></span>

                                        Active

                                    </span>

                                <?php else: ?>

                                    <span
                                        class="inline-flex
                                               items-center
                                               gap-1.5
                                               px-2 py-1
                                               rounded-md
                                               bg-gray-50
                                               border border-gray-200
                                               text-[12px]
                                               text-gray-600"
                                    >

                                        <span
                                            class="w-1.5 h-1.5
                                                   rounded-full
                                                   bg-red-500"
                                        ></span>

                                        Inactive

                                    </span>

                                <?php endif; ?>

                            </td>



                            <!-- JOINED DATE -->
                            <td
                                class="px-3 py-2.5
                                       text-gray-600
                                       whitespace-nowrap"
                            >

                                <?= !empty($employee['created_at'])
                                    ? htmlspecialchars(
                                        date(
                                            'd M Y, g:i a',
                                            strtotime(
                                                $employee['created_at']
                                            )
                                        )
                                    )
                                    : '-'
                                ?>

                            </td>



                            <!-- 2FA -->
                            <td class="px-3 py-2.5">

                                <span
                                    class="inline-flex
                                           px-2 py-1
                                           rounded
                                           bg-yellow-50
                                           border border-yellow-100
                                           text-[12px]
                                           text-yellow-700"
                                >
                                    Enabled
                                </span>

                            </td>



                            <!-- ACTIONS -->
                            <td class="px-3 py-2.5">

                                <div
                                    class="flex items-center
                                           gap-1.5"
                                >


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
                                                        (int) ($employee['role_id'] ?? 0),

                                                    'status' =>
                                                        $status
                                                ],

                                                JSON_HEX_APOS |
                                                JSON_HEX_QUOT
                                            ) ?>
                                        )'

                                        class="h-8 px-2.5
                                               inline-flex
                                               items-center
                                               gap-1.5
                                               border border-gray-200
                                               rounded-md
                                               bg-white
                                               text-[12px]
                                               font-medium
                                               text-gray-600
                                               hover:bg-gray-50"
                                    >

                                        <svg
                                            class="w-3.5 h-3.5"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="1.8"
                                                d="M12 20h9M16.5 3.5a2.1 2.1 0 013 3L8 18l-4 1 1-4L16.5 3.5z"
                                            />
                                        </svg>

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
                                        "
                                    >

                                        <input
                                            type="hidden"
                                            name="id"
                                            value="<?= (int) $employee['id'] ?>"
                                        >


                                        <button
                                            type="submit"
                                            class="h-8 px-2.5
                                                   inline-flex
                                                   items-center
                                                   gap-1.5
                                                   border border-gray-200
                                                   rounded-md
                                                   bg-white
                                                   text-[12px]
                                                   font-medium
                                                   text-gray-600
                                                   hover:bg-red-50
                                                   hover:text-red-600"
                                        >

                                            <svg
                                                class="w-3.5 h-3.5"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="1.7"
                                                    d="M4 7h16M9 7V4h6v3m-8 0 1 13h8l1-13M10 11v5M14 11v5"
                                                />
                                            </svg>

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



    <!-- =========================================================
         FOOTER / PAGINATION
    ========================================================== -->

    <div
        class="min-h-[58px]
               px-4
               border-t border-gray-200
               flex flex-col sm:flex-row
               sm:items-center
               sm:justify-between
               gap-3"
    >


        <!-- LEFT -->
        <div
            class="flex items-center
                   gap-4
                   py-3
                   text-[12px]
                   text-gray-500"
        >


            <div class="flex items-center gap-2">

                Rows per page

                <select
                    class="h-8
                           px-2
                           border border-gray-200
                           rounded-md
                           bg-white
                           text-gray-600
                           outline-none"
                >
                    <option>15</option>
                    <option>25</option>
                    <option>50</option>
                </select>

            </div>


            <span>

                <?php if (count($employees) > 0): ?>

                    1-<?= count($employees) ?>
                    of
                    <?= count($employees) ?>
                    rows

                <?php else: ?>

                    0 rows

                <?php endif; ?>

            </span>

        </div>



        <!-- RIGHT -->
        <div
            class="flex items-center
                   gap-1
                   py-3"
        >

            <button
                class="w-8 h-8
                       rounded-md
                       text-gray-400
                       hover:bg-gray-50"
            >
                «
            </button>

            <button
                class="w-8 h-8
                       rounded-md
                       text-gray-400
                       hover:bg-gray-50"
            >
                ‹
            </button>

            <button
                class="w-8 h-8
                       rounded-md
                       font-semibold
                       text-gray-900"
            >
                1
            </button>

            <button
                class="w-8 h-8
                       rounded-md
                       text-gray-600
                       hover:bg-gray-50"
            >
                2
            </button>

            <span class="px-1 text-gray-400">
                ...
            </span>

            <button
                class="w-8 h-8
                       rounded-md
                       text-gray-600
                       hover:bg-gray-50"
            >
                5
            </button>

            <button
                class="w-8 h-8
                       rounded-md
                       text-gray-500
                       hover:bg-gray-50"
            >
                ›
            </button>

            <button
                class="w-8 h-8
                       rounded-md
                       text-gray-500
                       hover:bg-gray-50"
            >
                »
            </button>

        </div>


    </div>


</div>



<!-- =============================================================
     ADD EMPLOYEE MODAL
============================================================== -->

<div
    id="employeeModal"
    class="fixed inset-0
           z-50
           hidden
           items-center
           justify-center
           p-4"
>


    <div
        onclick="closeEmployeeModal()"
        class="absolute inset-0
               bg-black/40"
    ></div>


    <div
        class="relative
               w-full
               max-w-lg
               bg-white
               rounded-xl
               shadow-xl"
    >


        <div
            class="px-6 py-5
                   flex items-center
                   justify-between
                   border-b
                   border-gray-200"
        >

            <div>

                <h2
                    class="text-lg
                           font-semibold
                           text-gray-900"
                >
                    Add User
                </h2>

                <p
                    class="text-sm
                           text-gray-500
                           mt-1"
                >
                    Create a new employee account.
                </p>

            </div>


            <button
                type="button"
                onclick="closeEmployeeModal()"
                class="w-8 h-8
                       rounded-md
                       text-gray-500
                       hover:bg-gray-100
                       text-xl"
            >
                &times;
            </button>

        </div>



        <form
            method="POST"
            action="<?= $baseUrl ?>/employees/store"
            class="p-6 space-y-4"
        >


            <div>

                <label
                    class="block
                           text-sm
                           font-medium
                           text-gray-700
                           mb-1.5"
                >
                    Full Name *
                </label>

                <input
                    required
                    type="text"
                    name="name"

                    value="<?= htmlspecialchars(
                        $old['name'] ?? ''
                    ) ?>"

                    class="w-full
                           h-10
                           px-3
                           border border-gray-200
                           rounded-md
                           text-sm
                           outline-none
                           focus:border-blue-400
                           focus:ring-2
                           focus:ring-blue-100"
                >

            </div>



            <div>

                <label
                    class="block
                           text-sm
                           font-medium
                           text-gray-700
                           mb-1.5"
                >
                    Email *
                </label>

                <input
                    required
                    type="email"
                    name="email"

                    value="<?= htmlspecialchars(
                        $old['email'] ?? ''
                    ) ?>"

                    class="w-full
                           h-10
                           px-3
                           border border-gray-200
                           rounded-md
                           text-sm
                           outline-none
                           focus:border-blue-400
                           focus:ring-2
                           focus:ring-blue-100"
                >

            </div>



            <div>

                <label
                    class="block
                           text-sm
                           font-medium
                           text-gray-700
                           mb-1.5"
                >
                    Password *
                </label>

                <input
                    required
                    minlength="6"
                    type="password"
                    name="password"

                    class="w-full
                           h-10
                           px-3
                           border border-gray-200
                           rounded-md
                           text-sm
                           outline-none
                           focus:border-blue-400
                           focus:ring-2
                           focus:ring-blue-100"
                >

            </div>



            <div>

                <label
                    class="block
                           text-sm
                           font-medium
                           text-gray-700
                           mb-1.5"
                >
                    Role *
                </label>

                <select
                    required
                    name="role_id"

                    class="w-full
                           h-10
                           px-3
                           border border-gray-200
                           rounded-md
                           bg-white
                           text-sm
                           outline-none"
                >

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
                            ?>
                        >

                            <?= htmlspecialchars(
                                $role['name']
                            ) ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>



            <div>

                <label
                    class="block
                           text-sm
                           font-medium
                           text-gray-700
                           mb-2"
                >
                    Status
                </label>


                <div class="flex gap-5">

                    <label
                        class="flex items-center
                               gap-2
                               text-sm
                               text-gray-700"
                    >

                        <input
                            type="radio"
                            name="status"
                            value="active"

                            <?= (
                                ($old['status'] ?? 'active')
                                === 'active'
                            )
                                ? 'checked'
                                : ''
                            ?>
                        >

                        Active

                    </label>


                    <label
                        class="flex items-center
                               gap-2
                               text-sm
                               text-gray-700"
                    >

                        <input
                            type="radio"
                            name="status"
                            value="inactive"

                            <?= (
                                ($old['status'] ?? '')
                                === 'inactive'
                            )
                                ? 'checked'
                                : ''
                            ?>
                        >

                        Inactive

                    </label>

                </div>

            </div>



            <div
                class="pt-5
                       border-t
                       flex justify-end
                       gap-2"
            >

                <button
                    type="button"
                    onclick="closeEmployeeModal()"
                    class="h-10 px-4
                           border border-gray-200
                           rounded-md
                           text-sm
                           text-gray-600
                           hover:bg-gray-50"
                >
                    Cancel
                </button>


                <button
                    type="submit"
                    class="h-10 px-5
                           bg-blue-600
                           hover:bg-blue-700
                           text-white
                           rounded-md
                           text-sm
                           font-medium"
                >
                    Add User
                </button>

            </div>


        </form>


    </div>

</div>



<!-- =============================================================
     EDIT EMPLOYEE MODAL
============================================================== -->

<div
    id="editEmployeeModal"
    class="fixed inset-0
           z-50
           hidden
           items-center
           justify-center
           p-4"
>


    <div
        onclick="closeEditModal()"
        class="absolute inset-0
               bg-black/40"
    ></div>


    <div
        class="relative
               bg-white
               rounded-xl
               shadow-xl
               w-full
               max-w-lg"
    >


        <div
            class="px-6 py-5
                   flex items-center
                   justify-between
                   border-b border-gray-200"
        >

            <h2
                class="text-lg
                       font-semibold
                       text-gray-900"
            >
                Edit User
            </h2>


            <button
                onclick="closeEditModal()"
                type="button"
                class="w-8 h-8
                       rounded-md
                       text-gray-500
                       hover:bg-gray-100
                       text-xl"
            >
                &times;
            </button>

        </div>



        <form
            method="POST"
            action="<?= $baseUrl ?>/employees/update"
            class="p-6 space-y-4"
        >


            <input
                type="hidden"
                id="edit_id"
                name="id"
            >



            <div>

                <label
                    class="block
                           text-sm
                           font-medium
                           text-gray-700
                           mb-1.5"
                >
                    Full Name *
                </label>

                <input
                    required
                    id="edit_name"
                    type="text"
                    name="name"

                    class="w-full
                           h-10
                           px-3
                           border border-gray-200
                           rounded-md
                           text-sm"
                >

            </div>



            <div>

                <label
                    class="block
                           text-sm
                           font-medium
                           text-gray-700
                           mb-1.5"
                >
                    Email *
                </label>

                <input
                    required
                    id="edit_email"
                    type="email"
                    name="email"

                    class="w-full
                           h-10
                           px-3
                           border border-gray-200
                           rounded-md
                           text-sm"
                >

            </div>



            <div>

                <label
                    class="block
                           text-sm
                           font-medium
                           text-gray-700
                           mb-1.5"
                >
                    New Password
                </label>

                <input
                    minlength="6"
                    type="password"
                    name="password"
                    placeholder="Leave blank to keep existing password"

                    class="w-full
                           h-10
                           px-3
                           border border-gray-200
                           rounded-md
                           text-sm"
                >

            </div>



            <div>

                <label
                    class="block
                           text-sm
                           font-medium
                           text-gray-700
                           mb-1.5"
                >
                    Role *
                </label>

                <select
                    required
                    id="edit_role_id"
                    name="role_id"

                    class="w-full
                           h-10
                           px-3
                           bg-white
                           border border-gray-200
                           rounded-md
                           text-sm"
                >

                    <?php foreach ($roles as $role): ?>

                        <option
                            value="<?= (int) $role['id'] ?>"
                        >

                            <?= htmlspecialchars(
                                $role['name']
                            ) ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>



            <div>

                <label
                    class="block
                           text-sm
                           font-medium
                           text-gray-700
                           mb-2"
                >
                    Status
                </label>


                <div class="flex gap-5">

                    <label class="flex gap-2 items-center text-sm">

                        <input
                            id="edit_status_active"
                            type="radio"
                            name="status"
                            value="active"
                        >

                        Active

                    </label>


                    <label class="flex gap-2 items-center text-sm">

                        <input
                            id="edit_status_inactive"
                            type="radio"
                            name="status"
                            value="inactive"
                        >

                        Inactive

                    </label>

                </div>

            </div>



            <div
                class="pt-5
                       border-t
                       flex justify-end
                       gap-2"
            >

                <button
                    type="button"
                    onclick="closeEditModal()"
                    class="h-10 px-4
                           border border-gray-200
                           rounded-md
                           text-sm
                           text-gray-600"
                >
                    Cancel
                </button>


                <button
                    type="submit"
                    class="h-10 px-5
                           bg-blue-600
                           text-white
                           rounded-md
                           text-sm
                           font-medium"
                >
                    Save Changes
                </button>

            </div>


        </form>


    </div>

</div>



<script>

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


function openEditModal(employee)
{
    document.getElementById('edit_id').value =
        employee.id;

    document.getElementById('edit_name').value =
        employee.name;

    document.getElementById('edit_email').value =
        employee.email;

    document.getElementById('edit_role_id').value =
        employee.role_id;


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

    document.body.classList.add('overflow-hidden');
}


function closeEditModal()
{
    const modal =
        document.getElementById(
            'editEmployeeModal'
        );

    modal.classList.add('hidden');
    modal.classList.remove('flex');

    document.body.classList.remove('overflow-hidden');
}


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
            .value
            .toLowerCase();


    const status =
        document
            .getElementById('statusFilter')
            .value
            .toLowerCase();


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