<?php $currentPage = 'orders'; ?>

<div class="ui-head">
    <div class="ui-head-left">
        <div class="breadcrumb">Finovo <i class="bi bi-chevron-right"></i> <a href="/orders" style="color:inherit; text-decoration:none;">Orders</a> <i class="bi bi-chevron-right"></i> <span class="current">Add Order</span></div>
        <h1>Add New Order</h1>
        <p>Add one or more products (or product variants) from your warehouse stock.</p>
    </div>
    <div class="ui-head-right">
        <a href="/orders" class="ui-btn"><i class="bi bi-arrow-left"></i> Back to Orders</a>
    </div>
</div>

<div style="display:grid; grid-template-columns:minmax(0, 1fr) 320px; gap:20px; align-items:start;" class="order-create-grid">

<div class="ui-card" style="overflow: visible; margin-bottom:0;">

    <?php if (!empty($error)): ?>
        <div class="banner banner-error" style="margin: 20px 22px 0;">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <span><?= htmlspecialchars($error) ?></span>
        </div>
    <?php endif; ?>

    <form method="POST" action="/orders/create" style="padding: 20px 22px 22px;">

        <div style="display:flex; justify-content:space-between; align-items:center; padding-bottom:16px; margin-bottom:18px; border-bottom:1px solid var(--border-soft);">
            <div class="ui-card-title">
                <div class="ui-card-title-icon"><i class="bi bi-bag-plus"></i></div>
                <div>
                    <h2 style="margin:0; font-size:15px; font-weight:700;">Order details</h2>
                    <div style="font-size:12.5px; color:#475569;">Only in-stock products / variants appear in the list below</div>
                </div>
            </div>
            <span class="ui-tag ui-pill-blue">New order</span>
        </div>

        <div class="ui-row" style="margin-bottom:18px;">
            <label class="ui-field">
                <span>Customer Name</span>
                <span class="ui-input">
                    <i class="bi bi-person"></i>
                    <input type="text" name="customer_name" placeholder="e.g. Ali Khan" required>
                </span>
            </label>
            <label class="ui-field">
                <span>Payment Method</span>
                <span class="ui-input">
                    <i class="bi bi-credit-card"></i>
                    <select name="payment_method">
                        <?php foreach ($paymentMethods as $pm): ?>
                            <option value="<?= htmlspecialchars($pm) ?>"><?= htmlspecialchars($pm) ?></option>
                        <?php endforeach; ?>
                    </select>
                </span>
            </label>
        </div>

        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
            <span style="font-size:12.5px; font-weight:600; color:var(--text-body);">Products</span>
            <span style="font-size:12px; color:var(--text-muted);">Product · Qty · Line total</span>
        </div>

        <?php if (empty($products)): ?>
            <div class="ui-alert" style="margin-bottom:16px;">
                <div class="ui-tip-icon"><i class="bi bi-exclamation-triangle"></i></div>
                <div class="ui-alert-body">
                    <strong>No stock available</strong>
                    <span>No products have stock available right now. Receive stock into a warehouse first.</span>
                </div>
                <a href="/warehouses" class="ui-btn ui-btn-sm" style="border-color:#fde68a; background:#fff;">Warehouses <i class="bi bi-arrow-right"></i></a>
            </div>
        <?php endif; ?>

        <div id="orderItemsContainer" style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 12px;"></div>

        <button type="button" onclick="addOrderRow()" class="ui-btn ui-btn-soft" style="margin-bottom: 20px;" <?= empty($products) ? 'disabled' : '' ?>>
            <i class="bi bi-plus-lg"></i> Add another product
        </button>

        <div style="display: flex; align-items: center; justify-content: space-between; padding: 14px 16px; background: var(--primary-light); border: 1px solid var(--primary-border); border-radius: 10px; margin-bottom: 20px;">
            <span style="font-size: 13px; font-weight:600; color: var(--primary-dark);">Order total</span>
            <span id="orderTotalDisplay" style="font-size: 18px; font-weight: 700; color: var(--primary-dark);">Rs. 0.00</span>
        </div>

        <div style="display: flex; gap: 10px;">
            <a href="/orders" class="ui-btn" style="flex: 1; height:48px; border-radius:10px;">
                Cancel
            </a>
            <button type="submit" class="ui-btn ui-btn-primary" style="flex: 2; height:48px; border-radius:10px; font-size:14px;" <?= empty($products) ? 'disabled' : '' ?>>
                <i class="bi bi-check-lg"></i> Create Order
            </button>
        </div>

    </form>

</div>

<div style="display:flex; flex-direction:column; gap:16px;">
    <div class="ui-info" style="width:auto;">
        <div class="ui-info-icon"><i class="bi bi-info-lg"></i></div>
        <div>
            <strong>How stock works</strong>
            <span>Creating the order takes stock from this store's linked warehouses automatically.</span>
        </div>
    </div>
    <div class="ui-tip">
        <div class="ui-tip-icon"><i class="bi bi-lightbulb"></i></div>
        <div>
            <strong>Tip</strong>
            <span>Keep $ and Rs. products in separate orders so the total stays in one currency.</span>
        </div>
    </div>
</div>

</div>

