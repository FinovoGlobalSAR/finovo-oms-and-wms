<?php $currentPage = 'orders'; ?>

<div class="breadcrumb">Finovo <i class="bi bi-chevron-right"></i> Orders <i class="bi bi-chevron-right"></i> Edit</div>

<div class="page-header-row">
    <h1>Edit Order #<?= $order['id'] ?></h1>
</div>
<p class="page-subtitle"><?= htmlspecialchars($order['customer_name']) ?> — <?= htmlspecialchars($order['product_name']) ?><?= !empty($order['variant_label']) ? ' (' . htmlspecialchars($order['variant_label']) . ')' : '' ?></p>

<div class="card" style="max-width: 560px;">
    <?php if (!empty($error)): ?>
        <div style="margin: 20px 20px 0; padding: 12px 16px; border-radius: 8px; background: #fee2e2; color: #991b1b; font-size: 13px;">
            <i class="bi bi-exclamation-triangle-fill"></i> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="/orders/update" style="padding: 24px;">
        <input type="hidden" name="id" value="<?= $order['id'] ?>">

        <div class="form-group">
            <label>Quantity</label>
            <input type="number" name="quantity" value="<?= $order['quantity'] ?>" min="1" required>
            <p style="font-size:12px; color:var(--text-muted); margin-top:6px;">Increasing quantity checks warehouse stock; decreasing quantity returns the difference to stock.</p>
        </div>

        <div class="form-group">
            <label>Status</label>
            <select name="status" style="width:100%; border:1px solid var(--border-color); border-radius:8px; padding:8px 10px; font-size:14px; font-family:'Inter',sans-serif;">
                <?php foreach (['pending', 'processing', 'delivered', 'cancelled'] as $s): ?>
                    <option value="<?= $s ?>" <?= $order['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Payment Status</label>
            <select name="payment_status" style="width:100%; border:1px solid var(--border-color); border-radius:8px; padding:8px 10px; font-size:14px; font-family:'Inter',sans-serif;">
                <?php foreach (['unpaid', 'paid'] as $s): ?>
                    <option value="<?= $s ?>" <?= $order['payment_status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Payment Method</label>
            <select name="payment_method" style="width:100%; border:1px solid var(--border-color); border-radius:8px; padding:8px 10px; font-size:14px; font-family:'Inter',sans-serif;">
                <?php foreach ($paymentMethods as $pm): ?>
                    <option value="<?= htmlspecialchars($pm) ?>" <?= ($order['payment_method'] ?? '') === $pm ? 'selected' : '' ?>><?= htmlspecialchars($pm) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="display: flex; gap: 10px; margin-top: 10px;">
            <a href="/orders" class="btn-secondary" style="flex: 1; text-align: center; text-decoration: none; display: flex; align-items: center; justify-content: center;">Cancel</a>
            <button type="submit" class="btn-primary" style="flex: 2;">Save Changes</button>
        </div>
    </form>
</div>