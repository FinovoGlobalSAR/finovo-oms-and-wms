<?php
$currentPage = 'orders';
$alreadySent = !empty($lines[0]['cj_order_id']);
$field = fn($key) => htmlspecialchars($address[$key] ?? '');
?>

<div class="ui-head">
    <div class="ui-head-left">
        <div class="breadcrumb">Finovo <i class="bi bi-chevron-right"></i> <a href="/orders" style="color:inherit; text-decoration:none;">Orders</a> <i class="bi bi-chevron-right"></i> <span class="current">Send to CJdropshipping</span></div>
        <h1>Send Order #<?= (int) $orderId ?> to CJdropshipping</h1>
        <p>CJ packs these products and ships them straight to your customer.</p>
    </div>
    <div class="ui-head-right">
        <a href="/orders" class="ui-btn"><i class="bi bi-arrow-left"></i> Back to Orders</a>
    </div>
</div>

<?php if (!empty($error)): ?>
    <div class="banner banner-error"><i class="bi bi-exclamation-triangle-fill"></i><span><?= htmlspecialchars($error) ?></span></div>
<?php endif; ?>
<?php if (!empty($refreshed)): ?>
    <div class="banner banner-success"><i class="bi bi-check-circle-fill"></i><span>Status updated from CJ.</span></div>
<?php endif; ?>

<?php if (!$cjReady): ?>
    <div class="ui-alert" style="margin-bottom:16px;">
        <div class="ui-tip-icon"><i class="bi bi-exclamation-triangle"></i></div>
        <div class="ui-alert-body">
            <strong>CJ is not connected for this store</strong>
            <span>Save the CJdropshipping email and API key on the Stores page first.</span>
        </div>
        <a href="/stores" class="ui-btn ui-btn-sm" style="background:#fff;">Stores <i class="bi bi-arrow-right"></i></a>
    </div>
<?php endif; ?>

<!-- Order ke products -->
<div class="ui-card">
    <div class="ui-card-head">
        <div class="ui-card-title">
            <div class="ui-card-title-icon"><i class="bi bi-box-seam"></i></div>
            <div><h2>Products</h2></div>
        </div>
    </div>
    <div style="padding: 6px 22px 18px;">
        <?php foreach ($lines as $line): ?>
            <?php $pid = (int) $line['product_id']; ?>
            <div style="display:flex; justify-content:space-between; align-items:center; gap:16px; padding:12px 0; border-bottom:1px solid var(--border-soft); flex-wrap:wrap;">
                <div>
                    <div style="font-weight:600; color:var(--text-dark);"><?= htmlspecialchars($line['product_name']) ?> <span style="color:var(--text-muted); font-weight:500;">× <?= (int) $line['quantity'] ?></span></div>
                    <?php if (empty($line['external_cj_product_id'])): ?>
                        <span class="ui-pill ui-pill-red">Not a CJ product</span>
                    <?php elseif (!empty($line['external_cj_variant_id'])): ?>
                        <span class="ui-pill ui-pill-green">CJ variant: <?= htmlspecialchars($line['external_cj_variant_id']) ?></span>
                    <?php else: ?>
                        <span class="ui-pill ui-pill-amber">Choose CJ variant below</span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php if ($alreadySent): ?>
    <!-- Pehle se CJ ko bheja ja chuka hai -->
    <div class="ui-card">
        <div class="ui-card-head">
            <div class="ui-card-title">
                <div class="ui-card-title-icon"><i class="bi bi-truck"></i></div>
                <div><h2>CJ order status</h2></div>
            </div>
            <div class="ui-card-tools">
                <a href="/orders/cj-refresh?id=<?= (int) $orderId ?>" class="ui-btn ui-btn-sm"><i class="bi bi-arrow-clockwise"></i> Refresh status</a>
            </div>
        </div>
        <div style="padding: 6px 22px 20px; display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:14px;">
            <div><div style="font-size:12px; color:var(--text-muted);">CJ order ID</div><strong><?= htmlspecialchars($lines[0]['cj_order_id']) ?></strong></div>
            <div><div style="font-size:12px; color:var(--text-muted);">Status</div><strong><?= htmlspecialchars($lines[0]['cj_order_status'] ?? '-') ?></strong></div>
            <div><div style="font-size:12px; color:var(--text-muted);">Shipping method</div><strong><?= htmlspecialchars($lines[0]['cj_logistic_name'] ?? '-') ?></strong></div>
            <div><div style="font-size:12px; color:var(--text-muted);">Tracking number</div><strong><?= htmlspecialchars($lines[0]['cj_tracking_number'] ?? 'Not shipped yet') ?></strong></div>
        </div>
        <div style="padding: 0 22px 20px; font-size:13px; color:var(--text-muted);">
            Orders are created in CJ as "create only". Pay for this order in your CJ dashboard so CJ starts processing it.
        </div>
    </div>
