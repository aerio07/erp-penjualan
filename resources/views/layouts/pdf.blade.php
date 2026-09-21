<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Dokumen')</title>
    <style>
        @page {
            margin: 15mm 18mm 15mm 18mm;
            size: a4 portrait;
        }
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, 'DejaVu Sans', sans-serif;
            font-size: 8.5pt;
            color: #222222;
            line-height: 1.4;
            background: #ffffff;
            padding: 30px 65px 40px 65px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            border-spacing: 0;
        }
        td, th {
            vertical-align: top;
        }

        /* ----------------------------------------------------
           1. CENTERED HEADER (Standardized Design)
        ---------------------------------------------------- */
        .doc-header {
            text-align: center;
            margin-bottom: 12px;
        }
        .doc-title {
            font-size: 19pt;
            font-weight: 800;
            color: #111111;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 3px;
        }
        .doc-subtitle {
            font-size: 9.5pt;
            color: #444444;
            font-weight: 600;
        }
        .header-divider {
            border-bottom: 2px solid #222222;
            margin: 12px 0 16px 0;
        }

        /* ----------------------------------------------------
           2. SECTION HEADINGS
        ---------------------------------------------------- */
        .section-heading {
            font-size: 8pt;
            font-weight: 700;
            color: #555555;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }

        /* ----------------------------------------------------
           3. PARTIES / TARGET PARTY SECTION
        ---------------------------------------------------- */
        .party-box {
            margin-bottom: 16px;
        }
        .parties-table {
            margin-bottom: 16px;
        }
        .party-col {
            width: 50%;
        }
        .party-name {
            font-size: 9.5pt;
            font-weight: 700;
            color: #111111;
            margin-bottom: 2px;
        }
        .party-info {
            font-size: 8.5pt;
            color: #444444;
            line-height: 1.4;
        }

        /* ----------------------------------------------------
           4. DETAILS SECTION (Key - Value Pairs)
        ---------------------------------------------------- */
        .details-box {
            margin-bottom: 16px;
        }
        .details-table {
            width: 100%;
        }
        .details-table td {
            padding: 2px 0;
            font-size: 8.5pt;
            vertical-align: top;
        }
        .detail-label {
            width: 42%;
            font-weight: 700;
            color: #111111;
        }
        .detail-colon {
            width: 4%;
            color: #111111;
            text-align: center;
        }
        .detail-value {
            width: 54%;
            color: #333333;
        }

        /* ----------------------------------------------------
           5. ITEMS TABLE (Clean styling, #f2f2f2 header)
        ---------------------------------------------------- */
        .items-section {
            margin-bottom: 14px;
        }
        .items-table {
            width: 100%;
            margin-top: 4px;
        }
        .items-table thead th {
            background-color: #f2f2f2;
            color: #111111;
            font-size: 8pt;
            font-weight: 700;
            padding: 7px 8px;
            border-top: 1px solid #e0e0e0;
            border-bottom: 1px solid #e0e0e0;
        }
        .items-table tbody td {
            font-size: 8.5pt;
            color: #333333;
            padding: 8px 8px;
            border-bottom: 1px solid #eeeeee;
        }
        .text-left { text-align: left; }
        .text-center { text-align: center; }
        .text-right { text-align: right; white-space: nowrap; }

        /* ----------------------------------------------------
           6. TOTALS SECTION (Right Aligned Summary)
        ---------------------------------------------------- */
        .totals-table {
            width: 100%;
            margin-bottom: 20px;
        }
        .totals-summary {
            width: 44%;
            float: right;
        }
        .totals-summary td {
            padding: 3px 6px;
            font-size: 8.5pt;
        }
        .tot-label {
            font-weight: 700;
            color: #222222;
            text-align: right;
            width: 50%;
        }
        .tot-value {
            text-align: right;
            color: #111111;
            width: 50%;
            white-space: nowrap;
        }
        .tot-discount {
            color: #111111;
        }
        .total-final td {
            border-top: 1.5px solid #222222;
            padding-top: 6px;
            font-size: 10pt;
            font-weight: 800;
            color: #000000;
        }

        /* ----------------------------------------------------
           7. TERMS & CONDITIONS / NOTES
        ---------------------------------------------------- */
        .terms-section {
            margin-bottom: 20px;
            clear: both;
        }
        .terms-content {
            font-size: 7.5pt;
            color: #666666;
            line-height: 1.4;
        }

        /* ----------------------------------------------------
           8. SIGNATURES SECTION
        ---------------------------------------------------- */
        .sig-table {
            width: 100%;
            margin-top: 18px;
            clear: both;
        }
        .sig-col {
            text-align: center;
            padding: 0 10px;
            vertical-align: top;
        }
        .sig-col-2 { width: 50%; }
        .sig-col-3 { width: 33.33%; }
        .sig-label {
            font-size: 7.5pt;
            color: #666666;
            margin-bottom: 45px;
            text-transform: uppercase;
            font-weight: 600;
        }
        .sig-line {
            border-top: 1px solid #333333;
            width: 130px;
            margin: 0 auto 4px auto;
        }
        .sig-name {
            font-size: 8pt;
            font-weight: 700;
            color: #111111;
        }
        .sig-role {
            font-size: 7pt;
            color: #777777;
        }

        /* Document Status (Clean neutral text, no background) */
        .badge, .doc-status {
            display: inline;
            padding: 0;
            font-size: 8.5pt;
            font-weight: 700;
            color: #222222;
            text-transform: uppercase;
            background: none !important;
            border: none;
        }
        .badge-success, .badge-warning, .badge-danger, .badge-info, .badge-gray {
            background: none !important;
            color: #222222 !important;
        }
    </style>
    @yield('extra_css')
</head>
<body>

    {{-- HEADER BLOCK --}}
    @hasSection('header')
        @yield('header')
    @else
        <div class="doc-header">
            @if(extension_loaded('gd') && file_exists(public_path('assets/FlashSuitePro - black.png')))
                <div style="margin-bottom: 6px;">
                    <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('assets/FlashSuitePro - black.png'))) }}" style="height: 22px; max-width: 180px;" alt="FlashSuite Pro">
                </div>
            @endif
            <div class="doc-title">@yield('doc-title')</div>
            <div class="doc-subtitle">@yield('doc-subtitle')</div>
        </div>
        <div class="header-divider"></div>
    @endif

    {{-- MAIN BODY CONTENT --}}
    @yield('content')

    {{-- SIGNATURES BLOCK --}}
    @yield('signatures')

</body>
</html>
