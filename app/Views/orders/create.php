<?php $currentPage = 'orders'; ?>

<div class="breadcrumb">Finovo <i class="bi bi-chevron-right"></i> Orders <i class="bi bi-chevron-right"></i> Add Order</div>

<div class="page-header-row">
    <h1>Add New Order</h1>
</div>
<p class="page-subtitle">Add one or more products (or product variants) from your warehouse stock.</p>

<div class="card" style="max-width: 720px; overflow: visible;">

    <?php if (!empty($error)): ?>
        <div style="margin: 20px 20px 0; padding: 12px 16px; border-radius: 8px; background: #fee2e2; color: #991b1b; font-size: 13px; display: flex; align-items: flex-start; gap: 8px;">
            <i class="bi bi-exclamation-triangle-fill" style="margin-top: 2px;"></i>
            <span><?= htmlspecialchars($error) ?></span>
        </div>
    <?php endif; ?>

    <form method="POST" action="/orders/create" style="padding: 24px;">

        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 20px; padding-bottom: 16px; border-bottom: 1px solid var(--border-color);">
            <div style="width: 36px; height: 36px; border-radius: 10px; background: #eef2ff; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                <i class="bi bi-bag-plus" style="color: #4338ca; font-size: 16px;"></i>
            </div>
            <div>
                <div style="font-size: 14px; font-weight: 600; color: var(--text-dark);">Order details</div>
                <div style="font-size: 12px; color: var(--text-muted);">Only in-stock products / variants appear in the list below</div>
            </div>
        </div>

        <div class="form-group">
            <label><i class="bi bi-person" style="margin-right: 4px; color: var(--text-muted);"></i>Customer Name</label>
            <input type="text" name="customer_name" placeholder="e.g. Ali Khan" required>
        </div>

        <div class="form-group">
            <label><i class="bi bi-credit-card" style="margin-right: 4px; color: var(--text-muted);"></i>Payment Method</label>
            <select name="payment_method" style="width:100%; border:1px solid var(--border-color); border-radius:8px; padding:8px 10px; font-size:14px; font-family:'Inter',sans-serif;">
                <?php foreach ($paymentMethods as $pm): ?>
                    <option value="<?= htmlspecialchars($pm) ?>"><?= htmlspecialchars($pm) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 8px; color: var(--text-dark);">
            <i class="bi bi-box-seam" style="margin-right: 4px; color: var(--text-muted);"></i>Products
        </label>

        <?php if (empty($products)): ?>
            <div style="padding: 14px; background: #fef3c7; color: #92400e; border-radius: 8px; font-size: 13px; margin-bottom: 16px;">
                <i class="bi bi-exclamation-triangle"></i> No products have stock available right now. Receive stock into a warehouse first.
            </div>
        <?php endif; ?>

        <div id="orderItemsContainer" style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 12px;"></div>

        <button type="button" onclick="addOrderRow()" class="toolbar-btn" style="margin-bottom: 20px;" <?= empty($products) ? 'disabled' : '' ?>>
            <i class="bi bi-plus-lg"></i> Add another product
        </button>

        <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px 16px; background: #f9fafb; border-radius: 8px; margin-bottom: 20px;">
            <span style="font-size: 13px; color: var(--text-muted);">Order total</span>
            <span id="orderTotalDisplay" style="font-size: 16px; font-weight: 700; color: var(--text-dark);">Rs. 0.00</span>
        </div>

        <div style="display: flex; gap: 10px;">
            <a href="/orders" class="btn-secondary" style="flex: 1; text-align: center; text-decoration: none; display: flex; align-items: center; justify-content: center;">
                Cancel
            </a>
            <button type="submit" class="btn-primary" style="flex: 2; display: flex; align-items: center; justify-content: center; gap: 6px;" <?= empty($products) ? 'disabled' : '' ?>>
                <i class="bi bi-check-lg"></i> Create Order
            </button>
        </div>

    </form>

</div>

<style>
.order-item-row {
    display: grid;
    grid-template-columns: 2fr 90px 130px 36px;
    gap: 8px;
    align-items: center;
}
.order-item-row select,
.order-item-row input[type="number"] {
    width: 100%;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    padding: 8px 10px;
    font-size: 13px;
    font-family: 'Inter', sans-serif;
    transition: border-color 0.15s, background 0.15s;
}
.order-row-price {
    font-size: 13px;
    color: var(--text-muted);
    text-align: right;
    white-space: nowrap;
}
.remove-row-btn {
    border: 1px solid var(--border-color);
    background: var(--bg-white);
    border-radius: 8px;
    width: 36px;
    height: 36px;
    cursor: pointer;
    color: var(--red);
    display: flex;
    align-items: center;
    justify-content: center;
}
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