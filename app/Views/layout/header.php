<header class="h-[72px] bg-white border-b border-gray-100 flex items-center justify-between px-5 lg:px-8 sticky top-0 z-30">

    <div class="flex items-center gap-4">

        <button
            id="mobileMenuButton"
            type="button"
            class="lg:hidden w-10 h-10 flex items-center justify-center rounded-xl text-gray-500 hover:bg-gray-50 hover:text-gray-700 transition"
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


        <div class="hidden sm:flex items-center">

            <div class="relative w-[260px] lg:w-[320px]">

                <svg
                    class="absolute left-3.5 top-1/2 -translate-y-1/2 w-[17px] h-[17px] text-gray-400"
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
                    placeholder="Search anything..."
                    class="w-full h-10 pl-10 pr-14 bg-gray-50 border border-transparent rounded-xl text-sm text-gray-700 placeholder-gray-400 outline-none transition focus:bg-white focus:border-gray-200 focus:ring-2 focus:ring-gray-100"
                >

                <span
                    class="absolute right-2.5 top-1/2 -translate-y-1/2 hidden lg:flex items-center justify-center h-6 px-1.5 rounded-md bg-white border border-gray-200 text-[11px] font-medium text-gray-400"
                >
                    ⌘ K
                </span>

            </div>

        </div>

    </div>


    <div class="flex items-center gap-2">


        <button
            type="button"
            class="hidden md:flex w-10 h-10 items-center justify-center rounded-xl text-gray-500 hover:bg-gray-50 hover:text-gray-700 transition"
            title="Help"
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


        <button
            type="button"
            class="relative w-10 h-10 flex items-center justify-center rounded-xl text-gray-500 hover:bg-gray-50 hover:text-gray-700 transition"
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
                class="absolute top-2 right-2 w-2 h-2 bg-red-500 rounded-full ring-2 ring-white"
            ></span>

        </button>


        <div class="h-8 w-px bg-gray-100 mx-2"></div>


        <button
            id="profileButton"
            type="button"
            class="flex items-center gap-2.5 px-2 py-1.5 rounded-xl hover:bg-gray-50 transition"
        >

            <div
                class="w-9 h-9 rounded-full bg-blue-50 border border-blue-100 flex items-center justify-center"
            >
                <span class="text-sm font-semibold text-blue-600">
                    M
                </span>
            </div>


            <div class="hidden sm:block text-left leading-tight">

                <p class="text-[13px] font-semibold text-gray-800">
                    Minahil
                </p>

                <p class="text-[11px] text-gray-400 mt-0.5">
                    Administrator
                </p>

            </div>


            <svg
                class="hidden sm:block w-4 h-4 text-gray-400 ml-1"
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

    const mobileMenuButton = document.getElementById('mobileMenuButton');
    const sidebar = document.getElementById('sidebar');

    if (mobileMenuButton && sidebar) {

        mobileMenuButton.addEventListener('click', () => {

            sidebar.classList.toggle('-translate-x-full');

        });

    }


    const profileButton = document.getElementById('profileButton');

    if (profileButton) {

        profileButton.addEventListener('click', () => {

            console.log('Profile clicked');

        });

    }

</script>