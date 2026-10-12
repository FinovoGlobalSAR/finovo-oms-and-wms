<?php
$avatarColors = [
    ['bg' => '#dbeafe', 'text' => '#1d4ed8'],
    ['bg' => '#dcfce7', 'text' => '#15803d'],
    ['bg' => '#fce7f3', 'text' => '#be185d'],
    ['bg' => '#fef3c7', 'text' => '#b45309'],
    ['bg' => '#e0e7ff', 'text' => '#4338ca'],
    ['bg' => '#cffafe', 'text' => '#0e7490'],
];

$stores = $stores ?? [];
$selectedStoreId = (int) ($selectedStoreId ?? 0);
$showStoreColumn = count($stores) > 1;    // Ek hi store ho to "Store" filter ki zaroorat nahi
$colCount = 4;                            // Customer, Orders, Total Spent, Actions

// ---------- Upar ke numbers: controller POORI list se deta hai (sirf is page se nahi) ----------
$stats = $stats ?? ['total' => count($customers), 'orders' => 0, 'repeat' => 0, 'contact' => 0];
$totalOrders = (int) $stats['orders'];
$repeatCount = (int) $stats['repeat'];
$withContact = (int) $stats['contact'];
$search = $search ?? '';
$type = $type ?? '';
$isFiltered = $search !== '' || $type !== '' || $selectedStoreId > 0;
?>

<div class="ui-head">
    <div class="ui-head-left">
        <div class="breadcrumb">Finovo <i class="bi bi-chevron-right"></i> <span class="current">Customers</span></div>
        <h1>Customers <span class="count-badge"><?= (int) $stats['total'] ?></span></h1>
        <p>People who bought from your stores. Customers are added automatically from your platform orders (Shopify, WooCommerce, eBay...) with their contact details.</p>
    </div>
</div>

<?php if (!empty($success)): ?><div class="banner banner-success"><i class="bi bi-check-circle"></i> <?= htmlspecialchars($success) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="banner banner-error"><i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div><?php endif; ?>

<div class="ui-stats">
    <div class="ui-stat">
        <div class="ui-stat-icon"><i class="bi bi-people"></i></div>
        <div>
            <div class="ui-stat-label">Total Customers</div>
            <div class="ui-stat-value"><b><?= (int) $stats['total'] ?></b><span class="ui-pill ui-pill-blue">All</span></div>
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
        <div class="ui-stat-icon"><i class="bi bi-envelope"></i></div>
        <div>
            <div class="ui-stat-label">With Contact Details</div>
            <div class="ui-stat-value"><b><?= $withContact ?></b><span class="ui-pill ui-pill-gray">Email / phone</span></div>
        </div>
    </div>
</div>

