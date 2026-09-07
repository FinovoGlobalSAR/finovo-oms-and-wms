 <aside id="sidebar"
    class="fixed inset-y-0 left-0 z-40 w-64 bg-white border-r border-gray-200
           transform -translate-x-full lg:translate-x-0 transition-transform duration-300">

    <!-- Logo -->
    <div class="h-16 flex items-center px-6 border-b border-gray-100">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-lg bg-blue-600 flex items-center justify-center">
                <span class="text-white font-bold text-lg">F</span>
            </div>

            <div>
                <h1 class="font-bold text-gray-900 text-lg leading-none">
                    Finovo
                </h1>
                <p class="text-xs text-gray-400 mt-1">
                    OMS / WMS
                </p>
            </div>
        </div>
    </div>


    <!-- Navigation -->
    <nav class="p-4 space-y-6 overflow-y-auto h-[calc(100vh-64px)]">

        <!-- General -->
        <div>
            <p class="px-3 mb-2 text-xs font-semibold uppercase tracking-wider text-gray-400">
                General
            </p>

            <div class="space-y-1">

                <a href="/dashboard"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg
                          text-gray-600 hover:bg-gray-100 hover:text-gray-900 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              stroke-width="1.8"
                              d="M3 12l9-9 9 9M5 10v10h14V10"/>
                    </svg>

                    <span class="text-sm font-medium">Home</span>
                </a>


                <!-- Active Dashboard -->
                <a href="/dashboard"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg
                          bg-blue-50 text-blue-600">

                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              stroke-width="1.8"
                              d="M4 13h6V4H4v9zm10 7h6V4h-6v16zM4 20h6v-3H4v3z"/>
                    </svg>

                    <span class="text-sm font-semibold">Dashboard</span>
                </a>


                <a href="#"
                   class="flex items-center justify-between px-3 py-2.5 rounded-lg
                          text-gray-600 hover:bg-gray-100 transition">

                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round"
                                  stroke-linejoin="round"
                                  stroke-width="1.8"
                                  d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 00-12 0v3.2a2 2 0 01-.6 1.4L4 17h5m2 4a2 2 0 002-2h-4a2 2 0 002 2z"/>
                        </svg>

                        <span class="text-sm font-medium">Notifications</span>
                    </div>

                    <span class="text-xs bg-red-100 text-red-600 px-2 py-0.5 rounded-full">
                        10
                    </span>
                </a>


                <a href="#"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg
                          text-gray-600 hover:bg-gray-100 transition">

                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              stroke-width="1.8"
                              d="M12 3v18M3 12h18"/>
                    </svg>

                    <span class="text-sm font-medium">Settings</span>
                </a>

            </div>
        </div>


        <!-- Management -->
        <div>
            <p class="px-3 mb-2 text-xs font-semibold uppercase tracking-wider text-gray-400">
                Management
            </p>

            <div class="space-y-1">

                <a href="/employees"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg
                          text-gray-600 hover:bg-gray-100 hover:text-gray-900 transition">

                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              stroke-width="1.8"
                              d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2M9 11a4 4 0 100-8 4 4 0 000 8zm7-3a3 3 0 110 6m4 7v-2a4 4 0 00-3-3.87"/>
                    </svg>

                    <span class="text-sm font-medium">Employees</span>
                </a>


                <a href="#"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg
                          text-gray-600 hover:bg-gray-100 transition">

                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              stroke-width="1.8"
                              d="M3 7h18M5 7v12h14V7M8 7V4h8v3"/>
                    </svg>

                    <span class="text-sm font-medium">Orders</span>
                </a>


                <a href="#"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg
                          text-gray-600 hover:bg-gray-100 transition">

                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              stroke-width="1.8"
                              d="M4 7h16v13H4zM8 7V4h8v3M8 11h8M8 15h5"/>
                    </svg>

                    <span class="text-sm font-medium">Inventory</span>
                </a>


                <a href="#"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg
                          text-gray-600 hover:bg-gray-100 transition">

                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              stroke-width="1.8"
                              d="M9 17v-2a4 4 0 014-4h4m0 0l-3-3m3 3l-3 3M5 21h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>

                    <span class="text-sm font-medium">Reports</span>
                </a>

            </div>
        </div>


        <!-- Account -->
        <div>
            <p class="px-3 mb-2 text-xs font-semibold uppercase tracking-wider text-gray-400">
                Account
            </p>

            <a href="/logout"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg
                      text-gray-600 hover:bg-red-50 hover:text-red-600 transition">

                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="1.8"
                          d="M17 16l4-4m0 0l-4-4m4 4H7m6-9H5a2 2 0 00-2 2v14a2 2 0 002 2h8"/>
                </svg>

                <span class="text-sm font-medium">Logout</span>
            </a>
        </div>

    </nav>
</aside>