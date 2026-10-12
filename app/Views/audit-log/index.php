<?php
$actionColors = [
    'credential_update' => ['bg' => '#fef3c7', 'text' => '#92400e'],
    'mapping_create'    => ['bg' => '#dcfce7', 'text' => '#166534'],
    'manual_override'   => ['bg' => '#fee2e2', 'text' => '#991b1b'],
];

// ---------- Summary numbers (sirf display ke liye, $logs se hi) ----------
$actionCounts = [];
$actors = [];
foreach ($logs as $l) {
    $actionCounts[$l['action']] = ($actionCounts[$l['action']] ?? 0) + 1;
    $actors[$l['actor']] = true;
}
ksort($actionCounts);

// Table mein sirf latest 5 entries (list pehle se naye se purane). Upar ke numbers phir bhi saari list se.
$latestLogs = array_slice($logs, 0, 5);
?>

<div class="ui-head">
    <div class="ui-head-left">
        <div class="breadcrumb">Finovo <i class="bi bi-chevron-right"></i> <span class="current">Audit Log</span></div>
        <h1>Audit Log <span class="count-badge"><?= count($logs) ?></span></h1>
        <p>Permanent record of credential changes, mappings, and manual overrides.</p>
    </div>
</div>

<div class="ui-stats">
    <div class="ui-stat">
        <div class="ui-stat-icon"><i class="bi bi-journal-text"></i></div>
        <div>
            <div class="ui-stat-label">Total Entries</div>
            <div class="ui-stat-value"><b><?= count($logs) ?></b><span class="ui-pill ui-pill-blue">Recorded</span></div>
        </div>
    </div>
    <div class="ui-stat">
        <div class="ui-stat-icon"><i class="bi bi-key"></i></div>
        <div>
            <div class="ui-stat-label">Credential Updates</div>
            <div class="ui-stat-value"><b><?= $actionCounts['credential_update'] ?? 0 ?></b><span class="ui-pill ui-pill-amber">Sensitive</span></div>
        </div>
    </div>
    <div class="ui-stat">
        <div class="ui-stat-icon"><i class="bi bi-link-45deg"></i></div>
        <div>
            <div class="ui-stat-label">Mappings Created</div>
            <div class="ui-stat-value"><b><?= $actionCounts['mapping_create'] ?? 0 ?></b><span class="ui-pill ui-pill-green">Setup</span></div>
        </div>
    </div>
    <div class="ui-stat">
        <div class="ui-stat-icon"><i class="bi bi-people"></i></div>
        <div>
            <div class="ui-stat-label">People Involved</div>
            <div class="ui-stat-value"><b><?= count($actors) ?></b><span class="ui-pill ui-pill-blue">Actors</span></div>
        </div>
    </div>
</div>

<div class="ui-card tbl-card">
    <div class="tbl-head">
        <h2>All Entries</h2>
        <div class="tbl-tools">
            <span class="tbl-note"><i class="bi bi-shield-lock"></i> Entries can't be edited or deleted</span>
            <div class="ui-menu">
                <button type="button" class="tbl-btn" data-menu-toggle><i class="bi bi-sliders"></i> Filters</button>
                <div class="ui-menu-list tbl-filter-panel">
                    <label for="alActionFilter">Action</label>
                    <select id="alActionFilter">
                        <option value="">All actions</option>
                        <?php foreach ($actionCounts as $act => $cnt): ?>
                            <option value="<?= htmlspecialchars($act) ?>"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $act))) ?> (<?= $cnt ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
    </div>
    <div class="tbl-search">
        <label class="ui-search">
            <i class="bi bi-search"></i>
            <input type="text" id="alSearch" placeholder="Search actor or details..." aria-label="Search audit log">
        </label>
    </div>

    <div style="overflow-x:auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Actor</th>
                    <th>Action</th>
                    <th>Details</th>
                    <th>When</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr><td colspan="4" class="ui-empty"><i class="bi bi-journal-text"></i>No audit entries yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($latestLogs as $l):
                        $color = $actionColors[$l['action']] ?? ['bg' => '#f1f5f9', 'text' => '#334155'];
                    ?>
                        <tr class="al-row" data-action="<?= htmlspecialchars($l['action']) ?>" data-search="<?= htmlspecialchars(strtolower($l['actor'] . ' ' . ($l['details'] ?? ''))) ?>">
                            <td>
                                <div style="display:flex; align-items:center; gap:10px;">
                                    <span style="width:28px; height:28px; border-radius:50%; background:#1e293b; color:#fff; display:flex; align-items:center; justify-content:center; font-size:11px; font-weight:700; flex-shrink:0;"><?= htmlspecialchars(strtoupper(substr($l['actor'], 0, 1))) ?></span>
                                    <span style="font-weight:600;"><?= htmlspecialchars($l['actor']) ?></span>
                                </div>
                            </td>
                            <td>
                                <span class="ui-tag" style="background:<?= $color['bg'] ?>; color:<?= $color['text'] ?>; white-space:nowrap;">
                                    <?= ucwords(str_replace('_', ' ', $l['action'])) ?>
                                </span>
                            </td>
                            <td style="max-width:340px; font-size:12.5px; color:#475569;"><?= htmlspecialchars($l['details'] ?? '-') ?></td>
                            <td style="white-space:nowrap; font-size:12.5px; line-height:1.4;">
                                <?= date('d M Y', strtotime($l['created_at'])) ?><br>
                                <span style="color:#64748b;"><?= date('h:i A', strtotime($l['created_at'])) ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <tr id="alNoMatch" style="display:none;"><td colspan="4" class="ui-empty"><i class="bi bi-search"></i>No entries match your search.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="pagination-bar">
        <span id="alShowing">Showing latest <?= count($latestLogs) ?> of <?= count($logs) ?> entries</span>
    </div>
</div>


<script>
(function () {
    var input = document.getElementById('alSearch');
    var filter = document.getElementById('alActionFilter');
    var rows = Array.prototype.slice.call(document.querySelectorAll('.al-row'));
    function apply() {
        var q = input.value.trim().toLowerCase();
        var a = filter.value;
        var shown = 0;
        rows.forEach(function (r) {
            var ok = (q === '' || r.dataset.search.indexOf(q) !== -1) && (a === '' || r.dataset.action === a);
            r.style.display = ok ? '' : 'none';
            if (ok) { shown++; }
        });
        var nm = document.getElementById('alNoMatch');
        if (nm) { nm.style.display = shown === 0 ? '' : 'none'; }
        document.getElementById('alShowing').textContent = 'Showing latest ' + shown + ' of <?= count($logs) ?> entries';
    }
    input.addEventListener('input', apply);
    filter.addEventListener('change', apply);
})();
</script>