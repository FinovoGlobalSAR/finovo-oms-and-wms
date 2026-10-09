<?php

$title = 'Employee Management';

$employees = $employees ?? [];
$roles = $roles ?? [];
$success = $success ?? null;
$error = $error ?? null;
$old = $old ?? [];

$assignableRoles = $assignableRoles ?? $roles;   // Manager ko "Admin" role nahi dikhta
$stats = $stats ?? ['total' => count($employees), 'active' => 0, 'inactive' => 0];
$totalEmployees = (int) $stats['total'];
$search = $search ?? '';
$roleFilter = $roleFilter ?? '';
$statusFilter = $statusFilter ?? '';
$isFiltered = $search !== '' || $roleFilter !== '' || $statusFilter !== '';

$baseUrl = '';

if (!function_exists('employeeInitials')) {
    function employeeInitials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name));
        $initials = '';
        foreach (array_slice($parts, 0, 2) as $part) {
            if ($part !== '') {
                $initials .= strtoupper(substr($part, 0, 1));
            }
        }
        return $initials ?: 'U';
    }
}

// ---------- Upar ke numbers: controller POORI list se deta hai ----------
$activeCount = (int) $stats['active'];
$inactiveCount = (int) $stats['inactive'];

$avatarColors = [
    ['bg' => '#dbeafe', 'text' => '#1d4ed8'],
    ['bg' => '#dcfce7', 'text' => '#15803d'],
    ['bg' => '#fce7f3', 'text' => '#be185d'],
    ['bg' => '#fef3c7', 'text' => '#b45309'],
    ['bg' => '#e0e7ff', 'text' => '#4338ca'],
    ['bg' => '#cffafe', 'text' => '#0e7490'],
];
?>

<div class="ui-head">
    <div class="ui-head-left">
        <div class="breadcrumb">Finovo <i class="bi bi-chevron-right"></i> <span class="current">Employees</span></div>
        <h1>Employee Management <span class="count-badge"><?= $totalEmployees ?></span></h1>
        <p>Create employees, assign roles and manage your team.</p>
    </div>
</div>

<?php if ($success): ?>
    <div class="banner banner-success"><i class="bi bi-check-circle"></i> <?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="banner banner-error"><i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="ui-stats">
    <div class="ui-stat">
        <div class="ui-stat-icon"><i class="bi bi-people"></i></div>
        <div>
            <div class="ui-stat-label">Total Employees</div>
            <div class="ui-stat-value"><b><?= $totalEmployees ?></b><span class="ui-pill ui-pill-blue">Team</span></div>
        </div>
    </div>
    <div class="ui-stat">
        <div class="ui-stat-icon"><i class="bi bi-person-check"></i></div>
        <div>
            <div class="ui-stat-label">Active</div>
            <div class="ui-stat-value"><b><?= $activeCount ?></b><span class="ui-pill ui-pill-green">Can log in</span></div>
        </div>
    </div>
    <div class="ui-stat">
        <div class="ui-stat-icon"><i class="bi bi-person-dash"></i></div>
        <div>
            <div class="ui-stat-label">Inactive</div>
            <div class="ui-stat-value"><b><?= $inactiveCount ?></b><span class="ui-pill ui-pill-gray">Blocked</span></div>
        </div>
    </div>
    <div class="ui-stat">
        <div class="ui-stat-icon"><i class="bi bi-shield-check"></i></div>
        <div>
            <div class="ui-stat-label">Roles</div>
            <div class="ui-stat-value"><b><?= count($roles) ?></b><span class="ui-pill ui-pill-blue">Available</span></div>
        </div>
    </div>
</div>

