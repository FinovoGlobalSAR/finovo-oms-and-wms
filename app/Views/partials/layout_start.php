<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finovo OMS</title>
    <?php include __DIR__ . '/theme_header.php'; ?>
</head>
<body>
<div class="app-shell">

    <aside class="sidebar">
        <div class="sidebar-company">
            <span class="logo-sq"></span>
            <span class="name">Finovo</span>
            <i class="bi bi-chevron-down chev"></i>
        </div>
        <div class="sidebar-search">
            <i class="bi bi-search"></i> Quick actions
            <span class="kbd">Ctrl K</span>
        </div>

        <div class="sidebar-section-title">General</div>
        <a href="/orders" class="sidebar-link <?= ($currentPage ?? '') === 'orders' ? 'active' : '' ?>"><i class="bi bi-box-seam"></i> Orders</a>
        <a href="/products" class="sidebar-link <?= ($currentPage ?? '') === 'products' ? 'active' : '' ?>"><i class="bi bi-tag"></i> Products</a>
    </aside>

    <main class="main-content">