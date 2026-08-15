<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'StoreManager | ERP Tactical Workspace') ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-color: #0b0f19;
            --panel-bg: rgba(22, 30, 49, 0.65);
            --border-color: rgba(45, 212, 191, 0.12);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --accent: #2dd4bf;
            --accent-glow: rgba(45, 212, 191, 0.1);
            --success: #34d399;
            --danger: #f87171;
            --warning: #fbbf24;
            --font-family: 'Plus Jakarta Sans', sans-serif;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            background-color: var(--bg-color);
            color: var(--text-main);
            font-family: var(--font-family);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            padding: 0;
            margin: 0;
            overflow-x: hidden;
        }

        .app-container {
            width: 100%;
            max-width: 100%;
            padding: 24px;
        }

        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(8, 12, 24, 0.7);
            border: 1px solid var(--border-color);
            padding: 16px 24px;
            border-radius: 20px;
            margin-bottom: 24px;
            backdrop-filter: blur(15px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.25);
        }

        .nav-logo { font-size: 20px; font-weight: 800; display: flex; align-items: center; gap: 10px; }
        .nav-logo span { color: var(--accent); }

        .nav-menu { display: flex; gap: 8px; }
        .nav-item {
            background: transparent;
            border: 1px solid transparent;
            color: var(--text-muted);
            padding: 10px 18px;
            border-radius: 12px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.3s;
            display: inline-block;
        }

        .nav-item:hover, .nav-item.active {
            background: var(--accent-glow);
            color: var(--accent);
            border-color: var(--accent);
        }

        .toast-box {
            position: fixed;
            top: 24px;
            right: 24px;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .toast {
            background: rgba(13, 20, 38, 0.9);
            border: 1px solid var(--border-color);
            padding: 16px 24px;
            border-radius: 16px;
            color: white;
            font-size: 13px;
            font-weight: 600;
            backdrop-filter: blur(10px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideIn 0.3s ease forwards;
        }
        .toast.success { border-left: 4px solid var(--success); }
        .toast.danger { border-left: 4px solid var(--danger); }

        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .badge.payee, .badge.badge-success { background: rgba(16, 185, 129, 0.1); color: var(--success); }
        .badge.non-payee, .badge.badge-danger { background: rgba(244, 63, 94, 0.1); color: var(--danger); }
        .badge.warning, .badge.badge-warning { background: rgba(245, 158, 11, 0.1); color: var(--warning); }

        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
            margin-bottom: 32px;
        }

        .kpi-card {
            background: var(--panel-bg);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            position: relative;
            overflow: hidden;
        }

        .kpi-card::after {
            content: '';
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background: radial-gradient(circle at 10% 20%, rgba(59, 130, 246, 0.03) 0%, transparent 80%);
            pointer-events: none;
        }

        .kpi-label { font-size: 11px; color: var(--text-muted); text-transform: uppercase; font-weight: 700; margin-bottom: 6px; letter-spacing: 0.5px; }
        .kpi-val { font-size: 24px; font-weight: 800; letter-spacing: -0.5px; }

        .progress-ring-container { position: relative; width: 60px; height: 60px; }
        .progress-ring { transform: rotate(-90deg); }
        .progress-ring-circle-bg { fill: transparent; stroke: rgba(255,255,255,0.03); stroke-width: 6; }
        .progress-ring-circle { fill: transparent; stroke: var(--accent); stroke-width: 6; stroke-dasharray: 157; stroke-dashoffset: 60; stroke-linecap: round; transition: stroke-dashoffset 0.35s; }

        .panel-card {
            background: var(--panel-bg);
            border: 1px solid var(--border-color);
            backdrop-filter: blur(15px);
            border-radius: 24px;
            padding: 28px;
            box-shadow: 0 12px 35px rgba(0, 0, 0, 0.3);
            margin-bottom: 24px;
        }

        .panel-title {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 20px;
            border-left: 4px solid var(--accent);
            padding-left: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .search-control {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 6px 12px;
            color: white;
            font-size: 12px;
            outline: none;
            font-family: var(--font-family);
            width: 220px;
        }
        .search-control:focus { border-color: var(--accent); }

        .keypad-container {
            background: #090e1a;
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 12px;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
            margin-top: 12px;
            max-width: 280px;
            display: none;
            animation: fadeIn 0.2s ease;
        }

        .keypad-btn {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.05);
            border-radius: 10px;
            color: white;
            font-size: 16px;
            font-weight: 700;
            padding: 12px 0;
            cursor: pointer;
            transition: all 0.2s;
            text-align: center;
        }
        .keypad-btn:hover { background: var(--accent-glow); color: var(--accent); }
        .keypad-btn:active { transform: scale(0.95); }

        .form-group { display: flex; flex-direction: column; gap: 6px; margin-bottom: 16px; position: relative; }
        .form-group label { font-size: 12px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; }
        
        .form-control {
            background: rgba(8, 12, 24, 0.7);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 14px 18px;
            color: white;
            font-family: var(--font-family);
            outline: none;
            font-size: 13px;
            transition: all 0.3s;
        }
        .form-control:focus { border-color: var(--accent); box-shadow: 0 0 12px rgba(59, 130, 246, 0.1); }

        .btn-submit {
            background: linear-gradient(135deg, var(--accent) 0%, #0d9488 100%);
            color: #0b0f19;
            border: none;
            border-radius: 12px;
            padding: 14px 20px;
            font-weight: 800;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            cursor: pointer;
            width: 100%;
            transition: all 0.3s;
        }

        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(45, 212, 191, 0.3); }
        .btn-submit.btn-success { background: linear-gradient(135deg, var(--success) 0%, #059669 100%); color: white; }
        .btn-submit.btn-success:hover { box-shadow: 0 8px 20px rgba(52, 211, 153, 0.3); }

        .debt-table { width: 100%; border-collapse: collapse; text-align: left; }
        .debt-table th {
            color: var(--text-muted);
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--border-color);
        }
        .debt-table td { padding: 14px 0; border-bottom: 1px solid rgba(255, 255, 255, 0.03); font-size: 13px; }

        .btn-quick-action {
            background: rgba(255,255,255,0.03);
            border: 1px solid var(--border-color);
            color: var(--text-main);
            border-radius: 8px;
            padding: 6px 12px;
            font-size: 11px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        .btn-quick-action:hover { background: var(--accent-glow); border-color: var(--accent); color: var(--accent); }

        .details-drawer {
            display: none;
            background: rgba(255,255,255,0.012);
            border: 1px solid rgba(255,255,255,0.03);
            border-radius: 16px;
            padding: 20px;
            margin-top: 10px;
            animation: fadeIn 0.3s ease;
        }

        .view-section { display: block; }

        input::-webkit-outer-spin-button,
        input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        input[type=number] {
            -moz-appearance: textfield;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes slideIn {
            from { transform: translateX(120%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
    </style>
</head>
<body>
    <div class="app-container">
