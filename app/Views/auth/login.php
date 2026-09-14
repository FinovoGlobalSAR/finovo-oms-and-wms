<?php ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finovo - Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="min-h-screen bg-white">
<div class="min-h-screen lg:h-screen flex">

    <!-- Left panel: banner image (hidden on small screens) -->
    <div class="hidden lg:block h-full relative bg-black overflow-hidden lg:aspect-[696/1429]">
        <img src="/assets/images/LoginImg.png" alt="Finovo"
             class="absolute inset-0 w-full h-full object-cover">
    </div>

    <!-- Right panel: login form -->
    <div class="w-full lg:flex-1 flex items-center justify-center px-4 py-8 md:px-8">
        <div class="w-full max-w-md mx-auto bg-white rounded-2xl border border-gray-200 shadow-xl p-7 sm:p-9 md:p-10">
            <div class="text-center mb-7">
                <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-gray-800 to-black flex items-center justify-center mx-auto mb-4">
                    <span class="text-white font-bold text-base">F</span>
                </div>
                <h2 class="text-3xl font-serif text-gray-900">Welcome back</h2>
                <p class="text-sm text-gray-500 mt-2">Enter your email below to login to your account</p>
            </div>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="bg-red-50 border border-red-200 text-red-600 text-sm rounded-lg px-4 py-3 mb-5">
                    <?= htmlspecialchars($_SESSION['error']) ?>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <form method="POST" action="/login" class="space-y-5" autocomplete="off">
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-800 mb-2">Email</label>
                    <input type="email" name="email" id="email" placeholder="mark@example.com" required autocomplete="off"
                        class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm text-gray-800 bg-white transition focus:outline-none focus:border-gray-500 focus:ring-2 focus:ring-gray-200">
                </div>
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label for="password" class="block text-sm font-medium text-gray-800">Password</label>
                        <a href="/forgot-password" class="text-sm text-gray-500 hover:text-black transition">Forgot password?</a>
                    </div>
                    <div class="relative">
                        <input type="password" name="password" id="password" placeholder="Password" required autocomplete="new-password"
                            class="w-full border border-gray-300 rounded-xl px-4 py-3 pr-12 text-sm text-gray-800 bg-white transition focus:outline-none focus:border-gray-500 focus:ring-2 focus:ring-gray-200">
                        <button type="button" onclick="togglePassword()" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-700 transition">
                            <i data-lucide="eye-off" id="eyeIcon" class="w-5 h-5"></i>
                        </button>
                    </div>
                </div>
                <button type="submit" class="w-full bg-black text-white font-semibold rounded-xl py-3 mt-2 hover:bg-gray-800 active:scale-[0.99] transition duration-200">
                    Login
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    <?php if (isset($_SESSION['login_alert'])): ?>
        alert(<?= json_encode($_SESSION['login_alert']) ?>);
        <?php unset($_SESSION['login_alert']); ?>
    <?php endif; ?>

    lucide.createIcons();

    function togglePassword() {
        const password = document.getElementById('password');
        const eyeIcon = document.getElementById('eyeIcon');
        if (password.type === 'password') {
            password.type = 'text';
            eyeIcon.setAttribute('data-lucide', 'eye');
        } else {
            password.type = 'password';
            eyeIcon.setAttribute('data-lucide', 'eye-off');
        }
        lucide.createIcons();
    }
</script>
</body>
</html>