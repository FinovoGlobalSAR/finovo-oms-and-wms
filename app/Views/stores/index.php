<?php
$avatarColors = [
    ['bg' => '#dbeafe', 'text' => '#1d4ed8'],
    ['bg' => '#dcfce7', 'text' => '#15803d'],
    ['bg' => '#fce7f3', 'text' => '#be185d'],
    ['bg' => '#fef3c7', 'text' => '#b45309'],
    ['bg' => '#e0e7ff', 'text' => '#4338ca'],
    ['bg' => '#cffafe', 'text' => '#0e7490'],
];
?>

<div class="breadcrumb"><a href="/dashboard" class="breadcrumb-link">Finovo</a> <i class="bi bi-chevron-right"></i> Stores</div>

<div class="page-header-row">
    <h1>Stores <span class="count-badge"><?= count($stores) ?></span></h1>
</div>

<?php if (!empty($created)): ?><div class="banner banner-success">Store created.</div><?php endif; ?>
<?php if (!empty($updated)): ?><div class="banner banner-success">Store updated.</div><?php endif; ?>
<?php if (!empty($deleted)): ?><div class="banner banner-success">Store deleted.</div><?php endif; ?>
<?php if (!empty($_GET['woo_connected'])): ?><div class="banner banner-success">WooCommerce connected successfully!</div><?php endif; ?>
<?php if (!empty($_GET['shopify_connected'])): ?><div class="banner banner-success">Shopify connected successfully!</div><?php endif; ?>
<?php if (!empty($error)): ?><div class="banner banner-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<div class="card">
    <div class="filter-row">
        <div class="push-right" style="margin-left:0;">
            <button type="button" class="toolbar-btn btn-dark" onclick="openModal('createStoreModal')"><i class="bi bi-plus-lg"></i> Add store</button>
        </div>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th>Store</th>
                <th>Platform</th>
                <th>Health</th>
                <th>Linked warehouses</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($stores)): ?>
                <tr><td colspan="5" style="text-align:center; color:#6b7280; padding:30px;">No stores yet.</td></tr>
            <?php else: ?>
                <?php foreach ($stores as $s):
                    $healthColors = [
                        'connected'     => ['bg' => '#dcfce7', 'text' => '#15803d'],
                        'sync_error'    => ['bg' => '#fee2e2', 'text' => '#991b1b'],
                        'disconnected'  => ['bg' => '#f3f4f6', 'text' => '#374151'],
                    ];
                    $hStatus = $s['health_status'] ?? 'disconnected';
                    $hColor = $healthColors[$hStatus] ?? $healthColors['disconnected'];
                ?>
                    <tr>
                        <td>
                            <div class="name-cell">
                                <span class="avatar-circle" style="background:#dbeafe; color:#1d4ed8;"><i class="bi bi-shop"></i></span>
                                <?= htmlspecialchars($s['name']) ?>
                                <?php if ($storeContextActive && (int) $s['id'] === (int) $currentStoreId): ?>
                                    <span class="source-badge" style="background:#dcfce7; color:#15803d; margin-left:6px;">Managing</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td><span class="source-badge" style="background:#f3f4f6; color:#374151;"><?= htmlspecialchars(ucfirst($s['platform'])) ?></span></td>
                        <td>
                            <span class="source-badge" style="background:<?= $hColor['bg'] ?>; color:<?= $hColor['text'] ?>;" title="<?= htmlspecialchars($s['last_error'] ?? '') ?>">
                                <?= ucwords(str_replace('_', ' ', $hStatus)) ?>
                            </span>
                        </td>
                        <td>
                            <?php if (empty($s['warehouses'])): ?>
                                <span class="source-badge" style="background:#fee2e2; color:#991b1b;">No warehouse linked</span>
                            <?php else: ?>
                                <?php foreach ($s['warehouses'] as $w): ?>
                                    <span class="source-badge" style="background:#eef2ff; color:#4338ca; margin-right:4px;">
                                        <?= htmlspecialchars($w['name']) ?><?= (int) $w['is_default'] === 1 ? ' ★' : '' ?>
                                    </span>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="/stores/manage?store_id=<?= $s['id'] ?>" class="action-link"><i class="bi bi-gear"></i> Manage</a>
                            <a href="#" class="action-link" onclick="openEditModal(<?= htmlspecialchars(json_encode($s), ENT_QUOTES) ?>); return false;"><i class="bi bi-pencil"></i> Edit</a>
                            <a href="#" class="action-link" style="color:var(--red);" onclick="if(confirm('Delete store &quot;<?= htmlspecialchars($s['name'], ENT_QUOTES) ?>&quot;?')){document.getElementById('deleteStoreForm<?= $s['id'] ?>').submit();} return false;"><i class="bi bi-trash"></i> Delete</a>
                            <form id="deleteStoreForm<?= $s['id'] ?>" action="/stores/delete" method="POST" style="display:none;">
                                <input type="hidden" name="id" value="<?= $s['id'] ?>">
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

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
                <button type="button" class="toolbar-btn" onclick="connectShopify()" style="width:100%; justify-content:center;">
                    <i class="bi bi-link-45deg"></i> Connect Shopify
                </button>
            </div>

            <div class="form-group" style="background:#f0fdf4; border-radius:8px; padding:12px;">
                <p style="font-size:12px; color:#166534; margin-bottom:8px;">
                    <i class="bi bi-magic"></i> Connect WooCommerce automatically — no need to copy Consumer Key/Secret manually.
                </p>
                <input type="text" id="wooConnectUrl" placeholder="http://localhost/wordpress" style="width:100%; margin-bottom:8px; border:1px solid var(--border-color); border-radius:8px; padding:8px 10px; font-size:13px; font-family:'Inter',sans-serif;">
                <button type="button" class="toolbar-btn" onclick="connectWooCommerce()" style="width:100%; justify-content:center;">
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
    document.getElementById('editStorePsUrl').value = store.prestashop_store_url || '';
    document.getElementById('editStorePsKey').value = store.prestashop_api_key || '';
    document.getElementById('editStoreOcUrl').value = store.opencart_store_url || '';
    document.getElementById('editStoreOscUrl').value = store.oscommerce_store_url || '';
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