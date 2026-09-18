<?php
$actionColors = [
    'credential_update' => ['bg' => '#fef3c7', 'text' => '#b45309'],
    'mapping_create'     => ['bg' => '#dcfce7', 'text' => '#15803d'],
    'manual_override'    => ['bg' => '#fee2e2', 'text' => '#991b1b'],
];
?>

<div class="breadcrumb"><a href="/dashboard" class="breadcrumb-link">Finovo</a> <i class="bi bi-chevron-right"></i> Audit Log</div>

<div class="page-header-row">
    <h1>Audit Log <span class="count-badge"><?= count($logs) ?></span></h1>
</div>
<p class="page-subtitle">Permanent record of credential changes, mappings, and manual overrides.</p>

<div class="card">
    <table class="data-table">
        <thead>
            <tr>
                <th>Actor</th>
                <th>Action</th>
                <th>Entity</th>
                <th>Details</th>
                <th>When</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($logs)): ?>
                <tr><td colspan="5" style="text-align:center; color:#6b7280; padding:30px;">No audit entries yet.</td></tr>
            <?php else: ?>
                <?php foreach ($logs as $l):
                    $color = $actionColors[$l['action']] ?? ['bg' => '#f3f4f6', 'text' => '#374151'];
                ?>
                    <tr>
                        <td><?= htmlspecialchars($l['actor']) ?></td>
                        <td>
                            <span class="source-badge" style="background:<?= $color['bg'] ?>; color:<?= $color['text'] ?>;">
                                <?= ucwords(str_replace('_', ' ', $l['action'])) ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($l['entity_type']) ?><?= $l['entity_id'] ? ' #' . htmlspecialchars($l['entity_id']) : '' ?></td>
                        <td style="max-width:300px; font-size:12px; color:var(--text-muted);"><?= htmlspecialchars($l['details'] ?? '-') ?></td>
                        <td><?= date('d M Y, h:i A', strtotime($l['created_at'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>