
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
        Finovo - Verify OTP
    </title>

    <script
        src="https://cdn.tailwindcss.com"
    ></script>

</head>


<body class="bg-gray-50 min-h-screen">


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



        <!-- OTP CARD -->

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
                    Verify OTP
                </h2>


                <p
                    class="text-sm
                           text-gray-500
                           mt-2"
                >
                    Enter the 6-digit OTP sent to your email
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
                           text-center
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



            <!-- SUCCESS MESSAGE / TEMPORARY OTP -->

            <?php if (isset($_SESSION['success'])): ?>

                <div
                    class="bg-green-50
                           border
                           border-green-200
                           text-green-700
                           text-sm
                           text-center
                           rounded-lg
                           px-4
                           py-3
                           mb-5"
                >

                    <?= htmlspecialchars(
                        $_SESSION['success']
                    ) ?>

                </div>


                <?php
                    unset(
                        $_SESSION['success']
                    );
                ?>

            <?php endif; ?>



            <!-- OTP FORM -->

            <form
                method="POST"
                action="/finovo-oms-and-wms/public/otp"
            >


                <div class="mb-5">

                    <label
                        class="block
                               text-sm
                               font-medium
                               text-gray-700
                               mb-2"
                    >
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

                        autocomplete="one-time-code"

                        class="w-full
                               border
                               border-gray-300
                               rounded-xl
                               px-4
                               py-3
                               text-center
                               text-lg
                               tracking-[8px]
                               text-gray-800
                               transition
                               focus:outline-none
                               focus:border-gray-500
                               focus:ring-2
                               focus:ring-gray-200"

                    >

                </div>



                <!-- VERIFY BUTTON -->

                <button

                    type="submit"

                    class="w-full
                           bg-black
                           text-white
                           font-semibold
                           rounded-xl
                           py-3
                           hover:bg-gray-800
                           active:scale-[0.99]
                           transition
                           duration-200"

                >

                    Verify OTP

                </button>


            </form>



            <!-- BACK TO LOGIN -->

            <div
                class="text-center
                       mt-5"
            >

                <a

                    href="/finovo-oms-and-wms/public/login"

                    class="text-sm
                           text-gray-500
                           hover:text-black
                           transition"

                >

                    ← Back to Login

                </a>

            </div>


        </div>


    </div>


</div>


</body>

</html>
