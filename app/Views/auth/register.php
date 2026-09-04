<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register Your Company — Finovo OMS/WMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-8">
    <div class="bg-white rounded-lg shadow p-8 w-full max-w-md">
        <h1 class="text-xl font-bold mb-1 text-gray-800">Register Your Company</h1>
        <p class="text-sm text-gray-500 mb-6">Create your workspace on Finovo OMS/WMS.</p>

        <?php if (!empty($error)): ?>
            <div class="bg-red-100 text-red-700 px-4 py-2 rounded mb-4 text-sm">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="/register" class="space-y-4">
            <div>
                <label class="block text-sm font-medium mb-1">Company Name</label>
                <input type="text" name="company_name" required
                       value="<?= htmlspecialchars($_POST['company_name'] ?? '') ?>"
                       class="w-full border border-gray-300 rounded px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Your Name</label>
                <input type="text" name="admin_name" required
                       value="<?= htmlspecialchars($_POST['admin_name'] ?? '') ?>"
                       class="w-full border border-gray-300 rounded px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Email</label>
                <input type="email" name="email" required
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                       class="w-full border border-gray-300 rounded px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Password</label>
                <input type="password" name="password" required minlength="8"
                       class="w-full border border-gray-300 rounded px-3 py-2">
                <p class="text-xs text-gray-500 mt-1">At least 8 characters.</p>
            </div>
            <button type="submit"
                    class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700">
                Create Company
            </button>
        </form>
    </div>
</body>
</html>