<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — FlashSuite Pro</title>



    <!-- Google Fonts & Material Symbols -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=block" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&family=Public+Sans:wght@100..900&display=swap" rel="stylesheet"/>

    <!-- FontAwesome Compatibility -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- ApexCharts -->
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Direct CSS Rules for All Legacy Forms, Cards, Tables, Buttons, and Layouts -->
    <style>
        [x-cloak] { display: none !important; }

        /* Critical Layout CSS: Responsive Off-Canvas Drawer & Collapsible Sidebar */
        aside, #erp-sidebar {
            position: fixed !important;
            top: 0 !important;
            bottom: 0 !important;
            height: 100vh !important;
            width: 240px !important;
            background-color: #03193c !important;
            border-right: 1px solid #1b2e52 !important;
            z-index: 50 !important;
            display: flex !important;
            flex-direction: column !important;
            overflow-y: auto !important;
            box-sizing: border-box !important;
            transition: transform 0.28s cubic-bezier(0.4, 0, 0.2, 1), left 0.28s cubic-bezier(0.4, 0, 0.2, 1) !important;
        }

        .erp-backdrop {
            position: fixed !important;
            inset: 0 !important;
            background-color: rgba(15, 23, 42, 0.6) !important;
            backdrop-filter: blur(2px) !important;
            -webkit-backdrop-filter: blur(2px) !important;
            z-index: 45 !important;
            transition: opacity 0.25s ease !important;
        }

        /* Mobile & Tablet (< 1024px): Off-Canvas Drawer */
        @media (max-width: 1023.98px) {
            aside, #erp-sidebar {
                left: 0 !important;
                transform: translateX(-100%) !important;
                box-shadow: none !important;
            }
            body.mobile-sidebar-open aside,
            body.mobile-sidebar-open #erp-sidebar {
                transform: translateX(0) !important;
                box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5) !important;
            }
            .erp-main-wrapper, .pl-\[240px\] {
                padding-left: 0 !important;
                min-height: 100vh !important;
                box-sizing: border-box !important;
                transition: padding-left 0.28s cubic-bezier(0.4, 0, 0.2, 1) !important;
            }
            header, header.fixed, #erp-header {
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                right: 0 !important;
                height: 56px !important;
                min-height: 56px !important;
                background-color: #ffffff !important;
                border-bottom: 1px solid #E2E8F0 !important;
                z-index: 40 !important;
                display: flex !important;
                align-items: center !important;
                justify-content: space-between !important;
                padding-left: 0.875rem !important;
                padding-right: 0.875rem !important;
                box-sizing: border-box !important;
                transition: left 0.28s cubic-bezier(0.4, 0, 0.2, 1) !important;
            }
        }

        /* Desktop (>= 1024px): Docked Sidebar & Collapsible */
        @media (min-width: 1024px) {
            aside, #erp-sidebar {
                left: 0 !important;
                transform: translateX(0) !important;
            }
            body.desktop-sidebar-collapsed aside,
            body.desktop-sidebar-collapsed #erp-sidebar {
                transform: translateX(-100%) !important;
            }

            .erp-main-wrapper, .pl-\[240px\] {
                padding-left: 240px !important;
                min-height: 100vh !important;
                box-sizing: border-box !important;
                transition: padding-left 0.28s cubic-bezier(0.4, 0, 0.2, 1) !important;
            }
            body.desktop-sidebar-collapsed .erp-main-wrapper,
            body.desktop-sidebar-collapsed .pl-\[240px\] {
                padding-left: 0 !important;
            }

            header, header.fixed, #erp-header {
                position: fixed !important;
                top: 0 !important;
                left: 240px !important;
                right: 0 !important;
                height: 56px !important;
                min-height: 56px !important;
                background-color: #ffffff !important;
                border-bottom: 1px solid #E2E8F0 !important;
                z-index: 40 !important;
                display: flex !important;
                align-items: center !important;
                justify-content: space-between !important;
                padding-left: 1.5rem !important;
                padding-right: 1.5rem !important;
                box-sizing: border-box !important;
                transition: left 0.28s cubic-bezier(0.4, 0, 0.2, 1) !important;
            }
            body.desktop-sidebar-collapsed header,
            body.desktop-sidebar-collapsed header.fixed,
            body.desktop-sidebar-collapsed #erp-header {
                left: 0 !important;
            }
        }

        /* Ensure sidebar links and buttons always have high-contrast text colors */
        aside nav a, aside nav button {
            color: rgba(255, 255, 255, 0.86) !important;
            text-decoration: none;
            transition: all 0.15s ease !important;
        }
        aside nav a:hover, aside nav button:hover {
            color: #ffffff !important;
            background-color: rgba(255, 255, 255, 0.08) !important;
        }
        aside nav a.font-bold, aside nav a.bg-\[\#1b2e52\] {
            color: #ffffff !important;
            background-color: #1b2e52 !important;
            box-shadow: inset 3px 0 0 0 #38bdf8 !important;
        }
        .sidebar-section-title {
            color: #cbd5e1 !important; /* Slate-300: High contrast on navy background */
            font-size: 11px !important;
            font-weight: 700 !important;
            letter-spacing: 0.08em !important;
            text-transform: uppercase !important;
            padding: 16px 16px 6px 16px !important;
            display: block !important;
        }

        main, main.pt-\[56px\] {
            position: relative !important;
            padding-top: 56px !important;
            min-height: 100vh !important;
            background-color: #F5F6F8 !important;
            box-sizing: border-box !important;
        }

        .material-symbols-outlined {
            font-family: 'Material Symbols Outlined' !important;
            font-weight: normal !important;
            font-style: normal !important;
            font-size: 20px;
            line-height: 1;
            letter-spacing: normal;
            text-transform: none;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            vertical-align: middle !important;
            flex-shrink: 0 !important;
            width: 20px !important;
            min-width: 20px !important;
            max-width: 20px !important;
            height: 20px !important;
            overflow: hidden !important;
            white-space: nowrap !important;
            word-wrap: normal !important;
            direction: ltr !important;
            -webkit-font-smoothing: antialiased !important;
            text-rendering: optimizeLegibility !important;
            font-feature-settings: 'liga' !important;
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24 !important;
            user-select: none !important;
        }
        .material-symbols-outlined.text-sm {
            font-size: 14px !important;
            width: 14px !important;
            min-width: 14px !important;
            max-width: 14px !important;
            height: 14px !important;
        }
        .material-symbols-outlined.text-xs {
            font-size: 12px !important;
            width: 12px !important;
            min-width: 12px !important;
            max-width: 12px !important;
            height: 12px !important;
        }
        .material-symbols-outlined.text-base {
            font-size: 16px !important;
            width: 16px !important;
            min-width: 16px !important;
            max-width: 16px !important;
            height: 16px !important;
        }
        ::-webkit-scrollbar { display: none; }

        /* Global Layout Components Overrides */
        .page-header {
            display: flex !important;
            flex-direction: row !important;
            align-items: center !important;
            justify-content: space-between !important;
            gap: 1rem !important;
            margin-bottom: 1.125rem !important;
            padding-bottom: 0.875rem !important;
            border-bottom: 1px solid #E2E8F0 !important;
            width: 100% !important;
        }
        .page-header h1 {
            font-size: 1.35rem !important;
            font-weight: 700 !important;
            color: #0e1b35 !important;
            margin: 0 !important;
            font-family: 'Public Sans', sans-serif !important;
        }
        .page-header p {
            font-size: 0.8125rem !important;
            color: #64748b !important;
            margin: 0.25rem 0 0 0 !important;
            font-family: 'Inter', sans-serif !important;
        }

        .card {
            background-color: #ffffff !important;
            border-radius: 0.625rem !important;
            border: 1px solid #E2E8F0 !important;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.04) !important;
            padding: 1.25rem !important;
            margin-bottom: 1.125rem !important;
            width: 100% !important;
            box-sizing: border-box !important;
        }
        .card.erp-filter-card {
            padding: 0.75rem 1.125rem !important;
            margin-bottom: 1rem !important;
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
            min-width: 0 !important;
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
            line-height: 1.25 !important;
            white-space: nowrap !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
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
            max-width: 100% !important;
            min-width: 0 !important;
            height: 38px !important;
            padding: 0.45rem 0.75rem !important;
            border-radius: 0.5rem !important;
            border: 1px solid #CBD5E1 !important;
            background-color: #ffffff !important;
            font-size: 0.845rem !important;
            color: #0e1b35 !important;
            box-sizing: border-box !important;
            font-family: 'Inter', sans-serif !important;
            transition: border-color 0.15s ease, box-shadow 0.15s ease !important;
        }
        .form-control:focus, 
        main input[type="text"]:focus, 
        main input[type="number"]:focus, 
        main input[type="email"]:focus, 
        main input[type="password"]:focus, 
        main input[type="date"]:focus, 
        main select:focus, 
        main textarea:focus {
            border-color: #2563eb !important;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12) !important;
            outline: none !important;
        }
        /* Custom Select & Searchable Select Trigger Overrides */
        .searchable-select-trigger,
        div.form-control.searchable-select-trigger,
        div.form-control[style*="display: flex"],
        div.form-control[style*="display:flex"] {
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            height: 38px !important;
            min-height: 38px !important;
            max-height: 38px !important;
            padding: 0 0.75rem !important;
            box-sizing: border-box !important;
            cursor: pointer !important;
        }
        .searchable-select-trigger span,
        div.form-control.searchable-select-trigger span,
        div.form-control[style*="display: flex"] span,
        div.form-control[style*="display:flex"] span {
            white-space: nowrap !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
            word-break: keep-all !important;
            flex: 1 1 auto !important;
            min-width: 0 !important;
            line-height: 1.2 !important;
            padding-right: 8px !important;
        }
        .searchable-select-trigger.is-invalid,
        div.form-control.searchable-select-trigger.is-invalid {
            border-color: #ef4444 !important;
            background-color: #fff1f2 !important;
            background-image: none !important;
            padding-right: 0.75rem !important;
        }
        /* Searchable Select Native Focus & Validation Styling */
        .searchable-select-wrapper input:focus + .searchable-select-trigger,
        .searchable-select-trigger.focused {
            border-color: #2563eb !important;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.2) !important;
        }
        .searchable-select-native-input {
            padding: 0 !important;
            margin: 0 !important;
            border: 0 !important;
            outline: none !important;
            box-shadow: none !important;
        }
        /* Search Input Icon Overlap Prevention */
        .search-input,
        .search-box input,
        .has-search-icon input,
        input.search-input,
        main input.search-input,
        main input[name="q"],
        main input[type="search"],
        .searchable-select-search-input,
        input.searchable-select-search-input,
        .searchable-select-wrapper input[type="text"]:not(.searchable-select-native-input),
        td[style*="position: relative"] input[type="text"]:not(.searchable-select-native-input) {
            padding-left: 2.25rem !important;
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
            .form-row-3 {
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            }
            .grid-3 {
                grid-template-columns: 1fr !important;
            }
            .grid-5 {
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            }
            .card[style*="grid-column:span 2"],
            .card[style*="grid-column: span 2"],
            div[style*="grid-column:span 2"],
            div[style*="grid-column: span 2"],
            div[style*="grid-column:span 3"],
            div[style*="grid-column: span 3"] {
                grid-column: span 1 !important;
            }
        }
        @media (max-width: 768px) {
            .form-row, .form-row-2, .form-row-3, .form-row-4, .grid-2, .grid-3, .grid-4, .grid-5 {
                grid-template-columns: 1fr !important;
            }
            .card[style*="grid-column:span 2"],
            .card[style*="grid-column: span 2"],
            div[style*="grid-column:span 2"],
            div[style*="grid-column: span 2"],
            div[style*="grid-column:span 3"],
            div[style*="grid-column: span 3"] {
                grid-column: span 1 !important;
            }
            .erp-filter-bar select:not([name="per_page"]),
            .filter-bar select:not([name="per_page"]),
            .list-filter-form select:not([name="per_page"]),
            .list-filter-slots select {
                width: 100% !important;
                min-width: 0 !important;
                display: block !important;
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

        .table-responsive {
            overflow-x: auto !important;
            border-radius: 0.625rem !important;
            border: 1px solid #E2E8F0 !important;
            background-color: #ffffff !important;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.04) !important;
            margin-bottom: 1.25rem !important;
            width: 100% !important;
            position: relative !important;
        }
        .table-responsive[style*="overflow: visible"],
        .table-responsive[style*="overflow:visible"],
        .table-responsive.overflow-visible,
        .table-has-dropdowns {
            overflow: visible !important;
            overflow-x: visible !important;
            overflow-y: visible !important;
        }
        .erp-table {
            width: 100% !important;
            border-collapse: collapse !important;
            min-width: 750px !important;
            text-align: left !important;
        }
        .erp-table thead {
            background-color: #F8FAFC !important;
            border-bottom: 2px solid #E2E8F0 !important;
            position: sticky !important;
            top: 0 !important;
            z-index: 10 !important;
        }
        .erp-table thead th {
            position: sticky !important;
            top: 0 !important;
            z-index: 10 !important;
            background-color: #F8FAFC !important;
            padding: 0.75rem 1rem !important;
            font-size: 0.72rem !important;
            font-weight: 700 !important;
            color: #475569 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.05em !important;
            white-space: nowrap !important;
            font-family: 'Public Sans', sans-serif !important;
        }
        .erp-table tbody tr {
            border-bottom: 1px solid #F1F5F9 !important;
            transition: background-color 0.15s ease !important;
        }
        .erp-table tbody tr:nth-child(even) {
            background-color: rgba(248, 250, 252, 0.65) !important;
        }
        .erp-table tbody tr:hover {
            background-color: #EEF2F6 !important;
        }
        .erp-table td {
            padding: 0.75rem 1rem !important;
            font-size: 0.845rem !important;
            color: #0e1b35 !important;
            vertical-align: middle !important;
            font-family: 'Inter', sans-serif !important;
        }

        /* Tabel Form Input Item (agar seluruh field input sejajar di atas saat ada helper/warning) */
        .erp-table.item-form-table td,
        .erp-table.item-form-table th {
            vertical-align: top !important;
        }

        /* Tabular Nums — Semua Kolom Angka / Nominal / Qty Rata Kanan */
        .erp-table td.text-right,
        .erp-table td[style*="text-align: right"],
        .erp-table td[style*="text-align:right"],
        .erp-table .nominal,
        .erp-table .qty {
            font-variant-numeric: tabular-nums !important;
            font-feature-settings: "tnum" 1 !important;
        }

        /* Kolom Catatan / Teks Panjang */
        .erp-table .col-notes {
            max-width: 250px !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
            display: -webkit-box !important;
            -webkit-line-clamp: 2 !important;
            -webkit-box-orient: vertical !important;
            cursor: help !important;
        }

        /* Kolom Aksi */
        .erp-table .col-actions {
            padding-right: 1.25rem !important;
            white-space: nowrap !important;
            text-align: center !important;
        }
        .erp-table .col-actions .btn,
        .erp-table .col-actions .btn-icon {
            min-width: 32px !important;
            margin-left: 2px !important;
            margin-right: 2px !important;
        }

        /* Table Footer Pagination */
        .table-footer {
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            flex-wrap: wrap !important;
            gap: 0.75rem !important;
            padding: 0.875rem 1.25rem !important;
            border-top: 1px solid #E2E8F0 !important;
            background-color: #ffffff !important;
            border-bottom-left-radius: 0.625rem !important;
            border-bottom-right-radius: 0.625rem !important;
        }

        /* Buttons & Tactile Feedback */
        .btn, button.btn, a.btn {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 0.5rem !important;
            padding: 0.5rem 1rem !important;
            border-radius: 0.5rem !important;
            font-size: 0.8125rem !important;
            font-weight: 600 !important;
            font-family: 'Inter', sans-serif !important;
            text-decoration: none !important;
            white-space: nowrap !important;
            cursor: pointer !important;
            box-sizing: border-box !important;
            border: 1px solid transparent !important;
            transition: all 0.15s cubic-bezier(0.4, 0, 0.2, 1) !important;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05) !important;
        }
        .btn:hover {
            text-decoration: none !important;
        }
        .btn-primary {
            background-color: #03193c !important;
            background-image: linear-gradient(180deg, #182e56 0%, #03193c 100%) !important;
            color: #ffffff !important;
            border-color: #03193c !important;
            box-shadow: 0 1px 3px 0 rgba(3, 25, 60, 0.2), 0 1px 2px -1px rgba(3, 25, 60, 0.1) !important;
        }
        .btn-primary:hover {
            background-image: linear-gradient(180deg, #243d6c 0%, #0c2654 100%) !important;
            transform: translateY(-1px) !important;
            box-shadow: 0 4px 6px -1px rgba(3, 25, 60, 0.25) !important;
            color: #ffffff !important;
        }
        .btn-primary:active {
            transform: translateY(0) !important;
            box-shadow: 0 1px 2px 0 rgba(3, 25, 60, 0.2) !important;
        }
        .btn-secondary {
            background-color: #ffffff !important;
            color: #334155 !important;
            border-color: #CBD5E1 !important;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05) !important;
        }
        .btn-secondary:hover {
            background-color: #f8fafc !important;
            border-color: #94a3b8 !important;
            color: #0f172a !important;
            transform: translateY(-1px) !important;
            box-shadow: 0 2px 4px -1px rgba(0, 0, 0, 0.08) !important;
        }
        .btn-secondary:active {
            transform: translateY(0) !important;
        }
        .btn-danger {
            background-color: #ba1a1a !important;
            background-image: linear-gradient(180deg, #dc2626 0%, #ba1a1a 100%) !important;
            color: #ffffff !important;
            border-color: #ba1a1a !important;
            box-shadow: 0 1px 2px 0 rgba(186, 26, 26, 0.2) !important;
        }
        .btn-danger:hover {
            background-image: linear-gradient(180deg, #b91c1c 0%, #93000a 100%) !important;
            border-color: #93000a !important;
            transform: translateY(-1px) !important;
            box-shadow: 0 4px 6px -1px rgba(186, 26, 26, 0.3) !important;
            color: #ffffff !important;
        }
        .btn-danger:active {
            transform: translateY(0) !important;
        }
        .btn-success {
            background-color: #059669 !important;
            background-image: linear-gradient(180deg, #10b981 0%, #059669 100%) !important;
            color: #ffffff !important;
            border-color: #059669 !important;
            box-shadow: 0 1px 2px 0 rgba(5, 150, 105, 0.2) !important;
        }
        .btn-success:hover {
            background-image: linear-gradient(180deg, #059669 0%, #047857 100%) !important;
            border-color: #047857 !important;
            transform: translateY(-1px) !important;
            box-shadow: 0 4px 6px -1px rgba(5, 150, 105, 0.3) !important;
            color: #ffffff !important;
        }
        .btn-success:active {
            transform: translateY(0) !important;
        }
        .btn-warning {
            background-color: #d97706 !important;
            background-image: linear-gradient(180deg, #f59e0b 0%, #d97706 100%) !important;
            color: #ffffff !important;
            border-color: #d97706 !important;
        }
        .btn-warning:hover {
            background-image: linear-gradient(180deg, #d97706 0%, #b45309 100%) !important;
            border-color: #b45309 !important;
            transform: translateY(-1px) !important;
            color: #ffffff !important;
        }
        .btn-info {
            background-color: #0284c7 !important;
            background-image: linear-gradient(180deg, #38bdf8 0%, #0284c7 100%) !important;
            color: #ffffff !important;
            border-color: #0284c7 !important;
        }
        .btn-info:hover {
            background-image: linear-gradient(180deg, #0284c7 0%, #0369a1 100%) !important;
            border-color: #0369a1 !important;
            transform: translateY(-1px) !important;
            color: #ffffff !important;
        }
        .btn-outline-primary {
            background-color: transparent !important;
            color: #03193c !important;
            border-color: #CBD5E1 !important;
        }
        .btn-outline-primary:hover {
            background-color: #03193c !important;
            color: #ffffff !important;
            border-color: #03193c !important;
        }
        .btn-outline-danger {
            background-color: transparent !important;
            color: #ba1a1a !important;
            border-color: #fecaca !important;
        }
        .btn-outline-danger:hover {
            background-color: #ba1a1a !important;
            color: #ffffff !important;
            border-color: #ba1a1a !important;
        }
        .btn-sm {
            padding: 0.35rem 0.75rem !important;
            font-size: 0.75rem !important;
        }
        .btn-icon {
            width: 32px !important;
            height: 32px !important;
            min-width: 32px !important;
            padding: 0 !important;
            border-radius: 0.375rem !important;
        }
        .btn-icon:hover {
            transform: translateY(-1px) !important;
        }
        .erp-table td .btn-icon.btn-secondary {
            background: #f8fafc !important;
            border-color: #e2e8f0 !important;
            color: #475569 !important;
        }
        .erp-table td .btn-icon.btn-secondary:hover {
            background: #e0f2fe !important;
            border-color: #7dd3fc !important;
            color: #0369a1 !important;
        }
        .erp-table td .btn-icon.btn-primary {
            background: #eff6ff !important;
            border-color: #bfdbfe !important;
            color: #1d4ed8 !important;
        }
        .erp-table td .btn-icon.btn-primary:hover {
            background: #1d4ed8 !important;
            border-color: #1d4ed8 !important;
            color: #ffffff !important;
        }
        .erp-table td .btn-icon.btn-danger {
            background: #fef2f2 !important;
            border-color: #fecaca !important;
            color: #dc2626 !important;
        }
        .erp-table td .btn-icon.btn-danger:hover {
            background: #dc2626 !important;
            border-color: #dc2626 !important;
            color: #ffffff !important;
        }

        /* SweetAlert2 Modal Styling */
        .swal2-popup.erp-swal-modal {
            font-family: 'Inter', sans-serif !important;
            border-radius: 16px !important;
            padding: 24px !important;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.15), 0 10px 10px -5px rgba(0, 0, 0, 0.04) !important;
            border: 1px solid #E2E8F0 !important;
            width: 480px !important;
            max-width: 92vw !important;
            box-sizing: border-box !important;
        }
        .swal2-popup.erp-swal-modal .swal2-title {
            font-family: 'Public Sans', sans-serif !important;
            font-size: 1.2rem !important;
            font-weight: 700 !important;
            color: #0e1b35 !important;
            padding: 0 !important;
            margin-bottom: 8px !important;
            line-height: 1.35 !important;
        }
        .swal2-popup.erp-swal-modal .swal2-html-container {
            font-family: 'Inter', sans-serif !important;
            font-size: 0.875rem !important;
            color: #475569 !important;
            margin: 0 0 8px 0 !important;
            padding: 0 !important;
            line-height: 1.5 !important;
            width: 100% !important;
            box-sizing: border-box !important;
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
            background-color: #dc2626;
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
            transition: background-color 0.15s ease !important;
        }
        .swal2-popup.erp-swal-modal .swal2-confirm:hover {
            background-color: #b91c1c !important;
        }
        .swal2-popup.erp-swal-modal .swal2-confirm.btn-success,
        .swal2-popup.erp-swal-modal .swal2-confirm.swal2-confirm-success {
            background-color: #16a34a !important;
        }
        .swal2-popup.erp-swal-modal .swal2-confirm.btn-success:hover,
        .swal2-popup.erp-swal-modal .swal2-confirm.swal2-confirm-success:hover {
            background-color: #15803d !important;
        }
        .swal2-popup.erp-swal-modal .swal2-confirm.btn-warning,
        .swal2-popup.erp-swal-modal .swal2-confirm.swal2-confirm-warning {
            background-color: #d97706 !important;
        }
        .swal2-popup.erp-swal-modal .swal2-confirm.btn-warning:hover,
        .swal2-popup.erp-swal-modal .swal2-confirm.swal2-confirm-warning:hover {
            background-color: #b45309 !important;
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
            transition: background-color 0.15s ease !important;
        }
        .swal2-popup.erp-swal-modal .swal2-cancel:hover {
            background-color: #e2e8f0 !important;
            color: #0f172a !important;
        }

        /* SweetAlert2 Input, Label & Textarea Fixes (Prevents Margin Overlap & Horizontal Overflow) */
        .swal2-popup .swal2-input-label,
        .swal2-popup.erp-swal-modal .swal2-input-label {
            display: block !important;
            text-align: left !important;
            font-family: 'Inter', sans-serif !important;
            font-size: 0.8125rem !important;
            font-weight: 600 !important;
            color: #1e293b !important;
            text-transform: none !important;
            letter-spacing: normal !important;
            margin: 12px 0 6px 0 !important;
            width: 100% !important;
            padding: 0 !important;
            box-sizing: border-box !important;
        }
        .swal2-popup .swal2-textarea,
        .swal2-popup.erp-swal-modal .swal2-textarea,
        .swal2-popup textarea.swal2-textarea {
            display: block !important;
            width: 100% !important;
            max-width: 100% !important;
            box-sizing: border-box !important;
            margin: 0 !important;
            min-height: 100px !important;
            height: auto !important;
            padding: 10px 14px !important;
            font-size: 0.875rem !important;
            font-family: 'Inter', sans-serif !important;
            color: #0e1b35 !important;
            background-color: #ffffff !important;
            border: 1px solid #cbd5e1 !important;
            border-radius: 8px !important;
            line-height: 1.5 !important;
            resize: vertical !important;
            box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.04) !important;
            transition: border-color 0.15s ease, box-shadow 0.15s ease !important;
        }
        .swal2-popup .swal2-textarea:focus,
        .swal2-popup.erp-swal-modal .swal2-textarea:focus,
        .swal2-popup textarea.swal2-textarea:focus {
            border-color: #2563eb !important;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15) !important;
            outline: none !important;
            background-color: #ffffff !important;
        }
        .swal2-popup .swal2-validation-message,
        .swal2-popup.erp-swal-modal .swal2-validation-message {
            margin: 8px 0 0 0 !important;
            padding: 8px 12px !important;
            background: #fef2f2 !important;
            color: #991b1b !important;
            font-size: 0.8125rem !important;
            font-weight: 500 !important;
            border-radius: 6px !important;
            border: 1px solid #fecaca !important;
            text-align: left !important;
            align-items: center !important;
            width: 100% !important;
            box-sizing: border-box !important;
        }
        .swal2-popup .swal2-validation-message[style*="display: none"],
        .swal2-popup:not(.swal2-has-validation-message) .swal2-validation-message {
            display: none !important;
        }
    </style>
    @stack('styles')
</head>
<body 
    x-data="{ 
        mobileSidebarOpen: false, 
        desktopSidebarOpen: localStorage.getItem('fs_sidebar_collapsed') !== 'true',
        toggleSidebar() {
            if (window.innerWidth < 1024) {
                this.mobileSidebarOpen = !this.mobileSidebarOpen;
            } else {
                this.desktopSidebarOpen = !this.desktopSidebarOpen;
                localStorage.setItem('fs_sidebar_collapsed', !this.desktopSidebarOpen);
            }
        },
        closeMobile() {
            this.mobileSidebarOpen = false;
        }
    }"
    :class="{ 
        'mobile-sidebar-open': mobileSidebarOpen, 
        'desktop-sidebar-collapsed': !desktopSidebarOpen 
    }"
    @keydown.escape.window="closeMobile()"
    class="bg-[#F5F6F8] font-body-base text-[#0e1b35] min-h-screen"
>

    <!-- ========================================================= -->
    <!-- MOBILE & TABLET BACKDROP OVERLAY -->
    <!-- ========================================================= -->
    <div 
        x-show="mobileSidebarOpen" 
        x-cloak 
        x-transition:enter="transition-opacity ease-linear duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-linear duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="closeMobile()" 
        class="erp-backdrop lg:hidden"
        aria-hidden="true"
    ></div>

    <!-- ========================================================= -->
    <!-- SIDEBAR NAVIGATION (240px Navy #03193c) -->
    <!-- ========================================================= -->
    <aside id="erp-sidebar" class="fixed left-0 top-0 h-full w-[240px] bg-[#03193c] z-50 flex flex-col overflow-y-auto border-r border-[#1b2e52]">
        <!-- Brand Header -->
        <a href="{{ route('dashboard') }}" class="flex items-center justify-center px-6 h-[68px] border-b border-[#1b2e52] mb-4 hover:opacity-90 transition-opacity">
            <img src="{{ asset('assets/FlashSuitePro - white.png') }}" alt="FlashSuite Pro" class="h-[21px] w-auto max-w-[172px] object-contain">
        </a>

        <nav class="flex-1 flex flex-col gap-1 px-2">
            @php
                $u = auth()->user();
                $isAdmin = $u && $u->isAdmin();
                $isFinance = $u && $u->isFinance();
                $isGudang = $u && $u->isGudang();
                $isSales = $u && $u->isSales();
                $isPurchasing = $u && $u->isPurchasing();
            @endphp

            <!-- Dashboard Link -->
            <a href="{{ route('dashboard') }}" 
               class="flex items-center px-4 py-2 rounded {{ request()->routeIs('dashboard') ? 'bg-[#1b2e52] text-white font-bold' : 'text-white/70 hover:bg-[#1b2e52] hover:text-white' }}">
                <span class="material-symbols-outlined mr-3">dashboard</span>
                <span class="text-sm font-medium">Dashboard</span>
            </a>

            <!-- SECTION 1: CORE OPERATIONS -->
            <div class="sidebar-section-title">Core Operations</div>

            <!-- Master Data Group -->
            @php $isMasterRoute = request()->is('master/*'); @endphp
            <div x-data="{ open: {{ $isMasterRoute ? 'true' : 'false' }} }" class="group">
                <button @click="open = !open" type="button" class="w-full flex items-center justify-between px-4 py-2 rounded text-white/70 hover:bg-[#1b2e52] hover:text-white">
                    <div class="flex items-center">
                        <span class="material-symbols-outlined mr-3">inventory_2</span>
                        <span class="text-sm font-medium">Master Data</span>
                    </div>
                    <span class="material-symbols-outlined text-sm" style="{{ $isMasterRoute ? 'transform: rotate(180deg);' : 'transform: rotate(0deg);' }} transition: transform 0.2s;" :style="open ? 'transform: rotate(180deg)' : 'transform: rotate(0deg)'">expand_more</span>
                </button>
                <div x-show="open" @if(!$isMasterRoute) x-cloak style="display: none;" @endif class="pl-9 pr-2 py-1 flex flex-col gap-1">
                    @if($isAdmin || $isPurchasing || $isGudang || $isSales || $isFinance)
                    <a href="{{ route('master.products.index') }}" class="py-1.5 px-2 text-xs rounded {{ request()->routeIs('master.products.*') ? 'bg-[#1b2e52] text-white font-bold' : 'text-white/60 hover:text-white' }}">Produk</a>
                    @endif
                    @if($isAdmin || $isPurchasing || $isGudang)
                    <a href="{{ route('master.categories.index') }}" class="py-1.5 px-2 text-xs rounded {{ request()->routeIs('master.categories.*') ? 'bg-[#1b2e52] text-white font-bold' : 'text-white/60 hover:text-white' }}">Kategori Produk</a>
                    @endif
                    @if($isAdmin || $isGudang)
                    <a href="{{ route('master.warehouses.index') }}" class="py-1.5 px-2 text-xs rounded {{ request()->routeIs('master.warehouses.*') ? 'bg-[#1b2e52] text-white font-bold' : 'text-white/60 hover:text-white' }}">Gudang</a>
                    @endif
                    @if($isAdmin || $isPurchasing || $isFinance)
                    <a href="{{ route('master.suppliers.index') }}" class="py-1.5 px-2 text-xs rounded {{ request()->routeIs('master.suppliers.*') ? 'bg-[#1b2e52] text-white font-bold' : 'text-white/60 hover:text-white' }}">Supplier</a>
                    @endif
                    @if($isAdmin || $isSales || $isFinance)
                    <a href="{{ route('master.customers.index') }}" class="py-1.5 px-2 text-xs rounded {{ request()->routeIs('master.customers.*') ? 'bg-[#1b2e52] text-white font-bold' : 'text-white/60 hover:text-white' }}">Customer</a>
                    @endif
                    @if($isAdmin || $isFinance)
                    <a href="{{ route('master.chart-of-accounts.index') }}" class="py-1.5 px-2 text-xs rounded {{ request()->routeIs('master.chart-of-accounts.*') ? 'bg-[#1b2e52] text-white font-bold' : 'text-white/60 hover:text-white' }}">Chart of Accounts</a>
                    @endif
                    @if($isAdmin)
                    <a href="{{ route('master.users.index') }}" class="py-1.5 px-2 text-xs rounded {{ request()->routeIs('master.users.*') ? 'bg-[#1b2e52] text-white font-bold' : 'text-white/60 hover:text-white' }}">User Management</a>
                    @endif
                </div>
            </div>

            <!-- Purchase Group -->
            @if($isAdmin || $isPurchasing || $isGudang || $isFinance)
            @php $isPurchaseRoute = request()->is('purchase/*') && !request()->routeIs('purchase.payments.*'); @endphp
            <div x-data="{ open: {{ $isPurchaseRoute ? 'true' : 'false' }} }" class="group">
                <button @click="open = !open" type="button" class="w-full flex items-center justify-between px-4 py-2 rounded text-white/70 hover:bg-[#1b2e52] hover:text-white">
                    <div class="flex items-center">
                        <span class="material-symbols-outlined mr-3">shopping_cart</span>
                        <span class="text-sm font-medium">Purchase</span>
                    </div>
                    <span class="material-symbols-outlined text-sm" style="{{ $isPurchaseRoute ? 'transform: rotate(180deg);' : 'transform: rotate(0deg);' }} transition: transform 0.2s;" :style="open ? 'transform: rotate(180deg)' : 'transform: rotate(0deg)'">expand_more</span>
                </button>
                <div x-show="open" @if(!$isPurchaseRoute) x-cloak style="display: none;" @endif class="pl-9 pr-2 py-1 flex flex-col gap-1">
                    @if($isAdmin || $isPurchasing || $isGudang || $isSales)
                    <a href="{{ \Illuminate\Support\Facades\Route::has('purchase.requests.index') ? route('purchase.requests.index') : url('/purchase/requests') }}" class="py-1.5 px-2 text-xs rounded {{ request()->routeIs('purchase.requests.*') ? 'bg-[#1b2e52] text-white font-bold' : 'text-white/60 hover:text-white' }}">Purchase Request</a>
                    @endif
                    @if($isAdmin || $isPurchasing)
                    <a href="{{ route('purchase.orders.index') }}" class="py-1.5 px-2 text-xs rounded {{ request()->routeIs('purchase.orders.*') ? 'bg-[#1b2e52] text-white font-bold' : 'text-white/60 hover:text-white' }}">Purchase Order</a>
                    @endif
                    @if($isAdmin || $isGudang)
                    <a href="{{ route('purchase.goods-receipts.index') }}" class="py-1.5 px-2 text-xs rounded {{ request()->routeIs('purchase.goods-receipts.*') ? 'bg-[#1b2e52] text-white font-bold' : 'text-white/60 hover:text-white' }}">Penerimaan Barang</a>
                    @endif
                    @if($isAdmin || $isFinance)
                    <a href="{{ route('purchase.invoices.index') }}" class="py-1.5 px-2 text-xs rounded {{ request()->routeIs('purchase.invoices.*') ? 'bg-[#1b2e52] text-white font-bold' : 'text-white/60 hover:text-white' }}">Invoice Pembelian</a>
                    @endif
                    @if($isAdmin || $isPurchasing)
                    <a href="{{ route('purchase.returns.index') }}" class="py-1.5 px-2 text-xs rounded {{ request()->routeIs('purchase.returns.*') ? 'bg-[#1b2e52] text-white font-bold' : 'text-white/60 hover:text-white' }}">Retur Pembelian</a>
                    @endif
                    @if($isAdmin || $isPurchasing || $isFinance)
                    {{-- Monitoring PO dinonaktifkan sementara --}}
                    {{-- <a href="{{ route('purchase.reports.fulfillment') }}" class="py-1.5 px-2 text-xs rounded {{ request()->routeIs('purchase.reports.fulfillment') ? 'bg-[#1b2e52] text-white font-bold' : 'text-white/60 hover:text-white' }}">Monitoring PO</a> --}}
                    <a href="{{ route('purchase.reports.recap-by-product') }}" class="py-1.5 px-2 text-xs rounded {{ request()->routeIs('purchase.reports.recap-by-product') ? 'bg-[#1b2e52] text-white font-bold' : 'text-white/60 hover:text-white' }}">Rekap Beli per Barang</a>
                    @endif
                </div>
            </div>
            @endif

            <!-- Inventory Group -->
            @if($isAdmin || $isGudang || $isPurchasing || $isSales || $isFinance)
            @php $isInventoryRoute = request()->is('inventory/*'); @endphp
            <div x-data="{ open: {{ $isInventoryRoute ? 'true' : 'false' }} }" class="group">
                <button @click="open = !open" type="button" class="w-full flex items-center justify-between px-4 py-2 rounded text-white/70 hover:bg-[#1b2e52] hover:text-white">
                    <div class="flex items-center">
                        <span class="material-symbols-outlined mr-3">warehouse</span>
                        <span class="text-sm font-medium">Inventory</span>
                    </div>
                    <span class="material-symbols-outlined text-sm" style="{{ $isInventoryRoute ? 'transform: rotate(180deg);' : 'transform: rotate(0deg);' }} transition: transform 0.2s;" :style="open ? 'transform: rotate(180deg)' : 'transform: rotate(0deg)'">expand_more</span>
                </button>
                <div x-show="open" @if(!$isInventoryRoute) x-cloak style="display: none;" @endif class="pl-9 pr-2 py-1 flex flex-col gap-1">
                    <a href="{{ route('inventory.stock-summary') }}" class="py-1.5 px-2 text-xs rounded {{ request()->routeIs('inventory.stock-summary') ? 'bg-[#1b2e52] text-white font-bold' : 'text-white/60 hover:text-white' }}">Ringkasan Stok</a>
                    @if($isAdmin || $isGudang)
                    <a href="{{ route('inventory.stock-card') }}" class="py-1.5 px-2 text-xs rounded {{ request()->routeIs('inventory.stock-card') ? 'bg-[#1b2e52] text-white font-bold' : 'text-white/60 hover:text-white' }}">Kartu Stok</a>
                    <a href="{{ route('inventory.movements.index') }}" class="py-1.5 px-2 text-xs rounded {{ request()->routeIs('inventory.movements.*') ? 'bg-[#1b2e52] text-white font-bold' : 'text-white/60 hover:text-white' }}">Mutasi Stok</a>
                    <a href="{{ route('inventory.transfers.index') }}" class="py-1.5 px-2 text-xs rounded {{ request()->routeIs('inventory.transfers.*') ? 'bg-[#1b2e52] text-white font-bold' : 'text-white/60 hover:text-white' }}">Transfer Gudang</a>
                    <a href="{{ route('inventory.opname.index') }}" class="py-1.5 px-2 text-xs rounded {{ request()->routeIs('inventory.opname.*') ? 'bg-[#1b2e52] text-white font-bold' : 'text-white/60 hover:text-white' }}">Stock Opname</a>
                    <a href="{{ route('inventory.dispositions.index') }}" class="py-1.5 px-2 text-xs rounded {{ request()->routeIs('inventory.dispositions.*') ? 'bg-[#1b2e52] text-white font-bold' : 'text-white/60 hover:text-white' }}">Disposisi Karantina</a>
                    <a href="{{ route('inventory.reports.returns-by-product') }}" class="py-1.5 px-2 text-xs rounded {{ request()->routeIs('inventory.reports.returns-by-product') ? 'bg-[#1b2e52] text-white font-bold' : 'text-white/60 hover:text-white' }}">Rekap Retur Barang</a>
                    @endif
                </div>
            </div>
            @endif

            <!-- Sales Group -->
            @if($isAdmin || $isSales || $isGudang || $isFinance)
            @php $isSalesRoute = request()->is('sales/*') && !request()->routeIs('sales.payments.*'); @endphp
            <div x-data="{ open: {{ $isSalesRoute ? 'true' : 'false' }} }" class="group">
                <button @click="open = !open" type="button" class="w-full flex items-center justify-between px-4 py-2 rounded text-white/70 hover:bg-[#1b2e52] hover:text-white">
                    <div class="flex items-center">
                        <span class="material-symbols-outlined mr-3">sell</span>
                        <span class="text-sm font-medium">Sales</span>
                    </div>
                    <span class="material-symbols-outlined text-sm" style="{{ $isSalesRoute ? 'transform: rotate(180deg);' : 'transform: rotate(0deg);' }} transition: transform 0.2s;" :style="open ? 'transform: rotate(180deg)' : 'transform: rotate(0deg)'">expand_more</span>
                </button>
                <div x-show="open" @if(!$isSalesRoute) x-cloak style="display: none;" @endif class="pl-9 pr-2 py-1 flex flex-col gap-1">
                    @if($isAdmin || $isSales)
                    <a href="{{ route('sales.orders.index') }}" class="py-1.5 px-2 text-xs rounded {{ request()->routeIs('sales.orders.*') && !request()->routeIs('sales.reports.*') ? 'bg-[#1b2e52] text-white font-bold' : 'text-white/60 hover:text-white' }}">Sales Order</a>
                    @endif
                    @if($isAdmin || $isGudang)
                    <a href="{{ route('sales.deliveries.index') }}" class="py-1.5 px-2 text-xs rounded {{ request()->routeIs('sales.deliveries.*') ? 'bg-[#1b2e52] text-white font-bold' : 'text-white/60 hover:text-white' }}">Surat Jalan</a>
                    @endif
                    @if($isAdmin || $isFinance)
                    <a href="{{ route('sales.invoices.index') }}" class="py-1.5 px-2 text-xs rounded {{ request()->routeIs('sales.invoices.*') ? 'bg-[#1b2e52] text-white font-bold' : 'text-white/60 hover:text-white' }}">Invoice Penjualan</a>
                    @endif
                    @if($isAdmin || $isSales)
                    <a href="{{ route('sales.returns.index') }}" class="py-1.5 px-2 text-xs rounded {{ request()->routeIs('sales.returns.*') ? 'bg-[#1b2e52] text-white font-bold' : 'text-white/60 hover:text-white' }}">Retur Penjualan</a>
                    @endif
                    @if($isAdmin || $isSales || $isFinance)
                    {{-- Monitoring SO dinonaktifkan sementara --}}
                    {{-- <a href="{{ route('sales.reports.fulfillment') }}" class="py-1.5 px-2 text-xs rounded {{ request()->routeIs('sales.reports.fulfillment') ? 'bg-[#1b2e52] text-white font-bold' : 'text-white/60 hover:text-white' }}">Monitoring SO</a> --}}
                    <a href="{{ route('sales.reports.recap-by-product') }}" class="py-1.5 px-2 text-xs rounded {{ request()->routeIs('sales.reports.recap-by-product') ? 'bg-[#1b2e52] text-white font-bold' : 'text-white/60 hover:text-white' }}">Rekap Jual per Barang</a>
                    @endif
                </div>
            </div>
            @endif

            <!-- SECTION 2: FINANCE & ACCOUNTING (Hanya Admin & Finance) -->
            @if($isAdmin || $isFinance)
            <div class="sidebar-section-title">Finance & Akuntansi</div>

            @php 
                $isAccountingRoute = request()->is('accounting/*') || request()->routeIs('sales.payments.*') || request()->routeIs('purchase.payments.*');
                $isBukuJurnal = request()->routeIs('accounting.journals.*') || request()->routeIs('accounting.reports.ledger') || request()->routeIs('accounting.reports.trial-balance') || request()->routeIs('accounting.opening-balance.*') || request()->routeIs('accounting.periods.*');
                $isHutangPiutang = request()->routeIs('sales.payments.*') || request()->routeIs('purchase.payments.*') || request()->routeIs('accounting.reports.receivables*') || request()->routeIs('accounting.reports.payables*') || request()->routeIs('accounting.reports.ledger-payable') || request()->routeIs('accounting.reports.ledger-receivable');
                $isLaporanKeuangan = request()->routeIs('accounting.reports.profit-loss') || request()->routeIs('accounting.reports.balance-sheet') || request()->routeIs('accounting.reports.cash-flow') || request()->routeIs('accounting.reports.cash-forecast') || request()->routeIs('accounting.reports.gross-profit') || request()->routeIs('accounting.reports.stock-valuation');
            @endphp
            <div x-data="{ 
                open: {{ $isAccountingRoute ? 'true' : 'false' }},
                subBukuJurnal: {{ $isBukuJurnal ? 'true' : 'false' }},
                subHutangPiutang: {{ $isHutangPiutang ? 'true' : 'false' }},
                subLaporanKeuangan: {{ $isLaporanKeuangan ? 'true' : 'false' }}
            }" class="group">
                <button @click="open = !open" type="button" class="w-full flex items-center justify-between px-4 py-2 rounded text-white/70 hover:bg-[#1b2e52] hover:text-white">
                    <div class="flex items-center">
                        <span class="material-symbols-outlined mr-3">account_balance</span>
                        <span class="text-sm font-medium">Akuntansi</span>
                    </div>
                    <span class="material-symbols-outlined text-sm" style="{{ $isAccountingRoute ? 'transform: rotate(180deg);' : 'transform: rotate(0deg);' }} transition: transform 0.2s;" :style="open ? 'transform: rotate(180deg)' : 'transform: rotate(0deg)'">expand_more</span>
                </button>
                <div x-show="open" @if(!$isAccountingRoute) x-cloak style="display: none;" @endif class="pl-6 pr-2 py-1 flex flex-col gap-1">
                    
                    <!-- Sub-Master 1: Buku & Jurnal -->
                    <div class="group">
                        <button @click="subBukuJurnal = !subBukuJurnal" type="button" class="w-full flex items-center justify-between px-3 py-1.5 rounded text-white/70 hover:bg-[#1b2e52] hover:text-white">
                            <div class="flex items-center">
                                <span class="material-symbols-outlined mr-3">menu_book</span>
                                <span class="text-xs font-medium">Buku & Jurnal</span>
                            </div>
                            <span class="material-symbols-outlined text-sm" style="{{ $isBukuJurnal ? 'transform: rotate(180deg);' : 'transform: rotate(0deg);' }} transition: transform 0.2s;" :style="subBukuJurnal ? 'transform: rotate(180deg)' : 'transform: rotate(0deg)'">expand_more</span>
                        </button>
                        <div x-show="subBukuJurnal" @if(!$isBukuJurnal) x-cloak style="display: none;" @endif class="pl-8 pr-2 py-1 flex flex-col gap-1">
                            <a href="{{ route('accounting.journals.index') }}" class="py-1.5 px-2 text-xs rounded {{ request()->routeIs('accounting.journals.*') ? 'bg-[#1b2e52] text-white font-bold' : 'text-white/60 hover:text-white' }}">Jurnal Umum</a>
                            <a href="{{ route('accounting.reports.ledger') }}" class="py-1.5 px-2 text-xs rounded {{ request()->routeIs('accounting.reports.ledger') ? 'bg-[#1b2e52] text-white font-bold' : 'text-white/60 hover:text-white' }}">Buku Besar</a>
                            <a href="{{ route('accounting.reports.trial-balance') }}" class="py-1.5 px-2 text-xs rounded {{ request()->routeIs('accounting.reports.trial-balance') ? 'bg-[#1b2e52] text-white font-bold' : 'text-white/60 hover:text-white' }}">Neraca Saldo</a>
                            <a href="{{ route('accounting.opening-balance.index') }}" class="py-1.5 px-2 text-xs rounded {{ request()->routeIs('accounting.opening-balance.*') ? 'bg-[#1b2e52] text-white font-bold' : 'text-white/60 hover:text-white' }}">Saldo Awal Neraca</a>
                            <a href="{{ route('accounting.periods.index') }}" class="py-1.5 px-2 text-xs rounded {{ request()->routeIs('accounting.periods.*') ? 'bg-[#1b2e52] text-white font-bold' : 'text-white/60 hover:text-white' }}">Periode & Closing</a>
                        </div>
                    </div>

                    <!-- Sub-Master 2: Hutang & Piutang -->
                    <div class="group">
                        <button @click="subHutangPiutang = !subHutangPiutang" type="button" class="w-full flex items-center justify-between px-3 py-1.5 rounded text-white/70 hover:bg-[#1b2e52] hover:text-white">
                            <div class="flex items-center">
                                <span class="material-symbols-outlined mr-3">payments</span>
                                <span class="text-xs font-medium">Hutang & Piutang</span>
                            </div>
                            <span class="material-symbols-outlined text-sm" style="{{ $isHutangPiutang ? 'transform: rotate(180deg);' : 'transform: rotate(0deg);' }} transition: transform 0.2s;" :style="subHutangPiutang ? 'transform: rotate(180deg)' : 'transform: rotate(0deg)'">expand_more</span>
                        </button>
                        <div x-show="subHutangPiutang" @if(!$isHutangPiutang) x-cloak style="display: none;" @endif class="pl-8 pr-2 py-1 flex flex-col gap-1">
                            <a href="{{ route('sales.payments.index') }}" class="py-1.5 px-2 text-xs rounded {{ request()->routeIs('sales.payments.*') ? 'bg-[#1b2e52] text-white font-bold' : 'text-white/60 hover:text-white' }}">Pembayaran Piutang</a>
                            <a href="{{ route('purchase.payments.index') }}" class="py-1.5 px-2 text-xs rounded {{ request()->routeIs('purchase.payments.*') ? 'bg-[#1b2e52] text-white font-bold' : 'text-white/60 hover:text-white' }}">Pembayaran Hutang</a>
                            <a href="{{ route('accounting.reports.receivables-upcoming') }}" class="py-1.5 px-2 text-xs rounded {{ request()->routeIs('accounting.reports.receivables-upcoming') ? 'bg-[#1b2e52] text-white font-bold' : 'text-white/60 hover:text-white' }}">Piutang Jatuh Tempo</a>
                            <a href="{{ route('accounting.reports.receivables-by-customer') }}" class="py-1.5 px-2 text-xs rounded {{ request()->routeIs('accounting.reports.receivables-by-customer') || request()->routeIs('accounting.reports.ledger-receivable') ? 'bg-[#1b2e52] text-white font-bold' : 'text-white/60 hover:text-white' }}">Rekap Piutang per Customer</a>
                            <a href="{{ route('accounting.reports.receivables') }}" class="py-1.5 px-2 text-xs rounded {{ request()->routeIs('accounting.reports.receivables') ? 'bg-[#1b2e52] text-white font-bold' : 'text-white/60 hover:text-white' }}">Umur Piutang (AR Aging)</a>
                            <a href="{{ route('accounting.reports.payables-upcoming') }}" class="py-1.5 px-2 text-xs rounded {{ request()->routeIs('accounting.reports.payables-upcoming') ? 'bg-[#1b2e52] text-white font-bold' : 'text-white/60 hover:text-white' }}">Hutang Jatuh Tempo</a>
                            <a href="{{ route('accounting.reports.payables-by-vendor') }}" class="py-1.5 px-2 text-xs rounded {{ request()->routeIs('accounting.reports.payables-by-vendor') || request()->routeIs('accounting.reports.ledger-payable') ? 'bg-[#1b2e52] text-white font-bold' : 'text-white/60 hover:text-white' }}">Rekap Hutang per Vendor</a>
                            <a href="{{ route('accounting.reports.payables') }}" class="py-1.5 px-2 text-xs rounded {{ request()->routeIs('accounting.reports.payables') ? 'bg-[#1b2e52] text-white font-bold' : 'text-white/60 hover:text-white' }}">Umur Hutang (AP Aging)</a>
                        </div>
                    </div>

                    <!-- Sub-Master: Fixed Asset & Inventaris -->
                    <a href="{{ route('accounting.fixed-assets.index') }}" class="flex items-center justify-between px-3 py-1.5 rounded {{ request()->routeIs('accounting.fixed-assets.*') ? 'bg-[#1b2e52] text-white font-bold' : 'text-white/70 hover:bg-[#1b2e52] hover:text-white' }}">
                        <div class="flex items-center">
                            <span class="material-symbols-outlined mr-3 text-base">precision_manufacturing</span>
                            <span class="text-xs font-medium">Aset Tetap & Inventaris</span>
                        </div>
                    </a>

                    <!-- Sub-Master 3: Laporan Keuangan -->
                    <div class="group">
                        <button @click="subLaporanKeuangan = !subLaporanKeuangan" type="button" class="w-full flex items-center justify-between px-3 py-1.5 rounded text-white/70 hover:bg-[#1b2e52] hover:text-white">
                            <div class="flex items-center">
                                <span class="material-symbols-outlined mr-3">bar_chart</span>
                                <span class="text-xs font-medium">Laporan Keuangan</span>
                            </div>
                            <span class="material-symbols-outlined text-sm" style="{{ $isLaporanKeuangan ? 'transform: rotate(180deg);' : 'transform: rotate(0deg);' }} transition: transform 0.2s;" :style="subLaporanKeuangan ? 'transform: rotate(180deg)' : 'transform: rotate(0deg)'">expand_more</span>
                        </button>
                        <div x-show="subLaporanKeuangan" @if(!$isLaporanKeuangan) x-cloak style="display: none;" @endif class="pl-8 pr-2 py-1 flex flex-col gap-1">
                            <a href="{{ route('accounting.reports.profit-loss') }}" class="py-1.5 px-2 text-xs rounded {{ request()->routeIs('accounting.reports.profit-loss') ? 'bg-[#1b2e52] text-white font-bold' : 'text-white/60 hover:text-white' }}">Laba Rugi</a>
                            <a href="{{ route('accounting.reports.balance-sheet') }}" class="py-1.5 px-2 text-xs rounded {{ request()->routeIs('accounting.reports.balance-sheet') ? 'bg-[#1b2e52] text-white font-bold' : 'text-white/60 hover:text-white' }}">Neraca Keuangan</a>
                            <a href="{{ route('accounting.reports.cash-flow') }}" class="py-1.5 px-2 text-xs rounded {{ request()->routeIs('accounting.reports.cash-flow') ? 'bg-[#1b2e52] text-white font-bold' : 'text-white/60 hover:text-white' }}">Laporan Arus Kas</a>
                            <a href="{{ route('accounting.reports.cash-forecast') }}" class="py-1.5 px-2 text-xs rounded {{ request()->routeIs('accounting.reports.cash-forecast') ? 'bg-[#1b2e52] text-white font-bold' : 'text-white/60 hover:text-white' }}">Proyeksi Kas</a>
                            <a href="{{ route('accounting.reports.gross-profit') }}" class="py-1.5 px-2 text-xs rounded {{ request()->routeIs('accounting.reports.gross-profit') ? 'bg-[#1b2e52] text-white font-bold' : 'text-white/60 hover:text-white' }}">Tren Laba Kotor</a>
                            <a href="{{ route('accounting.reports.stock-valuation') }}" class="py-1.5 px-2 text-xs rounded {{ request()->routeIs('accounting.reports.stock-valuation') ? 'bg-[#1b2e52] text-white font-bold' : 'text-white/60 hover:text-white' }}">Valuasi Persediaan</a>
                        </div>
                    </div>

                </div>
            </div>
            @endif

            <!-- SECTION 3: APPROVALS (HANYA ADMIN) -->
            @if($isAdmin)
            @php
                $pendingApprovalCount = \App\Models\ApprovalRequest::where('status', 'pending')->count();
            @endphp
            <div class="sidebar-section-title">Otorisasi</div>
            <a href="{{ route('approvals.index') }}" 
               class="flex items-center justify-between px-4 py-2 rounded {{ request()->routeIs('approvals.*') ? 'bg-[#1b2e52] text-white font-bold' : 'text-white/70 hover:bg-[#1b2e52] hover:text-white' }}">
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
                <button type="submit" class="w-full flex items-center px-4 py-2 rounded text-white/60 hover:bg-red-500/20 hover:text-red-300 text-left">
                    <span class="material-symbols-outlined mr-3">logout</span>
                    <span class="text-sm font-medium">Logout</span>
                </button>
            </form>
        </nav>
    </aside>

    <!-- ========================================================= -->
    <!-- TOP HEADER BAR & MAIN CONTENT WRAPPER -->
    <!-- ========================================================= -->
    <div class="erp-main-wrapper pl-[240px]">
        <header id="erp-header" class="fixed top-0 left-[240px] right-0 h-[56px] bg-white border-b border-[#E2E8F0] z-40 flex items-center justify-between px-3 sm:px-6">
            <!-- Left Options: Hamburger Toggle & Breadcrumbs -->
            <div class="flex items-center gap-2 sm:gap-3 min-w-0">
                <!-- Hamburger Toggle Button (Mobile/Tablet & Desktop) -->
                <button 
                    type="button" 
                    @click="toggleSidebar()" 
                    class="p-2 -ml-1 text-[#44474e] hover:text-[#03193c] hover:bg-[#F5F6F8] rounded-lg transition-colors flex items-center justify-center shrink-0 cursor-pointer focus:outline-none"
                    title="Toggle Sidebar Menu"
                    aria-label="Toggle Sidebar Menu"
                >
                    <span class="material-symbols-outlined text-[22px]">menu</span>
                </button>

                <!-- Breadcrumbs -->
                <div class="flex items-center gap-1.5 sm:gap-2 text-[#6B7280] text-xs sm:text-sm font-sans whitespace-nowrap min-w-0 overflow-hidden">
                    <a href="{{ route('dashboard') }}" class="flex items-center text-[#6B7280] hover:text-[#03193c] transition-colors shrink-0" title="Dashboard">
                        <span class="material-symbols-outlined text-base">home</span>
                    </a>
                    <span class="material-symbols-outlined text-xs shrink-0 opacity-40">chevron_right</span>
                    <span class="shrink-0 font-medium hidden md:inline">FlashSuite Pro</span>
                    <span class="material-symbols-outlined text-xs shrink-0 opacity-40 hidden md:inline">chevron_right</span>
                    <span class="text-[#03193c] font-bold truncate">@yield('page-title', View::yieldContent('title', 'Dashboard'))</span>
                </div>
            </div>

            <!-- Right Options -->
            <div class="flex items-center gap-3 sm:gap-6">
                <button class="relative text-[#44474e] hover:text-[#03193c] transition-colors p-1" title="Notifikasi">
                    <span class="material-symbols-outlined">notifications</span>
                    <span class="absolute top-0.5 right-0.5 w-2 h-2 bg-[#ba1a1a] rounded-full"></span>
                </button>
                <div class="h-6 sm:h-8 w-px bg-[#E2E8F0]"></div>
                
                <!-- Profile Dropdown -->
                <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                    <button type="button" @click="open = !open" class="flex items-center gap-2 sm:gap-3 cursor-pointer">
                        <div class="text-right hidden sm:block">
                            <div class="text-sm font-bold text-[#0e1b35]">{{ auth()->user()->name }}</div>
                            <div class="text-[10px] text-[#44474e] uppercase tracking-tighter">{{ auth()->user()->role }}</div>
                        </div>
                        <div class="w-8 h-8 rounded-full bg-[#1b2e52] text-white flex items-center justify-center font-bold text-sm border border-[#E2E8F0]">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </div>
                    </button>

                    <!-- Dropdown Menu -->
                    <div x-show="open" x-cloak x-transition style="display: none;" class="absolute right-0 top-12 w-48 bg-white rounded-lg shadow-lg border border-[#E2E8F0] py-1 z-50">
                        <a href="{{ route('profile.edit') }}" class="px-4 py-2 text-sm text-[#0e1b35] hover:bg-[#F5F6F8] flex items-center gap-2">
                            <span class="material-symbols-outlined text-base">person</span> Profil Saya
                        </a>
                        <hr class="border-[#E2E8F0] my-1">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full text-left px-4 py-2 text-sm text-[#ba1a1a] hover:bg-red-50 flex items-center gap-2 cursor-pointer">
                                <span class="material-symbols-outlined text-base">logout</span> Logout
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content Area with Standard Padding -->
        <main class="relative pt-[56px] min-h-screen bg-[#F5F6F8]">
            <div class="p-4 sm:p-6 max-w-7xl mx-auto">
                @if(session('success'))
                    <div class="bg-[#DCFCE3] border border-[#166534]/20 text-[#166534] px-4 py-3 rounded-lg mb-4 flex items-center gap-2 text-sm font-medium">
                        <span class="material-symbols-outlined text-base">check_circle</span> {{ session('success') }}
                    </div>
                @endif
                @if(session('error'))
                    @php
                        $isPeriodLocked = str_contains(session('error'), 'periode yang sudah ditutup') || str_contains(session('error'), 'periode ini terkunci');
                    @endphp
                    @if($isPeriodLocked)
                        <div class="bg-amber-50 border border-amber-300 text-amber-900 px-4 py-3 rounded-lg mb-4 flex items-start gap-3 text-sm shadow-sm">
                            <span class="material-symbols-outlined text-xl text-amber-600 shrink-0 mt-0.5">lock</span>
                            <div class="flex-1">
                                <span class="font-bold text-amber-800 block mb-0.5">Periode Akuntansi Terkunci</span>
                                <span>{{ session('error') }}</span>
                            </div>
                        </div>
                        <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                if (typeof Swal !== 'undefined') {
                                    Swal.fire({
                                        icon: 'warning',
                                        title: 'Periode Terkunci',
                                        text: @json(session('error')),
                                        confirmButtonText: 'Mengerti',
                                        confirmButtonColor: '#d97706'
                                    });
                                }
                            });
                        </script>
                    @else
                        <div class="bg-[#FDE2E1] border border-[#B91C1C]/20 text-[#B91C1C] px-4 py-3 rounded-lg mb-4 flex items-center gap-2 text-sm font-medium">
                            <span class="material-symbols-outlined text-base">error</span> {{ session('error') }}
                        </div>
                    @endif
                @endif

                <!-- Page Content -->
                @yield('content', $slot ?? '')
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
                        popup: 'erp-swal-modal',
                        confirmButton: 'btn btn-danger',
                        cancelButton: 'btn btn-secondary'
                    },
                    buttonsStyling: false
                }).then((r) => { 
                    if (r.isConfirmed) {
                        form.submit(); 
                    }
                });
            });
        });

        // Global SweetAlert handler for workflow actions (e.g. Batalkan, Konfirmasi, Posting)
        document.querySelectorAll('[data-confirm-swal]').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const formId = this.dataset.confirmSwal;
                const form = document.getElementById(formId);
                if (!form) return;

                const title = this.dataset.swalTitle || 'Konfirmasi Tindakan';
                const messageHtml = this.dataset.swalHtml || this.dataset.swalText || 'Apakah Anda yakin ingin melanjutkan tindakan ini?';
                const icon = this.dataset.swalIcon || 'warning';
                const iconColor = this.dataset.swalIconColor || (icon === 'warning' ? '#dc2626' : (icon === 'question' ? '#2563eb' : '#16a34a'));
                const confirmBtnText = this.dataset.swalBtnText || 'Ya, Lanjutkan';
                const confirmBtnIcon = this.dataset.swalBtnIcon ? `<i class="${this.dataset.swalBtnIcon}" style="margin-right:6px;"></i> ` : '';
                const confirmBtnClass = this.dataset.swalBtnClass || 'btn btn-danger';
                const cancelBtnText = this.dataset.swalCancelText || 'Batal';

                Swal.fire({
                    title: title,
                    html: messageHtml,
                    icon: icon,
                    iconColor: iconColor,
                    showCancelButton: true,
                    confirmButtonText: confirmBtnIcon + confirmBtnText,
                    cancelButtonText: cancelBtnText,
                    reverseButtons: true,
                    focusConfirm: icon === 'question',
                    focusCancel: icon === 'warning',
                    customClass: {
                        popup: 'erp-swal-modal',
                        confirmButton: confirmBtnClass,
                        cancelButton: 'btn btn-secondary'
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
