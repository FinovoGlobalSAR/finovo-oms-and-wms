<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= $title ?? 'Finovo OMS/WMS' ?></title>

    <script src="https://cdn.tailwindcss.com"></script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link
    rel="stylesheet"
    href="/finovo-oms-and-wms/public/css/style.css"
>
    <style>
    :root {
      --bg-page: #ffffff;
      --bg-white: #ffffff;
      --bg-hover: #f5f5f5;
      --border-color: #e5e7eb;
      --text-dark: #111827;
      --text-muted: #6b7280;
      --text-light: #9ca3af;
      --blue-link: #2563eb;
      --btn-dark-bg: #111111;
      --btn-dark-text: #ffffff;
      --green: #16a34a;
      --red: #dc2626;
    }

    .breadcrumb { font-size: 13px; color: var(--text-muted); margin-bottom: 8px; font-family: 'Inter', sans-serif; }
    .breadcrumb i { font-size: 11px; vertical-align: -1px; margin: 0 2px; }

    .page-header-row { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 4px; }
    .page-header-row h1 {
      font-size: 22px; font-weight: 600; margin: 0; display: flex; align-items: center; gap: 8px; color: var(--text-dark);
      font-family: 'Inter', sans-serif;
    }
    .page-header-row .count-badge { font-size: 14px; font-weight: 400; color: var(--text-muted); }

    .page-subtitle { margin: 0 0 16px; color: var(--text-muted); font-size: 14px; font-family: 'Inter', sans-serif; }

    .banner { padding: 10px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 14px; font-family: 'Inter', sans-serif; }
    .banner-success { background: #dcfce7; color: #166534; }
    .banner-error { background: #fee2e2; color: #991b1b; }

    .card { background: var(--bg-white); border: 1px solid var(--border-color); border-radius: 12px; overflow: hidden; font-family: 'Inter', sans-serif; }

    .filter-row { display: flex; align-items: center; gap: 8px; padding: 10px 18px; border-bottom: 1px solid var(--border-color); flex-wrap: wrap; }
    .filter-row .push-right { margin-left: auto; display: flex; gap: 8px; }

    .filter-btn, .toolbar-btn {
      border: 1px solid var(--border-color); background: var(--bg-white); padding: 6px 12px; border-radius: 8px;
      font-size: 13px; font-weight: 500; cursor: pointer; color: var(--text-dark); font-family: 'Inter', sans-serif;
      text-decoration: none; display: inline-flex; align-items: center; gap: 6px;
    }
    .filter-btn:hover, .toolbar-btn:hover { background: var(--bg-hover); }

    .btn-dark { background: var(--btn-dark-bg) !important; color: var(--btn-dark-text) !important; border: 1px solid var(--btn-dark-bg) !important; }
    .btn-dark:hover { opacity: 0.88; }

    table.data-table { width: 100%; border-collapse: collapse; }
    table.data-table th {
      text-align: left; font-size: 12px; color: var(--text-muted); font-weight: 600; padding: 9px 18px;
      border-bottom: 1px solid var(--border-color); background: var(--bg-white);
    }
    table.data-table th.checkbox-col, table.data-table td.checkbox-col { width: 20px; }
    table.data-table td { padding: 9px 18px; border-bottom: 1px solid var(--border-color); font-size: 13px; color: var(--text-dark); }
    table.data-table tr:last-child td { border-bottom: none; }
    table.data-table tr:hover td { background: #fafafa; }

    .avatar-circle {
      width: 24px; height: 24px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center;
      font-size: 10px; font-weight: 600; margin-right: 8px; vertical-align: middle; flex-shrink: 0;
    }
    .name-cell { display: flex; align-items: center; }

    .link-blue { color: var(--blue-link); text-decoration: none; font-weight: 500; }
    .link-blue:hover { text-decoration: underline; }

    .source-badge { font-size: 12px; font-weight: 500; padding: 3px 10px; border-radius: 20px; display: inline-block; }

    .action-link {
      color: var(--text-muted); text-decoration: none; font-size: 13px; margin-right: 14px;
      display: inline-flex; align-items: center; gap: 4px;
    }
    .action-link:hover { text-decoration: underline; color: var(--text-dark); }
    .action-link.danger { color: var(--red); }

    .pagination-bar { display: flex; align-items: center; justify-content: space-between; padding: 10px 18px; font-size: 12px; color: var(--text-muted); }
    .pagination-controls { display: flex; gap: 4px; align-items: center; }
    .pagination-controls button, .pagination-controls .page-num {
      border: 1px solid var(--border-color); background: var(--bg-white); width: 26px; height: 26px; border-radius: 6px;
      cursor: pointer; font-size: 12px; display: inline-flex; align-items: center; justify-content: center;
    }
    .pagination-controls .page-num.active { background: var(--btn-dark-bg); color: #ffffff; border-color: var(--btn-dark-bg); }

    .modal-backdrop {
      display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.45);
      z-index: 100; align-items: center; justify-content: center;
    }
    .modal-backdrop.show { display: flex; }
    .modal-box { background: var(--bg-white); border-radius: 12px; padding: 22px 24px; width: 100%; max-width: 420px; box-shadow: 0 10px 30px rgba(0,0,0,0.15); font-family: 'Inter', sans-serif; }
    .modal-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; }
    .modal-header h2 { font-size: 17px; margin: 0; color: var(--text-dark); }
    .modal-close { border: none; background: none; font-size: 18px; cursor: pointer; color: var(--text-muted); }
    .modal-help { font-size: 13px; color: var(--text-muted); margin-bottom: 14px; }
    .form-group { margin-bottom: 14px; }
    .form-group label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: var(--text-dark); }
    .form-group input[type="text"], .form-group input[type="file"] {
      width: 100%; border: 1px solid var(--border-color); border-radius: 8px; padding: 8px 10px; font-size: 14px; font-family: 'Inter', sans-serif;
    }
    .form-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 18px; }
    .btn-primary { background: var(--btn-dark-bg); color: #fff; border: 1px solid var(--btn-dark-bg); padding: 8px 16px; border-radius: 8px; font-size: 14px; cursor: pointer; font-family: 'Inter', sans-serif; }
    .btn-secondary { background: var(--bg-white); color: var(--text-dark); border: 1px solid var(--border-color); padding: 8px 16px; border-radius: 8px; font-size: 14px; cursor: pointer; font-family: 'Inter', sans-serif; }
    </style>
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