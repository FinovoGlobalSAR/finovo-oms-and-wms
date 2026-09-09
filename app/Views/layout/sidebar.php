<?php

$baseUrl = '';

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

<aside id="sidebar" class="fixed inset-y-0 left-0 z-40 w-[250px] bg-white border-r border-gray-200 transform -translate-x-full lg:translate-x-0 transition-transform duration-300">

    <div class="h-16 px-5 border-b border-gray-100 flex items-center">
        <div class="flex items-center justify-between w-full">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-blue-600 flex items-center justify-center">
                    <span class="text-white font-bold text-sm">F</span>
                </div>
                <div>
                    <div class="flex items-center gap-1">
                        <span class="text-sm font-semibold text-gray-900">Finovo</span>
                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 9l6 6 6-6" />
                        </svg>
                    </div>
                    <p class="text-[11px] text-gray-400">OMS / WMS</p>
                </div>
            </div>
            <button class="w-7 h-7 rounded-md flex items-center justify-center text-gray-400 hover:bg-gray-100 hover:text-gray-600">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 20h9" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16.5 3.5a2.1 2.1 0 013 3L8 18l-4 1 1-4L16.5 3.5z" />
                </svg>
            </button>
        </div>
    </div>

    <div class="px-3 pt-4">
        <div class="flex items-center justify-between px-2 mb-2">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-gray-400">Quick actions</span>
        </div>
        <div class="flex items-center gap-1">
            <button class="flex-1 flex items-center gap-2 px-2.5 py-2 rounded-md text-xs text-gray-600 hover:bg-gray-100">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 12h18M12 3v18" />
                </svg>
                New
            </button>
            <button class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:bg-gray-100">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M21 21l-4.35-4.35m2.35-5.65a8 8 0 11-16 0 8 8 0 0116 0z" />
                </svg>
            </button>
            <button class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:bg-gray-100">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 17h5l-1.5-1.5A2 2 0 0118 14v-3a6 6 0 00-12 0v3a2 2 0 01-.5 1.5L4 17h5" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10 21h4" />
                </svg>
            </button>
        </div>
    </div>

    <nav class="px-3 py-5 overflow-y-auto h-[calc(100vh-145px)]">

        <?php foreach ($navigation as $section => $items): ?>

            <div class="mb-6">

                <p class="px-2 mb-2 text-[11px] font-semibold uppercase tracking-wider text-gray-400">
                    <?= htmlspecialchars($section) ?>
                </p>

                <div class="space-y-0.5">

                    <?php foreach ($items as $item): ?>

                        <?php
                        $url = $item['url'];
                        $fullUrl = $url === '#' ? '#' : $baseUrl . $url;
                        $isActive = $url !== '#' && str_ends_with($currentPath, $url);
                        ?>

                        <a href="<?= htmlspecialchars($fullUrl) ?>" class="flex items-center justify-between px-2.5 py-2 rounded-md text-[13px] transition <?= $isActive ? 'bg-gray-100 text-gray-900 font-medium' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">

                            <div class="flex items-center gap-2.5">

                                <?php if ($item['icon'] === 'home'): ?>
                                    <svg class="w-4 h-4 <?= $isActive ? 'text-gray-700' : 'text-gray-400' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 12l9-9 9 9M5 10v10h14V10" />
                                    </svg>
                                <?php elseif ($item['icon'] === 'dashboard'): ?>
                                    <svg class="w-4 h-4 <?= $isActive ? 'text-gray-700' : 'text-gray-400' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h6v6h-6z" />
                                    </svg>
                                <?php elseif ($item['icon'] === 'notification'): ?>
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 17h5l-1.5-1.5A2 2 0 0118 14v-3a6 6 0 00-12 0v3a2 2 0 01-.5 1.5L4 17h5" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10 21h4" />
                                    </svg>
                                <?php elseif ($item['icon'] === 'settings'): ?>
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 15.5a3.5 3.5 0 100-7 3.5 3.5 0 000 7z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19.4 15a1.7 1.7 0 00.3 1.9l.1.1-1.8 1.8-.1-.1a1.7 1.7 0 00-1.9-.3 1.7 1.7 0 00-1 1.5V20h-2.5v-.1a1.7 1.7 0 00-1-1.5 1.7 1.7 0 00-1.9.3l-.1.1-1.8-1.8.1-.1a1.7 1.7 0 00.3-1.9 1.7 1.7 0 00-1.5-1H6v-2.5h.1a1.7 1.7 0 001.5-1 1.7 1.7 0 00-.3-1.9l-.1-.1L9 6.7l.1.1a1.7 1.7 0 001.9.3 1.7 1.7 0 001-1.5V5h2.5v.1a1.7 1.7 0 001 1.5 1.7 1.7 0 001.9-.3l.1-.1 1.8 1.8-.1.1a1.7 1.7 0 00-.3 1.9 1.7 1.7 0 001.5 1h.1v2.5h-.1a1.7 1.7 0 00-1.5 1z" />
                                    </svg>
                                <?php elseif ($item['icon'] === 'users'): ?>
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2" />
                                        <circle cx="9" cy="7" r="4" fill="none" stroke="currentColor" stroke-width="1.8" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M22 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75" />
                                    </svg>
                                <?php elseif ($item['icon'] === 'orders'): ?>
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 6h16v14H4zM8 6V4h8v2M8 10h8M8 14h5" />
                                    </svg>
                                <?php elseif ($item['icon'] === 'inventory'): ?>
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 7h16v13H4zM8 7V4h8v3M8 11h8M8 15h5" />
                                    </svg>
                                <?php elseif ($item['icon'] === 'reports'): ?>
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 19V5M4 19h16" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 16v-5M12 16V8M16 16v-7" />
                                    </svg>
                                <?php endif; ?>

                                <span><?= htmlspecialchars($item['label']) ?></span>

                            </div>

                            <?php if (isset($item['badge'])): ?>
                                <span class="min-w-5 h-5 px-1.5 flex items-center justify-center rounded-full bg-gray-100 text-[10px] font-medium text-gray-500">
                                    <?= htmlspecialchars($item['badge']) ?>
                                </span>
                            <?php endif; ?>

                        </a>

                    <?php endforeach; ?>

                </div>

            </div>

        <?php endforeach; ?>

        <div class="pt-2">
            <p class="px-2 mb-2 text-[11px] font-semibold uppercase tracking-wider text-gray-400">Account</p>
            <a href="#" class="flex items-center gap-2.5 px-2.5 py-2 rounded-md text-[13px] text-gray-600 hover:bg-gray-50 hover:text-gray-900">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 16l4-4m0 0l-4-4m4 4H7" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13 5H5a2 2 0 00-2 2v10a2 2 0 002 2h8" />
                </svg>
                Logout
            </a>
        </div>

    </nav>

</aside>