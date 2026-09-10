<?php

$title = 'User management';

$employees = $employees ?? [];
$roles = $roles ?? [];
$success = $success ?? null;
$error = $error ?? null;
$old = $old ?? [];

$baseUrl = '/finovo-oms-and-wms/public/index.php';

$totalEmployees = count($employees);


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


<div class="w-full bg-white min-h-screen overflow-hidden">


    <!-- PAGE HEADING -->

    <div class="px-5 pt-5 pb-4">

        <div class="flex items-center gap-2">

            <h1 class="text-[21px] font-semibold text-gray-900">
                User management
            </h1>

            <span
                class="inline-flex
                       items-center justify-center
                       min-w-5 h-5
                       px-1.5
                       rounded-full
                       bg-gray-100
                       text-[11px]
                       font-medium
                       text-gray-500"
            >
                <?= $totalEmployees ?>
            </span>

        </div>


        <p class="mt-1.5 text-[13px] text-gray-500">
            Manage your team members and their account permissions here.
        </p>

    </div>



    <!-- TOP TOOLBAR -->

    <div
        class="min-h-[52px]
               px-5
               border-y border-gray-200
               flex items-center
               justify-between
               gap-3"
    >


        <div class="flex items-center gap-1">


            <button
                type="button"
                class="h-8
                       px-3
                       inline-flex
                       items-center
                       gap-2
                       rounded-md
                       bg-gray-100
                       text-[13px]
                       font-medium
                       text-gray-800"
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
                        d="M4 10h16M10 4v16"
                        stroke-width="1.7"
                    />
                </svg>

                Table

            </button>



            <button
                type="button"
                class="h-8
                       px-3
                       inline-flex
                       items-center
                       gap-2
                       rounded-md
                       text-[13px]
                       text-gray-500
                       hover:bg-gray-50"
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
                        rx="1.5"
                        stroke-width="1.7"
                    />

                    <rect
                        x="14"
                        y="4"
                        width="6"
                        height="16"
                        rx="1.5"
                        stroke-width="1.7"
                    />
                </svg>

                Board

            </button>



            <button
                type="button"
                class="h-8
                       px-3
                       inline-flex
                       items-center
                       gap-2
                       rounded-md
                       text-[13px]
                       text-gray-500
                       hover:bg-gray-50"
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
                        d="M8 6h12M8 12h12M8 18h12"
                    />

                    <circle cx="4" cy="6" r="1" fill="currentColor"/>
                    <circle cx="4" cy="12" r="1" fill="currentColor"/>
                    <circle cx="4" cy="18" r="1" fill="currentColor"/>
                </svg>

                List

            </button>

        </div>



        <div class="flex items-center gap-1">


            <!-- SEARCH -->

            <div class="relative">

                <svg
                    class="absolute
                           left-2.5
                           top-1/2
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
                        r="6.5"
                        stroke-width="1.7"
                    />

                    <path
                        d="m19 19-3.5-3.5"
                        stroke-width="1.7"
                        stroke-linecap="round"
                    />
                </svg>


                <input
                    id="employeeSearch"
                    type="text"
                    placeholder="Search"
                    class="w-[105px]
                           h-8
                           pl-8 pr-2
                           border-0
                           outline-none
                           bg-transparent
                           text-[13px]
                           text-gray-600
                           placeholder-gray-400"
                >

            </div>



            <button
                type="button"
                class="h-8
                       px-2
                       inline-flex
                       items-center
                       gap-1.5
                       rounded-md
                       text-[13px]
                       text-gray-500
                       hover:bg-gray-50"
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
                        d="M4 7h16M7 12h10M10 17h4"
                    />
                </svg>

                Hide

            </button>



            <button
                type="button"
                class="h-8
                       px-2
                       inline-flex
                       items-center
                       gap-1.5
                       rounded-md
                       text-[13px]
                       text-gray-500
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
                        r="7"
                        stroke-width="1.7"
                    />

                    <circle
                        cx="12"
                        cy="12"
                        r="2.3"
                        stroke-width="1.7"
                    />
                </svg>

                Customize

            </button>



            <button
                type="button"
                class="w-8 h-8
                       rounded-md
                       text-gray-500
                       hover:bg-gray-50"
            >
                •••
            </button>



            <button
                type="button"
                class="h-8
                       px-3
                       border border-gray-200
                       rounded-md
                       bg-white
                       text-[12px]
                       font-medium
                       text-gray-600
                       hover:bg-gray-50"
            >
                Export
            </button>



            <button
                type="button"
                onclick="openEmployeeModal()"
                class="h-8
                       px-3
                       inline-flex
                       items-center
                       gap-2
                       border border-gray-200
                       rounded-md
                       bg-white
                       shadow-sm
                       text-[12px]
                       font-medium
                       text-gray-700
                       hover:bg-gray-50"
            >

                Add User

                <svg
                    class="w-3 h-3"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="1.8"
                        d="m7 9 5 5 5-5"
                    />
                </svg>

            </button>


        </div>

    </div>



    <!-- FILTERS -->

    <div
        class="h-[48px]
               px-5
               border-b border-gray-200
               flex items-center
               gap-2"
    >


        <div class="relative">

            <svg
                class="absolute
                       left-2.5
                       top-1/2
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


            <select
                id="roleFilter"
                class="appearance-none
                       h-8
                       pl-8 pr-8
                       border border-gray-200
                       rounded-md
                       bg-white
                       outline-none
                       text-[12px]
                       font-medium
                       text-gray-600"
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
                       right-2.5
                       top-1/2
                       -translate-y-1/2
                       w-3 h-3
                       text-gray-400
                       pointer-events-none"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="1.8"
                    d="m7 9 5 5 5-5"
                />
            </svg>

        </div>



        <button
            type="button"
            class="h-8
                   px-3
                   inline-flex
                   items-center
                   gap-2
                   border border-gray-200
                   rounded-md
                   bg-white
                   text-[12px]
                   font-medium
                   text-gray-600"
        >

            <span
                class="w-4 h-4
                       rounded-full
                       border border-gray-300
                       flex items-center
                       justify-center
                       text-[9px]"
            >
                ◎
            </span>

            2F Auth

            <svg
                class="w-3 h-3"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="1.8"
                    d="m7 9 5 5 5-5"
                />
            </svg>

        </button>



        <div class="relative">

            <select
                id="statusFilter"
                class="appearance-none
                       h-8
                       pl-3 pr-8
                       border border-gray-200
                       rounded-md
                       bg-white
                       outline-none
                       text-[12px]
                       font-medium
                       text-gray-600"
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
                       right-2.5
                       top-1/2
                       -translate-y-1/2
                       w-3 h-3
                       text-gray-400
                       pointer-events-none"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="1.8"
                    d="m7 9 5 5 5-5"
                />
            </svg>

        </div>



        <button
            type="button"
            class="h-8
                   px-2
                   inline-flex
                   items-center
                   gap-1.5
                   text-[12px]
                   text-gray-500
                   hover:text-gray-700"
        >

            <span class="text-lg font-light leading-none">
                +
            </span>

            Add filter

        </button>

    </div>



    <!-- ALERTS -->

    <?php if ($success): ?>

        <div
            class="mx-5 mt-3
                   px-4 py-2.5
                   rounded-md
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
            class="mx-5 mt-3
                   px-4 py-2.5
                   rounded-md
                   border border-red-200
                   bg-red-50
                   text-sm
                   text-red-700"
        >

            <?= htmlspecialchars($error) ?>

        </div>

    <?php endif; ?>



    <!-- TABLE -->

    <div class="w-full overflow-hidden">

        <table
            class="w-full
                   table-fixed
                   border-collapse
                   text-[13px]"
        >


            <colgroup>

                <col style="width: 4%">

                <col style="width: 15%">

                <col style="width: 18%">

                <col style="width: 12%">

                <col style="width: 10%">

                <col style="width: 16%">

                <col style="width: 9%">

                <col style="width: 16%">

            </colgroup>



            <thead>

                <tr
                    class="h-[40px]
                           bg-gray-50
                           border-b border-gray-200"
                >


                    <th class="px-2 text-center">

                        <input
                            id="selectAllEmployees"
                            type="checkbox"
                            class="w-4 h-4
                                   rounded
                                   border-gray-300"
                        >

                    </th>



                    <th
                        class="px-2
                               text-left
                               text-[12px]
                               font-medium
                               text-gray-500"
                    >

                        <div class="flex items-center gap-1.5">

                            <span
                                class="w-4 h-4
                                       rounded-full
                                       border border-gray-300
                                       flex items-center
                                       justify-center
                                       text-[9px]"
                            >
                                ◎
                            </span>

                            Full name

                        </div>

                    </th>



                    <th
                        class="px-2
                               text-left
                               text-[12px]
                               font-medium
                               text-gray-500"
                    >

                        <div class="flex items-center gap-1.5">

                            <span class="text-sm">
                                @
                            </span>

                            Email

                        </div>

                    </th>



                    <th
                        class="px-2
                               text-left
                               text-[12px]
                               font-medium
                               text-gray-500"
                    >

                        Role

                    </th>



                    <th
                        class="px-2
                               text-left
                               text-[12px]
                               font-medium
                               text-gray-500"
                    >

                        Status

                    </th>



                    <th
                        class="px-2
                               text-left
                               text-[12px]
                               font-medium
                               text-gray-500"
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
                                    d="M8 3v4M16 3v4M4 10h16"
                                    stroke-width="1.7"
                                />

                            </svg>

                            Joined date

                        </div>

                    </th>



                    <th
                        class="px-2
                               text-left
                               text-[12px]
                               font-medium
                               text-gray-500"
                    >

                        2F Auth

                    </th>



                    <th
                        class="px-2
                               text-left
                               text-[12px]
                               font-medium
                               text-gray-500"
                    >

                        Actions

                    </th>


                </tr>

            </thead>



            <tbody>


                <?php if (empty($employees)): ?>


                    <tr>

                        <td
                            colspan="8"
                            class="py-16
                                   text-center
                                   text-sm
                                   text-gray-400"
                        >

                            No users found.

                        </td>

                    </tr>


                <?php else: ?>


                    <?php foreach ($employees as $index => $employee): ?>


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


                        $avatarClasses = [

                            'bg-pink-100 text-pink-700',

                            'bg-blue-100 text-blue-700',

                            'bg-violet-100 text-violet-700',

                            'bg-orange-100 text-orange-700',

                            'bg-emerald-100 text-emerald-700',

                            'bg-amber-100 text-amber-700',

                        ];


                        $avatarClass =
                            $avatarClasses[
                                $index % count($avatarClasses)
                            ];

                        ?>


                        <tr
                            class="employee-row
                                   h-[46px]
                                   border-b border-gray-100
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

                            data-status="<?= htmlspecialchars(
                                $status
                            ) ?>"
                        >


                            <!-- ROW CHECKBOX -->

                            <td class="px-2 text-center">

                                <input
                                    type="checkbox"
                                    class="employee-checkbox
                                           w-4 h-4
                                           rounded
                                           border-gray-300"
                                >

                            </td>



                            <!-- NAME -->

                            <td class="px-2">

                                <div
                                    class="flex
                                           items-center
                                           gap-2
                                           min-w-0"
                                >


                                    <div
                                        class="w-7 h-7
                                               shrink-0
                                               rounded-full
                                               <?= $avatarClass ?>
                                               flex items-center
                                               justify-center"
                                    >

                                        <span
                                            class="text-[10px]
                                                   font-semibold"
                                        >

                                            <?= htmlspecialchars(
                                                employeeInitials(
                                                    $employee['name']
                                                )
                                            ) ?>

                                        </span>

                                    </div>


                                    <span
                                        class="block
                                               min-w-0
                                               truncate
                                               text-[13px]
                                               font-medium
                                               text-gray-800"
                                    >

                                        <?= htmlspecialchars(
                                            $employee['name']
                                        ) ?>

                                    </span>


                                </div>

                            </td>



                            <!-- EMAIL -->

                            <td class="px-2">

                                <span
                                    class="block
                                           truncate
                                           text-[13px]
                                           text-gray-600
                                           underline
                                           decoration-gray-300
                                           underline-offset-2"
                                >

                                    <?= htmlspecialchars(
                                        $employee['email']
                                    ) ?>

                                </span>

                            </td>



                            <!-- ROLE -->

                            <td class="px-2">

                                <span
                                    class="block
                                           truncate
                                           text-[13px]
                                           text-gray-600"
                                >

                                    <?= htmlspecialchars(
                                        $roleName
                                    ) ?>

                                </span>

                            </td>



                            <!-- STATUS -->

                            <td class="px-2">


                                <?php if ($isActive): ?>


                                    <span
                                        class="inline-flex
                                               items-center
                                               gap-1.5
                                               h-6
                                               px-2
                                               rounded-md
                                               border border-gray-200
                                               bg-white
                                               text-[11px]
                                               font-medium
                                               text-gray-600"
                                    >

                                        <span
                                            class="w-1.5 h-1.5
                                                   rounded-full
                                                   bg-green-500"
                                        ></span>

                                        Active

                                    </span>


                                <?php else: ?>


                                    <span
                                        class="inline-flex
                                               items-center
                                               gap-1.5
                                               h-6
                                               px-2
                                               rounded-md
                                               border border-gray-200
                                               bg-white
                                               text-[11px]
                                               font-medium
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
                                class="px-2
                                       text-[12px]
                                       text-gray-600"
                            >

                                <span class="block truncate">

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

                                </span>

                            </td>



                            <!-- 2FA -->

                            <td class="px-2">

                                <span
                                    class="inline-flex
                                           items-center
                                           px-2
                                           py-1
                                           rounded
                                           bg-amber-50
                                           border border-amber-100
                                           text-[11px]
                                           font-medium
                                           text-amber-700"
                                >
                                    Enabled
                                </span>

                            </td>



                            <!-- ACTIONS -->

                            <td class="px-2">

                                <div
                                    class="flex
                                           items-center
                                           gap-1.5"
                                >


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
                                                        (int) (
                                                            $employee['role_id']
                                                            ?? 0
                                                        ),

                                                    'status' =>
                                                        $status
                                                ],

                                                JSON_HEX_APOS |
                                                JSON_HEX_QUOT
                                            ) ?>
                                        )'

                                        class="h-7
                                               px-2
                                               inline-flex
                                               items-center
                                               justify-center
                                               gap-1
                                               rounded-md
                                               border border-gray-200
                                               bg-white
                                               text-[11px]
                                               font-medium
                                               text-gray-600
                                               hover:bg-gray-50
                                               hover:text-blue-600"
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
                                                d="M12 20h9M16.5 3.5a2.1 2.1 0 013 3L8 18l-4 1 1-4L16.5 3.5z"
                                            />
                                        </svg>

                                        Edit

                                    </button>



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

                                            class="h-7
                                                   px-2
                                                   inline-flex
                                                   items-center
                                                   justify-center
                                                   gap-1
                                                   rounded-md
                                                   border border-gray-200
                                                   bg-white
                                                   text-[11px]
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



    <!-- PAGINATION -->

    <div
        class="h-[58px]
               px-5
               flex items-center
               justify-between"
    >


        <div
            class="flex items-center
                   gap-4
                   text-[12px]
                   text-gray-500"
        >


            <div class="flex items-center gap-2">

                <span>
                    Rows per page
                </span>


                <button
                    type="button"
                    class="h-8
                           px-2.5
                           inline-flex
                           items-center
                           gap-2
                           rounded-md
                           border border-gray-200
                           bg-white
                           text-[12px]
                           text-gray-600"
                >

                    15

                    <svg
                        class="w-3 h-3"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.8"
                            d="m7 9 5 5 5-5"
                        />
                    </svg>

                </button>

            </div>



            <span>

                <?php if ($totalEmployees > 0): ?>

                    1-<?= $totalEmployees ?>
                    of
                    <?= $totalEmployees ?>
                    rows

                <?php else: ?>

                    0 rows

                <?php endif; ?>

            </span>


        </div>



        <div
            class="flex
                   items-center
                   gap-1
                   text-[12px]"
        >


            <button
                type="button"
                class="w-8 h-8
                       rounded-md
                       text-gray-400
                       hover:bg-gray-50"
            >
                «
            </button>


            <button
                type="button"
                class="w-8 h-8
                       rounded-md
                       text-gray-400
                       hover:bg-gray-50"
            >
                ‹
            </button>


            <button
                type="button"
                class="w-8 h-8
                       rounded-md
                       bg-gray-100
                       font-medium
                       text-gray-800"
            >
                1
            </button>


            <button
                type="button"
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
                type="button"
                class="w-8 h-8
                       rounded-md
                       text-gray-600
                       hover:bg-gray-50"
            >
                5
            </button>


            <button
                type="button"
                class="w-8 h-8
                       rounded-md
                       text-gray-500
                       hover:bg-gray-50"
            >
                ›
            </button>


            <button
                type="button"
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



