<?php

$baseUrl = '';

$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$currentRole = strtolower(trim($_SESSION['user']['role'] ?? ''));

// Orders/Products/Shipments waghera sirf tabhi dikhte hain jab
// kisi store pe "Manage" dabaya gaya ho.
$storeContextActive = !empty($_SESSION['store_context_active']);
$managedStoreName = null;

if ($storeContextActive && !empty($_SESSION['current_store_id'])) {
    require_once __DIR__ . '/../../Models/Store.php';
    $sidebarStoreModel = new Store();
    $sidebarStore = $sidebarStoreModel->find((int) $_SESSION['current_store_id']);
    $managedStoreName = $sidebarStore['name'] ?? null;
}

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
            'label' => 'Warehouses',
            'url'   => '/warehouses',
            'icon'  => 'warehouse',
            'roles' => ['admin', 'manager', 'warehouse staff'],
        ],
        [
            'label' => 'Stores',
            'url'   => '/stores',
            'icon'  => 'store',
            'roles' => ['admin', 'manager'],
        ],
    ],

    'Store Management' => [
        [
            'label' => 'Orders',
            'url'   => '/orders',
            'icon'  => 'orders',
            'roles' => ['admin', 'manager', 'sales staff'],
            'requires_store' => true,
        ],
        [
            'label' => 'Products',
            'url'   => '/products',
            'icon'  => 'inventory',
            'roles' => ['admin', 'manager', 'warehouse staff'],
            'requires_store' => true,
        ],
        [
            'label' => 'Shipments',
            'url'   => '/shipments',
            'icon'  => 'truck',
            'roles' => ['admin', 'manager', 'warehouse staff'],
            'requires_store' => true,
        ],
        [
            'label' => 'Picking',
            'url'   => '/picking',
            'icon'  => 'checklist',
            'roles' => ['admin', 'manager', 'warehouse staff'],
            'requires_store' => true,
        ],
        [
            'label' => 'Purchase Orders',
            'url'   => '/purchase-orders',
            'icon'  => 'clipboard',
            'roles' => ['admin', 'manager', 'warehouse staff'],
            'requires_store' => true,
        ],
        [
            'label' => 'Stock Movements',
            'url'   => '/stock',
            'icon'  => 'transfer',
            'roles' => ['admin', 'manager', 'warehouse staff'],
            'requires_store' => true,
        ],
        [
            'label' => 'Returns',
            'url'   => '/returns',
            'icon'  => 'return',
            'roles' => ['admin', 'manager', 'sales staff'],
            'requires_store' => true,
        ],
        [
            'label' => 'Customers',
            'url'   => '/customers',
            'icon'  => 'users',
            'roles' => ['admin', 'manager', 'sales staff'],
            'requires_store' => true,
        ],
        [
            'label' => 'SKU Mappings',
            'url'   => '/sku-mappings',
            'icon'  => 'mapping',
            'roles' => ['admin', 'manager'],
            'requires_store' => true,
        ],
        [
            'label' => 'Integration Errors',
            'url'   => '/integration-errors',
            'icon'  => 'alert',
            'roles' => ['admin', 'manager'],
            'requires_store' => true,
        ],
        [
            'label' => 'Audit Log',
            'url'   => '/audit-log',
            'icon'  => 'audit',
            'roles' => ['admin', 'manager'],
            'requires_store' => true,
        ],
    ],

    'Management' => [
        [
            'label' => 'Employees',
            'url'   => '/employees',
            'icon'  => 'employees',
            'roles' => ['admin', 'manager'],
        ],
    ],

];

foreach ($navigation as $section => &$items) {
    $items = array_filter($items, function ($item) use ($currentRole, $storeContextActive) {
        if (!empty($item['requires_store']) && !$storeContextActive) {
            return false;
        }
        if (!isset($item['roles'])) {
            return true;
        }
        return in_array($currentRole, $item['roles'], true);
    });
}
unset($items);

