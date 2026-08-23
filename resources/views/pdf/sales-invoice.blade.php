<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Invoice - {{ $invoice->invoice_number }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; color: #1e293b; line-height: 1.5; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; padding: 20px 0 16px; border-bottom: 2px solid #6366f1; margin-bottom: 20px; }
        .company-name { font-size: 22px; font-weight: 700; color: #6366f1; }
        .company-sub  { font-size: 11px; color: #64748b; margin-top: 2px; }
        .doc-title { text-align: right; }
        .doc-title h1 { font-size: 20px; font-weight: 700; color: #1e293b; }
        .doc-title .po-number { font-size: 14px; color: #6366f1; font-weight: 600; }
        .info-grid { display: flex; gap: 40px; margin-bottom: 20px; }
        .info-block { flex: 1; }
        .info-block label { font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; font-weight: 600; display: block; margin-bottom: 2px; }
        .info-block value { font-size: 12px; font-weight: 600; color: #1e293b; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        th { background: #f1f5f9; padding: 8px 10px; text-align: left; font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; }
        td { padding: 9px 10px; border-bottom: 1px solid #f1f5f9; font-size: 12px; }
        .text-right { text-align: right; }
        .total-section { margin-top: 8px; border-top: 1px solid #e2e8f0; padding-top: 12px; }
        .total-row { display: flex; justify-content: space-between; margin-bottom: 6px; }
        .total-grand { font-size: 15px; font-weight: 700; color: #6366f1; border-top: 2px solid #6366f1; padding-top: 8px; margin-top: 4px; }
        .badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 10px; font-weight: 600; background: #dbeafe; color: #1d4ed8; }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <div class="company-name">TradePro ERP</div>
            <div class="company-sub">Sistem ERP Trading Penjualan</div>
        </div>
        <div class="doc-title">
            <h1>INVOICE PENJUALAN</h1>
            <div class="po-number">{{ $invoice->invoice_number }}</div>
            <div style="font-size:11px; color:#64748b; margin-top:4px;">Tanggal: {{ $invoice->invoice_date->format('d/m/Y') }}</div>
        </div>
    </div>

    <div class="info-grid">
        <div class="info-block">
            <label>Tagihan Kepada (Customer)</label>
            <value>{{ $invoice->salesOrder->customer->name ?? '-' }}</value><br>
            <span style="color:#64748b; font-size:11px;">
                Ref SO: {{ $invoice->salesOrder->so_number }}<br>
                Alamat: {{ $invoice->salesOrder->customer->address ?? '-' }}
            </span>
        </div>
        <div class="info-block">
            <label>Tgl Invoice</label>
            <value>{{ $invoice->invoice_date->format('d F Y') }}</value><br><br>
            <label>Tgl Jatuh Tempo</label>
            <value>{{ $invoice->due_date->format('d F Y') }}</value>
        </div>
        <div class="info-block">
            <label>Status Pembayaran</label>
            <div class="badge">{{ strtoupper($invoice->status) }}</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Produk</th>
                <th class="text-right">Qty Ditagih</th>
                <th class="text-right">Harga Satuan</th>
                <th class="text-right">Diskon</th>
                <th class="text-right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @php
                $invoiceItems = $invoice->items->isNotEmpty() ? $invoice->items : $invoice->salesOrder->items;
            @endphp
            @foreach($invoiceItems as $i => $item)
            @php
                $qty = $item->qty_invoiced ?? $item->qty_ordered ?? 0;
                $price = $item->unit_price;
                $discPercent = $item->discount_percent ?? 0;
                $subtotal = $item->subtotal ?? (($qty * $price) - (($qty * $price) * ($discPercent / 100)));
            @endphp
            <tr>
                <td>{{ $i+1 }}</td>
                <td>{{ $item->product->name ?? '-' }}</td>
                <td class="text-right">{{ number_format($qty) }} {{ $item->product->unit ?? 'pcs' }}</td>
                <td class="text-right">Rp {{ number_format($price, 0, ',', '.') }}</td>
                <td class="text-right">{{ $discPercent > 0 ? $discPercent . '%' : '-' }}</td>
                <td class="text-right">Rp {{ number_format($subtotal, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div style="display:flex; justify-content:flex-end;">
        <div style="width:280px;">
            <div class="total-section">
                <div class="total-row"><span>Subtotal (DPP):</span><span>Rp {{ number_format($invoice->amount, 0, ',', '.') }}</span></div>
                <div class="total-row"><span>PPN ({{ $invoice->tax_rate }}%):</span><span>Rp {{ number_format($invoice->tax_amount, 0, ',', '.') }}</span></div>
                <div class="total-row total-grand"><span>TOTAL TAGIHAN:</span><span>Rp {{ number_format($invoice->effective_total_amount, 0, ',', '.') }}</span></div>
                @if($invoice->total_reversed_amount > 0)
                <div class="total-row" style="color:#dc2626; font-weight:600; margin-top:6px;"><span>Pengurang Retur:</span><span>- Rp {{ number_format($invoice->total_reversed_amount, 0, ',', '.') }}</span></div>
                @endif
                <div class="total-row" style="color:#059669; font-weight:600; margin-top:6px;"><span>Sudah Dibayar:</span><span>Rp {{ number_format($invoice->total_paid, 0, ',', '.') }}</span></div>
                <div class="total-row" style="color:#dc2626; font-weight:700;"><span>Sisa Piutang:</span><span>Rp {{ number_format($invoice->outstanding_amount, 0, ',', '.') }}</span></div>
            </div>
        </div>
    </div>
</body>
</html>
