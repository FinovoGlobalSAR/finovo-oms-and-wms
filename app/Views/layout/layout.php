<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= $title ?? 'Finovo OMS/WMS' ?></title>

    <script src="https://cdn.tailwindcss.com"></script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
    /* Rang aur size Figma (OMS-WMS) se liye gaye hain */
    :root {
      --bg-page: #f3f5f9;
      --bg-white: #ffffff;
      --bg-hover: #f8fafc;
      --bg-soft: #f3f5f9;
      --border-color: #e2e8f0;
      --border-soft: #f1f5f9;
      --border-input: #cbd5e1;
      --text-dark: #0f172a;
      --text-body: #475569;
      --text-muted: #64748b;
      --text-light: #94a3b8;
      --blue-link: #2563eb;
      --primary: #2563eb;
      --primary-hover: #1d4ed8;
      --primary-light: #eff6ff;
      --primary-border: #dbeafe;
      --primary-dark: #1e40af;
      --brand: #1e50d1;
      --btn-dark-bg: #2563eb;
      --btn-dark-text: #ffffff;
      --green: #047857;
      --green-bg: #e6f6ee;
      --green-text: #048557;
      --amber: #e48508;
      --red: #dc2626;
      --font-ui: 'Geist', 'Inter', 'Segoe UI', system-ui, sans-serif;
      --font-body: 'Inter', 'Segoe UI', system-ui, sans-serif;
    }

    body { font-family: var(--font-body); background: var(--bg-page); color: var(--text-dark); -webkit-font-smoothing: antialiased; }
    .sr-only { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0,0,0,0); border: 0; }

    /* ================= Sidebar (Figma: sidebar-container) ================= */
    .sb { width: 260px; background: #ffffff; border-right: 1px solid var(--border-color); padding: 24px; display: flex; flex-direction: column; gap: 24px; overflow-y: auto; font-family: var(--font-ui); }
    .sb-brand { display: flex; align-items: center; gap: 12px; text-decoration: none; }
    .sb-brand svg { flex-shrink: 0; }
    .sb-brand-name { display: block; font-size: 16px; font-weight: 700; color: var(--text-dark); line-height: 1.25; }
    .sb-brand-sub { display: block; font-size: 11px; font-weight: 500; color: var(--text-light); margin-top: 2px; }

    .sb-user { display: flex; align-items: center; gap: 12px; padding: 12px; background: #f8fafc; border: 1px solid var(--border-color); border-radius: 10px; }
    .sb-avatar, .hd-avatar { width: 32px; height: 32px; flex-shrink: 0; border-radius: 16px; background: var(--brand); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 600; }
    .sb-user-text { min-width: 0; }
    .sb-user-name { display: block; font-size: 13px; font-weight: 600; color: var(--text-dark); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .sb-user-role { display: block; font-size: 11px; color: var(--text-muted); }

    .sb-store-select { position: relative; display: flex; align-items: center; gap: 10px; padding: 0 12px; height: 36px; background: #f8fafc; border-radius: 8px; color: var(--text-dark); }
    .sb-store-select select { flex: 1; min-width: 0; height: 100%; border: none; background: transparent; appearance: none; -webkit-appearance: none; font-family: var(--font-ui); font-size: 13px; font-weight: 600; color: var(--text-dark); cursor: pointer; outline: none; padding-right: 18px; }
    .sb-select-chevron { position: absolute; right: 12px; pointer-events: none; color: var(--text-body); }

    .sb-managing { display: flex; align-items: center; gap: 10px; padding: 12px; background: var(--primary-light); border: 1px solid var(--primary-border); border-radius: 10px; text-decoration: none; }
    a.sb-managing:hover { background: #e4efff; }
    .sb-managing-icon { width: 32px; height: 32px; flex-shrink: 0; border-radius: 8px; background: var(--primary-border); color: var(--brand); display: flex; align-items: center; justify-content: center; }
    .sb-managing-text { flex: 1; min-width: 0; font-family: var(--font-body); }
    .sb-managing-label { display: block; font-size: 11px; font-weight: 600; color: var(--primary-dark); }
    .sb-managing-name { display: block; font-size: 12px; font-weight: 700; color: var(--text-dark); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-top: 1px; }
    .sb-managing-chevron { flex-shrink: 0; color: var(--text-muted); }

    .sb-nav { display: flex; flex-direction: column; gap: 4px; flex: 1; }
    .sb-section { margin: 0; font-size: 11px; font-weight: 700; color: var(--text-light); text-transform: uppercase; }
    .sb-link { position: relative; display: flex; align-items: center; gap: 12px; padding: 10px 16px; border-radius: 8px; font-size: 14px; font-weight: 500; color: var(--text-body); text-decoration: none; transition: background .15s, color .15s; }
    .sb-link svg { flex-shrink: 0; color: #1e293b; }
    .sb-link:hover { background: #f8fafc; color: var(--text-dark); }
    .sb-link.active { background: var(--primary-light); color: var(--brand); font-weight: 600; }
    .sb-link.active svg { color: var(--brand); }
    .sb-link.active::before { content: ''; position: absolute; left: 0; top: 8px; bottom: 8px; width: 4px; border-radius: 2px; background: var(--brand); }

    .sb-help { padding: 16px; background: #f8fafc; border: 1px solid var(--border-color); border-radius: 12px; }
    .sb-help-title { display: flex; align-items: center; gap: 8px; margin: 0 0 12px; font-size: 13px; font-weight: 600; color: var(--text-dark); }
    .sb-help-text { margin: 0; font-size: 12px; line-height: 1.4; color: var(--text-body); }

    /* ================= Header (Figma: top-nav-bar) ================= */
    .hd { position: sticky; top: 0; z-index: 30; height: 72px; padding: 0 40px; background: #ffffff; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; justify-content: space-between; gap: 16px; font-family: var(--font-ui); }
    .hd-left { display: flex; align-items: center; gap: 12px; min-width: 0; }
    .hd-search { position: relative; display: flex; align-items: center; gap: 10px; width: 320px; max-width: 100%; height: 36px; padding: 0 14px; background: #f8fafc; border: 1px solid var(--border-input); border-radius: 8px; }
    .hd-search:focus-within { background: #fff; border-color: #93b4f5; box-shadow: 0 0 0 3px rgba(37,99,235,0.12); }
    .hd-search input { flex: 1; min-width: 0; border: none; outline: none; background: transparent; font-family: var(--font-ui); font-size: 13px; color: var(--text-dark); }
    .hd-search input::placeholder { color: var(--text-light); }
    .hd-search-results { position: absolute; left: 0; right: 0; top: 42px; background: #fff; border: 1px solid var(--border-color); border-radius: 12px; box-shadow: 0 10px 25px rgba(15,23,42,0.12); overflow: hidden; max-height: 360px; overflow-y: auto; z-index: 50; }
    .hd-right { display: flex; align-items: center; gap: 24px; }
    .hd-icons { display: flex; align-items: center; gap: 16px; }
    .hd-icon-btn { position: relative; width: 28px; height: 28px; border: none; background: none; padding: 0; border-radius: 8px; color: #1e293b; display: flex; align-items: center; justify-content: center; cursor: pointer; }
    .hd-icon-btn:hover { color: var(--primary); }
    .hd-badge { position: absolute; top: -2px; right: -3px; min-width: 14px; height: 14px; padding: 0 3px; border-radius: 7px; background: #f04438; color: #fff; font-size: 9px; font-weight: 700; display: flex; align-items: center; justify-content: center; }
    .hd-menu-wrap { position: relative; }
    .hd-profile { display: flex; align-items: center; gap: 10px; border: none; background: none; padding: 0; cursor: pointer; text-align: left; font-family: var(--font-ui); }
    .hd-profile-name { display: block; font-size: 13px; font-weight: 600; color: var(--text-dark); }
    .hd-profile-role { display: block; font-size: 11px; color: var(--text-light); margin-top: 1px; }
    .hd-dropdown { position: absolute; right: 0; top: calc(100% + 12px); min-width: 220px; background: #fff; border: 1px solid var(--border-color); border-radius: 12px; box-shadow: 0 10px 25px rgba(15,23,42,0.12); padding: 6px; z-index: 40; }
    .hd-dropdown-wide { width: 340px; padding: 0; overflow: hidden; }
    .hd-dropdown-head { padding: 12px 16px; border-bottom: 1px solid var(--border-soft); }
    .hd-dropdown-title { margin: 0; font-size: 14px; font-weight: 600; color: var(--text-dark); }
    .hd-dropdown-sub { margin: 0; font-size: 12px; color: var(--text-light); }
    .hd-dropdown-body { max-height: 360px; overflow-y: auto; }
    .hd-empty { padding: 24px 16px; text-align: center; font-size: 13px; color: var(--text-light); }
    .hd-group { padding: 12px 16px 4px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-light); }
    .hd-alert-row { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 10px 16px; border-bottom: 1px solid #f8fafc; text-decoration: none; }
    a.hd-alert-row:hover { background: #f8fafc; }
    .hd-alert-name { display: block; font-size: 13px; font-weight: 500; color: var(--text-dark); }
    .hd-alert-sku { display: block; font-size: 11px; color: var(--text-light); }
    .hd-alert-pill { flex-shrink: 0; font-size: 11px; font-weight: 600; padding: 2px 8px; border-radius: 999px; }
    .hd-alert-pill.out { background: #fef2f2; color: #dc2626; }
    .hd-alert-pill.low { background: #fffbeb; color: #d97706; }
    .hd-dropdown-foot { display: block; text-align: center; padding: 10px; border-top: 1px solid var(--border-soft); font-size: 13px; font-weight: 600; color: var(--primary); text-decoration: none; }
    .hd-dropdown-foot:hover { background: var(--primary-light); }
    .hd-menu-link { display: flex; align-items: center; gap: 10px; padding: 9px 10px; border-radius: 8px; font-size: 13px; font-weight: 500; color: var(--text-body); text-decoration: none; }
    .hd-menu-link i { font-size: 14px; color: var(--text-muted); }
    .hd-menu-link:hover { background: #f8fafc; color: var(--text-dark); }
    .hd-menu-link.danger, .hd-menu-link.danger i { color: var(--red); }
    .hd-menu-link.danger:hover { background: #fef2f2; }
    .hd-menu-sep { height: 1px; background: var(--border-soft); margin: 6px 4px; }
    @media (min-width: 1024px) { #mobileMenuButton { display: none; } }
    @media (max-width: 1023px) { .hd { padding: 0 16px; } }
    @media (max-width: 640px) { .hd-search { width: 170px; } .hd-profile-text { display: none; } .hd-right { gap: 14px; } }

    .app-main { padding: 32px 40px 40px; }
    @media (max-width: 1023px) { .app-main { padding: 20px 16px 32px; } }

    /* ================= Existing shared classes (used by other pages too) ================= */
    .breadcrumb { font-size: 12px; font-weight: 500; color: var(--text-light); margin-bottom: 6px; display: flex; align-items: center; gap: 6px; }
    .breadcrumb i { font-size: 9px; }
    .breadcrumb .current { color: var(--text-body); font-weight: 500; }

    .page-header-row { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 4px; gap: 24px; }
    .page-header-row h1 { font-size: 28px; font-weight: 700; margin: 0; display: flex; align-items: center; gap: 12px; color: var(--text-dark); }
    .page-header-row .count-badge { font-size: 20px; font-weight: 600; color: var(--text-light); background: none; padding: 0; }

    .page-subtitle { margin: 0 0 16px; color: var(--text-body); font-size: 14px; line-height: 1.5; }

    .banner { padding: 11px 16px; border-radius: 10px; margin-bottom: 16px; font-size: 13.5px; display: flex; align-items: center; gap: 8px; }
    .banner-success { background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; }
    .banner-error { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }

    .card { background: var(--bg-white); border: 1px solid var(--border-color); border-radius: 20px; overflow: hidden; }

    .filter-row { display: flex; align-items: center; gap: 8px; padding: 12px 24px; border-bottom: 1px solid var(--border-color); flex-wrap: wrap; }
    .filter-row .push-right { margin-left: auto; display: flex; gap: 8px; }

    .filter-btn, .toolbar-btn {
      border: 1px solid var(--border-color); background: var(--bg-white); padding: 7px 12px; border-radius: 8px;
      font-size: 13px; font-weight: 500; cursor: pointer; color: var(--text-body); font-family: inherit;
      text-decoration: none; display: inline-flex; align-items: center; gap: 6px;
    }
    .filter-btn:hover, .toolbar-btn:hover { background: var(--bg-hover); }

    .btn-dark { background: var(--btn-dark-bg) !important; color: var(--btn-dark-text) !important; border: 1px solid var(--btn-dark-bg) !important; }
    .btn-dark:hover { background: var(--primary-hover) !important; }

    /* Table — Figma "Orders to Pick & Pack" / "Recent Movements" jaisa */
    table.data-table { width: 100%; border-collapse: collapse; }
    table.data-table th {
      text-align: left; font-size: 12px; color: var(--text-muted); font-weight: 700; padding: 10px 12px; text-transform: uppercase;
      border-top: 1px solid var(--border-color); border-bottom: 1px solid var(--border-color); background: #f8fafc; white-space: nowrap;
    }
    /* Row ke dono kinaron pe 22px (Figma), beech ke columns 12px */
    table.data-table th:first-child, table.data-table td:first-child { padding-left: 22px; }
    table.data-table th:last-child, table.data-table td:last-child { padding-right: 22px; }
    table.data-table th.checkbox-col, table.data-table td.checkbox-col { width: 36px; padding-right: 0; }
    table.data-table td { padding: 12px 12px; border-bottom: 1px solid var(--border-color); font-size: 13px; color: var(--text-body); vertical-align: middle; }
    table.data-table tr:last-child td { border-bottom: none; }
    table.data-table tbody tr:hover td { background: #fafcff; }
    table.data-table input[type="checkbox"] { width: 14px; height: 14px; accent-color: var(--primary); cursor: pointer; }

    /* Table card (Figma: table-card-container) */
    .tbl-card { border-radius: 14px; padding: 22px 0 20px; overflow: visible; }
    .tbl-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 0 22px; flex-wrap: wrap; }
    .tbl-head h2 { margin: 0; font-size: 16px; font-weight: 700; color: #1e293b; }
    .tbl-head .sub { margin-top: 2px; font-size: 12px; color: var(--text-muted); }
    .tbl-tools { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
    .tbl-btn { display: inline-flex; align-items: center; gap: 7px; height: 32px; padding: 0 14px; border: 1px solid var(--border-color); border-radius: 8px; background: #fff; color: var(--text-muted); font-family: inherit; font-size: 12px; font-weight: 600; text-decoration: none; cursor: pointer; white-space: nowrap; }
    .tbl-btn:hover { background: #f8fafc; }
    .tbl-btn-soft { background: var(--primary-light); border-color: #bfdbfe; color: var(--primary); }
    .tbl-btn-soft:hover { background: #dbeafe; }
    .tbl-btn .tbl-count { min-width: 18px; height: 18px; padding: 0 5px; border-radius: 9px; background: var(--primary); color: #fff; font-size: 10px; display: inline-flex; align-items: center; justify-content: center; }
    .tbl-search { display: flex; align-items: center; gap: 10px; padding: 14px 22px; margin: 0; }
    .tbl-search .ui-search { flex: 1; min-width: 0; height: 32px; }
    .tbl-note { display: inline-flex; align-items: center; gap: 6px; font-size: 12px; color: var(--text-muted); white-space: nowrap; }
    .tbl-filter-panel { min-width: 240px; padding: 12px; }
    .tbl-filter-panel label { display: block; margin: 0 0 4px; font-size: 11px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; }
    .tbl-filter-panel select, .tbl-filter-panel input { width: 100%; height: 34px; margin-bottom: 10px; padding: 0 10px; border: 1px solid var(--border-color); border-radius: 8px; background: #fff; font-family: inherit; font-size: 13px; color: var(--text-dark); }
    .tbl-filter-actions { display: flex; justify-content: space-between; gap: 8px; }
    .tbl-filter-panel .tbl-btn { flex: 1; width: auto; height: 32px; padding: 0 14px; justify-content: center; border: 1px solid var(--border-color); background: #fff; color: var(--text-muted); font-size: 12px; font-weight: 600; }
    .tbl-filter-panel .tbl-btn-soft { background: var(--primary-light); border-color: #bfdbfe; color: var(--primary); }
    .tbl-card .pagination-bar { border-top: none; padding: 20px 22px 0; }

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

    .pagination-bar { display: flex; align-items: center; justify-content: space-between; padding: 16px 24px; font-size: 13px; font-weight: 500; color: var(--text-light); border-top: 1px solid var(--border-color); gap: 12px; flex-wrap: wrap; }
    .pagination-controls { display: flex; gap: 4px; align-items: center; }
    .pagination-controls button, .pagination-controls .page-num {
      border: 1px solid var(--border-color); background: var(--bg-white); min-width: 32px; height: 32px; padding: 0 10px; border-radius: 8px;
      cursor: pointer; font-size: 13px; display: inline-flex; align-items: center; justify-content: center; gap: 5px; color: var(--text-body); font-family: inherit; text-decoration: none;
    }
    .pagination-controls button:disabled { opacity: .4; cursor: not-allowed; }
    .pagination-controls .page-num.active { background: var(--primary); color: #ffffff; border-color: var(--primary); font-weight: 600; }

    .modal-backdrop {
      display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(15,23,42,0.45);
      z-index: 100; align-items: center; justify-content: center;
    }
    .modal-backdrop.show { display: flex; }
    .modal-box { background: var(--bg-white); border-radius: 16px; padding: 22px 24px; width: 100%; max-width: 440px; box-shadow: 0 20px 40px rgba(15,23,42,0.18); }
    .modal-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; }
    .modal-header h2 { font-size: 17px; font-weight: 700; margin: 0; color: var(--text-dark); }
    .modal-close { border: none; background: none; font-size: 20px; cursor: pointer; color: var(--text-muted); }
    .modal-help { font-size: 12.5px; color: var(--text-muted); margin-bottom: 14px; }
    .form-group { margin-bottom: 14px; }
    .form-group label { display: block; font-size: 13px; font-weight: 500; margin-bottom: 6px; color: var(--text-dark); }
    .form-group input[type="text"], .form-group input[type="file"], .form-group input[type="number"] {
      width: 100%; border: 1px solid var(--border-input); border-radius: 8px; padding: 9px 12px; font-size: 13.5px; font-family: inherit; background: #ffffff;
    }
    .form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline: none; border-color: #93b4f5; box-shadow: 0 0 0 3px rgba(37,99,235,0.12); background: #ffffff; }
    .form-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 18px; }
    .btn-primary { background: var(--primary); color: #fff; border: 1px solid var(--primary); padding: 9px 16px; border-radius: 10px; font-size: 14px; font-weight: 600; cursor: pointer; font-family: inherit; }
    .btn-primary:hover { background: var(--primary-hover); }
    .btn-secondary { background: var(--bg-white); color: var(--text-body); border: 1px solid var(--border-color); padding: 9px 16px; border-radius: 10px; font-size: 14px; font-weight: 600; cursor: pointer; font-family: inherit; }
    .btn-secondary:hover { background: var(--bg-hover); }

    /* ================= Page components (Figma: header-section, kpi-card, table card) ================= */
    .ui-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 24px; margin-bottom: 24px; flex-wrap: wrap; }
    .ui-head-left { display: flex; flex-direction: column; gap: 6px; min-width: 0; }
    .ui-head-left .breadcrumb { margin-bottom: 0; }
    .ui-head h1 { font-size: 28px; font-weight: 700; margin: 0; color: var(--text-dark); display: flex; align-items: center; gap: 12px; line-height: 1.25; }
    .ui-head h1 .count-badge { font-size: 20px; font-weight: 600; color: var(--text-light); background: none; padding: 0; }
    .ui-head p { margin: 0; font-size: 14px; line-height: 1.5; color: var(--text-body); }
    .ui-head-right { display: flex; flex-direction: column; align-items: flex-end; gap: 12px; }

    .ui-info { width: 420px; max-width: 100%; display: flex; align-items: center; gap: 16px; padding: 16px; border: 1px solid var(--primary-border); background: var(--primary-light); border-radius: 16px; box-sizing: border-box; }
    .ui-info-icon { width: 40px; height: 40px; flex-shrink: 0; border-radius: 10px; background: var(--primary-border); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 18px; }
    .ui-info-icon.round { width: 32px; height: 32px; border-radius: 50%; background: var(--primary); color: #fff; font-size: 16px; }
    .ui-info strong { display: block; font-size: 13px; font-weight: 700; color: var(--primary-dark); margin-bottom: 2px; }
    .ui-info span { display: block; font-size: 11px; color: var(--text-body); line-height: 1.4; }
    .ui-info a { display: inline-flex; align-items: center; gap: 4px; margin-top: 4px; font-size: 12px; font-weight: 600; color: var(--primary); text-decoration: none; }
    .ui-info a:hover { text-decoration: underline; }
    .ui-info .ui-info-btn { margin: 0 0 0 auto; flex-shrink: 0; padding: 6px 10px; border: 1px solid var(--border-input); border-radius: 6px; background: #fff; font-size: 11px; font-weight: 500; color: var(--text-dark); }
    .ui-info .ui-info-btn:hover { text-decoration: none; background: #f8fafc; }

    .ui-stats { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 16px; margin-bottom: 24px; }
    @media (max-width: 1100px) { .ui-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (max-width: 600px) { .ui-stats { grid-template-columns: minmax(0, 1fr); } }
    .ui-stat { display: flex; align-items: center; gap: 16px; padding: 18px; background: #fff; border: 1px solid var(--border-color); border-radius: 16px; min-width: 0; }
    .ui-stat-icon { width: 44px; height: 44px; flex-shrink: 0; border-radius: 12px; background: var(--primary-light); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 20px; }
    .ui-stat-label { font-size: 12px; font-weight: 500; color: var(--text-body); }
    .ui-stat-value { display: flex; align-items: center; gap: 8px; margin-top: 4px; }
    .ui-stat-value b { font-size: 22px; font-weight: 700; color: var(--text-dark); line-height: 1.2; }
    .ui-tip { display: flex; align-items: center; gap: 16px; padding: 18px; background: #fffbeb; border: 1px solid #fef3c7; border-radius: 16px; min-width: 0; }
    .ui-tip-icon { width: 44px; height: 44px; flex-shrink: 0; border-radius: 12px; background: #fef3c7; color: #d97706; display: flex; align-items: center; justify-content: center; font-size: 20px; }
    .ui-tip strong { display: block; font-size: 12px; font-weight: 700; color: #d97706; margin-bottom: 2px; }
    .ui-tip span { font-size: 11px; color: var(--text-body); line-height: 1.4; }

    .ui-pill { font-size: 11px; font-weight: 600; padding: 3px 8px; border-radius: 6px; white-space: nowrap; }
    .ui-pill-blue { background: var(--primary-light); color: var(--primary); }
    .ui-pill-green { background: var(--green-bg); color: var(--green-text); }
    .ui-pill-amber { background: #fef3c7; color: #b45309; }
    .ui-pill-red { background: #fee2e2; color: #b91c1c; }
    .ui-pill-gray { background: #f1f5f9; color: #334155; }

    .ui-tag { display: inline-flex; align-items: center; gap: 4px; font-size: 12px; font-weight: 500; padding: 4px 10px; border-radius: 8px; }
    .ui-dot { display: inline-flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 600; }
    .ui-dot::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: currentColor; }

    .ui-card { background: #fff; border: 1px solid var(--border-color); border-radius: 20px; overflow: hidden; margin-bottom: 24px; }
    .ui-card-head { display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 24px; flex-wrap: wrap; }
    .ui-card-head h2 { margin: 0; font-size: 16px; font-weight: 700; color: var(--text-dark); }
    .ui-card-head .sub { font-size: 13px; color: var(--text-body); margin-top: 2px; }
    .ui-card-title { display: flex; align-items: center; gap: 16px; }
    .ui-card-title-icon { width: 40px; height: 40px; border-radius: 10px; background: var(--bg-soft); color: var(--text-body); display: flex; align-items: center; justify-content: center; font-size: 18px; flex-shrink: 0; }
    .ui-card-tools { display: flex; gap: 8px; flex-wrap: wrap; }
    .ui-card-search { padding: 0 24px 18px; margin-top: -6px; }

    .ui-btn { height: 34px; padding: 0 12px; border-radius: 8px; font-family: inherit; font-size: 13px; font-weight: 500; display: inline-flex; align-items: center; justify-content: center; gap: 6px; cursor: pointer; text-decoration: none; border: 1px solid var(--border-color); background: #fff; color: var(--text-body); white-space: nowrap; }
    .ui-btn:hover { background: var(--bg-hover); }
    .ui-btn-primary { height: 40px; padding: 0 16px; border-radius: 10px; font-size: 14px; font-weight: 600; background: var(--primary); border-color: var(--primary); color: #fff; }
    .ui-btn-primary:hover { background: var(--primary-hover); color: #fff; }
    .ui-btn-soft { background: var(--primary-light); border-color: var(--border-color); color: var(--primary); font-weight: 600; }
    .ui-btn-soft:hover { background: #dbeafe; }
    .ui-btn-icon { width: 32px; height: 32px; padding: 0; }
    .ui-btn-sm { height: 32px; padding: 0 12px; font-size: 13px; }
    .ui-btn-block { width: 100%; height: 56px; font-size: 18px; font-weight: 500; border-radius: 10px; }

    .ui-search { display: flex; align-items: center; gap: 8px; height: 32px; padding: 0 12px; border: 1px solid var(--border-color); border-radius: 8px; background: #f8fafc; color: var(--text-light); }
    .ui-search input { border: none; outline: none; background: transparent; font: inherit; font-size: 12px; width: 100%; color: var(--text-dark); }
    .ui-search input::placeholder { color: var(--text-light); }
    .ui-search:focus-within { border-color: #93b4f5; background: #fff; box-shadow: 0 0 0 3px rgba(37,99,235,0.12); }

    .ui-select { height: 32px; padding: 0 8px; border: 1px solid var(--border-color); border-radius: 8px; background: #fff; font: inherit; font-size: 12.5px; color: var(--text-body); cursor: pointer; }

    .ui-field { display: flex; flex-direction: column; gap: 8px; flex: 1; min-width: 220px; }
    .ui-field > span:first-child { font-size: 13px; font-weight: 500; color: var(--text-dark); }
    .ui-field > span:first-child small { font-weight: 500; color: var(--text-dark); font-size: 13px; }
    .ui-field > .ui-input, .ui-input { display: flex; align-items: center; gap: 10px; height: 44px; padding: 0 14px; border: 1px solid var(--border-input); border-radius: 8px; background: #fff; color: var(--text-dark); }
    .ui-input:focus-within { border-color: #93b4f5; box-shadow: 0 0 0 3px rgba(37,99,235,0.12); }
    .ui-input input, .ui-input select { flex: 1; min-width: 0; height: 100%; border: none; outline: none; background: transparent; font: inherit; font-size: 14px; color: var(--text-dark); cursor: pointer; }
    .ui-input input { cursor: text; }
    .ui-input input::placeholder { color: #64748b; }
    .ui-help { font-size: 12px; color: var(--text-muted); margin: 0; }
    .ui-row { display: flex; gap: 16px; flex-wrap: wrap; }

    .ui-tabs { display: inline-flex; gap: 6px; margin-bottom: 12px; flex-wrap: wrap; }
    .ui-tab { display: inline-flex; align-items: center; gap: 8px; height: 38px; padding: 0 10px; border-radius: 4px; border: 1px solid var(--border-color); border-bottom-width: 2px; background: #fff; color: var(--text-body); font: inherit; font-size: 14px; font-weight: 400; cursor: pointer; }
    .ui-tab.active { border-color: var(--border-color); border-bottom-color: var(--primary); color: var(--primary); }

    .ui-alert { display: flex; align-items: center; gap: 16px; padding: 18px; background: #fffbeb; border: 1px solid #fef3c7; border-radius: 16px; margin-bottom: 10px; }
    .ui-alert-body { flex: 1; }
    .ui-alert strong { display: block; font-size: 12px; color: #d97706; }
    .ui-alert span { font-size: 11px; color: var(--text-body); }

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

<body>

    <?php require __DIR__ . '/sidebar.php'; ?>

    <div class="lg:ml-[260px] min-h-screen">

        <?php require __DIR__ . '/header.php'; ?>

        <main class="app-main">
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
        var insidePanel = e.target.closest('.tbl-filter-panel');
        document.querySelectorAll('.ui-menu.open').forEach(function (m) {
          if (insidePanel && m.contains(insidePanel)) { return; }   // Filters panel ke andar click se band na ho
          if (!toggle || m !== toggle.closest('.ui-menu')) { m.classList.remove('open'); }
        });
        if (toggle) { toggle.closest('.ui-menu').classList.toggle('open'); }
      });
    </script>

    <script>
      // CSRF token: har POST form aur fetch() POST ke saath apne aap jata hai (core/Csrf.php)
      (function () {
        var token = <?= json_encode(Csrf::token()) ?>;
        if (!token) { return; }

        function sameOrigin(url) {
          try { return new URL(url, window.location.href).origin === window.location.origin; } catch (e) { return false; }
        }
        function addToken(form) {
          // getAttribute: form ke andar "method"/"action" naam ka input ho to form.method galat cheez deta hai
          if (!form || !form.getAttribute) { return; }
          var method = (form.getAttribute('method') || 'get').toLowerCase();
          var action = form.getAttribute('action') || window.location.href;
          if (method !== 'post' || !sameOrigin(action)) { return; }
          var input = form.querySelector('input[name="_csrf"]');
          if (!input) {
            input = document.createElement('input');
            input.type = 'hidden';
            input.name = '_csrf';
            form.appendChild(input);
          }
          input.value = token;
        }

        // Normal submit (button / Enter)
        document.addEventListener('submit', function (e) { addToken(e.target); }, true);

        // JavaScript se form.submit() (yeh "submit" event nahi chalata)
        var nativeSubmit = HTMLFormElement.prototype.submit;
        HTMLFormElement.prototype.submit = function () { addToken(this); return nativeSubmit.call(this); };

        // fetch() POST requests
        if (window.fetch) {
          var nativeFetch = window.fetch;
          window.fetch = function (input, init) {
            init = init || {};
            var method = String(init.method || 'GET').toUpperCase();
            var url = typeof input === 'string' ? input : (input && input.url) || '';
            if (method !== 'GET' && method !== 'HEAD' && sameOrigin(url)) {
              var headers = new Headers(init.headers || {});
              headers.set('X-CSRF-Token', token);
              init.headers = headers;
            }
            return nativeFetch.call(this, input, init);
          };
        }
      })();
    </script>

</body>
</html>