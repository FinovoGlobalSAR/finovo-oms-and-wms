<?php

$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/dashboard';

$currentRole = strtolower(trim($_SESSION['user']['role'] ?? ''));
$isAdminOrManager = in_array($currentRole, ['admin', 'manager'], true);

$storeContextActive = !empty($_SESSION['store_context_active']);
$managedStoreName = null;
$sidebarStores = [];

if ($storeContextActive && !empty($_SESSION['current_store_id'])) {
    require_once __DIR__ . '/../../Models/Store.php';
    $sidebarStoreModel = new Store();
    $sidebarStore = $sidebarStoreModel->find((int) $_SESSION['current_store_id']);
    $managedStoreName = $sidebarStore['name'] ?? null;
    // Store badalne ka dropdown (sab roles — Sales / Warehouse Staff bhi doosre store pe ja sakte hain)
    $sidebarStores = $sidebarStoreModel->all();
}

// Sidebar mein sirf wohi pages jo Figma mein hain.
// Baaqi pages (Employees, SKU Mappings, Audit Log waghera) header ke profile menu mein hain.
$navigation = [
    'Division' => [
        ['label' => 'Dashboard', 'url' => '/dashboard', 'icon' => 'dashboard'],
    ],
    'General' => [
        ['label' => 'Warehouses', 'url' => '/warehouses', 'icon' => 'warehouse', 'roles' => ['admin', 'manager', 'warehouse staff']],
        ['label' => 'Stores', 'url' => '/stores', 'icon' => 'store', 'roles' => ['admin', 'manager']],
    ],
    'Store Management' => [
        ['label' => 'Orders', 'url' => '/orders', 'icon' => 'orders', 'roles' => ['admin', 'manager', 'sales staff'], 'requires_store' => true],
        ['label' => 'Products', 'url' => '/products', 'icon' => 'products', 'roles' => ['admin', 'manager', 'warehouse staff'], 'requires_store' => true],
        ['label' => 'Shipments', 'url' => '/shipments', 'icon' => 'truck', 'roles' => ['admin', 'manager', 'warehouse staff'], 'requires_store' => true, 'also' => ['/3pl/']],
        ['label' => 'Picking', 'url' => '/picking', 'icon' => 'picking', 'roles' => ['admin', 'manager', 'warehouse staff'], 'requires_store' => true],
        ['label' => 'Purchase Orders', 'url' => '/purchase-orders', 'icon' => 'file', 'roles' => ['admin', 'manager', 'warehouse staff'], 'requires_store' => true],
        ['label' => 'Stock Movements', 'url' => '/stock', 'icon' => 'transfer', 'roles' => ['admin', 'manager', 'warehouse staff'], 'requires_store' => true],
        ['label' => 'Returns', 'url' => '/returns', 'icon' => 'return', 'roles' => ['admin', 'manager', 'sales staff'], 'requires_store' => true],
        // Customers ke liye store "Manage" karna zaroori nahi — saare stores ke customers
        ['label' => 'Customers', 'url' => '/customers', 'icon' => 'users', 'roles' => ['admin', 'manager', 'sales staff']],
    ],
];

foreach ($navigation as $section => &$items) {
    $items = array_filter($items, function ($item) use ($currentRole, $storeContextActive) {
        if (!empty($item['requires_store']) && !$storeContextActive) {
            return false;
        }
        return !isset($item['roles']) || in_array($currentRole, $item['roles'], true);
    });
}
unset($items);
$navigation = array_filter($navigation, fn($items) => !empty($items));

$sidebarIcons = [
    'dashboard' => '<rect x="3.5" y="3.5" width="7" height="7" rx="2"/><rect x="13.5" y="3.5" width="7" height="7" rx="2"/><rect x="3.5" y="13.5" width="7" height="7" rx="2"/><rect x="13.5" y="13.5" width="7" height="7" rx="2"/>',
    'warehouse' => '<path d="M4 10.2 12 4l8 6.2V19a1 1 0 0 1-1 1h-4v-5.5h-6V20H5a1 1 0 0 1-1-1z"/>',
    'store'     => '<rect x="4.5" y="3.5" width="15" height="17" rx="2.5"/><path d="M4.5 8.5h15"/><path d="M9 11.5a3 3 0 0 0 6 0"/>',
    'orders'    => '<rect x="5" y="3" width="14" height="18" rx="2.5"/><path d="M9 3v2h6V3"/><path d="M9 11h6M9 15h6"/>',
    'products'  => '<path d="M12 3 20 7.5v9L12 21l-8-4.5v-9z"/><path d="m4 7.5 8 4.5 8-4.5M12 12v9"/>',
    'truck'     => '<path d="M2.5 6.5h11v9h-11z"/><path d="M13.5 9.5h4l3 3.2v2.8h-7"/><circle cx="6.5" cy="17" r="2"/><circle cx="17" cy="17" r="2"/>',
    'picking'   => '<rect x="4" y="4" width="16" height="16" rx="2.5"/><path d="m8.5 12 2.5 2.5 4.5-5"/>',
    'file'      => '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5"/>',
    'transfer'  => '<path d="M20 7.5H4.5M16.5 4 20 7.5 16.5 11"/><path d="M4 16.5h15.5M7.5 13 4 16.5 7.5 20"/>',
    'return'    => '<path d="M9 14 4 9l5-5"/><path d="M4 9h10a6 6 0 0 1 0 12h-2"/>',
    'users'     => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0"/><path d="M15.5 4.6a3.5 3.5 0 0 1 0 6.8M18 14.3a6.5 6.5 0 0 1 3.5 5.7"/>',
];

