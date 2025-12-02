@if ($paginator->hasPages())
<nav>
    <ul class="pagination">
        {{-- Previous Page Link --}}
        <li class="page-item {{ $paginator->onFirstPage() ? 'disabled' : '' }}">
            <a class="page-link" href="{{ $paginator->onFirstPage() ? '#' : $paginator->previousPageUrl() }}" rel="prev" aria-label="Previous">
                &laquo;
            </a>
        </li>

        @php
            $last = $paginator->lastPage();
            $current = $paginator->currentPage();
        @endphp

        {{-- If only few pages, show all --}}
        @if ($last <= 6)
            @for ($page = 1; $page <= $last; $page++)
                <li class="page-item {{ $page == $current ? 'active' : '' }}">
                    <a class="page-link" href="{{ $paginator->url($page) }}">{{ $page }}</a>
                </li>
            @endfor
        @else
            {{-- First two pages --}}
            @for ($page = 1; $page <= 2; $page++)
                <li class="page-item {{ $page == $current ? 'active' : '' }}">
                    <a class="page-link" href="{{ $paginator->url($page) }}">{{ $page }}</a>
                </li>
            @endfor

            {{-- Ellipsis --}}
            <li class="page-item disabled"><span class="page-link">…</span></li>

            {{-- Last two pages --}}
            @for ($page = $last - 1; $page <= $last; $page++)
                <li class="page-item {{ $page == $current ? 'active' : '' }}">
                    <a class="page-link" href="{{ $paginator->url($page) }}">{{ $page }}</a>
                </li>
            @endfor
        @endif

        {{-- Next Page Link --}}
        <li class="page-item {{ $paginator->hasMorePages() ? '' : 'disabled' }}">
            <a class="page-link" href="{{ $paginator->hasMorePages() ? $paginator->nextPageUrl() : '#' }}" rel="next" aria-label="Next">
                &raquo;
            </a>
        </li>
    </ul>
</nav>
@endif

