<?php
// ---------- Transfers + adjustments ko ek list mein jor do (sirf display ke liye) ----------
$movements = [];
foreach ($transfers as $t) {
    $movements[] = [
        'ref'     => 'TRF-' . str_pad((string) $t['id'], 6, '0', STR_PAD_LEFT),
        'type'    => 'Transfer',
        'product' => $t['product_name'],
        'from'    => $t['from_name'],
        'to'      => $t['to_name'],
        'qty'     => (int) $t['quantity'],
        'note'    => $t['notes'] ?? '',
        'date'    => $t['created_at'] ?? null,
    ];
}
foreach ($adjustments as $a) {
    $movements[] = [
        'ref'     => 'ADJ-' . str_pad((string) $a['id'], 6, '0', STR_PAD_LEFT),
        'type'    => 'Adjustment',
        'product' => $a['product_name'],
        'from'    => $a['warehouse_name'],
        'to'      => null,
        'qty'     => (int) $a['quantity_change'],
        'note'    => $a['reason'] ?? '',
        'date'    => $a['created_at'] ?? null,
    ];
}
usort($movements, fn($x, $y) => strcmp((string) $y['date'], (string) $x['date']));

$thisMonth = date('Y-m');
$movementsThisMonth = 0;
foreach ($movements as $m) {
    if ($m['date'] && substr($m['date'], 0, 7) === $thisMonth) { $movementsThisMonth++; }
}

// Adjustment ke baad wapas usi tab pe aao
$activeTab = !empty($adjusted) ? 'adjust' : 'transfer';

if (!function_exists('renderMovementRows')) {
function renderMovementRows(array $list): void
{
    foreach ($list as $m):
        $isTransfer = $m['type'] === 'Transfer';
        $qtyText = $isTransfer ? (string) $m['qty'] : (($m['qty'] >= 0 ? '+' : '') . $m['qty']);
        $qtyColor = $isTransfer ? '#0f172a' : ($m['qty'] >= 0 ? '#166534' : '#991b1b');
        ?>
        <tr class="mv-row" data-search="<?= htmlspecialchars(strtolower($m['ref'] . ' ' . $m['product'] . ' ' . $m['from'] . ' ' . ($m['to'] ?? '') . ' ' . $m['note'])) ?>">
            <td style="font-weight:700; white-space:nowrap;"><?= htmlspecialchars($m['ref']) ?></td>
            <td><span class="ui-tag <?= $isTransfer ? 'ui-pill-blue' : 'ui-pill-amber' ?>"><?= $m['type'] ?></span></td>
            <td style="color:#334155;"><?= htmlspecialchars($m['product']) ?></td>
            <td style="color:#334155;"><?= htmlspecialchars($m['from']) ?></td>
            <td style="color:#334155;"><?= $m['to'] !== null ? htmlspecialchars($m['to']) : '<span style="color:#94a3b8;">—</span>' ?></td>
            <td style="font-weight:700; color:<?= $qtyColor ?>;"><?= $qtyText ?></td>
            <td style="color:#475569; max-width:220px;"><?= $m['note'] !== '' ? htmlspecialchars($m['note']) : '<span style="color:#94a3b8;">—</span>' ?></td>
            <td style="color:#334155; font-size:12px; line-height:1.4; white-space:nowrap;">
                <?php if ($m['date']): ?>
                    <?= date('d M Y', strtotime($m['date'])) ?><br><span style="color:#64748b;"><?= date('h:i A', strtotime($m['date'])) ?></span>
                <?php else: ?>—<?php endif; ?>
            </td>
            <td><span class="ui-dot" style="color:#166534;">Completed</span></td>
        </tr>
        <?php
    endforeach;
}
}
?>

<div class="ui-head" style="align-items:center;">
    <div class="ui-head-left">
        <div class="breadcrumb">Finovo <i class="bi bi-chevron-right"></i> <span class="current">Stock Movements</span></div>
        <h1>Stock Movements</h1>
        <p>Transfer stock between warehouses, or adjust stock for damage, loss or correction.</p>
    </div>
    <div class="ui-head-right">
        <a href="/warehouses" class="ui-btn"><i class="bi bi-house-door"></i> View Warehouses</a>
    </div>
</div>

<?php if (!empty($transferred)): ?><div class="banner banner-success"><i class="bi bi-check-circle"></i> Stock transferred.</div><?php endif; ?>
<?php if (!empty($adjusted)): ?><div class="banner banner-success"><i class="bi bi-check-circle"></i> Stock adjusted.</div><?php endif; ?>
<?php if (!empty($error)): ?><div class="banner banner-error"><i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div><?php endif; ?>

