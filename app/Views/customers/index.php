<?php
$avatarColors = [
    ['bg' => '#dbeafe', 'text' => '#1d4ed8'],
    ['bg' => '#dcfce7', 'text' => '#15803d'],
    ['bg' => '#fce7f3', 'text' => '#be185d'],
    ['bg' => '#fef3c7', 'text' => '#b45309'],
    ['bg' => '#e0e7ff', 'text' => '#4338ca'],
    ['bg' => '#cffafe', 'text' => '#0e7490'],
];

// ---------- Summary numbers (sirf display ke liye, $customers se hi) ----------
$totalOrders = 0;
$repeatCount = 0;
foreach ($customers as $c) {
    $totalOrders += (int) $c['order_count'];
    if ((int) $c['order_count'] > 1) { $repeatCount++; }
}
$oneTimeCount = count($customers) - $repeatCount;
?>

<div class="ui-head">
    <div class="ui-head-left">
        <div class="breadcrumb">Finovo <i class="bi bi-chevron-right"></i> <span class="current">Customers</span></div>
        <h1>Customers <span class="count-badge"><?= count($customers) ?></span></h1>
        <p>Customer directory built from your order history.</p>
    </div>
</div>

<div class="ui-stats">
    <div class="ui-stat">
        <div class="ui-stat-icon"><i class="bi bi-people"></i></div>
        <div>
            <div class="ui-stat-label">Total Customers</div>
            <div class="ui-stat-value"><b><?= count($customers) ?></b><span class="ui-pill ui-pill-blue">All</span></div>
        </div>
    </div>
    <div class="ui-stat">
        <div class="ui-stat-icon"><i class="bi bi-bag-check"></i></div>
        <div>
            <div class="ui-stat-label">Total Orders</div>
            <div class="ui-stat-value"><b><?= $totalOrders ?></b><span class="ui-pill ui-pill-blue">From customers</span></div>
        </div>
    </div>
    <div class="ui-stat">
        <div class="ui-stat-icon"><i class="bi bi-arrow-repeat"></i></div>
        <div>
            <div class="ui-stat-label">Repeat Customers</div>
            <div class="ui-stat-value"><b><?= $repeatCount ?></b><span class="ui-pill ui-pill-green">2+ orders</span></div>
        </div>
    </div>
    <div class="ui-stat">
        <div class="ui-stat-icon"><i class="bi bi-person"></i></div>
        <div>
            <div class="ui-stat-label">One-time Customers</div>
            <div class="ui-stat-value"><b><?= $oneTimeCount ?></b><span class="ui-pill ui-pill-gray">1 order</span></div>
        </div>
    </div>
</div>

<div class="ui-card">
    <div class="cust-toolbar">
        <label class="ui-search cust-search">
            <i class="bi bi-search"></i>
            <input type="text" id="custSearch" placeholder="Search customers by name..." aria-label="Search customers">
        </label>
        <select id="custFilter" class="ui-btn" aria-label="Filter customers" style="padding-right:10px;">
            <option value="">All customers</option>
            <option value="repeat">Repeat customers</option>
            <option value="once">One-time customers</option>
        </select>
    </div>

    <div style="overflow-x:auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Orders</th>
                    <th>Total Spent</th>
                    <th>Last Order</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($customers)): ?>
                    <tr><td colspan="5" class="ui-empty"><i class="bi bi-people"></i>No customers yet. Customers appear here automatically once they place an order.</td></tr>
                <?php else: ?>
                    <?php $i = 0; foreach ($customers as $c):
                        $avColor = $avatarColors[$i % count($avatarColors)]; $i++;
                        $isRepeat = (int) $c['order_count'] > 1;
                    ?>
                        <tr class="cust-row" data-type="<?= $isRepeat ? 'repeat' : 'once' ?>" data-search="<?= htmlspecialchars(strtolower($c['customer_name'])) ?>">
                            <td>
                                <a href="/customers/view?name=<?= urlencode($c['customer_name']) ?>" style="display:flex; align-items:center; gap:10px; text-decoration:none; color:inherit;">
                                    <span style="width:32px; height:32px; border-radius:50%; background:<?= $avColor['bg'] ?>; color:<?= $avColor['text'] ?>; display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:700; flex-shrink:0;"><?= htmlspecialchars(strtoupper(substr($c['customer_name'], 0, 1))) ?></span>
                                    <span style="font-weight:600; color:var(--text-dark);"><?= htmlspecialchars($c['customer_name']) ?></span>
                                    <?php if ($isRepeat): ?><span class="ui-pill ui-pill-green">Repeat</span><?php endif; ?>
                                </a>
                            </td>
                            <td style="font-weight:600;"><?= (int) $c['order_count'] ?></td>
                            <td style="white-space:nowrap;">
                                <?php foreach ($c['totals'] as $currency => $total): ?>
                                    <div style="font-weight:600;"><?= $currency ?> <?= number_format($total, 2) ?></div>
                                <?php endforeach; ?>
                            </td>
                            <td style="color:#334155; white-space:nowrap;"><?= date('d M Y', strtotime($c['last_order'])) ?></td>
                            <td style="text-align:right;">
                                <a href="/customers/view?name=<?= urlencode($c['customer_name']) ?>" class="ui-btn ui-btn-soft ui-btn-sm"><i class="bi bi-eye"></i> View</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <tr id="custNoMatch" style="display:none;"><td colspan="5" class="ui-empty"><i class="bi bi-search"></i>No customers match your search.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="pagination-bar">
        <span id="custShowing">Showing <?= count($customers) ?> of <?= count($customers) ?> customers</span>
    </div>
</div>

<style>
.cust-toolbar { display: flex; align-items: center; gap: 8px; flex-wrap: nowrap; padding: 14px 20px; border-bottom: 1px solid var(--border-color); overflow-x: auto; }
.cust-search { flex: 0 1 380px; min-width: 180px; height: 34px; }
.cust-toolbar .ui-btn { height: 34px; padding: 0 10px; font-size: 12.5px; white-space: nowrap; }
</style>

<script>
(function () {
    var input = document.getElementById('custSearch');
    var filter = document.getElementById('custFilter');
    var rows = Array.prototype.slice.call(document.querySelectorAll('.cust-row'));
    function apply() {
        var q = input.value.trim().toLowerCase();
        var t = filter.value;
        var shown = 0;
        rows.forEach(function (r) {
            var ok = (q === '' || r.dataset.search.indexOf(q) !== -1) && (t === '' || r.dataset.type === t);
            r.style.display = ok ? '' : 'none';
            if (ok) { shown++; }
        });
        var nm = document.getElementById('custNoMatch');
        if (nm) { nm.style.display = shown === 0 ? '' : 'none'; }
        document.getElementById('custShowing').textContent = 'Showing ' + shown + ' of ' + rows.length + ' customers';
    }
    input.addEventListener('input', apply);
    filter.addEventListener('change', apply);
})();
</script>