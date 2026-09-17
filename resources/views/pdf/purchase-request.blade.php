@extends('layouts.pdf')

@section('title', 'Purchase Request - ' . $purchase_request->pr_number)
@section('doc-title', 'PURCHASE REQUEST')
@section('doc-subtitle', 'Purchase Request #: ' . $purchase_request->pr_number)

@section('content')
    {{-- REQUESTOR & PR DETAILS SECTION (Side by Side) --}}
    <table class="parties-table">
        <tr>
            <td style="width: 52%; vertical-align: top; padding-right: 15px;">
                <div class="section-heading">PEMOHON & DIVISI</div>
                <div class="party-name">{{ $purchase_request->user->name ?? '-' }}</div>
                <div class="party-info">
                    Divisi: {{ $purchase_request->division ?? 'Umum' }}<br>
                    Gudang Tujuan: {{ $purchase_request->warehouse->name ?? 'Gudang Utama' }}<br>
                    @if(!empty($purchase_request->warehouse?->address))
                        Alamat Gudang: {{ $purchase_request->warehouse->address }}<br>
                    @endif
                    @if(!empty($purchase_request->category))
                        Kategori: {{ $purchase_request->category->name }}<br>
                    @endif
                </div>
            </td>
            <td style="width: 48%; vertical-align: top;">
                <div class="section-heading">REQUEST DETAILS</div>
                <table class="details-table">
                    <tr>
                        <td class="detail-label">Tanggal Pengajuan</td>
                        <td class="detail-colon">:</td>
                        <td class="detail-value">{{ $purchase_request->request_date ? $purchase_request->request_date->format('d/m/Y') : date('d/m/Y') }}</td>
                    </tr>
                    <tr>
                        <td class="detail-label">Tanggal Dibutuhkan</td>
                        <td class="detail-colon">:</td>
                        <td class="detail-value">{{ $purchase_request->required_date ? $purchase_request->required_date->format('d/m/Y') : '-' }}</td>
                    </tr>
                    @if($purchase_request->expired_date)
                    <tr>
                        <td class="detail-label">Batas Waktu (Exp)</td>
                        <td class="detail-colon">:</td>
                        <td class="detail-value">{{ $purchase_request->expired_date->format('d/m/Y') }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td class="detail-label">Status Dokumen</td>
                        <td class="detail-colon">:</td>
                        <td class="detail-value" style="font-weight: 700;">{{ $purchase_request->status_label }}</td>
                    </tr>
                    @if($purchase_request->purchaseOrders->count() > 0)
                    <tr>
                        <td class="detail-label">Ref. PO Terbit</td>
                        <td class="detail-colon">:</td>
                        <td class="detail-value">{{ $purchase_request->purchaseOrders->pluck('po_number')->implode(', ') }}</td>
                    </tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    {{-- ITEMS SECTION --}}
    <div class="items-section">
        <div class="section-heading">DAFTAR BARANG YANG DIAJUKAN</div>
        <table class="items-table">
            <thead>
                <tr>
                    <th class="text-center" style="width: 4%;">#</th>
                    <th class="text-left" style="width: 15%;">Kode Produk</th>
                    <th class="text-left" style="width: 31%;">Deskripsi Barang</th>
                    <th class="text-center" style="width: 11%;">Qty Diminta</th>
                    <th class="text-center" style="width: 11%;">Qty di-PO</th>
                    <th class="text-center" style="width: 9%;">Sisa</th>
                    <th class="text-center" style="width: 8%;">Satuan</th>
                    <th class="text-left" style="width: 11%;">Catatan Item</th>
                </tr>
            </thead>
            <tbody>
                @forelse($purchase_request->items as $i => $item)
                <tr>
                    <td class="text-center">{{ $i + 1 }}</td>
                    <td class="text-left" style="font-family: monospace; font-size: 8pt; color: #444;">
                        {{ $item->product->sku ?? '-' }}
                    </td>
                    <td class="text-left" style="font-weight: 600; color: #111;">
                        {{ $item->product->name ?? '-' }}
                    </td>
                    <td class="text-center" style="font-weight: 600;">
                        {{ number_format($item->qty ?? 0) }}
                    </td>
                    <td class="text-center">
                        {{ number_format($item->qty_ordered ?? 0) }}
                    </td>
                    <td class="text-center" style="font-weight: 600;">
                        {{ number_format($item->qty_remaining ?? 0) }}
                    </td>
                    <td class="text-center" style="color: #444;">
                        {{ $item->product->unit ?? 'Pcs' }}
                    </td>
                    <td class="text-left" style="font-size: 8pt; color: #555;">
                        {{ $item->notes ?: '-' }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center" style="padding: 15px; color: #888;">Tidak ada item.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- NOTES & PURPOSE SECTION --}}
    @if($purchase_request->notes)
    <div class="terms-section" style="margin-top: 15px;">
        <div class="section-heading">CATATAN & KEPERLUAN PENGADAAN:</div>
        <div class="terms-content" style="font-size: 8.5pt; color: #333; line-height: 1.5;">
            {{ $purchase_request->notes }}
        </div>
    </div>
    @endif
@endsection

@section('signatures')
    @php
        $approverName = $purchase_request->approver?->name ?: $purchase_request->approvalRequests->where('status', 'approved')->first()?->approver?->name;
    @endphp
    @include('pdf.partials.signatures', [
        'cols' => [
            ['label' => 'Diajukan Oleh', 'name' => $purchase_request->user->name ?? 'Pemohon', 'role' => 'Pemohon / Staff'],
            ['label' => 'Mengetahui', 'name' => 'Kepala Divisi ' . ($purchase_request->division ?? ''), 'role' => 'Head of Department'],
            ['label' => 'Disetujui Oleh', 'name' => $approverName ?: 'Manajemen / Purchasing', 'role' => 'Approval / Purchasing'],
        ]
    ])
@endsection