<div class="ui-card">
    <!-- Search / filters: server pe chalte hain, har page ka result sahi -->
    <form method="GET" action="<?= $baseUrl ?>/employees" class="emp-toolbar">
        <label class="ui-search emp-search">
            <i class="bi bi-search"></i>
            <input name="q" type="text" value="<?= htmlspecialchars($search) ?>" placeholder="Search by name or email..." aria-label="Search employees">
        </label>
        <select name="role" class="ui-btn" aria-label="Filter by role" style="padding-right:10px;" onchange="this.form.submit()">
            <option value="">All Roles</option>
            <?php foreach ($roles as $role): ?>
                <?php $rv = strtolower($role['name']); ?>
                <option value="<?= htmlspecialchars($rv) ?>" <?= $roleFilter === $rv ? 'selected' : '' ?>><?= htmlspecialchars($role['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="status" class="ui-btn" aria-label="Filter by status" style="padding-right:10px;" onchange="this.form.submit()">
            <option value="">All Status</option>
            <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Active</option>
            <option value="inactive" <?= $statusFilter === 'inactive' ? 'selected' : '' ?>>Inactive</option>
        </select>
        <button type="submit" class="ui-btn"><i class="bi bi-search"></i> Search</button>
        <?php if ($isFiltered): ?>
            <a href="<?= $baseUrl ?>/employees" class="ui-btn"><i class="bi bi-x"></i> Clear</a>
        <?php endif; ?>
        <div class="emp-toolbar-actions">
            <button type="button" class="ui-btn ui-btn-primary" onclick="openEmployeeModal()"><i class="bi bi-person-plus"></i> Add Employee</button>
        </div>
    </form>

    <div style="overflow-x:auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Joined</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($employees)): ?>
                    <?php if ($isFiltered): ?>
                        <tr><td colspan="6" class="ui-empty"><i class="bi bi-search"></i>No employees match your filters.</td></tr>
                    <?php else: ?>
                        <tr><td colspan="6" class="ui-empty"><i class="bi bi-people"></i>No employees found. Click "Add Employee" to create the first account.</td></tr>
                    <?php endif; ?>
                <?php else: ?>
                    <?php $i = 0; foreach ($employees as $employee):
                        $status = strtolower($employee['status'] ?? 'inactive');
                        $roleName = $employee['role_name'] ?? 'No Role';
                        $isActive = $status === 'active';
                        $avColor = $avatarColors[$i % count($avatarColors)]; $i++;
                    ?>
                        <tr class="employee-row">
                            <td>
                                <div style="display:flex; align-items:center; gap:10px;">
                                    <span style="width:34px; height:34px; border-radius:50%; background:<?= $avColor['bg'] ?>; color:<?= $avColor['text'] ?>; display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:700; flex-shrink:0;">
                                        <?= htmlspecialchars(employeeInitials($employee['name'])) ?>
                                    </span>
                                    <span style="font-weight:600;"><?= htmlspecialchars($employee['name']) ?></span>
                                    <?php if (!empty($employee['is_self'])): ?><span class="ui-pill ui-pill-blue">You</span><?php endif; ?>
                                </div>
                            </td>
                            <td style="color:#475569;"><?= htmlspecialchars($employee['email']) ?></td>
                            <td>
                                <span class="ui-tag" style="background:var(--primary-light); color:var(--primary-dark); border:1px solid var(--primary-border);">
                                    <i class="bi bi-shield"></i> <?= htmlspecialchars($roleName) ?>
                                </span>
                            </td>
                            <td>
                                <span class="ui-dot" style="color:<?= $isActive ? '#166534' : '#b91c1c' ?>;"><?= $isActive ? 'Active' : 'Inactive' ?></span>
                            </td>
                            <td style="color:#334155; white-space:nowrap;">
                                <?= !empty($employee['created_at']) ? htmlspecialchars(date('d M Y', strtotime($employee['created_at']))) : '-' ?>
                            </td>
                            <td>
                                <div style="display:flex; justify-content:flex-end; gap:6px;">
                                    <?php if (!empty($employee['can_edit'])): ?>
                                        <button type="button"
                                            onclick='openEditModal(<?= json_encode([
                                                'id' => (int) $employee['id'],
                                                'name' => $employee['name'],
                                                'email' => $employee['email'],
                                                'role_id' => (int) $employee['role_id'],
                                                'status' => $status,
                                                'is_self' => !empty($employee['is_self']),
                                            ], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP) ?>)'
                                            class="ui-btn ui-btn-soft ui-btn-sm">
                                            <i class="bi bi-pencil"></i> Edit
                                        </button>
                                    <?php endif; ?>
                                    <?php if (!empty($employee['can_delete'])): ?>
                                        <form method="POST" action="<?= $baseUrl ?>/employees/delete" style="margin:0;"
                                              onsubmit="return confirm('Are you sure you want to delete this employee?');">
                                            <input type="hidden" name="id" value="<?= (int) $employee['id'] ?>">
                                            <button type="submit" class="ui-btn ui-btn-sm" style="border-color:#fecaca; color:#b91c1c;">
                                                <i class="bi bi-trash"></i> Delete
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if (empty($employee['can_edit'])): ?>
                                        <!-- Manager kisi Admin ko Edit / Delete nahi kar sakta -->
                                        <span style="font-size:12px; color:var(--text-muted);" title="Only an Admin can change an Admin account"><i class="bi bi-lock"></i> Admin only</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if (!empty($pager)) { $pagerLabel = 'employees'; require __DIR__ . '/../partials/pagination.php'; } ?>
