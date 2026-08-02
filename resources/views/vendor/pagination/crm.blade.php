@if ($paginator->hasPages())
    <nav class="crm-pagination" role="navigation" aria-label="Pagination">

        <p class="crm-pagination-summary">
            Showing {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }}
            of {{ $paginator->total() }}
        </p>

        <ul class="pagination">

            @if ($paginator->onFirstPage())
                <li><span class="page-link is-disabled" aria-disabled="true"><i class="ti ti-chevron-left"></i></span></li>
            @else
                <li><a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Previous page"><i class="ti ti-chevron-left"></i></a></li>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <li><span class="page-link is-disabled">{{ $element }}</span></li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li><span class="page-link active" aria-current="page">{{ $page }}</span></li>
                        @else
                            <li><a class="page-link" href="{{ $url }}">{{ $page }}</a></li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <li><a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Next page"><i class="ti ti-chevron-right"></i></a></li>
            @else
                <li><span class="page-link is-disabled" aria-disabled="true"><i class="ti ti-chevron-right"></i></span></li>
            @endif

        </ul>
    </nav>
@endif
