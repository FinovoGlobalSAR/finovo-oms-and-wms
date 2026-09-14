<?php
$statusColors = [
    'pending'    => ['bg' => '#fef3c7', 'text' => '#b45309'],
    'processing' => ['bg' => '#dbeafe', 'text' => '#1d4ed8'],
    'delivered'  => ['bg' => '#dcfce7', 'text' => '#15803d'],
    'cancelled'  => ['bg' => '#fee2e2', 'text' => '#991b1b'],
];
?>

<div class="breadcrumb">Finovo <i class="bi bi-chevron-right"></i> Customers <i class="bi bi-chevron-right"></i> <?= htmlspecialchars($customerName) ?></div>

<div class="page-header-row">
    <h1><?= htmlspecialchars($customerName) ?></h1>
</div>
<p class="page-subtitle">
    <?= count($orders) ?> order(s) —
    Total spent:
    <?php foreach ($totals as $currency => $total): ?>
        <?= $currency ?> <?= number_format($total, 2) ?>&nbsp;
    <?php endforeach; ?>
</p>

<div class="card">
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
            <?php foreach ($orders as $o):
                $statusColor = $statusColors[$o['status']] ?? ['bg' => '#f3f4f6', 'text' => '#374151'];
            ?>
                <tr>
                    <td>#<?= (int) $o['id'] ?></td>
                    <td><?= htmlspecialchars($o['product_name']) ?></td>
                    <td><?= (int) $o['quantity'] ?></td>
                    <td><?= $o['currency_symbol'] ?> <?= number_format((float) $o['price'], 2) ?></td>
                    <td>
                        <span class="source-badge" style="background:<?= $statusColor['bg'] ?>; color:<?= $statusColor['text'] ?>;">
                            <?= ucfirst($o['status']) ?>
                        </span>
                    </td>
                    <td><?= date('d M Y', strtotime($o['created_at'])) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<a href="/customers" style="display:block; margin-top:16px; font-size:13px; color:var(--text-muted);">&larr; Back to Customers</a>