<!-- ==========================================================
     ADD USER MODAL
=========================================================== -->

<div
    id="employeeModal"
    class="fixed
           inset-0
           z-50
           hidden
           items-center
           justify-center
           p-4"
>


    <div
        onclick="closeEmployeeModal()"
        class="absolute inset-0 bg-black/30"
    ></div>


    <div
        class="relative
               w-full
               max-w-md
               bg-white
               rounded-xl
               shadow-xl"
    >


        <div
            class="px-6 py-5
                   border-b border-gray-200
                   flex items-center
                   justify-between"
        >


            <div>

                <h2 class="text-xl font-semibold text-gray-900">
                    Add User
                </h2>

                <p class="text-sm text-gray-500 mt-1">
                    Create a new employee account.
                </p>

            </div>


            <button
                type="button"
                onclick="closeEmployeeModal()"
                class="w-9 h-9
                       rounded-md
                       text-xl
                       text-gray-400
                       hover:bg-gray-100"
            >
                ×
            </button>


        </div>



        <form
            method="POST"
            action="<?= $baseUrl ?>/employees/store"
            class="p-6 space-y-4"
            autocomplete="off"
        >


            <!-- DUMMY AUTOFILL FIELDS -->

            <input
                type="text"
                name="fake_username"
                autocomplete="username"
                tabindex="-1"
                aria-hidden="true"
                style="
                    position:absolute;
                    left:-9999px;
                    width:1px;
                    height:1px;
                "
            >


            <input
                type="password"
                name="fake_password"
                autocomplete="current-password"
                tabindex="-1"
                aria-hidden="true"
                style="
                    position:absolute;
                    left:-9999px;
                    width:1px;
                    height:1px;
                "
            >



            <!-- FULL NAME -->

            <div>

                <label
                    class="block
                           text-sm
                           font-medium
                           text-gray-700
                           mb-1"
                >
                    Full Name
                </label>


                <input
                    id="add_employee_name"
                    required
                    type="text"
                    name="name"
                    autocomplete="off"

                    value="<?= htmlspecialchars(
                        $old['name'] ?? ''
                    ) ?>"

                    class="w-full
                           h-10
                           px-3
                           border border-gray-200
                           rounded-md
                           outline-none
                           text-sm
                           bg-white
                           focus:border-blue-400
                           focus:ring-2
                           focus:ring-blue-100"
                >

            </div>



            <!-- EMAIL -->

            <div>

                <label
                    class="block
                           text-sm
                           font-medium
                           text-gray-700
                           mb-1"
                >
                    Email
                </label>


                <input
                    id="add_employee_email"
                    required
                    type="email"
                    name="email"
                    autocomplete="off"

                    value="<?= htmlspecialchars(
                        $old['email'] ?? ''
                    ) ?>"

                    class="w-full
                           h-10
                           px-3
                           border border-gray-200
                           rounded-md
                           outline-none
                           text-sm
                           bg-white
                           focus:border-blue-400
                           focus:ring-2
                           focus:ring-blue-100"
                >

            </div>



            <!-- PASSWORD -->

            <div>

                <label
                    class="block
                           text-sm
                           font-medium
                           text-gray-700
                           mb-1"
                >
                    Password
                </label>


                <input
                    id="add_employee_password"
                    required
                    minlength="6"
                    type="password"
                    name="password"
                    autocomplete="new-password"
                    value=""

                    class="w-full
                           h-10
                           px-3
                           border border-gray-200
                           rounded-md
                           outline-none
                           text-sm
                           bg-white
                           focus:border-blue-400
                           focus:ring-2
                           focus:ring-blue-100"
                >

            </div>



            <!-- ROLE -->

            <div>

                <label
                    class="block
                           text-sm
                           font-medium
                           text-gray-700
                           mb-1"
                >
                    Role
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
                           text-sm"
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



            <!-- STATUS -->

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


                <div class="flex items-center gap-5 text-sm">


                    <label class="flex items-center gap-2">

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


                    <label class="flex items-center gap-2">

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
                class="pt-4
                       border-t
                       flex
                       justify-end
                       gap-2"
            >


                <button
                    type="button"
                    onclick="closeEmployeeModal()"

                    class="h-10
                           px-4
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

                    class="h-10
                           px-5
                           rounded-md
                           bg-gray-900
                           text-sm
                           font-medium
                           text-white
                           hover:bg-gray-800"
                >
                    Add User
                </button>


            </div>


        </form>


    </div>


