<?php
require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/../../Models/Store.php';
require_once __DIR__ . '/../../Models/Product.php';
require_once __DIR__ . '/../../Models/Warehouse.php';

$lowStockProducts = [];
$warehouseAlerts = [];

try {
    $headerStoreModel = new Store();
    $headerStore = $headerStoreModel->first();

    if ($headerStore) {
        $headerProductModel = new Product();
        $lowStockProducts = $headerProductModel->lowStockList($headerStore['id']);

        $headerWarehouseModel = new Warehouse();
        $warehouseAlerts = $headerWarehouseModel->warehouseStockAlerts($headerStore['id']);
    }
} catch (Throwable $e) {
    $lowStockProducts = [];
    $warehouseAlerts = [];
}

// Total badge count = overall low-stock products + every per-warehouse alert item
$warehouseAlertItemCount = 0;
foreach ($warehouseAlerts as $wh) {
    $warehouseAlertItemCount += count($wh['items']);
}

$lowStockCountForBell = count($lowStockProducts) + $warehouseAlertItemCount;

// ---------- Global page search: same page list as the sidebar, filtered by role ----------
$headerCurrentRole = strtolower(trim($_SESSION['user']['role'] ?? ''));

$searchablePages = [
    ['label' => 'Dashboard', 'url' => '/dashboard', 'icon' => 'bi-grid'],
    ['label' => 'Orders', 'url' => '/orders', 'icon' => 'bi-bag-check', 'roles' => ['admin', 'manager', 'sales staff']],
    ['label' => 'Add Order', 'url' => '/orders/create', 'icon' => 'bi-plus-circle', 'roles' => ['admin', 'manager', 'sales staff']],
    ['label' => 'Order Settings', 'url' => '/orders/settings', 'icon' => 'bi-gear', 'roles' => ['admin', 'manager', 'sales staff']],
    ['label' => 'Products', 'url' => '/products', 'icon' => 'bi-box-seam', 'roles' => ['admin', 'manager', 'warehouse staff']],
    ['label' => 'Warehouses', 'url' => '/warehouses', 'icon' => 'bi-building', 'roles' => ['admin', 'manager', 'warehouse staff']],
    ['label' => 'Shipments', 'url' => '/shipments', 'icon' => 'bi-truck', 'roles' => ['admin', 'manager', 'warehouse staff']],
    ['label' => 'Picking', 'url' => '/picking', 'icon' => 'bi-check2-square', 'roles' => ['admin', 'manager', 'warehouse staff']],
    ['label' => 'Purchase Orders', 'url' => '/purchase-orders', 'icon' => 'bi-clipboard-check', 'roles' => ['admin', 'manager', 'warehouse staff']],
    ['label' => 'Suppliers', 'url' => '/suppliers', 'icon' => 'bi-truck', 'roles' => ['admin', 'manager', 'warehouse staff']],
    ['label' => 'Stock Movements', 'url' => '/stock', 'icon' => 'bi-arrow-left-right', 'roles' => ['admin', 'manager', 'warehouse staff']],
    ['label' => 'Returns', 'url' => '/returns', 'icon' => 'bi-arrow-return-left', 'roles' => ['admin', 'manager', 'sales staff']],
    ['label' => 'Customers', 'url' => '/customers', 'icon' => 'bi-people', 'roles' => ['admin', 'manager', 'sales staff']],
    ['label' => 'Stores', 'url' => '/stores', 'icon' => 'bi-shop', 'roles' => ['admin', 'manager']],
    ['label' => 'Employees', 'url' => '/employees', 'icon' => 'bi-person-badge', 'roles' => ['admin', 'manager']],
];

