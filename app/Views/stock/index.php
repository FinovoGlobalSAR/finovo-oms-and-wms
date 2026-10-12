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
        'before'  => $t['quantity_before'] ?? null,
        'after'   => $t['quantity_after'] ?? null,
        'user'    => $t['actor'] ?? null,
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
        'before'  => $a['quantity_before'] ?? null,
        'after'   => $a['quantity_after'] ?? null,
        'user'    => $a['actor'] ?? null,
        'note'    => $a['reason'] ?? '',
        'date'    => $a['created_at'] ?? null,
    ];
}
usort($movements, fn($x, $y) => strcmp((string) $y['date'], (string) $x['date']));

// Table mein sirf latest 5 movements (baaki sirf database mein). Upar ke numbers phir bhi poori list se.
$totalMovements = count($movements);
$latestMovements = array_slice($movements, 0, 5);

$thisMonth = date('Y-m');
$movementsThisMonth = 0;
foreach ($movements as $m) {
    if ($m['date'] && substr($m['date'], 0, 7) === $thisMonth) { $movementsThisMonth++; }
}

$stockMap = $stockMap ?? [];
$warehouseNames = [];
foreach ($warehouses as $w) {
    $warehouseNames[(int) $w['id']] = $w['name'];
}

// Adjustment ke baad wapas usi tab pe aao
$activeTab = !empty($adjusted) ? 'adjust' : 'transfer';

if (!function_exists('renderMovementRows')) {
function renderMovementRows(array $list): void
{
    foreach ($list as $i => $m):
        $isTransfer = $m['type'] === 'Transfer';
        $qtyText = $isTransfer ? (string) $m['qty'] : (($m['qty'] >= 0 ? '+' : '') . $m['qty']);
        $detail = [
            'Reference' => $m['ref'],
            'Type' => $m['type'],
            'Product' => $m['product'],
            ($isTransfer ? 'From Warehouse' : 'Warehouse') => $m['from'],
            'To Warehouse' => $m['to'] ?? '—',
            'Quantity' => $qtyText,
            'Before' => $m['before'] !== null ? (string) (int) $m['before'] : '—',
            'After' => $m['after'] !== null ? (string) (int) $m['after'] : '—',
            'User' => $m['user'] ?: '—',
            ($isTransfer ? 'Notes' : 'Reason') => $m['note'] !== '' ? $m['note'] : '—',
            'Date & Time' => $m['date'] ? date('d M Y, h:i A', strtotime($m['date'])) : '—',
        ];
        if ($isTransfer === false) { unset($detail['To Warehouse']); }
        ?>
        <tr class="mv-row" data-type="<?= strtolower($m['type']) ?>" data-search="<?= htmlspecialchars(strtolower($m['ref'] . ' ' . $m['product'] . ' ' . $m['from'] . ' ' . ($m['to'] ?? '') . ' ' . ($m['user'] ?? ''))) ?>">
            <td class="mv-ref"><?= htmlspecialchars($m['ref']) ?></td>
            <td><span class="mv-type <?= $isTransfer ? '' : 'adjust' ?>"><?= $m['type'] ?></span></td>
            <td><?= htmlspecialchars($m['product']) ?></td>
            <td><?= htmlspecialchars($m['from']) ?></td>
            <td><?= $m['to'] !== null ? htmlspecialchars($m['to']) : '—' ?></td>
            <td><?= $qtyText ?></td>
            <td><?= $m['before'] !== null ? (int) $m['before'] : '—' ?></td>
            <td><?= $m['after'] !== null ? (int) $m['after'] : '—' ?></td>
            <td><?= htmlspecialchars($m['user'] ?: '—') ?></td>
            <td class="mv-date">
                <?php if ($m['date']): ?>
                    <?= date('d M Y', strtotime($m['date'])) ?><br><?= date('h:i A', strtotime($m['date'])) ?>
                <?php else: ?>—<?php endif; ?>
            </td>
            <td><span class="mv-status">Completed</span></td>
            <td><button type="button" class="mv-view" data-detail="<?= htmlspecialchars(json_encode($detail), ENT_QUOTES) ?>">View</button></td>
        </tr>
        <?php
    endforeach;
}
}
?>

