<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Orders — Finovo OMS/WMS</title>
    <?php require __DIR__ . '/../partials/theme_header.php'; ?>
</head>
<body class="bg-canvas min-h-screen p-8 text-ink">
    <div class="max-w-4xl mx-auto">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold">Orders</h1>
                <p class="text-sm text-ink/60">All orders</p>
            </div>
            <a href="/orders/create" class="bg-ink text-canvas px-4 py-2 rounded hover:opacity-80">
                + Add New Order
            </a>
        </div>

        <div class="bg-canvas border border-ink rounded-lg p-4 mb-6">
            <p class="text-sm font-medium mb-1">API Key</p>
            <code class="text-xs bg-canvas px-2 py-1 rounded border border-ink/20 break-all">
                <?= htmlspecialchars($apiKey) ?>
            </code>
        </div>

        <div class="bg-canvas border border-ink rounded-lg overflow-hidden">
            <table class="w-full text-left">
                <thead class="border-b border-ink">
                    <tr>
                        <th class="p-3">Order ID</th>
                        <th class="p-3">Customer</th>
                        <th class="p-3">Product</th>
                        <th class="p-3">Qty</th>
                        <th class="p-3">Price</th>
                        <th class="p-3">Source</th>
                        <th class="p-3">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)): ?>
                        <tr><td colspan="7" class="p-4 text-center text-ink/60">No orders yet.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($orders as $order): ?>
                        <tr class="border-b border-ink/20">
                            <td class="p-3">#<?= $order['id'] ?></td>
                            <td class="p-3"><?= htmlspecialchars($order['customer_name']) ?></td>
                            <td class="p-3"><?= htmlspecialchars($order['product_name']) ?></td>
                            <td class="p-3"><?= $order['quantity'] ?></td>
                            <td class="p-3">Rs. <?= number_format($order['price'], 2) ?></td>
                            <td class="p-3">
                                <?php
                                $src = $order['source'] ?? 'manual';
                                $label = $src === 'api_push' ? 'Pushed' : 'Manual';
                                $classes = $src === 'api_push'
                                    ? 'bg-ink text-canvas'
                                    : 'bg-canvas text-ink border border-ink/40';
                                ?>
                                <span class="<?= $classes ?> px-2 py-1 rounded text-xs"><?= $label ?></span>
                            </td>
                            <td class="p-3">
                                <span class="bg-canvas text-ink border border-ink/40 px-2 py-1 rounded text-xs">
                                    <?= htmlspecialchars($order['status']) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>