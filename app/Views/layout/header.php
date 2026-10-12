<?php
require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/../../Models/Store.php';
require_once __DIR__ . '/../../Models/Product.php';
require_once __DIR__ . '/../../Models/Warehouse.php';

$lowStockProducts = [];
$warehouseAlerts = [];

try {
    $headerStoreModel = new Store();
    // Jo store abhi manage ho raha hai usi ke alerts dikhao (pehle hamesha pehla store aata tha)
    $headerStore = !empty($_SESSION['current_store_id'])
        ? $headerStoreModel->find((int) $_SESSION['current_store_id'])
        : null;
    $headerStore = $headerStore ?: $headerStoreModel->first();

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
// Low-stock list ke links sirf un ke liye jo Products / Warehouses page khol sakte hain (Sales Staff nahi)
$canOpenStockPages = in_array($headerCurrentRole, ['admin', 'manager', 'warehouse staff'], true);
$stockLinkTag = $canOpenStockPages ? 'a' : 'div';

$searchablePages = [
    ['label' => 'Dashboard', 'url' => '/dashboard', 'icon' => 'bi-grid'],
    ['label' => 'Orders', 'url' => '/orders', 'icon' => 'bi-bag-check', 'roles' => ['admin', 'manager', 'sales staff']],
    ['label' => 'Add Order', 'url' => '/orders/create', 'icon' => 'bi-plus-circle', 'roles' => ['admin', 'manager', 'sales staff']],
    ['label' => 'Order Settings', 'url' => '/orders/settings', 'icon' => 'bi-gear', 'roles' => ['admin', 'manager']],
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
    ['label' => 'SKU Mappings', 'url' => '/sku-mappings', 'icon' => 'bi-diagram-3', 'roles' => ['admin', 'manager']],
    ['label' => 'Field Mapping', 'url' => '/field-mappings', 'icon' => 'bi-sliders', 'roles' => ['admin', 'manager']],
    ['label' => 'Integration Errors', 'url' => '/integration-errors', 'icon' => 'bi-exclamation-triangle', 'roles' => ['admin', 'manager']],
    ['label' => 'Audit Log', 'url' => '/audit-log', 'icon' => 'bi-list-check', 'roles' => ['admin', 'manager']],
    ['label' => 'LM Inventory Scan', 'url' => '/3pl/scan', 'icon' => 'bi-upc-scan', 'roles' => ['admin', 'manager', 'warehouse staff']],
    ['label' => '3PL Remittance', 'url' => '/3pl/remittance', 'icon' => 'bi-cash-stack', 'roles' => ['admin', 'manager', 'warehouse staff']],
];

$searchablePages = array_values(array_filter($searchablePages, function ($page) use ($headerCurrentRole) {
    if (!isset($page['roles'])) {
        return true;
    }
    return in_array($headerCurrentRole, $page['roles'], true);
}));

// ---------- Profile menu: woh pages jo Figma ke sidebar mein nahi, lekin kaam ke hain ----------
$headerStoreContext = !empty($_SESSION['store_context_active']);
$profileMenuLinks = [
    ['label' => 'Employees', 'url' => '/employees', 'icon' => 'bi-person-badge', 'roles' => ['admin', 'manager']],
    ['label' => 'SKU Mappings', 'url' => '/sku-mappings', 'icon' => 'bi-diagram-3', 'roles' => ['admin', 'manager'], 'requires_store' => true],
    ['label' => 'Field Mapping', 'url' => '/field-mappings', 'icon' => 'bi-sliders', 'roles' => ['admin', 'manager'], 'requires_store' => true],
    ['label' => 'Integration Errors', 'url' => '/integration-errors', 'icon' => 'bi-exclamation-triangle', 'roles' => ['admin', 'manager'], 'requires_store' => true],
    ['label' => 'Audit Log', 'url' => '/audit-log', 'icon' => 'bi-list-check', 'roles' => ['admin', 'manager'], 'requires_store' => true],
    ['label' => 'LM Inventory Scan', 'url' => '/3pl/scan', 'icon' => 'bi-upc-scan', 'roles' => ['admin', 'manager', 'warehouse staff'], 'requires_store' => true],
    ['label' => '3PL Remittance', 'url' => '/3pl/remittance', 'icon' => 'bi-cash-stack', 'roles' => ['admin', 'manager', 'warehouse staff'], 'requires_store' => true],
    ['label' => 'Exit store management', 'url' => '/stores/exit-management', 'icon' => 'bi-box-arrow-left', 'roles' => ['admin', 'manager'], 'requires_store' => true],
];
$profileMenuLinks = array_values(array_filter($profileMenuLinks, function ($link) use ($headerCurrentRole, $headerStoreContext) {
    if (!empty($link['requires_store']) && !$headerStoreContext) {
        return false;
    }
    return in_array($headerCurrentRole, $link['roles'], true);
}));
$headerUserName = (string) ($_SESSION['user']['name'] ?? 'User');
?>
<header class="hd">

    <div class="hd-left">
        <button id="mobileMenuButton" type="button" class="hd-icon-btn lg:hidden" aria-label="Open menu">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>

        <div class="hd-search">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
            <input id="pageSearchInput" type="text" placeholder="Search anything..." autocomplete="off" aria-label="Search pages">
            <div id="pageSearchDropdown" class="hidden hd-search-results"></div>
        </div>
    </div>

    <div class="hd-right">
        <div class="hd-icons">
            <button type="button" class="hd-icon-btn" title="Help" aria-label="Help">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9.5"/><path d="M9.6 9.2a2.5 2.5 0 0 1 4.8 1c0 1.7-2.4 2.2-2.4 3.6"/><path d="M12 17.2h.01"/></svg>
            </button>

            <div class="hd-menu-wrap">
                <button id="notificationButton" type="button" class="hd-icon-btn" aria-label="Stock alerts">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 8.5a6 6 0 0 0-12 0c0 6.5-2.5 8-2.5 8h17S18 15 18 8.5"/><path d="M10.3 20a1.9 1.9 0 0 0 3.4 0"/></svg>
                    <?php if ($lowStockCountForBell > 0): ?>
                        <span class="hd-badge"><?= $lowStockCountForBell > 9 ? '9+' : $lowStockCountForBell ?></span>
                    <?php endif; ?>
                </button>

                <div id="notificationDropdown" class="hidden hd-dropdown hd-dropdown-wide">
                    <div class="hd-dropdown-head">
                        <p class="hd-dropdown-title">Stock alerts</p>
                        <p class="hd-dropdown-sub"><?= $lowStockCountForBell ?> item(s) need attention</p>
                    </div>

                    <div class="hd-dropdown-body">
                        <?php if (empty($lowStockProducts) && empty($warehouseAlerts)): ?>
                            <div class="hd-empty">All products are well stocked.</div>
                        <?php endif; ?>

                        <?php if (!empty($lowStockProducts)): ?>
                            <div class="hd-group">Overall (all warehouses combined)</div>
                            <?php foreach ($lowStockProducts as $lp):
                                $stock = (int) $lp['stock_quantity'];
                                $isOut = $stock <= 0;
                            ?>
                                <<?= $stockLinkTag ?> <?= $canOpenStockPages ? 'href="/products"' : '' ?> class="hd-alert-row">
                                    <span>
                                        <span class="hd-alert-name"><?= htmlspecialchars($lp['name']) ?></span>
                                        <span class="hd-alert-sku"><?= htmlspecialchars($lp['sku'] ?: '-') ?></span>
                                    </span>
                                    <span class="hd-alert-pill <?= $isOut ? 'out' : 'low' ?>"><?= $isOut ? 'Out of stock' : 'Low stock (' . $stock . ')' ?></span>
                                </<?= $stockLinkTag ?>>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <?php foreach ($warehouseAlerts as $wh): ?>
                            <div class="hd-group"><?= htmlspecialchars($wh['warehouse_name']) ?></div>
                            <?php foreach ($wh['items'] as $item): ?>
                                <<?= $stockLinkTag ?> <?= $canOpenStockPages ? 'href="/warehouses/view?id=' . (int) $wh['warehouse_id'] . '"' : '' ?> class="hd-alert-row">
                                    <span>
                                        <span class="hd-alert-name"><?= htmlspecialchars($item['name']) ?></span>
                                        <span class="hd-alert-sku"><?= htmlspecialchars($item['sku'] ?: '-') ?></span>
                                    </span>
                                    <span class="hd-alert-pill <?= $item['is_out'] ? 'out' : 'low' ?>"><?= $item['is_out'] ? 'Out of stock' : 'Low stock (' . $item['stock'] . ')' ?></span>
                                </<?= $stockLinkTag ?>>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </div>

                    <?php if ($canOpenStockPages): ?>
                        <a href="/warehouses" class="hd-dropdown-foot">View all warehouses</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="hd-menu-wrap">
            <button id="profileButton" type="button" class="hd-profile" aria-haspopup="true" aria-expanded="false">
                <span class="hd-avatar"><?= htmlspecialchars(strtoupper(substr($headerUserName, 0, 1))) ?></span>
                <span class="hd-profile-text">
                    <span class="hd-profile-name"><?= htmlspecialchars($headerUserName) ?></span>
                    <span class="hd-profile-role"><?= htmlspecialchars(ucwords($headerCurrentRole)) ?></span>
                </span>
            </button>

            <div id="profileDropdown" class="hidden hd-dropdown">
                <?php foreach ($profileMenuLinks as $link): ?>
                    <a href="<?= htmlspecialchars($link['url']) ?>" class="hd-menu-link">
                        <i class="bi <?= htmlspecialchars($link['icon']) ?>"></i>
                        <?= htmlspecialchars($link['label']) ?>
                    </a>
                <?php endforeach; ?>
                <?php if (!empty($profileMenuLinks)): ?><div class="hd-menu-sep"></div><?php endif; ?>
                <a href="/logout" class="hd-menu-link danger" onclick="return confirm('Are you sure you want to logout?');">
                    <i class="bi bi-box-arrow-right"></i>
                    Logout
                </a>
            </div>
        </div>
    </div>

</header>


<script>

    const mobileMenuButton = document.getElementById('mobileMenuButton');
    const sidebar = document.getElementById('sidebar');

    if (mobileMenuButton && sidebar) {
        mobileMenuButton.addEventListener('click', (e) => {
            e.stopPropagation();
            sidebar.classList.toggle('-translate-x-full');
        });

        // Mobile: sidebar ke bahar click karo to band ho jaye
        document.addEventListener('click', (e) => {
            if (window.innerWidth < 1024 && !sidebar.contains(e.target) && !sidebar.classList.contains('-translate-x-full')) {
                sidebar.classList.add('-translate-x-full');
            }
        });
    }


    // Profile menu (Employees, Audit Log, Logout waghera)
    const profileButton = document.getElementById('profileButton');
    const profileDropdown = document.getElementById('profileDropdown');

    if (profileButton && profileDropdown) {
        profileButton.addEventListener('click', (e) => {
            e.stopPropagation();
            profileDropdown.classList.toggle('hidden');
            profileButton.setAttribute('aria-expanded', profileDropdown.classList.contains('hidden') ? 'false' : 'true');
        });

        document.addEventListener('click', (e) => {
            if (!profileDropdown.contains(e.target) && !profileButton.contains(e.target)) {
                profileDropdown.classList.add('hidden');
                profileButton.setAttribute('aria-expanded', 'false');
            }
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