<div class="ui-stats">
    <div class="ui-stat">
        <div class="ui-stat-icon"><i class="bi bi-house-door"></i></div>
        <div>
            <div class="ui-stat-label">Linked Warehouses</div>
            <div class="ui-stat-value"><b><?= count($warehouses) ?></b><span class="ui-pill ui-pill-blue">Active</span></div>
        </div>
    </div>
    <div class="ui-stat">
        <div class="ui-stat-icon"><i class="bi bi-box-seam"></i></div>
        <div>
            <div class="ui-stat-label">Total Products</div>
            <div class="ui-stat-value"><b><?= number_format(count($products)) ?></b><span class="ui-pill ui-pill-blue">In system</span></div>
        </div>
    </div>
    <div class="ui-stat">
        <div class="ui-stat-icon"><i class="bi bi-arrow-repeat"></i></div>
        <div>
            <div class="ui-stat-label">Movements This Month</div>
            <div class="ui-stat-value"><b><?= $movementsThisMonth ?></b><span class="ui-pill ui-pill-blue">On track</span></div>
        </div>
    </div>
    <div class="ui-stat">
        <div class="ui-stat-icon"><i class="bi bi-clock-history"></i></div>
        <div>
            <div class="ui-stat-label">Total Movements</div>
            <div class="ui-stat-value"><b><?= number_format(count($movements)) ?></b><span class="ui-pill ui-pill-blue">All time</span></div>
        </div>
    </div>
</div>

<div class="ui-tabs" role="tablist" aria-label="Stock movement type">
    <button type="button" class="ui-tab <?= $activeTab === 'transfer' ? 'active' : '' ?>" role="tab" data-tab="transfer"><i class="bi bi-arrow-left-right"></i> Transfer Stock</button>
    <button type="button" class="ui-tab <?= $activeTab === 'adjust' ? 'active' : '' ?>" role="tab" data-tab="adjust"><i class="bi bi-pencil"></i> Stock Adjustment</button>
    <button type="button" class="ui-tab" role="tab" data-tab="history"><i class="bi bi-clock-history"></i> Movement History</button>
</div>

