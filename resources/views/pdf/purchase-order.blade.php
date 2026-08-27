<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>PO - {{ $order->po_number }}</title>
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
        .footer { margin-top: 40px; display: flex; justify-content: space-between; }
        .sig-block { text-align: center; }
        .sig-block .sig-line { width: 150px; border-top: 1px solid #1e293b; margin-top: 50px; margin-bottom: 4px; }
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
            <h1>PURCHASE ORDER</h1>
            <div class="po-number">{{ $order->po_number }}</div>
            <div style="font-size:11px; color:#64748b; margin-top:4px;">Tanggal: {{ $order->order_date->format('d/m/Y') }}</div>
        </div>
    </div>

    <div class="info-grid">
        <div class="info-block">
            <label>Kepada (Supplier)</label>
            <value>{{ $order->supplier->name }}</value><br>
            <span style="color:#64748b; font-size:11px;">
                {{ $order->supplier->contact_person }}<br>
                {{ $order->supplier->phone }}<br>
                {{ $order->supplier->address }}
            </span>
        </div>
        <div class="info-block">
            <label>Ship To (Tujuan Pengiriman)</label>
            <value style="color:#0f766e;">{{ $order->ship_to ?: 'Gudang Perusahaan' }}</value><br><br>
            <label>Expected Delivery</label>
            <value>{{ $order->expected_date ? $order->expected_date->format('d F Y') : '-' }}</value>
        </div>
        <div class="info-block">
            <label>Tanggal PO</label>
            <value>{{ $order->order_date->format('d F Y') }}</value><br><br>
            <label>Status / Dibuat Oleh</label>
            <div class="badge">{{ strtoupper($order->status) }}</div>
            <div style="font-size:11px; color:#64748b; margin-top:3px;">{{ $order->user->name }}</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Produk</th>
                <th>SKU</th>
                <th>Unit</th>
                <th class="text-right">Qty</th>
                <th class="text-right">Harga Satuan</th>
                <th class="text-right">Diskon</th>
                <th class="text-right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $i => $item)
            <tr>
                <td>{{ $i+1 }}</td>
                <td>{{ $item->product->name }}</td>
                <td>{{ $item->product->sku }}</td>
                <td>{{ $item->product->unit }}</td>
                <td class="text-right">{{ number_format($item->qty_ordered) }}</td>
                <td class="text-right">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                <td class="text-right">{{ $item->discount_percent > 0 ? $item->discount_percent.'%' : '-' }}</td>
                <td class="text-right">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div style="display:flex; justify-content:flex-end;">
        <div style="width:280px;">
            <div class="total-section">
                <div class="total-row"><span>Subtotal:</span><span>Rp {{ number_format($order->items->sum('subtotal'), 0, ',', '.') }}</span></div>
                @if($order->discount_amount > 0)
                <div class="total-row" style="color:#ef4444;"><span>Diskon Header:</span><span>- Rp {{ number_format($order->discount_amount, 0, ',', '.') }}</span></div>
                @endif
                <div class="total-row"><span>PPN ({{ $order->tax_rate }}%):</span><span>Rp {{ number_format($order->tax_amount, 0, ',', '.') }}</span></div>
                <div class="total-row total-grand"><span>TOTAL:</span><span>Rp {{ number_format($order->total_amount, 0, ',', '.') }}</span></div>
            </div>
        </div>
    </div>

    @if($order->notes)
    <div style="margin-top:16px; padding:10px; background:#f8fafc; border-radius:6px; font-size:11px; color:#64748b;">
        <strong>Catatan:</strong> {{ $order->notes }}
    </div>
    @endif

    <div class="footer">
        <div class="sig-block">
            <div class="sig-line"></div>
            <div class="sig-name">{{ $order->supplier->name }}</div>
            <div class="sig-role">Supplier</div>
        </div>
        <div class="sig-block">
            <div class="sig-line"></div>
            <div class="sig-name">{{ $order->user->name }}</div>
            <div class="sig-role">Purchasing</div>
        </div>
        <div class="sig-block">
            <div class="sig-line"></div>
            <div class="sig-name">Direktur</div>
            <div class="sig-role">Approval</div>
        </div>
    </div>
</body>
</html>
