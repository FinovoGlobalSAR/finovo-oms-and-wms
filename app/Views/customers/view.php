<?php
$statusColors = [
    'pending'    => ['bg' => '#fef3c7', 'text' => '#92400e'],
    'processing' => ['bg' => '#dbeafe', 'text' => '#1d4ed8'],
    'delivered'  => ['bg' => '#dcfce7', 'text' => '#166534'],
    'completed'  => ['bg' => '#dcfce7', 'text' => '#166534'],
    'cancelled'  => ['bg' => '#fee2e2', 'text' => '#991b1b'],
];

$sourceLabels = [
    'shopify' => 'Shopify', 'woocommerce' => 'WooCommerce', 'bigcommerce' => 'BigCommerce',
    'prestashop' => 'PrestaShop', 'opencart' => 'OpenCart', 'oscommerce' => 'osCommerce',
    'wix' => 'Wix', 'ebay' => 'eBay', 'magento' => 'Magento', 'custom_bridge' => 'Custom Store',
    'manual' => 'Manual', 'api_push' => 'API', 'csv' => 'CSV',
];

// ---------- Summary numbers (sirf display ke liye, $orders se hi) ----------
$itemsBought = 0;
$lastOrderDate = null;
foreach ($orders as $o) {
    $itemsBought += (int) $o['quantity'];
    if ($lastOrderDate === null || strtotime($o['created_at']) > strtotime($lastOrderDate)) {
        $lastOrderDate = $o['created_at'];
    }
}

// Table mein is customer ke sirf latest 5 orders (baaki sirf database mein). Upar ke numbers phir bhi saare orders se.
$latestOrders = $orders;
usort($latestOrders, fn($a, $b) => (int) $b['id'] <=> (int) $a['id']);
$latestOrders = array_slice($latestOrders, 0, 5);

$editData = [
    'id' => (int) $customer['id'], 'name' => $customer['name'], 'email' => $customer['email'] ?? '', 'phone' => $customer['phone'] ?? '',
    'address' => $customer['address'] ?? '', 'city' => $customer['city'] ?? '', 'country' => $customer['country'] ?? '',
];

$location = implode(', ', array_filter([$customer['city'] ?? '', $customer['country'] ?? '']));
$contactRows = [
    ['bi-envelope', 'Email', $customer['email'] ?? ''],
    ['bi-telephone', 'Phone', $customer['phone'] ?? ''],
    ['bi-geo-alt', 'Address', $customer['address'] ?? ''],
    ['bi-globe2', 'City / Country', $location],
    ['bi-building', 'Store', $storeName ?? ''],
    ['bi-shop', 'Source', !empty($customer['source']) ? ($sourceLabels[$customer['source']] ?? ucfirst($customer['source'])) : ''],
    ['bi-hash', 'Platform Customer ID', $customer['external_customer_id'] ?? ''],
    ['bi-calendar-plus', 'Customer Since', !empty($customer['created_at']) ? date('d M Y', strtotime($customer['created_at'])) : ''],
];
?>