<!-- ================= Transfer ================= -->
<div class="ui-card tab-panel" data-panel="transfer" style="padding:20px 22px; <?= $activeTab === 'transfer' ? '' : 'display:none;' ?>">
    <div style="display:flex; justify-content:space-between; align-items:center; padding-bottom:16px; margin-bottom:18px; border-bottom:1px solid var(--border-soft);">
        <div class="ui-card-title">
            <div class="ui-card-title-icon"><i class="bi bi-arrow-left-right"></i></div>
            <div>
                <h2 style="margin:0; font-size:15px; font-weight:700;">Transfer Stock Between Warehouses</h2>
                <div style="font-size:12.5px; color:#475569;">Move stock from one warehouse to another.</div>
            </div>
        </div>
        <span class="ui-tag ui-pill-blue">Transfer</span>
    </div>

    <form method="POST" action="/stock/transfer" style="display:flex; flex-direction:column; gap:18px;">
        <label class="ui-field">
            <span>Product</span>
            <span class="ui-input">
                <i class="bi bi-box-seam"></i>
                <select name="product_id" required>
                    <option value="">Select product...</option>
                    <?php foreach ($products as $p): ?>
                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </span>
        </label>

        <div class="ui-row">
            <label class="ui-field">
                <span>From Warehouse</span>
                <span class="ui-input">
                    <i class="bi bi-house-door"></i>
                    <select name="from_warehouse_id" required>
                        <option value="">Select warehouse...</option>
                        <?php foreach ($warehouses as $w): ?>
                            <option value="<?= $w['id'] ?>"><?= htmlspecialchars($w['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </span>
            </label>
            <label class="ui-field">
                <span>To Warehouse</span>
                <span class="ui-input">
                    <i class="bi bi-house-door"></i>
                    <select name="to_warehouse_id" required>
                        <option value="">Select warehouse...</option>
                        <?php foreach ($warehouses as $w): ?>
                            <option value="<?= $w['id'] ?>"><?= htmlspecialchars($w['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </span>
            </label>
        </div>

        <div class="ui-row">
            <label class="ui-field">
                <span>Quantity</span>
                <span class="ui-input">
                    <i class="bi bi-hash"></i>
                    <input type="number" name="quantity" min="1" required placeholder="Enter quantity...">
                </span>
            </label>
            <label class="ui-field">
                <span>Notes <small>(optional)</small></span>
                <span class="ui-input">
                    <i class="bi bi-file-earmark-text"></i>
                    <input type="text" name="notes" placeholder="e.g. Rebalancing stock, seasonal transfer">
                </span>
            </label>
        </div>

        <button type="submit" class="ui-btn ui-btn-primary ui-btn-block"><i class="bi bi-arrow-left-right"></i> Transfer Stock</button>
    </form>
</div>

<!-- ================= Adjustment ================= -->
<div class="ui-card tab-panel" data-panel="adjust" style="padding:20px 22px; <?= $activeTab === 'adjust' ? '' : 'display:none;' ?>">
    <div style="display:flex; justify-content:space-between; align-items:center; padding-bottom:16px; margin-bottom:18px; border-bottom:1px solid var(--border-soft);">
        <div class="ui-card-title">
            <div class="ui-card-title-icon" style="background:#fef3c7; color:#b45309;"><i class="bi bi-pencil"></i></div>
            <div>
                <h2 style="margin:0; font-size:15px; font-weight:700;">Adjust Stock</h2>
                <div style="font-size:12.5px; color:#475569;">Correct stock for damage, loss or a recount.</div>
            </div>
        </div>
        <span class="ui-tag ui-pill-amber">Adjustment</span>
    </div>

    <form method="POST" action="/stock/adjust" style="display:flex; flex-direction:column; gap:18px;">
        <div class="ui-row">
            <label class="ui-field">
                <span>Product</span>
                <span class="ui-input">
                    <i class="bi bi-box-seam"></i>
                    <select name="product_id" required>
                        <option value="">Select product...</option>
                        <?php foreach ($products as $p): ?>
                            <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </span>
            </label>
            <label class="ui-field">
                <span>Warehouse</span>
                <span class="ui-input">
                    <i class="bi bi-house-door"></i>
                    <select name="warehouse_id" required>
                        <option value="">Select warehouse...</option>
                        <?php foreach ($warehouses as $w): ?>
                            <option value="<?= $w['id'] ?>"><?= htmlspecialchars($w['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </span>
            </label>
        </div>

        <div class="ui-row">
            <div class="ui-field">
                <label for="adjQtyChange" style="font-size:12.5px; font-weight:600; color:var(--text-body);">Quantity Change</label>
                <span class="ui-input">
                    <i class="bi bi-plus-slash-minus"></i>
                    <input type="number" id="adjQtyChange" name="quantity_change" required placeholder="e.g. -5 for loss, 5 for found stock">
                </span>
                <p class="ui-help">Use a negative number to reduce stock, positive to add.</p>
            </div>
            <label class="ui-field">
                <span>Reason</span>
                <span class="ui-input">
                    <i class="bi bi-file-earmark-text"></i>
                    <input type="text" name="reason" required placeholder="e.g. Damaged in warehouse">
                </span>
            </label>
        </div>

        <button type="submit" class="ui-btn ui-btn-primary ui-btn-block"><i class="bi bi-check2-circle"></i> Apply Adjustment</button>
    </form>
</div>

<!-- ================= Movements table (Recent / full History) ================= -->
<div class="ui-card" id="movementsCard">
    <div class="ui-card-head">
        <h2 id="movementsTitle">Recent Movements</h2>
    </div>

    <!-- Ek hi line: search + type filter -->
    <div class="mv-toolbar">
        <label class="ui-search mv-search">
            <i class="bi bi-search"></i>
            <input type="text" id="mvSearch" placeholder="Search by reference, product or warehouse..." aria-label="Search movements">
        </label>
        <select id="mvTypeFilter" class="mv-select" aria-label="Filter by type">
            <option value="">All types</option>
            <option value="transfer">Transfers</option>
            <option value="adjustment">Adjustments</option>
        </select>
    </div>

    <div style="overflow-x:auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Reference</th>
                    <th>Type</th>
                    <th>Product</th>
                    <th>From / Warehouse</th>
                    <th>To</th>
                    <th>Qty</th>
                    <th>Notes / Reason</th>
                    <th>Date &amp; Time</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody id="mvBody">
                <?php if (empty($movements)): ?>
                    <tr><td colspan="9" class="ui-empty"><i class="bi bi-arrow-left-right"></i>No stock movements yet. Transfer or adjust stock above to see it here.</td></tr>
                <?php else: ?>
                    <?php renderMovementRows($movements); ?>
                    <tr id="mvNoMatch" style="display:none;"><td colspan="9" class="ui-empty"><i class="bi bi-search"></i>No movements match your search.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="pagination-bar">
        <span id="mvShowing"></span>
        <div class="pagination-controls" id="mvPager"></div>
    </div>
</div>

<style>
.mv-toolbar { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; padding: 14px 20px 16px; }
.mv-toolbar .ui-search, .mv-toolbar .mv-select { height: 36px; box-sizing: border-box; }
.mv-toolbar .mv-search { flex: 1 1 260px; max-width: 420px; }
.mv-select {
    padding: 0 26px 0 10px;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    background: #fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 16 16'%3E%3Cpath fill='%2364748b' d='M8 11L3 6h10z'/%3E%3C/svg%3E") no-repeat right 8px center;
    -webkit-appearance: none;
    appearance: none;
    font: inherit;
    font-size: 13px;
    font-weight: 600;
    color: var(--text-body);
    cursor: pointer;
}
@media (max-width: 700px) { .mv-toolbar .mv-search { flex: 1 1 100%; max-width: none; } }
</style>

<script>
(function () {
    var mode = '<?= $activeTab ?>';           // transfer | adjust | history
    var PER_PAGE = 10;
    var page = 1;
    var rows = Array.prototype.slice.call(document.querySelectorAll('.mv-row'));
    var search = document.getElementById('mvSearch');
    var typeFilter = document.getElementById('mvTypeFilter');
    var pager = document.getElementById('mvPager');
    var showing = document.getElementById('mvShowing');
    var noMatch = document.getElementById('mvNoMatch');

    function render() {
        var q = (search.value || '').trim().toLowerCase();
        var t = typeFilter.value;
        var list = rows.filter(function (r) {
            var typeOk = t === '' || r.querySelector('.ui-tag').textContent.trim().toLowerCase() === t;
            return typeOk && (q === '' || r.dataset.search.indexOf(q) !== -1);
        });

        // Transfer/Adjustment tabs pe sirf latest 5 dikhao, History tab pe sab (paged)
        var limit = mode === 'history' ? PER_PAGE : 5;
        var pages = mode === 'history' ? Math.max(1, Math.ceil(list.length / PER_PAGE)) : 1;
        if (page > pages) { page = pages; }
        var start = mode === 'history' ? (page - 1) * PER_PAGE : 0;

        rows.forEach(function (r) { r.style.display = 'none'; });
        list.slice(start, start + limit).forEach(function (r) { r.style.display = ''; });
        if (noMatch) { noMatch.style.display = (rows.length > 0 && list.length === 0) ? '' : 'none'; }

        var shown = Math.min(limit, Math.max(0, list.length - start));
        showing.textContent = 'Showing ' + shown + ' of ' + list.length + ' movements';

        pager.innerHTML = '';
        if (mode !== 'history') {
            if (list.length > 5) {
                var all = document.createElement('button');
                all.type = 'button';
                all.innerHTML = 'View full history <i class="bi bi-arrow-right"></i>';
                all.addEventListener('click', function () { setTab('history'); });
                pager.appendChild(all);
            }
            return;
        }
        if (pages <= 1) { return; }
        function add(label, target, disabled, active) {
            var b = document.createElement('button');
            b.type = 'button';
            b.innerHTML = label;
            b.disabled = disabled;
            if (active) { b.className = 'page-num active'; b.setAttribute('aria-current', 'page'); }
            b.addEventListener('click', function () { page = target; render(); });
            pager.appendChild(b);
        }
        add('<i class="bi bi-arrow-left"></i> Prev', page - 1, page === 1, false);
        for (var i = 1; i <= pages; i++) { add(String(i), i, false, i === page); }
        add('Next <i class="bi bi-arrow-right"></i>', page + 1, page === pages, false);
    }

    function setTab(tab) {
        mode = tab;
        page = 1;
        document.querySelectorAll('.ui-tab').forEach(function (b) {
            var on = b.dataset.tab === tab;
            b.classList.toggle('active', on);
            b.setAttribute('aria-selected', on ? 'true' : 'false');
        });
        document.querySelectorAll('.tab-panel').forEach(function (p) {
            p.style.display = p.dataset.panel === tab ? '' : 'none';
        });
        document.getElementById('movementsTitle').textContent = tab === 'history' ? 'Movement History' : 'Recent Movements';
        render();
    }

    document.querySelectorAll('.ui-tab').forEach(function (b) {
        b.addEventListener('click', function () { setTab(b.dataset.tab); });
    });
    search.addEventListener('input', function () { page = 1; render(); });
    typeFilter.addEventListener('change', function () { page = 1; render(); });
    setTab(mode);
})();
</script>