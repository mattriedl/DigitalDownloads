@if ($paginator->hasPages())
    <nav class="mt-6 flex items-center justify-between gap-2 text-sm" aria-label="Pages">
        @if ($paginator->onFirstPage())
            <span class="rounded-md border border-rule px-3 py-2 text-muted opacity-50">Previous</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="btn-quiet">Previous</a>
        @endif

        <span class="hidden gap-1 sm:flex">
            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="px-2 py-2 text-muted">…</span>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span aria-current="page" class="rounded-md bg-brand px-3 py-2 font-semibold text-brand-ink">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="rounded-md px-3 py-2 text-muted hover:bg-raised hover:text-ink">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach
        </span>
        <span class="text-muted sm:hidden">Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}</span>

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="btn-quiet">Next</a>
        @else
            <span class="rounded-md border border-rule px-3 py-2 text-muted opacity-50">Next</span>
        @endif
    </nav>
@endif
