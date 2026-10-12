<?php
// ---------- Display helpers (sirf UI ke liye) ----------
$platformInfo = [
    'shopify'     => ['label' => 'Shopify',        'icon' => 'bi-bag',             'bg' => '#e6f4ea', 'fg' => '#16a34a'],
    'woocommerce' => ['label' => 'WooCommerce',    'icon' => 'bi-cart3',           'bg' => '#f5f3ff', 'fg' => '#7c3aed'],
    'bigcommerce' => ['label' => 'BigCommerce',    'icon' => 'bi-shop-window',     'bg' => '#e0e7ff', 'fg' => '#3730a3'],
    'prestashop'  => ['label' => 'PrestaShop',     'icon' => 'bi-bag-heart',       'bg' => '#fce7f3', 'fg' => '#9d174d'],
    'opencart'    => ['label' => 'OpenCart',       'icon' => 'bi-cart',            'bg' => '#e0f2fe', 'fg' => '#075985'],
    'oscommerce'  => ['label' => 'osCommerce',     'icon' => 'bi-basket',          'bg' => '#ecfccb', 'fg' => '#3f6212'],
    'wix'         => ['label' => 'Wix',            'icon' => 'bi-window',          'bg' => '#f1f5f9', 'fg' => '#0f172a'],
    'ebay'        => ['label' => 'eBay',           'icon' => 'bi-tag',             'bg' => '#fef3c7', 'fg' => '#92400e'],
    'cj'          => ['label' => 'CJdropshipping', 'icon' => 'bi-truck',           'bg' => '#ffedd5', 'fg' => '#9a3412'],
    'magento'     => ['label' => 'Magento',        'icon' => 'bi-box2',            'bg' => '#ffe4e6', 'fg' => '#be123c'],
    'custom'      => ['label' => 'Custom Store',   'icon' => 'bi-code-slash',      'bg' => '#f1f5f9', 'fg' => '#334155'],
    'manual'      => ['label' => 'Manual',         'icon' => 'bi-file-earmark-text', 'bg' => '#f1f5f9', 'fg' => '#334155'],
];

// Figma ke status: Active (hara), Setup Incomplete (narangi), Sync Error (laal)
$statusInfo = [
    'active'     => ['label' => 'Active',           'color' => '#047857'],
    'incomplete' => ['label' => 'Setup Incomplete', 'color' => '#e48508'],
    'sync_error' => ['label' => 'Sync Error',       'color' => '#dc2626'],
];

$connectedCount = 0;
$syncErrorCount = 0;
$attentionCount = 0;
$awaitingSyncTotal = 0;
foreach ($stores as &$s) {
    $health = $s['health_status'] ?? 'disconnected';
    $isManualStore = in_array(strtolower($s['platform'] ?? 'manual'), ['manual', ''], true);

    if ($health === 'sync_error') {
        $s['ui_status'] = 'sync_error';
    } elseif (empty($s['warehouses']) || (!$isManualStore && $health !== 'connected')) {
        // Warehouse link nahi, ya platform abhi connect nahi hua
        $s['ui_status'] = 'incomplete';
    } else {
        $s['ui_status'] = 'active';
    }

    if ($health === 'connected') { $connectedCount++; }
    if ($health === 'sync_error') { $syncErrorCount++; }
    if ($s['ui_status'] !== 'active') { $attentionCount++; }
    $awaitingSyncTotal += (int) ($s['orders_awaiting_sync'] ?? 0);
}
unset($s);
$totalStores = count($stores);

// Table mein sirf latest 5 stores (sab se naye pehle). Upar ke numbers phir bhi saare stores se.
$latestStores = $stores;
usort($latestStores, fn($a, $b) => (int) $b['id'] <=> (int) $a['id']);
$latestStores = array_slice($latestStores, 0, 5);
?>

<div class="ui-head">
    <div class="ui-head-left">
        <div class="breadcrumb">Finovo <i class="bi bi-chevron-right"></i> <span class="current">Stores</span></div>
        <h1>Stores <span class="count-badge"><?= $totalStores ?></span></h1>
        <p>Manage your sales channels and link them with warehouses.</p>
    </div>
    <div class="ui-head-right">
        <button type="button" class="ui-btn ui-btn-primary" onclick="openModal('createStoreModal')"><i class="bi bi-plus-lg"></i> Add Store</button>
        <div class="ui-info">
            <div class="ui-info-icon"><i class="bi bi-shop"></i></div>
            <div>
                <strong>Connect your stores</strong>
                <span>Link your stores with warehouses to streamline inventory and order fulfillment.</span>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($created)): ?><div class="banner banner-success"><i class="bi bi-check-circle"></i> Store created.</div><?php endif; ?>
