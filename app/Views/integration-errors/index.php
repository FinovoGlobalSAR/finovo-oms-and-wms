<?php
$statusColors = [
    'pending'     => ['bg' => '#fef3c7', 'text' => '#92400e'],
    'dead_letter' => ['bg' => '#fee2e2', 'text' => '#991b1b'],
    'resolved'    => ['bg' => '#dcfce7', 'text' => '#166534'],
];

$errCounts = ['pending' => 0, 'dead_letter' => 0, 'resolved' => 0];
$connectors = [];
foreach ($errors as $e) {
    if (isset($errCounts[$e['status']])) { $errCounts[$e['status']]++; }
    $connectors[$e['connector']] = true;
}
ksort($connectors);
$openCount = $errCounts['pending'] + $errCounts['dead_letter'];

// Table mein sirf latest 5 errors (list pehle se naye se purane). Upar ke numbers phir bhi saari list se.
$latestErrors = array_slice($errors, 0, 5);
?>

<div class="ui-head">
    <div class="ui-head-left">
        <div class="breadcrumb">Finovo <i class="bi bi-chevron-right"></i> <span class="current">Integration Errors</span></div>
        <h1>Integration Errors <span class="count-badge"><?= count($errors) ?></span></h1>
        <p>Failed external API calls (Shopify/WooCommerce push) and their retry status.</p>
    </div>
</div>

<?php if ($errCounts['dead_letter'] > 0): ?>
    <div class="ui-alert">
        <div class="ui-tip-icon"><i class="bi bi-exclamation-triangle"></i></div>
        <div class="ui-alert-body">
            <strong><?= $errCounts['dead_letter'] ?> call(s) stopped retrying</strong>
            <span>These reached the maximum attempts and won't retry on their own. Fix the cause (for example, the store connection), then mark them resolved.</span>
        </div>
    </div>
<?php endif; ?>

<div class="ui-stats">
    <div class="ui-stat">
        <div class="ui-stat-icon"><i class="bi bi-bug"></i></div>
        <div>
            <div class="ui-stat-label">Open Errors</div>
            <div class="ui-stat-value"><b><?= $openCount ?></b>
                <span class="ui-pill <?= $openCount > 0 ? 'ui-pill-amber' : 'ui-pill-green' ?>"><?= $openCount > 0 ? 'Needs attention' : 'All clear' ?></span>
            </div>
        </div>
    </div>
    <div class="ui-stat">
        <div class="ui-stat-icon"><i class="bi bi-arrow-repeat"></i></div>
        <div>
            <div class="ui-stat-label">Retrying</div>
            <div class="ui-stat-value"><b><?= $errCounts['pending'] ?></b><span class="ui-pill ui-pill-amber">Pending</span></div>
        </div>
    </div>
    <div class="ui-stat">
        <div class="ui-stat-icon"><i class="bi bi-x-octagon"></i></div>
        <div>
            <div class="ui-stat-label">Stopped (Dead Letter)</div>
            <div class="ui-stat-value"><b><?= $errCounts['dead_letter'] ?></b>
                <span class="ui-pill <?= $errCounts['dead_letter'] > 0 ? 'ui-pill-red' : 'ui-pill-green' ?>"><?= $errCounts['dead_letter'] > 0 ? 'Fix needed' : 'None' ?></span>
            </div>
        </div>
    </div>
    <div class="ui-stat">
        <div class="ui-stat-icon"><i class="bi bi-check2-circle"></i></div>
        <div>
            <div class="ui-stat-label">Resolved</div>
            <div class="ui-stat-value"><b><?= $errCounts['resolved'] ?></b><span class="ui-pill ui-pill-green">Closed</span></div>
        </div>
    </div>
</div>

