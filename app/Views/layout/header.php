<header class="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-4 lg:px-6">

    <!-- Left -->
    <div class="flex items-center gap-4">

        <!-- Mobile Menu -->
        <button
            id="mobileMenuButton"
            class="lg:hidden p-2 rounded-lg hover:bg-gray-100"
        >
            <svg
                class="w-6 h-6 text-gray-600"
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
        <div class="hidden md:flex items-center w-72">

            <div class="relative w-full">

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
                    placeholder="Search..."
                    class="w-full pl-9 pr-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >

            </div>

        </div>

    </div>


    <!-- Right -->
    <div class="flex items-center gap-3">

        <!-- Notification -->
        <button class="relative p-2 rounded-lg hover:bg-gray-100">

            <svg
                class="w-5 h-5 text-gray-600"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="1.8"
                    d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 00-12 0v3.2a2 2 0 01-.6 1.4L4 17h5m2 4a2 2 0 002-2h-4a2 2 0 002 2z"
                />
            </svg>

            <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-red-500 rounded-full"></span>

        </button>


        <!-- Profile -->
        <button class="flex items-center gap-3 pl-3 border-l border-gray-200">

            <div class="w-9 h-9 rounded-full bg-blue-100 flex items-center justify-center">
                <span class="text-sm font-semibold text-blue-600">
                    M
                </span>
            </div>

            <div class="hidden sm:block text-left">

                <p class="text-sm font-semibold text-gray-800">
                    Minahil
                </p>

                <p class="text-xs text-gray-500">
                    Admin
                </p>

            </div>

            <svg
                class="w-4 h-4 text-gray-400 hidden sm:block"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M19 9l-7 7-7-7"
                />
            </svg>

        </button>

    </div>

</header>


<script>
    const mobileMenuButton = document.getElementById('mobileMenuButton');
    const sidebar = document.getElementById('sidebar');

    if (mobileMenuButton && sidebar) {
        mobileMenuButton.addEventListener('click', () => {
            sidebar.classList.toggle('-translate-x-full');
        });
    }
</script>