<?php
$statusColors = [
    'pending'     => ['bg' => '#fef3c7', 'text' => '#b45309'],
    'dead_letter' => ['bg' => '#fee2e2', 'text' => '#991b1b'],
    'resolved'    => ['bg' => '#dcfce7', 'text' => '#15803d'],
];
?>

<div class="breadcrumb">Finovo <i class="bi bi-chevron-right"></i> Integration Errors</div>

<div class="page-header-row">
    <h1>Integration Errors <span class="count-badge"><?= count($errors) ?></span></h1>
</div>
<p class="page-subtitle">Failed external API calls (Shopify/WooCommerce push) and their retry status.</p>

<div class="card">
    <table class="data-table">
        <thead>
            <tr>
                <th>Connector</th>
                <th>Operation</th>
                <th>HTTP</th>
                <th>Error</th>
                <th>Attempts</th>
                <th>Status</th>
                <th style="width:100px;">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($errors)): ?>
                <tr><td colspan="7" style="text-align:center; color:#6b7280; padding:30px;">No integration errors — everything is syncing cleanly.</td></tr>
            <?php else: ?>
                <?php foreach ($errors as $e):
                    $color = $statusColors[$e['status']] ?? ['bg' => '#f3f4f6', 'text' => '#374151'];
                ?>
                    <tr>
                        <td><?= htmlspecialchars(ucfirst($e['connector'])) ?></td>
                        <td><?= htmlspecialchars($e['operation']) ?></td>
                        <td><?= $e['http_status'] ?? '-' ?></td>
                        <td style="max-width:280px; overflow:hidden; text-overflow:ellipsis; font-size:12px;" title="<?= htmlspecialchars($e['error_message']) ?>">
                            <?= htmlspecialchars(mb_substr($e['error_message'], 0, 60)) ?>...
                        </td>
                        <td><?= (int) $e['attempts'] ?> / 5</td>
                        <td>
                            <span class="source-badge" style="background:<?= $color['bg'] ?>; color:<?= $color['text'] ?>;">
                                <?= ucwords(str_replace('_', ' ', $e['status'])) ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($e['status'] !== 'resolved'): ?>
                                <form action="/integration-errors/resolve" method="POST">
                                    <input type="hidden" name="id" value="<?= $e['id'] ?>">
                                    <button type="submit" class="filter-btn" style="padding:5px 10px;">Mark Resolved</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>