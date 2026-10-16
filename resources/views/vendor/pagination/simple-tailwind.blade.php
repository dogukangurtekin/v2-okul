@if ($paginator->hasPages())
<nav class="pg" role="navigation" aria-label="Sayfalar">
    <span class="pg-info">{{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} / {{ $paginator->total() }}</span>
    <div class="pg-list">
        @if ($paginator->onFirstPage()) <span class="pg-i off">←</span> @else <a class="pg-i" href="{{ $paginator->previousPageUrl() }}" rel="prev">←</a> @endif
        @foreach ($elements as $element)
            @if (is_string($element)) <span class="pg-i off">{{ $element }}</span> @endif
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage()) <span class="pg-i on" aria-current="page">{{ $page }}</span> @else <a class="pg-i" href="{{ $url }}">{{ $page }}</a> @endif
                @endforeach
            @endif
        @endforeach
        @if ($paginator->hasMorePages()) <a class="pg-i" href="{{ $paginator->nextPageUrl() }}" rel="next">→</a> @else <span class="pg-i off">→</span> @endif
    </div>
</nav>
@endif
