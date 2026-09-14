<?php $currentPage = 'products'; ?>

<div class="breadcrumb">Finovo <i class="bi bi-chevron-right"></i> Products <i class="bi bi-chevron-right"></i> Edit</div>

<div class="page-header-row">
    <h1>Edit Product</h1>
</div>
<p class="page-subtitle">Update the base details of this product. Stock is managed from the Warehouses page.</p>

<div class="card" style="max-width: 560px;">
    <?php if (!empty($error)): ?>
        <div style="margin: 20px 20px 0; padding: 12px 16px; border-radius: 8px; background: #fee2e2; color: #991b1b; font-size: 13px;">
            <i class="bi bi-exclamation-triangle-fill"></i> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="/products/update" style="padding: 24px;">
        <input type="hidden" name="id" value="<?= $product['id'] ?>">

        <div class="form-group">
            <label>Product Name</label>
            <input type="text" name="name" value="<?= htmlspecialchars($product['name']) ?>" required>
        </div>

        <div class="form-group">
            <label>SKU</label>
            <input type="text" name="sku" value="<?= htmlspecialchars($product['sku'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label>Price</label>
            <input type="number" name="price" step="0.01" value="<?= $product['price'] ?>" required>
        </div>

        <div class="form-group">
            <label>Low Stock Threshold</label>
            <input type="number" name="low_stock_threshold" value="<?= $product['low_stock_threshold'] ?? 5 ?>" min="0">
        </div>

        <div style="display: flex; gap: 10px; margin-top: 10px;">
            <a href="/products" class="btn-secondary" style="flex: 1; text-align: center; text-decoration: none; display: flex; align-items: center; justify-content: center;">Cancel</a>
            <button type="submit" class="btn-primary" style="flex: 2;">Save Changes</button>
        </div>
    </form>
</div>