<?php

$title = 'User management';

$employees = $employees ?? [];
$roles = $roles ?? [];
$success = $success ?? null;
$error = $error ?? null;
$old = $old ?? [];

$totalEmployees = count($employees);

$baseUrl =
    '/finovo-oms-and-wms/public/index.php';


function employeeInitials(string $name): string
{
    $parts = preg_split(
        '/\s+/',
        trim($name)
    );

    $initials = '';

    foreach (
        array_slice($parts, 0, 2)
        as $part
    ) {
        if ($part !== '') {
            $initials .= strtoupper(
                substr($part, 0, 1)
            );
        }
    }

    return $initials ?: 'U';
}

?>


<style>
    .user-management-page {
        font-family:
            Inter,
            ui-sans-serif,
            system-ui,
            -apple-system,
            BlinkMacSystemFont,
            "Segoe UI",
            sans-serif;

        color: #24262d;
    }


    .um-checkbox {
        appearance: none;
        -webkit-appearance: none;

        width: 16px;
        height: 16px;

        background: white;

        border: 1px solid #c9cbd1;
        border-radius: 4px;

        cursor: pointer;

        position: relative;

        flex-shrink: 0;
    }


    .um-checkbox:checked {
        background: #1f2937;
        border-color: #1f2937;
    }


    .um-checkbox:checked::after {
        content: "";

        position: absolute;

        width: 7px;
        height: 4px;

        left: 4px;
        top: 4px;

        border-left: 1.5px solid white;
        border-bottom: 1.5px solid white;

        transform: rotate(-45deg);
    }

    .um-select {
        appearance: none;
        -webkit-appearance: none;
    }



    .um-table {
        border-collapse: collapse;
    }


    .um-table th,
    .um-table td {
        vertical-align: middle;
    }

    .um-scrollbar::-webkit-scrollbar {
        height: 6px;
    }


    .um-scrollbar::-webkit-scrollbar-track {
        background: transparent;
    }


    .um-scrollbar::-webkit-scrollbar-thumb {
        background: #d7d8dc;
        border-radius: 20px;
    }

    .um-action-button {
        transition:
            background-color .15s ease,
            border-color .15s ease;
    }


    .um-action-button:hover {
        background: #f8f8f9;
        border-color: #d2d3d7;
    }
</style>



