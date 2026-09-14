<?php ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finovo - Reset Password</title>
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

            <?php if (empty($otpVerified)): ?>

                <div class="text-center mb-7">
                    <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-gray-800 to-black flex items-center justify-center mx-auto mb-4">
                        <span class="text-white font-bold text-base">F</span>
                    </div>
                    <h2 class="text-3xl font-serif text-gray-900">Enter reset code</h2>
                    <p class="text-sm text-gray-500 mt-2">Code sent to <?= htmlspecialchars($_SESSION['reset_email'] ?? 'your email') ?></p>
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

                <form method="POST" action="/reset-password/verify" class="space-y-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-800 mb-2">Reset Code</label>
                        <input type="text" name="otp" maxlength="6" inputmode="numeric" pattern="[0-9]{6}" placeholder="6-digit code" required
                            class="w-full border border-gray-300 rounded-xl px-4 py-3 text-center text-lg tracking-[8px] text-gray-800 transition focus:outline-none focus:border-gray-500 focus:ring-2 focus:ring-gray-200">
                    </div>
                    <button type="submit" class="w-full bg-black text-white font-semibold rounded-xl py-3 hover:bg-gray-800 active:scale-[0.99] transition duration-200">
                        Verify Code
                    </button>
                </form>

                <div class="text-center mt-5">
                    <a href="/forgot-password" class="text-sm text-gray-500 hover:text-black transition">Resend code</a>
                </div>

            <?php else: ?>

                <div class="text-center mb-7">
                    <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-gray-800 to-black flex items-center justify-center mx-auto mb-4">
                        <span class="text-white font-bold text-base">F</span>
                    </div>
                    <h2 class="text-3xl font-serif text-gray-900">Set new password</h2>
                    <p class="text-sm text-gray-500 mt-2">Code verified — choose a new password</p>
                </div>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="bg-red-50 border border-red-200 text-red-600 text-sm rounded-lg px-4 py-3 mb-5">
                        <?= htmlspecialchars($_SESSION['error']) ?>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>

                <form method="POST" action="/reset-password/set" class="space-y-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-800 mb-2">New Password</label>
                        <input type="password" name="password" minlength="6" required
                            class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm text-gray-800 bg-white transition focus:outline-none focus:border-gray-500 focus:ring-2 focus:ring-gray-200">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-800 mb-2">Confirm New Password</label>
                        <input type="password" name="confirm_password" minlength="6" required
                            class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm text-gray-800 bg-white transition focus:outline-none focus:border-gray-500 focus:ring-2 focus:ring-gray-200">
                    </div>
                    <button type="submit" class="w-full bg-black text-white font-semibold rounded-xl py-3 hover:bg-gray-800 active:scale-[0.99] transition duration-200">
                        Reset Password
                    </button>
                </form>

            <?php endif; ?>

        </div>
    </div>
</div>
</body>
</html>