</div>

<!-- ============================================ -->
<!-- ADD EMPLOYEE MODAL -->
<!-- ============================================ -->
<div class="modal-backdrop" id="employeeModal" onclick="if (event.target === this) closeEmployeeModal();">
    <div class="modal-box" style="max-width:480px;">
        <div class="modal-header">
            <div>
                <h2>Add Employee</h2>
                <p class="modal-help" style="margin:2px 0 0;">Create a new employee account.</p>
            </div>
            <button type="button" class="modal-close" onclick="closeEmployeeModal()" aria-label="Close">&times;</button>
        </div>
        <form method="POST" action="<?= $baseUrl ?>/employees/store">
            <div class="form-group">
                <label>Full Name *</label>
                <input required type="text" name="name" value="<?= htmlspecialchars($old['name'] ?? '') ?>" placeholder="e.g. Ali Khan">
            </div>
            <div class="form-group">
                <label>Email *</label>
                <input required type="email" name="email" value="<?= htmlspecialchars($old['email'] ?? '') ?>" placeholder="e.g. ali@company.com" class="emp-input">
            </div>
            <div class="form-group">
                <label>Password *</label>
                <input required minlength="6" type="password" name="password" placeholder="At least 6 characters" class="emp-input">
            </div>
            <div class="form-group">
                <label>Role *</label>
                <select required name="role_id" class="emp-input">
                    <option value="">Select Role</option>
                    <?php foreach ($assignableRoles as $role): ?>
                        <option value="<?= (int) $role['id'] ?>" <?= ((int) ($old['role_id'] ?? 0) === (int) $role['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($role['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Status</label>
                <div class="emp-radio-group">
                    <label class="emp-radio">
                        <input type="radio" name="status" value="active" <?= (($old['status'] ?? 'active') === 'active') ? 'checked' : '' ?>>
                        <span><i class="bi bi-person-check"></i> Active</span>
                    </label>
                    <label class="emp-radio">
                        <input type="radio" name="status" value="inactive" <?= (($old['status'] ?? '') === 'inactive') ? 'checked' : '' ?>>
                        <span><i class="bi bi-person-dash"></i> Inactive</span>
                    </label>
                </div>
            </div>
            <div class="form-actions">
                <button type="button" class="btn-secondary" onclick="closeEmployeeModal()">Cancel</button>
                <button type="submit" class="btn-primary">Create Employee</button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================ -->
<!-- EDIT EMPLOYEE MODAL -->
<!-- ============================================ -->
<div class="modal-backdrop" id="editEmployeeModal" onclick="if (event.target === this) closeEditModal();">
    <div class="modal-box" style="max-width:480px;">
        <div class="modal-header">
            <h2>Edit Employee</h2>
            <button type="button" class="modal-close" onclick="closeEditModal()" aria-label="Close">&times;</button>
        </div>
        <form method="POST" action="<?= $baseUrl ?>/employees/update">
            <input type="hidden" id="edit_id" name="id">
            <div class="form-group">
                <label for="edit_name">Full Name *</label>
                <input required id="edit_name" type="text" name="name">
            </div>
            <div class="form-group">
                <label for="edit_email">Email *</label>
                <input required id="edit_email" type="email" name="email" class="emp-input">
            </div>
            <div class="form-group">
                <label>New Password</label>
                <input minlength="6" type="password" name="password" placeholder="Leave blank to keep existing password" class="emp-input">
            </div>
            <div class="form-group">
                <label for="edit_role_id">Role *</label>
                <select required id="edit_role_id" name="role_id" class="emp-input">
                    <?php foreach ($assignableRoles as $role): ?>
                        <option value="<?= (int) $role['id'] ?>"><?= htmlspecialchars($role['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <!-- Apna account edit karte waqt role / status badal nahi sakte: select band, yeh hidden fields jaate hain -->
                <input type="hidden" id="edit_self_role_id" name="role_id" disabled>
                <input type="hidden" id="edit_self_status" name="status" value="active" disabled>
                <p id="edit_self_note" class="modal-help" style="display:none; margin:6px 0 0;">You cannot change your own role or status.</p>
            </div>
            <div class="form-group">
                <label>Status</label>
                <div class="emp-radio-group">
                    <label class="emp-radio">
                        <input id="edit_status_active" type="radio" name="status" value="active">
                        <span><i class="bi bi-person-check"></i> Active</span>
                    </label>
                    <label class="emp-radio">
                        <input id="edit_status_inactive" type="radio" name="status" value="inactive">
                        <span><i class="bi bi-person-dash"></i> Inactive</span>
                    </label>
                </div>
            </div>
            <div class="form-actions">
                <button type="button" class="btn-secondary" onclick="closeEditModal()">Cancel</button>
                <button type="submit" class="btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<style>
.emp-toolbar { display: flex; align-items: center; gap: 8px; flex-wrap: nowrap; padding: 14px 20px; border-bottom: 1px solid var(--border-color); overflow-x: auto; }
.emp-search { flex: 1 1 auto; min-width: 180px; height: 34px; }
.emp-toolbar-actions { display: flex; align-items: center; gap: 6px; flex-shrink: 0; margin-left: auto; }
.emp-toolbar .ui-btn { height: 34px; padding: 0 10px; font-size: 12.5px; white-space: nowrap; }
.emp-input { width: 100%; box-sizing: border-box; border: 1px solid var(--border-color); border-radius: 9px; padding: 9px 12px; font-size: 13.5px; font-family: inherit; background: #f8fafc; }
.emp-input:focus { outline: none; border-color: #93b4f5; background: #fff; box-shadow: 0 0 0 3px rgba(29,78,216,0.12); }
.emp-radio-group { display: flex; gap: 10px; }
.emp-radio { flex: 1; cursor: pointer; margin: 0 !important; }
.emp-radio input { position: absolute; opacity: 0; pointer-events: none; }
.emp-radio span { display: flex; align-items: center; justify-content: center; gap: 6px; height: 40px; border: 1px solid var(--border-color); border-radius: 9px; background: #f8fafc; font-size: 13px; font-weight: 500; color: var(--text-body); }
.emp-radio input:checked + span { border-color: var(--primary); background: var(--primary-light); color: var(--primary); font-weight: 600; }
.emp-radio input:focus-visible + span { outline: 2px solid #93b4f5; outline-offset: 2px; }
.modal-box { max-height: 90vh; overflow-y: auto; }
</style>

<script>
/* ---------- Add Employee Modal ---------- */
function openEmployeeModal() {
    document.getElementById('employeeModal').classList.add('show');
    document.body.classList.add('overflow-hidden');
}
function closeEmployeeModal() {
    document.getElementById('employeeModal').classList.remove('show');
    document.body.classList.remove('overflow-hidden');
}

/* ---------- Edit Employee Modal ---------- */
function openEditModal(employee) {
    document.getElementById('edit_id').value = employee.id;
    document.getElementById('edit_name').value = employee.name;
    document.getElementById('edit_email').value = employee.email;
    const roleSelect = document.getElementById('edit_role_id');
    roleSelect.value = employee.role_id;
    document.getElementById('edit_status_active').checked = employee.status === 'active';
    document.getElementById('edit_status_inactive').checked = employee.status === 'inactive';

    // Apna account: role / status band (server bhi rokta hai)
    const self = !!employee.is_self;
    roleSelect.disabled = self;
    document.getElementById('edit_status_active').disabled = self;
    document.getElementById('edit_status_inactive').disabled = self;
    document.getElementById('edit_self_role_id').disabled = !self;
    document.getElementById('edit_self_role_id').value = employee.role_id;
    document.getElementById('edit_self_status').disabled = !self;
    document.getElementById('edit_self_note').style.display = self ? '' : 'none';

    document.getElementById('editEmployeeModal').classList.add('show');
    document.body.classList.add('overflow-hidden');
}
function closeEditModal() {
    document.getElementById('editEmployeeModal').classList.remove('show');
    document.body.classList.remove('overflow-hidden');
}

<?php if ($error && !empty($old)): ?>
openEmployeeModal();
<?php endif; ?>
</script>