<div class="sm-page">

<div class="ui-head">
    <div class="ui-head-left">
        <div class="breadcrumb"><a href="/dashboard" class="sm-crumb">Home</a> <i class="bi bi-chevron-right"></i> <span class="current">Stock Movements</span></div>
        <h1>Stock Movements</h1>
        <p>Transfer stock between warehouses, or adjust stock for damage/loss/correction.</p>
    </div>
    <div class="sm-info">
        <i class="bi bi-info-circle"></i>
        <div class="sm-info-text">
            <strong>Keep your inventory accurate</strong>
            <span>Use stock movements to maintain accurate inventory across all warehouses and stores.</span>
        </div>
        <button type="button" class="sm-info-btn" onclick="document.getElementById('smHelp').classList.toggle('show');">Learn more</button>
        <p class="sm-help" id="smHelp">Transfer moves stock from one warehouse to another. Adjustment fixes stock after damage, loss or a recount. Every movement is saved with the stock before and after, and who made it.</p>
    </div>
</div>

<?php if (!empty($transferred)): ?><div class="banner banner-success"><i class="bi bi-check-circle"></i> Stock transferred.</div><?php endif; ?>
<?php if (!empty($adjusted)): ?><div class="banner banner-success"><i class="bi bi-check-circle"></i> Stock adjusted.</div><?php endif; ?>
<?php if (!empty($error)): ?><div class="banner banner-error"><i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div><?php endif; ?>

<div class="ui-stats">
    <div class="ui-stat">
        <div class="ui-stat-icon"><i class="bi bi-box"></i></div>
        <div>
            <div class="ui-stat-label">Total Warehouses</div>
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
    <div class="ui-tip">
        <div class="ui-tip-icon"><i class="bi bi-lightbulb"></i></div>
        <div>
            <strong>Best Practice</strong>
            <span>Record all stock movements to keep your inventory accurate and auditable.</span>
        </div>
    </div>
</div>

<div class="sm-tabs" role="tablist" aria-label="Stock movement type">
    <button type="button" class="sm-tab <?= $activeTab === 'transfer' ? 'active' : '' ?>" role="tab" data-tab="transfer"><i class="bi bi-arrow-left-right"></i> Transfer Stock</button>
    <button type="button" class="sm-tab <?= $activeTab === 'adjust' ? 'active' : '' ?>" role="tab" data-tab="adjust"><i class="bi bi-pencil"></i> Stock Adjustment</button>
    <button type="button" class="sm-tab" role="tab" data-tab="history"><i class="bi bi-file-earmark-text"></i> Movement History</button>
</div>

