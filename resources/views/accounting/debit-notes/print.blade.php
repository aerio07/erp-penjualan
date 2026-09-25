<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Debet Note - {{ $debitNote->dn_number }}</title>
    <style>
        @page { size: A4; margin: 15mm; }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 11px;
            color: #1e293b;
            margin: 0;
            padding: 0;
            line-height: 1.4;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 12px;
            margin-bottom: 16px;
        }
        .company-name {
            font-size: 16px;
            font-weight: 800;
            color: #0f172a;
            text-transform: uppercase;
        }
        .doc-title {
            font-size: 18px;
            font-weight: 800;
            color: #0369a1;
            text-align: right;
            margin: 0;
            text-transform: uppercase;
        }
        .doc-number {
            font-size: 12px;
            font-weight: 700;
            color: #334155;
            text-align: right;
            margin-top: 2px;
        }
        .meta-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 16px;
            background: #f8fafc;
            padding: 10px 14px;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
        }
        .meta-row {
            display: flex;
            margin-bottom: 4px;
        }
        .meta-label {
            width: 110px;
            font-weight: 600;
            color: #64748b;
        }
        .meta-value {
            font-weight: 600;
            color: #0f172a;
            flex: 1;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }
        th {
            background-color: #f1f5f9;
            color: #334155;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 10px;
            padding: 6px 8px;
            border: 1px solid #cbd5e1;
            text-align: left;
        }
        td {
            padding: 6px 8px;
            border: 1px solid #e2e8f0;
            font-size: 10.5px;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: 700; }
        .total-box {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 24px;
        }
        .total-table {
            width: 280px;
        }
        .total-table td {
            border: none;
            padding: 4px 8px;
        }
        .signatures {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-top: 30px;
            text-align: center;
        }
        .sig-box {
            border-top: 1px solid #94a3b8;
            margin-top: 50px;
            padding-top: 4px;
            font-weight: 600;
            color: #334155;
            font-size: 10px;
        }
        .no-print-bar {
            background: #0f172a;
            color: white;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        @media print {
            .no-print-bar { display: none; }
        }
    </style>
</head>
<body>
    <div class="no-print-bar">
        <div><strong>Dokumen Debet Note:</strong> {{ $debitNote->dn_number }}</div>
        <div>
            <button onclick="window.print()" style="background:#0284c7; color:#fff; border:none; padding:6px 16px; border-radius:4px; font-weight:bold; cursor:pointer;">
                Cetak / Simpan PDF
            </button>
            <button onclick="window.close()" style="background:#475569; color:#fff; border:none; padding:6px 14px; border-radius:4px; font-weight:bold; cursor:pointer; margin-left:8px;">
                Tutup
            </button>
        </div>
    </div>

    <div class="header">
        <div>
            <div class="company-name">PT. TRADING & DISTRIBUSI</div>
        </div>
        <div>
            <div class="doc-title">BUKTI DEBET NOTE</div>
            <div class="doc-number">No: {{ $debitNote->dn_number }}</div>
            <div style="font-size:10px; color:#64748b; text-align:right;">Status: {{ strtoupper($debitNote->status) }}</div>
        </div>
    </div>

    <div class="meta-grid">
        <div>
            <div class="meta-row">
                <span class="meta-label">Tipe Debet Note:</span>
                <span class="meta-value">{{ $debitNote->dn_type }} ({{ $debitNote->dn_type === 'AP' ? 'Potong Hutang Vendor' : 'Potong Piutang Customer' }})</span>
            </div>
            <div class="meta-row">
                <span class="meta-label">{{ $debitNote->dn_type === 'AP' ? 'Supplier / Vendor:' : 'Customer / Toko:' }}</span>
                <span class="meta-value">{{ $debitNote->partner_name }}</span>
            </div>
            <div class="meta-row">
                <span class="meta-label">Keterangan:</span>
                <span class="meta-value">{{ $debitNote->notes ?: '-' }}</span>
            </div>
        </div>
        <div>
            <div class="meta-row">
                <span class="meta-label">Tanggal Transaksi:</span>
                <span class="meta-value">{{ $debitNote->dn_date ? $debitNote->dn_date->format('d/m/Y') : '-' }}</span>
            </div>
            <div class="meta-row">
                <span class="meta-label">No. Draft:</span>
                <span class="meta-value">{{ $debitNote->draft_number ?: $debitNote->id }}</span>
            </div>
            <div class="meta-row">
                <span class="meta-label">Pengaturan Pajak:</span>
                <span class="meta-value">{{ $debitNote->tax_type }} ({{ $debitNote->tax_rate }}%)</span>
            </div>
        </div>
    </div>

    <!-- Tabel 1: Detail Faktur yang Dipotong -->
    <div style="font-weight:700; margin-bottom:6px; color:#334155; font-size:10.5px;">DETAIL FAKTUR YANG DIPOTONG:</div>
    <table>
        <thead>
            <tr>
                <th style="width:30px;" class="text-center">No</th>
                <th>No. Invoice</th>
                <th>No. Surat Jalan / Reff</th>
                <th class="text-center">Tanggal Inv</th>
                <th class="text-right">Sisa Tagihan</th>
                <th class="text-right">Potongan DN</th>
                <th class="text-right">Sisa Akhir</th>
            </tr>
        </thead>
        <tbody>
            @foreach($debitNote->invoices as $idx => $inv)
            <tr>
                <td class="text-center">{{ $idx + 1 }}</td>
                <td class="font-bold">{{ $inv->invoice_number }}</td>
                <td>{{ $inv->delivery_order_number ?: '-' }}</td>
                <td class="text-center">{{ $inv->invoice_date ? $inv->invoice_date->format('d/m/Y') : '-' }}</td>
                <td class="text-right">Rp {{ number_format($inv->remaining_amount, 2, ',', '.') }}</td>
                <td class="text-right font-bold">Rp {{ number_format($inv->dn_amount, 2, ',', '.') }}</td>
                <td class="text-right">Rp {{ number_format($inv->balance_amount, 2, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Tabel 2: Alokasi Akun COA -->
    <div style="font-weight:700; margin-bottom:6px; color:#334155; font-size:10.5px;">ALOKASI AKUN PEMBUKUAN (COA):</div>
    <table>
        <thead>
            <tr>
                <th style="width:30px;" class="text-center">No</th>
                <th>Akun Debet</th>
                <th>Akun Kredit</th>
                <th class="text-right">Nominal DN</th>
                <th class="text-right">Pajak</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($debitNote->accounts as $idx => $acc)
            <tr>
                <td class="text-center">{{ $idx + 1 }}</td>
                <td class="font-bold">{{ $acc->debitAccount->code ?? '' }} - {{ $acc->debitAccount->name ?? '-' }}</td>
                <td class="font-bold">{{ $acc->creditAccount->code ?? '' }} - {{ $acc->creditAccount->name ?? '-' }}</td>
                <td class="text-right font-bold">Rp {{ number_format($acc->dn_amount, 2, ',', '.') }}</td>
                <td class="text-right">Rp {{ number_format($acc->tax_amount, 2, ',', '.') }}</td>
                <td>{{ $acc->notes ?: '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Total -->
    <div class="total-box">
        <table class="total-table">
            <tr>
                <td class="font-bold">Total Potongan DN:</td>
                <td class="text-right font-bold" style="font-size:13px; color:#0369a1;">
                    Rp {{ number_format($debitNote->total_amount, 2, ',', '.') }}
                </td>
            </tr>
            @if($debitNote->tax_amount > 0)
            <tr>
                <td class="font-bold">Total Pajak:</td>
                <td class="text-right font-bold">Rp {{ number_format($debitNote->tax_amount, 2, ',', '.') }}</td>
            </tr>
            @endif
        </table>
    </div>

    <!-- Tanda Tangan -->
    <div class="signatures">
        <div>
            <div>Dibuat Oleh,</div>
            <div class="sig-box">{{ $debitNote->creator->name ?? 'Staff Admin' }}</div>
        </div>
        <div>
            <div>Diperiksa Oleh,</div>
            <div class="sig-box">Supervisor / Accounting</div>
        </div>
        <div>
            <div>Disetujui Oleh,</div>
            <div class="sig-box">Finance Manager</div>
        </div>
    </div>
</body>
</html>