function sidebarIcon(array $icons, string $name, int $size = 18): string
{
    $paths = $icons[$name] ?? $icons['file'];
    return '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $paths . '</svg>';
}

function sidebarIsActive(string $currentPath, array $item): bool
{
    if ($currentPath === $item['url'] || str_starts_with($currentPath, $item['url'] . '/')) {
        return true;
    }
    foreach ($item['also'] ?? [] as $prefix) {
        if (str_starts_with($currentPath, $prefix)) {
            return true;
        }
    }
    return false;
}

$userName = (string) ($_SESSION['user']['name'] ?? '');
?>
<aside id="sidebar" class="sb fixed inset-y-0 left-0 z-40 transform -translate-x-full lg:translate-x-0 transition-transform duration-300">

    <!-- Logo -->
    <a href="/dashboard" class="sb-brand">
        <svg width="40" height="40" viewBox="0 0 49 49" fill="none" aria-hidden="true">
            <path d="M0 41.7273C0.0385166 30.1387 0.0684739 18.5545 0.11127 6.96595C0.11555 5.25119 0.278175 3.65616 1.56634 2.23646C3.17976 0.457555 5.1441 0.115458 7.33099 0.119734C16.0828 0.132563 24.8303 0.136839 33.5822 0.132563C34.4338 0.132563 35.2855 0.0769718 36.1371 0.0470383C36.702 0.0470383 37.2669 0.0470383 37.8318 0.0470383C39.0387 0.0299335 40.2455 0.0171048 41.4524 0C41.7656 0 42.079 0.0267063 42.3857 0.0901868C46.7155 0.986401 48.2749 2.85623 48.2998 7.5133C48.364 18.8324 48.3597 30.1516 48.2998 41.4707C48.2784 46.0035 45.9117 48.2742 41.3796 48.2827C30.0515 48.3084 18.7276 48.2528 7.39946 48.3426C4.9385 48.3625 2.96982 47.6186 1.31588 45.975C1.07819 45.7388 0.888045 45.4602 0.730791 45.1643C0.560837 44.8445 0.436134 44.5027 0.358341 44.149C0.309717 43.9279 0.260879 43.7069 0.212112 43.4858C0.084702 42.9082 0.0169054 42.3185 0 41.7273ZM42.5308 23.8805C42.5308 23.8746 42.5356 23.8698 42.5415 23.8698C42.5474 23.8698 42.5522 23.865 42.5522 23.8591C42.5523 21.2285 42.5693 18.5937 42.548 15.9631C42.5266 13.1536 41.3497 12.4652 38.9616 13.8763C35.4053 15.9759 31.9816 18.3449 28.284 20.1623C25.5664 21.4965 25.3567 23.455 25.6135 25.9267C25.7804 27.5302 26.4181 27.9408 27.8346 27.0641C29.953 25.7556 32.0843 24.4599 34.2412 23.2198C36.1328 22.1294 36.8732 22.5356 36.9459 24.6695C36.993 26.0207 36.9074 27.3763 36.9631 28.7276C37.0401 30.7246 36.1799 32.1058 34.4638 33.0893C31.682 34.6801 28.9987 36.4461 26.1613 37.9343C23.6192 39.2684 22.5407 41.1885 22.8232 44.0022C23.0029 45.8068 23.5122 46.136 25.1128 45.191C30.1413 42.219 35.1399 39.2 40.1642 36.2195C41.7862 35.2574 42.5651 33.8932 42.5308 31.9946C42.4966 29.2871 42.5308 26.5838 42.5308 23.8805ZM11.3196 29.0889C11.3196 29.0854 11.3225 29.0825 11.326 29.0825C11.3296 29.0825 11.3324 29.0796 11.3324 29.0761C11.3328 26.6579 11.5248 24.2183 11.2854 21.8215C10.9301 18.2509 12.4965 16.1555 15.5393 14.5006C20.3539 11.8836 24.9972 8.95011 29.692 6.11926C30.1199 5.86269 31.1428 5.76006 30.4152 4.80646C28.9131 2.85651 25.4295 2.13811 23.2383 3.42952C18.2098 6.40577 13.2283 9.4547 8.20831 12.4395C6.50502 13.453 5.74753 14.8727 5.76465 16.8483C5.80744 22.1893 5.82028 27.5345 5.75609 32.8755C5.72185 35.7534 6.93726 37.7376 9.44084 39.0974C10.7718 39.8201 11.4266 39.9869 11.3495 38.054C11.2341 35.0756 11.3195 32.0801 11.3196 29.0889ZM14.3538 27.2395C14.3538 29.3733 14.3795 31.5071 14.341 33.641C14.3196 34.7186 14.7005 34.8426 15.5992 34.4064C18.4238 33.0252 20.4609 31.195 19.9131 27.6457C19.2797 23.5576 20.662 20.5814 24.6891 19.0078C27.9288 17.7463 28.7505 15.1634 28.4552 11.9221C28.3353 10.6007 28.0358 10.481 26.9744 11.1224C23.632 13.1579 20.2854 15.1848 16.8917 17.1348C15.0043 18.2209 14.2383 19.7732 14.3282 21.8985C14.4052 23.6774 14.3496 25.4606 14.3538 27.2395Z" fill="url(#sbLogoGradient)"/>
            <defs>
                <linearGradient id="sbLogoGradient" x1="13.5763" y1="-4.94104" x2="34.7674" y2="53.2849" gradientUnits="userSpaceOnUse">
                    <stop stop-color="#2F7BFF"/>
                    <stop offset="1" stop-color="#1250DB"/>
                </linearGradient>
            </defs>
        </svg>
        <span>
            <span class="sb-brand-name">Finovo</span>
            <span class="sb-brand-sub">OMS / WMS</span>
        </span>
    </a>

    <!-- Logged-in user -->
    <?php if ($userName !== ''): ?>
        <div class="sb-user">
            <span class="sb-avatar"><?= htmlspecialchars(strtoupper(substr($userName, 0, 1))) ?></span>
            <span class="sb-user-text">
                <span class="sb-user-name"><?= htmlspecialchars($userName) ?></span>
                <span class="sb-user-role"><?= htmlspecialchars(ucwords($currentRole)) ?></span>
            </span>
        </div>
    <?php endif; ?>

    <?php if ($storeContextActive && $managedStoreName): ?>
        <!-- Store badalna: isi page pe wapas aata hai, naye store ke data ke saath -->
        <?php if (count($sidebarStores) > 1): ?>
            <form method="GET" action="/stores/switch" class="sb-store-select">
                <input type="hidden" name="redirect" value="<?= htmlspecialchars($currentPath) ?>">
                <?= sidebarIcon($sidebarIcons, 'store', 16) ?>
                <label for="sidebarStoreSwitch" class="sr-only">Switch store</label>
                <select id="sidebarStoreSwitch" name="store_id" onchange="this.form.submit()">
                    <?php foreach ($sidebarStores as $st): ?>
                        <option value="<?= (int) $st['id'] ?>" <?= (int) $st['id'] === (int) $_SESSION['current_store_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($st['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <svg class="sb-select-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                <noscript><button type="submit">Switch</button></noscript>
            </form>
        <?php endif; ?>

        <!-- Jo store abhi manage ho raha hai -->
        <?php $managingTag = $isAdminOrManager ? 'a href="/stores" title="Go to Stores"' : 'div'; ?>
        <<?= $managingTag ?> class="sb-managing">
            <span class="sb-managing-icon">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 9.5 4.5 4h15L21 9.5"/><path d="M3 9.5h18v1a3 3 0 0 1-6 0 3 3 0 0 1-6 0 3 3 0 0 1-6 0z"/><path d="M5 13.5V20h14v-6.5"/></svg>
            </span>
            <span class="sb-managing-text">
                <span class="sb-managing-label">Managing</span>
                <span class="sb-managing-name"><?= htmlspecialchars($managedStoreName) ?></span>
            </span>
            <?php if ($isAdminOrManager): ?>
                <svg class="sb-managing-chevron" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg>
            <?php endif; ?>
        </<?= $isAdminOrManager ? 'a' : 'div' ?>>
    <?php endif; ?>

    <!-- Navigation -->
    <nav class="sb-nav" aria-label="Main">
        <?php foreach ($navigation as $section => $items): ?>
            <p class="sb-section"><?= htmlspecialchars($section) ?></p>
            <?php foreach ($items as $item):
                $isActive = sidebarIsActive($currentPath, $item);
            ?>
                <a href="<?= htmlspecialchars($item['url']) ?>" class="sb-link<?= $isActive ? ' active' : '' ?>" <?= $isActive ? 'aria-current="page"' : '' ?>>
                    <?= sidebarIcon($sidebarIcons, $item['icon']) ?>
                    <span><?= htmlspecialchars($item['label']) ?></span>
                </a>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </nav>

    <!-- Need help -->
    <div class="sb-help">
        <p class="sb-help-title">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#1e50d1" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9.5"/><path d="M9.6 9.2a2.5 2.5 0 0 1 4.8 1c0 1.7-2.4 2.2-2.4 3.6"/><path d="M12 17.2h.01"/></svg>
            Need help?
        </p>
        <p class="sb-help-text">Check our documentation or contact support.</p>
    </div>

</aside>