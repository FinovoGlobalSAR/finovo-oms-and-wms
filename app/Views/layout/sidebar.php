<?php

$baseUrl =
    '/finovo-oms-and-wms/public/index.php';

$currentPath =
    parse_url(
        $_SERVER['REQUEST_URI'],
        PHP_URL_PATH
    );


$navigation = [

    'General' => [

        [
            'label' => 'Dashboard',
            'url'   => '/dashboard',
            'icon'  => 'dashboard',
        ],

        [
            'label' => 'Orders',
            'url'   => '/orders',
            'icon'  => 'orders',
        ],

        [
            'label' => 'Products',
            'url'   => '/products',
            'icon'  => 'products',
        ],

    ],


    'Management' => [

        [
            'label' => 'Employees',
            'url'   => '/employees',
            'icon'  => 'users',
        ],

    ],

];

?>


<style>

#sidebar {
    font-family:
        Inter,
        ui-sans-serif,
        system-ui,
        -apple-system,
        BlinkMacSystemFont,
        "Segoe UI",
        sans-serif;
}


.sidebar-scroll::-webkit-scrollbar {
    width: 3px;
}


.sidebar-scroll::-webkit-scrollbar-track {
    background: transparent;
}


.sidebar-scroll::-webkit-scrollbar-thumb {
    background: #dedfe2;
    border-radius: 999px;
}


.sidebar-nav-item {
    transition:
        background-color 0.12s ease,
        color 0.12s ease;
}

</style>



