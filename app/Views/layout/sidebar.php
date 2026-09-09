<?php

$baseUrl = '';
$baseUrl = '/finovo-oms-and-wms/public/index.php';

$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$navigation = [

    'Design' => [

        [
            'label' => 'Dashboard',
            'url'   => '/dashboard',
            'icon'  => 'dashboard',
        ],

    ],

    'General' => [

        [
            'label' => 'Orders',
            'url'   => '/orders',
            'icon'  => 'orders',
        ],

        [
            'label' => 'Products',
            'url'   => '/products',
            'icon'  => 'inventory',
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


<aside
    id="sidebar"
    class="fixed inset-y-0 left-0 z-40 w-[250px]
           bg-white border-r border-gray-200
           transform -translate-x-full lg:translate-x-0
           transition-transform duration-300"
>


    <!-- ==========================================
         BRAND
    =========================================== -->

    <div class="h-[72px] px-5 border-b border-gray-100 flex items-center">

        <div class="flex items-center justify-between w-full">


            <div class="flex items-center gap-3">

                <!-- Logo -->
                <div
                    class="w-9 h-9 rounded-xl
                           bg-blue-600
                           shadow-sm shadow-blue-200
                           flex items-center justify-center"
                >
                    <span class="text-white font-bold text-sm">
                        F
                    </span>
                </div>


                <!-- Brand Details -->
                <div>

                    <div class="flex items-center gap-1.5">

                        <span class="text-[15px] font-bold text-gray-900">
                            Finovo
                        </span>

                        <span
                            class="px-1.5 py-0.5
                                   rounded-md
                                   bg-blue-50
                                   text-[8px]
                                   uppercase
                                   tracking-wide
                                   font-bold
                                   text-blue-600"
                        >
                            ERP
                        </span>

                    </div>

                    <p class="text-[10px] font-medium text-gray-500 mt-0.5">
                        OMS / WMS
                    </p>

                </div>

            </div>


            <!-- Edit button -->
            <button
                type="button"
                class="w-8 h-8 rounded-lg
                       flex items-center justify-center
                       text-gray-400
                       hover:bg-gray-100
                       hover:text-gray-700
                       transition"
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
                        stroke-width="1.8"
                        d="M12 20h9"
                    />

                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="1.8"
                        d="M16.5 3.5a2.1 2.1 0 013 3L8 18l-4 1 1-4L16.5 3.5z"
                    />
                </svg>

            </button>

        </div>

    </div>



    <!-- ==========================================
         QUICK ACTIONS
    =========================================== -->

    <div class="px-3 pt-4">

        <div class="flex items-center justify-between px-2 mb-2">

            <span
                class="text-[10px]
                       font-bold
                       uppercase
                       tracking-[0.12em]
                       text-gray-500"
            >
                Quick actions
            </span>

        </div>


        <div
            class="flex items-center gap-1
                   p-1
                   bg-gray-50
                   border border-gray-100
                   rounded-xl"
        >


            <!-- New -->
            <button
                type="button"
                class="flex-1
                       h-9
                       flex items-center gap-2
                       px-2.5
                       rounded-lg
                       text-xs
                       font-medium
                       text-gray-700
                       hover:bg-white
                       hover:text-blue-600
                       hover:shadow-sm
                       transition-all"
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
                        stroke-width="1.8"
                        d="M12 5v14M5 12h14"
                    />
                </svg>

                New

            </button>


            <!-- Search -->
            <button
                type="button"
                class="w-9 h-9
                       flex items-center justify-center
                       rounded-lg
                       text-gray-500
                       hover:bg-white
                       hover:text-blue-600
                       hover:shadow-sm
                       transition-all"
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
                        stroke-width="1.8"
                        d="m21 21-4.35-4.35m2.35-5.65a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z"
                    />
                </svg>

            </button>


            <!-- Notification -->
            <button
                type="button"
                class="relative
                       w-9 h-9
                       flex items-center justify-center
                       rounded-lg
                       text-gray-500
                       hover:bg-white
                       hover:text-blue-600
                       hover:shadow-sm
                       transition-all"
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
                        stroke-width="1.8"
                        d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"
                    />

                    <path
                        stroke-linecap="round"
                        stroke-width="1.8"
                        d="M10 21h4"
                    />
                </svg>


                <span
                    class="absolute
                           top-2 right-2
                           w-1.5 h-1.5
                           bg-red-500
                           rounded-full
                           ring-2 ring-gray-50"
                ></span>

            </button>


        </div>

    </div>



    <!-- ==========================================
         NAVIGATION
    =========================================== -->

    <nav
        class="px-3 py-5
               overflow-y-auto
               h-[calc(100vh-220px)]"
    >


        <?php foreach ($navigation as $section => $items): ?>


            <div class="mb-6">


                <!-- Section Heading -->
                <p
                    class="px-2.5 mb-2
                           text-[10px]
                           font-bold
                           uppercase
                           tracking-[0.12em]
                           text-gray-500"
                >
                    <?= htmlspecialchars($section) ?>
                </p>


                <div class="space-y-1">


                    <?php foreach ($items as $item): ?>


                        <?php

                        $url = $item['url'];

                        $fullUrl = $url === '#'
                            ? '#'
                            : $baseUrl . $url;


                        $isActive =
                            $url !== '#'
                            && str_ends_with($currentPath, $url);

                        ?>


                        <a
                            href="<?= htmlspecialchars($fullUrl) ?>"

                            class="
                                group
                                relative
                                flex items-center
                                justify-between
                                px-3 py-2.5
                                rounded-xl
                                text-[13px]
                                transition-all
                                duration-200

                                <?= $isActive
                                    ? 'bg-blue-50 text-blue-700 font-semibold'
                                    : 'text-gray-700 font-medium hover:bg-gray-50 hover:text-gray-900'
                                ?>
                            "
                        >


                            <!-- Active left bar -->
                            <?php if ($isActive): ?>

                                <span
                                    class="absolute
                                           left-0
                                           top-1/2
                                           -translate-y-1/2
                                           w-[3px]
                                           h-5
                                           bg-blue-600
                                           rounded-r-full"
                                ></span>

                            <?php endif; ?>



                            <div class="flex items-center gap-3">


                                <!-- ICON CONTAINER -->
                                <div
                                    class="
                                        w-7 h-7
                                        rounded-lg
                                        flex items-center
                                        justify-center
                                        transition

                                        <?= $isActive
                                            ? 'bg-blue-100'
                                            : 'bg-gray-50 group-hover:bg-gray-100'
                                        ?>
                                    "
                                >


                                    <!-- Dashboard -->
                                    <?php if ($item['icon'] === 'dashboard'): ?>

                                        <svg
                                            class="w-[16px] h-[16px]
                                                <?= $isActive
                                                    ? 'text-blue-600'
                                                    : 'text-gray-600'
                                                ?>"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >

                                            <rect
                                                x="3"
                                                y="3"
                                                width="7"
                                                height="7"
                                                rx="1.5"
                                                stroke-width="1.8"
                                            />

                                            <rect
                                                x="14"
                                                y="3"
                                                width="7"
                                                height="7"
                                                rx="1.5"
                                                stroke-width="1.8"
                                            />

                                            <rect
                                                x="3"
                                                y="14"
                                                width="7"
                                                height="7"
                                                rx="1.5"
                                                stroke-width="1.8"
                                            />

                                            <rect
                                                x="14"
                                                y="14"
                                                width="7"
                                                height="7"
                                                rx="1.5"
                                                stroke-width="1.8"
                                            />

                                        </svg>


                                    <!-- Employees -->
                                    <?php elseif ($item['icon'] === 'users'): ?>

                                        <svg
                                            class="w-[16px] h-[16px]
                                                <?= $isActive
                                                    ? 'text-blue-600'
                                                    : 'text-gray-600'
                                                ?>"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >

                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="1.8"
                                                d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2"
                                            />

                                            <circle
                                                cx="9"
                                                cy="7"
                                                r="4"
                                                stroke-width="1.8"
                                            />

                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="1.8"
                                                d="M22 21v-2a4 4 0 00-3-3.87"
                                            />

                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="1.8"
                                                d="M16 3.13a4 4 0 010 7.75"
                                            />

                                        </svg>


                                    <!-- Orders -->
                                    <?php elseif ($item['icon'] === 'orders'): ?>

                                        <svg
                                            class="w-[16px] h-[16px]
                                                <?= $isActive
                                                    ? 'text-blue-600'
                                                    : 'text-gray-600'
                                                ?>"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >

                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="1.8"
                                                d="M4 6h16v14H4z"
                                            />

                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="1.8"
                                                d="M8 6V4h8v2"
                                            />

                                            <path
                                                stroke-linecap="round"
                                                stroke-width="1.8"
                                                d="M8 10h8M8 14h5"
                                            />

                                        </svg>


                                    <!-- Products / Inventory -->
                                    <?php elseif ($item['icon'] === 'inventory'): ?>

                                        <svg
                                            class="w-[16px] h-[16px]
                                                <?= $isActive
                                                    ? 'text-blue-600'
                                                    : 'text-gray-600'
                                                ?>"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >

                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="1.8"
                                                d="M4 7h16v13H4z"
                                            />

                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="1.8"
                                                d="M8 7V4h8v3"
                                            />

                                            <path
                                                stroke-linecap="round"
                                                stroke-width="1.8"
                                                d="M8 11h8M8 15h5"
                                            />

                                        </svg>


                                    <?php endif; ?>


                                </div>


                                <span>
                                    <?= htmlspecialchars($item['label']) ?>
                                </span>


                            </div>



                            <!-- Active dot -->
                            <?php if ($isActive): ?>

                                <span
                                    class="w-1.5 h-1.5
                                           rounded-full
                                           bg-blue-600"
                                ></span>

                            <?php endif; ?>


                        </a>


                    <?php endforeach; ?>


                </div>


            </div>


        <?php endforeach; ?>



        <!-- ==========================================
             ACCOUNT
        =========================================== -->

        <div class="pt-1">


            <p
                class="px-2.5 mb-2
                       text-[10px]
                       font-bold
                       uppercase
                       tracking-[0.12em]
                       text-gray-500"
            >
                Account
            </p>


            <a
                href="#"
                class="group
                       flex items-center gap-3
                       px-3 py-2.5
                       rounded-xl
                       text-[13px]
                       font-medium
                       text-gray-700
                       hover:bg-red-50
                       hover:text-red-600
                       transition-all"
            >


                <div
                    class="w-7 h-7
                           rounded-lg
                           bg-gray-50
                           flex items-center justify-center
                           group-hover:bg-red-100
                           transition"
                >

                    <svg
                        class="w-[16px] h-[16px]
                               text-gray-600
                               group-hover:text-red-600"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >

                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.8"
                            d="M17 16l4-4m0 0l-4-4m4 4H7"
                        />

                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.8"
                            d="M13 5H5a2 2 0 00-2 2v10a2 2 0 002 2h8"
                        />

                    </svg>

                </div>


                Logout


            </a>


        </div>


    </nav>



    <!-- ==========================================
         FOOTER
    =========================================== -->

    <div
        class="absolute
               bottom-0
               left-0 right-0
               px-5 py-3
               bg-white
               border-t border-gray-100"
    >

        <div class="flex items-center justify-between">

            <span class="text-[10px] font-medium text-gray-400">
                Finovo OMS/WMS
            </span>

            <span
                class="px-1.5 py-0.5
                       rounded
                       bg-gray-100
                       text-[9px]
                       font-semibold
                       text-gray-500"
            >
                v1.0
            </span>

        </div>

    </div>


</aside>