$searchablePages = array_values(array_filter($searchablePages, function ($page) use ($headerCurrentRole) {
    if (!isset($page['roles'])) {
        return true;
    }
    return in_array($headerCurrentRole, $page['roles'], true);
}));
?>
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
                    id="pageSearchInput"
                    type="text"
                    placeholder="Search anything..."
                    autocomplete="off"
                    class="w-full h-10 pl-10 pr-14 bg-gray-50 border border-transparent rounded-xl text-sm text-gray-700 placeholder-gray-400 outline-none transition focus:bg-white focus:border-gray-200 focus:ring-2 focus:ring-gray-100"
                >

                <span
                    class="absolute right-2.5 top-1/2 -translate-y-1/2 hidden lg:flex items-center justify-center h-6 px-1.5 rounded-md bg-white border border-gray-200 text-[11px] font-medium text-gray-400"
                >
                    ⌘ K
                </span>

                <div
                    id="pageSearchDropdown"
                    class="hidden absolute left-0 right-0 mt-2 bg-white border border-gray-200 rounded-xl shadow-lg overflow-hidden z-50 max-h-[360px] overflow-y-auto"
                ></div>

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


        <div class="relative">

            <button
                id="notificationButton"
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

                <?php if ($lowStockCountForBell > 0): ?>
                    <span
                        class="absolute top-1 right-1 min-w-[16px] h-4 px-1 flex items-center justify-center rounded-full bg-red-500 text-white text-[10px] font-semibold ring-2 ring-white"
                    ><?= $lowStockCountForBell > 9 ? '9+' : $lowStockCountForBell ?></span>
                <?php endif; ?>

            </button>

            <div
                id="notificationDropdown"
                class="hidden absolute right-0 mt-2 w-[340px] bg-white border border-gray-200 rounded-xl shadow-lg overflow-hidden z-40"
            >
                <div class="px-4 py-3 border-b border-gray-100">
                    <p class="text-sm font-semibold text-gray-800">Stock alerts</p>
                    <p class="text-[12px] text-gray-400"><?= $lowStockCountForBell ?> item(s) need attention</p>
                </div>

                <div class="max-h-[360px] overflow-y-auto">

                    <?php if (empty($lowStockProducts) && empty($warehouseAlerts)): ?>
                        <div class="px-4 py-6 text-center text-sm text-gray-400">
                            All products are well stocked.
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($lowStockProducts)): ?>
                        <div class="px-4 pt-3 pb-1 text-[11px] font-semibold uppercase tracking-wider text-gray-400">
                            Overall (all warehouses combined)
                        </div>
                        <?php foreach ($lowStockProducts as $lp): ?>
                            <?php
                            $stock = (int) $lp['stock_quantity'];
                            $isOut = $stock <= 0;
                            ?>
                            <a href="/products" class="flex items-center justify-between px-4 py-2.5 hover:bg-gray-50 border-b border-gray-50">
                                <div>
                                    <p class="text-[13px] font-medium text-gray-800"><?= htmlspecialchars($lp['name']) ?></p>
                                    <p class="text-[11px] text-gray-400"><?= htmlspecialchars($lp['sku'] ?: '-') ?></p>
                                </div>
                                <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full <?= $isOut ? 'bg-red-50 text-red-600' : 'bg-amber-50 text-amber-600' ?>">
                                    <?= $isOut ? 'Out of stock' : 'Low stock (' . $stock . ')' ?>
                                </span>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <?php foreach ($warehouseAlerts as $wh): ?>
                        <div class="px-4 pt-3 pb-1 text-[11px] font-semibold uppercase tracking-wider text-gray-400">
                            <?= htmlspecialchars($wh['warehouse_name']) ?>
                        </div>
                        <?php foreach ($wh['items'] as $item): ?>
                            <a href="/warehouses/view?id=<?= $wh['warehouse_id'] ?>" class="flex items-center justify-between px-4 py-2.5 hover:bg-gray-50 border-b border-gray-50">
                                <div>
                                    <p class="text-[13px] font-medium text-gray-800"><?= htmlspecialchars($item['name']) ?></p>
                                    <p class="text-[11px] text-gray-400"><?= htmlspecialchars($item['sku'] ?: '-') ?></p>
                                </div>
                                <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full <?= $item['is_out'] ? 'bg-red-50 text-red-600' : 'bg-amber-50 text-amber-600' ?>">
                                    <?= $item['is_out'] ? 'Out of stock' : 'Low stock (' . $item['stock'] . ')' ?>
                                </span>
                            </a>
                        <?php endforeach; ?>
                    <?php endforeach; ?>

                </div>

                <a href="/warehouses" class="block text-center text-[13px] font-medium text-gray-700 py-2.5 border-t border-gray-100 hover:bg-gray-50">
                    View all warehouses
                </a>
            </div>

        </div>


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
                    <?= htmlspecialchars(strtoupper(substr($_SESSION['user']['name'] ?? 'U', 0, 1))) ?>
                </span>
            </div>


            <div class="hidden sm:block text-left leading-tight">

                <p class="text-[13px] font-semibold text-gray-800">
                    <?= htmlspecialchars($_SESSION['user']['name'] ?? 'User') ?>
                </p>

                <p class="text-[11px] text-gray-400 mt-0.5">
                    <?= htmlspecialchars(ucfirst($_SESSION['user']['role'] ?? '')) ?>
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


    const notificationButton = document.getElementById('notificationButton');
    const notificationDropdown = document.getElementById('notificationDropdown');

    if (notificationButton && notificationDropdown) {

        notificationButton.addEventListener('click', (e) => {
            e.stopPropagation();
            notificationDropdown.classList.toggle('hidden');
        });

        document.addEventListener('click', (e) => {
            if (!notificationDropdown.contains(e.target) && !notificationButton.contains(e.target)) {
                notificationDropdown.classList.add('hidden');
            }
        });

    }


    // ---------- Global page search ----------
    const searchablePages = <?= json_encode($searchablePages) ?>;
    const pageSearchInput = document.getElementById('pageSearchInput');
    const pageSearchDropdown = document.getElementById('pageSearchDropdown');
    let searchHighlightIndex = -1;
    let currentMatches = [];

    function iconSvg() {
        return `<svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 6h16v14H4zM8 6V4h8v2M8 10h8M8 14h5" /></svg>`;
    }

    function renderSearchResults(matches) {
        currentMatches = matches;
        searchHighlightIndex = matches.length > 0 ? 0 : -1;

        if (matches.length === 0) {
            pageSearchDropdown.innerHTML = `<div class="px-4 py-4 text-sm text-gray-400 text-center">No pages found.</div>`;
            pageSearchDropdown.classList.remove('hidden');
            return;
        }

        pageSearchDropdown.innerHTML = matches.map((page, i) => `
            <a href="${page.url}" data-index="${i}" class="search-result-item flex items-center gap-3 px-4 py-2.5 text-sm ${i === 0 ? 'bg-gray-50' : ''} hover:bg-gray-50 border-b border-gray-50 last:border-b-0">
                <i class="bi ${page.icon} text-gray-400"></i>
                <span class="text-gray-800 font-medium">${page.label}</span>
            </a>
        `).join('');

        pageSearchDropdown.classList.remove('hidden');
    }

    function updateHighlight() {
        const items = pageSearchDropdown.querySelectorAll('.search-result-item');
        items.forEach((item, i) => {
            if (i === searchHighlightIndex) {
                item.classList.add('bg-gray-50');
            } else {
                item.classList.remove('bg-gray-50');
            }
        });
    }

    if (pageSearchInput && pageSearchDropdown) {

        pageSearchInput.addEventListener('input', () => {
            const query = pageSearchInput.value.trim().toLowerCase();

            if (query === '') {
                pageSearchDropdown.classList.add('hidden');
                return;
            }

            const matches = searchablePages.filter(p => p.label.toLowerCase().includes(query));
            renderSearchResults(matches);
        });

        pageSearchInput.addEventListener('focus', () => {
            if (pageSearchInput.value.trim() !== '' && currentMatches.length > 0) {
                pageSearchDropdown.classList.remove('hidden');
            }
        });

        pageSearchInput.addEventListener('keydown', (e) => {
            if (currentMatches.length === 0) return;

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                searchHighlightIndex = Math.min(searchHighlightIndex + 1, currentMatches.length - 1);
                updateHighlight();
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                searchHighlightIndex = Math.max(searchHighlightIndex - 1, 0);
                updateHighlight();
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (searchHighlightIndex >= 0 && currentMatches[searchHighlightIndex]) {
                    window.location.href = currentMatches[searchHighlightIndex].url;
                }
            } else if (e.key === 'Escape') {
                pageSearchDropdown.classList.add('hidden');
                pageSearchInput.blur();
            }
        });

        document.addEventListener('click', (e) => {
            if (!pageSearchDropdown.contains(e.target) && e.target !== pageSearchInput) {
                pageSearchDropdown.classList.add('hidden');
            }
        });

        // Ctrl+K / Cmd+K to focus the search box from anywhere
        document.addEventListener('keydown', (e) => {
            if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
                e.preventDefault();
                pageSearchInput.focus();
                pageSearchInput.select();
            }
        });

    }

</script>