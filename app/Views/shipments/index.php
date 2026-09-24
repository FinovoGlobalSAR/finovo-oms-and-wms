<?php
$statusColors = [
    'pending'     => ['bg' => '#f3f4f6', 'text' => '#374151'],
    'packed'      => ['bg' => '#fef3c7', 'text' => '#b45309'],
    'dispatched'  => ['bg' => '#dbeafe', 'text' => '#1d4ed8'],
    'in_transit'  => ['bg' => '#e0e7ff', 'text' => '#4338ca'],
    'delivered'   => ['bg' => '#dcfce7', 'text' => '#15803d'],
    'returned'    => ['bg' => '#fee2e2', 'text' => '#991b1b'],
];
?>

<div class="breadcrumb">Finovo <i class="bi bi-chevron-right"></i> Shipments</div>

<div class="page-header-row">
    <h1>Shipments <span class="count-badge"><?= count($shipments) ?></span></h1>
</div>
<p class="page-subtitle">Dispatch orders with a courier and track them until delivery.</p>

<?php if (!empty($created)): ?><div class="banner banner-success">Shipment created.</div><?php endif; ?>
<?php if (!empty($updated)): ?><div class="banner banner-success">Shipment updated.</div><?php endif; ?>
<?php if (!empty($_GET['tracked'])): ?><div class="banner banner-success">Live status: <?= htmlspecialchars($_GET['tracked']) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="banner banner-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<div class="card" style="overflow: visible;">
    <div class="filter-row">
        <div class="push-right" style="margin-left:0;">
            <button type="button" class="toolbar-btn btn-dark" onclick="openModal('createShipmentModal')" <?= empty($unassignedUnits) ? 'disabled' : '' ?>>
                <i class="bi bi-truck"></i> Dispatch Order
            </button>
        </div>
    </div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Shipment</th>
                <th>Customer</th>
                <th>Items</th>
                <th>Courier</th>
                <th>Tracking #</th>
                <th>Status</th>
                <th style="width:200px;">Update Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($shipments)): ?>
                <tr><td colspan="7" style="text-align:center; color:#6b7280; padding:30px;">No shipments yet.</td></tr>
            <?php else: ?>
                <?php foreach ($shipments as $s):
                    $statusColor = $statusColors[$s['status']] ?? ['bg' => '#f3f4f6', 'text' => '#374151'];
                ?>
                    <tr>
                        <td>#<?= (int) $s['id'] ?></td>
                        <td><?= htmlspecialchars($s['customer_name'] ?? '-') ?></td>
                        <td>
                            <span title="<?= htmlspecialchars(implode(', ', array_map(fn($o) => $o['product_name'], $s['orders']))) ?>">
                                <?= (int) $s['item_count'] ?> item(s)
                            </span>
                        </td>
                        <td><?= htmlspecialchars($s['courier_name']) ?></td>
                        <td><?= htmlspecialchars($s['tracking_number'] ?: '-') ?></td>
                        <td>
                            <span class="source-badge" style="background:<?= $statusColor['bg'] ?>; color:<?= $statusColor['text'] ?>;">
                                <?= htmlspecialchars(ucwords(str_replace('_', ' ', $s['status']))) ?>
                            </span>
                        </td>
                        <td>
                            <form method="POST" action="/shipments/update-status" style="display:flex; gap:6px;">
                                <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                <select name="status" style="flex:1; border:1px solid var(--border-color); border-radius:6px; padding:5px 8px; font-size:12px; font-family:'Inter',sans-serif;">
                                    <?php foreach ($statuses as $st): ?>
                                        <option value="<?= $st ?>" <?= $s['status'] === $st ? 'selected' : '' ?>><?= ucwords(str_replace('_', ' ', $st)) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="filter-btn" style="padding:5px 10px;"><i class="bi bi-check-lg"></i></button>
                            </form>
                            <a href="/3pl/track?id=<?= $s['id'] ?>" class="action-link" style="display:block; margin-top:6px; font-size:11px;">
                                <i class="bi bi-satellite"></i> Track Live (DHL only for now)
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<div class="modal-backdrop" id="createShipmentModal">
    <div class="modal-box">
        <div class="modal-header">
            <h2>Dispatch Order</h2>
            <button class="modal-close" onclick="closeModal('createShipmentModal')">&times;</button>
        </div>
        <form action="/shipments/create" method="POST">
            <div class="form-group">
                <label>Order</label>
                <select name="order_unit" required style="width:100%; border:1px solid var(--border-color); border-radius:8px; padding:8px 10px; font-size:14px; font-family:'Inter',sans-serif;">
                    <option value="">Select an order...</option>
                    <?php foreach ($unassignedUnits as $u): ?>
                        <option value="<?= htmlspecialchars($u['unit_key']) ?>">
                            <?= htmlspecialchars($u['customer_name']) ?> — <?= htmlspecialchars($u['products']) ?> (<?= (int) $u['item_count'] ?> item<?= $u['item_count'] > 1 ? 's' : '' ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Courier</label>
                <select name="courier_name" required style="width:100%; border:1px solid var(--border-color); border-radius:8px; padding:8px 10px; font-size:14px; font-family:'Inter',sans-serif;">
                    <?php foreach ($couriers as $c): ?>
                        <option value="<?= htmlspecialchars($c) ?>"><?= htmlspecialchars($c) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Tracking Number (optional)</label>
                <input type="text" name="tracking_number" placeholder="e.g. TCS1234567890">
            </div>
            <div class="form-group">
                <label>Notes (optional)</label>
                <input type="text" name="notes" placeholder="e.g. Fragile, handle with care">
            </div>
            <div class="form-actions">
                <button type="button" class="btn-secondary" onclick="closeModal('createShipmentModal')">Cancel</button>
                <button type="submit" class="btn-primary">Dispatch</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(id) { document.getElementById(id).classList.add('show'); }
function closeModal(id) { document.getElementById(id).classList.remove('show'); }
</script>