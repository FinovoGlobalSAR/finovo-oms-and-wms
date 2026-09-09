<?php

$userName =
    $_SESSION['user_name']
    ?? $_SESSION['name']
    ?? $_SESSION['user']['name']
    ?? 'User';

$userRole =
    $_SESSION['role']
    ?? $_SESSION['role_name']
    ?? $_SESSION['user']['role']
    ?? 'Employee';

$userInitial = strtoupper(
    substr(trim($userName), 0, 1)
);

?>

<header
    class="
        h-[72px]
        bg-white/95
        backdrop-blur
        border-b border-slate-200
        flex items-center justify-between
        px-4 sm:px-5 lg:px-7
        sticky top-0
        z-30
    "
>

    <!-- LEFT SIDE -->
    <div class="flex items-center gap-4">

        <!-- Mobile menu -->
        <button
            id="mobileMenuButton"
            type="button"
            class="
                lg:hidden
                w-10 h-10
                flex items-center justify-center
                rounded-xl
                border border-slate-200
                bg-white
                text-slate-500
                hover:text-blue-600
                hover:bg-blue-50
                hover:border-blue-100
                transition-all
            "
        >
            <svg
                class="w-5 h-5"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M4 6h16M4 12h16M4 18h16"
                />
            </svg>
        </button>


        <!-- Search -->
        <div class="hidden sm:flex items-center">

            <div class="relative w-[270px] lg:w-[360px]">

                <svg
                    class="
                        absolute
                        left-3.5
                        top-1/2
                        -translate-y-1/2
                        w-[17px] h-[17px]
                        text-slate-400
                    "
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

                <input
                    type="text"
                    placeholder="Search dashboard..."
                    class="
                        w-full
                        h-10
                        pl-10
                        pr-16
                        bg-slate-50
                        border border-slate-200
                        rounded-xl
                        text-sm text-slate-700
                        placeholder-slate-400
                        outline-none
                        transition-all
                        focus:bg-white
                        focus:border-blue-300
                        focus:ring-4
                        focus:ring-blue-50
                    "
                >

                <span
                    class="
                        absolute
                        right-2.5
                        top-1/2
                        -translate-y-1/2
                        hidden lg:flex
                        items-center justify-center
                        h-6
                        px-2
                        rounded-md
                        bg-white
                        border border-slate-200
                        text-[10px]
                        font-semibold
                        text-slate-400
                    "
                >
                    ⌘ K
                </span>

            </div>

        </div>

    </div>


    <!-- RIGHT SIDE -->
    <div class="flex items-center gap-1.5">


        <!-- Help -->
        <button
            type="button"
            title="Help & Support"
            class="
                hidden md:flex
                w-10 h-10
                items-center justify-center
                rounded-xl
                text-slate-500
                hover:text-blue-600
                hover:bg-blue-50
                transition-all
            "
        >

            <svg
                class="w-[19px] h-[19px]"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <circle
                    cx="12"
                    cy="12"
                    r="9"
                    stroke-width="1.8"
                />

                <path
                    stroke-linecap="round"
                    stroke-width="1.8"
                    d="M9.8 9a2.2 2.2 0 1 1 3.8 1.5c-.9.8-1.6 1.2-1.6 2.5"
                />

                <circle
                    cx="12"
                    cy="16.5"
                    r=".8"
                    fill="currentColor"
                    stroke="none"
                />
            </svg>

        </button>


        <!-- Notifications -->
        <button
            type="button"
            title="Notifications"
            class="
                relative
                w-10 h-10
                flex items-center justify-center
                rounded-xl
                text-slate-500
                hover:text-blue-600
                hover:bg-blue-50
                transition-all
            "
        >

            <svg
                class="w-[19px] h-[19px]"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="1.7"
                    d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"
                />

                <path
                    stroke-linecap="round"
                    stroke-width="1.7"
                    d="M10 21h4"
                />
            </svg>

            <span
                class="
                    absolute
                    top-[9px]
                    right-[9px]
                    w-2 h-2
                    bg-red-500
                    rounded-full
                    ring-2 ring-white
                "
            ></span>

        </button>


        <div
            class="
                hidden sm:block
                h-8
                w-px
                bg-slate-200
                mx-2
            "
        ></div>


        <!-- Profile -->
        <button
            id="profileButton"
            type="button"
            class="
                flex items-center
                gap-2.5
                pl-1.5
                pr-2.5
                py-1.5
                rounded-xl
                hover:bg-slate-50
                transition-all
                group
            "
        >

            <!-- Avatar -->
            <div
                class="
                    relative
                    w-9 h-9
                    rounded-xl
                    bg-gradient-to-br
                    from-blue-600
                    to-indigo-600
                    flex items-center justify-center
                    shadow-sm
                "
            >

                <span class="text-sm font-bold text-white">
                    <?= htmlspecialchars($userInitial) ?>
                </span>

                <span
                    class="
                        absolute
                        -bottom-[1px]
                        -right-[1px]
                        w-2.5 h-2.5
                        rounded-full
                        bg-emerald-500
                        border-2 border-white
                    "
                ></span>

            </div>


            <!-- User Info -->
            <div class="hidden sm:block text-left leading-tight max-w-[145px]">

                <p
                    class="
                        text-[13px]
                        font-semibold
                        text-slate-800
                        truncate
                    "
                >
                    <?= htmlspecialchars($userName) ?>
                </p>

                <p
                    class="
                        text-[11px]
                        text-slate-400
                        mt-0.5
                        truncate
                    "
                >
                    <?= htmlspecialchars($userRole) ?>
                </p>

            </div>


            <!-- Arrow -->
            <svg
                class="
                    hidden sm:block
                    w-4 h-4
                    text-slate-400
                    group-hover:text-slate-600
                    transition
                "
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

</header>


<script>

    const mobileMenuButton =
        document.getElementById('mobileMenuButton');

    const sidebar =
        document.getElementById('sidebar');


    if (mobileMenuButton && sidebar) {

        mobileMenuButton.addEventListener('click', () => {

            sidebar.classList.toggle('-translate-x-full');

        });

    }


    const profileButton =
        document.getElementById('profileButton');

    if (profileButton) {

        profileButton.addEventListener('click', () => {

            console.log('Profile clicked');

        });

    }

</script>