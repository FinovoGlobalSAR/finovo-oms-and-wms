<?php
$pickColors = [
    'not_started' => ['bg' => '#f3f4f6', 'text' => '#374151'],
    'picking'     => ['bg' => '#fef3c7', 'text' => '#b45309'],
    'picked'      => ['bg' => '#dbeafe', 'text' => '#1d4ed8'],
    'packed'      => ['bg' => '#dcfce7', 'text' => '#15803d'],
];
?>

<div class="breadcrumb">Finovo <i class="bi bi-chevron-right"></i> Picking</div>

<div class="page-header-row">
    <h1>Picking &amp; Packing <span class="count-badge"><?= count($orders) ?></span></h1>
</div>
<p class="page-subtitle">Orders waiting to be picked and packed before dispatch.</p>

<?php if (!empty($updated)): ?><div class="banner banner-success">Updated.</div><?php endif; ?>

<div class="card">
    <table class="data-table">
        <thead>
            <tr>
                <th>Order</th>
                <th>Customer</th>
                <th>Product</th>
                <th>Qty</th>
                <th>Status</th>
                <th style="width:200px;">Update</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($orders)): ?>
                <tr><td colspan="6" style="text-align:center; color:#6b7280; padding:30px;">Nothing to pick right now.</td></tr>
            <?php else: ?>
                <?php foreach ($orders as $o):
                    $pk = $o['picking_status'] ?? 'not_started';
                    $color = $pickColors[$pk] ?? ['bg' => '#f3f4f6', 'text' => '#374151'];
                ?>
                    <tr>
                        <td>#<?= (int) $o['id'] ?></td>
                        <td><?= htmlspecialchars($o['customer_name']) ?></td>
                        <td><?= htmlspecialchars($o['product_name']) ?><?= !empty($o['variant_label']) ? ' (' . htmlspecialchars($o['variant_label']) . ')' : '' ?></td>
                        <td><?= (int) $o['quantity'] ?></td>
                        <td>
                            <span class="source-badge" style="background:<?= $color['bg'] ?>; color:<?= $color['text'] ?>;">
                                <?= ucwords(str_replace('_', ' ', $pk)) ?>
                            </span>
                        </td>
                        <td>
                            <form method="POST" action="/picking/update-status" style="display:flex; gap:6px;">
                                <input type="hidden" name="id" value="<?= $o['id'] ?>">
                                <select name="status" style="flex:1; border:1px solid var(--border-color); border-radius:6px; padding:5px 8px; font-size:12px; font-family:'Inter',sans-serif;">
                                    <?php foreach (['not_started', 'picking', 'picked', 'packed'] as $st): ?>
                                        <option value="<?= $st ?>" <?= $pk === $st ? 'selected' : '' ?>><?= ucwords(str_replace('_', ' ', $st)) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="filter-btn" style="padding:5px 10px;"><i class="bi bi-check-lg"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>