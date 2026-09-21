@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Navigasi Halaman" class="erp-pagination-container">
        <ul class="erp-pagination-list">
            {{-- Tombol Sebelumnya --}}
            @if ($paginator->onFirstPage())
                <li class="erp-page-item disabled" aria-disabled="true" aria-label="Sebelumnya">
                    <span class="erp-page-link prev-next-btn" aria-hidden="true">
                        <i class="fa-solid fa-chevron-left"></i>
                    </span>
                </li>
            @else
                <li class="erp-page-item">
                    <a class="erp-page-link prev-next-btn" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Sebelumnya">
                        <i class="fa-solid fa-chevron-left"></i>
                    </a>
                </li>
            @endif

            {{-- Angka-angka Halaman (1, 2, 3, dst) --}}
            @foreach ($elements as $element)
                {{-- Pemisah "..." --}}
                @if (is_string($element))
                    <li class="erp-page-item disabled" aria-disabled="true">
                        <span class="erp-page-link dots">{{ $element }}</span>
                    </li>
                @endif

                {{-- Nomor Halaman --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li class="erp-page-item active" aria-current="page">
                                <span class="erp-page-link number-btn active">{{ $page }}</span>
                            </li>
                        @else
                            <li class="erp-page-item">
                                <a class="erp-page-link number-btn" href="{{ $url }}">{{ $page }}</a>
                            </li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Tombol Selanjutnya --}}
            @if ($paginator->hasMorePages())
                <li class="erp-page-item">
                    <a class="erp-page-link prev-next-btn" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Selanjutnya">
                        <i class="fa-solid fa-chevron-right"></i>
                    </a>
                </li>
            @else
                <li class="erp-page-item disabled" aria-disabled="true" aria-label="Selanjutnya">
                    <span class="erp-page-link prev-next-btn" aria-hidden="true">
                        <i class="fa-solid fa-chevron-right"></i>
                    </span>
                </li>
            @endif
        </ul>
    </nav>
@endif
