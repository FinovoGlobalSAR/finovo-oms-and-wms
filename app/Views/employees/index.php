<?php
$title = 'Employee Management';

$employees = $employees ?? [];
$roles = $roles ?? [];
$success = $success ?? null;
$error = $error ?? null;
$old = $old ?? [];
$totalEmployees = count($employees);
$baseUrl = '/finovo-oms-and-wms/public/index.php';

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
?>

<div class="space-y-5">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <h1 class="text-2xl font-bold text-gray-900">Employee Management</h1>
                <span class="text-sm bg-gray-100 text-gray-600 px-2 py-1 rounded-md">
                    <?= $totalEmployees ?>
                </span>
            </div>
            <p class="text-sm text-gray-500 mt-1">Manage your team members and their roles.</p>
        </div>

        <button
            onclick="openEmployeeModal()"
            type="button"
            class="inline-flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-lg text-sm font-medium transition"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14M5 12h14" />
            </svg>
            Add Employee
        </button>
    </div>

    <?php if ($success): ?>
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
        <div class="p-4 border-b border-gray-200">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
                <div class="relative w-full lg:w-80">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35m2.35-5.65a8 8 0 11-16 0 8 8 0 0116 0z" />
                    </svg>
                    <input
                        id="employeeSearch"
                        type="text"
                        placeholder="Search employees..."
                        class="w-full pl-9 pr-4 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                </div>

                <div class="flex flex-wrap gap-2">
                    <select id="roleFilter" class="px-3 py-2 border border-gray-200 rounded-lg text-sm text-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">All Roles</option>
                        <?php foreach ($roles as $role): ?>
                            <option value="<?= htmlspecialchars(strtolower($role['name'])) ?>">
                                <?= htmlspecialchars($role['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <select id="statusFilter" class="px-3 py-2 border border-gray-200 rounded-lg text-sm text-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="text-left px-5 py-3 font-semibold text-gray-500">Employee</th>
                        <th class="text-left px-5 py-3 font-semibold text-gray-500">Email</th>
                        <th class="text-left px-5 py-3 font-semibold text-gray-500">Role</th>
                        <th class="text-left px-5 py-3 font-semibold text-gray-500">Status</th>
                        <th class="text-left px-5 py-3 font-semibold text-gray-500">Joined Date</th>
                        <th class="text-right px-5 py-3 font-semibold text-gray-500">Actions</th>
                    </tr>
                </thead>

                <tbody id="employeeTableBody" class="divide-y divide-gray-100">
                    <?php if (empty($employees)): ?>
                        <tr id="emptyEmployeeRow">
                            <td colspan="6" class="px-5 py-10 text-center text-gray-500">
                                No employees found. Click <strong>Add Employee</strong> to create your first employee.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($employees as $employee): ?>
                            <?php
                                $status = strtolower($employee['status']);
                                $isActive = $status === 'active';
                                $roleName = $employee['role_name'] ?? 'No Role';
                            ?>
                            <tr
                                class="employee-row hover:bg-gray-50 transition"
                                data-name="<?= htmlspecialchars(strtolower($employee['name'])) ?>"
                                data-email="<?= htmlspecialchars(strtolower($employee['email'])) ?>"
                                data-role="<?= htmlspecialchars(strtolower($roleName)) ?>"
                                data-status="<?= htmlspecialchars($status) ?>"
                            >
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-full flex items-center justify-center bg-blue-100 text-blue-600">
                                            <span class="text-sm font-semibold">
                                                <?= htmlspecialchars(employeeInitials($employee['name'])) ?>
                                            </span>
                                        </div>
                                        <span class="font-medium text-gray-800">
                                            <?= htmlspecialchars($employee['name']) ?>
                                        </span>
                                    </div>
                                </td>

                                <td class="px-5 py-4 text-gray-500">
                                    <?= htmlspecialchars($employee['email']) ?>
                                </td>

                                <td class="px-5 py-4">
                                    <span class="px-2.5 py-1 rounded-md text-xs font-medium bg-purple-50 text-purple-700">
                                        <?= htmlspecialchars($roleName) ?>
                                    </span>
                                </td>

                                <td class="px-5 py-4">
                                    <span class="inline-flex items-center gap-1.5 text-xs font-medium <?= $isActive ? 'text-green-600' : 'text-red-600' ?>">
                                        <span class="w-2 h-2 rounded-full <?= $isActive ? 'bg-green-500' : 'bg-red-500' ?>"></span>
                                        <?= $isActive ? 'Active' : 'Inactive' ?>
                                    </span>
                                </td>

                                <td class="px-5 py-4 text-gray-500">
                                    <?= htmlspecialchars(date('d M Y', strtotime($employee['created_at']))) ?>
                                </td>

                                <td class="px-5 py-4">
                                    <div class="flex items-center justify-end gap-2">
                                        <button
                                            type="button"
                                            onclick='openEditModal(<?= json_encode([
                                                "id" => (int) $employee["id"],
                                                "name" => $employee["name"],
                                                "email" => $employee["email"],
                                                "role_id" => (int) $employee["role_id"],
                                                "status" => $status,
                                            ], JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'
                                            class="px-3 py-1.5 border border-gray-200 rounded-md text-xs font-medium text-gray-600 hover:bg-gray-50"
                                        >
                                            Edit
                                        </button>

                                        <form method="POST" action="<?= $baseUrl ?>/employees/delete" onsubmit="return confirm('Are you sure you want to delete this employee?');">
                                            <input type="hidden" name="id" value="<?= (int) $employee['id'] ?>">
                                            <button type="submit" class="px-3 py-1.5 border border-red-200 rounded-md text-xs font-medium text-red-600 hover:bg-red-50">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="px-5 py-4 border-t border-gray-200 flex items-center justify-between gap-3">
            <p class="text-sm text-gray-500">
                Total <span class="font-medium text-gray-700"><?= $totalEmployees ?></span> employees
            </p>
        </div>
    </div>
</div>

<!-- CREATE EMPLOYEE MODAL -->
<div id="employeeModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4">
    <div onclick="closeEmployeeModal()" class="absolute inset-0 bg-black/40 backdrop-blur-sm"></div>

    <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-5 border-b border-gray-200">
            <div>
                <h2 class="text-lg font-bold text-gray-900">Create Employee</h2>
                <p class="text-sm text-gray-500 mt-1">Add a new employee and assign a role.</p>
            </div>
            <button type="button" onclick="closeEmployeeModal()" class="w-8 h-8 rounded-lg hover:bg-gray-100 flex items-center justify-center">
                <span class="text-xl text-gray-500">&times;</span>
            </button>
        </div>

        <form method="POST" action="<?= $baseUrl ?>/employees/store" class="p-6 space-y-5">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Full Name *</label>
                <input required type="text" name="name" value="<?= htmlspecialchars($old['name'] ?? '') ?>" placeholder="Enter employee name" class="w-full px-3.5 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Email Address *</label>
                <input required type="email" name="email" value="<?= htmlspecialchars($old['email'] ?? '') ?>" placeholder="employee@example.com" class="w-full px-3.5 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Temporary Password *</label>
                <input required minlength="6" type="password" name="password" placeholder="Minimum 6 characters" class="w-full px-3.5 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                <p class="text-xs text-gray-400 mt-1.5">The password is securely hashed before saving.</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Role *</label>
                <select required name="role_id" class="w-full px-3.5 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Select employee role</option>
                    <?php foreach ($roles as $role): ?>
                        <option value="<?= (int) $role['id'] ?>" <?= ((int) ($old['role_id'] ?? 0) === (int) $role['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($role['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="bg-blue-50 border border-blue-100 rounded-lg p-4">
                <p class="text-sm font-medium text-blue-800">Role-based permissions</p>
                <p class="text-xs text-blue-600 mt-1 leading-relaxed">The selected role can later be connected with your permissions table.</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Account Status</label>
                <div class="flex gap-4">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="status" value="active" <?= (($old['status'] ?? 'active') === 'active') ? 'checked' : '' ?> class="text-blue-600 focus:ring-blue-500">
                        <span class="text-sm text-gray-700">Active</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="status" value="inactive" <?= (($old['status'] ?? '') === 'inactive') ? 'checked' : '' ?> class="text-blue-600 focus:ring-blue-500">
                        <span class="text-sm text-gray-700">Inactive</span>
                    </label>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-3 border-t border-gray-100">
                <button type="button" onclick="closeEmployeeModal()" class="px-4 py-2.5 border border-gray-200 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-50">Cancel</button>
                <button type="submit" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium">Create Employee</button>
            </div>
        </form>
    </div>
</div>

<!-- EDIT EMPLOYEE MODAL -->
<div id="editEmployeeModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4">
    <div onclick="closeEditModal()" class="absolute inset-0 bg-black/40 backdrop-blur-sm"></div>

    <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-5 border-b border-gray-200">
            <div>
                <h2 class="text-lg font-bold text-gray-900">Edit Employee</h2>
                <p class="text-sm text-gray-500 mt-1">Update employee information and role.</p>
            </div>
            <button type="button" onclick="closeEditModal()" class="w-8 h-8 rounded-lg hover:bg-gray-100 flex items-center justify-center">
                <span class="text-xl text-gray-500">&times;</span>
            </button>
        </div>

        <form method="POST" action="<?= $baseUrl ?>/employees/update" class="p-6 space-y-5">
            <input type="hidden" id="edit_id" name="id">

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Full Name *</label>
                <input required id="edit_name" type="text" name="name" class="w-full px-3.5 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Email Address *</label>
                <input required id="edit_email" type="email" name="email" class="w-full px-3.5 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">New Password</label>
                <input minlength="6" type="password" name="password" placeholder="Leave blank to keep current password" class="w-full px-3.5 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Role *</label>
                <select required id="edit_role_id" name="role_id" class="w-full px-3.5 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <?php foreach ($roles as $role): ?>
                        <option value="<?= (int) $role['id'] ?>"><?= htmlspecialchars($role['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Account Status</label>
                <div class="flex gap-4">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input id="edit_status_active" type="radio" name="status" value="active" class="text-blue-600 focus:ring-blue-500">
                        <span class="text-sm text-gray-700">Active</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input id="edit_status_inactive" type="radio" name="status" value="inactive" class="text-blue-600 focus:ring-blue-500">
                        <span class="text-sm text-gray-700">Inactive</span>
                    </label>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-3 border-t border-gray-100">
                <button type="button" onclick="closeEditModal()" class="px-4 py-2.5 border border-gray-200 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-50">Cancel</button>
                <button type="submit" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEmployeeModal() {
    const modal = document.getElementById('employeeModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.classList.add('overflow-hidden');
}

function closeEmployeeModal() {
    const modal = document.getElementById('employeeModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    document.body.classList.remove('overflow-hidden');
}

function openEditModal(employee) {
    document.getElementById('edit_id').value = employee.id;
    document.getElementById('edit_name').value = employee.name;
    document.getElementById('edit_email').value = employee.email;
    document.getElementById('edit_role_id').value = employee.role_id;
    document.getElementById('edit_status_active').checked = employee.status === 'active';
    document.getElementById('edit_status_inactive').checked = employee.status === 'inactive';

    const modal = document.getElementById('editEmployeeModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.classList.add('overflow-hidden');
}

function closeEditModal() {
    const modal = document.getElementById('editEmployeeModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    document.body.classList.remove('overflow-hidden');
}

function filterEmployees() {
    const search = document.getElementById('employeeSearch').value.toLowerCase().trim();
    const role = document.getElementById('roleFilter').value;
    const status = document.getElementById('statusFilter').value;
    const rows = document.querySelectorAll('.employee-row');

    rows.forEach(row => {
        const matchesSearch = !search || row.dataset.name.includes(search) || row.dataset.email.includes(search);
        const matchesRole = !role || row.dataset.role === role;
        const matchesStatus = !status || row.dataset.status === status;
        row.style.display = matchesSearch && matchesRole && matchesStatus ? '' : 'none';
    });
}

document.getElementById('employeeSearch').addEventListener('input', filterEmployees);
document.getElementById('roleFilter').addEventListener('change', filterEmployees);
document.getElementById('statusFilter').addEventListener('change', filterEmployees);

<?php if ($error && !empty($old)): ?>
openEmployeeModal();
<?php endif; ?>
</script>
