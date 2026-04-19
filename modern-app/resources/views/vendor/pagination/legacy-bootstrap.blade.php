@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination Navigation">
        <ul class="legacy-pagination">
            @if ($paginator->onFirstPage())
                <li class="legacy-pagination__item legacy-pagination__item--disabled" aria-disabled="true" aria-label="@lang('pagination.previous')">
                    <span class="legacy-pagination__link">{{ __('Previous') }}</span>
                </li>
            @else
                <li class="legacy-pagination__item">
                    <a class="legacy-pagination__link" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="{{ __('Previous') }}">{{ __('Previous') }}</a>
                </li>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <li class="legacy-pagination__item legacy-pagination__item--disabled" aria-disabled="true">
                        <span class="legacy-pagination__link">{{ $element }}</span>
                    </li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li class="legacy-pagination__item legacy-pagination__item--active" aria-current="page">
                                <span class="legacy-pagination__link">{{ $page }}</span>
                            </li>
                        @else
                            <li class="legacy-pagination__item">
                                <a class="legacy-pagination__link" href="{{ $url }}">{{ $page }}</a>
                            </li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <li class="legacy-pagination__item">
                    <a class="legacy-pagination__link" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="{{ __('Next') }}">{{ __('Next') }}</a>
                </li>
            @else
                <li class="legacy-pagination__item legacy-pagination__item--disabled" aria-disabled="true" aria-label="@lang('pagination.next')">
                    <span class="legacy-pagination__link">{{ __('Next') }}</span>
                </li>
            @endif
        </ul>
    </nav>
@endif
