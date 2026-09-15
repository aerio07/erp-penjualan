{{--
    Reusable Signature Block Component
    Usage:
    @include('pdf.partials.signatures', [
        'cols' => [
            ['label' => 'Dibuat Oleh:', 'name' => $userName, 'role' => 'Staff'],
            ['label' => 'Mengetahui:', 'name' => 'Manager', 'role' => 'Finance'],
            ['label' => 'Disetujui Oleh:', 'name' => $partnerName, 'role' => 'Supplier / Customer'],
        ]
    ])
--}}
@php
    $count = count($cols ?? []);
    $colClass = $count === 2 ? 'sig-col-2' : 'sig-col-3';
@endphp

@if($count > 0)
<table class="sig-table">
    <tr>
        @foreach($cols as $sig)
        <td class="sig-col {{ $colClass }}">
            <div class="sig-label">{{ $sig['label'] ?? 'Tanda Tangan' }}</div>
            <div class="sig-line"></div>
            <div class="sig-name">{{ $sig['name'] ?? '( ................................ )' }}</div>
            @if(!empty($sig['role']))
            <div class="sig-role">{{ $sig['role'] }}</div>
            @endif
        </td>
        @endforeach
    </tr>
</table>
@endif
