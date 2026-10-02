<?php $currentPage = 'orders'; ?>

<div class="ui-head">
    <div class="ui-head-left">
        <div class="breadcrumb">Finovo <i class="bi bi-chevron-right"></i> <a href="/orders" style="color:inherit; text-decoration:none;">Orders</a> <i class="bi bi-chevron-right"></i> <span class="current">Edit</span></div>
        <h1>Edit Order #<?= $order['id'] ?></h1>
        <p><?= htmlspecialchars($order['customer_name']) ?> — <?= htmlspecialchars($order['product_name']) ?><?= !empty($order['variant_label']) ? ' (' . htmlspecialchars($order['variant_label']) . ')' : '' ?></p>
    </div>
    <div class="ui-head-right">
        <div style="display:flex; gap:8px;">
            <a href="/orders/invoice?order_id=<?= $order['id'] ?>" class="ui-btn"><i class="bi bi-file-earmark-pdf"></i> Invoice</a>
            <a href="/orders" class="ui-btn"><i class="bi bi-arrow-left"></i> Back to Orders</a>
        </div>
    </div>
</div>

<div style="display:grid; grid-template-columns:minmax(0, 620px) 320px; gap:20px; align-items:start;" class="order-edit-grid">

<div class="ui-card" style="margin-bottom:0;">
    <?php if (!empty($error)): ?>
        <div class="banner banner-error" style="margin: 20px 22px 0;">
            <i class="bi bi-exclamation-triangle-fill"></i> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="/orders/update" style="padding: 20px 22px 22px; display:flex; flex-direction:column; gap:18px;">
        <input type="hidden" name="id" value="<?= $order['id'] ?>">

        <div style="display:flex; justify-content:space-between; align-items:center; padding-bottom:16px; border-bottom:1px solid var(--border-soft);">
            <div class="ui-card-title">
                <div class="ui-card-title-icon"><i class="bi bi-pencil"></i></div>
                <div>
                    <h2 style="margin:0; font-size:15px; font-weight:700;">Order details</h2>
                    <div style="font-size:12.5px; color:#475569;">Update quantity, status and payment.</div>
                </div>
            </div>
            <span class="ui-tag ui-pill-blue">#<?= $order['id'] ?></span>
        </div>

        <div class="ui-field">
            <label for="editQty" style="font-size:12.5px; font-weight:600; color:var(--text-body);">Quantity</label>
            <span class="ui-input">
                <i class="bi bi-hash"></i>
                <input type="number" id="editQty" name="quantity" value="<?= $order['quantity'] ?>" min="1" required>
            </span>
            <p class="ui-help">Increasing quantity checks warehouse stock; decreasing quantity returns the difference to stock.</p>
        </div>

        <div class="ui-row">
            <label class="ui-field">
                <span>Status</span>
                <span class="ui-input">
                    <i class="bi bi-flag"></i>
                    <select name="status">
                        <?php foreach (['pending', 'processing', 'delivered', 'cancelled'] as $s): ?>
                            <option value="<?= $s ?>" <?= $order['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                        <?php endforeach; ?>
                    </select>
                </span>
            </label>
            <label class="ui-field">
                <span>Payment Status</span>
                <span class="ui-input">
                    <i class="bi bi-cash-coin"></i>
                    <select name="payment_status">
                        <?php foreach (['unpaid', 'paid'] as $s): ?>
                            <option value="<?= $s ?>" <?= $order['payment_status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                        <?php endforeach; ?>
                    </select>
                </span>
            </label>
        </div>

        <label class="ui-field">
            <span>Payment Method</span>
            <span class="ui-input">
                <i class="bi bi-credit-card"></i>
                <select name="payment_method">
                    <?php foreach ($paymentMethods as $pm): ?>
                        <option value="<?= htmlspecialchars($pm) ?>" <?= ($order['payment_method'] ?? '') === $pm ? 'selected' : '' ?>><?= htmlspecialchars($pm) ?></option>
                    <?php endforeach; ?>
                </select>
            </span>
        </label>

        <div style="display: flex; gap: 10px; margin-top: 4px;">
            <a href="/orders" class="ui-btn" style="flex: 1; height:48px; border-radius:10px;">Cancel</a>
            <button type="submit" class="ui-btn ui-btn-primary" style="flex: 2; height:48px; border-radius:10px; font-size:14px;"><i class="bi bi-check-lg"></i> Save Changes</button>
        </div>
    </form>
</div>

<div class="ui-info" style="width:auto;">
    <div class="ui-info-icon"><i class="bi bi-info-lg"></i></div>
    <div>
        <strong>Stock is kept in sync</strong>
        <span>Changing the quantity here updates warehouse stock automatically, so you don't need to adjust it by hand.</span>
    </div>
</div>

</div>

<style>
@media (max-width: 1000px) { .order-edit-grid { grid-template-columns: minmax(0, 1fr) !important; } }
</style>