
<?php
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <link
        rel="stylesheet"
        href="/finovo-oms-and-wms/public/css/style.css"
    >

    <title>
        Finovo - Login
    </title>

    <script
        src="https://cdn.tailwindcss.com"
    ></script>

    <script
        src="https://unpkg.com/lucide@latest"
    ></script>

</head>


<body class="min-h-screen bg-gray-50">


<div
    class="min-h-screen
           flex
           items-center
           justify-center
           px-4
           py-8
           md:px-8"
>


    <div
        class="w-full
               max-w-5xl
               grid
               md:grid-cols-2
               items-center
               gap-8
               md:gap-12"
    >


        <!-- LEFT IMAGE -->

        <div
            class="hidden
                   md:flex
                   items-center
                   justify-center"
        >

            <div
                class="w-full
                       max-w-md
                       overflow-hidden
                       rounded-3xl"
            >

                <img
                    src="/finovo-oms-and-wms/assets/images/LoginImg.png"
                    alt="Finovo"
                    class="w-full
                           h-[520px]
                           object-cover"
                >

            </div>

        </div>



        <!-- LOGIN CARD -->

        <div
            class="w-full
                   max-w-md
                   mx-auto
                   bg-white
                   rounded-2xl
                   border
                   border-gray-200
                   shadow-xl
                   p-7
                   sm:p-9
                   md:p-10"
        >


            <!-- HEADER -->

            <div
                class="text-center
                       mb-7"
            >

                <h2
                    class="text-3xl
                           font-bold
                           text-gray-900"
                >
                    Welcome Back
                </h2>


                <p
                    class="text-sm
                           text-gray-500
                           mt-2"
                >
                    Enter your credentials to login
                </p>

            </div>



            <!-- ERROR MESSAGE -->

            <?php if (isset($_SESSION['error'])): ?>

                <div
                    class="bg-red-50
                           border
                           border-red-200
                           text-red-600
                           text-sm
                           rounded-lg
                           px-4
                           py-3
                           mb-5"
                >

                    <?= htmlspecialchars(
                        $_SESSION['error']
                    ) ?>

                </div>


                <?php
                    unset(
                        $_SESSION['error']
                    );
                ?>

            <?php endif; ?>



            <!-- LOGIN FORM -->

            <form

                method="POST"

                action="/finovo-oms-and-wms/public/login"

                class="space-y-5"

                autocomplete="off"

            >


                <!-- HIDDEN FAKE FIELDS -->

                <div
                    style="
                        position: absolute;
                        left: -9999px;
                        width: 1px;
                        height: 1px;
                        overflow: hidden;
                    "
                    aria-hidden="true"
                >

                    <input
                        type="text"
                        name="fake_username"
                        autocomplete="username"
                        tabindex="-1"
                    >


                    <input
                        type="password"
                        name="fake_password"
                        autocomplete="current-password"
                        tabindex="-1"
                    >

                </div>



                <!-- EMAIL -->

                <div>

                    <label
                        for="email"
                        class="block
                               text-sm
                               font-medium
                               text-gray-700
                               mb-2"
                    >
                        Email
                    </label>


                    <div class="relative">

                        <input

                            type="email"

                            name="email"

                            id="email"

                            value=""

                            placeholder="mark@example.com"

                            required

                            autocomplete="off"

                            autocapitalize="none"

                            spellcheck="false"

                            class="w-full
                                   border
                                   border-gray-300
                                   rounded-xl
                                   px-4
                                   py-3
                                   text-sm
                                   text-gray-800
                                   bg-white
                                   transition
                                   focus:outline-none
                                   focus:border-gray-500
                                   focus:ring-2
                                   focus:ring-gray-200"

                        >

                    </div>

                </div>



                <!-- PASSWORD -->

                <div>


                    <div
                        class="flex
                               items-center
                               justify-between
                               mb-2"
                    >

                        <label
                            for="password"
                            class="block
                                   text-sm
                                   font-medium
                                   text-gray-700"
                        >
                            Password
                        </label>


                        <a
                            href="/finovo-oms-and-wms/forgot-password"
                            class="text-sm
                                   text-gray-500
                                   hover:text-black
                                   font-medium
                                   transition"
                        >
                            Forgot Password?
                        </a>

                    </div>



                    <div class="relative">


                        <input

                            type="password"

                            name="password"

                            id="password"

                            value=""

                            placeholder="Enter your password"

                            required

                            autocomplete="new-password"

                            class="w-full
                                   border
                                   border-gray-300
                                   rounded-xl
                                   px-4
                                   py-3
                                   pr-12
                                   text-sm
                                   text-gray-800
                                   bg-white
                                   transition
                                   focus:outline-none
                                   focus:border-gray-500
                                   focus:ring-2
                                   focus:ring-gray-200"

                        >



                        <button

                            type="button"

                            onclick="togglePassword()"

                            class="absolute
                                   right-3
                                   top-1/2
                                   -translate-y-1/2
                                   text-gray-400
                                   hover:text-gray-700
                                   transition"

                            aria-label="Show or hide password"

                        >

                            <i
                                data-lucide="eye-off"
                                id="eyeIcon"
                                class="w-5 h-5"
                            ></i>

                        </button>


                    </div>

                </div>



                <!-- LOGIN BUTTON -->

                <button

                    type="submit"

                    class="w-full
                           bg-black
                           text-white
                           font-semibold
                           rounded-xl
                           py-3
                           mt-2
                           hover:bg-gray-800
                           active:scale-[0.99]
                           transition
                           duration-200"

                >

                    Login

                </button>


            </form>


        </div>


    </div>


</div>



<script>


    <?php if (isset($_SESSION['login_alert'])): ?>

        alert(
            <?= json_encode(
                $_SESSION['login_alert']
            ) ?>
        );

        <?php
            unset(
                $_SESSION['login_alert']
            );
        ?>

    <?php endif; ?>


    lucide.createIcons();



    function clearLoginFields()
    {
        const email =
            document.getElementById(
                'email'
            );


        const password =
            document.getElementById(
                'password'
            );


        if (email) {
            email.value = '';
        }


        if (password) {
            password.value = '';
        }
    }



    document.addEventListener(
        'DOMContentLoaded',
        function ()
        {
            clearLoginFields();


            setTimeout(
                clearLoginFields,
                100
            );


            setTimeout(
                clearLoginFields,
                300
            );


            setTimeout(
                clearLoginFields,
                700
            );
        }
    );



    window.addEventListener(
        'pageshow',
        function ()
        {
            clearLoginFields();
        }
    );



    function togglePassword()
    {
        const password =
            document.getElementById(
                'password'
            );


        const eyeIcon =
            document.getElementById(
                'eyeIcon'
            );


        if (
            password.type ===
            'password'
        ) {

            password.type =
                'text';


            eyeIcon.setAttribute(
                'data-lucide',
                'eye'
            );

        } else {

            password.type =
                'password';


            eyeIcon.setAttribute(
                'data-lucide',
                'eye-off'
            );
        }


        lucide.createIcons();
    }


</script>


</body>

</html>
