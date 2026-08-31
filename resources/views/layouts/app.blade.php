<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — TradePro ERP</title>

    <!-- Tailwind CSS CDN & Configuration Script -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script id="tailwind-config">
    tailwind.config = {
        theme: {
            extend: {
                "colors": {
                    "primary": "#03193c",
                    "primary-container": "#1b2e52",
                    "on-primary": "#ffffff",
                    "on-primary-container": "#8496c0",
                    "primary-fixed": "#d8e2ff",
                    "primary-fixed-dim": "#b4c6f3",
                    "on-primary-fixed-variant": "#34466c",
                    "secondary": "#455e90",
                    "secondary-container": "#aec6ff",
                    "on-secondary-container": "#395283",
                    "slate-bg": "#F5F6F8",
                    "paper": "#FFFFFF",
                    "surface": "#faf9ff",
                    "surface-variant": "#d9e2ff",
                    "surface-dim": "#cddafc",
                    "surface-container": "#e9edff",
                    "surface-container-low": "#f1f3ff",
                    "on-surface": "#0e1b35",
                    "on-surface-variant": "#44474e",
                    "border-light": "#E2E8F0",
                    "border-medium": "#CBD5E1",
                    "outline": "#75777f",
                    "outline-variant": "#c5c6cf",
                    "status-active-bg": "#DBE7FB", "status-active-text": "#1D4ED8",
                    "status-success-bg": "#DCFCE3", "status-success-text": "#166534",
                    "status-pending-bg": "#FBEBD2", "status-pending-text": "#92640B",
                    "status-danger-bg": "#FDE2E1", "status-danger-text": "#B91C1C",
                    "status-neutral-bg": "#F3F4F6", "status-neutral-text": "#6B7280",
                    "tertiary-container": "#432900", "on-tertiary": "#ffffff",
                    "error": "#ba1a1a"
                },
                "borderRadius": { "DEFAULT": "0.25rem", "lg": "0.5rem", "xl": "0.75rem", "full": "9999px" },
                "spacing": {
                    "unit-xs": "4px", "unit-sm": "8px", "unit-md": "16px", "unit-lg": "24px", "unit-xl": "32px",
                    "gutter": "16px", "page-margin": "24px", "sidebar-width": "240px", "header-height": "56px"
                },
                "fontFamily": {
                    "headline-lg": ["Public Sans", "sans-serif"],
                    "headline-md": ["Public Sans", "sans-serif"],
                    "title-sm": ["Public Sans", "sans-serif"],
                    "label-xs": ["Public Sans", "sans-serif"],
                    "body-base": ["Inter", "sans-serif"],
                    "body-medium": ["Inter", "sans-serif"],
                    "body-sm": ["Inter", "sans-serif"],
                    "table-data": ["Inter", "sans-serif"],
                    "stat-number": ["Inter", "sans-serif"]
                }
            }
        }
    }
    </script>

    <!-- Google Fonts & Material Symbols -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&family=Public+Sans:wght@100..900&display=swap" rel="stylesheet"/>

    <!-- FontAwesome Compatibility -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Alpine.js Collapse Plugin MUST BE BEFORE Alpine Core -->
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- ApexCharts -->
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Direct CSS Rules for All Legacy Forms, Cards, Tables, Buttons, and Layouts -->
    <style>
        .material-symbols-outlined {
            font-family: 'Material Symbols Outlined';
            font-weight: normal;
            font-style: normal;
            font-size: 20px;
            line-height: 1;
            letter-spacing: normal;
            text-transform: none;
            display: inline-block;
            white-space: nowrap;
            word-wrap: normal;
            direction: ltr;
            -webkit-font-smoothing: antialiased;
        }
        ::-webkit-scrollbar { display: none; }

        /* Global Layout Components Overrides */
        .page-header {
            display: flex !important;
            flex-direction: row !important;
            align-items: center !important;
            justify-content: space-between !important;
            gap: 1rem !important;
            margin-bottom: 1.5rem !important;
            padding-bottom: 1rem !important;
            border-bottom: 1px solid #E2E8F0 !important;
            width: 100% !important;
        }
        .page-header h1 {
            font-size: 1.5rem !important;
            font-weight: 700 !important;
            color: #0e1b35 !important;
            margin: 0 !important;
            font-family: 'Public Sans', sans-serif !important;
        }
        .page-header p {
            font-size: 0.875rem !important;
            color: #44474e !important;
            margin: 0.25rem 0 0 0 !important;
            font-family: 'Inter', sans-serif !important;
        }

        .card {
            background-color: #ffffff !important;
            border-radius: 0.5rem !important;
            border: 1px solid #E2E8F0 !important;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05) !important;
            padding: 1.5rem !important;
            margin-bottom: 1.5rem !important;
            width: 100% !important;
            box-sizing: border-box !important;
        }
        .card-header {
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            padding-bottom: 1rem !important;
            margin-bottom: 1rem !important;
            border-bottom: 1px solid #E2E8F0 !important;
        }
        .card-header h3, .card-title {
            font-size: 1rem !important;
            font-weight: 700 !important;
            color: #0e1b35 !important;
            margin: 0 !important;
            font-family: 'Public Sans', sans-serif !important;
        }

        /* Form Controls & Vertical Label Stacking */
        .form-group {
            display: flex !important;
            flex-direction: column !important;
            margin-bottom: 1.25rem !important;
            width: 100% !important;
            box-sizing: border-box !important;
        }
        .form-label, label {
            display: block !important;
            width: 100% !important;
            margin-bottom: 0.375rem !important;
            font-size: 0.75rem !important;
            font-weight: 700 !important;
            letter-spacing: 0.05em !important;
            text-transform: uppercase !important;
            color: #0e1b35 !important;
            font-family: 'Public Sans', sans-serif !important;
        }
        .form-control, 
        main input[type="text"], 
        main input[type="number"], 
        main input[type="email"], 
        main input[type="password"], 
        main input[type="date"], 
        main select, 
        main textarea {
            display: block !important;
            width: 100% !important;
            height: 40px !important;
            padding: 0.5rem 0.875rem !important;
            border-radius: 0.375rem !important;
            border: 1px solid #CBD5E1 !important;
            background-color: #faf9ff !important;
            font-size: 0.875rem !important;
            color: #0e1b35 !important;
            box-sizing: border-box !important;
            font-family: 'Inter', sans-serif !important;
        }
        /* Search Input Icon Overlap Prevention */
        .search-input,
        .search-box input,
        .has-search-icon input,
        input.search-input,
        main input.search-input,
        main input[name="q"],
        main input[type="search"] {
            padding-left: 2.5rem !important;
        }
        main textarea, textarea.form-control {
            height: auto !important;
            min-height: 90px !important;
            padding: 0.625rem 0.875rem !important;
        }
        main select, select.form-control {
            cursor: pointer !important;
        }
        /* Filter Bar Specific Overrides: Prevent selects/dates from forcing 100% width and overlapping */
        .erp-filter-bar select,
        .erp-filter-bar input[type="date"],
        .filter-bar select,
        .filter-bar input[type="date"],
        .list-filter-form select,
        .list-filter-form input[type="date"] {
            width: auto !important;
            display: inline-block !important;
            min-width: 140px;
            max-width: 100%;
        }
        .erp-filter-bar select[name="per_page"],
        .filter-bar select[name="per_page"],
        .list-filter-form select[name="per_page"] {
            min-width: 70px !important;
            width: 75px !important;
        }
        .form-text {
            display: block !important;
            font-size: 0.75rem !important;
            color: #6B7280 !important;
            margin-top: 0.25rem !important;
        }

        /* Checkbox */
        .form-check, .checkbox-group {
            display: flex !important;
            align-items: center !important;
            gap: 0.625rem !important;
            margin-top: 0.5rem !important;
            margin-bottom: 0.5rem !important;
        }
        .form-check-input, main input[type="checkbox"] {
            width: 1.125rem !important;
            height: 1.125rem !important;
            margin: 0 !important;
            display: inline-block !important;
            cursor: pointer !important;
        }
        .form-check-label {
            display: inline-block !important;
            margin-bottom: 0 !important;
            font-size: 0.875rem !important;
            font-weight: 500 !important;
            text-transform: none !important;
            letter-spacing: normal !important;
            color: #0e1b35 !important;
        }

        /* SweetAlert2 Input Reset */
        .swal2-container input:not(.swal2-input),
        .swal2-container textarea:not(.swal2-textarea),
        .swal2-container select:not(.swal2-select),
        .swal2-container .swal2-input[style*="display: none"],
        .swal2-container .swal2-file[style*="display: none"],
        .swal2-container .swal2-range[style*="display: none"],
        .swal2-container .swal2-select[style*="display: none"],
        .swal2-container .swal2-radio[style*="display: none"],
        .swal2-container .swal2-checkbox[style*="display: none"],
        .swal2-container .swal2-textarea[style*="display: none"] {
            display: none !important;
        }

        /* Multi-Column Form Grid Layouts */
        .form-row, .form-row-2, .grid-2 {
            display: grid !important;
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            gap: 1.25rem !important;
            width: 100% !important;
            margin-bottom: 1rem !important;
        }
        .form-row-3, .grid-3 {
            display: grid !important;
            grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
            gap: 1.25rem !important;
            width: 100% !important;
            margin-bottom: 1rem !important;
        }
        .form-row-4, .grid-4 {
            display: grid !important;
            grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
            gap: 1.25rem !important;
            width: 100% !important;
            margin-bottom: 1rem !important;
        }
        .grid-5 {
            display: grid !important;
            grid-template-columns: repeat(5, minmax(0, 1fr)) !important;
            gap: 1.25rem !important;
            width: 100% !important;
            margin-bottom: 1rem !important;
        }
        @media (max-width: 1280px) {
            .grid-5 {
                grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
            }
        }
        @media (max-width: 1024px) {
            .form-row-4, .grid-4 {
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            }
            .form-row-3, .grid-3 {
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            }
            .grid-5 {
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            }
        }
        @media (max-width: 768px) {
            .form-row, .form-row-2, .form-row-3, .form-row-4, .grid-2, .grid-3, .grid-4, .grid-5 {
                grid-template-columns: 1fr !important;
            }
        }

        /* Stat Card Styles */
        .stat-card {
            background-color: #ffffff !important;
            border-radius: 0.5rem !important;
            border: 1px solid #E2E8F0 !important;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05) !important;
            padding: 1.25rem !important;
            display: flex !important;
            align-items: center !important;
            gap: 1rem !important;
            box-sizing: border-box !important;
            width: 100% !important;
            min-width: 0 !important;
            transition: transform 0.15s ease, box-shadow 0.15s ease !important;
        }
        .stat-card:hover {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1) !important;
        }
        .stat-card .stat-icon, .stat-card .icon {
            width: 44px !important;
            height: 44px !important;
            min-width: 44px !important;
            border-radius: 0.5rem !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            font-size: 1.25rem !important;
        }
        .stat-card .stat-info {
            display: flex !important;
            flex-direction: column !important;
            min-width: 0 !important;
            flex: 1 !important;
        }
        .stat-card .stat-label, .stat-card .label {
            font-size: 0.75rem !important;
            font-weight: 700 !important;
            color: #64748b !important;
            text-transform: uppercase !important;
            letter-spacing: 0.05em !important;
            margin-bottom: 0.25rem !important;
            font-family: 'Public Sans', sans-serif !important;
            white-space: nowrap !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
        }
        .stat-card .stat-value, .stat-card .value {
            font-size: 1.125rem !important;
            font-weight: 700 !important;
            color: #0e1b35 !important;
            font-family: 'Inter', sans-serif !important;
            word-break: break-word !important;
        }

        /* Detail Grid (Show pages) */
        .detail-grid {
            display: grid !important;
            grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
            gap: 1rem !important;
            padding: 1rem !important;
            background-color: #FAF9FF !important;
            border-radius: 0.5rem !important;
            border: 1px solid #E2E8F0 !important;
            margin-bottom: 1.5rem !important;
            width: 100% !important;
        }
        .detail-group {
            display: flex !important;
            flex-direction: column !important;
            gap: 0.25rem !important;
        }
        .detail-label {
            font-size: 0.6875rem !important;
            font-weight: 700 !important;
            color: #6B7280 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.05em !important;
        }
        .detail-value {
            font-size: 0.875rem !important;
            font-weight: 600 !important;
            color: #0e1b35 !important;
        }

        /* Table & Action Buttons */
        .table-responsive {
            overflow-x: auto !important;
            border-radius: 0.5rem !important;
            border: 1px solid #E2E8F0 !important;
            background-color: #ffffff !important;
            margin-bottom: 1.5rem !important;
            width: 100% !important;
        }
        .erp-table {
            width: 100% !important;
            border-collapse: collapse !important;
            min-width: 750px !important;
            text-align: left !important;
        }
        .erp-table thead {
            background-color: #F5F6F8 !important;
            border-bottom: 1px solid #CBD5E1 !important;
        }
        .erp-table th {
            padding: 0.875rem 1rem !important;
            font-size: 0.75rem !important;
            font-weight: 700 !important;
            color: #0e1b35 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.05em !important;
            white-space: nowrap !important;
            font-family: 'Public Sans', sans-serif !important;
        }
        .erp-table tbody tr {
            border-bottom: 1px solid #E2E8F0 !important;
            transition: background-color 0.15s ease !important;
        }
        .erp-table tbody tr:hover {
            background-color: rgba(245, 246, 248, 0.7) !important;
        }
        .erp-table td {
            padding: 0.875rem 1rem !important;
            font-size: 0.875rem !important;
            color: #0e1b35 !important;
            vertical-align: middle !important;
            font-family: 'Inter', sans-serif !important;
        }

        /* Buttons */
        .btn, button.btn, a.btn {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 0.5rem !important;
            padding: 0.5rem 1rem !important;
            border-radius: 0.375rem !important;
            font-size: 0.875rem !important;
            font-weight: 500 !important;
            font-family: 'Inter', sans-serif !important;
            text-decoration: none !important;
            white-space: nowrap !important;
            cursor: pointer !important;
            box-sizing: border-box !important;
            border: 1px solid transparent !important;
        }
        .btn-primary {
            background-color: #03193c !important;
            color: #ffffff !important;
            border-color: #03193c !important;
        }
        .btn-primary:hover {
            background-color: #1b2e52 !important;
        }
        .btn-secondary {
            background-color: #ffffff !important;
            color: #0e1b35 !important;
            border-color: #CBD5E1 !important;
        }
        .btn-secondary:hover {
            background-color: #F5F6F8 !important;
        }
        .btn-danger {
            background-color: #ba1a1a !important;
            color: #ffffff !important;
            border-color: #ba1a1a !important;
        }
        .btn-danger:hover {
            background-color: #93000a !important;
        }
        .btn-sm {
            padding: 0.375rem 0.75rem !important;
            font-size: 0.75rem !important;
        }
        .btn-icon {
            width: 32px !important;
            height: 32px !important;
            min-width: 32px !important;
            padding: 0 !important;
            border-radius: 0.375rem !important;
        }

        /* SweetAlert2 Modal Styling */
        .swal2-popup.erp-swal-modal {
            font-family: 'Inter', sans-serif !important;
            border-radius: 16px !important;
            padding: 24px !important;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.15), 0 10px 10px -5px rgba(0, 0, 0, 0.04) !important;
            border: 1px solid #E2E8F0 !important;
            width: 440px !important;
            max-width: 90vw !important;
        }
        .swal2-popup.erp-swal-modal .swal2-title {
            font-family: 'Public Sans', sans-serif !important;
            font-size: 1.25rem !important;
            font-weight: 700 !important;
            color: #0e1b35 !important;
            padding: 0 !important;
            margin-bottom: 6px !important;
        }
        .swal2-popup.erp-swal-modal .swal2-html-container {
            font-family: 'Inter', sans-serif !important;
            font-size: 0.875rem !important;
            color: #475569 !important;
            margin: 0 !important;
            padding: 0 !important;
            line-height: 1.5 !important;
        }
        .swal2-popup.erp-swal-modal .swal2-icon {
            margin: 4px auto 16px !important;
            transform: scale(0.85) !important;
        }
        .swal2-popup.erp-swal-modal .swal2-actions {
            margin-top: 20px !important;
            gap: 10px !important;
            width: 100% !important;
            justify-content: center !important;
        }
        .swal2-popup.erp-swal-modal .swal2-confirm {
            background-color: #dc2626 !important;
            color: #ffffff !important;
            border-radius: 8px !important;
            font-size: 0.875rem !important;
            font-weight: 600 !important;
            padding: 10px 20px !important;
            border: none !important;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05) !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: 6px !important;
            cursor: pointer !important;
        }
        .swal2-popup.erp-swal-modal .swal2-confirm:hover {
            background-color: #b91c1c !important;
        }
        .swal2-popup.erp-swal-modal .swal2-cancel {
            background-color: #f1f5f9 !important;
            color: #334155 !important;
            border: 1px solid #cbd5e1 !important;
            border-radius: 8px !important;
            font-size: 0.875rem !important;
            font-weight: 600 !important;
            padding: 10px 20px !important;
            cursor: pointer !important;
        }
        .swal2-popup.erp-swal-modal .swal2-cancel:hover {
            background-color: #e2e8f0 !important;
            color: #0f172a !important;
        }
    </style>
    @stack('styles')
