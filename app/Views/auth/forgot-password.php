<?php ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finovo - Forgot Password</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-white">
<div class="min-h-screen lg:h-screen flex">

    <!-- Left panel: banner image (hidden on small screens) -->
    <div class="hidden lg:block h-full relative bg-black overflow-hidden lg:aspect-[696/1429]">
        <img src="/assets/images/LoginImg.png" alt="Finovo"
             class="absolute inset-0 w-full h-full object-cover">
    </div>

    <!-- Right panel: form -->
    <div class="w-full lg:flex-1 flex items-center justify-center px-4 py-8 md:px-8">
        <div class="w-full max-w-md mx-auto bg-white rounded-2xl border border-gray-200 shadow-xl p-7 sm:p-9 md:p-10">
            <div class="text-center mb-7">
                <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-gray-800 to-black flex items-center justify-center mx-auto mb-4">
                    <span class="text-white font-bold text-base">F</span>
                </div>
                <h2 class="text-3xl font-serif text-gray-900">Forgot password?</h2>
                <p class="text-sm text-gray-500 mt-2">Enter your email and we'll send you a reset code</p>
            </div>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="bg-red-50 border border-red-200 text-red-600 text-sm rounded-lg px-4 py-3 mb-5">
                    <?= htmlspecialchars($_SESSION['error']) ?>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg px-4 py-3 mb-5">
                    <?= htmlspecialchars($_SESSION['success']) ?>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <form method="POST" action="/forgot-password" class="space-y-5">
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-800 mb-2">Email</label>
                    <input type="email" name="email" id="email" placeholder="mark@example.com" required
                        class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm text-gray-800 bg-white transition focus:outline-none focus:border-gray-500 focus:ring-2 focus:ring-gray-200">
                </div>
                <button type="submit" class="w-full bg-black text-white font-semibold rounded-xl py-3 hover:bg-gray-800 active:scale-[0.99] transition duration-200">
                    Send Reset Code
                </button>
            </form>

            <div class="text-center mt-5">
                <a href="/login" class="text-sm text-gray-500 hover:text-black transition">&larr; Back to Login</a>
            </div>
        </div>
    </div>
</div>
</body>
</html>