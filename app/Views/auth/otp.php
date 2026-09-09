

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Finovo - Verify OTP</title>

    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-50 min-h-screen flex items-center justify-center">

    <div class="w-full max-w-sm bg-white shadow-md rounded-xl p-8">

        <!-- Logo / Title -->
        <div class="text-center mb-6">

            <h2 class="text-2xl font-bold text-gray-800">
                Verify OTP
            </h2>

            <p class="text-sm text-gray-500 mt-2">
                Enter the 6-digit OTP sent to your email
            </p>

        </div>


        <!-- Error Message -->
        <?php if (isset($_SESSION['error'])): ?>

            <div class="bg-red-50 border border-red-200 text-red-600
                        text-sm text-center rounded-lg px-3 py-2 mb-4">

                <?= htmlspecialchars($_SESSION['error']) ?>

            </div>

            <?php unset($_SESSION['error']); ?>

        <?php endif; ?>


        <!-- Success Message / Temporary OTP -->
        <?php if (isset($_SESSION['success'])): ?>

            <div class="bg-green-50 border border-green-200 text-green-700
                        text-sm text-center rounded-lg px-3 py-2 mb-4">

                <?= htmlspecialchars($_SESSION['success']) ?>

            </div>

            <?php unset($_SESSION['success']); ?>

        <?php endif; ?>


        <form method="POST" action="/finovo-oms-and-wms/otp">

            <div class="mb-5">

                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Enter OTP
                </label>

                <input
                    type="text"
                    name="otp"
                    maxlength="6"
                    minlength="6"
                    inputmode="numeric"
                    pattern="[0-9]{6}"
                    placeholder="Enter 6-digit OTP"
                    required
                    class="w-full border border-gray-300 rounded-lg
                           px-3 py-3 text-center text-lg tracking-[8px]
                           focus:outline-none focus:ring-2
                           focus:ring-black"
                >

            </div>


            <button
                type="submit"
                class="w-full bg-black text-white font-medium
                       rounded-lg py-3 hover:bg-gray-800
                       transition"
            >
                Verify OTP
            </button>

        </form>


        <div class="text-center mt-5">

            <a
                href="/login"
                class="text-sm text-gray-500 hover:text-black"
            >
                ← Back to Login
            </a>

        </div>

    </div>

</body>

</html> 
