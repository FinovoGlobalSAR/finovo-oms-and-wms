<?php
$title = 'Dashboard';
?>

<div class="space-y-6">

   

    <div>
        <h1 class="text-2xl font-bold text-gray-900">
            Dashboard
        </h1>

        <p class="text-sm text-gray-500 mt-1">
            Welcome back! Here's what's happening with your business today.
        </p>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">

        <div class="bg-white border border-gray-200 rounded-xl p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Total Employees</p>
                    <h2 class="text-2xl font-bold text-gray-900 mt-2">24</h2>
                    <p class="text-xs text-green-600 mt-2">+4 this month</p>
                </div>

                <div class="w-11 h-11 rounded-lg bg-blue-50 flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600"
                         fill="none"
                         stroke="currentColor"
                         viewBox="0 0 24 24">
                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              stroke-width="1.8"
                              d="M17 20h5v-2a4 4 0 00-4-4h-1M9 20H4v-2a4 4 0 014-4h1m4-5a4 4 0 100-8 4 4 0 000 8zm5 1a3 3 0 100-6"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Total Orders</p>
                    <h2 class="text-2xl font-bold text-gray-900 mt-2">1,248</h2>
                    <p class="text-xs text-green-600 mt-2">+12.5% from last month</p>
                </div>

                <div class="w-11 h-11 rounded-lg bg-green-50 flex items-center justify-center">
                    <svg class="w-5 h-5 text-green-600"
                         fill="none"
                         stroke="currentColor"
                         viewBox="0 0 24 24">
                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              stroke-width="1.8"
                              d="M3 7h18M5 7v12h14V7M8 7V4h8v3"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Inventory Items</p>
                    <h2 class="text-2xl font-bold text-gray-900 mt-2">5,482</h2>
                    <p class="text-xs text-orange-600 mt-2">18 low stock items</p>
                </div>

                <div class="w-11 h-11 rounded-lg bg-orange-50 flex items-center justify-center">
                    <svg class="w-5 h-5 text-orange-600"
                         fill="none"
                         stroke="currentColor"
                         viewBox="0 0 24 24">
                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              stroke-width="1.8"
                              d="M4 7h16v13H4zM8 7V4h8v3"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Revenue</p>
                    <h2 class="text-2xl font-bold text-gray-900 mt-2">$48,920</h2>
                    <p class="text-xs text-green-600 mt-2">+8.2% from last month</p>
                </div>

                <div class="w-11 h-11 rounded-lg bg-purple-50 flex items-center justify-center">
                    <span class="text-purple-600 font-bold text-lg">$</span>
                </div>
            </div>
        </div>

    </div>

    <!-- Recent Activity -->
    <div class="bg-white border border-gray-200 rounded-xl">

        <div class="px-5 py-4 border-b border-gray-200 flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-gray-900">
                    Recent Activity
                </h2>

                <p class="text-xs text-gray-500 mt-1">
                    Latest activity from your team
                </p>
            </div>

            <button class="text-sm text-blue-600 hover:underline">
                View all
            </button>
        </div>

        <div class="divide-y divide-gray-100">

            <div class="px-5 py-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full bg-blue-100 flex items-center justify-center">
                        <span class="text-sm font-semibold text-blue-600">A</span>
                    </div>

                    <div>
                        <p class="text-sm font-medium text-gray-800">
                            Ahmed created a new order
                        </p>

                        <p class="text-xs text-gray-500">
                            10 minutes ago
                        </p>
                    </div>
                </div>

                <span class="text-xs text-green-600 font-medium">
                    Order
                </span>
            </div>

            <div class="px-5 py-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full bg-purple-100 flex items-center justify-center">
                        <span class="text-sm font-semibold text-purple-600">S</span>
                    </div>

                    <div>
                        <p class="text-sm font-medium text-gray-800">
                            Sara updated inventory
                        </p>

                        <p class="text-xs text-gray-500">
                            32 minutes ago
                        </p>
                    </div>
                </div>

                <span class="text-xs text-orange-600 font-medium">
                    Inventory
                </span>
            </div>

        </div>
    </div>

</div>