$navigation = array_filter($navigation, fn($items) => !empty($items));

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

    <?php if (!empty($_SESSION['user']['name'])): ?>
        <div class="px-4 pt-3 pb-1">
            <div class="flex items-center gap-2 px-2 py-2 rounded-lg bg-gray-50">
                <div class="w-8 h-8 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center text-xs font-semibold">
                    <?= strtoupper(substr($_SESSION['user']['name'], 0, 1)) ?>
                </div>
                <div class="min-w-0">
                    <p class="text-xs font-semibold text-gray-900 truncate"><?= htmlspecialchars($_SESSION['user']['name']) ?></p>
                    <p class="text-[11px] text-gray-500 truncate"><?= htmlspecialchars(ucwords($currentRole)) ?></p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($storeContextActive && $managedStoreName): ?>
        <div class="px-4 pt-2 pb-1">
            <div class="flex items-center justify-between px-2.5 py-2 rounded-lg bg-blue-50 border border-blue-100">
                <div class="min-w-0">
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-blue-500">Managing</p>
                    <p class="text-[12px] font-medium text-blue-800 truncate"><?= htmlspecialchars($managedStoreName) ?></p>
                </div>
                <a href="/stores/exit-management" title="Exit store management" class="text-blue-400 hover:text-blue-700 text-[11px] flex-shrink-0 ml-2">
                    <i class="bi bi-x-lg"></i>
                </a>
            </div>
        </div>
    <?php endif; ?>

    <nav class="px-3 py-4 overflow-y-auto h-[calc(100vh-190px)]">

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
                        $iconColor = $isActive ? 'text-gray-700' : 'text-gray-400';
                        ?>

                        <a href="<?= htmlspecialchars($fullUrl) ?>" class="flex items-center justify-between px-2.5 py-2 rounded-md text-[13px] transition <?= $isActive ? 'bg-gray-100 text-gray-900 font-medium' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">

                            <div class="flex items-center gap-2.5">

                                <?php if ($item['icon'] === 'dashboard'): ?>
                                    <svg class="w-4 h-4 <?= $iconColor ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h6v6h-6z" />
                                    </svg>

                                <?php elseif ($item['icon'] === 'orders'): ?>
                                    <svg class="w-4 h-4 <?= $iconColor ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 6h16v14H4zM8 6V4h8v2M8 10h8M8 14h5" />
                                    </svg>

                                <?php elseif ($item['icon'] === 'inventory'): ?>
                                    <svg class="w-4 h-4 <?= $iconColor ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 7h16v13H4zM8 7V4h8v3M8 11h8M8 15h5" />
                                    </svg>

                                <?php elseif ($item['icon'] === 'warehouse'): ?>
                                    <svg class="w-4 h-4 <?= $iconColor ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 21V9l9-6 9 6v12M3 21h18M9 21v-6h6v6" />
                                    </svg>

                                <?php elseif ($item['icon'] === 'truck'): ?>
                                    <svg class="w-4 h-4 <?= $iconColor ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 7h11v9H3zM14 10h4l3 3v3h-7z" />
                                        <circle cx="7" cy="18" r="1.6" stroke="currentColor" stroke-width="1.8" fill="none" />
                                        <circle cx="17.5" cy="18" r="1.6" stroke="currentColor" stroke-width="1.8" fill="none" />
                                    </svg>

                                <?php elseif ($item['icon'] === 'checklist'): ?>
                                    <svg class="w-4 h-4 <?= $iconColor ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 11l2 2 4-4M5 5h14v14H5z" />
                                    </svg>

                                <?php elseif ($item['icon'] === 'clipboard'): ?>
                                    <svg class="w-4 h-4 <?= $iconColor ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 4h6a1 1 0 011 1v1H8V5a1 1 0 011-1zM6 6h12v14H6z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6M9 16h4" />
                                    </svg>

                                <?php elseif ($item['icon'] === 'transfer'): ?>
                                    <svg class="w-4 h-4 <?= $iconColor ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 7h11l-3-3M17 17H6l3 3" />
                                    </svg>

                                <?php elseif ($item['icon'] === 'return'): ?>
                                    <svg class="w-4 h-4 <?= $iconColor ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 14l-4-4 4-4M5 10h9a5 5 0 015 5v1" />
                                    </svg>

                                <?php elseif ($item['icon'] === 'store'): ?>
                                    <svg class="w-4 h-4 <?= $iconColor ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 9l1-5h14l1 5M4 9v11h16V9M4 9a2 2 0 004 0 2 2 0 004 0 2 2 0 004 0 2 2 0 004 0" />
                                    </svg>

                                <?php elseif ($item['icon'] === 'employees'): ?>
                                    <svg class="w-4 h-4 <?= $iconColor ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20v-2a4 4 0 00-3-3.87M13 3.13a4 4 0 010 7.75" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M11 20v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2" />
                                        <circle cx="7" cy="8" r="4" fill="none" stroke="currentColor" stroke-width="1.8" />
                                    </svg>

                                <?php elseif ($item['icon'] === 'users'): ?>
                                    <svg class="w-4 h-4 <?= $iconColor ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2" />
                                        <circle cx="9" cy="7" r="4" fill="none" stroke="currentColor" stroke-width="1.8" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M22 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75" />
                                    </svg>

                                <?php elseif ($item['icon'] === 'alert'): ?>
                                    <svg class="w-4 h-4 <?= $iconColor ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                                    </svg>

                                <?php elseif ($item['icon'] === 'mapping'): ?>
                                    <svg class="w-4 h-4 <?= $iconColor ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <circle cx="6" cy="6" r="2.5" stroke="currentColor" stroke-width="1.8" fill="none" />
                                        <circle cx="18" cy="18" r="2.5" stroke="currentColor" stroke-width="1.8" fill="none" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8.2 7.8l7.6 8.4" />
                                    </svg>

                                <?php elseif ($item['icon'] === 'audit'): ?>
                                    <svg class="w-4 h-4 <?= $iconColor ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 5a2 2 0 012-2h2a2 2 0 012 2v0a2 2 0 01-2 2h-2a2 2 0 01-2-2v0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6M9 16h6" />
                                    </svg>

                                <?php endif; ?>

                                <span><?= htmlspecialchars($item['label']) ?></span>

                            </div>

                        </a>

                    <?php endforeach; ?>

                </div>

            </div>

        <?php endforeach; ?>

        <div class="pt-2">
            <p class="px-2 mb-2 text-[11px] font-semibold uppercase tracking-wider text-gray-400">Account</p>
            <a href="#" onclick="if(confirm('Are you sure you want to logout?')){window.location.href='/logout';} return false;" class="flex items-center gap-2.5 px-2.5 py-2 rounded-md text-[13px] text-gray-600 hover:bg-gray-50 hover:text-gray-900">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 16l4-4m0 0l-4-4m4 4H7" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13 5H5a2 2 0 00-2 2v10a2 2 0 002 2h8" />
                </svg>
                Logout
            </a>
        </div>

    </nav>

</aside>