<div class="ui-card tbl-card">
    <div class="tbl-head">
        <h2>All Errors</h2>
        <div class="tbl-tools">
            <div class="ui-menu">
                <button type="button" class="tbl-btn" data-menu-toggle><i class="bi bi-sliders"></i> Filters</button>
                <div class="ui-menu-list tbl-filter-panel">
                    <label for="ieStatusFilter">Status</label>
                    <select id="ieStatusFilter">
                        <option value="">All statuses</option>
                        <option value="pending">Pending</option>
                        <option value="dead_letter">Dead Letter</option>
                        <option value="resolved">Resolved</option>
                    </select>
                    <label for="ieConnectorFilter">Connector</label>
                    <select id="ieConnectorFilter">
                        <option value="">All connectors</option>
                        <?php foreach (array_keys($connectors) as $cn): ?>
                            <option value="<?= htmlspecialchars($cn) ?>"><?= htmlspecialchars(ucfirst($cn)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
    </div>
    <div class="tbl-search">
        <label class="ui-search">
            <i class="bi bi-search"></i>
            <input type="text" id="ieSearch" placeholder="Search error message..." aria-label="Search errors">
        </label>
    </div>

    <div style="overflow-x:auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Connector</th>
                    <th>Error</th>
                    <th>Status</th>
                    <th style="width:140px; text-align:right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($errors)): ?>
                    <tr><td colspan="4" class="ui-empty"><i class="bi bi-check2-circle"></i>No integration errors — everything is syncing cleanly.</td></tr>
                <?php else: ?>
                    <?php foreach ($latestErrors as $e):
                        $color = $statusColors[$e['status']] ?? ['bg' => '#f1f5f9', 'text' => '#334155'];
                        $msg = (string) $e['error_message'];
                    ?>
                        <tr class="ie-row" data-status="<?= htmlspecialchars($e['status']) ?>" data-connector="<?= htmlspecialchars($e['connector']) ?>" data-search="<?= htmlspecialchars(strtolower($msg)) ?>">
                            <td>
                                <div style="display:flex; align-items:center; gap:10px;">
                                    <span style="width:28px; height:28px; border-radius:8px; background:var(--primary-light); color:var(--primary); display:flex; align-items:center; justify-content:center; flex-shrink:0;"><i class="bi bi-plug"></i></span>
                                    <span style="font-weight:600;"><?= htmlspecialchars(ucfirst($e['connector'])) ?></span>
                                </div>
                            </td>
                            <td style="max-width:320px;">
                                <?php if (mb_strlen($msg) > 60): ?>
                                    <details class="ie-details">
                                        <summary><?= htmlspecialchars(mb_substr($msg, 0, 60)) ?>…</summary>
                                        <div class="ie-full"><?= htmlspecialchars($msg) ?></div>
                                    </details>
                                <?php else: ?>
                                    <span style="font-size:12.5px; color:#334155;"><?= htmlspecialchars($msg) ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="ui-tag" style="background:<?= $color['bg'] ?>; color:<?= $color['text'] ?>; white-space:nowrap;">
                                    <?= ucwords(str_replace('_', ' ', $e['status'])) ?>
                                </span>
                            </td>
                            <td style="text-align:right;">
                                <?php if ($e['status'] !== 'resolved'): ?>
                                    <form action="/integration-errors/resolve" method="POST" style="margin:0;">
                                        <input type="hidden" name="id" value="<?= $e['id'] ?>">
                                        <button type="submit" class="ui-btn ui-btn-soft ui-btn-sm"><i class="bi bi-check2"></i> Mark Resolved</button>
                                    </form>
                                <?php else: ?>
                                    <span class="ui-tag ui-pill-gray"><i class="bi bi-lock"></i> Done</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <tr id="ieNoMatch" style="display:none;"><td colspan="4" class="ui-empty"><i class="bi bi-search"></i>No errors match your filters.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="pagination-bar">
        <span id="ieShowing">Showing latest <?= count($latestErrors) ?> of <?= count($errors) ?> errors</span>
    </div>
</div>

<style>
.ie-details summary { cursor: pointer; font-size: 12.5px; color: #334155; list-style: none; }
.ie-details summary::-webkit-details-marker { display: none; }
.ie-details summary::after { content: ' Show more'; color: var(--primary); font-weight: 600; font-size: 11.5px; }
.ie-details[open] summary::after { content: ' Show less'; }
.ie-full { margin-top: 6px; padding: 8px 10px; background: #f8fafc; border: 1px solid var(--border-color); border-radius: 8px; font-size: 12px; color: #334155; white-space: pre-wrap; word-break: break-word; font-family: ui-monospace, 'Cascadia Code', Consolas, monospace; }
</style>

<script>
(function () {
    var input = document.getElementById('ieSearch');
    var statusF = document.getElementById('ieStatusFilter');
    var connF = document.getElementById('ieConnectorFilter');
    var rows = Array.prototype.slice.call(document.querySelectorAll('.ie-row'));
    function apply() {
        var q = input.value.trim().toLowerCase();
        var st = statusF.value;
        var cn = connF.value;
        var shown = 0;
        rows.forEach(function (r) {
            var ok = (q === '' || r.dataset.search.indexOf(q) !== -1)
                  && (st === '' || r.dataset.status === st)
                  && (cn === '' || r.dataset.connector === cn);
            r.style.display = ok ? '' : 'none';
            if (ok) { shown++; }
        });
        var nm = document.getElementById('ieNoMatch');
        if (nm) { nm.style.display = shown === 0 ? '' : 'none'; }
        document.getElementById('ieShowing').textContent = 'Showing latest ' + shown + ' of <?= count($errors) ?> errors';
    }
    input.addEventListener('input', apply);
    statusF.addEventListener('change', apply);
    connF.addEventListener('change', apply);
})();
</script>