<?php else: ?>
    <form method="POST" action="/orders/cj-fulfill/quote" class="ui-card" style="overflow:visible;">
        <input type="hidden" name="order_id" value="<?= (int) $orderId ?>">

        <div class="ui-card-head">
            <div class="ui-card-title">
                <div class="ui-card-title-icon"><i class="bi bi-geo-alt"></i></div>
                <div><h2>Shipping details</h2></div>
            </div>
        </div>

        <div style="padding: 6px 22px 22px;">

            <?php foreach ($variantChoices as $productId => $variants): ?>
                <label class="ui-field" style="margin-bottom:14px;">
                    <span>CJ variant for "<?= htmlspecialchars($lines[array_search($productId, array_map('intval', array_column($lines, 'product_id')))]['product_name'] ?? '') ?>"</span>
                    <span class="ui-input">
                        <i class="bi bi-tags"></i>
                        <select name="variant[<?= (int) $productId ?>]" required>
                            <?php if (count($variants) !== 1): ?><option value="">Choose variant...</option><?php endif; ?>
                            <?php foreach ($variants as $v): ?>
                                <option value="<?= htmlspecialchars($v['vid']) ?>" <?= count($variants) === 1 ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($v['name']) ?><?= $v['sku'] ? ' (' . htmlspecialchars($v['sku']) . ')' : '' ?> — $<?= number_format($v['price'], 2) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </span>
                </label>
            <?php endforeach; ?>

            <div class="ui-row" style="margin-bottom:14px;">
                <label class="ui-field">
                    <span>Customer name</span>
                    <span class="ui-input"><i class="bi bi-person"></i><input type="text" name="customer_name" maxlength="50" value="<?= $field('customer_name') ?>" required></span>
                </label>
                <label class="ui-field">
                    <span>Phone</span>
                    <span class="ui-input"><i class="bi bi-telephone"></i><input type="text" name="phone" maxlength="20" value="<?= $field('phone') ?>" required></span>
                </label>
            </div>

            <label class="ui-field" style="margin-bottom:14px;">
                <span>Address line 1</span>
                <span class="ui-input"><i class="bi bi-house"></i><input type="text" name="address" maxlength="500" value="<?= $field('address') ?>" required></span>
            </label>
            <label class="ui-field" style="margin-bottom:14px;">
                <span>Address line 2 <small>(optional)</small></span>
                <span class="ui-input"><i class="bi bi-house"></i><input type="text" name="address2" maxlength="500" value="<?= $field('address2') ?>"></span>
            </label>

            <div class="ui-row" style="margin-bottom:14px;">
                <label class="ui-field">
                    <span>City</span>
                    <span class="ui-input"><i class="bi bi-building"></i><input type="text" name="city" maxlength="50" value="<?= $field('city') ?>" required></span>
                </label>
                <label class="ui-field">
                    <span>Province / State</span>
                    <span class="ui-input"><i class="bi bi-map"></i><input type="text" name="province" maxlength="50" value="<?= $field('province') ?>" required></span>
                </label>
                <label class="ui-field">
                    <span>ZIP / Postal code</span>
                    <span class="ui-input"><i class="bi bi-mailbox"></i><input type="text" name="zip" maxlength="20" value="<?= $field('zip') ?>"></span>
                </label>
            </div>

            <div class="ui-row" style="margin-bottom:14px;">
                <label class="ui-field">
                    <span>Country code <small>(2 letters, e.g. SA, US)</small></span>
                    <span class="ui-input"><i class="bi bi-flag"></i><input type="text" name="country_code" maxlength="2" value="<?= $field('country_code') ?>" style="text-transform:uppercase;" required></span>
                </label>
                <label class="ui-field">
                    <span>Country name</span>
                    <span class="ui-input"><i class="bi bi-globe"></i><input type="text" name="country" maxlength="50" value="<?= $field('country') ?>" required></span>
                </label>
                <label class="ui-field">
                    <span>Ship from <small>(CJ warehouse country)</small></span>
                    <span class="ui-input"><i class="bi bi-box-arrow-up-right"></i><input type="text" name="from_country_code" maxlength="2" value="<?= $field('from_country_code') ?>" style="text-transform:uppercase;" required></span>
                </label>
            </div>

            <?php if (!empty($shippingOptions)): ?>
                <div style="font-size:12.5px; font-weight:600; color:var(--text-body); margin:6px 0 8px;">Shipping method</div>
                <div style="display:flex; flex-direction:column; gap:8px; margin-bottom:18px;">
                    <?php foreach ($shippingOptions as $i => $option): ?>
                        <label style="display:flex; align-items:center; gap:10px; padding:10px 12px; border:1px solid var(--border-color); border-radius:10px; cursor:pointer;">
                            <input type="radio" name="logistic_name" value="<?= htmlspecialchars($option['name']) ?>" <?= $i === 0 ? 'checked' : '' ?>>
                            <span style="flex:1; font-weight:600;"><?= htmlspecialchars($option['name']) ?></span>
                            <span style="color:var(--text-muted); font-size:12.5px;"><?= htmlspecialchars($option['days']) ?> days</span>
                            <strong>$<?= number_format($option['price'], 2) ?></strong>
                        </label>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                <button type="submit" class="ui-btn" <?= $cjReady ? '' : 'disabled' ?>>
                    <i class="bi bi-calculator"></i> <?= empty($shippingOptions) ? 'Get shipping options' : 'Recalculate shipping' ?>
                </button>
                <?php if (!empty($shippingOptions)): ?>
                    <button type="submit" formaction="/orders/cj-fulfill/submit" class="ui-btn ui-btn-primary"
                        onclick="return confirm('Create this order in CJdropshipping? You will pay for it in your CJ dashboard.');">
                        <i class="bi bi-send"></i> Send to CJ
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </form>
<?php endif; ?>