<div class="user-management-page w-full min-w-0">

    <div>

        <div class="flex items-center gap-[12px]">

            <h1
                class="
                    text-[28px]
                    leading-[34px]
                    font-semibold
                    tracking-[-0.6px]
                    text-[#191b20]
                ">
                User management
            </h1>


            <span
                class="
                    text-[15px]
                    leading-[20px]
                    font-medium
                    text-[#767981]
                    mt-[3px]
                ">
                <?= $totalEmployees ?>
            </span>

        </div>


        <p
            class="
                mt-[5px]
                text-[15px]
                leading-[22px]
                font-normal
                text-[#68717f]
            ">
            Manage your team members and their account permissions here.
        </p>

    </div>




    <div class="h-[48px]"></div>


    <div
        class="
            h-[48px]
            flex
            items-center
            border-b
            border-[#e8e9ec]
        ">

        <div
            class="
                flex
                items-center
                gap-[18px]
            ">



            <button
                type="button"

                class="
                    h-[36px]

                    inline-flex
                    items-center
                    gap-[8px]

                    px-[10px]

                    text-[14px]
                    font-medium
                    text-[#444851]

                    rounded-[7px]

                    bg-[#fafafa]
                ">

                <svg
                    width="17"
                    height="17"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="#68707e"
                    stroke-width="1.7">

                    <rect
                        x="3.5"
                        y="3.5"
                        width="17"
                        height="17"
                        rx="1.5" />

                    <path d="M3.5 10h17" />
                    <path d="M10 3.5v17" />

                </svg>

                Table

            </button>




            <button
                type="button"

                class="
                    h-[36px]

                    inline-flex
                    items-center
                    gap-[8px]

                    px-[7px]

                    text-[14px]
                    font-normal
                    text-[#626976]
                ">

                <svg
                    width="17"
                    height="17"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="#68707e"
                    stroke-width="1.7">

                    <rect
                        x="4"
                        y="3.5"
                        width="6"
                        height="17"
                        rx="1.3" />

                    <rect
                        x="14"
                        y="3.5"
                        width="6"
                        height="17"
                        rx="1.3" />

                </svg>

                Board

            </button>




            <button
                type="button"

                class="
                    h-[36px]

                    inline-flex
                    items-center
                    gap-[8px]

                    px-[7px]

                    text-[14px]
                    font-normal
                    text-[#626976]
                ">

                <svg
                    width="17"
                    height="17"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="#68707e"
                    stroke-width="1.7">

                    <circle
                        cx="5"
                        cy="6"
                        r="1"
                        fill="#68707e"
                        stroke="none" />

                    <circle
                        cx="5"
                        cy="12"
                        r="1"
                        fill="#68707e"
                        stroke="none" />

                    <circle
                        cx="5"
                        cy="18"
                        r="1"
                        fill="#68707e"
                        stroke="none" />

                    <path d="M9 6h11" />
                    <path d="M9 12h11" />
                    <path d="M9 18h11" />

                </svg>

                List

            </button>

        </div>

    </div>


    <div
        class="
            min-h-[60px]

            flex
            flex-wrap
            items-center
            gap-[8px]

            border-b
            border-[#e8e9ec]
        ">


        <div
            class="
                relative

                h-[34px]
                min-w-[120px]

                border
                border-[#dedfe3]

                rounded-full

                bg-white
            ">


            <svg
                class="
                    absolute
                    left-[11px]
                    top-1/2
                    -translate-y-1/2
                    pointer-events-none
                "

                width="17"
                height="17"

                viewBox="0 0 24 24"

                fill="none"

                stroke="#68717f"

                stroke-width="1.5">

                <circle
                    cx="10"
                    cy="8"
                    r="3" />

                <path
                    d="M4.5 19c.5-3.6 2.5-5.5 5.5-5.5s5 1.9 5.5 5.5" />

                <circle
                    cx="17"
                    cy="16"
                    r="4" />

                <path
                    d="M17 14.5v3" />

                <path
                    d="M15.5 16h3" />

            </svg>



            <select
                id="roleFilter"

                class="
                    um-select

                    w-full
                    h-full

                    pl-[35px]
                    pr-[30px]

                    rounded-full

                    border-0
                    bg-transparent

                    outline-none
                    focus:ring-0

                    text-[13px]
                    font-medium
                    text-[#535a65]

                    cursor-pointer
                ">

                <option value="">
                    Role
                </option>


                <?php foreach ($roles as $role): ?>

                    <option
                        value="<?= htmlspecialchars(
                                    strtolower(
                                        $role['name']
                                    )
                                ) ?>">
                        <?= htmlspecialchars(
                            $role['name']
                        ) ?>
                    </option>

                <?php endforeach; ?>

            </select>



            <svg
                class="
                    absolute
                    right-[11px]
                    top-1/2
                    -translate-y-1/2
                    pointer-events-none
                "

                width="12"
                height="12"

                viewBox="0 0 24 24"

                fill="none"

                stroke="#68717f"

                stroke-width="1.8">
                <path d="m7 9 5 5 5-5" />
            </svg>

        </div>

        <button
            type="button"

            class="
                h-[34px]
                min-w-[142px]

                px-[11px]

                border
                border-[#dedfe3]

                rounded-full

                bg-white

                inline-flex
                items-center
                justify-between

                text-[13px]
                font-medium
                text-[#535a65]
            ">

            <span
                class="
                    inline-flex
                    items-center
                    gap-[8px]
                ">


                <span
                    class="
                        w-[18px]
                        h-[18px]

                        rounded-full

                        border
                        border-[#9ca2ad]

                        inline-flex
                        items-center
                        justify-center
                    ">

                    <svg
                        width="11"
                        height="11"

                        viewBox="0 0 24 24"

                        fill="none"

                        stroke="#68717f"

                        stroke-width="1.7">

                        <rect
                            x="6"
                            y="10"
                            width="12"
                            height="9"
                            rx="2" />

                        <path
                            d="M8.5 10V7a3.5 3.5 0 017 0v3" />

                    </svg>

                </span>


                2F Auth

            </span>



            <svg
                width="12"
                height="12"

                viewBox="0 0 24 24"

                fill="none"

                stroke="#68717f"

                stroke-width="1.8">
                <path d="m7 9 5 5 5-5" />
            </svg>

        </button>



        <!-- =================================================
             STATUS
        ================================================== -->

        <div
            class="
                relative

                h-[34px]
                min-w-[106px]

                border
                border-[#dedfe3]

                rounded-full

                bg-white
            ">

            <select
                id="statusFilter"

                class="
                    um-select

                    w-full
                    h-full

                    pl-[15px]
                    pr-[29px]

                    rounded-full

                    border-0
                    bg-transparent

                    outline-none
                    focus:ring-0

                    text-[13px]
                    font-medium
                    text-[#535a65]

                    cursor-pointer
                ">

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
                class="
                    absolute
                    right-[11px]
                    top-1/2
                    -translate-y-1/2
                    pointer-events-none
                "

                width="12"
                height="12"

                viewBox="0 0 24 24"

                fill="none"

                stroke="#68717f"

                stroke-width="1.8">
                <path d="m7 9 5 5 5-5" />
            </svg>

        </div>



        <!-- =================================================
             ADD FILTER
        ================================================== -->

        <button
            type="button"

            class="
                h-[34px]

                px-[7px]

                inline-flex
                items-center
                gap-[7px]

                text-[13px]
                font-normal
                text-[#626976]
            ">

            <svg
                width="15"
                height="15"

                viewBox="0 0 24 24"

                fill="none"

                stroke="#68717f"

                stroke-width="1.5">
                <path d="M12 5v14M5 12h14" />
            </svg>

            Add filter

        </button>



        <!-- =================================================
             RIGHT TOOLBAR
        ================================================== -->

        <div
            class="
                ml-auto

                hidden
                xl:flex

                items-center
                gap-[5px]
            ">


            <div
                class="
                    relative

                    h-[34px]
                    w-[150px]
                ">

                <svg
                    class="
                        absolute
                        left-[9px]
                        top-1/2
                        -translate-y-1/2
                    "

                    width="14"
                    height="14"

                    viewBox="0 0 24 24"

                    fill="none"

                    stroke="#868c96"

                    stroke-width="1.7">

                    <circle
                        cx="11"
                        cy="11"
                        r="7" />

                    <path
                        d="m20 20-3.5-3.5" />

                </svg>


                <input
                    id="employeeSearch"

                    type="text"

                    placeholder="Search"

                    autocomplete="off"

                    class="
                        w-full
                        h-full

                        pl-[30px]
                        pr-[8px]

                        border-0

                        outline-none

                        focus:ring-0

                        text-[12px]
                        text-[#50555f]

                        placeholder:text-[#9ca0a8]
                    ">

            </div>



            <button
                type="button"

                class="
                    h-[34px]
                    px-[8px]

                    text-[12px]
                    text-[#666c77]
                ">
                Hide
            </button>



            <button
                type="button"

                class="
                    h-[34px]
                    px-[8px]

                    text-[12px]
                    text-[#666c77]
                ">
                Customize
            </button>



            <button
                type="button"

                class="
                    h-[34px]
                    px-[7px]

                    text-[17px]
                    leading-none
                    text-[#777d86]
                ">
                ···
            </button>



            <button
                type="button"

                class="
                    h-[34px]

                    px-[12px]

                    border
                    border-[#dedfe3]

                    rounded-[6px]

                    bg-white

                    text-[12px]
                    font-medium
                    text-[#565c66]
                ">
                Export
            </button>



            <button
                type="button"

                onclick="openEmployeeModal()"

                class="
                    h-[34px]

                    px-[12px]

                    border
                    border-[#dedfe3]

                    rounded-[6px]

                    bg-white

                    text-[12px]
                    font-medium
                    text-[#565c66]
                ">
                Add User
            </button>

        </div>

    </div>



    <!-- =====================================================
         TABLE
    ====================================================== -->

    <div
        class="
            w-full
            overflow-x-auto
           
        ">

        <table
            class="
                um-table
                w-full
              
                table-fixed
            ">


            <colgroup>
                <col style="width: 4%;">
                <col style="width: 16%;">
                <col style="width: 20%;">
                <col style="width: 11%;">
                <col style="width: 11%;">
                <col style="width: 16%;">
                <col style="width: 10%;">
                <col style="width: 12%;">
            </colgroup>


            <thead
                class="
                    bg-[#fafbfc]

                    border-b
                    border-[#e6e7ea]
                ">

                <tr class="h-[44px]">



                    <th
                        class="
                            pl-[16px]
                            pr-[4px]
                        ">

                        <input
                            id="selectAllEmployees"

                            type="checkbox"

                            class="um-checkbox">

                    </th>




                    <th
                        class="
                            px-[9px]

                            text-left

                            text-[12px]
                            font-medium
                            text-[#626975]
                        ">

                        <div
                            class="
                                flex
                                items-center
                                gap-[8px]
                            ">

                            <svg
                                width="18"
                                height="18"

                                viewBox="0 0 24 24"

                                fill="none"

                                stroke="#727984"

                                stroke-width="1.5">

                                <circle
                                    cx="10"
                                    cy="8"
                                    r="3" />

                                <path
                                    d="M5 19c.4-3.3 2.2-5 5-5s4.6 1.7 5 5" />

                                <circle
                                    cx="17"
                                    cy="16"
                                    r="4" />

                                <path
                                    d="M17 14.5v3" />

                                <path
                                    d="M15.5 16h3" />

                            </svg>

                            Full name

                        </div>

                    </th>




                    <th
                        class="
                            px-[9px]

                            text-left

                            text-[12px]
                            font-medium
                            text-[#626975]
                        ">

                        <div
                            class="
                                flex
                                items-center
                                gap-[8px]
                            ">

                            <span
                                class="
                                    text-[17px]
                                    leading-none
                                    font-medium
                                    text-[#727984]
                                ">
                                @
                            </span>

                            Email

                        </div>

                    </th>




                    <th
                        class="
                            px-[9px]

                            text-left

                            text-[12px]
                            font-medium
                            text-[#626975]
                        ">

                        <div
                            class="
                                flex
                                items-center
                                gap-[7px]
                            ">

                            <svg
                                width="18"
                                height="18"

                                viewBox="0 0 24 24"

                                fill="none"

                                stroke="#727984"

                                stroke-width="1.5">

                                <circle
                                    cx="9"
                                    cy="8"
                                    r="3" />

                                <path
                                    d="M4.5 18.5c.4-3 2-4.5 4.5-4.5" />

                                <circle
                                    cx="16"
                                    cy="15"
                                    r="4" />

                                <path
                                    d="m14.5 15 1 1 2-2" />

                            </svg>

                            Role

                        </div>

                    </th>




                    <th
                        class="
                            px-[9px]

                            text-left

                            text-[12px]
                            font-medium
                            text-[#626975]
                        ">

                        <div
                            class="
                                flex
                                items-center
                                gap-[7px]
                            ">

                            <svg
                                width="18"
                                height="18"

                                viewBox="0 0 24 24"

                                fill="none"

                                stroke="#727984"

                                stroke-width="1.5">

                                <circle
                                    cx="12"
                                    cy="12"
                                    r="7" />

                                <path
                                    d="M12 7v3" />

                                <circle
                                    cx="12"
                                    cy="14.5"
                                    r=".8"
                                    fill="#727984"
                                    stroke="none" />

                            </svg>

                            Status

                        </div>

                    </th>




                    <th
                        class="
                            px-[9px]

                            text-left

                            text-[12px]
                            font-medium
                            text-[#626975]
                        ">

                        <div
                            class="
                                flex
                                items-center
                                gap-[8px]
                            ">

                            <svg
                                width="17"
                                height="17"

                                viewBox="0 0 24 24"

                                fill="none"

                                stroke="#727984"

                                stroke-width="1.5">

                                <rect
                                    x="4"
                                    y="5"
                                    width="16"
                                    height="15"
                                    rx="2" />

                                <path
                                    d="M8 3v4M16 3v4M4 10h16" />

                            </svg>

                            Joined date

                        </div>

                    </th>




                    <th
                        class="
                            px-[9px]

                            text-left

                            text-[12px]
                            font-medium
                            text-[#626975]
                        ">

                        <div
                            class="
                                flex
                                items-center
                                gap-[7px]
                            ">

                            <svg
                                width="18"
                                height="18"

                                viewBox="0 0 24 24"

                                fill="none"

                                stroke="#727984"

                                stroke-width="1.5">

                                <circle
                                    cx="12"
                                    cy="12"
                                    r="8" />

                                <rect
                                    x="9"
                                    y="10"
                                    width="6"
                                    height="5"
                                    rx="1" />

                                <path
                                    d="M10.5 10V8.5a1.5 1.5 0 013 0V10" />

                            </svg>

                            2F Auth

                        </div>

                    </th>




                    <th
                        class="
                            px-[9px]

                            text-left

                            text-[12px]
                            font-medium
                            text-[#626975]
                        ">

                        <div
                            class="
                                flex
                                items-center
                                gap-[7px]
                            ">

                            <svg
                                width="18"
                                height="18"

                                viewBox="0 0 24 24"

                                fill="none"

                                stroke="#727984"

                                stroke-width="1.5">

                                <circle
                                    cx="12"
                                    cy="12"
                                    r="7" />

                                <circle
                                    cx="12"
                                    cy="12"
                                    r="2" />

                                <path
                                    d="M12 3v2M12 19v2M3 12h2M19 12h2" />

                            </svg>

                            Actions

                        </div>

                    </th>

                </tr>

            </thead>


            <tbody>


                <?php if (empty($employees)): ?>


                    <tr>

                        <td
                            colspan="8"

                            class="
                            h-[110px]

                            text-center

                            text-[13px]
                            text-[#878c95]
                        ">
                            No users found
                        </td>

                    </tr>


                <?php else: ?>


                    <?php foreach ($employees as $employee): ?>


                        <?php

                        $status =
                            strtolower(
                                $employee['status']
                                    ?? 'inactive'
                            );


                        $isActive =
                            $status === 'active';


                        $roleName =
                            $employee['role_name']
                            ??
                            $employee['role']
                            ??
                            'No Role';


                        $joinedDate = '-';


                        if (
                            !empty($employee['created_at'])
                        ) {

                            $joinedDate =
                                date(
                                    'd M Y, g:i a',

                                    strtotime(
                                        $employee['created_at']
                                    )
                                );
                        }

                        ?>


                        <tr

                            class="
                            employee-row

                            h-[48px]

                            border-b
                            border-[#eceef0]

                            bg-white

                            hover:bg-[#fafbfc]

                            transition
                        "

                            data-name="<?= htmlspecialchars(
                                            strtolower(
                                                $employee['name']
                                                    ?? ''
                                            )
                                        ) ?>"

                            data-email="<?= htmlspecialchars(
                                            strtolower(
                                                $employee['email']
                                                    ?? ''
                                            )
                                        ) ?>"

                            data-role="<?= htmlspecialchars(
                                            strtolower(
                                                $roleName
                                            )
                                        ) ?>"

                            data-status="<?= htmlspecialchars(
                                                $status
                                            ) ?>">



                            <td
                                class="
                                pl-[16px]
                                pr-[4px]
                            ">

                                <input
                                    type="checkbox"

                                    class="
                                    employee-checkbox
                                    um-checkbox
                                ">

                            </td>




                            <td class="px-[9px]">

                                <div
                                    class="
                                    flex
                                    items-center
                                    gap-[9px]
                                    min-w-0
                                ">

                                    <div
                                        class="
                                        w-[28px]
                                        h-[28px]

                                        shrink-0

                                        rounded-full

                                        bg-[#eeeefe]

                                        flex
                                        items-center
                                        justify-center

                                        text-[10px]
                                        font-semibold
                                        text-[#6565a0]
                                    ">
                                        <?= htmlspecialchars(
                                            employeeInitials(
                                                $employee['name']
                                                    ?? ''
                                            )
                                        ) ?>
                                    </div>


                                    <span
                                        class="
                                        truncate

                                        text-[12px]
                                        leading-[17px]

                                        font-medium
                                        text-[#444952]
                                    ">
                                        <?= htmlspecialchars(
                                            $employee['name']
                                                ?? ''
                                        ) ?>
                                    </span>

                                </div>

                            </td>



                            <td class="px-[9px]">

                                <span
                                    class="
                                    block
                                    truncate

                                    text-[12px]
                                    leading-[17px]

                                    text-[#626975]

                                    underline
                                    decoration-[#d1d3d7]
                                    underline-offset-[2px]
                                ">
                                    <?= htmlspecialchars(
                                        $employee['email']
                                            ?? ''
                                    ) ?>
                                </span>

                            </td>



                            <td
                                class="
                                px-[9px]

                                text-[12px]
                                leading-[17px]

                                text-[#626975]
                            ">
                                <?= htmlspecialchars(
                                    $roleName
                                ) ?>
                            </td>

                            <td class="px-[9px]">


                                <?php if ($isActive): ?>


                                    <span
                                        class="
                                        h-[27px]

                                        inline-flex
                                        items-center
                                        gap-[7px]

                                        px-[10px]

                                        border
                                        border-[#dfe6e2]

                                        rounded-full

                                        bg-white

                                        text-[11px]
                                        leading-none
                                        font-medium
                                        text-[#5c636b]
                                    ">

                                        <span
                                            class="
                                            w-[6px]
                                            h-[6px]

                                            rounded-full

                                            bg-[#10b981]
                                        "></span>

                                        Active

                                    </span>


                                <?php else: ?>


                                    <span
                                        class="
                                        h-[27px]

                                        inline-flex
                                        items-center
                                        gap-[7px]

                                        px-[10px]

                                        border
                                        border-[#eadfdf]

                                        rounded-full

                                        bg-white

                                        text-[11px]
                                        leading-none
                                        font-medium
                                        text-[#5c636b]
                                    ">

                                        <span
                                            class="
                                            w-[6px]
                                            h-[6px]

                                            rounded-full

                                            bg-[#ef4444]
                                        "></span>

                                        Inactive

                                    </span>


                                <?php endif; ?>


                            </td>


                            <td
                                class="
                                px-[9px]

                                whitespace-nowrap

                                text-[11px]
                                leading-[17px]

                                text-[#737983]
                            ">
                                <?= htmlspecialchars(
                                    $joinedDate
                                ) ?>
                            </td>


                            <td class="px-[9px]">

                                <span
                                    class="
                                    h-[25px]

                                    inline-flex
                                    items-center

                                    px-[9px]

                                    rounded-[5px]

                                    bg-[#fff2b8]

                                    text-[11px]
                                    leading-none

                                    font-medium

                                    text-[#7a6408]
                                ">
                                    Enabled
                                </span>

                            </td>


                            <td class="px-[9px]">

                                <div
                                    class="
                                    flex
                                    items-center
                                    gap-[6px]
                                ">


                                    <button

                                        type="button"

                                        onclick='openEditModal(
                                        <?= json_encode(
                                            [
                                                'id' =>
                                                (int)
                                                $employee['id'],

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

                                            JSON_HEX_TAG
                                                |
                                                JSON_HEX_APOS
                                                |
                                                JSON_HEX_AMP
                                                |
                                                JSON_HEX_QUOT
                                        ) ?>
                                    )'

                                        class="
                                        um-action-button

                                        h-[27px]

                                        px-[8px]

                                        border
                                        border-[#dedfe3]

                                        rounded-[5px]

                                        bg-white

                                        inline-flex
                                        items-center
                                        gap-[5px]

                                        text-[10px]
                                        font-medium
                                        text-[#595f68]
                                    ">

                                        <svg
                                            width="11"
                                            height="11"

                                            viewBox="0 0 24 24"

                                            fill="none"

                                            stroke="currentColor"

                                            stroke-width="1.7">

                                            <path d="M12 20h9" />

                                            <path
                                                d="M16.5 3.5a2.1 2.1 0 013 3L8 18l-4 1 1-4z" />

                                        </svg>

                                        Edit

                                    </button>



                                    <form

                                        method="POST"

                                        action="<?= $baseUrl ?>/employees/delete"

                                        onsubmit="
                                        return confirm(
                                            'Are you sure you want to delete this user?'
                                        );
                                    ">

                                        <input
                                            type="hidden"
                                            name="id"
                                            value="<?= (int) $employee['id'] ?>">


                                        <button
                                            type="submit"

                                            class="
                                            um-action-button

                                            h-[27px]

                                            px-[8px]

                                            border
                                            border-[#dedfe3]

                                            rounded-[5px]

                                            bg-white

                                            inline-flex
                                            items-center
                                            gap-[5px]

                                            text-[10px]
                                            font-medium
                                            text-[#595f68]
                                        ">

                                            <svg
                                                width="11"
                                                height="11"

                                                viewBox="0 0 24 24"

                                                fill="none"

                                                stroke="currentColor"

                                                stroke-width="1.7">

                                                <path d="M3 6h18" />

                                                <path
                                                    d="M8 6V4h8v2" />

                                                <path
                                                    d="M19 6l-1 14H6L5 6" />

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

    <div
        class="
            min-h-[52px]

            px-[16px]

            flex
            flex-wrap
            items-center
            justify-between

            gap-[12px]

            border-b
            border-[#e8e9ec]

            text-[11px]
            text-[#707680]
        ">


        <div
            class="
                flex
                items-center
                gap-[15px]
            ">

            <span>
                Rows per page
            </span>


            <button
                type="button"

                class="
                    inline-flex
                    items-center
                    gap-[6px]

                    font-medium
                    text-[#4e545d]
                ">

                15


                <svg
                    width="11"
                    height="11"

                    viewBox="0 0 24 24"

                    fill="none"

                    stroke="currentColor"

                    stroke-width="1.8">
                    <path
                        d="m7 9 5 5 5-5" />
                </svg>

            </button>


            <span>

                <?= $totalEmployees > 0 ? 1 : 0 ?>

                -

                <?= min(
                    15,
                    $totalEmployees
                ) ?>

                of

                <?= $totalEmployees ?>

                rows

            </span>

        </div>



        <div
            class="
                flex
                items-center
                gap-[5px]
            ">

            <button
                type="button"
                class="
                    w-[25px]
                    h-[25px]
                    text-[#a5a8ae]
                ">
                «
            </button>


            <button
                type="button"
                class="
                    w-[25px]
                    h-[25px]
                    text-[#a5a8ae]
                ">
                ‹
            </button>


            <button
                type="button"
                class="
                    w-[26px]
                    h-[26px]

                    rounded-[5px]

                    bg-[#f2f2f3]

                    font-medium
                    text-[#333840]
                ">
                1
            </button>


            <button
                type="button"
                class="
                    w-[26px]
                    h-[26px]

                    text-[#666c75]
                ">
                2
            </button>


            <span>
                ...
            </span>


            <button
                type="button"
                class="
                    w-[26px]
                    h-[26px]

                    text-[#666c75]
                ">
                5
            </button>


            <button
                type="button"
                class="
                    w-[25px]
                    h-[25px]

                    text-[#666c75]
                ">
                ›
            </button>


            <button
                type="button"
                class="
                    w-[25px]
                    h-[25px]

                    text-[#666c75]
                ">
                »
            </button>

        </div>

    </div>

</div>




<div
    id="employeeModal"

    class="
        fixed
        inset-0
        z-50

        hidden

        items-center
        justify-center

        p-4
    ">


    <div
        onclick="closeEmployeeModal()"

        class="
            absolute
            inset-0

            bg-black/30
        "></div>



    <div
        class="
            relative

            w-full
            max-w-[500px]

            bg-white

            border
            border-[#e1e2e5]

            rounded-xl

            shadow-xl
        ">


        <div
            class="
                px-5
                py-4

                flex
                items-start
                justify-between

                border-b
                border-[#e9eaec]
            ">


            <div>

                <h2
                    class="
                        text-[18px]
                        font-semibold
                        text-[#22262d]
                    ">
                    Add User
                </h2>


                <p
                    class="
                        mt-[2px]

                        text-[13px]
                        text-[#777d86]
                    ">
                    Create a new employee account.
                </p>

            </div>



            <button
                type="button"

                onclick="closeEmployeeModal()"

                class="
                    text-[22px]
                    leading-none
                    text-[#777d86]
                ">
                &times;
            </button>

        </div>



        <form

            id="employeeForm"

            method="POST"

            action="<?= $baseUrl ?>/employees/store"

            autocomplete="off"

            class="
                px-5
                py-5

                space-y-4
            ">


            <div
                style="
                    position:absolute;
                    left:-9999px;
                    width:1px;
                    height:1px;
                    overflow:hidden;
                ">

                <input
                    type="text"
                    name="fake_username"
                    autocomplete="username">


                <input
                    type="password"
                    name="fake_password"
                    autocomplete="current-password">

            </div>



            <div>

                <label
                    class="
                        block

                        mb-[6px]

                        text-[13px]
                        font-medium
                        text-[#40454e]
                    ">
                    Full Name *
                </label>


                <input

                    id="employeeName"

                    type="text"

                    name="name"

                    required

                    autocomplete="off"

                    value="<?= htmlspecialchars(
                                $old['name']
                                    ?? ''
                            ) ?>"

                    class="
                        w-full
                        h-[40px]

                        px-3

                        border
                        border-[#dadce0]

                        rounded-[7px]

                        text-[13px]

                        outline-none

                        focus:ring-0
                        focus:border-[#9da1a8]
                    ">

            </div>



            <div>

                <label
                    class="
                        block

                        mb-[6px]

                        text-[13px]
                        font-medium
                        text-[#40454e]
                    ">
                    Email *
                </label>


                <input

                    id="employeeEmail"

                    type="email"

                    name="email"

                    required

                    autocomplete="off"

                    value="<?= htmlspecialchars(
                                $old['email']
                                    ?? ''
                            ) ?>"

                    class="
                        w-full
                        h-[40px]

                        px-3

                        border
                        border-[#dadce0]

                        rounded-[7px]

                        text-[13px]

                        outline-none

                        focus:ring-0
                        focus:border-[#9da1a8]
                    ">

            </div>



            <div>

                <label
                    class="
                        block

                        mb-[6px]

                        text-[13px]
                        font-medium
                        text-[#40454e]
                    ">
                    Password *
                </label>


                <input

                    id="employeePassword"

                    type="password"

                    name="password"

                    required

                    minlength="6"

                    autocomplete="new-password"

                    value=""

                    class="
                        w-full
                        h-[40px]

                        px-3

                        border
                        border-[#dadce0]

                        rounded-[7px]

                        text-[13px]

                        outline-none

                        focus:ring-0
                        focus:border-[#9da1a8]
                    ">

            </div>



            <div>

                <label
                    class="
                        block

                        mb-[6px]

                        text-[13px]
                        font-medium
                        text-[#40454e]
                    ">
                    Role *
                </label>


                <select

                    name="role_id"

                    required

                    class="
                        w-full
                        h-[40px]

                        px-3

                        border
                        border-[#dadce0]

                        rounded-[7px]

                        bg-white

                        text-[13px]

                        outline-none

                        focus:ring-0
                        focus:border-[#9da1a8]
                    ">

                    <option value="">
                        Select Role
                    </option>


                    <?php foreach ($roles as $role): ?>

                        <option

                            value="<?= (int) $role['id'] ?>"

                            <?= (
                                (int) (
                                    $old['role_id']
                                    ?? 0
                                )
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



            <div>

                <label
                    class="
                        block
                        mb-[8px]

                        text-[13px]
                        font-medium
                        text-[#40454e]
                    ">
                    Status
                </label>


                <div
                    class="
                        flex
                        gap-5

                        text-[13px]
                    ">


                    <label
                        class="
                            inline-flex
                            items-center
                            gap-2
                        ">

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



                    <label
                        class="
                            inline-flex
                            items-center
                            gap-2
                        ">

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



            <div
                class="
                    pt-4

                    border-t
                    border-[#e9eaec]

                    flex
                    justify-end
                    gap-2
                ">

                <button
                    type="button"

                    onclick="closeEmployeeModal()"

                    class="
                        h-[36px]

                        px-4

                        border
                        border-[#dadce0]

                        rounded-[6px]

                        text-[12px]
                        font-medium
                        text-[#555b65]
                    ">
                    Cancel
                </button>


                <button
                    type="submit"

                    class="
                        h-[36px]

                        px-4

                        rounded-[6px]

                        bg-[#1f2024]

                        text-[12px]
                        font-medium
                        text-white
                    ">
                    Add User
                </button>

            </div>

        </form>

    </div>

</div>



<div
    id="editEmployeeModal"

    class="
        fixed
        inset-0
        z-50

        hidden

        items-center
        justify-center

        p-4
    ">


    <div
        onclick="closeEditModal()"

        class="
            absolute
            inset-0

            bg-black/30
        "></div>



    <div
        class="
            relative

            w-full
            max-w-[500px]

            bg-white

            border
            border-[#e1e2e5]

            rounded-xl

            shadow-xl
        ">


        <div
            class="
                px-5
                py-4

                flex
                items-start
                justify-between

                border-b
                border-[#e9eaec]
            ">


            <div>

                <h2
                    class="
                        text-[18px]
                        font-semibold
                        text-[#22262d]
                    ">
                    Edit User
                </h2>


                <p
                    class="
                        mt-[2px]

                        text-[13px]
                        text-[#777d86]
                    ">
                    Update employee account details.
                </p>

            </div>



            <button
                type="button"

                onclick="closeEditModal()"

                class="
                    text-[22px]
                    leading-none
                    text-[#777d86]
                ">
                &times;
            </button>

        </div>



        <form

            method="POST"

            action="<?= $baseUrl ?>/employees/update"

            autocomplete="off"

            class="
                px-5
                py-5

                space-y-4
            ">


            <input
                id="edit_id"

                type="hidden"

                name="id">



            <div>

                <label
                    class="
                        block
                        mb-[6px]

                        text-[13px]
                        font-medium
                        text-[#40454e]
                    ">
                    Full Name *
                </label>


                <input
                    id="edit_name"

                    type="text"

                    name="name"

                    required

                    class="
                        w-full
                        h-[40px]

                        px-3

                        border
                        border-[#dadce0]

                        rounded-[7px]

                        text-[13px]

                        outline-none

                        focus:ring-0
                        focus:border-[#9da1a8]
                    ">

            </div>



            <div>

                <label
                    class="
                        block
                        mb-[6px]

                        text-[13px]
                        font-medium
                        text-[#40454e]
                    ">
                    Email *
                </label>


                <input
                    id="edit_email"

                    type="email"

                    name="email"

                    required

                    class="
                        w-full
                        h-[40px]

                        px-3

                        border
                        border-[#dadce0]

                        rounded-[7px]

                        text-[13px]

                        outline-none

                        focus:ring-0
                        focus:border-[#9da1a8]
                    ">

            </div>



            <div>

                <label
                    class="
                        block
                        mb-[6px]

                        text-[13px]
                        font-medium
                        text-[#40454e]
                    ">
                    New Password
                </label>


                <input
                    id="edit_password"

                    type="password"

                    name="password"

                    minlength="6"

                    autocomplete="new-password"

                    placeholder="Leave blank to keep current password"

                    class="
                        w-full
                        h-[40px]

                        px-3

                        border
                        border-[#dadce0]

                        rounded-[7px]

                        text-[13px]

                        outline-none

                        focus:ring-0
                        focus:border-[#9da1a8]
                    ">

            </div>



            <div>

                <label
                    class="
                        block
                        mb-[6px]

                        text-[13px]
                        font-medium
                        text-[#40454e]
                    ">
                    Role *
                </label>


                <select
                    id="edit_role_id"

                    name="role_id"

                    required

                    class="
                        w-full
                        h-[40px]

                        px-3

                        border
                        border-[#dadce0]

                        rounded-[7px]

                        bg-white

                        text-[13px]

                        outline-none

                        focus:ring-0
                        focus:border-[#9da1a8]
                    ">

                    <option value="">
                        Select Role
                    </option>


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



            <div>

                <label
                    class="
                        block
                        mb-[8px]

                        text-[13px]
                        font-medium
                        text-[#40454e]
                    ">
                    Status
                </label>


                <div
                    class="
                        flex
                        gap-5

                        text-[13px]
                    ">


                    <label
                        class="
                            inline-flex
                            items-center
                            gap-2
                        ">

                        <input
                            id="edit_status_active"

                            type="radio"

                            name="status"

                            value="active">

                        Active

                    </label>



                    <label
                        class="
                            inline-flex
                            items-center
                            gap-2
                        ">

                        <input
                            id="edit_status_inactive"

                            type="radio"

                            name="status"

                            value="inactive">

                        Inactive

                    </label>

                </div>

            </div>



            <div
                class="
                    pt-4

                    border-t
                    border-[#e9eaec]

                    flex
                    justify-end
                    gap-2
                ">

                <button
                    type="button"

                    onclick="closeEditModal()"

                    class="
                        h-[36px]

                        px-4

                        border
                        border-[#dadce0]

                        rounded-[6px]

                        text-[12px]
                        font-medium
                        text-[#555b65]
                    ">
                    Cancel
                </button>


                <button
                    type="submit"

                    class="
                        h-[36px]

                        px-4

                        rounded-[6px]

                        bg-[#1f2024]

                        text-[12px]
                        font-medium
                        text-white
                    ">
                    Save Changes
                </button>

            </div>

        </form>

    </div>

</div>


<script>
    function openEmployeeModal() {
        const modal =
            document.getElementById(
                'employeeModal'
            );


        const form =
            document.getElementById(
                'employeeForm'
            );


        document
            .getElementById(
                'employeeErrorAlert'
            )
            ?.remove();


        document
            .getElementById(
                'employeeSuccessAlert'
            )
            ?.remove();



        <?php if (!$error): ?>

            if (form) {
                form.reset();
            }

        <?php endif; ?>



        modal?.classList.remove(
            'hidden'
        );


        modal?.classList.add(
            'flex'
        );


        document.body.classList.add(
            'overflow-hidden'
        );



        <?php if (!$error): ?>

            setTimeout(
                function() {

                    const name =
                        document.getElementById(
                            'employeeName'
                        );


                    const email =
                        document.getElementById(
                            'employeeEmail'
                        );


                    const password =
                        document.getElementById(
                            'employeePassword'
                        );


                    if (name) {
                        name.value = '';
                    }


                    if (email) {
                        email.value = '';
                    }


                    if (password) {
                        password.value = '';
                    }

                },
                100
            );

        <?php endif; ?>

    }



    function closeEmployeeModal() {
        const modal =
            document.getElementById(
                'employeeModal'
            );


        modal?.classList.add(
            'hidden'
        );


        modal?.classList.remove(
            'flex'
        );


        document.body.classList.remove(
            'overflow-hidden'
        );
    }


    function openEditModal(employee) {
        document
            .getElementById(
                'edit_id'
            )
            .value =
            employee.id;


        document
            .getElementById(
                'edit_name'
            )
            .value =
            employee.name;


        document
            .getElementById(
                'edit_email'
            )
            .value =
            employee.email;


        document
            .getElementById(
                'edit_role_id'
            )
            .value =
            employee.role_id;


        document
            .getElementById(
                'edit_password'
            )
            .value =
            '';


        document
            .getElementById(
                'edit_status_active'
            )
            .checked =
            employee.status ===
            'active';


        document
            .getElementById(
                'edit_status_inactive'
            )
            .checked =
            employee.status ===
            'inactive';


        const modal =
            document.getElementById(
                'editEmployeeModal'
            );


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



    function closeEditModal() {
        const modal =
            document.getElementById(
                'editEmployeeModal'
            );


        modal?.classList.add(
            'hidden'
        );


        modal?.classList.remove(
            'flex'
        );


        document.body.classList.remove(
            'overflow-hidden'
        );
    }

    function filterEmployees() {
        const search =
            document
            .getElementById(
                'employeeSearch'
            )
            ?.value
            .toLowerCase()
            .trim() ??
            '';


        const role =
            document
            .getElementById(
                'roleFilter'
            )
            ?.value
            .toLowerCase() ??
            '';


        const status =
            document
            .getElementById(
                'statusFilter'
            )
            ?.value
            .toLowerCase() ??
            '';


        const rows =
            document.querySelectorAll(
                '.employee-row'
            );


        rows.forEach(
            row => {

                const matchesSearch = !search ||
                    row.dataset.name.includes(
                        search
                    ) ||
                    row.dataset.email.includes(
                        search
                    );


                const matchesRole = !role ||
                    row.dataset.role ===
                    role;


                const matchesStatus = !status ||
                    row.dataset.status ===
                    status;


                row.style.display =
                    (
                        matchesSearch &&
                        matchesRole &&
                        matchesStatus
                    ) ?
                    '' :
                    'none';

            }
        );
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


    const selectAllEmployees =
        document.getElementById(
            'selectAllEmployees'
        );


    function getEmployeeCheckboxes() {
        return Array.from(
            document.querySelectorAll(
                '.employee-checkbox'
            )
        );
    }


    selectAllEmployees
        ?.addEventListener(
            'change',
            function() {

                const checkboxes =
                    getEmployeeCheckboxes();


                checkboxes.forEach(
                    checkbox => {

                        const row =
                            checkbox.closest(
                                '.employee-row'
                            );


                        if (
                            row &&
                            row.style.display !==
                            'none'
                        ) {

                            checkbox.checked =
                                selectAllEmployees.checked;
                        }

                    }
                );

            }
        );



    getEmployeeCheckboxes()
        .forEach(
            checkbox => {

                checkbox.addEventListener(
                    'change',
                    function() {

                        if (
                            selectAllEmployees &&
                            selectAllEmployees.checked
                        ) {

                            selectAllEmployees.checked =
                                false;
                        }


                        if (selectAllEmployees) {

                            selectAllEmployees.indeterminate =
                                false;
                        }

                    }
                );

            }
        );

    <?php if ($error && !empty($old)): ?>

            (function() {

                const modal =
                    document.getElementById(
                        'employeeModal'
                    );


                modal?.classList.remove(
                    'hidden'
                );


                modal?.classList.add(
                    'flex'
                );


                document.body.classList.add(
                    'overflow-hidden'
                );

            })();

    <?php endif; ?>
</script>