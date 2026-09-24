<?php
$statusColors = [
    'requested' => ['bg' => '#fef3c7', 'text' => '#b45309'],
    'approved'  => ['bg' => '#dbeafe', 'text' => '#1d4ed8'],
    'rejected'  => ['bg' => '#fee2e2', 'text' => '#991b1b'],
    'completed' => ['bg' => '#dcfce7', 'text' => '#15803d'],
];
?>

<div class="breadcrumb">Finovo <i class="bi bi-chevron-right"></i> Returns</div>

<div class="page-header-row">
    <h1>Returns <span class="count-badge"><?= count($returns) ?></span></h1>
</div>
<p class="page-subtitle">Track returned items and refunds. Completing a resellable return restores warehouse stock.</p>

<?php if (!empty($created)): ?><div class="banner banner-success">Return recorded.</div><?php endif; ?>
<?php if (!empty($updated)): ?><div class="banner banner-success">Return updated.</div><?php endif; ?>
<?php if (!empty($error)): ?><div class="banner banner-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<div class="card" style="overflow: visible;">
    <div class="filter-row">
        <div class="push-right" style="margin-left:0;">
            <button type="button" class="toolbar-btn btn-dark" onclick="openModal('createReturnModal')"><i class="bi bi-arrow-return-left"></i> New Return</button>
        </div>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th>Return</th>
                <th>Order</th>
                <th>Product</th>
                <th>Qty</th>
                <th>Refund</th>
                <th>Status</th>
                <th style="width:220px;">Update</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($returns)): ?>
                <tr><td colspan="7" style="text-align:center; color:#6b7280; padding:30px;">No returns yet.</td></tr>
            <?php else: ?>
                <?php foreach ($returns as $r):
                    $statusColor = $statusColors[$r['status']] ?? ['bg' => '#f3f4f6', 'text' => '#374151'];
                    $currency = $r['currency_symbol'] ?? 'Rs.';
                ?>
                    <tr>
                        <td>#<?= (int) $r['id'] ?></td>
                        <td>#<?= (int) $r['order_id'] ?> — <?= htmlspecialchars($r['customer_name']) ?></td>
                        <td><?= htmlspecialchars($r['product_name']) ?></td>
                        <td><?= (int) $r['quantity'] ?></td>
                        <td><?= $currency ?> <?= number_format((float) $r['refund_amount'], 2) ?></td>
                        <td>
                            <span class="source-badge" style="background:<?= $statusColor['bg'] ?>; color:<?= $statusColor['text'] ?>;">
                                <?= ucfirst($r['status']) ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($r['status'] !== 'completed' && $r['status'] !== 'rejected'): ?>
                                <form method="POST" action="/returns/update-status" style="display:flex; gap:6px;">
                                    <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                    <select name="status" style="border:1px solid var(--border-color); border-radius:6px; padding:5px 6px; font-size:12px; font-family:'Inter',sans-serif;">
                                        <?php foreach ($statuses as $st): ?>
                                            <option value="<?= $st ?>" <?= $r['status'] === $st ? 'selected' : '' ?>><?= ucfirst($st) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <select name="condition_status" style="border:1px solid var(--border-color); border-radius:6px; padding:5px 6px; font-size:12px; font-family:'Inter',sans-serif;">
                                        <?php foreach ($conditions as $c): ?>
                                            <option value="<?= $c ?>" <?= $r['condition_status'] === $c ? 'selected' : '' ?>><?= ucfirst($c) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="filter-btn" style="padding:5px 10px;"><i class="bi bi-check-lg"></i></button>
                                </form>
                            <?php else: ?>
                                <span style="font-size:12px; color:var(--text-muted);">Final</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="modal-backdrop" id="createReturnModal">
    <div class="modal-box">
        <div class="modal-header">
            <h2>New Return</h2>
            <button class="modal-close" onclick="closeModal('createReturnModal')">&times;</button>
        </div>
        <form action="/returns/create" method="POST">
            <div class="form-group">
                <label>Order</label>
                <select name="order_id" required style="width:100%; border:1px solid var(--border-color); border-radius:8px; padding:8px 10px; font-size:14px; font-family:'Inter',sans-serif;">
                    <option value="">Select order...</option>
                    <?php if (empty($recentOrders)): ?>
                        <option value="" disabled>No eligible orders — all recent orders already have a return.</option>
                    <?php endif; ?>
                    <?php foreach ($recentOrders as $o): ?>
                        <option value="<?= $o['id'] ?>">#<?= $o['id'] ?> — <?= htmlspecialchars($o['customer_name']) ?> — <?= htmlspecialchars($o['product_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Quantity</label>
                <input type="number" name="quantity" min="1" value="1" required>
            </div>
            <div class="form-group">
                <label>Reason</label>
                <input type="text" name="reason" placeholder="e.g. Wrong size, defective">
            </div>
            <div class="form-group">
                <label>Refund Amount</label>
                <input type="number" name="refund_amount" min="0" step="0.01" placeholder="0.00">
            </div>
            <div class="form-actions">
                <button type="button" class="btn-secondary" onclick="closeModal('createReturnModal')">Cancel</button>
                <button type="submit" class="btn-primary">Record Return</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(id) { document.getElementById(id).classList.add('show'); }
function closeModal(id) { document.getElementById(id).classList.remove('show'); }
</script>