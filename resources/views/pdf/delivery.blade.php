<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Surat Jalan - {{ $delivery->delivery_number }}</title>
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
        .text-center { text-align: center; }
        .footer { margin-top: 50px; display: flex; justify-content: space-between; }
        .sig-block { text-align: center; }
        .sig-block .sig-line { width: 150px; border-top: 1px solid #1e293b; margin-top: 60px; margin-bottom: 4px; }
        .sig-block .sig-name { font-size: 11px; font-weight: 600; }
        .sig-block .sig-role { font-size: 10px; color: #64748b; }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <div class="company-name">TradePro ERP</div>
            <div class="company-sub">Sistem ERP Trading Penjualan</div>
        </div>
        <div class="doc-title">
            <h1>SURAT JALAN</h1>
            <div class="po-number">{{ $delivery->delivery_number }}</div>
            <div style="font-size:11px; color:#64748b; margin-top:4px;">Tanggal: {{ $delivery->delivery_date->format('d/m/Y') }}</div>
        </div>
    </div>

    <div class="info-grid">
        <div class="info-block">
            <label>Penerima / Tujuan Pengiriman</label>
            <value>{{ $delivery->recipient_name ?? $delivery->salesOrder->customer->name }}</value><br>
            <span style="color:#64748b; font-size:11px;">
                {{ $delivery->shipping_address ?? $delivery->salesOrder->customer->address }}
            </span>
        </div>
        <div class="info-block">
            <label>Ref. Sales Order</label>
            <value>{{ $delivery->salesOrder->so_number }}</value><br><br>
            <label>Gudang Pengirim</label>
            <value>{{ $delivery->warehouse->name }}</value>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Kode SKU</th>
                <th>Nama Produk</th>
                <th class="text-center">Qty Dikirim</th>
                <th>Kondisi</th>
            </tr>
        </thead>
        <tbody>
            @foreach($delivery->items as $i => $item)
            <tr>
                <td>{{ $i+1 }}</td>
                <td>{{ $item->salesOrderItem->product->sku }}</td>
                <td>{{ $item->salesOrderItem->product->name }}</td>
                <td class="text-center" style="font-weight:600;">{{ number_format($item->qty_delivered) }} {{ $item->salesOrderItem->product->unit }}</td>
                <td>{{ $item->condition }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    @if($delivery->notes)
    <div style="margin-top:12px; padding:10px; background:#f8fafc; border-radius:6px; font-size:11px; color:#64748b;">
        <strong>Catatan Pengiriman:</strong> {{ $delivery->notes }}
    </div>
    @endif

    <div class="footer">
        <div class="sig-block">
            <div class="sig-line"></div>
            <div class="sig-name">Penerima</div>
            <div class="sig-role">Customer</div>
        </div>
        <div class="sig-block">
            <div class="sig-line"></div>
            <div class="sig-name">Pengemudi / Ekspedisi</div>
            <div class="sig-role">Kurir</div>
        </div>
        <div class="sig-block">
            <div class="sig-line"></div>
            <div class="sig-name">{{ $delivery->user->name ?? 'Gudang' }}</div>
            <div class="sig-role">Kepala Gudang</div>
        </div>
    </div>
</body>
</html>
