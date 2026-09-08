
<?php
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Finovo - Login</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body>
<div class=" flex items-center justify-center p-6 ">

    <div class="w-full max-w-5xl items-center rounded-2xl overflow-hidden grid md:grid-cols-2 gap-10">
 <div class="hidden md:block relative min-h-[500px]">

          <img
    src="/finovo-oms-and-wms/assets/images/LoginImg.png"
    alt="Finovo"
    class="w-full  object-cover"
>
           

        </div>
       <div class="p-8 md:p-10 bg-white rounded-2xl border border-gray-200 shadow-lg">

            <div class="text-center mb-6">
                <h2 class="text-2xl font-bold text-gray-800">
                    Welcome Back
                </h2>

                <p class="text-sm text-gray-500 mt-1">
                    Enter your credentials to login
                </p>
            </div>

            <?php if (isset($_SESSION['error'])): ?>

                <div class="bg-red-50 border border-red-200 text-red-600
                            text-sm text-center rounded-lg px-3 py-2 mb-4">

                    <?= htmlspecialchars($_SESSION['error']) ?>

                </div>

                <?php unset($_SESSION['error']); ?>

            <?php endif; ?>

            <form
                method="POST"
                action="/finovo-oms-and-wms/login"
                class="space-y-4"
            >

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Email
                    </label>

                    <input
                        type="email"
                        name="email"
                        placeholder="mark@example.com"
                        required
                        class="w-full border border-gray-300 rounded-lg
                               px-3 py-2 text-sm
                               focus:outline-none focus:ring-2
                               focus:ring-black"
                    >
                </div>

            <div>
    <div class="flex items-center justify-between mb-1">

        <label class="block text-sm font-medium text-gray-700">
            Password
        </label>

        <a
            href="/finovo-oms-and-wms/forgot-password "
            class="text-sm text-gray-500 hover:text-black font-bold"
        >
            Forgot Password?
        </a>

    </div>

    <div class="relative">

        <input
            type="password"
            name="password"
            id="password"
            placeholder="Enter your password"
            required
            class="w-full border border-gray-300 rounded-lg
                   px-3 py-2 pr-10 text-sm
                   focus:outline-none focus:ring-2
                   focus:ring-black"
        >

        <button
            type="button"
            onclick="togglePassword()"
            class="absolute right-3 top-1/2 -translate-y-1/2
                   text-gray-500 hover:text-black"
        >
            <i
                data-lucide="eye-off"
                id="eyeIcon"
                class="w-5 h-5"
            ></i>
        </button>

    </div>
</div>


                <!-- Role -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Role
                    </label>

                    <select
                        name="role"
                        required
                        class="w-full border border-gray-300 rounded-lg
                               px-3 py-2 text-sm bg-white
                               focus:outline-none focus:ring-2
                               focus:ring-black"
                    >
                        <option value="">
                            Select your role
                        </option>

                        <option value="admin">
                            Admin
                        </option>

                        <option value="manager">
                            Manager
                        </option>

                        <option value="warehouse">
                            Warehouse
                        </option>
                    </select>
                </div>

                <!-- Login Button -->
                <button
                    type="submit"
                    class="w-full bg-black text-white font-medium
                           rounded-lg py-2
                           hover:bg-gray-800 transition"
                >
                    Login
                </button>

            </form>

        </div>


       

    </div>
            </div>
</body>

<script>
    lucide.createIcons();

    function togglePassword() {
        const password = document.getElementById("password");
        const eyeIcon = document.getElementById("eyeIcon");

        if (password.type === "password") {
            password.type = "text";
            eyeIcon.setAttribute("data-lucide", "eye");
        } else {
            password.type = "password";
            eyeIcon.setAttribute("data-lucide", "eye-off");
        }

        lucide.createIcons();
    }
</script>
</html>