</head>
<body class="bg-[#F5F6F8] font-body-base text-[#0e1b35] min-h-screen">

    <!-- ========================================================= -->
    <!-- SIDEBAR NAVIGATION (240px Fixed Navy #03193c) -->
    <!-- ========================================================= -->
    <aside class="fixed left-0 top-0 h-full w-[240px] bg-[#03193c] z-50 flex flex-col overflow-y-auto border-r border-[#1b2e52]">
        <!-- Brand Header -->
        <div class="flex items-center gap-3 px-6 h-[56px] border-b border-[#1b2e52] mb-3">
            <div class="h-8 w-8 rounded bg-[#1b2e52] flex items-center justify-center text-white font-bold text-lg">T</div>
            <span class="text-white font-bold text-lg tracking-tight font-sans">TradePro</span>
        </div>

        <nav class="flex-1 flex flex-col gap-1 px-2">
            <!-- Dashboard Link -->
            <a href="{{ route('dashboard') }}" 
               class="flex items-center px-4 py-2 rounded transition-all {{ request()->routeIs('dashboard') ? 'bg-[#1b2e52] text-white font-bold' : 'text-white/70 hover:bg-[#1b2e52] hover:text-white' }}">
                <span class="material-symbols-outlined mr-3">dashboard</span>
                <span class="text-sm font-medium">Dashboard</span>
            </a>

            <!-- SECTION 1: CORE OPERATIONS -->
            <div class="mt-3 mb-1 px-4 text-[10px] uppercase tracking-widest text-white/40 font-bold">Core Operations</div>

            <!-- Master Data Group -->
            <div x-data="{ open: {{ request()->is('master/*') ? 'true' : 'false' }} }" class="group">
                <button @click="open = !open" type="button" class="w-full flex items-center justify-between px-4 py-2 rounded text-white/70 hover:bg-[#1b2e52] hover:text-white transition-all">
                    <div class="flex items-center">
                        <span class="material-symbols-outlined mr-3">inventory_2</span>
                        <span class="text-sm font-medium">Master Data</span>
                    </div>
                    <span class="material-symbols-outlined text-sm transition-transform duration-200" :class="open ? 'rotate-180' : ''">expand_more</span>
                </button>
                <div x-show="open" x-collapse class="pl-9 pr-2 py-1 flex flex-col gap-1">
                    <a href="{{ route('master.products.index') }}" class="py-1.5 px-2 text-xs rounded {{ request()->routeIs('master.products.*') ? 'bg-[#1b2e52] text-white font-bold' : 'text-white/60 hover:text-white' }}">Produk</a>
                    <a href="{{ route('master.categories.index') }}" class="py-1.5 px-2 text-xs rounded {{ request()->routeIs('master.categories.*') ? 'bg-[#1b2e52] text-white font-bold' : 'text-white/60 hover:text-white' }}">Kategori Produk</a>
                    <a href="{{ route('master.warehouses.index') }}" class="py-1.5 px-2 text-xs rounded {{ request()->routeIs('master.warehouses.*') ? 'bg-[#1b2e52] text-white font-bold' : 'text-white/60 hover:text-white' }}">Gudang</a>
                    <a href="{{ route('master.suppliers.index') }}" class="py-1.5 px-2 text-xs rounded {{ request()->routeIs('master.suppliers.*') ? 'bg-[#1b2e52] text-white font-bold' : 'text-white/60 hover:text-white' }}">Supplier</a>
                    <a href="{{ route('master.customers.index') }}" class="py-1.5 px-2 text-xs rounded {{ request()->routeIs('master.customers.*') ? 'bg-[#1b2e52] text-white font-bold' : 'text-white/60 hover:text-white' }}">Customer</a>
                    @if(auth()->user()->isAdmin() || auth()->user()->isFinance())
                    <a href="{{ route('master.chart-of-accounts.index') }}" class="py-1.5 px-2 text-xs rounded {{ request()->routeIs('master.chart-of-accounts.*') ? 'bg-[#1b2e52] text-white font-bold' : 'text-white/60 hover:text-white' }}">Chart of Accounts</a>
                    @endif
                    @if(auth()->user()->isAdmin())
                    <a href="{{ route('master.users.index') }}" class="py-1.5 px-2 text-xs rounded {{ request()->routeIs('master.users.*') ? 'bg-[#1b2e52] text-white font-bold' : 'text-white/60 hover:text-white' }}">User Management</a>
                    @endif
                </div>
            </div>

            <!-- Purchase Group -->
            <div x-data="{ open: {{ request()->is('purchase/*') ? 'true' : 'false' }} }" class="group">
                <button @click="open = !open" type="button" class="w-full flex items-center justify-between px-4 py-2 rounded text-white/70 hover:bg-[#1b2e52] hover:text-white transition-all">
                    <div class="flex items-center">
                        <span class="material-symbols-outlined mr-3">shopping_cart</span>
                        <span class="text-sm font-medium">Purchase</span>
                    </div>
                    <span class="material-symbols-outlined text-sm transition-transform duration-200" :class="open ? 'rotate-180' : ''">expand_more</span>
                </button>
                <div x-show="open" x-collapse class="pl-9 pr-2 py-1 flex flex-col gap-1">
                    <a href="{{ route('purchase.demands.index') }}" class="py-1.5 px-2 text-xs rounded {{ request()->routeIs('purchase.demands.*') ? 'bg-[#1b2e52] text-white font-bold' : 'text-white/60 hover:text-white' }}">Kebutuhan Pengadaan</a>
                    <a href="{{ route('purchase.orders.index') }}" class="py-1.5 px-2 text-xs rounded {{ request()->routeIs('purchase.orders.*') ? 'bg-[#1b2e52] text-white font-bold' : 'text-white/60 hover:text-white' }}">Purchase Order</a>
                    <a href="{{ route('purchase.goods-receipts.index') }}" class="py-1.5 px-2 text-xs rounded {{ request()->routeIs('purchase.goods-receipts.*') ? 'bg-[#1b2e52] text-white font-bold' : 'text-white/60 hover:text-white' }}">Penerimaan Barang</a>
                    <a href="{{ route('purchase.invoices.index') }}" class="py-1.5 px-2 text-xs rounded {{ request()->routeIs('purchase.invoices.*') ? 'bg-[#1b2e52] text-white font-bold' : 'text-white/60 hover:text-white' }}">Invoice Pembelian</a>
                    <a href="{{ route('purchase.returns.index') }}" class="py-1.5 px-2 text-xs rounded {{ request()->routeIs('purchase.returns.*') ? 'bg-[#1b2e52] text-white font-bold' : 'text-white/60 hover:text-white' }}">Retur Pembelian</a>
                    <a href="{{ route('purchase.payments.index') }}" class="py-1.5 px-2 text-xs rounded {{ request()->routeIs('purchase.payments.*') ? 'bg-[#1b2e52] text-white font-bold' : 'text-white/60 hover:text-white' }}">Bayar Hutang</a>
                </div>
            </div>

            <!-- Inventory Group -->
            <div x-data="{ open: {{ request()->is('inventory/*') ? 'true' : 'false' }} }" class="group">
                <button @click="open = !open" type="button" class="w-full flex items-center justify-between px-4 py-2 rounded text-white/70 hover:bg-[#1b2e52] hover:text-white transition-all">
                    <div class="flex items-center">
                        <span class="material-symbols-outlined mr-3">warehouse</span>
                        <span class="text-sm font-medium">Inventory</span>
                    </div>
                    <span class="material-symbols-outlined text-sm transition-transform duration-200" :class="open ? 'rotate-180' : ''">expand_more</span>
                </button>
                <div x-show="open" x-collapse class="pl-9 pr-2 py-1 flex flex-col gap-1">
                    <a href="{{ route('inventory.stock-summary') }}" class="py-1.5 px-2 text-xs rounded {{ request()->routeIs('inventory.stock-summary') ? 'bg-[#1b2e52] text-white font-bold' : 'text-white/60 hover:text-white' }}">Ringkasan Stok</a>
                    <a href="{{ route('inventory.stock-card') }}" class="py-1.5 px-2 text-xs rounded {{ request()->routeIs('inventory.stock-card') ? 'bg-[#1b2e52] text-white font-bold' : 'text-white/60 hover:text-white' }}">Kartu Stok</a>
                    <a href="{{ route('inventory.movements.index') }}" class="py-1.5 px-2 text-xs rounded {{ request()->routeIs('inventory.movements.*') ? 'bg-[#1b2e52] text-white font-bold' : 'text-white/60 hover:text-white' }}">Mutasi Stok</a>
                    <a href="{{ route('inventory.transfers.index') }}" class="py-1.5 px-2 text-xs rounded {{ request()->routeIs('inventory.transfers.*') ? 'bg-[#1b2e52] text-white font-bold' : 'text-white/60 hover:text-white' }}">Transfer Gudang</a>
                    <a href="{{ route('inventory.opname.index') }}" class="py-1.5 px-2 text-xs rounded {{ request()->routeIs('inventory.opname.*') ? 'bg-[#1b2e52] text-white font-bold' : 'text-white/60 hover:text-white' }}">Stock Opname</a>
                    <a href="{{ route('inventory.dispositions.index') }}" class="py-1.5 px-2 text-xs rounded {{ request()->routeIs('inventory.dispositions.*') ? 'bg-[#1b2e52] text-white font-bold' : 'text-white/60 hover:text-white' }}">Disposisi Karantina</a>
                </div>
            </div>

            <!-- Sales Group -->
            <div x-data="{ open: {{ request()->is('sales/*') ? 'true' : 'false' }} }" class="group">
                <button @click="open = !open" type="button" class="w-full flex items-center justify-between px-4 py-2 rounded text-white/70 hover:bg-[#1b2e52] hover:text-white transition-all">
                    <div class="flex items-center">
                        <span class="material-symbols-outlined mr-3">sell</span>
                        <span class="text-sm font-medium">Sales</span>
                    </div>
                    <span class="material-symbols-outlined text-sm transition-transform duration-200" :class="open ? 'rotate-180' : ''">expand_more</span>
                </button>
                <div x-show="open" x-collapse class="pl-9 pr-2 py-1 flex flex-col gap-1">
                    <a href="{{ route('sales.orders.index') }}" class="py-1.5 px-2 text-xs rounded {{ request()->routeIs('sales.orders.*') ? 'bg-[#1b2e52] text-white font-bold' : 'text-white/60 hover:text-white' }}">Sales Order</a>
                    <a href="{{ route('sales.deliveries.index') }}" class="py-1.5 px-2 text-xs rounded {{ request()->routeIs('sales.deliveries.*') ? 'bg-[#1b2e52] text-white font-bold' : 'text-white/60 hover:text-white' }}">Surat Jalan</a>
                    <a href="{{ route('sales.invoices.index') }}" class="py-1.5 px-2 text-xs rounded {{ request()->routeIs('sales.invoices.*') ? 'bg-[#1b2e52] text-white font-bold' : 'text-white/60 hover:text-white' }}">Invoice Penjualan</a>
                    <a href="{{ route('sales.returns.index') }}" class="py-1.5 px-2 text-xs rounded {{ request()->routeIs('sales.returns.*') ? 'bg-[#1b2e52] text-white font-bold' : 'text-white/60 hover:text-white' }}">Retur Penjualan</a>
                    <a href="{{ route('sales.payments.index') }}" class="py-1.5 px-2 text-xs rounded {{ request()->routeIs('sales.payments.*') ? 'bg-[#1b2e52] text-white font-bold' : 'text-white/60 hover:text-white' }}">Terima Piutang</a>
                </div>
            </div>

            <!-- SECTION 2: FINANCE & ACCOUNTING -->
            <div class="mt-3 mb-1 px-4 text-[10px] uppercase tracking-widest text-white/40 font-bold">Finance</div>

            <div x-data="{ open: {{ request()->is('accounting/*') ? 'true' : 'false' }} }" class="group">
                <button @click="open = !open" type="button" class="w-full flex items-center justify-between px-4 py-2 rounded text-white/70 hover:bg-[#1b2e52] hover:text-white transition-all">
                    <div class="flex items-center">
                        <span class="material-symbols-outlined mr-3">account_balance</span>
                        <span class="text-sm font-medium">Akuntansi</span>
                    </div>
                    <span class="material-symbols-outlined text-sm transition-transform duration-200" :class="open ? 'rotate-180' : ''">expand_more</span>
                </button>
                <div x-show="open" x-collapse class="pl-9 pr-2 py-1 flex flex-col gap-1">
                    <a href="{{ route('accounting.journals.index') }}" class="py-1.5 px-2 text-xs rounded {{ request()->routeIs('accounting.journals.*') ? 'bg-[#1b2e52] text-white font-bold' : 'text-white/60 hover:text-white' }}">Jurnal Umum</a>
                    <a href="{{ route('accounting.reports.ledger') }}" class="py-1.5 px-2 text-xs rounded {{ request()->routeIs('accounting.reports.ledger') ? 'bg-[#1b2e52] text-white font-bold' : 'text-white/60 hover:text-white' }}">Buku Besar</a>
                    <a href="{{ route('accounting.reports.trial-balance') }}" class="py-1.5 px-2 text-xs rounded {{ request()->routeIs('accounting.reports.trial-balance') ? 'bg-[#1b2e52] text-white font-bold' : 'text-white/60 hover:text-white' }}">Neraca Saldo</a>
                    <a href="{{ route('accounting.reports.cash-flow') }}" class="py-1.5 px-2 text-xs rounded {{ request()->routeIs('accounting.reports.cash-flow') ? 'bg-[#1b2e52] text-white font-bold' : 'text-white/60 hover:text-white' }}">Arus Kas</a>
                    <a href="{{ route('accounting.reports.receivables') }}" class="py-1.5 px-2 text-xs rounded {{ request()->routeIs('accounting.reports.receivables') ? 'bg-[#1b2e52] text-white font-bold' : 'text-white/60 hover:text-white' }}">Laporan Piutang</a>
                    <a href="{{ route('accounting.reports.payables') }}" class="py-1.5 px-2 text-xs rounded {{ request()->routeIs('accounting.reports.payables') ? 'bg-[#1b2e52] text-white font-bold' : 'text-white/60 hover:text-white' }}">Laporan Hutang</a>
                    <a href="{{ route('accounting.reports.profit-loss') }}" class="py-1.5 px-2 text-xs rounded {{ request()->routeIs('accounting.reports.profit-loss') ? 'bg-[#1b2e52] text-white font-bold' : 'text-white/60 hover:text-white' }}">Laba / Rugi</a>
                    <a href="{{ route('accounting.reports.balance-sheet') }}" class="py-1.5 px-2 text-xs rounded {{ request()->routeIs('accounting.reports.balance-sheet') ? 'bg-[#1b2e52] text-white font-bold' : 'text-white/60 hover:text-white' }}">Neraca</a>
                    <a href="{{ route('accounting.reports.stock-valuation') }}" class="py-1.5 px-2 text-xs rounded {{ request()->routeIs('accounting.reports.stock-valuation') ? 'bg-[#1b2e52] text-white font-bold' : 'text-white/60 hover:text-white' }}">Valuasi Stok</a>
                </div>
            </div>

            @if(auth()->user()->isAdmin() || auth()->user()->isFinance())
            @php
                $pendingApprovalCount = \App\Models\ApprovalRequest::where('status', 'pending')->count();
            @endphp
            <a href="{{ route('approvals.index') }}" 
               class="flex items-center justify-between px-4 py-2 rounded transition-all {{ request()->routeIs('approvals.*') ? 'bg-[#1b2e52] text-white font-bold' : 'text-white/70 hover:bg-[#1b2e52] hover:text-white' }}">
                <div class="flex items-center">
                    <span class="material-symbols-outlined mr-3">verified_user</span>
                    <span class="text-sm font-medium">Approval</span>
                </div>
                @if($pendingApprovalCount > 0)
                <span class="bg-[#FBEBD2] text-[#92640B] px-2 py-0.5 rounded text-[10px] font-bold">{{ $pendingApprovalCount }} PENDING</span>
                @endif
            </a>
            @endif

            <!-- Logout Button -->
            <form method="POST" action="{{ route('logout') }}" class="mt-auto mb-4">
                @csrf
                <button type="submit" class="w-full flex items-center px-4 py-2 rounded text-white/60 hover:bg-red-500/20 hover:text-red-300 transition-all text-left">
                    <span class="material-symbols-outlined mr-3">logout</span>
                    <span class="text-sm font-medium">Logout</span>
                </button>
            </form>
        </nav>
    </aside>

    <!-- ========================================================= -->
    <!-- TOP HEADER BAR (Fixed 56px #FFFFFF) -->
    <!-- ========================================================= -->
    <div class="pl-[240px]">
        <header class="fixed top-0 left-[240px] right-0 h-[56px] bg-white border-b border-[#E2E8F0] z-40 flex items-center justify-between px-6">
            <!-- Breadcrumbs -->
            <div class="flex items-center gap-2 text-[#6B7280] text-sm">
                <span class="material-symbols-outlined text-base">home</span>
                <span class="material-symbols-outlined text-xs">chevron_right</span>
                <span>TradePro</span>
                <span class="material-symbols-outlined text-xs">chevron_right</span>
                <span class="text-[#03193c] font-bold">@yield('page-title', 'Overview')</span>
            </div>

            <!-- Right Options -->
            <div class="flex items-center gap-6">
                <button class="relative text-[#44474e] hover:text-[#03193c] transition-colors">
                    <span class="material-symbols-outlined">notifications</span>
                    <span class="absolute -top-1 -right-1 w-2 h-2 bg-[#ba1a1a] rounded-full"></span>
                </button>
                <div class="h-8 w-px bg-[#E2E8F0]"></div>
                
                <!-- Profile Dropdown -->
                <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                    <button type="button" @click="open = !open" class="flex items-center gap-3 cursor-pointer">
                        <div class="text-right hidden sm:block">
                            <div class="text-sm font-bold text-[#0e1b35]">{{ auth()->user()->name }}</div>
                            <div class="text-[10px] text-[#44474e] uppercase tracking-tighter">{{ auth()->user()->role }}</div>
                        </div>
                        <div class="w-8 h-8 rounded-full bg-[#1b2e52] text-white flex items-center justify-center font-bold text-sm border border-[#E2E8F0]">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </div>
                    </button>

                    <!-- Dropdown Menu -->
                    <div x-show="open" x-transition style="display: none;" class="absolute right-0 top-12 w-48 bg-white rounded-lg shadow-lg border border-[#E2E8F0] py-1 z-50">
                        <a href="{{ route('profile.edit') }}" class="px-4 py-2 text-sm text-[#0e1b35] hover:bg-[#F5F6F8] flex items-center gap-2">
                            <span class="material-symbols-outlined text-base">person</span> Profil Saya
                        </a>
                        <hr class="border-[#E2E8F0] my-1">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full text-left px-4 py-2 text-sm text-[#ba1a1a] hover:bg-red-50 flex items-center gap-2">
                                <span class="material-symbols-outlined text-base">logout</span> Logout
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content Area with Standard 24px Padding -->
        <main class="relative pt-[56px] min-h-screen bg-[#F5F6F8]">
            <div class="p-6 max-w-7xl mx-auto">
                @if(session('success'))
                    <div class="bg-[#DCFCE3] border border-[#166534]/20 text-[#166534] px-4 py-3 rounded-lg mb-4 flex items-center gap-2 text-sm font-medium">
                        <span class="material-symbols-outlined text-base">check_circle</span> {{ session('success') }}
                    </div>
                @endif
                @if(session('error'))
                    <div class="bg-[#FDE2E1] border border-[#B91C1C]/20 text-[#B91C1C] px-4 py-3 rounded-lg mb-4 flex items-center gap-2 text-sm font-medium">
                        <span class="material-symbols-outlined text-base">error</span> {{ session('error') }}
                    </div>
                @endif

                <!-- Page Content -->
                @yield('content')
            </div>
        </main>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('[data-confirm-delete]').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const formId = this.dataset.confirmDelete;
                const form = document.getElementById(formId);
                if (!form) return;

                const itemName = this.dataset.name || this.dataset.productName || '';
                
                const messageHtml = itemName 
                    ? `<div style="background:#fef2f2; border:1px solid #fee2e2; border-radius:10px; padding:12px 16px; margin:14px 0 10px 0; text-align:left;">
                         <div style="font-size:11px; color:#991b1b; font-weight:700; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:4px;">Item yang akan dihapus:</div>
                         <div style="font-weight:700; font-size:15px; color:#1e293b; word-break:break-word;">${itemName}</div>
                       </div>
                       <div style="font-size:13px; color:#64748b; margin-top:8px;">
                         <i class="fa-solid fa-triangle-exclamation" style="color:#dc2626; margin-right:4px;"></i> Data yang dihapus <strong>tidak dapat dipulihkan</strong>.
                       </div>` 
                    : '<div style="font-size:14px; color:#64748b; margin-top:8px;">Data yang dihapus <strong>tidak dapat dipulihkan</strong>!</div>';

                Swal.fire({
                    title: 'Konfirmasi Hapus',
                    html: messageHtml,
                    icon: 'warning',
                    iconColor: '#dc2626',
                    showCancelButton: true,
                    confirmButtonText: '<i class="fa-solid fa-trash" style="font-size:13px;"></i> Ya, Hapus',
                    cancelButtonText: 'Batal',
                    reverseButtons: true,
                    focusCancel: true,
                    customClass: {
                        popup: 'erp-swal-modal'
                    },
                    buttonsStyling: false
                }).then((r) => { 
                    if (r.isConfirmed) {
                        form.submit(); 
                    }
                });
            });
        });
    });
    </script>
    @stack('scripts')
</body>
</html>
