@extends('layouts.app')
@section('title', 'Detail Surat Jalan - ' . $delivery->delivery_number)
@section('page-title', 'Detail Surat Jalan (Pengiriman)')

@section('content')
@php
    $customerTermDays = $delivery->salesOrder?->customer?->payment_term_days ?? 30;
    $customerTermLabel = $delivery->salesOrder?->customer?->payment_term ?? ($customerTermDays . ' hari');
    $defaultDueDate = date('Y-m-d', strtotime('+' . $customerTermDays . ' days'));
@endphp
<div class="animate-in" x-data="{ 
    showConfirmModal: false, 
    autoInvoice: false,
    receivedDate: '{{ date('Y-m-d') }}',
    invoiceDate: '{{ date('Y-m-d') }}',
    dueDate: '{{ $defaultDueDate }}',
    paymentTerm: {{ $customerTermDays }},
    paymentTermLabel: '{{ $customerTermLabel }}',
    onInvoiceDateChange() {
        if (!this.invoiceDate) return;
        const d = new Date(this.invoiceDate);
        if (isNaN(d.getTime())) return;
        d.setDate(d.getDate() + (parseInt(this.paymentTerm) || 0));
        const yyyy = d.getFullYear();
        const mm = String(d.getMonth() + 1).padStart(2, '0');
        const dd = String(d.getDate()).padStart(2, '0');
        this.dueDate = `${yyyy}-${mm}-${dd}`;
    }
}">
    <x-detail-header 
        :title="$delivery->delivery_number"
        :back-url="route('sales.deliveries.index')"
    >
        <x-slot:badge>
            @if($delivery->status === 'delivered')
                <span class="badge badge-success"><i class="fa-solid fa-circle-check"></i> Diterima (Delivered)</span>
            @elseif($delivery->status === 'in_transit')
                <span class="badge badge-warning"><i class="fa-solid fa-truck-fast"></i> Dalam Pengiriman (In Transit)</span>
            @else
                <span class="badge">{{ $delivery->status_label }}</span>
            @endif

            @if($delivery->is_invoiced)
                <span class="badge badge-info"><i class="fa-solid fa-receipt"></i> Invoiced</span>
            @endif
        </x-slot:badge>
        <x-slot:subtitle>
            Pengiriman untuk SO: <a href="{{ route('sales.orders.show', $delivery->salesOrder) }}" style="color:var(--primary); font-weight:600;">{{ $delivery->salesOrder->so_number }}</a>
        </x-slot:subtitle>
        <x-slot:actions>
            @if($delivery->status === 'in_transit')
            <button type="button" @click="showConfirmModal = true" class="btn btn-primary">
                <i class="fa-solid fa-clipboard-check"></i> Konfirmasi Penerimaan Barang
            </button>
            @elseif($delivery->status === 'delivered' && !$delivery->is_invoiced)
            <a href="{{ route('sales.invoices.create', ['so_id' => $delivery->sales_order_id, 'delivery_id' => $delivery->id]) }}" class="btn btn-primary">
                <i class="fa-solid fa-file-invoice-dollar"></i> Terbitkan Invoice Penjualan
            </a>
            @endif

            @if(in_array($delivery->status, ['in_transit', 'delivered']))
            <a href="{{ route('pdf.delivery', $delivery) }}" class="btn btn-secondary" target="_blank">
                <i class="fa-solid fa-file-pdf"></i> Cetak Surat Jalan PDF
            </a>
            @endif
        </x-slot:actions>
    </x-detail-header>

    <div class="grid grid-3" style="margin-bottom:20px;">
        {{-- Card Informasi Pengiriman --}}
        <div class="card" style="grid-column:span 2;">
            <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
                <h3>Informasi Pengiriman & Penerimaan</h3>
                @if($delivery->status === 'delivered')
                    <span class="badge badge-success"><i class="fa-solid fa-check"></i> Barang Sampai</span>
                @else
                    <span class="badge badge-warning"><i class="fa-solid fa-clock"></i> Belum Sampai</span>
                @endif
            </div>
            <div class="card-body">
                <div class="form-row form-row-2">
                    <div>
                        <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Customer & Lokasi Pengiriman</div>
                        <div style="font-weight:600;">{{ $delivery->recipient_name ?? $delivery->salesOrder->customer->name }}</div>
                        <div style="font-size:13px; color:var(--text-secondary); margin-top:2px;">{{ $delivery->shipping_address ?? '-' }}</div>
                    </div>
                    <div>
                        <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Gudang Asal Barang</div>
                        <div style="font-weight:600; color:var(--primary);">
                            <i class="fa-solid fa-warehouse"></i> {{ $delivery->warehouse->name ?? '-' }}
                        </div>
                    </div>
                    <div>
                        <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Tanggal Pengiriman (Kirim)</div>
                        <div style="font-weight:600;">{{ $delivery->delivery_date ? $delivery->delivery_date->format('d F Y') : '-' }}</div>
                    </div>
                    <div>
                        <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Tanggal Diterima Customer</div>
                        <div style="font-weight:600; {{ $delivery->received_date ? 'color:#059669;' : 'color:var(--text-secondary);' }}">
                            {{ $delivery->received_date ? $delivery->received_date->format('d F Y') : 'Belum Diterima' }}
                        </div>
                    </div>
                    <div>
                        <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Disiapkan / Dikirim Oleh</div>
                        <div style="font-weight:600;">{{ $delivery->user->name ?? '-' }}</div>
                    </div>
                    <div>
                        <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Kondisi Barang</div>
                        <span class="badge badge-done">{{ ucfirst($delivery->condition_status) }}</span>
                    </div>
                </div>

                @if($delivery->receipt_notes)
                <div style="margin-top:14px; padding:12px 14px; background:#ecfdf5; border:1px solid #a7f3d0; border-radius:8px; font-size:13px; color:#065f46;">
                    <strong><i class="fa-solid fa-clipboard-check"></i> Catatan Tanda Terima Customer:</strong>
                    <div style="margin-top:4px;">{{ $delivery->receipt_notes }}</div>
                </div>
                @endif

                @if($delivery->notes)
                <div style="margin-top:12px; padding:12px 14px; background:#f8fafc; border:1px solid var(--border); border-radius:8px; font-size:13px; color:var(--text-secondary);">
                    <i class="fa-solid fa-note-sticky" style="margin-right:6px;"></i> <strong>Catatan Pengiriman:</strong> {{ $delivery->notes }}
                </div>
                @endif
            </div>
        </div>

        {{-- Card Status Faktur & Statistik --}}
        <div style="display:flex; flex-direction:column; gap:16px;">
            <div class="card">
                <div class="card-header"><h3>Status Faktur (Invoice)</h3></div>
                <div class="card-body">
                    @if($delivery->is_invoiced && $delivery->salesInvoice)
                        <div style="padding:14px; background:#eff6ff; border:1px solid #bfdbfe; border-radius:8px;">
                            <div style="font-size:12px; color:#1e40af; font-weight:600; text-transform:uppercase;">Telah Di-Invoice</div>
                            <div style="margin-top:6px;">
                                <a href="{{ route('sales.invoices.show', $delivery->salesInvoice) }}" style="font-size:16px; font-weight:700; color:var(--primary); text-decoration:none;">
                                    #{{ $delivery->salesInvoice->invoice_number }}
                                </a>
                            </div>
                            <div style="font-size:13px; margin-top:4px; color:var(--text-secondary);">
                                Total Tagihan: <strong>Rp {{ number_format($delivery->salesInvoice->total_amount, 0, ',', '.') }}</strong>
                            </div>
                            <div style="font-size:12px; color:var(--text-secondary); margin-top:2px;">
                                Jatuh Tempo: {{ $delivery->salesInvoice->due_date ? $delivery->salesInvoice->due_date->format('d/m/Y') : '-' }}
                            </div>
                            <div style="margin-top:10px;">
                                <span class="badge {{ $delivery->salesInvoice->status === 'paid' ? 'badge-success' : 'badge-warning' }}">
                                    {{ ucfirst($delivery->salesInvoice->status) }}
                                </span>
                            </div>
                        </div>
                    @elseif($delivery->status === 'delivered')
                        <div style="padding:14px; background:#f0fdf4; border:1px solid #bbf7d0; border-radius:8px;">
                            <div style="font-size:13px; color:#166534; font-weight:600;">
                                <i class="fa-solid fa-circle-check"></i> Siap Diterbitkan Invoice
                            </div>
                            <p style="font-size:12.5px; color:var(--text-secondary); margin:6px 0 12px 0;">
                                Barang telah sampai dan diterima. Anda dapat menerbitkan invoice tagihan sekarang.
                            </p>
                            <a href="{{ route('sales.invoices.create', ['so_id' => $delivery->sales_order_id, 'delivery_id' => $delivery->id]) }}" class="btn btn-primary btn-sm" style="width:100%; justify-content:center;">
                                <i class="fa-solid fa-file-invoice-dollar"></i> Terbitkan Invoice Sekarang
                            </a>
                        </div>
                    @else
                        <div style="padding:14px; background:#f8fafc; border:1px solid var(--border); border-radius:8px;">
                            <div style="font-size:13px; color:var(--text-secondary); font-weight:600;">
                                <i class="fa-solid fa-hourglass-half"></i> Menunggu Konfirmasi Penerimaan
                            </div>
                            <p style="font-size:12.5px; color:var(--text-secondary); margin:6px 0 0 0;">
                                Invoice dapat diterbitkan setelah konfirmasi penerimaan barang oleh customer.
                            </p>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h3>Statistik Pengiriman</h3></div>
                <div class="card-body">
                    <div style="font-size:12px; color:var(--text-secondary);">Total Kuantitas Dikirim</div>
                    <div style="font-size:24px; font-weight:700; color:var(--primary); margin-top:4px;">
                        {{ number_format($delivery->items->sum('qty_delivered')) }} pcs
                    </div>
                    <div style="font-size:12px; color:var(--text-secondary); margin-top:10px;">Total Baris Item</div>
                    <div style="font-size:16px; font-weight:600; margin-top:2px;">
                        {{ $delivery->items->count() }} Jenis Produk
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Items Table --}}
    <div class="card">
        <div class="card-header"><h3>Detail Barang Dalam Surat Jalan</h3></div>
        <div class="table-responsive">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th style="width:50px;">#</th>
                        <th>Produk</th>
                        <th style="text-align:center; width:150px;">Qty Dikirim</th>
                        <th style="text-align:center; width:150px;">Status Invoice</th>
                        <th style="text-align:center; width:150px;">Kondisi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($delivery->items as $idx => $item)
                    <tr>
                        <td>{{ $idx + 1 }}</td>
                        <td>
                            <div style="font-weight:600;">{{ $item->salesOrderItem->product->name ?? '-' }}</div>
                            <div style="font-size:12px; color:var(--text-secondary);">SKU: {{ $item->salesOrderItem->product->sku ?? '-' }}</div>
                        </td>
                        <td style="text-align:center; font-weight:600; font-size:14.5px;">
                            {{ $item->qty_delivered }} {{ $item->salesOrderItem->product->unit ?? 'pcs' }}
                        </td>
                        <td style="text-align:center;">
                            @if($delivery->is_invoiced)
                                <span class="badge badge-info"><i class="fa-solid fa-check"></i> Invoiced ({{ $item->invoiced_qty ?? $item->qty_delivered }})</span>
                            @else
                                <span class="badge" style="background:#f1f5f9; color:#64748b;">Pending</span>
                            @endif
                        </td>
                        <td style="text-align:center;">
                            <span class="badge badge-done">{{ $item->condition }}</span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Modal Konfirmasi Penerimaan Barang --}}
    <div x-show="showConfirmModal" x-cloak style="position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9999; display:flex; align-items:center; justify-content:center; padding:16px; overflow-y:auto;">
        <div @click.away="showConfirmModal = false" class="card" style="width:100%; max-width:560px; max-height:calc(100vh - 32px); display:flex; flex-direction:column; background:#fff; box-shadow:0 20px 25px -5px rgba(0,0,0,0.2); border-radius:12px; margin:auto; overflow:hidden;">
            <div class="card-header" style="flex-shrink:0; display:flex; justify-content:space-between; align-items:center; background:#f8fafc; border-bottom:1px solid var(--border);">
                <h3 style="margin:0; font-size:16px; display:flex; align-items:center; gap:8px;">
                    <i class="fa-solid fa-clipboard-check" style="color:var(--primary);"></i> Konfirmasi Penerimaan Barang
                </h3>
                <button type="button" @click="showConfirmModal = false" style="background:none; border:none; font-size:18px; cursor:pointer; color:var(--text-secondary);">&times;</button>
            </div>

            <form method="POST" action="{{ route('sales.deliveries.confirm-receipt', $delivery) }}" style="display:flex; flex-direction:column; flex:1; min-height:0; margin:0;">
                @csrf
                @method('PATCH')

                <div class="card-body" style="padding:20px; display:flex; flex-direction:column; gap:16px; overflow-y:auto; flex:1; min-height:0;">
                    <div style="font-size:13px; color:var(--text-secondary);">
                        Pastikan fisik barang telah benar-benar diterima oleh pihak customer / pembeli sebelum konfirmasi dilakukan.
                    </div>

                    <div class="form-group">
                        <label class="form-label">Tanggal Barang Diterima Customer <span style="color:var(--danger);">*</span></label>
                        <input type="date" name="received_date" x-model="receivedDate" class="form-control" min="{{ $delivery->delivery_date ? $delivery->delivery_date->toDateString() : date('Y-m-d') }}" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Nama Penerima di Lokasi Customer</label>
                        <input type="text" name="recipient_name" class="form-control" value="{{ $delivery->recipient_name }}" placeholder="Nama staf / penanggung jawab penerima">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Catatan Tanda Terima / Bukti Diterima</label>
                        <textarea name="receipt_notes" class="form-control" rows="2" placeholder="Catatan kondisi saat diterima, nama kurir, atau no. tanda terima fisik...">{{ old('receipt_notes') }}</textarea>
                    </div>

                    @if(!$delivery->is_invoiced)
                    <div style="padding:12px; background:#f8fafc; border:1px solid var(--border); border-radius:8px;">
                        <label style="display:flex; align-items:center; gap:10px; cursor:pointer; font-weight:600; font-size:13px;">
                            <input type="checkbox" name="auto_generate_invoice" value="1" x-model="autoInvoice" style="width:18px; height:18px; accent-color:var(--primary);">
                            <span>Langsung terbitkan Sales Invoice setelah konfirmasi</span>
                        </label>

                        <div x-show="autoInvoice" style="margin-top:12px; display:flex; flex-direction:column; gap:10px;">
                            <div class="form-row form-row-2">
                                <div class="form-group" style="margin:0;">
                                    <label class="form-label" style="font-size:12px;">Tanggal Faktur</label>
                                    <input type="date" name="invoice_date" x-model="invoiceDate" @change="onInvoiceDateChange()" class="form-control" style="font-size:12.5px;">
                                </div>
                                <div class="form-group" style="margin:0;">
                                    <label class="form-label" style="font-size:12px;">Tanggal Jatuh Tempo</label>
                                    <input type="date" name="due_date" x-model="dueDate" class="form-control" style="font-size:12.5px;">
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>

                <div style="flex-shrink:0; padding:16px 20px; background:#f8fafc; border-top:1px solid var(--border); display:flex; justify-content:flex-end; gap:10px;">
                    <button type="button" @click="showConfirmModal = false" class="btn btn-secondary">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-check"></i> Simpan Konfirmasi
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
