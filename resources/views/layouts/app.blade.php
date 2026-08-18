<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'ERP') — TradePro</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- Vite Assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- ApexCharts -->
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --primary-light: #818cf8;
            --sidebar-bg: #0f172a;
            --sidebar-hover: #1e293b;
            --sidebar-active: #6366f1;
            --topbar-bg: #ffffff;
            --body-bg: #f1f5f9;
            --card-bg: #ffffff;
            --text-primary: #0f172a;
            --text-secondary: #64748b;
            --border: #e2e8f0;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #3b82f6;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background: var(--body-bg); color: var(--text-primary); display: flex; min-height: 100vh; }

        #sidebar {
            width: 260px; min-height: 100vh; background: var(--sidebar-bg);
            position: fixed; left: 0; top: 0; bottom: 0;
            display: flex; flex-direction: column; z-index: 100;
            transition: width 0.3s ease; overflow: hidden;
        }
        #sidebar.collapsed { width: 64px; }

        .sidebar-brand { padding: 20px 16px; display: flex; align-items: center; gap: 12px; border-bottom: 1px solid #1e293b; }
        .sidebar-brand .logo { width: 36px; height: 36px; background: linear-gradient(135deg, var(--primary), var(--primary-dark)); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 16px; flex-shrink: 0; }
        .sidebar-brand .brand-text { color: white; font-weight: 700; font-size: 18px; white-space: nowrap; }
        .sidebar-brand .brand-sub  { color: #94a3b8; font-size: 11px; white-space: nowrap; }

        .sidebar-nav { flex: 1; overflow-y: auto; padding: 12px 8px; }
        .sidebar-nav::-webkit-scrollbar { width: 4px; }
        .sidebar-nav::-webkit-scrollbar-thumb { background: #334155; border-radius: 4px; }

        .nav-section-title { color: #475569; font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; padding: 16px 12px 6px; white-space: nowrap; overflow: hidden; }
        .nav-item { display: flex; align-items: center; gap: 12px; padding: 10px 12px; border-radius: 8px; color: #94a3b8; text-decoration: none; transition: all 0.2s; margin-bottom: 2px; white-space: nowrap; }
        .nav-item:hover { background: var(--sidebar-hover); color: #e2e8f0; }
        .nav-item.active { background: var(--sidebar-active); color: white; }
        .nav-item i { width: 18px; text-align: center; font-size: 15px; flex-shrink: 0; }
        .nav-item span { font-size: 13.5px; font-weight: 500; }

        .nav-group { margin-bottom: 2px; }
        .nav-group-toggle { display: flex; align-items: center; gap: 12px; padding: 10px 12px; border-radius: 8px; color: #94a3b8; cursor: pointer; transition: all 0.2s; white-space: nowrap; }
        .nav-group-toggle:hover { background: var(--sidebar-hover); color: #e2e8f0; }
        .nav-group-toggle i { width: 18px; text-align: center; font-size: 15px; flex-shrink: 0; }
        .nav-group-toggle .arrow { margin-left: auto; font-size: 11px; transition: transform 0.2s; }
        .nav-group-toggle.open .arrow { transform: rotate(180deg); }
        .nav-sub { padding-left: 30px; overflow: hidden; max-height: 0; transition: max-height 0.35s ease; }
        .nav-sub.open { max-height: 500px; }
        .nav-sub .nav-item { font-size: 13px; color: #64748b; }
        .nav-sub .nav-item.active { background: #1e293b; color: var(--primary-light); }

        #main-content { margin-left: 260px; flex: 1; display: flex; flex-direction: column; min-height: 100vh; transition: margin-left 0.3s ease; }
        #main-content.expanded { margin-left: 64px; }

        .topbar { background: var(--topbar-bg); border-bottom: 1px solid var(--border); padding: 0 24px; height: 64px; display: flex; align-items: center; gap: 16px; position: sticky; top: 0; z-index: 50; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .topbar-toggle { background: none; border: none; cursor: pointer; color: var(--text-secondary); font-size: 18px; padding: 8px; border-radius: 8px; transition: all 0.2s; }
        .topbar-toggle:hover { background: var(--body-bg); color: var(--text-primary); }
        .topbar-title { font-weight: 600; font-size: 17px; color: var(--text-primary); }
        .topbar-spacer { flex: 1; }
        .topbar-right { display: flex; align-items: center; gap: 12px; }
        .topbar-badge { position: relative; background: none; border: none; cursor: pointer; color: var(--text-secondary); font-size: 18px; padding: 8px; border-radius: 8px; transition: all 0.2s; }
        .topbar-badge:hover { background: var(--body-bg); }
        .badge-dot { position: absolute; top: 6px; right: 6px; width: 8px; height: 8px; background: var(--danger); border-radius: 50%; border: 2px solid white; }

        .user-menu { display: flex; align-items: center; gap: 10px; cursor: pointer; padding: 6px 10px; border-radius: 10px; transition: all 0.2s; }
        .user-menu:hover { background: var(--body-bg); }
        .user-avatar { width: 36px; height: 36px; background: linear-gradient(135deg, var(--primary), var(--primary-dark)); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 14px; }
        .user-info .name { font-weight: 600; font-size: 13.5px; color: var(--text-primary); }
        .user-info .role { font-size: 11px; color: var(--text-secondary); text-transform: capitalize; }

        .page-content { padding: 24px; flex: 1; }

        .card { background: var(--card-bg); border-radius: 16px; border: 1px solid var(--border); box-shadow: 0 1px 3px rgba(0,0,0,0.04); overflow: hidden; }
        .card-header { padding: 16px 20px; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; }
        .card-header h3 { font-size: 15px; font-weight: 600; color: var(--text-primary); }
        .card-body { padding: 20px; }

        .stat-card { background: var(--card-bg); border-radius: 16px; border: 1px solid var(--border); padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); transition: transform 0.2s, box-shadow 0.2s; }
        .stat-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,0.08); }
        .stat-card .icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; margin-bottom: 16px; }
        .stat-card .value { font-size: 24px; font-weight: 700; color: var(--text-primary); }
        .stat-card .label { font-size: 13px; color: var(--text-secondary); margin-top: 4px; }

        .btn { display: inline-flex; align-items: center; gap: 8px; padding: 9px 18px; border-radius: 10px; font-size: 13.5px; font-weight: 500; cursor: pointer; border: none; text-decoration: none; transition: all 0.2s; white-space: nowrap; }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--primary-dark); transform: translateY(-1px); box-shadow: 0 4px 12px rgba(99,102,241,0.4); }
        .btn-secondary { background: var(--body-bg); color: var(--text-secondary); border: 1px solid var(--border); }
        .btn-secondary:hover { background: var(--border); color: var(--text-primary); }
        .btn-success { background: var(--success); color: white; }
        .btn-success:hover { background: #059669; }
        .btn-danger { background: var(--danger); color: white; }
        .btn-danger:hover { background: #dc2626; }
        .btn-warning { background: var(--warning); color: white; }
        .btn-warning:hover { background: #d97706; }
        .btn-sm { padding: 5px 12px; font-size: 12px; border-radius: 8px; }
        .btn-icon { width: 32px; height: 32px; padding: 0; justify-content: center; }

        .badge { display: inline-flex; align-items: center; gap: 4px; padding: 3px 10px; border-radius: 20px; font-size: 11.5px; font-weight: 500; }
        .badge-draft     { background: #f1f5f9; color: #64748b; }
        .badge-confirmed { background: #dbeafe; color: #1d4ed8; }
        .badge-done      { background: #d1fae5; color: #065f46; }
        .badge-cancelled { background: #fee2e2; color: #991b1b; }
        .badge-pending   { background: #fef3c7; color: #92400e; }
        .badge-waiting_approval { background: #ede9fe; color: #6d28d9; }
        .badge-posted    { background: #d1fae5; color: #065f46; }
        .badge-unpaid    { background: #fee2e2; color: #991b1b; }
        .badge-partial   { background: #fef3c7; color: #92400e; }
        .badge-paid      { background: #d1fae5; color: #065f46; }

        .table-responsive { overflow-x: auto; }
        .erp-table { width: 100%; border-collapse: collapse; font-size: 13.5px; }
        .erp-table th { background: #f8fafc; padding: 12px 16px; text-align: left; font-weight: 600; font-size: 12px; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid var(--border); }
        .erp-table td { padding: 13px 16px; border-bottom: 1px solid #f1f5f9; color: var(--text-primary); }
        .erp-table tr:hover td { background: #f8fafc; }
        .erp-table tr:last-child td { border-bottom: none; }

        .form-label { display: block; font-size: 13px; font-weight: 500; color: var(--text-primary); margin-bottom: 6px; }
        .form-control { width: 100%; padding: 9px 14px; border: 1px solid var(--border); border-radius: 10px; font-size: 14px; font-family: 'Inter', sans-serif; color: var(--text-primary); background: white; transition: border-color 0.2s, box-shadow 0.2s; }
        .form-control:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(99,102,241,0.15); }
        .form-control.is-invalid { border-color: var(--danger); }
        .invalid-feedback { font-size: 12px; color: var(--danger); margin-top: 4px; }
        .form-row { display: grid; gap: 16px; }
        .form-row-2 { grid-template-columns: 1fr 1fr; }
        .form-row-3 { grid-template-columns: 1fr 1fr 1fr; }
        .form-group { margin-bottom: 16px; }

        .alert { padding: 14px 16px; border-radius: 12px; font-size: 14px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .alert-danger   { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .alert-warning  { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
        .alert-info     { background: #dbeafe; color: #1e40af; border: 1px solid #bfdbfe; }

        .page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; }
        .page-header h1 { font-size: 24px; font-weight: 700; }
        .page-header p  { color: var(--text-secondary); font-size: 14px; margin-top: 4px; }

        .grid { display: grid; gap: 16px; }
        .grid-2 { grid-template-columns: repeat(2, 1fr); }
        .grid-3 { grid-template-columns: repeat(3, 1fr); }
        .grid-4 { grid-template-columns: repeat(4, 1fr); }

        @media (max-width: 768px) {
            #sidebar { width: 0; }
            #main-content { margin-left: 0; }
            .grid-4 { grid-template-columns: repeat(2, 1fr); }
            .grid-3 { grid-template-columns: 1fr; }
            .form-row-2, .form-row-3 { grid-template-columns: 1fr; }
        }

        @keyframes fadeInUp { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
        .animate-in { animation: fadeInUp 0.35s ease forwards; }

        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
    </style>
    @stack('styles')
</head>
<body>

<aside id="sidebar">
    <div class="sidebar-brand">
        <div class="logo">T</div>
        <div>
            <div class="brand-text">TradePro</div>
            <div class="brand-sub">ERP System</div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <i class="fa-solid fa-gauge-high"></i><span>Dashboard</span>
        </a>

        <div class="nav-section-title">Master Data</div>
        <div class="nav-group" x-data="{ open: {{ request()->is('master/*') ? 'true' : 'false' }} }">
            <div class="nav-group-toggle" :class="{ open: open }" @click="open = !open">
                <i class="fa-solid fa-database"></i><span>Master Data</span>
                <i class="fa-solid fa-chevron-down arrow"></i>
            </div>
            <div class="nav-sub" :class="{ open: open }">
                <a href="{{ route('master.products.index') }}" class="nav-item {{ request()->routeIs('master.products.*') ? 'active' : '' }}"><i class="fa-solid fa-box"></i><span>Produk</span></a>
                <a href="{{ route('master.warehouses.index') }}" class="nav-item {{ request()->routeIs('master.warehouses.*') ? 'active' : '' }}"><i class="fa-solid fa-warehouse"></i><span>Gudang</span></a>
                <a href="{{ route('master.suppliers.index') }}" class="nav-item {{ request()->routeIs('master.suppliers.*') ? 'active' : '' }}"><i class="fa-solid fa-truck"></i><span>Supplier</span></a>
                <a href="{{ route('master.customers.index') }}" class="nav-item {{ request()->routeIs('master.customers.*') ? 'active' : '' }}"><i class="fa-solid fa-users"></i><span>Customer</span></a>
                @if(auth()->user()->isAdmin() || auth()->user()->isFinance())
                <a href="{{ route('master.chart-of-accounts.index') }}" class="nav-item {{ request()->routeIs('master.chart-of-accounts.*') ? 'active' : '' }}"><i class="fa-solid fa-book"></i><span>Chart of Accounts</span></a>
                @endif
                @if(auth()->user()->isAdmin())
                <a href="{{ route('master.users.index') }}" class="nav-item {{ request()->routeIs('master.users.*') ? 'active' : '' }}"><i class="fa-solid fa-user-gear"></i><span>Users</span></a>
                @endif
            </div>
        </div>

        <div class="nav-section-title">Pembelian</div>
        <div class="nav-group" x-data="{ open: {{ request()->is('purchase/*') ? 'true' : 'false' }} }">
            <div class="nav-group-toggle" :class="{ open: open }" @click="open = !open">
                <i class="fa-solid fa-cart-shopping"></i><span>Purchase</span>
                <i class="fa-solid fa-chevron-down arrow"></i>
            </div>
            <div class="nav-sub" :class="{ open: open }">
                <a href="{{ route('purchase.orders.index') }}" class="nav-item {{ request()->routeIs('purchase.orders.*') ? 'active' : '' }}"><i class="fa-solid fa-file-lines"></i><span>Purchase Order</span></a>
                <a href="{{ route('purchase.goods-receipts.index') }}" class="nav-item {{ request()->routeIs('purchase.goods-receipts.*') ? 'active' : '' }}"><i class="fa-solid fa-boxes-stacked"></i><span>Penerimaan Barang</span></a>
                <a href="{{ route('purchase.invoices.index') }}" class="nav-item {{ request()->routeIs('purchase.invoices.*') ? 'active' : '' }}"><i class="fa-solid fa-file-invoice"></i><span>Invoice Pembelian</span></a>
                <a href="{{ route('purchase.returns.index') }}" class="nav-item {{ request()->routeIs('purchase.returns.*') ? 'active' : '' }}"><i class="fa-solid fa-rotate-left"></i><span>Retur Pembelian</span></a>
                <a href="{{ route('purchase.payments.index') }}" class="nav-item {{ request()->routeIs('purchase.payments.*') ? 'active' : '' }}"><i class="fa-solid fa-money-check"></i><span>Bayar Hutang</span></a>
            </div>
        </div>

        <div class="nav-section-title">Inventori</div>
        <div class="nav-group" x-data="{ open: {{ request()->is('inventory/*') ? 'true' : 'false' }} }">
            <div class="nav-group-toggle" :class="{ open: open }" @click="open = !open">
                <i class="fa-solid fa-cubes"></i><span>Inventory</span>
                <i class="fa-solid fa-chevron-down arrow"></i>
            </div>
            <div class="nav-sub" :class="{ open: open }">
                <a href="{{ route('inventory.stock-summary') }}" class="nav-item {{ request()->routeIs('inventory.stock-summary') ? 'active' : '' }}"><i class="fa-solid fa-chart-bar"></i><span>Ringkasan Stok</span></a>
                <a href="{{ route('inventory.stock-card') }}" class="nav-item {{ request()->routeIs('inventory.stock-card') ? 'active' : '' }}"><i class="fa-solid fa-rectangle-list"></i><span>Kartu Stok</span></a>
                <a href="{{ route('inventory.transfers.index') }}" class="nav-item {{ request()->routeIs('inventory.transfers.*') ? 'active' : '' }}"><i class="fa-solid fa-arrow-right-arrow-left"></i><span>Transfer Gudang</span></a>
                <a href="{{ route('inventory.opname.index') }}" class="nav-item {{ request()->routeIs('inventory.opname.*') ? 'active' : '' }}"><i class="fa-solid fa-clipboard-check"></i><span>Stock Opname</span></a>
                <a href="{{ route('inventory.dispositions.index') }}" class="nav-item {{ request()->routeIs('inventory.dispositions.*') ? 'active' : '' }}"><i class="fa-solid fa-box-archive"></i><span>Disposisi Karantina</span></a>
            </div>
        </div>

        <div class="nav-section-title">Penjualan</div>
        <div class="nav-group" x-data="{ open: {{ request()->is('sales/*') ? 'true' : 'false' }} }">
            <div class="nav-group-toggle" :class="{ open: open }" @click="open = !open">
                <i class="fa-solid fa-store"></i><span>Sales</span>
                <i class="fa-solid fa-chevron-down arrow"></i>
            </div>
            <div class="nav-sub" :class="{ open: open }">
                <a href="{{ route('sales.orders.index') }}" class="nav-item {{ request()->routeIs('sales.orders.*') ? 'active' : '' }}"><i class="fa-solid fa-file-lines"></i><span>Sales Order</span></a>
                <a href="{{ route('sales.deliveries.index') }}" class="nav-item {{ request()->routeIs('sales.deliveries.*') ? 'active' : '' }}"><i class="fa-solid fa-truck-fast"></i><span>Surat Jalan</span></a>
                <a href="{{ route('sales.invoices.index') }}" class="nav-item {{ request()->routeIs('sales.invoices.*') ? 'active' : '' }}"><i class="fa-solid fa-file-invoice-dollar"></i><span>Invoice Penjualan</span></a>
                <a href="{{ route('sales.returns.index') }}" class="nav-item {{ request()->routeIs('sales.returns.*') ? 'active' : '' }}"><i class="fa-solid fa-rotate-left"></i><span>Retur Penjualan</span></a>
                <a href="{{ route('sales.payments.index') }}" class="nav-item {{ request()->routeIs('sales.payments.*') ? 'active' : '' }}"><i class="fa-solid fa-hand-holding-dollar"></i><span>Terima Piutang</span></a>
            </div>
        </div>

        <div class="nav-section-title">Akuntansi</div>
        <div class="nav-group" x-data="{ open: {{ request()->is('accounting/*') ? 'true' : 'false' }} }">
            <div class="nav-group-toggle" :class="{ open: open }" @click="open = !open">
                <i class="fa-solid fa-calculator"></i><span>Akuntansi</span>
                <i class="fa-solid fa-chevron-down arrow"></i>
            </div>
            <div class="nav-sub" :class="{ open: open }">
                <a href="{{ route('accounting.journals.index') }}" class="nav-item {{ request()->routeIs('accounting.journals.*') ? 'active' : '' }}"><i class="fa-solid fa-book-open"></i><span>Jurnal Umum</span></a>
                <a href="{{ route('accounting.reports.ledger') }}" class="nav-item {{ request()->routeIs('accounting.reports.ledger') ? 'active' : '' }}"><i class="fa-solid fa-book"></i><span>Buku Besar</span></a>
                <a href="{{ route('accounting.reports.trial-balance') }}" class="nav-item {{ request()->routeIs('accounting.reports.trial-balance') ? 'active' : '' }}"><i class="fa-solid fa-scale-balanced"></i><span>Neraca Saldo</span></a>
                <a href="{{ route('accounting.reports.cash-flow') }}" class="nav-item {{ request()->routeIs('accounting.reports.cash-flow') ? 'active' : '' }}"><i class="fa-solid fa-money-bill-transfer"></i><span>Arus Kas</span></a>
                <a href="{{ route('accounting.reports.receivables') }}" class="nav-item {{ request()->routeIs('accounting.reports.receivables') ? 'active' : '' }}"><i class="fa-solid fa-file-invoice-dollar"></i><span>Laporan Piutang</span></a>
                <a href="{{ route('accounting.reports.payables') }}" class="nav-item {{ request()->routeIs('accounting.reports.payables') ? 'active' : '' }}"><i class="fa-solid fa-file-invoice"></i><span>Laporan Hutang</span></a>
                <a href="{{ route('accounting.reports.profit-loss') }}" class="nav-item {{ request()->routeIs('accounting.reports.profit-loss') ? 'active' : '' }}"><i class="fa-solid fa-chart-line"></i><span>Laba / Rugi</span></a>
                <a href="{{ route('accounting.reports.balance-sheet') }}" class="nav-item {{ request()->routeIs('accounting.reports.balance-sheet') ? 'active' : '' }}"><i class="fa-solid fa-scale-unbalanced"></i><span>Neraca</span></a>
                <a href="{{ route('accounting.reports.stock-valuation') }}" class="nav-item {{ request()->routeIs('accounting.reports.stock-valuation') ? 'active' : '' }}"><i class="fa-solid fa-boxes-stacked"></i><span>Valuasi Stok</span></a>
            </div>
        </div>

        @if(auth()->user()->isAdmin() || auth()->user()->isFinance())
        <div class="nav-section-title">Approval</div>
        <a href="{{ route('approvals.index') }}" class="nav-item {{ request()->routeIs('approvals.*') ? 'active' : '' }}">
            <i class="fa-solid fa-circle-check"></i><span>Approval Request</span>
        </a>
        @endif
    </nav>

    <div style="padding: 12px 8px; border-top: 1px solid #1e293b;">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="nav-item" style="width:100%; background:none; border:none; cursor:pointer; text-align:left;">
                <i class="fa-solid fa-right-from-bracket"></i><span>Logout</span>
            </button>
        </form>
    </div>
</aside>

<div id="main-content" x-data="{ sidebarCollapsed: false }" :class="{ 'expanded': sidebarCollapsed }">
    <header class="topbar">
        <button class="topbar-toggle" @click="sidebarCollapsed = !sidebarCollapsed; document.getElementById('sidebar').classList.toggle('collapsed', sidebarCollapsed);">
            <i class="fa-solid fa-bars"></i>
        </button>
        <span class="topbar-title">@yield('page-title', 'Dashboard')</span>
        <div class="topbar-spacer"></div>
        <div class="topbar-right">
            <button class="topbar-badge"><i class="fa-regular fa-bell"></i><span class="badge-dot"></span></button>
            <div class="user-menu" x-data="{ open: false }" @click="open = !open" @click.outside="open = false" style="position:relative;">
                <div class="user-avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
                <div class="user-info">
                    <div class="name">{{ auth()->user()->name }}</div>
                    <div class="role">{{ auth()->user()->role }}</div>
                </div>
                <i class="fa-solid fa-chevron-down" style="font-size:11px; color: var(--text-secondary);"></i>
                <div x-show="open" x-transition style="position:absolute; top:calc(100% + 8px); right:0; background:white; border-radius:12px; border:1px solid var(--border); box-shadow:0 8px 24px rgba(0,0,0,0.12); min-width:180px; padding:8px; z-index:200;">
                    <a href="{{ route('profile.edit') }}" style="display:flex; align-items:center; gap:10px; padding:8px 12px; border-radius:8px; color:var(--text-primary); text-decoration:none; font-size:13.5px;" onmouseover="this.style.background='var(--body-bg)'" onmouseout="this.style.background='none'">
                        <i class="fa-solid fa-user" style="width:16px;"></i> Profil Saya
                    </a>
                    <hr style="border:none; border-top:1px solid var(--border); margin:6px 0;">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" style="display:flex; align-items:center; gap:10px; padding:8px 12px; border-radius:8px; color:var(--danger); background:none; border:none; cursor:pointer; font-size:13.5px; width:100%; font-family:'Inter',sans-serif;" onmouseover="this.style.background='#fee2e2'" onmouseout="this.style.background='none'">
                            <i class="fa-solid fa-right-from-bracket" style="width:16px;"></i> Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </header>

    <div style="padding: 16px 24px 0;">
        @if(session('success'))
            <div class="alert alert-success animate-in"><i class="fa-solid fa-circle-check"></i> {{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger animate-in"><i class="fa-solid fa-circle-xmark"></i> {{ session('error') }}</div>
        @endif
        @if(session('info'))
            <div class="alert alert-info animate-in"><i class="fa-solid fa-circle-info"></i> {{ session('info') }}</div>
        @endif
        @if(session('warning'))
            <div class="alert alert-warning animate-in"><i class="fa-solid fa-triangle-exclamation"></i> {{ session('warning') }}</div>
        @endif
    </div>

    <main class="page-content">@yield('content')</main>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('[data-confirm-delete]').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const form = document.getElementById(this.dataset.confirmDelete);
            Swal.fire({ title: 'Konfirmasi Hapus', text: 'Data yang dihapus tidak dapat dikembalikan!', icon: 'warning', showCancelButton: true, confirmButtonColor: '#ef4444', cancelButtonColor: '#64748b', confirmButtonText: 'Ya, Hapus!', cancelButtonText: 'Batal' }).then((r) => { if (r.isConfirmed) form.submit(); });
        });
    });
    setTimeout(() => { document.querySelectorAll('.alert').forEach(el => { el.style.opacity='0'; el.style.transition='opacity 0.5s'; setTimeout(()=>el.remove(),500); }); }, 5000);
});
</script>
@stack('scripts')
</body>
</html>
