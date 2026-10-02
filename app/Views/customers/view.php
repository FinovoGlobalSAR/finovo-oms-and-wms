<?php
$statusColors = [
    'pending'    => ['bg' => '#fef3c7', 'text' => '#92400e'],
    'processing' => ['bg' => '#dbeafe', 'text' => '#1d4ed8'],
    'delivered'  => ['bg' => '#dcfce7', 'text' => '#166534'],
    'completed'  => ['bg' => '#dcfce7', 'text' => '#166534'],
    'cancelled'  => ['bg' => '#fee2e2', 'text' => '#991b1b'],
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
?>

<div class="ui-head">
    <div class="ui-head-left">
        <div class="breadcrumb">Finovo <i class="bi bi-chevron-right"></i> <a href="/customers" style="color:inherit; text-decoration:none;">Customers</a> <i class="bi bi-chevron-right"></i> <span class="current"><?= htmlspecialchars($customerName) ?></span></div>
        <div style="display:flex; align-items:center; gap:14px;">
            <span style="width:48px; height:48px; border-radius:50%; background:var(--primary-light); color:var(--primary); display:flex; align-items:center; justify-content:center; font-size:18px; font-weight:700; flex-shrink:0;"><?= htmlspecialchars(strtoupper(substr($customerName, 0, 1))) ?></span>
            <div>
                <h1><?= htmlspecialchars($customerName) ?></h1>
                <p style="margin-top:2px;"><?= count($orders) ?> order(s) from this customer</p>
            </div>
        </div>
    </div>
    <a href="/customers" class="ui-btn"><i class="bi bi-arrow-left"></i> Back to Customers</a>
</div>

<div class="ui-stats">
    <div class="ui-stat">
        <div class="ui-stat-icon"><i class="bi bi-bag-check"></i></div>
        <div>
            <div class="ui-stat-label">Orders</div>
            <div class="ui-stat-value"><b><?= count($orders) ?></b>
                <span class="ui-pill <?= count($orders) > 1 ? 'ui-pill-green' : 'ui-pill-gray' ?>"><?= count($orders) > 1 ? 'Repeat' : 'One-time' ?></span>
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
            <div class="ui-card-title-icon"><i class="bi bi-receipt"></i></div>
            <div>
                <h2>Order History</h2>
                <div class="sub">Every order placed by <?= htmlspecialchars($customerName) ?>.</div>
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
                    <th>Qty</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orders)): ?>
                    <tr><td colspan="6" class="ui-empty"><i class="bi bi-receipt"></i>No orders found for this customer.</td></tr>
                <?php endif; ?>
                <?php foreach ($orders as $o):
                    $statusColor = $statusColors[$o['status']] ?? ['bg' => '#f1f5f9', 'text' => '#334155'];
                ?>
                    <tr class="cv-row" data-search="<?= htmlspecialchars(strtolower('#' . $o['id'] . ' ' . $o['product_name'])) ?>">
                        <td style="font-weight:700;">#<?= (int) $o['id'] ?></td>
                        <td style="color:#334155;"><?= htmlspecialchars($o['product_name']) ?></td>
                        <td style="font-weight:600;"><?= (int) $o['quantity'] ?></td>
                        <td style="white-space:nowrap;"><?= $o['currency_symbol'] ?> <?= number_format((float) $o['price'], 2) ?></td>
                        <td>
                            <span class="ui-tag" style="background:<?= $statusColor['bg'] ?>; color:<?= $statusColor['text'] ?>;">
                                <?= ucfirst($o['status']) ?>
                            </span>
                        </td>
                        <td style="color:#334155; white-space:nowrap;"><?= date('d M Y', strtotime($o['created_at'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr id="cvNoMatch" style="display:none;"><td colspan="6" class="ui-empty"><i class="bi bi-search"></i>No orders match your search.</td></tr>
            </tbody>
        </table>
    </div>

    <div class="pagination-bar">
        <span id="cvShowing">Showing <?= count($orders) ?> of <?= count($orders) ?> orders</span>
    </div>
</div>

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
        document.getElementById('cvShowing').textContent = 'Showing ' + shown + ' of ' + rows.length + ' orders';
    });
})();
</script>