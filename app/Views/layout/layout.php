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

    <style>
    :root {
      --bg-page: #f5f7fb;
      --bg-white: #ffffff;
      --bg-hover: #f8fafc;
      --border-color: #e5e7eb;
      --border-soft: #eef1f5;
      --text-dark: #0f172a;
      --text-body: #334155;
      --text-muted: #64748b;
      --text-light: #94a3b8;
      --blue-link: #1d4ed8;
      --primary: #1d4ed8;
      --primary-hover: #1e40af;
      --primary-light: #eff6ff;
      --primary-border: #dbe7fe;
      --primary-dark: #1e3a8a;
      --btn-dark-bg: #1d4ed8;
      --btn-dark-text: #ffffff;
      --green: #16a34a;
      --red: #dc2626;
    }

    body { font-family: 'Inter', 'Segoe UI', system-ui, sans-serif; background: var(--bg-page); }

    /* ================= Existing shared classes (used by other pages too) ================= */
    .breadcrumb { font-size: 12px; color: var(--text-muted); margin-bottom: 6px; font-family: 'Inter', sans-serif; display: flex; align-items: center; gap: 6px; }
    .breadcrumb i { font-size: 10px; }
    .breadcrumb .current { color: var(--primary); font-weight: 600; }

    .page-header-row { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 4px; gap: 24px; }
    .page-header-row h1 {
      font-size: 26px; font-weight: 700; margin: 0; display: flex; align-items: center; gap: 10px; color: var(--text-dark);
      font-family: 'Inter', sans-serif; letter-spacing: -0.01em;
    }
    .page-header-row .count-badge { font-size: 13px; font-weight: 600; color: var(--primary); background: var(--primary-light); padding: 2px 9px; border-radius: 999px; }

    .page-subtitle { margin: 0 0 16px; color: #475569; font-size: 13.5px; font-family: 'Inter', sans-serif; }

    .banner { padding: 11px 16px; border-radius: 10px; margin-bottom: 16px; font-size: 13.5px; font-family: 'Inter', sans-serif; display: flex; align-items: center; gap: 8px; }
    .banner-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
    .banner-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

    .card { background: var(--bg-white); border: 1px solid var(--border-color); border-radius: 14px; overflow: hidden; font-family: 'Inter', sans-serif; }

    .filter-row { display: flex; align-items: center; gap: 8px; padding: 12px 20px; border-bottom: 1px solid var(--border-color); flex-wrap: wrap; }
    .filter-row .push-right { margin-left: auto; display: flex; gap: 8px; }

    .filter-btn, .toolbar-btn {
      border: 1px solid var(--border-color); background: var(--bg-white); padding: 7px 13px; border-radius: 8px;
      font-size: 13px; font-weight: 600; cursor: pointer; color: var(--text-body); font-family: 'Inter', sans-serif;
      text-decoration: none; display: inline-flex; align-items: center; gap: 6px;
    }
    .filter-btn:hover, .toolbar-btn:hover { background: var(--bg-hover); }

    .btn-dark { background: var(--btn-dark-bg) !important; color: var(--btn-dark-text) !important; border: 1px solid var(--btn-dark-bg) !important; }
    .btn-dark:hover { background: var(--primary-hover) !important; }

    table.data-table { width: 100%; border-collapse: collapse; }
    table.data-table th {
      text-align: left; font-size: 11.5px; color: #475569; font-weight: 600; padding: 12px 20px; letter-spacing: 0.04em; text-transform: uppercase;
      border-bottom: 1px solid var(--border-color); background: #f8fafc;
    }
    table.data-table th.checkbox-col, table.data-table td.checkbox-col { width: 20px; }
    table.data-table td { padding: 13px 20px; border-bottom: 1px solid var(--border-soft); font-size: 13px; color: var(--text-dark); vertical-align: middle; }
    table.data-table tr:last-child td { border-bottom: none; }
    table.data-table tbody tr:hover td { background: #fafcff; }
    table.data-table input[type="checkbox"] { width: 16px; height: 16px; accent-color: var(--primary); cursor: pointer; }

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

    .pagination-bar { display: flex; align-items: center; justify-content: space-between; padding: 14px 20px; font-size: 12.5px; color: #475569; border-top: 1px solid var(--border-color); }
    .pagination-controls { display: flex; gap: 6px; align-items: center; }
    .pagination-controls button, .pagination-controls .page-num {
      border: 1px solid var(--border-color); background: var(--bg-white); min-width: 32px; height: 32px; padding: 0 10px; border-radius: 7px;
      cursor: pointer; font-size: 12.5px; display: inline-flex; align-items: center; justify-content: center; gap: 5px; color: var(--text-body); font-family: 'Inter', sans-serif;
    }
    .pagination-controls button:disabled { color: var(--text-light); cursor: not-allowed; }
    .pagination-controls .page-num.active { background: var(--primary); color: #ffffff; border-color: var(--primary); font-weight: 600; }

    .modal-backdrop {
      display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(15,23,42,0.45);
      z-index: 100; align-items: center; justify-content: center;
    }
    .modal-backdrop.show { display: flex; }
    .modal-box { background: var(--bg-white); border-radius: 14px; padding: 22px 24px; width: 100%; max-width: 440px; box-shadow: 0 20px 40px rgba(15,23,42,0.18); font-family: 'Inter', sans-serif; }
    .modal-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; }
    .modal-header h2 { font-size: 17px; font-weight: 700; margin: 0; color: var(--text-dark); }
    .modal-close { border: none; background: none; font-size: 20px; cursor: pointer; color: var(--text-muted); }
    .modal-help { font-size: 12.5px; color: var(--text-muted); margin-bottom: 14px; }
    .form-group { margin-bottom: 14px; }
    .form-group label { display: block; font-size: 12.5px; font-weight: 600; margin-bottom: 6px; color: var(--text-body); }
    .form-group input[type="text"], .form-group input[type="file"], .form-group input[type="number"] {
      width: 100%; border: 1px solid var(--border-color); border-radius: 9px; padding: 9px 12px; font-size: 13.5px; font-family: 'Inter', sans-serif; background: #f8fafc;
    }
    .form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline: none; border-color: #93b4f5; box-shadow: 0 0 0 3px rgba(29,78,216,0.12); background: #ffffff; }
    .form-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 18px; }
    .btn-primary { background: var(--primary); color: #fff; border: 1px solid var(--primary); padding: 9px 16px; border-radius: 9px; font-size: 13.5px; font-weight: 600; cursor: pointer; font-family: 'Inter', sans-serif; }
    .btn-primary:hover { background: var(--primary-hover); }
    .btn-secondary { background: var(--bg-white); color: var(--text-body); border: 1px solid var(--border-color); padding: 9px 16px; border-radius: 9px; font-size: 13.5px; font-weight: 600; cursor: pointer; font-family: 'Inter', sans-serif; }
    .btn-secondary:hover { background: var(--bg-hover); }

    /* ================= New design components ================= */
    .ui-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 24px; margin-bottom: 22px; flex-wrap: wrap; }
    .ui-head-left { display: flex; flex-direction: column; gap: 6px; }
    .ui-head h1 { font-size: 26px; font-weight: 700; margin: 0; color: var(--text-dark); letter-spacing: -0.01em; display: flex; align-items: center; gap: 10px; }
    .ui-head h1 .count-badge { font-size: 13px; font-weight: 600; color: var(--primary); background: var(--primary-light); padding: 2px 9px; border-radius: 999px; }
    .ui-head p { margin: 0; font-size: 13.5px; color: #475569; }
    .ui-head-right { display: flex; flex-direction: column; align-items: flex-end; gap: 14px; }

    .ui-info { width: 340px; max-width: 100%; display: flex; gap: 12px; padding: 14px 16px; border: 1px solid var(--primary-border); background: var(--primary-light); border-radius: 12px; box-sizing: border-box; }
    .ui-info-icon { width: 30px; height: 30px; flex-shrink: 0; border-radius: 50%; background: var(--primary); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 15px; }
    .ui-info strong { display: block; font-size: 13px; color: var(--primary-dark); margin-bottom: 3px; }
    .ui-info span { display: block; font-size: 12px; color: var(--text-body); line-height: 1.45; }
    .ui-info a { display: inline-flex; align-items: center; gap: 4px; margin-top: 5px; font-size: 12px; font-weight: 600; color: var(--primary); text-decoration: none; }
    .ui-info a:hover { text-decoration: underline; }

    .ui-stats { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 16px; margin-bottom: 22px; }
    @media (max-width: 1100px) { .ui-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (max-width: 600px) { .ui-stats { grid-template-columns: minmax(0, 1fr); } }
    .ui-stat { display: flex; align-items: center; gap: 14px; padding: 18px; background: #fff; border: 1px solid var(--border-color); border-radius: 12px; }
    .ui-stat-icon { width: 42px; height: 42px; flex-shrink: 0; border-radius: 10px; background: var(--primary-light); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 19px; }
    .ui-stat-label { font-size: 12.5px; color: #475569; }
    .ui-stat-value { display: flex; align-items: center; gap: 8px; margin-top: 3px; }
    .ui-stat-value b { font-size: 24px; font-weight: 700; color: var(--text-dark); }
    .ui-tip { display: flex; gap: 12px; padding: 16px 18px; background: #fffbeb; border: 1px solid #fde68a; border-radius: 12px; }
    .ui-tip-icon { width: 36px; height: 36px; flex-shrink: 0; border-radius: 10px; background: #fef3c7; color: #b45309; display: flex; align-items: center; justify-content: center; font-size: 17px; }
    .ui-tip strong { display: block; font-size: 13px; color: #92400e; margin-bottom: 3px; }
    .ui-tip span { font-size: 12px; color: #78350f; line-height: 1.45; }

    .ui-pill { font-size: 10.5px; font-weight: 600; padding: 2px 8px; border-radius: 999px; white-space: nowrap; }
    .ui-pill-blue { background: var(--primary-light); color: var(--primary); }
    .ui-pill-green { background: #dcfce7; color: #166534; }
    .ui-pill-amber { background: #fef3c7; color: #92400e; }
    .ui-pill-red { background: #fee2e2; color: #991b1b; }
    .ui-pill-gray { background: #f1f5f9; color: #334155; }

    .ui-tag { display: inline-flex; align-items: center; gap: 4px; font-size: 11.5px; font-weight: 600; padding: 3px 9px; border-radius: 6px; }
    .ui-dot { display: inline-flex; align-items: center; gap: 6px; font-size: 12.5px; font-weight: 600; }
    .ui-dot::before { content: ''; width: 7px; height: 7px; border-radius: 50%; background: currentColor; }

    .ui-card { background: #fff; border: 1px solid var(--border-color); border-radius: 14px; overflow: hidden; margin-bottom: 22px; }
    .ui-card-head { display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 18px 20px 0; flex-wrap: wrap; }
    .ui-card-head h2 { margin: 0; font-size: 16px; font-weight: 700; color: var(--text-dark); }
    .ui-card-head .sub { font-size: 12.5px; color: #475569; margin-top: 2px; }
    .ui-card-title { display: flex; align-items: center; gap: 12px; }
    .ui-card-title-icon { width: 38px; height: 38px; border-radius: 10px; background: var(--primary-light); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 17px; flex-shrink: 0; }
    .ui-card-tools { display: flex; gap: 8px; flex-wrap: wrap; }
    .ui-card-search { padding: 14px 20px 18px; }

    .ui-btn { height: 36px; padding: 0 14px; border-radius: 8px; font-family: inherit; font-size: 13px; font-weight: 600; display: inline-flex; align-items: center; justify-content: center; gap: 7px; cursor: pointer; text-decoration: none; border: 1px solid var(--border-color); background: #fff; color: var(--text-body); white-space: nowrap; }
    .ui-btn:hover { background: var(--bg-hover); }
    .ui-btn-primary { background: var(--primary); border-color: var(--primary); color: #fff; }
    .ui-btn-primary:hover { background: var(--primary-hover); color: #fff; }
    .ui-btn-soft { background: var(--primary-light); border-color: var(--primary-border); color: var(--primary); }
    .ui-btn-soft:hover { background: #dbeafe; }
    .ui-btn-icon { width: 32px; height: 32px; padding: 0; }
    .ui-btn-sm { height: 32px; padding: 0 12px; font-size: 12.5px; }
    .ui-btn-block { width: 100%; height: 48px; font-size: 14px; border-radius: 10px; }

    .ui-search { display: flex; align-items: center; gap: 8px; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); border-radius: 9px; background: #f8fafc; color: var(--text-muted); }
    .ui-search input { border: none; outline: none; background: transparent; font: inherit; font-size: 13px; width: 100%; color: var(--text-dark); }
    .ui-search:focus-within { border-color: #93b4f5; background: #fff; box-shadow: 0 0 0 3px rgba(29,78,216,0.12); }

    .ui-select { height: 32px; padding: 0 8px; border: 1px solid var(--border-color); border-radius: 7px; background: #fff; font: inherit; font-size: 12.5px; color: var(--text-body); cursor: pointer; }

    .ui-field { display: flex; flex-direction: column; gap: 7px; flex: 1; min-width: 220px; }
    .ui-field > span:first-child { font-size: 12.5px; font-weight: 600; color: var(--text-body); }
    .ui-field > span:first-child small { font-weight: 400; color: var(--text-muted); font-size: 12.5px; }
    .ui-field > .ui-input, .ui-input { display: flex; align-items: center; gap: 10px; height: 44px; padding: 0 14px; border: 1px solid var(--border-color); border-radius: 9px; background: #f8fafc; color: var(--text-muted); }
    .ui-input:focus-within { border-color: #93b4f5; background: #fff; box-shadow: 0 0 0 3px rgba(29,78,216,0.12); }
    .ui-input input, .ui-input select { flex: 1; min-width: 0; height: 100%; border: none; outline: none; background: transparent; font: inherit; font-size: 13px; color: var(--text-dark); cursor: pointer; }
    .ui-input input { cursor: text; }
    .ui-help { font-size: 12px; color: var(--text-muted); margin: 0; }
    .ui-row { display: flex; gap: 16px; flex-wrap: wrap; }

    .ui-tabs { display: inline-flex; gap: 6px; padding: 4px; background: #eef2f7; border-radius: 10px; margin-bottom: 16px; flex-wrap: wrap; }
    .ui-tab { display: inline-flex; align-items: center; gap: 7px; height: 38px; padding: 0 14px; border-radius: 8px; border: 1px solid transparent; background: transparent; color: var(--text-body); font: inherit; font-size: 13px; font-weight: 500; cursor: pointer; }
    .ui-tab.active { border-color: var(--primary-border); background: #fff; color: var(--primary); font-weight: 600; box-shadow: 0 1px 2px rgba(15,23,42,0.06); }

    .ui-alert { display: flex; align-items: center; gap: 14px; padding: 14px 18px; background: #fffbeb; border: 1px solid #fde68a; border-radius: 12px; margin-bottom: 22px; }
    .ui-alert-body { flex: 1; }
    .ui-alert strong { display: block; font-size: 13px; color: #92400e; }
    .ui-alert span { font-size: 12.5px; color: #78350f; }

    .ui-empty { text-align: center; color: var(--text-muted); padding: 36px 20px !important; font-size: 13px; }
    .ui-empty i { display: block; font-size: 26px; color: var(--text-light); margin-bottom: 8px; }

    .ui-menu { position: relative; }
    .ui-menu-list { display: none; position: absolute; right: 0; top: 38px; min-width: 150px; background: #fff; border: 1px solid var(--border-color); border-radius: 10px; box-shadow: 0 10px 25px rgba(15,23,42,0.12); padding: 6px; z-index: 20; }
    .ui-menu.open .ui-menu-list { display: block; }
    .ui-menu-list a, .ui-menu-list button { display: flex; align-items: center; gap: 8px; width: 100%; padding: 8px 10px; border: none; background: none; border-radius: 7px; font: inherit; font-size: 13px; color: var(--text-body); text-decoration: none; cursor: pointer; text-align: left; }
    .ui-menu-list a:hover, .ui-menu-list button:hover { background: var(--bg-hover); }
    .ui-menu-list .danger { color: var(--red); }

    :focus-visible { outline: 2px solid #93b4f5; outline-offset: 2px; }
    </style>
</head>

<body class="text-gray-800">

    <?php require __DIR__ . '/sidebar.php'; ?>

    <div class="lg:ml-64 min-h-screen">

        <?php require __DIR__ . '/header.php'; ?>

        <main class="p-4 sm:p-6 lg:px-8 lg:py-7">
            <?= $content ?? '' ?>
        </main>

    </div>
    <script>
      document.querySelectorAll('.breadcrumb').forEach(function (el) {
      el.innerHTML = el.innerHTML.replace(
          /^Finovo/,
          '<a href="/dashboard" style="color:inherit; text-decoration:none;" onmouseover="this.style.textDecoration=\'underline\'" onmouseout="this.style.textDecoration=\'none\'">Finovo</a>'
        );
      });

      // Three-dot menus (e.g. Stores table actions)
      document.addEventListener('click', function (e) {
        var toggle = e.target.closest('[data-menu-toggle]');
        document.querySelectorAll('.ui-menu.open').forEach(function (m) {
          if (!toggle || m !== toggle.closest('.ui-menu')) { m.classList.remove('open'); }
        });
        if (toggle) { toggle.closest('.ui-menu').classList.toggle('open'); }
      });
    </script>

</body>
</html>