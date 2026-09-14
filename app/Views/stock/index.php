<div class="breadcrumb">Finovo <i class="bi bi-chevron-right"></i> Stock Movements</div>

<div class="page-header-row">
    <h1>Stock Movements</h1>
</div>
<p class="page-subtitle">Transfer stock between warehouses, or adjust stock for damage/loss/correction.</p>

<?php if (!empty($transferred)): ?><div class="banner banner-success">Stock transferred.</div><?php endif; ?>
<?php if (!empty($adjusted)): ?><div class="banner banner-success">Stock adjusted.</div><?php endif; ?>
<?php if (!empty($error)): ?><div class="banner banner-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<div style="display:flex; gap:16px; flex-wrap:wrap; margin-bottom:20px;">

    <div class="card" style="padding:20px; flex:1; min-width:320px;">
        <h3 style="font-size:14px; font-weight:600; margin-bottom:14px;">Transfer Stock Between Warehouses</h3>
        <form method="POST" action="/stock/transfer">
            <div class="form-group">
                <label>Product</label>
                <select name="product_id" required style="width:100%; border:1px solid var(--border-color); border-radius:8px; padding:8px 10px; font-size:13px; font-family:'Inter',sans-serif;">
                    <option value="">Select product...</option>
                    <?php foreach ($products as $p): ?>
                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>From Warehouse</label>
                <select name="from_warehouse_id" required style="width:100%; border:1px solid var(--border-color); border-radius:8px; padding:8px 10px; font-size:13px; font-family:'Inter',sans-serif;">
                    <option value="">Select warehouse...</option>
                    <?php foreach ($warehouses as $w): ?>
                        <option value="<?= $w['id'] ?>"><?= htmlspecialchars($w['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>To Warehouse</label>
                <select name="to_warehouse_id" required style="width:100%; border:1px solid var(--border-color); border-radius:8px; padding:8px 10px; font-size:13px; font-family:'Inter',sans-serif;">
                    <option value="">Select warehouse...</option>
                    <?php foreach ($warehouses as $w): ?>
                        <option value="<?= $w['id'] ?>"><?= htmlspecialchars($w['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Quantity</label>
                <input type="number" name="quantity" min="1" required>
            </div>
            <div class="form-group">
                <label>Notes (optional)</label>
                <input type="text" name="notes" placeholder="e.g. Rebalancing stock">
            </div>
            <button type="submit" class="btn-primary" style="width:100%;">Transfer Stock</button>
        </form>
    </div>

    <div class="card" style="padding:20px; flex:1; min-width:320px;">
        <h3 style="font-size:14px; font-weight:600; margin-bottom:14px;">Adjust Stock (Damage / Loss / Correction)</h3>
        <form method="POST" action="/stock/adjust">
            <div class="form-group">
                <label>Product</label>
                <select name="product_id" required style="width:100%; border:1px solid var(--border-color); border-radius:8px; padding:8px 10px; font-size:13px; font-family:'Inter',sans-serif;">
                    <option value="">Select product...</option>
                    <?php foreach ($products as $p): ?>
                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Warehouse</label>
                <select name="warehouse_id" required style="width:100%; border:1px solid var(--border-color); border-radius:8px; padding:8px 10px; font-size:13px; font-family:'Inter',sans-serif;">
                    <option value="">Select warehouse...</option>
                    <?php foreach ($warehouses as $w): ?>
                        <option value="<?= $w['id'] ?>"><?= htmlspecialchars($w['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Quantity Change</label>
                <input type="number" name="quantity_change" required placeholder="e.g. -5 for loss, 5 for found stock">
                <p class="modal-help" style="margin-top:6px;">Use a negative number to reduce stock, positive to add.</p>
            </div>
            <div class="form-group">
                <label>Reason</label>
                <input type="text" name="reason" required placeholder="e.g. Damaged in warehouse">
            </div>
            <button type="submit" class="btn-primary" style="width:100%;">Apply Adjustment</button>
        </form>
    </div>

</div>

<div style="display:flex; gap:16px; flex-wrap:wrap;">

    <div class="card" style="padding:0; flex:1; min-width:320px; overflow:hidden;">
        <h3 style="font-size:14px; font-weight:600; padding:20px 20px 0;">Recent Transfers</h3>
        <table class="data-table" style="margin-top:10px;">
            <thead><tr><th>Product</th><th>From</th><th>To</th><th>Qty</th></tr></thead>
            <tbody>
                <?php if (empty($transfers)): ?>
                    <tr><td colspan="4" style="text-align:center; color:#6b7280; padding:20px;">No transfers yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($transfers as $t): ?>
                        <tr>
                            <td><?= htmlspecialchars($t['product_name']) ?></td>
                            <td><?= htmlspecialchars($t['from_name']) ?></td>
                            <td><?= htmlspecialchars($t['to_name']) ?></td>
                            <td><?= (int) $t['quantity'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="card" style="padding:0; flex:1; min-width:320px; overflow:hidden;">
        <h3 style="font-size:14px; font-weight:600; padding:20px 20px 0;">Recent Adjustments</h3>
        <table class="data-table" style="margin-top:10px;">
            <thead><tr><th>Product</th><th>Warehouse</th><th>Change</th><th>Reason</th></tr></thead>
            <tbody>
                <?php if (empty($adjustments)): ?>
                    <tr><td colspan="4" style="text-align:center; color:#6b7280; padding:20px;">No adjustments yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($adjustments as $a): ?>
                        <tr>
                            <td><?= htmlspecialchars($a['product_name']) ?></td>
                            <td><?= htmlspecialchars($a['warehouse_name']) ?></td>
                            <td>
                                <span class="source-badge" style="background:<?= $a['quantity_change'] >= 0 ? '#dcfce7' : '#fee2e2' ?>; color:<?= $a['quantity_change'] >= 0 ? '#15803d' : '#991b1b' ?>;">
                                    <?= $a['quantity_change'] >= 0 ? '+' : '' ?><?= (int) $a['quantity_change'] ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($a['reason']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>