</div>



<!-- ==========================================================
     EDIT USER MODAL
=========================================================== -->

<div
    id="editEmployeeModal"
    class="fixed
           inset-0
           z-50
           hidden
           items-center
           justify-center
           p-4"
>


    <div
        onclick="closeEditModal()"
        class="absolute inset-0 bg-black/30"
    ></div>


    <div
        class="relative
               w-full
               max-w-md
               bg-white
               rounded-xl
               shadow-xl"
    >


        <div
            class="px-6 py-5
                   border-b border-gray-200
                   flex items-center
                   justify-between"
        >

            <h2 class="text-xl font-semibold text-gray-900">
                Edit User
            </h2>


            <button
                type="button"
                onclick="closeEditModal()"
                class="w-9 h-9
                       rounded-md
                       text-xl
                       text-gray-400
                       hover:bg-gray-100"
            >
                ×
            </button>


        </div>



        <form
            method="POST"
            action="<?= $baseUrl ?>/employees/update"
            class="p-6 space-y-4"
            autocomplete="off"
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
                           mb-1"
                >
                    Full Name
                </label>

                <input
                    required
                    id="edit_name"
                    type="text"
                    name="name"
                    autocomplete="off"

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
                           mb-1"
                >
                    Email
                </label>

                <input
                    required
                    id="edit_email"
                    type="email"
                    name="email"
                    autocomplete="off"

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
                           mb-1"
                >
                    New Password
                </label>

                <input
                    minlength="6"
                    type="password"
                    name="password"
                    autocomplete="new-password"

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
                           mb-1"
                >
                    Role
                </label>


                <select
                    required
                    id="edit_role_id"
                    name="role_id"

                    class="w-full
                           h-10
                           px-3
                           border border-gray-200
                           rounded-md
                           bg-white
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


                <div class="flex gap-5 text-sm">

                    <label class="flex items-center gap-2">

                        <input
                            id="edit_status_active"
                            type="radio"
                            name="status"
                            value="active"
                        >

                        Active

                    </label>


                    <label class="flex items-center gap-2">

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
                class="pt-4
                       border-t
                       flex
                       justify-end
                       gap-2"
            >

                <button
                    type="button"
                    onclick="closeEditModal()"

                    class="h-10
                           px-4
                           border border-gray-200
                           rounded-md
                           text-sm
                           text-gray-600"
                >
                    Cancel
                </button>


                <button
                    type="submit"

                    class="h-10
                           px-5
                           rounded-md
                           bg-gray-900
                           text-sm
                           font-medium
                           text-white"
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


    const nameInput =
        document.getElementById('add_employee_name');


    const emailInput =
        document.getElementById('add_employee_email');


    const passwordInput =
        document.getElementById('add_employee_password');


    <?php if (!$error): ?>

    if (nameInput) {
        nameInput.value = '';
    }

    if (emailInput) {
        emailInput.value = '';
    }

    if (passwordInput) {
        passwordInput.value = '';
    }

    <?php endif; ?>


    modal.classList.remove('hidden');

    modal.classList.add('flex');

    document.body.classList.add(
        'overflow-hidden'
    );


    /*
     Small delay helps against browser autofill
    */
    setTimeout(() => {

        <?php if (!$error): ?>

        if (emailInput) {
            emailInput.value = '';
        }

        if (passwordInput) {
            passwordInput.value = '';
        }

        <?php endif; ?>

    }, 100);
}



