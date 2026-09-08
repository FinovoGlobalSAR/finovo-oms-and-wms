<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= $title ?? 'Finovo OMS/WMS' ?></title>

    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-50 text-gray-800">

    <?php require __DIR__ . '/sidebar.php'; ?>

    <div class="lg:ml-64 min-h-screen">

        <?php require __DIR__ . '/header.php'; ?>

        <main class="p-4 sm:p-6">
            <?= $content ?? '' ?>
        </main>

    </div>

</body>
</html>