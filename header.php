<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'Adam Car Accessories' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f0f2f5; }
        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, #1a1a2e 0%, #16213e 100%);
            position: fixed;
            height: 100vh;
            padding: 20px 0;
            overflow-y: auto;
            z-index: 100;
        }
        .sidebar-header { text-align: center; padding: 0 20px 20px; border-bottom: 1px solid rgba(255,255,255,0.1); margin-bottom: 20px; }
        .sidebar-header h3 { color: #FFD700; font-weight: 700; }
        .sidebar-header p { color: #94a3b8; font-size: 0.75rem; }
        .sidebar-menu { padding: 0 15px; }
        .menu-item { display: flex; align-items: center; gap: 12px; padding: 12px 18px; margin: 5px 0; color: #cbd5e1; text-decoration: none; border-radius: 12px; transition: 0.3s; }
        .menu-item:hover { background: rgba(255,255,255,0.1); color: white; transform: translateX(5px); }
        .menu-item i { width: 22px; }
        .menu-item.active { background: linear-gradient(135deg, #4f46e5, #4338ca); color: white; }
        .menu-divider { height: 1px; background: rgba(255,255,255,0.1); margin: 15px 18px; }
        .main-content { margin-left: 280px; padding: 20px; min-height: 100vh; }
        .top-bar { background: white; border-radius: 16px; padding: 15px 25px; margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .page-title { font-size: 1.5rem; font-weight: 700; color: #1e293b; margin: 0; }
        .user-avatar { width: 40px; height: 40px; background: linear-gradient(135deg, #4f46e5, #4338ca); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; }
        .stat-card { background: white; border-radius: 20px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); transition: 0.3s; }
        .stat-card:hover { transform: translateY(-3px); box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        .data-table { background: white; border-radius: 20px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .data-table th { background: #f8fafc; padding: 15px; font-weight: 600; border-bottom: 2px solid #e2e8f0; }
        .data-table td { padding: 12px 15px; border-bottom: 1px solid #e2e8f0; vertical-align: middle; }
        .data-table tr:hover { background: #f8fafc; }
        .btn-edit { background: #f59e0b; color: white; padding: 6px 12px; border-radius: 8px; text-decoration: none; font-size: 0.75rem; margin: 2px; display: inline-flex; align-items: center; gap: 5px; border: none; cursor: pointer; }
        .btn-edit:hover { background: #d97706; color: white; }
        .btn-delete { background: #ef4444; color: white; padding: 6px 12px; border-radius: 8px; text-decoration: none; font-size: 0.75rem; margin: 2px; display: inline-flex; align-items: center; gap: 5px; border: none; cursor: pointer; }
        .btn-delete:hover { background: #dc2626; color: white; }
        .btn-permissions { background: #8b5cf6; color: white; padding: 6px 12px; border-radius: 8px; text-decoration: none; font-size: 0.75rem; margin: 2px; display: inline-flex; align-items: center; gap: 5px; border: none; cursor: pointer; }
        .btn-permissions:hover { background: #7c3aed; color: white; }
        .btn-add { background: #10b981; color: white; padding: 8px 16px; border-radius: 10px; text-decoration: none; font-size: 0.85rem; display: inline-flex; align-items: center; gap: 8px; border: none; cursor: pointer; }
        .btn-add:hover { background: #059669; color: white; }
        .badge-active { background: #d1fae5; color: #059669; padding: 4px 12px; border-radius: 30px; font-size: 0.7rem; display: inline-block; }
        .badge-inactive { background: #fee2e2; color: #dc2626; padding: 4px 12px; border-radius: 30px; font-size: 0.7rem; display: inline-block; }
        @media (max-width: 992px) {
            .sidebar { transform: translateX(-100%); position: fixed; z-index: 1000; }
            .main-content { margin-left: 0; }
            .sidebar.active { transform: translateX(0); }
        }
    </style>
</head>
<body>