<!-- ================= Transfer ================= -->
<div class="sm-form-card tab-panel" data-panel="transfer" style="<?= $activeTab === 'transfer' ? '' : 'display:none;' ?>">
    <div class="sm-form-head">
        <div class="sm-form-title">
            <span class="sm-form-icon"><i class="bi bi-arrow-left-right"></i></span>
            <div>
                <h2>Transfer Stock Between Warehouses</h2>
                <p>Move stock from one warehouse to another.</p>
            </div>
        </div>
        <span class="sm-pill">Transfer</span>
    </div>

    <form method="POST" action="/stock/transfer" class="sm-form">
        <div class="sm-field">
            <label for="trProduct">Product</label>
            <div class="sm-input sm-select-wrap">
                <i class="bi bi-box"></i>
                <select name="product_id" id="trProduct" required>
                    <option value="">Select product...</option>
                    <?php /* Variant wala product: har variant alag (stock variant ka hota hai) */ ?>
                    <?php foreach ($stockItems as $item): ?>
                        <option value="<?= htmlspecialchars($item['value']) ?>"><?= htmlspecialchars($item['label']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="sm-available" id="trAvailable" hidden>
                <i class="bi bi-box"></i>
                <div>
                    <div>Available Stock: <span id="trAvailableQty">0</span> units</div>
                    <div>Warehouse: <span id="trAvailableWh">All linked warehouses</span></div>
                </div>
                <a href="#" id="trByWarehouse" class="sm-available-link">View by Warehouse</a>
            </div>
        </div>

        <div class="sm-row">
            <div class="sm-field">
                <label for="trFrom">From Warehouse</label>
                <div class="sm-input sm-select-wrap">
                    <i class="bi bi-house"></i>
                    <select name="from_warehouse_id" id="trFrom" required>
                        <option value="">Select warehouse...</option>
                        <?php foreach ($warehouses as $w): ?>
                            <option value="<?= $w['id'] ?>"><?= htmlspecialchars($w['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="sm-field">
                <label for="trTo">To Warehouse</label>
                <div class="sm-input sm-select-wrap">
                    <i class="bi bi-house"></i>
                    <select name="to_warehouse_id" id="trTo" required>
                        <option value="">Select warehouse...</option>
                        <?php foreach ($warehouses as $w): ?>
                            <option value="<?= $w['id'] ?>"><?= htmlspecialchars($w['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="sm-row">
            <div class="sm-field">
                <label for="trQty">Quantity</label>
                <div class="sm-input">
                    <i class="bi bi-hash"></i>
                    <input type="number" name="quantity" id="trQty" min="1" required placeholder="Enter quantity...">
                </div>
            </div>
            <div class="sm-field">
                <label for="trNotes">Notes (optional)</label>
                <div class="sm-input">
                    <i class="bi bi-file-earmark-text"></i>
                    <input type="text" name="notes" id="trNotes" placeholder="e.g. Rebalancing stock, seasonal transfer, etc.">
                </div>
            </div>
        </div>

        <button type="submit" class="sm-submit"><i class="bi bi-arrow-left-right"></i> Transfer Stock</button>
    </form>
</div>

<!-- ================= Adjustment ================= -->
<div class="sm-form-card tab-panel" data-panel="adjust" style="<?= $activeTab === 'adjust' ? '' : 'display:none;' ?>">
    <div class="sm-form-head">
        <div class="sm-form-title">
            <span class="sm-form-icon"><i class="bi bi-pencil"></i></span>
            <div>
                <h2>Adjust Stock</h2>
                <p>Correct stock for damage, loss or a recount.</p>
            </div>
        </div>
        <span class="sm-pill">Adjustment</span>
    </div>

    <form method="POST" action="/stock/adjust" class="sm-form">
        <div class="sm-row">
            <div class="sm-field">
                <label for="adProduct">Product</label>
                <div class="sm-input sm-select-wrap">
                    <i class="bi bi-box"></i>
                    <select name="product_id" id="adProduct" required>
                        <option value="">Select product...</option>
                        <?php foreach ($stockItems as $item): ?>
                            <option value="<?= htmlspecialchars($item['value']) ?>"><?= htmlspecialchars($item['label']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="sm-field">
                <label for="adWarehouse">Warehouse</label>
                <div class="sm-input sm-select-wrap">
                    <i class="bi bi-house"></i>
                    <select name="warehouse_id" id="adWarehouse" required>
                        <option value="">Select warehouse...</option>
                        <?php foreach ($warehouses as $w): ?>
                            <option value="<?= $w['id'] ?>"><?= htmlspecialchars($w['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="sm-row">
            <div class="sm-field">
                <label for="adjQtyChange">Quantity Change</label>
                <div class="sm-input">
                    <i class="bi bi-plus-slash-minus"></i>
                    <input type="number" id="adjQtyChange" name="quantity_change" required placeholder="e.g. -5 for loss, 5 for found stock">
                </div>
                <p class="ui-help">Use a negative number to reduce stock, positive to add.</p>
            </div>
            <div class="sm-field">
                <label for="adReason">Reason</label>
                <div class="sm-input">
                    <i class="bi bi-file-earmark-text"></i>
                    <input type="text" name="reason" id="adReason" required placeholder="e.g. Damaged in warehouse">
                </div>
            </div>
        </div>

        <button type="submit" class="sm-submit"><i class="bi bi-check2-circle"></i> Apply Adjustment</button>
    </form>
</div>

<!-- ================= Movements table (sirf latest 5) ================= -->
<div class="ui-card sm-table-card" id="movementsCard" style="overflow:visible;">
    <h2 class="sm-table-title" id="movementsTitle">Recent Movements</h2>

    <div class="sm-table-tools">
        <label class="ui-search">
            <i class="bi bi-search"></i>
            <input type="text" id="mvSearch" placeholder="Search by reference, product or warehouse..." aria-label="Search movements">
        </label>
        <div class="ui-menu">
            <button type="button" class="pk-btn sm-filter-btn" data-menu-toggle><i class="bi bi-sliders"></i> <span id="mvFilterLabel">Filters</span></button>
            <div class="ui-menu-list">
                <button type="button" data-mv-type="">All types</button>
                <button type="button" data-mv-type="transfer">Transfers</button>
                <button type="button" data-mv-type="adjustment">Adjustments</button>
            </div>
        </div>
    </div>

    <div style="overflow-x:auto;">
        <table class="data-table sm-table">
            <thead>
                <tr>
                    <th>Reference</th>
                    <th>Type</th>
                    <th>Product</th>
                    <th>From Warehouse</th>
                    <th>To Warehouse</th>
                    <th>Quantity</th>
                    <th>Before</th>
                    <th>After</th>
                    <th>User</th>
                    <th>Date &amp; Time</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="mvBody">
                <?php if (empty($latestMovements)): ?>
                    <tr><td colspan="12" class="ui-empty"><i class="bi bi-arrow-left-right"></i>No stock movements yet. Transfer or adjust stock above to see it here.</td></tr>
                <?php else: ?>
                    <?php renderMovementRows($latestMovements); ?>
                    <tr id="mvNoMatch" style="display:none;"><td colspan="12" class="ui-empty"><i class="bi bi-search"></i>No movements match your search.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="pagination-bar">
        <span id="mvShowing"></span>
    </div>
</div>

</div>

<!-- Movement ki poori detail (notes / reason yahan) -->
<div class="modal-backdrop" id="mvDetailModal">
    <div class="modal-box">
        <div class="modal-header">
            <h2>Movement details</h2>
            <button type="button" class="modal-close" aria-label="Close" onclick="document.getElementById('mvDetailModal').classList.remove('show')">&times;</button>
        </div>
        <dl class="mv-detail" id="mvDetailList"></dl>
    </div>
</div>

<style>
/* Stock Movements — Figma "finovo-stock-movements" */
.sm-page { font-family: var(--font-body); }
.sm-page .ui-head { margin-bottom: 28px; }
.sm-page .breadcrumb { font-family: var(--font-ui); font-weight: 400; gap: 8px; }
.sm-crumb { color: var(--text-light); text-decoration: none; }
.sm-crumb:hover { text-decoration: underline; }
.sm-page .ui-head h1 { font-family: var(--font-ui); font-weight: 400; }
.sm-page .ui-head p { font-family: var(--font-ui); }
.sm-info { width: 420px; max-width: 100%; display: flex; flex-wrap: wrap; align-items: center; gap: 12px; padding: 16px; background: var(--primary-light); border: 1px solid var(--border-color); border-radius: 10px; font-family: var(--font-ui); box-sizing: border-box; }
.sm-info > i { color: var(--brand); font-size: 18px; }
.sm-info-text { flex: 1; min-width: 0; }
.sm-info-text strong { display: block; font-size: 13px; font-weight: 400; color: var(--brand); margin-bottom: 2px; }
.sm-info-text span { display: block; font-size: 11px; line-height: 1.4; color: var(--text-body); }
.sm-info-btn { flex-shrink: 0; padding: 6px 10px; background: #fff; border: 1px solid var(--border-input); border-radius: 6px; font-family: inherit; font-size: 11px; color: var(--text-dark); cursor: pointer; }
.sm-help { display: none; flex-basis: 100%; margin: 0; padding: 8px 10px; background: #fff; border: 1px solid var(--primary-border); border-radius: 8px; font-size: 12px; line-height: 1.45; color: var(--text-body); }
.sm-help.show { display: block; }
.sm-info-btn:hover { background: #f8fafc; }

.sm-page .ui-stats { gap: 12px; margin-bottom: 28px; }
.sm-page .ui-stat { padding: 16px; gap: 14px; border-radius: 14px; }
.sm-page .ui-stat-icon { width: 40px; height: 40px; border-radius: 10px; font-size: 18px; }
.sm-page .ui-stat-label { font-weight: 400; color: var(--text-muted); }
.sm-page .ui-stat-value { gap: 6px; margin-top: 3px; }
.sm-page .ui-stat-value b { font-weight: 400; color: #1e293b; }
.sm-page .ui-stat .ui-pill { font-size: 10px; font-weight: 400; padding: 2px 7px; border-radius: 5px; }
.sm-page .ui-tip strong { font-weight: 400; }

.sm-tabs { display: flex; gap: 5px; margin-bottom: 10px; flex-wrap: wrap; }
.sm-tab { display: inline-flex; align-items: center; gap: 10px; height: 37px; padding: 0 10px; border: none; border-bottom: 2px solid transparent; border-radius: 5px; background: #fff; font-family: var(--font-ui); font-size: 14px; color: var(--text-muted); cursor: pointer; }
.sm-tab.active { border-bottom-color: var(--primary); color: var(--primary); }

.sm-form-card { display: flex; flex-direction: column; gap: 20px; padding: 28px; margin-bottom: 28px; background: #fff; border: 1px solid var(--border-color); border-radius: 16px; font-family: var(--font-ui); }
.sm-form-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding-bottom: 20px; border-bottom: 1px solid var(--border-color); }
.sm-form-title { display: flex; align-items: center; gap: 12px; }
.sm-form-icon { padding: 8px; border-radius: 10px; background: var(--primary-light); color: var(--primary); font-size: 18px; line-height: 1; display: flex; }
.sm-form-title h2 { margin: 0; font-size: 16px; font-weight: 400; color: var(--text-dark); }
.sm-form-title p { margin: 2px 0 0; font-size: 12px; color: var(--text-muted); }
.sm-pill { padding: 4px 10px; border-radius: 100px; background: var(--primary-light); color: var(--brand); font-size: 12px; }
.sm-form { display: flex; flex-direction: column; gap: 16px; }
.sm-row { display: flex; gap: 16px; flex-wrap: wrap; }
.sm-row > .sm-field { flex: 1 1 280px; min-width: 0; }
.sm-field { display: flex; flex-direction: column; gap: 6px; }
.sm-field label { font-size: 13px; color: var(--text-dark); }
.sm-input { position: relative; display: flex; align-items: center; gap: 10px; height: 55px; padding: 0 14px; background: #f8fafc; border: 1px solid var(--border-input); border-radius: 8px; color: var(--text-dark); }
.sm-input > i { font-size: 16px; }
.sm-input:focus-within { border-color: #93b4f5; background: #fff; box-shadow: 0 0 0 3px rgba(37,99,235,0.12); }
.sm-input input, .sm-input select { flex: 1; min-width: 0; height: 100%; border: none; outline: none; background: transparent; font-family: inherit; font-size: 14px; color: var(--text-dark); }
.sm-input input::placeholder { color: var(--text-muted); }
.sm-select-wrap select { -webkit-appearance: none; appearance: none; cursor: pointer; padding-right: 26px; }
.sm-select-wrap::after { content: ''; position: absolute; right: 18px; top: 50%; width: 8px; height: 8px; margin-top: -6px; border-right: 1.6px solid #475569; border-bottom: 1.6px solid #475569; transform: rotate(45deg); pointer-events: none; }
.sm-available { display: flex; align-items: center; gap: 18px; margin-top: -6px; padding: 10px; background: #f3f9fe; border-radius: 0 0 10px 10px; font-size: 14px; color: var(--text-dark); }
.sm-available[hidden] { display: none; }
.sm-available > i { font-size: 20px; }
.sm-available > div { flex: 1; display: flex; flex-direction: column; gap: 3px; }
.sm-available-link { font-size: 14px; color: var(--brand); text-decoration: none; white-space: nowrap; }
.sm-available-link:hover { text-decoration: underline; }
.sm-submit { display: flex; align-items: center; justify-content: center; gap: 8px; height: 55px; margin-top: 26px; border: none; border-radius: 10px; background: var(--brand); color: #fff; font-family: var(--font-ui); font-size: 18px; cursor: pointer; }
.sm-submit:hover { background: #1a46b8; }

.sm-table-card { border-radius: 14px; padding: 22px 0 20px; }
.sm-table-title { margin: 0 22px; font-size: 16px; font-weight: 700; color: #1e293b; }
.sm-table-tools { display: flex; align-items: center; gap: 12px; padding: 14px 22px; }
.sm-table-tools .ui-search { flex: 1; max-width: 967px; height: 32px; }
.sm-table-tools .ui-menu { margin-left: auto; }
.pk-btn { display: inline-flex; align-items: center; gap: 7px; height: 32px; padding: 0 14px; border: 1px solid var(--border-color); border-radius: 8px; background: #fff; color: var(--text-muted); font-family: inherit; font-size: 12px; font-weight: 600; cursor: pointer; white-space: nowrap; }
.pk-btn:hover { background: #f8fafc; }
table.data-table.sm-table th { padding: 10px 8px; font-size: 12px; font-weight: 700; color: var(--text-muted); background: #f8fafc; text-align: center; text-transform: none; }
table.data-table.sm-table td { padding: 12px 8px; font-size: 13px; color: var(--text-body); border-bottom: 1px solid var(--border-color); text-align: center; }
.sm-table .mv-ref, .sm-table .mv-date, .sm-table td:nth-child(2), .sm-table td:nth-last-child(-n+2) { white-space: nowrap; }
table.data-table.sm-table th:first-child, table.data-table.sm-table td:first-child { padding-left: 22px; text-align: left; }
table.data-table.sm-table th:last-child, table.data-table.sm-table td:last-child { padding-right: 22px; }
table.data-table.sm-table td.ui-empty { text-align: center; }
.sm-table .mv-ref { font-weight: 600; color: #1e293b; }
.sm-table .mv-date { line-height: 1.25; }
.mv-type { display: inline-block; padding: 3px 10px; border-radius: 6px; background: #eaf4ff; border: 1px solid var(--border-color); color: var(--primary); font-size: 12px; font-weight: 500; }
.mv-type.adjust { background: #fffbeb; color: #b45309; }
.mv-status { display: inline-flex; align-items: center; gap: 3px; font-weight: 600; color: #0d8039; }
.mv-status::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
.mv-view { padding: 4px 8px 4px 10px; border: 1px solid var(--border-color); border-radius: 6px; background: #fff; color: var(--primary); font-family: inherit; font-size: 12px; font-weight: 600; cursor: pointer; }
.mv-view:hover { background: var(--primary-light); }
.sm-table-card .pagination-bar { border-top: none; padding: 20px 22px 0; }
.mv-detail { display: grid; grid-template-columns: 130px 1fr; gap: 8px 12px; margin: 0; font-size: 13px; }
.mv-detail dt { color: var(--text-muted); }
.mv-detail dd { margin: 0; color: var(--text-dark); font-weight: 500; word-break: break-word; }
</style>

<script>
(function () {
    // ---------- "Available Stock" box (product + From warehouse ke mutabiq) ----------
    var stockMap = <?= json_encode($stockMap, JSON_HEX_TAG | JSON_HEX_AMP) ?>;
    var warehouseNames = <?= json_encode($warehouseNames, JSON_HEX_TAG | JSON_HEX_AMP) ?>;
    var productSel = document.getElementById('trProduct');
    var fromSel = document.getElementById('trFrom');
    var box = document.getElementById('trAvailable');

    function updateAvailable() {
        var value = productSel.value;
        if (!value) { box.hidden = true; return; }
        var perWarehouse = stockMap[value] || {};
        var qty = 0;
        var whText = 'All linked warehouses';
        if (fromSel.value) {
            qty = perWarehouse[fromSel.value] || 0;
            whText = warehouseNames[fromSel.value] || '';
        } else {
            Object.keys(perWarehouse).forEach(function (k) { qty += perWarehouse[k]; });
        }
        document.getElementById('trAvailableQty').textContent = qty;
        document.getElementById('trAvailableWh').textContent = whText;
        document.getElementById('trByWarehouse').href = '/warehouses/product-stock?product_id=' + encodeURIComponent(value.split(':')[0]);
        box.hidden = false;
    }
    productSel.addEventListener('change', updateAvailable);
    fromSel.addEventListener('change', updateAvailable);

    // ---------- Movements table ----------
    var mode = '<?= $activeTab ?>';           // transfer | adjust | history
    var TOTAL = <?= (int) $totalMovements ?>;  // database mein kul movements (sirf ginti)
    var typeFilter = '';
    var rows = Array.prototype.slice.call(document.querySelectorAll('.mv-row'));   // sirf latest 5 hi page pe aate hain
    var search = document.getElementById('mvSearch');
    var showing = document.getElementById('mvShowing');
    var noMatch = document.getElementById('mvNoMatch');

    function render() {
        var q = (search.value || '').trim().toLowerCase();
        var shown = 0;
        rows.forEach(function (r) {
            var typeOk = typeFilter === '' || r.dataset.type === typeFilter;
            var ok = typeOk && (q === '' || r.dataset.search.indexOf(q) !== -1);
            r.style.display = ok ? '' : 'none';
            if (ok) { shown++; }
        });
        if (noMatch) { noMatch.style.display = (rows.length > 0 && shown === 0) ? '' : 'none'; }
        showing.textContent = 'Showing latest ' + shown + ' of ' + TOTAL + ' movements';
    }

    function setTab(tab) {
        mode = tab;
        document.querySelectorAll('.sm-tab').forEach(function (b) {
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

    document.querySelectorAll('.sm-tab').forEach(function (b) {
        b.addEventListener('click', function () { setTab(b.dataset.tab); });
    });
    document.querySelectorAll('[data-mv-type]').forEach(function (b) {
        b.addEventListener('click', function () {
            typeFilter = b.dataset.mvType;
            document.getElementById('mvFilterLabel').textContent = typeFilter === '' ? 'Filters' : b.textContent;
            render();
        });
    });
    search.addEventListener('input', render);

    // "View" — poori detail modal mein
    document.getElementById('mvBody').addEventListener('click', function (e) {
        var btn = e.target.closest('.mv-view');
        if (!btn) { return; }
        var detail = JSON.parse(btn.dataset.detail);
        var list = document.getElementById('mvDetailList');
        list.innerHTML = '';
        Object.keys(detail).forEach(function (key) {
            var dt = document.createElement('dt');
            dt.textContent = key;
            var dd = document.createElement('dd');
            dd.textContent = detail[key];
            list.appendChild(dt);
            list.appendChild(dd);
        });
        document.getElementById('mvDetailModal').classList.add('show');
    });

    setTab(mode);
})();
</script>