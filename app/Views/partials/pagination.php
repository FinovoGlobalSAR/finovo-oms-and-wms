<?php
/**
 * Neeche ka "Showing 1–25 of 230 orders   ← Prev 1 2 3 … 10 Next →"
 * Chahiye: $pager (Paginator::make se), $pagerLabel (jaise 'orders')
 */
require_once __DIR__ . '/../../../core/Paginator.php';
$pagerLabel = $pagerLabel ?? 'rows';
?>
<div class="pagination-bar">
    <span>
        <?php if ($pager['total'] > 0): ?>
            Showing <?= (int) $pager['from'] ?>–<?= (int) $pager['to'] ?> of <?= (int) $pager['total'] ?> <?= htmlspecialchars($pagerLabel) ?>
        <?php else: ?>
            0 <?= htmlspecialchars($pagerLabel) ?>
        <?php endif; ?>
    </span>
    <?php if ($pager['pages'] > 1): ?>
        <nav class="pagination-controls" aria-label="Pages">
            <?php if ($pager['page'] > 1): ?>
                <a class="page-num" href="<?= htmlspecialchars(Paginator::url($pager['page'] - 1)) ?>" style="text-decoration:none;"><i class="bi bi-arrow-left"></i> Prev</a>
            <?php else: ?>
                <button type="button" disabled><i class="bi bi-arrow-left"></i> Prev</button>
            <?php endif; ?>

            <?php foreach (Paginator::window($pager['page'], $pager['pages']) as $n): ?>
                <?php if ($n === null): ?>
                    <span style="padding:0 4px; color:var(--text-muted);">…</span>
                <?php elseif ($n === $pager['page']): ?>
                    <span class="page-num active" aria-current="page"><?= $n ?></span>
                <?php else: ?>
                    <a class="page-num" href="<?= htmlspecialchars(Paginator::url($n)) ?>" style="text-decoration:none;"><?= $n ?></a>
                <?php endif; ?>
            <?php endforeach; ?>

            <?php if ($pager['page'] < $pager['pages']): ?>
                <a class="page-num" href="<?= htmlspecialchars(Paginator::url($pager['page'] + 1)) ?>" style="text-decoration:none;">Next <i class="bi bi-arrow-right"></i></a>
            <?php else: ?>
                <button type="button" disabled>Next <i class="bi bi-arrow-right"></i></button>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
</div>