<div class="ui-card tbl-card">
    <!-- Search / filters: GET form — server pe chalte hain, har page ka result sahi -->
    <form method="GET" action="/customers">
        <div class="tbl-head">
            <h2>All Customers</h2>
            <div class="tbl-tools">
                <div class="ui-menu">
                    <?php $nf = ($type !== '') + ($showStoreColumn && $selectedStoreId > 0); ?>
                    <button type="button" class="tbl-btn" data-menu-toggle><i class="bi bi-sliders"></i> Filters<?php if ($nf): ?> <span class="tbl-count"><?= $nf ?></span><?php endif; ?></button>
                    <div class="ui-menu-list tbl-filter-panel">
                        <?php if ($showStoreColumn): ?>
                            <label for="custStore">Store</label>
                            <select name="store" id="custStore" onchange="this.form.submit()">
                                <option value="0">All stores</option>
                                <?php foreach ($stores as $storeId => $storeName): ?>
                                    <option value="<?= (int) $storeId ?>" <?= $selectedStoreId === (int) $storeId ? 'selected' : '' ?>><?= htmlspecialchars($storeName) ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>
                        <label for="custType">Customers</label>
                        <select name="type" id="custType" onchange="this.form.submit()">
                            <option value="" <?= $type === '' ? 'selected' : '' ?>>All customers</option>
                            <option value="repeat" <?= $type === 'repeat' ? 'selected' : '' ?>>Repeat customers</option>
                            <option value="once" <?= $type === 'once' ? 'selected' : '' ?>>One-time customers</option>
                        </select>
                        <div class="tbl-filter-actions">
                            <a href="/customers?store=0" class="tbl-btn">Clear</a>
                            <button type="submit" class="tbl-btn tbl-btn-soft">Apply</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="tbl-search">
            <label class="ui-search">
                <i class="bi bi-search"></i>
                <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search by name, email, phone or city..." aria-label="Search customers">
            </label>
        </div>
    </form>

    <div style="overflow-x:auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Orders</th>
                    <th>Total Spent</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($customers)): ?>
                    <?php if ($isFiltered): ?>
                        <tr><td colspan="<?= $colCount ?>" class="ui-empty"><i class="bi bi-search"></i>No customers match your search.</td></tr>
                    <?php else: ?>
                        <tr><td colspan="<?= $colCount ?>" class="ui-empty"><i class="bi bi-people"></i>No customers yet. Customers appear here automatically when orders come in from your store.</td></tr>
                    <?php endif; ?>
                <?php else: ?>
                    <?php $i = 0; foreach ($customers as $c):
                        $avColor = $avatarColors[$i % count($avatarColors)]; $i++;
                        $orderCount = (int) $c['order_count'];
                        $isRepeat = $orderCount > 1;
                        $editData = [
                            'id' => (int) $c['id'], 'name' => $c['name'], 'email' => $c['email'] ?? '', 'phone' => $c['phone'] ?? '',
                            'address' => $c['address'] ?? '', 'city' => $c['city'] ?? '', 'country' => $c['country'] ?? '',
                        ];
                    ?>
                        <tr class="cust-row">
                            <td>
                                <a href="/customers/view?id=<?= (int) $c['id'] ?>" style="display:flex; align-items:center; gap:10px; text-decoration:none; color:inherit;">
                                    <span style="width:32px; height:32px; border-radius:50%; background:<?= $avColor['bg'] ?>; color:<?= $avColor['text'] ?>; display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:700; flex-shrink:0;"><?= htmlspecialchars(strtoupper(mb_substr($c['name'], 0, 1))) ?></span>
                                    <span style="min-width:0;">
                                        <span style="font-weight:600; color:var(--text-dark); display:flex; align-items:center; gap:6px;">
                                            <?= htmlspecialchars($c['name']) ?>
                                            <?php if ($isRepeat): ?><span class="ui-pill ui-pill-green">Repeat</span><?php endif; ?>
                                        </span>
                                        <span style="display:block; font-size:12px; color:var(--text-muted);"><?= htmlspecialchars($c['email'] ?: 'No email') ?></span>
                                    </span>
                                </a>
                            </td>
                            <td style="font-weight:600;"><?= $orderCount ?></td>
                            <td style="white-space:nowrap;">
                                <?php if (empty($c['totals'])): ?>-<?php endif; ?>
                                <?php foreach ($c['totals'] as $currency => $total): ?>
                                    <div style="font-weight:600;"><?= $currency ?> <?= number_format($total, 2) ?></div>
                                <?php endforeach; ?>
                            </td>
                            <td>
                                <div style="display:flex; justify-content:flex-end; gap:6px;">
                                    <a href="/customers/view?id=<?= (int) $c['id'] ?>" class="ui-btn ui-btn-soft ui-btn-sm"><i class="bi bi-eye"></i> View</a>
                                    <button type="button" class="ui-btn ui-btn-sm" onclick='openCustomerEdit(<?= json_encode($editData, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP) ?>)'><i class="bi bi-pencil"></i> Edit</button>
                                    <?php if (!empty($canDelete) && $orderCount === 0): ?>
                                        <form method="POST" action="/customers/delete" onsubmit="return confirm('Delete this customer? This cannot be undone.');" style="margin:0;">
                                            <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                                            <button type="submit" class="ui-btn ui-btn-sm" style="color:#dc2626;" title="Delete (customer has no orders)"><i class="bi bi-trash"></i></button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php $pagerLabel = 'customers'; require __DIR__ . '/../partials/pagination.php'; ?>
</div>

<?php $editBack = 'list'; require __DIR__ . '/_edit_modal.php'; ?>