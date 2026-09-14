@props([
    'showUrl' => null,
    'editUrl' => null,
    'pdfUrl' => null,
    'postUrl' => null,
    'postMethod' => 'POST',
    'postConfirm' => 'Posting dokumen ini sekarang?',
    'postTitle' => 'Posting Jurnal',
    'postIcon' => 'fa-solid fa-paper-plane',
    'deleteUrl' => null,
    'deleteConfirm' => 'Yakin ingin menghapus dokumen ini?',
])

<div {{ $attributes->merge(['class' => 'table-actions-group']) }} style="display: flex; gap: 6px; justify-content: center; align-items: center;">
    @if($showUrl)
        <a href="{{ $showUrl }}" class="btn btn-secondary btn-sm btn-icon" title="Lihat Detail">
            <i class="fa-solid fa-eye"></i>
        </a>
    @endif

    @if($editUrl)
        <a href="{{ $editUrl }}" class="btn btn-secondary btn-sm btn-icon" title="Edit">
            <i class="fa-solid fa-pen"></i>
        </a>
    @endif

    @if($postUrl)
        <form action="{{ $postUrl }}" method="POST" style="display: inline; margin: 0;" onsubmit="return confirm('{{ $postConfirm }}');">
            @csrf
            @if(strtoupper($postMethod) !== 'POST')
                @method($postMethod)
            @endif
            <button type="submit" class="btn btn-primary btn-sm btn-icon" title="{{ $postTitle }}">
                <i class="{{ $postIcon }}"></i>
            </button>
        </form>
    @endif

    {{ $slot }}

    @if($pdfUrl)
        <a href="{{ $pdfUrl }}" class="btn btn-secondary btn-sm btn-icon" title="Export PDF" target="_blank">
            <i class="fa-solid fa-file-pdf"></i>
        </a>
    @endif

    @if($deleteUrl)
        <form action="{{ $deleteUrl }}" method="POST" style="display: inline; margin: 0;" onsubmit="return confirm('{{ $deleteConfirm }}');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger btn-sm btn-icon" title="Hapus">
                <i class="fa-solid fa-trash"></i>
            </button>
        </form>
    @endif
</div>