<aside

    id="sidebar"

    class="
        fixed
        inset-y-0
        left-0
        z-40

        w-[232px]

        bg-[#fcfcfc]

        border-r
        border-[#eeeeef]

        transform
        -translate-x-full
        lg:translate-x-0

        transition-transform
        duration-300

        flex
        flex-col
    "

>


    <!-- =========================================================
         TOP COMPANY
    ========================================================== -->

    <div
        class="
            h-[54px]

            px-[14px]

            shrink-0

            flex
            items-center
            justify-between
        "
    >


        <button
            type="button"

            class="
                min-w-0

                flex
                items-center
                gap-[8px]

                text-[#292b30]
            "
        >


            <!-- Logo -->

            <span
                class="
                    w-[20px]
                    h-[20px]

                    shrink-0

                    rounded-[5px]

                    bg-[#202124]

                    flex
                    items-center
                    justify-center

                    text-white
                "
            >

                <svg
                    width="12"
                    height="12"

                    viewBox="0 0 24 24"

                    fill="none"

                    stroke="currentColor"

                    stroke-width="2"

                    stroke-linecap="round"
                    stroke-linejoin="round"
                >

                    <path d="M12 3v4" />
                    <path d="M12 17v4" />
                    <path d="M3 12h4" />
                    <path d="M17 12h4" />
                    <path d="m5.6 5.6 2.8 2.8" />
                    <path d="m15.6 15.6 2.8 2.8" />
                    <path d="m18.4 5.6-2.8 2.8" />
                    <path d="m8.4 15.6-2.8 2.8" />

                </svg>

            </span>



            <!-- Finovo -->

            <span
                class="
                    text-[15px]
                    leading-none

                    font-bold

                    tracking-[-0.15px]

                    text-[#222429]
                "
            >
                Finovo
            </span>



            <svg
                width="11"
                height="11"

                viewBox="0 0 24 24"

                fill="none"

                stroke="#55585f"

                stroke-width="2"

                stroke-linecap="round"
                stroke-linejoin="round"
            >
                <path d="m8 10 4 4 4-4" />
            </svg>

        </button>



        <!-- Edit -->

        <button
            type="button"

            class="
                w-[27px]
                h-[27px]

                rounded-[6px]

                flex
                items-center
                justify-center

                text-[#484b51]

                hover:bg-[#f1f1f2]

                transition
            "
        >

            <svg
                width="16"
                height="16"

                viewBox="0 0 24 24"

                fill="none"

                stroke="currentColor"

                stroke-width="1.8"

                stroke-linecap="round"
                stroke-linejoin="round"
            >

                <path d="M12 20h9" />

                <path
                    d="M16.5 3.5a2.1 2.1 0 013 3L8 18l-4 1 1-4z"
                />

            </svg>

        </button>

    </div>



    <!-- =========================================================
         QUICK ACTIONS
    ========================================================== -->

    <div
        class="
            px-[12px]
            pb-[13px]

            shrink-0
        "
    >

        <div
            class="
                flex
                items-center
                gap-[6px]
            "
        >


            <button
                type="button"

                class="
                    flex-1

                    h-[33px]

                    px-[9px]

                    border
                    border-[#e5e5e7]

                    rounded-[6px]

                    bg-white

                    flex
                    items-center
                    justify-between

                    shadow-[0_1px_2px_rgba(0,0,0,0.02)]
                "
            >


                <span
                    class="
                        flex
                        items-center
                        gap-[7px]
                    "
                >

                    <svg
                        width="14"
                        height="14"

                        viewBox="0 0 24 24"

                        fill="none"

                        stroke="#4d5056"

                        stroke-width="1.8"

                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >

                        <rect
                            x="4"
                            y="5"
                            width="16"
                            height="14"
                            rx="2"
                        />

                        <path d="M8 9h8" />
                        <path d="M8 13h5" />

                    </svg>


                    <span
                        class="
                            text-[12px]
                            leading-none

                            font-semibold

                            text-[#303238]
                        "
                    >
                        Quick actions
                    </span>

                </span>


                <span
                    class="
                        text-[10px]
                        leading-none

                        font-semibold

                        text-[#85878d]
                    "
                >
                    ⌘K
                </span>

            </button>



            <!-- Search -->

            <button
                type="button"

                class="
                    w-[34px]
                    h-[33px]

                    border
                    border-[#e5e5e7]

                    rounded-[6px]

                    bg-white

                    flex
                    items-center
                    justify-center

                    text-[#44474d]

                    hover:bg-[#f8f8f8]

                    transition
                "
            >

                <svg
                    width="15"
                    height="15"

                    viewBox="0 0 24 24"

                    fill="none"

                    stroke="currentColor"

                    stroke-width="1.9"

                    stroke-linecap="round"
                    stroke-linejoin="round"
                >

                    <circle
                        cx="11"
                        cy="11"
                        r="7"
                    />

                    <path
                        d="m20 20-3.6-3.6"
                    />

                </svg>

            </button>

        </div>

    </div>



    <!-- =========================================================
         NAVIGATION
    ========================================================== -->

    <nav
        class="
            sidebar-scroll

            flex-1

            overflow-y-auto

            px-[10px]
            pb-[12px]
        "
    >


        <?php foreach ($navigation as $section => $items): ?>


            <div class="mb-[19px]">


                <!-- Section heading -->

                <p
                    class="
                        px-[8px]

                        mb-[6px]

                        text-[11px]
                        leading-[15px]

                        font-medium

                        tracking-[-0.05px]

                        text-[#777a80]
                    "
                >
                    <?= htmlspecialchars($section) ?>
                </p>



                <div class="space-y-[2px]">


                    <?php foreach ($items as $item): ?>


                        <?php

                        $url =
                            $item['url'];


                        $fullUrl =
                            $baseUrl
                            . $url;


                        $isActive =
                            str_ends_with(
                                $currentPath,
                                $url
                            );

                        ?>


                        <a

                            href="<?= htmlspecialchars(
                                $fullUrl
                            ) ?>"

                            class="
                                sidebar-nav-item
                                group

                                h-[34px]

                                px-[8px]

                                rounded-[6px]

                                flex
                                items-center

                                gap-[9px]

                                text-[13px]
                                leading-none

                                tracking-[-0.05px]

                                <?= $isActive

                                    ? '
                                        bg-[#eeeeef]
                                        text-[#202227]
                                        font-semibold
                                    '

                                    : '
                                        text-[#34373d]
                                        font-semibold
                                        hover:bg-[#f2f2f3]
                                        hover:text-[#1f2125]
                                    '
                                ?>
                            "

                        >


                            <!-- DASHBOARD -->

                            <?php if (
                                $item['icon']
                                ===
                                'dashboard'
                            ): ?>

                                <svg
                                    width="15"
                                    height="15"

                                    class="shrink-0"

                                    viewBox="0 0 24 24"

                                    fill="none"

                                    stroke="currentColor"

                                    stroke-width="1.8"

                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >

                                    <rect
                                        x="3.5"
                                        y="3.5"
                                        width="7"
                                        height="7"
                                        rx="1.2"
                                    />

                                    <rect
                                        x="13.5"
                                        y="3.5"
                                        width="7"
                                        height="7"
                                        rx="1.2"
                                    />

                                    <rect
                                        x="3.5"
                                        y="13.5"
                                        width="7"
                                        height="7"
                                        rx="1.2"
                                    />

                                    <rect
                                        x="13.5"
                                        y="13.5"
                                        width="7"
                                        height="7"
                                        rx="1.2"
                                    />

                                </svg>



                            <!-- ORDERS -->

                            <?php elseif (
                                $item['icon']
                                ===
                                'orders'
                            ): ?>

                                <svg
                                    width="15"
                                    height="15"

                                    class="shrink-0"

                                    viewBox="0 0 24 24"

                                    fill="none"

                                    stroke="currentColor"

                                    stroke-width="1.8"

                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >

                                    <rect
                                        x="5"
                                        y="4"
                                        width="14"
                                        height="16"
                                        rx="2"
                                    />

                                    <path d="M8 8h8" />
                                    <path d="M8 12h8" />
                                    <path d="M8 16h5" />

                                </svg>



                            <!-- PRODUCTS -->

                            <?php elseif (
                                $item['icon']
                                ===
                                'products'
                            ): ?>

                                <svg
                                    width="15"
                                    height="15"

                                    class="shrink-0"

                                    viewBox="0 0 24 24"

                                    fill="none"

                                    stroke="currentColor"

                                    stroke-width="1.8"

                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >

                                    <path
                                        d="M4 8l8-4 8 4-8 4z"
                                    />

                                    <path
                                        d="M4 8v8l8 4 8-4V8"
                                    />

                                    <path d="M12 12v8" />

                                </svg>



                            <!-- USERS -->

                            <?php elseif (
                                $item['icon']
                                ===
                                'users'
                            ): ?>

                                <svg
                                    width="15"
                                    height="15"

                                    class="shrink-0"

                                    viewBox="0 0 24 24"

                                    fill="none"

                                    stroke="currentColor"

                                    stroke-width="1.8"

                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >

                                    <circle
                                        cx="9"
                                        cy="8"
                                        r="3"
                                    />

                                    <path
                                        d="M4.5 19c.4-3.3 2-5 4.5-5s4.1 1.7 4.5 5"
                                    />

                                    <circle
                                        cx="17"
                                        cy="10"
                                        r="2.3"
                                    />

                                    <path
                                        d="M16 15c2.4.2 3.7 1.5 4 4"
                                    />

                                </svg>

                            <?php endif; ?>


                            <span>
                                <?= htmlspecialchars(
                                    $item['label']
                                ) ?>
                            </span>


                        </a>


                    <?php endforeach; ?>


                </div>

            </div>


        <?php endforeach; ?>



        <!-- =====================================================
             ACCOUNT
        ====================================================== -->

        <div class="mb-[14px]">


            <p
                class="
                    px-[8px]

                    mb-[6px]

                    text-[11px]
                    leading-[15px]

                    font-medium

                    text-[#777a80]
                "
            >
                Account
            </p>



            <a

                href="<?= $baseUrl ?>/logout"

                class="
                    sidebar-nav-item

                    h-[34px]

                    px-[8px]

                    rounded-[6px]

                    flex
                    items-center

                    gap-[9px]

                    text-[13px]
                    leading-none

                    font-semibold

                    text-[#34373d]

                    hover:bg-[#f2f2f3]
                    hover:text-[#1f2125]
                "
            >

                <svg
                    width="15"
                    height="15"

                    class="shrink-0"

                    viewBox="0 0 24 24"

                    fill="none"

                    stroke="currentColor"

                    stroke-width="1.8"

                    stroke-linecap="round"
                    stroke-linejoin="round"
                >

                    <path
                        d="M15 16l4-4-4-4"
                    />

                    <path
                        d="M19 12H8"
                    />

                    <path
                        d="M11 5H5a2 2 0 00-2 2v10a2 2 0 002 2h6"
                    />

                </svg>


                <span>
                    Logout
                </span>

            </a>

        </div>

    </nav>



    <!-- =========================================================
         BOTTOM BAR
    ========================================================== -->

    <div
        class="
            h-[47px]

            px-[14px]

            shrink-0

            flex
            items-center
            justify-between
        "
    >


        <button
            type="button"

            class="
                w-[25px]
                h-[25px]

                flex
                items-center
                justify-center

                rounded-full

                text-[#62656c]

                hover:bg-[#f1f1f2]

                transition
            "
        >

            <svg
                width="14"
                height="14"

                viewBox="0 0 24 24"

                fill="none"

                stroke="currentColor"

                stroke-width="1.8"

                stroke-linecap="round"
                stroke-linejoin="round"
            >

                <circle
                    cx="12"
                    cy="12"
                    r="8"
                />

                <path
                    d="M9.8 9a2.4 2.4 0 014.6 1c0 1.8-2.4 2-2.4 3.8"
                />

                <circle
                    cx="12"
                    cy="17"
                    r=".8"
                    fill="currentColor"
                    stroke="none"
                />

            </svg>

        </button>



        <div
            class="
                h-[29px]

                px-[9px]

                border
                border-[#e5e5e7]

                rounded-full

                bg-white

                flex
                items-center
                gap-[6px]
            "
        >

            <span
                class="
                    w-[15px]
                    h-[15px]

                    rounded-full

                    flex
                    items-center
                    justify-center

                    text-[#55585f]
                "
            >

                <svg
                    width="13"
                    height="13"

                    viewBox="0 0 24 24"

                    fill="none"

                    stroke="currentColor"

                    stroke-width="1.8"
                >

                    <circle
                        cx="12"
                        cy="8"
                        r="3"
                    />

                    <path
                        d="M6 20c.4-4 2.4-6 6-6s5.6 2 6 6"
                    />

                </svg>

            </span>


            <span
                class="
                    text-[11px]
                    leading-none

                    font-semibold

                    text-[#404349]
                "
            >
                Account
            </span>

        </div>

    </div>

</aside>