<?php if (!empty($updated)): ?><div class="banner banner-success"><i class="bi bi-check-circle"></i> Store updated.</div><?php endif; ?>
<?php if (!empty($deleted)): ?><div class="banner banner-success"><i class="bi bi-check-circle"></i> Store deleted.</div><?php endif; ?>
<?php if (!empty($_GET['woo_connected'])): ?><div class="banner banner-success"><i class="bi bi-check-circle"></i> WooCommerce connected successfully!</div><?php endif; ?>
<?php if (!empty($_GET['ebay_connected'])): ?><div class="banner banner-success"><i class="bi bi-check-circle"></i> eBay connected successfully! The access token will now refresh automatically.</div><?php endif; ?>
<?php if (!empty($_GET['shopify_connected'])): ?><div class="banner banner-success"><i class="bi bi-check-circle"></i> Shopify connected successfully!</div><?php endif; ?>
<?php if (!empty($error)): ?><div class="banner banner-error"><i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div><?php endif; ?>

<?php if ($attentionCount > 0): ?>
    <div class="ui-alert" id="storeAttention">
        <div class="ui-tip-icon"><i class="bi bi-lightbulb"></i></div>
        <div class="ui-alert-body">
            <strong>Some stores need attention</strong>
            <span><?= $attentionCount ?> <?= $attentionCount === 1 ? 'store needs' : 'stores need' ?> setup completion or <?= $attentionCount === 1 ? 'has' : 'have' ?> sync issues. Review details below.</span>
        </div>
        <a href="#allStoresCard" class="st-view-detail" onclick="filterStores('attention');">View Detail <i class="bi bi-arrow-right"></i></a>
        <button type="button" class="st-alert-close" aria-label="Dismiss" onclick="document.getElementById('storeAttention').style.display='none';"><i class="bi bi-x-lg"></i></button>
    </div>
<?php endif; ?>

<div class="ui-stats">
    <div class="ui-stat">
        <div class="ui-stat-icon"><i class="bi bi-shop"></i></div>
        <div>
            <div class="ui-stat-label">Total Stores</div>
            <div class="ui-stat-value"><b><?= $totalStores ?></b><span class="ui-pill ui-pill-blue">Active</span></div>
        </div>
    </div>
    <div class="ui-stat">
        <div class="ui-stat-icon"><i class="bi bi-link-45deg"></i></div>
        <div>
            <div class="ui-stat-label">Connected Stores</div>
            <div class="ui-stat-value"><b><?= $connectedCount ?></b>
                <?php if ($connectedCount === $totalStores && $totalStores > 0): ?>
                    <span class="ui-pill ui-pill-green">All Connected</span>
                <?php else: ?>
                    <span class="ui-pill ui-pill-gray"><?= $totalStores - $connectedCount ?> not connected</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="ui-stat">
        <div class="ui-stat-icon"><i class="bi bi-arrow-repeat"></i></div>
        <div>
            <div class="ui-stat-label">Sync Issues</div>
            <div class="ui-stat-value"><b><?= $syncErrorCount ?></b>
                <span class="ui-pill <?= $syncErrorCount > 0 ? 'ui-pill-red' : 'ui-pill-green' ?>"><?= $syncErrorCount > 0 ? 'Needs review' : 'Healthy' ?></span>
            </div>
        </div>
    </div>
    <div class="ui-stat">
        <div class="ui-stat-icon"><i class="bi bi-cart3"></i></div>
        <div>
            <div class="ui-stat-label">Order Awaiting Sync</div>
            <div class="ui-stat-value"><b><?= $awaitingSyncTotal ?></b>
                <span class="ui-pill <?= $awaitingSyncTotal > 0 ? 'ui-pill-amber' : 'ui-pill-green' ?>"><?= $awaitingSyncTotal > 0 ? 'Pending' : 'Up to date' ?></span>
            </div>
        </div>
    </div>
</div>