<div class="ui-head">
    <div class="ui-head-left">
        <div class="breadcrumb">Finovo <i class="bi bi-chevron-right"></i> <a href="/customers" style="color:inherit; text-decoration:none;">Customers</a> <i class="bi bi-chevron-right"></i> <span class="current"><?= htmlspecialchars($customerName) ?></span></div>
        <div style="display:flex; align-items:center; gap:14px;">
            <span style="width:48px; height:48px; border-radius:50%; background:var(--primary-light); color:var(--primary); display:flex; align-items:center; justify-content:center; font-size:18px; font-weight:700; flex-shrink:0;"><?= htmlspecialchars(strtoupper(mb_substr($customerName, 0, 1))) ?></span>
            <div>
                <h1><?= htmlspecialchars($customerName) ?></h1>
                <p style="margin-top:2px;"><?= (int) $orderCount ?> order(s) from this customer</p>
            </div>
        </div>
    </div>
    <div style="display:flex; gap:8px; flex-wrap:wrap;">
        <a href="/customers" class="ui-btn"><i class="bi bi-arrow-left"></i> Back to Customers</a>
        <button type="button" class="ui-btn ui-btn-primary" onclick='openCustomerEdit(<?= json_encode($editData, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP) ?>)'><i class="bi bi-pencil"></i> Edit Customer</button>
        <?php if (!empty($canDelete) && (int) $orderCount === 0): ?>
            <!-- Delete sirf us customer ka jis ka koi order nahi -->
            <form method="POST" action="/customers/delete" onsubmit="return confirm('Delete this customer? This cannot be undone.');" style="margin:0;">
                <input type="hidden" name="id" value="<?= (int) $customer['id'] ?>">
                <button type="submit" class="ui-btn" style="color:#dc2626;"><i class="bi bi-trash"></i> Delete</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($success)): ?><div class="banner banner-success"><i class="bi bi-check-circle"></i> <?= htmlspecialchars($success) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="banner banner-error"><i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div><?php endif; ?>

<div class="ui-stats">
    <div class="ui-stat">
        <div class="ui-stat-icon"><i class="bi bi-bag-check"></i></div>
        <div>
            <div class="ui-stat-label">Orders</div>
            <div class="ui-stat-value"><b><?= (int) $orderCount ?></b>
                <span class="ui-pill <?= $orderCount > 1 ? 'ui-pill-green' : 'ui-pill-gray' ?>"><?= $orderCount > 1 ? 'Repeat' : 'One-time' ?></span>
            </div>
        </div>
    </div>
    <div class="ui-stat">
        <div class="ui-stat-icon"><i class="bi bi-cash-stack"></i></div>
        <div style="min-width:0;">
            <div class="ui-stat-label">Total Spent</div>
            <div style="margin-top:3px;">
                <?php if (empty($totals)): ?>
                    <b style="font-size:20px;">—</b>
                <?php endif; ?>
                <?php foreach ($totals as $currency => $total): ?>
                    <div style="font-size:18px; font-weight:700; color:var(--text-dark); line-height:1.3;"><?= $currency ?> <?= number_format($total, 2) ?></div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <div class="ui-stat">
        <div class="ui-stat-icon"><i class="bi bi-box-seam"></i></div>
        <div>
            <div class="ui-stat-label">Items Bought</div>
            <div class="ui-stat-value"><b><?= $itemsBought ?></b><span class="ui-pill ui-pill-blue">Units</span></div>
        </div>
    </div>
    <div class="ui-stat">
        <div class="ui-stat-icon"><i class="bi bi-calendar-event"></i></div>
        <div>
            <div class="ui-stat-label">Last Order</div>
            <div class="ui-stat-value"><b style="font-size:18px;"><?= $lastOrderDate ? date('d M Y', strtotime($lastOrderDate)) : '—' ?></b></div>
        </div>
    </div>
</div>

