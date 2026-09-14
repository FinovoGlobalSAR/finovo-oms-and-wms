<div class="breadcrumb">Finovo <i class="bi bi-chevron-right"></i> Customers</div>

<div class="page-header-row">
    <h1>Customers <span class="count-badge"><?= count($customers) ?></span></h1>
</div>
<p class="page-subtitle">Customer directory built from your order history.</p>

<div class="card">
    <table class="data-table">
        <thead>
            <tr>
                <th>Customer</th>
                <th>Orders</th>
                <th>Total Spent</th>
                <th>Last Order</th>
                <th style="width:60px;"></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($customers)): ?>
                <tr><td colspan="5" style="text-align:center; color:#6b7280; padding:30px;">No customers yet.</td></tr>
            <?php else: ?>
                <?php foreach ($customers as $c): ?>
                    <tr>
                        <td><?= htmlspecialchars($c['customer_name']) ?></td>
                        <td><?= (int) $c['order_count'] ?></td>
                        <td>
                            <?php foreach ($c['totals'] as $currency => $total): ?>
                                <div><?= $currency ?> <?= number_format($total, 2) ?></div>
                            <?php endforeach; ?>
                        </td>
                        <td><?= date('d M Y', strtotime($c['last_order'])) ?></td>
                        <td>
                            <a href="/customers/view?name=<?= urlencode($c['customer_name']) ?>" class="action-link">View</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>