<?php
/**
 * Table ke neeche ki line: "Showing latest 5 of 230 orders"
 * Pages (Prev / Next) nahi hain — table mein sirf latest records dikhte hain, baaki sirf database mein.
 * Chahiye: $pager (Paginator::latest se), $pagerLabel (jaise 'orders')
 */
$pagerLabel = $pagerLabel ?? 'rows';
?>
<div class="pagination-bar">
    <span>
        <?php if ($pager['total'] > 0): ?>
            Showing latest <?= count($pager['items']) ?> of <?= (int) $pager['total'] ?> <?= htmlspecialchars($pagerLabel) ?>
        <?php else: ?>
            0 <?= htmlspecialchars($pagerLabel) ?>
        <?php endif; ?>
    </span>
</div>