<style>
@media (max-width: 1000px) { .order-create-grid { grid-template-columns: minmax(0, 1fr) !important; } }
.order-item-row {
    display: grid;
    grid-template-columns: 2fr 90px 130px 38px;
    gap: 8px;
    align-items: center;
    padding: 10px;
    border: 1px solid var(--border-color);
    border-radius: 10px;
    background: #f8fafc;
}
.order-item-row select,
.order-item-row input[type="number"] {
    width: 100%;
    height: 40px;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    padding: 0 10px;
    font-size: 13px;
    font-family: inherit;
    background: #ffffff;
    transition: border-color 0.15s, background 0.15s;
}
.order-item-row select:focus,
.order-item-row input[type="number"]:focus { outline: none; border-color: #93b4f5; box-shadow: 0 0 0 3px rgba(29,78,216,0.12); }
.order-row-price {
    font-size: 13px;
    font-weight: 600;
    color: var(--text-body);
    text-align: right;
    white-space: nowrap;
}
.remove-row-btn {
    border: 1px solid #fecaca;
    background: #fff;
    border-radius: 8px;
    width: 38px;
    height: 38px;
    cursor: pointer;
    color: var(--red);
    display: flex;
    align-items: center;
    justify-content: center;
}
.remove-row-btn:hover { background: #fee2e2; }
</style>

<script>
const orderProducts = <?= json_encode($products ?? []) ?>;

function buildOptions() {
    let opts = '<option value="">Select a product...</option>';
    orderProducts.forEach(p => {
        opts += `<option value="${p.product_id}|${p.variant_id}" data-price="${p.price}" data-stock="${p.stock}" data-currency="${p.currency}">${p.currency} ${p.label} (${p.stock} in stock)</option>`;
    });
    return opts;
}

function addOrderRow() {
    const container = document.getElementById('orderItemsContainer');
    const row = document.createElement('div');
    row.className = 'order-item-row';
    row.innerHTML = `
        <select class="item-select" required onchange="onRowChange(this)">${buildOptions()}</select>
        <input type="number" class="item-qty" min="1" value="1" required onchange="onRowChange(this)" oninput="onRowChange(this)">
        <span class="order-row-price">0.00</span>
        <button type="button" class="remove-row-btn" onclick="removeOrderRow(this)"><i class="bi bi-trash"></i></button>
        <input type="hidden" name="product_id[]" class="hidden-product-id">
        <input type="hidden" name="variant_id[]" class="hidden-variant-id">
        <input type="hidden" name="quantity[]" class="hidden-quantity">
    `;
    container.appendChild(row);
}

function removeOrderRow(btn) {
    const rows = document.querySelectorAll('.order-item-row');
    if (rows.length > 1) {
        btn.closest('.order-item-row').remove();
        recalcTotal();
    }
}

function onRowChange(el) {
    const row = el.closest('.order-item-row');
    const select = row.querySelector('.item-select');
    const qtyInput = row.querySelector('.item-qty');
    const priceSpan = row.querySelector('.order-row-price');

    const opt = select.options[select.selectedIndex];
    const price = opt && opt.dataset.price ? parseFloat(opt.dataset.price) : 0;
    const stock = opt && opt.dataset.stock ? parseInt(opt.dataset.stock) : 0;
    const currency = opt && opt.dataset.currency ? opt.dataset.currency : 'Rs.';

    const qty = parseInt(qtyInput.value) || 0;

    if (qty > stock) {
        qtyInput.style.borderColor = '#dc2626';
        qtyInput.style.background = '#fee2e2';
        priceSpan.style.color = '#dc2626';
        priceSpan.textContent = 'Only ' + stock + ' in stock!';
    } else {
        qtyInput.style.borderColor = '';
        qtyInput.style.background = '';
        priceSpan.style.color = '';
        const lineTotal = price * qty;
        priceSpan.textContent = currency + ' ' + lineTotal.toFixed(2);
    }

    const [productId, variantId] = (select.value || '0|0').split('|');
    row.querySelector('.hidden-product-id').value = productId || '0';
    row.querySelector('.hidden-variant-id').value = variantId || '0';
    row.querySelector('.hidden-quantity').value = qty;

    recalcTotal();
}

function recalcTotal() {
    let total = 0;
    let currencies = new Set();

    document.querySelectorAll('.order-item-row').forEach(row => {
        const select = row.querySelector('.item-select');
        const qtyInput = row.querySelector('.item-qty');
        const opt = select.options[select.selectedIndex];
        const price = opt && opt.dataset.price ? parseFloat(opt.dataset.price) : 0;
        const currency = opt && opt.dataset.currency ? opt.dataset.currency : null;
        if (currency) currencies.add(currency);
        total += price * (parseInt(qtyInput.value) || 0);
    });

    let label;
    if (currencies.size === 0) {
        label = 'Rs. 0.00';
    } else if (currencies.size === 1) {
        label = Array.from(currencies)[0] + ' ' + total.toFixed(2);
    } else {
        label = 'Mixed currencies — ' + total.toFixed(2) + ' (avoid combining $ and Rs. products in one order)';
    }

    document.getElementById('orderTotalDisplay').textContent = label;
}

document.querySelector('form').addEventListener('submit', function() {
    document.querySelectorAll('.order-item-row').forEach(row => {
        const select = row.querySelector('.item-select');
        const [productId, variantId] = (select.value || '0|0').split('|');
        row.querySelector('.hidden-product-id').value = productId || '0';
        row.querySelector('.hidden-variant-id').value = variantId || '0';
        row.querySelector('.hidden-quantity').value = row.querySelector('.item-qty').value;
    });
});

if (orderProducts.length > 0) {
    addOrderRow();
}
</script>