function closeEmployeeModal()
{
    const modal =
        document.getElementById('employeeModal');


    modal.classList.add('hidden');

    modal.classList.remove('flex');


    document.body.classList.remove(
        'overflow-hidden'
    );
}



function openEditModal(employee)
{
    document.getElementById(
        'edit_id'
    ).value =
        employee.id;


    document.getElementById(
        'edit_name'
    ).value =
        employee.name;


    document.getElementById(
        'edit_email'
    ).value =
        employee.email;


    document.getElementById(
        'edit_role_id'
    ).value =
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
============================================
SELECT ALL CHECKBOX
============================================
*/

/*
============================================
SELECT ALL CHECKBOX
============================================
*/

const selectAllEmployees =
    document.getElementById(
        'selectAllEmployees'
    );


function getEmployeeCheckboxes()
{
    return Array.from(
        document.querySelectorAll(
            '.employee-checkbox'
        )
    );
}


/*
Master checkbox:
tick = all visible rows checked
untick = all visible rows unchecked
*/
selectAllEmployees?.addEventListener(
    'change',
    function ()
    {
        const checkboxes =
            getEmployeeCheckboxes();


        checkboxes.forEach(
            checkbox =>
            {
                const row =
                    checkbox.closest(
                        '.employee-row'
                    );


                if (
                    row &&
                    row.style.display !== 'none'
                ) {
                    checkbox.checked =
                        selectAllEmployees.checked;
                }
            }
        );
    }
);


/*
Individual row checkbox:
DO NOT change master checkbox
*/
getEmployeeCheckboxes().forEach(
    checkbox =>
    {
        checkbox.addEventListener(
            'change',
            function ()
            {
                // Master checkbox stays unchanged
            }
        );
    }
);



/*
============================================
FILTER EMPLOYEES
============================================
*/

function filterEmployees()
{
    const search =
        document
            .getElementById(
                'employeeSearch'
            )
            .value
            .toLowerCase()
            .trim();


    const role =
        document
            .getElementById(
                'roleFilter'
            )
            .value
            .toLowerCase();


    const status =
        document
            .getElementById(
                'statusFilter'
            )
            .value
            .toLowerCase();


    const rows =
        document.querySelectorAll(
            '.employee-row'
        );


    rows.forEach(row => {


        const matchesSearch =
            !search
            ||
            row.dataset.name.includes(
                search
            )
            ||
            row.dataset.email.includes(
                search
            );


        const matchesRole =
            !role
            ||
            row.dataset.role === role;


        const matchesStatus =
            !status
            ||
            row.dataset.status === status;


        row.style.display =
            matchesSearch
            &&
            matchesRole
            &&
            matchesStatus

                ? ''

                : 'none';

    });


    /*
    After filter update master checkbox
    */

    updateSelectAllState();
}



document
    .getElementById(
        'employeeSearch'
    )
    ?.addEventListener(
        'input',
        filterEmployees
    );


document
    .getElementById(
        'roleFilter'
    )
    ?.addEventListener(
        'change',
        filterEmployees
    );


document
    .getElementById(
        'statusFilter'
    )
    ?.addEventListener(
        'change',
        filterEmployees
    );


<?php if ($error && !empty($old)): ?>

openEmployeeModal();

<?php endif; ?>


</script>