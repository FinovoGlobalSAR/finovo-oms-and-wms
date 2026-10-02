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

    'Division' => [
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
            'icon'  => 'truck',
            'roles' => ['admin', 'manager', 'warehouse staff'],
            'requires_store' => true,
            'children' => [
                [
                    'label' => 'Shipment List',
                    'url'   => '/shipments',
                ],
                [
                    'label' => 'LM Inventory Scan',
                    'url'   => '/3pl/scan',
                ],
                [
                    'label' => '3PL Remittance',
                    'url'   => '/3pl/remittance',
                ],
            ],
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
            'label' => 'Integration Settings',
            'icon'  => 'mapping',
            'roles' => ['admin', 'manager'],
            'requires_store' => true,
            'children' => [
                [
                    'label' => 'SKU Mappings',
                    'url'   => '/sku-mappings',
                ],
                [
                    'label' => 'Field Mapping',
                    'url'   => '/field-mappings',
                ],
            ],
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
// ---------- Sidebar icons (UI only) ----------
$sidebarIcons = [
    'dashboard' => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/>',
    'warehouse' => '<path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V21h14V9.5"/><path d="M10 21v-6h4v6"/>',
    'store'     => '<path d="M3 9l1.5-5h15L21 9"/><path d="M3 9h18v1.5a3 3 0 0 1-6 0 3 3 0 0 1-6 0 3 3 0 0 1-6 0z"/><path d="M5 13v8h14v-8"/>',
    'orders'    => '<path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><path d="M14 3v6h6"/><path d="M8 13h8M8 17h6"/>',
    'inventory' => '<path d="M21 8 12 3 3 8v8l9 5 9-5z"/><path d="M3 8l9 5 9-5"/><path d="M12 13v8"/>',
    'truck'     => '<path d="M3 6h11v10H3z"/><path d="M14 10h4l3 3v3h-7"/><circle cx="7" cy="18" r="2"/><circle cx="17" cy="18" r="2"/>',
    'checklist' => '<rect x="3" y="3" width="18" height="18" rx="2"/><path d="m8 12 3 3 5-6"/>',
    'clipboard' => '<rect x="8" y="2" width="8" height="4" rx="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/>',
    'transfer'  => '<path d="M8 3 4 7l4 4"/><path d="M4 7h16"/><path d="m16 21 4-4-4-4"/><path d="M20 17H4"/>',
    'return'    => '<path d="M9 14 4 9l5-5"/><path d="M4 9h10.5a5.5 5.5 0 0 1 0 11H11"/>',
    'users'     => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.9"/><path d="M16 3.1a4 4 0 0 1 0 7.8"/>',
    'employees' => '<circle cx="12" cy="8" r="4"/><path d="M4 21v-1a6 6 0 0 1 6-6h4a6 6 0 0 1 6 6v1"/>',
    'mapping'   => '<path d="M4 21v-7M4 10V3M12 21v-9M12 8V3M20 21v-5M20 12V3M1 14h6M9 8h6M17 16h6"/>',
    'alert'     => '<path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/><path d="M12 9v4M12 17h.01"/>',
    'audit'     => '<path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/>',
    'logout'    => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/>',
];

function sidebarIcon(array $icons, string $name, string $class = 'w-[17px] h-[17px]'): string
{
    $paths = $icons[$name] ?? $icons['orders'];
    return '<svg class="' . $class . ' flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $paths . '</svg>';
}

?>
<aside id="sidebar" class="fixed inset-y-0 left-0 z-40 w-64 bg-white border-r border-gray-200 transform -translate-x-full lg:translate-x-0 transition-transform duration-300 flex flex-col">

    <!-- Logo -->
    <div class="px-5 pt-5 pb-3 flex items-center gap-2.5">
        <a href="/dashboard" class="w-9 h-9 rounded-[9px] bg-blue-700 flex items-center justify-center flex-shrink-0">
            <span class="text-white font-bold text-[17px]">F</span>
        </a>
        <div class="leading-tight">
            <p class="text-[15px] font-bold text-slate-900">Finovo</p>
            <p class="text-[11px] text-slate-500">OMS / WMS</p>
        </div>
    </div>

    <!-- Logged-in user -->
    <?php if (!empty($_SESSION['user']['name'])): ?>
        <div class="px-4 pb-3">
            <div class="flex items-center gap-2.5 px-3 py-2.5 rounded-[10px] border border-gray-200">
                <div class="w-8 h-8 rounded-full bg-slate-800 text-white flex items-center justify-center text-[11px] font-semibold flex-shrink-0">
                    <?= htmlspecialchars(strtoupper(substr($_SESSION['user']['name'], 0, 1))) ?>
                </div>
                <div class="min-w-0">
                    <p class="text-[13px] font-semibold text-slate-900 truncate"><?= htmlspecialchars($_SESSION['user']['name']) ?></p>
                    <p class="text-[11px] text-slate-500 truncate"><?= htmlspecialchars(ucwords($currentRole)) ?></p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Store being managed -->
    <?php if ($storeContextActive && $managedStoreName): ?>
        <div class="px-4 pb-2">
            <div class="flex items-center gap-2.5 pl-3 pr-2 py-2.5 rounded-[10px] bg-blue-50 border border-blue-100">
                <a href="/stores" class="flex items-center gap-2.5 min-w-0 flex-1" title="Go to Stores">
                    <span class="w-[30px] h-[30px] rounded-lg bg-white text-blue-700 flex items-center justify-center flex-shrink-0">
                        <?= sidebarIcon($sidebarIcons, 'store', 'w-4 h-4') ?>
                    </span>
                    <span class="min-w-0 leading-tight">
                        <span class="block text-[11px] font-semibold text-blue-700">Managing</span>
                        <span class="block text-[13px] font-bold text-blue-900 truncate"><?= htmlspecialchars($managedStoreName) ?></span>
                    </span>
                </a>
                <a href="/stores/exit-management" title="Exit store management" aria-label="Exit store management" class="w-7 h-7 rounded-md flex items-center justify-center text-blue-500 hover:bg-white hover:text-blue-800 flex-shrink-0">
                    <i class="bi bi-x-lg text-[12px]"></i>
                </a>
            </div>
        </div>
    <?php endif; ?>

    <!-- Navigation -->
    <nav class="flex-1 overflow-y-auto px-4 pb-3">

        <?php foreach ($navigation as $section => $items): ?>

            <p class="px-2 pt-3 pb-1.5 text-[10.5px] font-semibold uppercase tracking-[0.06em] text-slate-400">
                <?= htmlspecialchars($section) ?>
            </p>

            <div class="space-y-0.5">

                <?php foreach ($items as $item): ?>

                    <?php if (!empty($item['children'])): ?>

                        <?php
                        // Dropdown ko open rakho agar current page uske kisi child ka URL hai
                        $childIsActive = false;
                        foreach ($item['children'] as $child) {
                            if (str_ends_with($currentPath, $child['url'])) {
                                $childIsActive = true;
                                break;
                            }
                        }
                        $dropdownId = 'dropdown-' . preg_replace('/[^a-z0-9]+/i', '-', strtolower($item['label']));
                        $dropdownIcon = $item['icon'] ?? 'mapping';
                        ?>

                        <button type="button"
                                aria-expanded="<?= $childIsActive ? 'true' : 'false' ?>"
                                aria-controls="<?= $dropdownId ?>"
                                onclick="var d=document.getElementById('<?= $dropdownId ?>'); d.classList.toggle('hidden'); this.querySelector('.dropdown-chevron').classList.toggle('rotate-180'); this.setAttribute('aria-expanded', d.classList.contains('hidden') ? 'false' : 'true');"
                                class="w-full flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-[13px] transition <?= $childIsActive ? 'text-slate-900 font-semibold' : 'text-slate-700 font-medium hover:bg-slate-50 hover:text-slate-900' ?>">
                            <span class="<?= $childIsActive ? 'text-blue-700' : 'text-slate-500' ?>"><?= sidebarIcon($sidebarIcons, $dropdownIcon) ?></span>
                            <span class="flex-1 text-left"><?= htmlspecialchars($item['label']) ?></span>
                            <svg class="dropdown-chevron w-3.5 h-3.5 text-slate-400 transition-transform duration-150 <?= $childIsActive ? 'rotate-180' : '' ?>" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                        </button>

                        <div id="<?= $dropdownId ?>" class="<?= $childIsActive ? '' : 'hidden' ?> ml-[18px] pl-3 border-l border-gray-200 space-y-px mb-1">
                            <?php foreach ($item['children'] as $child):
                                $childActive = str_ends_with($currentPath, $child['url']);
                            ?>
                                <a href="<?= htmlspecialchars($baseUrl . $child['url']) ?>" class="flex items-center gap-2 px-2.5 py-1.5 rounded-md text-[12.5px] transition <?= $childActive ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-slate-600 font-medium hover:bg-slate-50 hover:text-slate-900' ?>">
                                    <span class="w-[5px] h-[5px] rounded-full <?= $childActive ? 'bg-blue-700' : 'bg-slate-300' ?>"></span>
                                    <?= htmlspecialchars($child['label']) ?>
                                </a>
                            <?php endforeach; ?>
                        </div>

                    <?php else: ?>

                        <?php
                        $url = $item['url'];
                        $fullUrl = $url === '#' ? '#' : $baseUrl . $url;
                        $isActive = $url !== '#' && str_ends_with($currentPath, $url);
                        ?>

                        <a href="<?= htmlspecialchars($fullUrl) ?>" <?= $isActive ? 'aria-current="page"' : '' ?> class="relative flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-[13px] transition <?= $isActive ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-slate-700 font-medium hover:bg-slate-50 hover:text-slate-900' ?>">
                            <?php if ($isActive): ?>
                                <span class="absolute -left-4 top-1.5 bottom-1.5 w-[3px] rounded-r bg-blue-700"></span>
                            <?php endif; ?>
                            <span class="<?= $isActive ? 'text-blue-700' : 'text-slate-500' ?>"><?= sidebarIcon($sidebarIcons, $item['icon']) ?></span>
                            <span><?= htmlspecialchars($item['label']) ?></span>
                        </a>

                    <?php endif; ?>

                <?php endforeach; ?>

            </div>

        <?php endforeach; ?>

        <p class="px-2 pt-3 pb-1.5 text-[10.5px] font-semibold uppercase tracking-[0.06em] text-slate-400">Account</p>
        <a href="#" onclick="if(confirm('Are you sure you want to logout?')){window.location.href='/logout';} return false;" class="flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-[13px] font-medium text-slate-700 hover:bg-red-50 hover:text-red-700">
            <span class="text-slate-500"><?= sidebarIcon($sidebarIcons, 'logout') ?></span>
            Logout
        </a>

    </nav>

</aside>