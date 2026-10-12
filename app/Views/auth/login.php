<?php ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finovo - Log in</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        html, body { margin: 0; }
        body {
            font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
            background: #f3f6fc;
            color: #0f1730;
            -webkit-font-smoothing: antialiased;
        }
        a { text-decoration: none; }

        .auth-page { display: flex; min-height: 100vh; }

        /* ===== Left side (brand + illustration) ===== */
        .auth-hero {
            position: relative;
            flex: 1;
            min-width: 0;
            overflow: hidden;
            background:
                radial-gradient(ellipse 55% 50% at 70% 45%, rgba(225, 234, 253, 1) 0%, rgba(225, 234, 253, 0) 70%),
                linear-gradient(160deg, #f8faff 0%, #eff4fd 60%, #e8eefc 100%);
        }
        .auth-hero-art {
            position: absolute;
            left: max(34%, 430px);
            right: 0;
            bottom: 0;
            pointer-events: none;
        }
        .auth-hero-art img { display: block; width: 100%; height: auto; }
        .auth-hero-wave { position: absolute; left: 0; bottom: 0; width: 40.6%; max-width: 560px; height: auto; pointer-events: none; }

        .auth-hero-text { position: relative; z-index: 1; width: 450px; max-width: 100%; padding: 52px 70px 40px; display: flex; flex-direction: column; gap: 21px; }

        .brand { display: flex; align-items: center; gap: 14px; }
        .brand svg { width: 48px; height: 48px; flex-shrink: 0; }
        .brand-name { font-size: 22px; font-weight: 700; letter-spacing: -0.01em; color: #0f1730; line-height: 1.2; }
        .brand-sub { font-size: 14px; color: #8b93a7; }

        .hero-title {
            margin: 0;
            padding-top: 34px;
            font-size: 60px;
            font-weight: 800;
            line-height: 1.215;
            letter-spacing: -0.045em;
            text-transform: uppercase;
            color: #0f1730;
            white-space: nowrap;
        }
        .hero-title span { display: block; color: #1557e6; }

        .hero-copy { margin: 0; max-width: 310px; font-size: 19px; line-height: 28.5px; color: #4f5970; }

        .hero-points { list-style: none; margin: 0; padding: 8px 0 0; display: flex; flex-direction: column; gap: 22px; }
        .hero-points li { display: flex; align-items: center; gap: 20px; }
        .hero-point-icon {
            width: 58px; height: 58px; flex-shrink: 0; border-radius: 14px; background: #ffffff;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 12px 26px -16px rgba(21, 70, 180, 0.5);
        }
        .hero-points strong { display: block; font-size: 15px; font-weight: 600; color: #0f1730; margin-bottom: 5px; }
        .hero-points small { display: block; font-size: 13px; color: #5b6478; }

        /* ===== Right side (form card) ===== */
        .auth-side { width: 540px; flex-shrink: 0; display: flex; padding: 40px 26px 40px 0; }
        .auth-card {
            position: relative;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            background: #ffffff;
            border: 1px solid #eef1f6;
            border-radius: 24px;
            box-shadow: 0 26px 60px -38px rgba(20, 40, 90, 0.4);
            padding: 76px 67px 30px;
            overflow: hidden;
        }
        .auth-card-cube { position: absolute; top: 40px; right: 22px; width: 96px; height: 96px; }

        .auth-card h1 { margin: 38px 0 0; font-size: 38px; font-weight: 800; letter-spacing: -0.04em; color: #0f1730; line-height: 1.2; }
        .auth-card .lead { margin: 11px 0 0; font-size: 16px; line-height: 24.8px; color: #5b6478; }

        .auth-form { display: flex; flex-direction: column; gap: 18px; padding-top: 19px; }
        .field label { display: block; font-size: 14px; font-weight: 500; color: #1f2940; margin-bottom: 9px; }
        .input-wrap { position: relative; }
        .input-wrap > svg { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); pointer-events: none; }
        .input-wrap input {
            width: 100%; height: 52px; padding: 0 48px; border: 1px solid #dfe4ee; border-radius: 10px; background: #ffffff;
            font-family: inherit; font-size: 15px; color: #0f1730; outline: none; transition: border-color .15s, box-shadow .15s;
        }
        .input-wrap input::placeholder { color: #9aa2b4; }
        .input-wrap input:focus { border-color: #8fb4f8; box-shadow: 0 0 0 3px rgba(21, 87, 230, 0.12); }
        .eye-btn {
            position: absolute; right: 8px; top: 50%; transform: translateY(-50%); width: 36px; height: 36px; border: none;
            border-radius: 8px; background: transparent; cursor: pointer; display: flex; align-items: center; justify-content: center;
        }
        .eye-btn:hover { background: #f3f6fc; }

        .form-row { display: flex; align-items: center; justify-content: space-between; padding-bottom: 6px; }
        .check { display: inline-flex; align-items: center; gap: 10px; font-size: 14.5px; color: #1f2940; cursor: pointer; }
        .check input { width: 18px; height: 18px; margin: 0; border-radius: 4px; accent-color: #1557e6; cursor: pointer; }
        .link { font-size: 14.5px; font-weight: 500; color: #1557e6; }
        .link:hover { text-decoration: underline; }

        .btn-login {
            position: relative; width: 100%; height: 52px; border: none; border-radius: 10px; cursor: pointer;
            background: linear-gradient(180deg, #2266f0 0%, #1453dc 100%);
            box-shadow: 0 14px 26px -14px rgba(21, 87, 230, 0.85);
            color: #ffffff; font-family: inherit; font-size: 17px; font-weight: 600;
        }
        .btn-login svg { position: absolute; right: 18px; top: 15px; }
        .btn-login:hover { background: linear-gradient(180deg, #1d5de6 0%, #1149c9 100%); }

        .divider { display: flex; align-items: center; gap: 14px; margin-top: 18px; padding-top: 11px; font-size: 14px; color: #5b6478; }
        .divider::before, .divider::after { content: ''; flex: 1; height: 1px; background: #e3e8f1; }

        .sso-list { display: flex; flex-direction: column; gap: 12px; margin-top: 18px; padding-top: 5px; }
        .btn-sso {
            width: 100%; height: 50px; display: flex; align-items: center; justify-content: center; gap: 12px;
            border: 1px solid #d9dfe9; border-radius: 10px; background: #ffffff; cursor: pointer;
            font-family: inherit; font-size: 15.5px; font-weight: 500; color: #0f1730;
        }
        .btn-sso:hover { background: #f8faff; }

        .alert-error { margin-top: 18px; padding: 12px 14px; border-radius: 10px; background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; font-size: 14px; }
        .note { display: none; margin-top: 12px; padding: 10px 14px; border-radius: 10px; background: #eff4fd; border: 1px solid #d6e2f8; color: #1f2940; font-size: 13.5px; text-align: center; }
        .note.show { display: block; }

        /* ===== Smaller screens ===== */
        @media (max-width: 1500px) {
            .hero-title { font-size: 52px; }
            .hero-copy { font-size: 17px; line-height: 26px; }
        }
        @media (max-height: 820px) {
            .auth-card { padding-top: 40px; }
            .auth-card h1 { margin-top: 20px; font-size: 34px; }
            .auth-card-cube { width: 72px; height: 72px; top: 24px; }
            .hero-title { padding-top: 16px; font-size: 46px; }
            .hero-points { gap: 14px; }
        }
        @media (max-width: 1100px) {
            .auth-hero { display: none; }
            .auth-side { width: 100%; padding: 24px 16px; justify-content: center; }
            .auth-card { max-width: 540px; padding: 56px 28px 28px; }
            .auth-card-cube { display: none; }
            .auth-card h1 { margin-top: 0; }
        }
    </style>
</head>
<body>
<div class="auth-page">

    <!-- Left side: brand, heading and illustration -->
    <section class="auth-hero">
        <svg class="auth-hero-wave" viewBox="0 0 560 240" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <path opacity="0.8" d="M0 20C120 40 250 110 360 170C420 205 470 225 560 240H0V20Z" fill="#CFDEFC"/>
            <path d="M0 70C130 80 240 150 330 200C370 222 410 234 450 240H0V70Z" fill="url(#waveGradient)"/>
            <defs>
                <linearGradient id="waveGradient" x1="0" y1="70" x2="112.403" y2="367.537" gradientUnits="userSpaceOnUse">
                    <stop stop-color="#6EA4FF"/>
                    <stop offset="1" stop-color="#1557E6"/>
                </linearGradient>
            </defs>
        </svg>

        <div class="auth-hero-art">
            <img src="/assets/images/login-illustration.webp" alt="Finovo warehouse, stores and shipments overview">
        </div>

        <div class="auth-hero-text">
            <div class="brand">
                <svg viewBox="0 0 49 49" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path d="M0 41.7273C0.0385166 30.1387 0.0684739 18.5545 0.11127 6.96595C0.11555 5.25119 0.278175 3.65616 1.56634 2.23646C3.17976 0.457555 5.1441 0.115458 7.33099 0.119734C16.0828 0.132563 24.8303 0.136839 33.5822 0.132563C34.4338 0.132563 35.2855 0.0769718 36.1371 0.0470383C36.702 0.0470383 37.2669 0.0470383 37.8318 0.0470383C39.0387 0.0299335 40.2455 0.0171048 41.4524 0C41.7656 0 42.079 0.0267063 42.3857 0.0901868C46.7155 0.986401 48.2749 2.85623 48.2998 7.5133C48.364 18.8324 48.3597 30.1516 48.2998 41.4707C48.2784 46.0035 45.9117 48.2742 41.3796 48.2827C30.0515 48.3084 18.7276 48.2528 7.39946 48.3426C4.9385 48.3625 2.96982 47.6186 1.31588 45.975C1.07819 45.7388 0.888045 45.4602 0.730791 45.1643C0.560837 44.8445 0.436134 44.5027 0.358341 44.149C0.309717 43.9279 0.260879 43.7069 0.212112 43.4858C0.084702 42.9082 0.0169054 42.3185 0 41.7273ZM42.5308 23.8805C42.5308 23.8746 42.5356 23.8698 42.5415 23.8698C42.5474 23.8698 42.5522 23.865 42.5522 23.8591C42.5523 21.2285 42.5693 18.5937 42.548 15.9631C42.5266 13.1536 41.3497 12.4652 38.9616 13.8763C35.4053 15.9759 31.9816 18.3449 28.284 20.1623C25.5664 21.4965 25.3567 23.455 25.6135 25.9267C25.7804 27.5302 26.4181 27.9408 27.8346 27.0641C29.953 25.7556 32.0843 24.4599 34.2412 23.2198C36.1328 22.1294 36.8732 22.5356 36.9459 24.6695C36.993 26.0207 36.9074 27.3763 36.9631 28.7276C37.0401 30.7246 36.1799 32.1058 34.4638 33.0893C31.682 34.6801 28.9987 36.4461 26.1613 37.9343C23.6192 39.2684 22.5407 41.1885 22.8232 44.0022C23.0029 45.8068 23.5122 46.136 25.1128 45.191C30.1413 42.219 35.1399 39.2 40.1642 36.2195C41.7862 35.2574 42.5651 33.8932 42.5308 31.9946C42.4966 29.2871 42.5308 26.5838 42.5308 23.8805ZM11.3196 29.0889C11.3196 29.0854 11.3225 29.0825 11.326 29.0825C11.3296 29.0825 11.3324 29.0796 11.3324 29.0761C11.3328 26.6579 11.5248 24.2183 11.2854 21.8215C10.9301 18.2509 12.4965 16.1555 15.5393 14.5006C20.3539 11.8836 24.9972 8.95011 29.692 6.11926C30.1199 5.86269 31.1428 5.76006 30.4152 4.80646C28.9131 2.85651 25.4295 2.13811 23.2383 3.42952C18.2098 6.40577 13.2283 9.4547 8.20831 12.4395C6.50502 13.453 5.74753 14.8727 5.76465 16.8483C5.80744 22.1893 5.82028 27.5345 5.75609 32.8755C5.72185 35.7534 6.93726 37.7376 9.44084 39.0974C10.7718 39.8201 11.4266 39.9869 11.3495 38.054C11.2341 35.0756 11.3195 32.0801 11.3196 29.0889ZM14.3538 27.2395C14.3538 29.3733 14.3795 31.5071 14.341 33.641C14.3196 34.7186 14.7005 34.8426 15.5992 34.4064C18.4238 33.0252 20.4609 31.195 19.9131 27.6457C19.2797 23.5576 20.662 20.5814 24.6891 19.0078C27.9288 17.7463 28.7505 15.1634 28.4552 11.9221C28.3353 10.6007 28.0358 10.481 26.9744 11.1224C23.632 13.1579 20.2854 15.1848 16.8917 17.1348C15.0043 18.2209 14.2383 19.7732 14.3282 21.8985C14.4052 23.6774 14.3496 25.4606 14.3538 27.2395Z" fill="url(#logoGradient)"/>
                    <defs>
                        <linearGradient id="logoGradient" x1="13.5763" y1="-4.94104" x2="34.7674" y2="53.2849" gradientUnits="userSpaceOnUse">
                            <stop stop-color="#2F7BFF"/>
                            <stop offset="1" stop-color="#1250DB"/>
                        </linearGradient>
                    </defs>
                </svg>
                <div>
                    <div class="brand-name">Finovo</div>
                    <div class="brand-sub">OMS / WMS</div>
                </div>
            </div>

            <h2 class="hero-title">Log in to<br>keep your<span>operations<br>moving.</span></h2>

            <p class="hero-copy">Access your workspace and manage orders, inventory, stores and warehouses — all in one place.</p>

            <ul class="hero-points">
                <li>
                    <div class="hero-point-icon">
                        <svg width="26" height="26" viewBox="0 0 26 26" fill="none" aria-hidden="true"><path d="M13 2.70825L22.75 7.58325V18.4166L13 23.2916L3.25 18.4166V7.58325L13 2.70825Z" stroke="#1557E6" stroke-width="2.05833" stroke-linejoin="round"/><path d="M3.25 7.58325L13 12.4583M13 12.4583L22.75 7.58325M13 12.4583V23.2916" stroke="#1557E6" stroke-width="2.05833" stroke-linejoin="round"/></svg>
                    </div>
                    <div><strong>Real-Time Control</strong><small>Stay updated across all locations</small></div>
                </li>
                <li>
                    <div class="hero-point-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6.3 13H4.2C3.53726 13 3 13.5373 3 14.2V19.8C3 20.4627 3.53726 21 4.2 21H6.3C6.96274 21 7.5 20.4627 7.5 19.8V14.2C7.5 13.5373 6.96274 13 6.3 13Z" fill="#1557E6"/><path d="M13.05 8H10.95C10.2873 8 9.75 8.53726 9.75 9.2V19.8C9.75 20.4627 10.2873 21 10.95 21H13.05C13.7127 21 14.25 20.4627 14.25 19.8V9.2C14.25 8.53726 13.7127 8 13.05 8Z" fill="#1557E6"/><path d="M19.8 3H17.7C17.0373 3 16.5 3.53726 16.5 4.2V19.8C16.5 20.4627 17.0373 21 17.7 21H19.8C20.4627 21 21 20.4627 21 19.8V4.2C21 3.53726 20.4627 3 19.8 3Z" fill="#1557E6"/></svg>
                    </div>
                    <div><strong>Smarter Fulfillment</strong><small>From orders to delivery</small></div>
                </li>
                <li>
                    <div class="hero-point-icon">
                        <svg width="26" height="26" viewBox="0 0 26 26" fill="none" aria-hidden="true"><path d="M12.9999 8.99172C14.6752 8.99172 16.0332 7.63365 16.0332 5.95838C16.0332 4.28312 14.6752 2.92505 12.9999 2.92505C11.3246 2.92505 9.96655 4.28312 9.96655 5.95838C9.96655 7.63365 11.3246 8.99172 12.9999 8.99172Z" stroke="#1557E6" stroke-width="2.05833" stroke-linecap="round"/><path d="M5.95838 22.5332C7.63365 22.5332 8.99172 21.1752 8.99172 19.4999C8.99172 17.8246 7.63365 16.4666 5.95838 16.4666C4.28312 16.4666 2.92505 17.8246 2.92505 19.4999C2.92505 21.1752 4.28312 22.5332 5.95838 22.5332Z" stroke="#1557E6" stroke-width="2.05833" stroke-linecap="round"/><path d="M20.0416 22.5332C21.7169 22.5332 23.075 21.1752 23.075 19.4999C23.075 17.8246 21.7169 16.4666 20.0416 16.4666C18.3664 16.4666 17.0083 17.8246 17.0083 19.4999C17.0083 21.1752 18.3664 22.5332 20.0416 22.5332Z" stroke="#1557E6" stroke-width="2.05833" stroke-linecap="round"/><path d="M11.4833 8.66675L7.58325 16.7917M14.5166 8.66675L18.4166 16.7917M8.99159 19.5001H17.0083" stroke="#1557E6" stroke-width="2.05833" stroke-linecap="round"/></svg>
                    </div>
                    <div><strong>One Unified Platform</strong><small>Stores, inventory and warehouses</small></div>
                </li>
            </ul>
        </div>
    </section>

    <!-- Right side: login form -->
    <section class="auth-side">
        <div class="auth-card">
            <svg class="auth-card-cube" viewBox="0 0 96 96" fill="none" aria-hidden="true">
                <g opacity="0.6">
                    <path d="M48 9.59998L86.4 30.72V67.2L48 88.32L9.59998 67.2V30.72L48 9.59998Z" fill="#EEF3FD"/>
                    <path d="M48 49.92L86.4 30.72V67.2L48 88.32V49.92Z" fill="#DDE7FB"/>
                    <path d="M9.59998 30.72L48 49.92V88.32L9.59998 67.2V30.72Z" fill="#E8EFFD"/>
                    <path d="M59.52 59.52L72.96 52.8V72.96L59.52 79.68V59.52Z" fill="#C7D7F7"/>
                </g>
            </svg>

            <div>
                <h1>Welcome back</h1>
                <p class="lead">Log in to access your workspace and continue managing your operations.</p>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert-error"><?= htmlspecialchars($_SESSION['error']) ?></div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>

                <form method="POST" action="/login" class="auth-form" autocomplete="off">
                    <div class="field">
                        <label for="email">Work email</label>
                        <div class="input-wrap">
                            <svg width="18" height="18" viewBox="0 0 18 18" fill="none" aria-hidden="true"><path d="M13.875 3.75H4.125C3.08947 3.75 2.25 4.58947 2.25 5.625V12.375C2.25 13.4105 3.08947 14.25 4.125 14.25H13.875C14.9105 14.25 15.75 13.4105 15.75 12.375V5.625C15.75 4.58947 14.9105 3.75 13.875 3.75Z" stroke="#6B7489" stroke-width="1.35" stroke-linecap="round" stroke-linejoin="round"/><path d="M2.625 4.875L9 9.75L15.375 4.875" stroke="#6B7489" stroke-width="1.35" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            <input type="email" name="email" id="email" placeholder="you@company.com" required autocomplete="off">
                        </div>
                    </div>

                    <div class="field">
                        <label for="password">Password</label>
                        <div class="input-wrap">
                            <svg width="18" height="18" viewBox="0 0 18 18" fill="none" aria-hidden="true"><path d="M13.125 7.5H4.875C3.83947 7.5 3 8.33947 3 9.375V13.875C3 14.9105 3.83947 15.75 4.875 15.75H13.125C14.1605 15.75 15 14.9105 15 13.875V9.375C15 8.33947 14.1605 7.5 13.125 7.5Z" stroke="#6B7489" stroke-width="1.35" stroke-linecap="round" stroke-linejoin="round"/><path d="M6 7.5V5.25C6 4.45435 6.31607 3.69129 6.87868 3.12868C7.44129 2.56607 8.20435 2.25 9 2.25C9.79565 2.25 10.5587 2.56607 11.1213 3.12868C11.6839 3.69129 12 4.45435 12 5.25V7.5" stroke="#6B7489" stroke-width="1.35" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            <input type="password" name="password" id="password" placeholder="Enter your password" required autocomplete="new-password">
                            <button type="button" class="eye-btn" id="togglePassword" aria-label="Show password">
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M1.66669 10.0001C1.66669 10.0001 4.66669 4.16675 10 4.16675C15.3334 4.16675 18.3334 10.0001 18.3334 10.0001C18.3334 10.0001 15.3334 15.8334 10 15.8334C4.66669 15.8334 1.66669 10.0001 1.66669 10.0001Z" stroke="#5B6478" stroke-width="1.41667" stroke-linecap="round" stroke-linejoin="round"/><path d="M10 12.5C11.3807 12.5 12.5 11.3807 12.5 10C12.5 8.61929 11.3807 7.5 10 7.5C8.61929 7.5 7.5 8.61929 7.5 10C7.5 11.3807 8.61929 12.5 10 12.5Z" stroke="#5B6478" stroke-width="1.41667" stroke-linecap="round" stroke-linejoin="round"/><line id="eyeSlash" x1="3" y1="3" x2="17" y2="17" stroke="#5B6478" stroke-width="1.41667" stroke-linecap="round" style="display:none"/></svg>
                            </button>
                        </div>
                    </div>

                    <div class="form-row">
                        <label class="check"><input type="checkbox" name="remember" value="1"> Keep me signed in</label>
                        <a href="/forgot-password" id="forgotPasswordLink" class="link">Forgot password?</a>
                    </div>

                    <button type="submit" class="btn-login">
                        Log in
                        <svg width="22" height="22" viewBox="0 0 22 22" fill="none" aria-hidden="true"><path d="M4.58331 11H17.4166M11.9166 16.5L17.4166 11L11.9166 5.5" stroke="white" stroke-width="1.83333" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                </form>

                <div class="divider">Or continue with</div>

                <div class="sso-list">
                    <button type="button" class="btn-sso" data-soon="Microsoft sign-in">
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M7.91667 2.5H3.75C3.05964 2.5 2.5 3.05964 2.5 3.75V7.91667C2.5 8.60702 3.05964 9.16667 3.75 9.16667H7.91667C8.60702 9.16667 9.16667 8.60702 9.16667 7.91667V3.75C9.16667 3.05964 8.60702 2.5 7.91667 2.5Z" stroke="#1557E6" stroke-width="1.58333" stroke-linecap="round" stroke-linejoin="round"/><path d="M16.25 2.5H12.0834C11.393 2.5 10.8334 3.05964 10.8334 3.75V7.91667C10.8334 8.60702 11.393 9.16667 12.0834 9.16667H16.25C16.9404 9.16667 17.5 8.60702 17.5 7.91667V3.75C17.5 3.05964 16.9404 2.5 16.25 2.5Z" stroke="#1557E6" stroke-width="1.58333" stroke-linecap="round" stroke-linejoin="round"/><path d="M7.91667 10.8333H3.75C3.05964 10.8333 2.5 11.3929 2.5 12.0833V16.2499C2.5 16.9403 3.05964 17.4999 3.75 17.4999H7.91667C8.60702 17.4999 9.16667 16.9403 9.16667 16.2499V12.0833C9.16667 11.3929 8.60702 10.8333 7.91667 10.8333Z" stroke="#1557E6" stroke-width="1.58333" stroke-linecap="round" stroke-linejoin="round"/><path d="M16.25 10.8333H12.0834C11.393 10.8333 10.8334 11.3929 10.8334 12.0833V16.2499C10.8334 16.9403 11.393 17.4999 12.0834 17.4999H16.25C16.9404 17.4999 17.5 16.9403 17.5 16.2499V12.0833C17.5 11.3929 16.9404 10.8333 16.25 10.8333Z" stroke="#1557E6" stroke-width="1.58333" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        Sign in with Microsoft
                    </button>
                    <button type="button" class="btn-sso" data-soon="SSO sign-in">
                        <svg width="18" height="20" viewBox="0 0 18 20" fill="none" aria-hidden="true"><path d="M13.5834 8.33325H4.41671C3.26611 8.33325 2.33337 9.26599 2.33337 10.4166V17.0833C2.33337 18.2338 3.26611 19.1666 4.41671 19.1666H13.5834C14.734 19.1666 15.6667 18.2338 15.6667 17.0833V10.4166C15.6667 9.26599 14.734 8.33325 13.5834 8.33325Z" fill="#0F1730"/><path d="M5.25 8.33325V5.83325C5.25 4.83869 5.64509 3.88486 6.34835 3.1816C7.05161 2.47834 8.00544 2.08325 9 2.08325C9.99456 2.08325 10.9484 2.47834 11.6516 3.1816C12.3549 3.88486 12.75 4.83869 12.75 5.83325V8.33325" stroke="#0F1730" stroke-width="2"/></svg>
                        Sign in with SSO
                    </button>
                </div>
                <div class="note" id="soonNote"></div>
            </div>

        </div>
    </section>
</div>

<script>
    <?php if (isset($_SESSION['login_alert'])): ?>
        alert(<?= json_encode($_SESSION['login_alert']) ?>);
        <?php unset($_SESSION['login_alert']); ?>
    <?php endif; ?>

    // Password dikhao / chhupao
    document.getElementById('togglePassword').addEventListener('click', function () {
        var input = document.getElementById('password');
        var slash = document.getElementById('eyeSlash');
        var show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        slash.style.display = show ? 'inline' : 'none';
        this.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
    });

    // Forgot password: likha hua email saath le jao
    var forgotLink = document.getElementById('forgotPasswordLink');
    var emailInput = document.getElementById('email');
    forgotLink.addEventListener('click', function () {
        var email = emailInput.value.trim();
        if (email) {
            forgotLink.href = '/forgot-password?email=' + encodeURIComponent(email);
        }
    });

    // Microsoft / SSO abhi backend mein nahi — user ko seedha bata do
    var note = document.getElementById('soonNote');
    function showNote(text) {
        note.textContent = text;
        note.classList.add('show');
    }
    document.querySelectorAll('[data-soon]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            showNote(btn.getAttribute('data-soon') + ' is coming soon. Please log in with your work email for now.');
        });
    });
</script>
</body>
</html>