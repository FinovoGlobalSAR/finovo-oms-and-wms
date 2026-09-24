<div class="breadcrumb">Finovo <i class="bi bi-chevron-right"></i> 3PL <i class="bi bi-chevron-right"></i> Remittance</div>

<div class="page-header-row">
    <h1>3PL Remittance <span class="count-badge"><?= count($shipments) ?></span></h1>
</div>
<p class="page-subtitle">Track COD payments received back from 3PL courier companies.</p>

<?php if (!empty($updated)): ?>
    <div class="banner banner-success">Remittance updated.</div>
<?php endif; ?>

<div class="card">
    <table class="data-table">
        <thead>
            <tr>
                <th>AWB No.</th>
                <th>Courier</th>
                <th>Customer</th>
                <th>Order Total</th>
                <th>Status</th>
                <th style="width:260px;">Update Remittance</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($shipments)): ?>
                <tr><td colspan="6" style="text-align:center; color:#6b7280; padding:30px;">No handed-over shipments yet.</td></tr>
            <?php else: ?>
                <?php foreach ($shipments as $s): ?>
                    <tr>
                        <td><?= htmlspecialchars($s['tracking_number']) ?></td>
                        <td><?= htmlspecialchars($s['courier_name']) ?></td>
                        <td><?= htmlspecialchars($s['customer_name'] ?? '-') ?></td>
                        <td>Rs. <?= number_format((float) $s['order_total'], 2) ?></td>
                        <td>
                            <?php if ($s['remittance_status'] === 'remitted'): ?>
                                <span class="source-badge" style="background:#dcfce7; color:#15803d;">Remitted</span>
                            <?php else: ?>
                                <span class="source-badge" style="background:#fee2e2; color:#991b1b;">Not Remitted</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <form method="POST" action="/3pl/remittance/update" style="display:flex; gap:6px;">
                                <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                <select name="remittance_status" style="border:1px solid var(--border-color); border-radius:6px; padding:5px 6px; font-size:12px; font-family:'Inter',sans-serif;">
                                    <option value="not_remitted" <?= $s['remittance_status'] === 'not_remitted' ? 'selected' : '' ?>>Not Remitted</option>
                                    <option value="remitted" <?= $s['remittance_status'] === 'remitted' ? 'selected' : '' ?>>Remitted</option>
                                </select>
                                <input type="number" name="remittance_amount" step="0.01" placeholder="Amount"
                                       value="<?= $s['remittance_amount'] !== null ? htmlspecialchars($s['remittance_amount']) : '' ?>"
                                       style="width:90px; border:1px solid var(--border-color); border-radius:6px; padding:5px 6px; font-size:12px;">
                                <button type="submit" class="filter-btn" style="padding:5px 10px;"><i class="bi bi-check-lg"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>