<div class="ui-card">
    <div class="ui-card-head" style="padding-bottom:14px;">
        <div class="ui-card-title">
            <div class="ui-card-title-icon"><i class="bi bi-person-vcard"></i></div>
            <div>
                <h2>Contact Details</h2>
                <div class="sub">Taken from this customer's orders on the store's platform.</div>
            </div>
        </div>
    </div>
    <div class="cust-details">
        <?php foreach ($contactRows as [$icon, $label, $value]): ?>
            <div class="cust-detail">
                <div class="cust-detail-label"><i class="bi <?= $icon ?>"></i> <?= $label ?></div>
                <div class="cust-detail-value">
                    <?php if ($value === '' || $value === null): ?>
                        <span style="color:var(--text-muted);">Not provided</span>
                    <?php elseif ($label === 'Email'): ?>
                        <a href="mailto:<?= htmlspecialchars($value) ?>"><?= htmlspecialchars($value) ?></a>
                    <?php elseif ($label === 'Phone'): ?>
                        <a href="tel:<?= htmlspecialchars(preg_replace('/[^0-9+]/', '', $value)) ?>"><?= htmlspecialchars($value) ?></a>
                    <?php else: ?>
                        <?= htmlspecialchars($value) ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="ui-card">
    <div class="ui-card-head" style="padding-bottom:14px;">
        <div class="ui-card-title">
            <div class="ui-card-title-icon"><i class="bi bi-receipt"></i></div>
            <div>
                <h2>Order History</h2>
                <div class="sub">Latest 5 orders placed by <?= htmlspecialchars($customerName) ?>.</div>
            </div>
        </div>
        <label class="ui-search" style="width:260px; height:34px;">
            <i class="bi bi-search"></i>
            <input type="text" id="cvSearch" placeholder="Search product or order #..." aria-label="Search this customer's orders">
        </label>
    </div>

    <div style="overflow-x:auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Order</th>
                    <th>Product</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orders)): ?>
                    <tr><td colspan="4" class="ui-empty"><i class="bi bi-receipt"></i>No orders found for this customer.</td></tr>
                <?php endif; ?>
                <?php foreach ($latestOrders as $o):
                    $statusColor = $statusColors[$o['status']] ?? ['bg' => '#f1f5f9', 'text' => '#334155'];
                ?>
                    <tr class="cv-row" data-search="<?= htmlspecialchars(strtolower('#' . $o['id'] . ' ' . $o['product_name'] . ' ' . ($o['order_group'] ?? ''))) ?>">
                        <td>
                            <div style="font-weight:700;">#<?= (int) $o['id'] ?></div>
                            <?php if (!empty($o['order_group'])): ?>
                                <div style="font-size:11.5px; color:var(--text-muted);"><?= htmlspecialchars($o['order_group']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td style="color:#334155;">
                            <?= htmlspecialchars($o['product_name']) ?>
                            <?php if (!empty($o['variant_label'])): ?>
                                <div style="font-size:11.5px; color:var(--text-muted);"><?= htmlspecialchars($o['variant_label']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="ui-tag" style="background:<?= $statusColor['bg'] ?>; color:<?= $statusColor['text'] ?>;">
                                <?= ucfirst($o['status']) ?>
                            </span>
                        </td>
                        <td style="color:#334155; white-space:nowrap;"><?= date('d M Y', strtotime($o['created_at'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr id="cvNoMatch" style="display:none;"><td colspan="4" class="ui-empty"><i class="bi bi-search"></i>No orders match your search.</td></tr>
            </tbody>
        </table>
    </div>

    <div class="pagination-bar">
        <span id="cvShowing">Showing latest <?= count($latestOrders) ?> of <?= count($orders) ?> order lines</span>
    </div>
</div>

<?php $editBack = 'view'; require __DIR__ . '/_edit_modal.php'; ?>

<style>
.cust-details { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 14px 24px; padding: 4px 20px 20px; }
.cust-detail-label { font-size: 12px; color: var(--text-muted); display: flex; align-items: center; gap: 6px; margin-bottom: 4px; }
.cust-detail-value { font-size: 13.5px; font-weight: 600; color: var(--text-dark); word-break: break-word; }
.cust-detail-value a { color: var(--primary); text-decoration: none; }
</style>

<script>
(function () {
    var input = document.getElementById('cvSearch');
    var rows = Array.prototype.slice.call(document.querySelectorAll('.cv-row'));
    input.addEventListener('input', function () {
        var q = input.value.trim().toLowerCase();
        var shown = 0;
        rows.forEach(function (r) {
            var ok = q === '' || r.dataset.search.indexOf(q) !== -1;
            r.style.display = ok ? '' : 'none';
            if (ok) { shown++; }
        });
        document.getElementById('cvNoMatch').style.display = (rows.length > 0 && shown === 0) ? '' : 'none';
        document.getElementById('cvShowing').textContent = 'Showing latest ' + shown + ' of <?= count($orders) ?> order lines';
    });
})();
</script>