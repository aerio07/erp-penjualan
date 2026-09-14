@props([
    'title' => 'Dokumen Masih Berstatus DRAFT',
    'message' => 'Dokumen ini belum diposting atau dikonfirmasi. Periksa kembali seluruh rincian sebelum mempostingnya.',
    'actionUrl' => null,
    'actionMethod' => 'POST',
    'actionLabel' => 'Posting Sekarang',
    'actionIcon' => 'fa-solid fa-check',
    'confirmText' => 'Posting dokumen ini sekarang?',
    'editUrl' => null,
    'deleteUrl' => null,
    'deleteConfirm' => 'Yakin ingin menghapus dokumen draft ini?'
])

<div {{ $attributes->merge(['class' => 'draft-banner']) }} style="background: #fffbeb; border: 1px solid #fde68a; border-left: 4px solid #f59e0b; border-radius: 8px; padding: 14px 18px; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
    <div style="display: flex; align-items: center; gap: 14px; min-width: 0; flex: 1;">
        <div style="width: 36px; height: 36px; border-radius: 50%; background: #fef3c7; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
            <i class="fa-solid fa-file-pen" style="font-size: 16px; color: #d97706;"></i>
        </div>
        <div style="min-width: 0;">
            <div style="font-weight: 700; color: #92400e; font-size: 14px; line-height: 1.3;">
                {{ $title }}
            </div>
            <div style="color: #b45309; font-size: 12.5px; margin-top: 2px; line-height: 1.4;">
                {{ $message }}
            </div>
        </div>
    </div>

    @php
        $hasActions = $editUrl || $deleteUrl || $actionUrl || (isset($slot) && !empty(trim((string) $slot)));
    @endphp

    @if($hasActions)
    <div style="display: flex; align-items: center; gap: 8px; flex-shrink: 0; margin-left: auto;">
        @if($editUrl)
            <a href="{{ $editUrl }}" class="btn btn-secondary btn-sm" style="display: inline-flex; align-items: center; gap: 6px; font-weight: 600; padding: 6px 14px; border-radius: 6px; font-size: 12.5px; text-decoration: none;">
                <i class="fa-solid fa-pen"></i> Ubah
            </a>
        @endif

        @if($deleteUrl)
            <form action="{{ $deleteUrl }}" method="POST" style="margin: 0; display: inline;" onsubmit="return confirm('{{ $deleteConfirm }}');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger btn-sm" style="display: inline-flex; align-items: center; gap: 6px; font-weight: 600; padding: 6px 14px; border-radius: 6px; font-size: 12.5px;">
                    <i class="fa-solid fa-trash"></i> Hapus
                </button>
            </form>
        @endif

        @if($actionUrl)
            <form action="{{ $actionUrl }}" method="POST" style="margin: 0; display: inline;" onsubmit="return confirm('{{ $confirmText }}');">
                @csrf
                @if(strtoupper($actionMethod) !== 'POST')
                    @method($actionMethod)
                @endif
                <button type="submit" class="btn btn-primary btn-sm" style="display: inline-flex; align-items: center; gap: 6px; font-weight: 600; padding: 6px 16px; border-radius: 6px; font-size: 12.5px; white-space: nowrap;">
                    <i class="{{ $actionIcon }}"></i> {{ $actionLabel }}
                </button>
            </form>
        @endif

        {{ $slot }}
    </div>
    @endif
</div>
