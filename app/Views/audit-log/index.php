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

<div class="ui-card">
    <div class="al-toolbar">
        <label class="ui-search al-search">
            <i class="bi bi-search"></i>
            <input type="text" id="alSearch" placeholder="Search actor, entity or details..." aria-label="Search audit log">
        </label>
        <select id="alActionFilter" class="ui-btn" aria-label="Filter by action" style="padding-right:10px;">
            <option value="">All actions</option>
            <?php foreach ($actionCounts as $act => $cnt): ?>
                <option value="<?= htmlspecialchars($act) ?>"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $act))) ?> (<?= $cnt ?>)</option>
            <?php endforeach; ?>
        </select>
        <div style="margin-left:auto; flex-shrink:0; display:flex; align-items:center; gap:6px; font-size:12px; color:#64748b; white-space:nowrap;">
            <i class="bi bi-shield-lock"></i> Entries can't be edited or deleted
        </div>
    </div>

    <div style="overflow-x:auto;">
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
                    <tr><td colspan="5" class="ui-empty"><i class="bi bi-journal-text"></i>No audit entries yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($logs as $l):
                        $color = $actionColors[$l['action']] ?? ['bg' => '#f1f5f9', 'text' => '#334155'];
                        $entity = $l['entity_type'] . ($l['entity_id'] ? ' #' . $l['entity_id'] : '');
                    ?>
                        <tr class="al-row" data-action="<?= htmlspecialchars($l['action']) ?>" data-search="<?= htmlspecialchars(strtolower($l['actor'] . ' ' . $entity . ' ' . ($l['details'] ?? ''))) ?>">
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
                            <td style="color:#334155; white-space:nowrap;"><?= htmlspecialchars($entity) ?></td>
                            <td style="max-width:340px; font-size:12.5px; color:#475569;"><?= htmlspecialchars($l['details'] ?? '-') ?></td>
                            <td style="white-space:nowrap; font-size:12.5px; line-height:1.4;">
                                <?= date('d M Y', strtotime($l['created_at'])) ?><br>
                                <span style="color:#64748b;"><?= date('h:i A', strtotime($l['created_at'])) ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <tr id="alNoMatch" style="display:none;"><td colspan="5" class="ui-empty"><i class="bi bi-search"></i>No entries match your search.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="pagination-bar">
        <span id="alShowing">Showing <?= count($logs) ?> of <?= count($logs) ?> entries</span>
    </div>
</div>

<style>
.al-toolbar { display: flex; align-items: center; gap: 8px; flex-wrap: nowrap; padding: 14px 20px; border-bottom: 1px solid var(--border-color); overflow-x: auto; }
.al-search { flex: 0 1 380px; min-width: 180px; height: 34px; }
.al-toolbar .ui-btn { height: 34px; padding: 0 10px; font-size: 12.5px; white-space: nowrap; }
</style>

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
        document.getElementById('alShowing').textContent = 'Showing ' + shown + ' of ' + rows.length + ' entries';
    }
    input.addEventListener('input', apply);
    filter.addEventListener('change', apply);
})();
</script>