<div class="ui-card" id="allStoresCard" style="overflow:visible;">
    <div class="ui-card-head">
        <div class="ui-card-title">
            <div class="ui-card-title-icon"><i class="bi bi-database"></i></div>
            <div>
                <h2>All Stores</h2>
                <div class="sub">View and manage your sales channels.</div>
            </div>
        </div>
        <div class="ui-menu">
            <button type="button" class="ui-btn" data-menu-toggle id="storeFilterBtn"><i class="bi bi-sliders"></i> <span id="storeFilterLabel">Filter</span></button>
            <div class="ui-menu-list">
                <button type="button" onclick="filterStores('all')">All stores</button>
                <button type="button" onclick="filterStores('active')">Active</button>
                <button type="button" onclick="filterStores('attention')">Needs attention</button>
            </div>
        </div>
    </div>

    <div style="overflow-x:auto; overflow-y:visible;">
        <table class="data-table st-table">
            <thead>
                <tr>
                    <th style="width:200px;">Store</th>
                    <th style="width:150px;">Platform</th>
                    <th style="width:310px;">Linked Warehouses</th>
                    <th style="width:135px;">Status</th>
                    <th style="width:130px;">Last Sync</th>
                    <th style="width:130px;">Orders Awaiting Sync</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($stores)): ?>
                    <tr><td colspan="7" class="ui-empty"><i class="bi bi-shop"></i>No stores yet. Click "Add Store" to connect your first sales channel.</td></tr>
                <?php else: ?>
                    <?php foreach ($latestStores as $s):
                        $pKey = strtolower($s['platform'] ?? 'manual');
                        $pInfo = $platformInfo[$pKey] ?? ['label' => ucfirst($pKey), 'icon' => 'bi-shop', 'bg' => '#f1f5f9', 'fg' => '#334155'];
                        $sInfo = $statusInfo[$s['ui_status']];
                        $isManaging = $storeContextActive && (int) $s['id'] === (int) $currentStoreId;
                    ?>
                        <tr class="store-row" data-status="<?= $s['ui_status'] === 'active' ? 'active' : 'attention' ?>">
                            <td>
                                <div class="st-store">
                                    <span class="st-store-icon"><i class="bi bi-shop"></i></span>
                                    <div>
                                        <span class="st-store-name"><?= htmlspecialchars($s['name']) ?></span>
                                        <?php if ($isManaging): ?><span class="st-managing">Managing</span><?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="st-platform">
                                    <span class="st-platform-icon" style="background:<?= $pInfo['bg'] ?>; color:<?= $pInfo['fg'] ?>;"><i class="bi <?= $pInfo['icon'] ?>"></i></span>
                                    <?= htmlspecialchars($pInfo['label']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if (empty($s['warehouses'])): ?>
                                    <span class="ui-tag ui-pill-red"><i class="bi bi-exclamation-circle"></i> No warehouse linked</span>
                                <?php else: ?>
                                    <div class="st-wh-list">
                                        <?php foreach ($s['warehouses'] as $w): ?>
                                            <span class="st-wh" <?= (int) $w['is_default'] === 1 ? 'title="Default warehouse"' : '' ?>>
                                                <?= htmlspecialchars($w['name']) ?>
                                                <?php if ((int) $w['is_default'] === 1): ?><i class="bi bi-star-fill"></i><?php endif; ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="ui-dot" style="color:<?= $sInfo['color'] ?>; white-space:nowrap;" title="<?= htmlspecialchars($s['last_error'] ?? '') ?>"><?= $sInfo['label'] ?></span>
                            </td>
                            <td class="st-muted" style="white-space:nowrap;">
                                <?= !empty($s['last_successful_sync']) ? date('d M Y', strtotime($s['last_successful_sync'])) : 'Never' ?>
                            </td>
                            <td class="st-muted" style="text-align:center;"><?= (int) ($s['orders_awaiting_sync'] ?? 0) ?></td>
                            <td>
                                <div style="display:flex; align-items:center; justify-content:flex-end; gap:6px;">
                                    <a href="/stores/manage?store_id=<?= $s['id'] ?>" class="ui-btn ui-btn-soft ui-btn-sm"><i class="bi bi-gear-wide-connected"></i> Manage</a>
                                    <div class="ui-menu">
                                        <button type="button" class="ui-btn ui-btn-icon" data-menu-toggle aria-label="More actions for <?= htmlspecialchars($s['name']) ?>"><i class="bi bi-three-dots-vertical"></i></button>
                                        <div class="ui-menu-list">
                                            <a href="#" onclick="openEditModal(<?= htmlspecialchars(json_encode($s), ENT_QUOTES) ?>); return false;"><i class="bi bi-pencil"></i> Edit</a>
                                            <?php if (($s['platform'] ?? '') === 'ebay'): ?>
                                                <a href="/ebay-connect/connect?store_id=<?= $s['id'] ?>"><i class="bi bi-plug"></i> <?= !empty($s['ebay_refresh_token']) ? 'Reconnect eBay' : 'Connect eBay' ?></a>
                                            <?php endif; ?>
                                            <a href="#" class="danger" onclick="if(confirm('Delete store &quot;<?= htmlspecialchars($s['name'], ENT_QUOTES) ?>&quot;?')){document.getElementById('deleteStoreForm<?= $s['id'] ?>').submit();} return false;"><i class="bi bi-trash"></i> Delete</a>
                                        </div>
                                    </div>
                                </div>
                                <form id="deleteStoreForm<?= $s['id'] ?>" action="/stores/delete" method="POST" style="display:none;">
                                    <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <tr id="storeNoMatch" style="display:none;"><td colspan="7" class="ui-empty"><i class="bi bi-funnel"></i>No stores match this filter.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="pagination-bar">
        <span id="storeShowing">Showing latest <?= count($latestStores) ?> of <?= $totalStores ?> stores</span>
    </div>
</div>

<style>
    .st-view-detail { display: inline-flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 600; color: #1d59e5; text-decoration: none; margin-right: 28px; white-space: nowrap; }
    .st-view-detail:hover { text-decoration: underline; }
    .st-alert-close { border: none; background: none; cursor: pointer; color: var(--text-dark); font-size: 13px; padding: 4px; }
    /* Stores table Figma mein thoda alag hai (chhote, normal-case headings) */
    table.data-table.st-table th { padding: 12px 24px; font-size: 11px; font-weight: 600; text-transform: none; color: var(--text-body); background: var(--bg-soft); }
    table.data-table.st-table td { padding: 16px 24px; border-bottom-color: var(--border-soft); color: var(--text-dark); }
    .st-store { display: flex; align-items: center; gap: 12px; }
    .st-store-icon { width: 32px; height: 32px; flex-shrink: 0; border-radius: 8px; background: var(--primary-light); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 14px; }
    .st-store-name { display: block; font-size: 13px; font-weight: 600; color: var(--text-dark); }
    .st-managing { display: inline-block; margin-top: 2px; padding: 2px 6px; border-radius: 4px; background: #ecfdf5; color: #047857; font-size: 10px; font-weight: 600; }
    .st-platform { display: inline-flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 500; color: var(--text-dark); white-space: nowrap; }
    .st-platform-icon { width: 24px; height: 24px; border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: 13px; }
    .st-wh-list { display: flex; gap: 6px; flex-wrap: wrap; }
    .st-wh { display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; border-radius: 8px; background: var(--primary-light); border: 1px solid var(--primary-border); color: var(--primary-dark); font-size: 12px; font-weight: 500; white-space: nowrap; }
    .st-wh i { font-size: 10px; color: var(--primary-dark); }
    .st-muted { color: var(--text-body) !important; }
</style>

<script>
// Filter (All / Active / Needs attention) — browser mein, sirf latest 5 rows ke andar (pages nahi)
(function () {
    var rows = Array.prototype.slice.call(document.querySelectorAll('.store-row'));
    var total = <?= (int) $totalStores ?>;
    var filter = 'all';

    function render() {
        var shown = 0;
        rows.forEach(function (r) {
            var ok = filter === 'all' || r.dataset.status === filter;
            r.style.display = ok ? '' : 'none';
            if (ok) { shown++; }
        });
        var noMatch = document.getElementById('storeNoMatch');
        if (noMatch) { noMatch.style.display = (rows.length > 0 && shown === 0) ? '' : 'none'; }
        document.getElementById('storeShowing').textContent = 'Showing latest ' + shown + ' of ' + total + ' stores';
    }

    window.filterStores = function (value) {
        filter = value;
        var labels = { all: 'Filter', active: 'Active', attention: 'Needs attention' };
        document.getElementById('storeFilterLabel').textContent = labels[value] || 'Filter';
        render();
    };

    render();
})();
</script>

<div class="modal-backdrop" id="createStoreModal">
    <div class="modal-box">
        <div class="modal-header">
            <h2>Add Store</h2>
            <button class="modal-close" onclick="closeModal('createStoreModal')">&times;</button>
        </div>
        <form action="/stores/create" method="POST">
            <div class="form-group">
                <label>Store Name</label>
                <input type="text" name="name" placeholder="e.g. Finovo Karachi Store" required>
            </div>
            <div class="form-group">
                <label>Platform</label>
                <select name="platform" style="width:100%; border:1px solid var(--border-color); border-radius:8px; padding:8px 10px; font-size:14px; font-family:'Inter',sans-serif;">
                    <option value="manual">Manual</option>
                    <option value="shopify">Shopify</option>
                    <option value="woocommerce">WooCommerce</option>
                    <option value="custom">Custom Store</option>
                    <option value="bigcommerce">BigCommerce</option>
                    <option value="prestashop">PrestaShop</option>
                    <option value="opencart">OpenCart</option>
                    <option value="oscommerce">osCommerce</option>
                    <option value="wix">Wix</option>
                    <option value="ebay">eBay</option>
                    <option value="cj">CJdropshipping</option>
                    <option value="magento">Magento</option>
                </select>
            </div>
            <div class="form-group">
                <label>Store URL (optional, Shopify)</label>
                <input type="text" name="store_url" placeholder="yourstore.myshopify.com">
            </div>
            <div class="form-group">
                <label>WooCommerce Store URL (optional)</label>
                <input type="text" name="woocommerce_store_url" placeholder="http://localhost/wordpress">
            </div>
            <div class="form-group">
                <label>WooCommerce Consumer Key (optional)</label>
                <input type="text" name="woocommerce_consumer_key" placeholder="ck_...">
            </div>
            <div class="form-group">
                <label>WooCommerce Consumer Secret (optional)</label>
                <input type="text" name="woocommerce_consumer_secret" placeholder="cs_...">
            </div>
            <div class="form-group">
                <label>Custom Bridge URL (optional)</label>
                <input type="text" name="bridge_url" placeholder="https://customerstore.com/oms-bridge">
            </div>
            <div class="form-group">
                <label>Bridge API Key (optional)</label>
                <input type="text" name="bridge_api_key" placeholder="Generate a random key">
            </div>
            <div class="form-group">
                <label>Bridge Shared Secret (optional)</label>
                <input type="text" name="bridge_shared_secret" placeholder="Generate a random secret">
            </div>
            <div class="form-group">
                <label>BigCommerce Store Hash (optional)</label>
                <input type="text" name="bigcommerce_store_hash" placeholder="e.g. zej9n5vxfp">
            </div>
            <div class="form-group">
                <label>BigCommerce Access Token (optional)</label>
                <input type="text" name="bigcommerce_access_token">
            </div>
            <div class="form-group">
                <label>PrestaShop Store URL (optional)</label>
                <input type="text" name="prestashop_store_url" placeholder="http://localhost/prestashop">
            </div>
            <div class="form-group">
                <label>PrestaShop API Key (optional)</label>
                <input type="text" name="prestashop_api_key">
            </div>
            <div class="form-group">
                <label>OpenCart Database Name (optional)</label>
                <input type="text" name="opencart_store_url" placeholder="e.g. opencart_test">
            </div>
            <div class="form-group">
                <label>osCommerce Store URL (optional)</label>
                <input type="text" name="oscommerce_store_url" placeholder="http://localhost/oscommerce">
            </div>
            <div class="form-group">
                <label>Wix Site ID (optional)</label>
                <input type="text" name="wix_site_id" placeholder="e.g. 31713e68-9b53-4000-a708-6953a2b16712">
            </div>
            <div class="form-group">
                <label>Wix API Key (optional)</label>
                <input type="text" name="wix_api_key">
            </div>
            <div class="form-group">
                <label>eBay User Token (optional — expires every 2 hours in Sandbox)</label>
                <textarea name="ebay_user_token" rows="3" style="width:100%; border:1px solid var(--border-color); border-radius:8px; padding:8px 10px; font-size:12px; font-family:monospace;" placeholder="v^1.1#..."></textarea>
            </div>
            <div class="form-group">
                <label>CJdropshipping Email (optional)</label>
                <input type="text" name="cj_email" placeholder="e.g. you@example.com">
            </div>
            <div class="form-group">
                <label>CJdropshipping API Key (optional)</label>
                <input type="text" name="cj_api_key" placeholder="CJxxxx@api@xxxx">
            </div>
            <div class="form-group">
                <label>Magento Store URL (optional)</label>
                <input type="text" name="magento_store_url" placeholder="https://yourmagentostore.com">
            </div>
            <div class="form-group">
                <label>Magento Access Token (optional)</label>
                <input type="text" name="magento_access_token" placeholder="From Magento: System → Integrations">
            </div>
            <div class="form-group">
                <label>Link to warehouse(s)</label>
                <?php if (empty($allWarehouses)): ?>
                    <p class="modal-help" style="margin:0;">No warehouses yet — create one from the Warehouses page first, then link it here.</p>
                <?php else: ?>
                    <div style="max-height:140px; overflow-y:auto; border:1px solid var(--border-color); border-radius:8px; padding:8px 10px;">
                        <?php foreach ($allWarehouses as $w): ?>
                            <label style="display:flex; align-items:center; gap:8px; font-size:13px; font-weight:400; padding:4px 0;">
                                <input type="checkbox" name="warehouse_ids[]" value="<?= $w['id'] ?>">
                                <?= htmlspecialchars($w['name']) ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <p class="modal-help" style="margin-top:6px;">First checked warehouse becomes this store's default.</p>
                <?php endif; ?>
            </div>
            <div class="form-actions">
                <button type="button" class="btn-secondary" onclick="closeModal('createStoreModal')">Cancel</button>
                <button type="submit" class="btn-primary">Create</button>
            </div>
        </form>
    </div>
</div>

<div class="modal-backdrop" id="editStoreModal">
    <div class="modal-box">
        <div class="modal-header">
            <h2>Edit Store</h2>
            <button class="modal-close" onclick="closeModal('editStoreModal')">&times;</button>
        </div>
        <form action="/stores/update" method="POST" id="editStoreForm">
            <input type="hidden" name="id" id="editStoreId">
            <div class="form-group">
                <label>Store Name</label>
                <input type="text" name="name" id="editStoreName" required>
            </div>
            <div class="form-group">
                <label>Platform</label>
                <select name="platform" id="editStorePlatform" style="width:100%; border:1px solid var(--border-color); border-radius:8px; padding:8px 10px; font-size:14px; font-family:'Inter',sans-serif;">
                    <option value="manual">Manual</option>
                    <option value="shopify">Shopify</option>
                    <option value="woocommerce">WooCommerce</option>
                    <option value="custom">Custom Store</option>
                    <option value="bigcommerce">BigCommerce</option>
                    <option value="prestashop">PrestaShop</option>
                    <option value="opencart">OpenCart</option>
                    <option value="oscommerce">osCommerce</option>
                    <option value="wix">Wix</option>
                    <option value="ebay">eBay</option>
                    <option value="cj">CJdropshipping</option>
                    <option value="magento">Magento</option>
                </select>
            </div>
            <div class="form-group">
                <label>Store URL</label>
                <input type="text" name="store_url" id="editStoreUrl">
            </div>

            <div class="form-group" style="background:#eef2ff; border-radius:8px; padding:12px;">
                <p style="font-size:12px; color:#3730a3; margin-bottom:8px;">
                    <i class="bi bi-magic"></i> Connect Shopify automatically — no need to copy an Access Token manually.
                </p>
                <input type="text" id="shopifyConnectUrl" placeholder="yourstore.myshopify.com" style="width:100%; margin-bottom:8px; border:1px solid var(--border-color); border-radius:8px; padding:8px 10px; font-size:13px; font-family:'Inter',sans-serif;">
                <button type="button" class="ui-btn" onclick="connectShopify()" style="width:100%; justify-content:center;">
                    <i class="bi bi-link-45deg"></i> Connect Shopify
                </button>
            </div>

            <div class="form-group" style="background:#f0fdf4; border-radius:8px; padding:12px;">
                <p style="font-size:12px; color:#166534; margin-bottom:8px;">
                    <i class="bi bi-magic"></i> Connect WooCommerce automatically — no need to copy Consumer Key/Secret manually.
                </p>
                <input type="text" id="wooConnectUrl" placeholder="http://localhost/wordpress" style="width:100%; margin-bottom:8px; border:1px solid var(--border-color); border-radius:8px; padding:8px 10px; font-size:13px; font-family:'Inter',sans-serif;">
                <button type="button" class="ui-btn" onclick="connectWooCommerce()" style="width:100%; justify-content:center;">
                    <i class="bi bi-link-45deg"></i> Connect WooCommerce
                </button>
            </div>

            <div class="form-group">
                <label>WooCommerce Store URL (manual, if not using Connect above)</label>
                <input type="text" name="woocommerce_store_url" id="editStoreWcUrl">
            </div>
            <div class="form-group">
                <label>WooCommerce Consumer Key</label>
                <input type="text" name="woocommerce_consumer_key" id="editStoreWcKey">
            </div>
            <div class="form-group">
                <label>WooCommerce Consumer Secret</label>
                <input type="text" name="woocommerce_consumer_secret" id="editStoreWcSecret">
            </div>
            <div class="form-group">
                <label>Custom Bridge URL</label>
                <input type="text" name="bridge_url" id="editStoreBridgeUrl">
            </div>
            <div class="form-group">
                <label>Bridge API Key</label>
                <input type="text" name="bridge_api_key" id="editStoreBridgeKey">
            </div>
            <div class="form-group">
                <label>Bridge Shared Secret</label>
                <input type="text" name="bridge_shared_secret" id="editStoreBridgeSecret">
            </div>
            <div class="form-group">
                <label>BigCommerce Store Hash</label>
                <input type="text" name="bigcommerce_store_hash" id="editStoreBcHash">
            </div>
            <div class="form-group">
                <label>BigCommerce Access Token</label>
                <input type="text" name="bigcommerce_access_token" id="editStoreBcToken">
            </div>
            <div class="form-group" style="background:#fdf2f8; border-radius:8px; padding:12px;">
                <p style="font-size:12px; color:#9d174d; margin-bottom:8px;">
                    <i class="bi bi-key"></i> PrestaShop connection code — paste this into the Finovo module in PrestaShop (in the "Store ID" / "Connection code" box). The module only works with this code.
                </p>
                <input type="text" id="editStorePsCode" readonly onclick="this.select()" style="width:100%; border:1px solid var(--border-color); border-radius:8px; padding:8px 10px; font-size:12px; font-family:monospace; background:#fff;">
            </div>
            <div class="form-group">
                <label>PrestaShop Store URL</label>
                <input type="text" name="prestashop_store_url" id="editStorePsUrl">
            </div>
            <div class="form-group">
                <label>PrestaShop API Key</label>
                <input type="text" name="prestashop_api_key" id="editStorePsKey">
            </div>
            <div class="form-group">
                <label>OpenCart Database Name</label>
                <input type="text" name="opencart_store_url" id="editStoreOcUrl">
            </div>
            <div class="form-group">
                <label>osCommerce Store URL</label>
                <input type="text" name="oscommerce_store_url" id="editStoreOscUrl">
            </div>
            <div class="form-group">
                <label>Wix Site ID</label>
                <input type="text" name="wix_site_id" id="editStoreWixSiteId">
            </div>
            <div class="form-group">
                <label>Wix API Key</label>
                <input type="text" name="wix_api_key" id="editStoreWixApiKey">
            </div>
            <div class="form-group">
                <label>eBay User Token (expires every 2 hours in Sandbox)</label>
                <textarea name="ebay_user_token" id="editStoreEbayToken" rows="3" style="width:100%; border:1px solid var(--border-color); border-radius:8px; padding:8px 10px; font-size:12px; font-family:monospace;"></textarea>
            </div>
            <div class="form-group">
                <label>CJdropshipping Email</label>
                <input type="text" name="cj_email" id="editStoreCjEmail">
            </div>
            <div class="form-group">
                <label>CJdropshipping API Key</label>
                <input type="text" name="cj_api_key" id="editStoreCjApiKey">
            </div>
            <div class="form-group">
                <label>Magento Store URL</label>
                <input type="text" name="magento_store_url" id="editStoreMagentoUrl" placeholder="https://yourmagentostore.com">
            </div>
            <div class="form-group">
                <label>Magento Access Token</label>
                <input type="text" name="magento_access_token" id="editStoreMagentoToken" placeholder="From Magento: System → Integrations">
            </div>
            <div class="form-group">
                <label>Linked warehouse(s)</label>
                <?php if (!empty($allWarehouses)): ?>
                    <div id="editWarehouseCheckboxes" style="max-height:140px; overflow-y:auto; border:1px solid var(--border-color); border-radius:8px; padding:8px 10px;">
                        <?php foreach ($allWarehouses as $w): ?>
                            <label style="display:flex; align-items:center; gap:8px; font-size:13px; font-weight:400; padding:4px 0;">
                                <input type="checkbox" name="warehouse_ids[]" value="<?= $w['id'] ?>" class="edit-warehouse-checkbox" data-warehouse-id="<?= $w['id'] ?>">
                                <?= htmlspecialchars($w['name']) ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="form-actions">
                <button type="button" class="btn-secondary" onclick="closeModal('editStoreModal')">Cancel</button>
                <button type="submit" class="btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(id) { document.getElementById(id).classList.add('show'); }
function closeModal(id) { document.getElementById(id).classList.remove('show'); }

function openEditModal(store) {
    document.getElementById('editStoreId').value = store.id;
    document.getElementById('editStoreName').value = store.name;
    document.getElementById('editStorePlatform').value = store.platform;
    document.getElementById('editStoreUrl').value = store.store_url || '';
    document.getElementById('editStoreWcUrl').value = store.woocommerce_store_url || '';
    document.getElementById('editStoreWcKey').value = store.woocommerce_consumer_key || '';
    document.getElementById('editStoreWcSecret').value = store.woocommerce_consumer_secret || '';
    document.getElementById('editStoreBridgeUrl').value = store.bridge_url || '';
    document.getElementById('editStoreBridgeKey').value = store.bridge_api_key || '';
    document.getElementById('editStoreBridgeSecret').value = store.bridge_shared_secret || '';
    document.getElementById('editStoreBcHash').value = store.bigcommerce_store_hash || '';
    document.getElementById('editStoreBcToken').value = store.bigcommerce_access_token || '';
    document.getElementById('editStorePsCode').value = store.ps_connect_code || '';
    document.getElementById('editStorePsUrl').value = store.prestashop_store_url || '';
    document.getElementById('editStorePsKey').value = store.prestashop_api_key || '';
    document.getElementById('editStoreOcUrl').value = store.opencart_store_url || '';
    document.getElementById('editStoreOscUrl').value = store.oscommerce_store_url || '';
    document.getElementById('editStoreWixSiteId').value = store.wix_site_id || '';
    document.getElementById('editStoreWixApiKey').value = store.wix_api_key || '';
    document.getElementById('editStoreEbayToken').value = store.ebay_user_token || '';
    document.getElementById('editStoreCjEmail').value = store.cj_email || '';
    document.getElementById('editStoreCjApiKey').value = store.cj_api_key || '';
    document.getElementById('editStoreMagentoUrl').value = store.magento_store_url || '';
    document.getElementById('editStoreMagentoToken').value = store.magento_access_token || '';
    document.getElementById('wooConnectUrl').value = store.woocommerce_store_url || '';
    document.getElementById('shopifyConnectUrl').value = store.store_url || '';

    var linkedIds = (store.warehouses || []).map(function (w) { return String(w.id); });
    document.querySelectorAll('.edit-warehouse-checkbox').forEach(function (cb) {
        cb.checked = linkedIds.indexOf(cb.dataset.warehouseId) !== -1;
    });

    openModal('editStoreModal');
}

function connectWooCommerce() {
    const wpUrl = document.getElementById('wooConnectUrl').value.trim();
    const storeId = document.getElementById('editStoreId').value;

    if (!wpUrl) {
        alert('Please enter your WordPress site URL first.');
        return;
    }
    if (!storeId) {
        alert('Please save the store first, then edit it to connect WooCommerce.');
        return;
    }

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/woocommerce-connect/connect';

    const storeIdInput = document.createElement('input');
    storeIdInput.type = 'hidden';
    storeIdInput.name = 'store_id';
    storeIdInput.value = storeId;

    const wpUrlInput = document.createElement('input');
    wpUrlInput.type = 'hidden';
    wpUrlInput.name = 'wp_url';
    wpUrlInput.value = wpUrl;

    form.appendChild(storeIdInput);
    form.appendChild(wpUrlInput);
    document.body.appendChild(form);
    form.submit();
}

function connectShopify() {
    const shopUrl = document.getElementById('shopifyConnectUrl').value.trim();
    const storeId = document.getElementById('editStoreId').value;

    if (!shopUrl) {
        alert('Please enter your Shopify store URL first.');
        return;
    }
    if (!storeId) {
        alert('Please save the store first, then edit it to connect Shopify.');
        return;
    }

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/shopify-connect/connect';

    const storeIdInput = document.createElement('input');
    storeIdInput.type = 'hidden';
    storeIdInput.name = 'store_id';
    storeIdInput.value = storeId;

    const shopUrlInput = document.createElement('input');
    shopUrlInput.type = 'hidden';
    shopUrlInput.name = 'shop_url';
    shopUrlInput.value = shopUrl;

    form.appendChild(storeIdInput);
    form.appendChild(shopUrlInput);
    document.body.appendChild(form);
    form.submit();
}
</script>

<style>
.modal-box {
    max-height: 90vh;
    overflow-y: auto;
}
</style>