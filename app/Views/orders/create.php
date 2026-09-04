<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Order — Finovo OMS/WMS</title>
    <?php require __DIR__ . '/../partials/theme_header.php'; ?>
</head>
<body class="bg-canvas min-h-screen flex items-center justify-center p-8 text-ink">
    <div class="bg-canvas border border-ink rounded-lg p-8 w-full max-w-md">
        <h1 class="text-xl font-bold mb-6">Add New Order</h1>

        <?php if (!empty($error)): ?>
            <div class="border border-ink px-4 py-2 rounded mb-4 text-sm font-medium">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="/orders/create" class="space-y-4">
            <div>
                <label class="block text-sm font-medium mb-1">Customer Name</label>
                <input type="text" name="customer_name" required
                       class="w-full border border-ink/40 rounded px-3 py-2 focus:outline-none focus:border-ink">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Product Name</label>
                <input type="text" name="product_name" required
                       class="w-full border border-ink/40 rounded px-3 py-2 focus:outline-none focus:border-ink">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Quantity</label>
                <input type="number" name="quantity" value="1" min="1" required
                       class="w-full border border-ink/40 rounded px-3 py-2 focus:outline-none focus:border-ink">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Price (per unit)</label>
                <input type="number" name="price" step="0.01" required
                       class="w-full border border-ink/40 rounded px-3 py-2 focus:outline-none focus:border-ink">
            </div>
            <button type="submit"
                    class="w-full bg-ink text-canvas py-2 rounded hover:opacity-80">
                Add Order
            </button>
        </form>

        <a href="/orders" class="block text-center text-sm text-ink/60 mt-4 hover:underline">
            &larr; Back to orders
        </a>
    </div>
</body>
</html>