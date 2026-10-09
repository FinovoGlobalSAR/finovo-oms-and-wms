<?php

$editBack = $editBack ?? 'list';
?>
<div class="modal-backdrop" id="customerEditModal" onclick="if (event.target === this) closeCustomerEdit();">
    <div class="modal-box" style="max-width:520px;">
        <div class="modal-header">
            <div>
                <h2>Edit Customer</h2>
                <p class="modal-help" style="margin:2px 0 0;">New orders from the platform can still fill in empty fields.</p>
            </div>
            <button type="button" class="modal-close" onclick="closeCustomerEdit()" aria-label="Close">&times;</button>
        </div>
        <form method="POST" action="/customers/update">
            <input type="hidden" name="id" id="ce_id">
            <input type="hidden" name="back" value="<?= htmlspecialchars($editBack) ?>">
            <div class="form-group">
                <label for="ce_name">Full Name *</label>
                <input required id="ce_name" type="text" name="name" maxlength="150">
            </div>
            <div class="ce-grid">
                <div class="form-group">
                    <label for="ce_email">Email</label>
                    <input id="ce_email" type="email" name="email" maxlength="150" placeholder="name@example.com">
                </div>
                <div class="form-group">
                    <label for="ce_phone">Phone</label>
                    <input id="ce_phone" type="text" name="phone" maxlength="50" placeholder="e.g. 0300-1234567">
                </div>
            </div>
            <div class="form-group">
                <label for="ce_address">Address</label>
                <input id="ce_address" type="text" name="address" maxlength="500" placeholder="House, street, area">
            </div>
            <div class="ce-grid">
                <div class="form-group">
                    <label for="ce_city">City</label>
                    <input id="ce_city" type="text" name="city" maxlength="100">
                </div>
                <div class="form-group">
                    <label for="ce_country">Country</label>
                    <input id="ce_country" type="text" name="country" maxlength="100">
                </div>
            </div>
            <div class="form-actions">
                <button type="button" class="btn-secondary" onclick="closeCustomerEdit()">Cancel</button>
                <button type="submit" class="btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<style>
.ce-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0 12px; }
@media (max-width: 560px) { .ce-grid { grid-template-columns: 1fr; } }
#customerEditModal .modal-box { max-height: 90vh; overflow-y: auto; }
/* Email field ko bhi baaki fields jaisa dikhao (layout ka style sirf type="text" pe lagta hai) */
#customerEditModal .form-group input[type="email"] {
    width: 100%; box-sizing: border-box; border: 1px solid var(--border-color); border-radius: 9px;
    padding: 9px 12px; font-size: 13.5px; font-family: 'Inter', sans-serif; background: #f8fafc;
}
</style>

<script>
function openCustomerEdit(c) {
    document.getElementById('ce_id').value = c.id;
    document.getElementById('ce_name').value = c.name || '';
    document.getElementById('ce_email').value = c.email || '';
    document.getElementById('ce_phone').value = c.phone || '';
    document.getElementById('ce_address').value = c.address || '';
    document.getElementById('ce_city').value = c.city || '';
    document.getElementById('ce_country').value = c.country || '';
    document.getElementById('customerEditModal').classList.add('show');
}
function closeCustomerEdit() {
    document.getElementById('customerEditModal').classList.remove('show');
}
</script>