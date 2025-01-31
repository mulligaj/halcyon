@if ($paginator->hasPages())
    <nav class="d-flex w-100" aria-label="Pagination">
        <ul class="pagination flex-grow-1">
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <li class="page-item disabled" aria-label="@lang('pagination.previous')">
                    <span class="page-link" aria-hidden="true">&lsaquo;</span>
                </li>
            @else
                <li class="page-item">
                    <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="@lang('pagination.previous')">&lsaquo;</a>
                </li>
            @endif

            {{-- Pagination Elements --}}
            @foreach ($elements as $element)
                {{-- "Three Dots" Separator --}}
                @if (is_string($element))
                    <li class="page-item disabled"><span class="page-link">{{ $element }}</span></li>
                @endif

                {{-- Array Of Links --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li class="page-item active" aria-current="page"><span class="page-link">{{ number_format($page) }}</span></li>
                        @else
                            <li class="page-item"><a class="page-link" href="{{ $url }}">{{ number_format($page) }}</a></li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <li class="page-item">
                    <a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="@lang('pagination.next')">&rsaquo;</a>
                </li>
            @else
                <li class="page-item disabled" aria-label="@lang('pagination.next')">
                    <span class="page-link" aria-hidden="true">&rsaquo;</span>
                </li>
            @endif
        </ul>
        <p class="text-right">
            <?php
            $end = $paginator->total();
            if ($paginator->total() > $paginator->perPage()):
                $pages = (int) ceil($paginator->total() / $paginator->perPage());
                if ($paginator->currentPage() < $pages):
                    $end = $paginator->currentPage()*$paginator->perPage();
                endif;
            endif;
            ?>
            @lang('pagination.results shown of total', ['start' => number_format(($paginator->currentPage()-1)*$paginator->perPage()+1), 'end' => number_format($end), 'total' => number_format($paginator->total())])
            @if (app('isAdmin'))
                <?php
                $l = rand();
                $limits = array(
                    10,
                    20,
                    50,
                    100,
                    200,
                    500
                );
                ?>
                <select class="form-control filter-submit ml-4" name="limit" id="limit-{{ $l }}">
                    @foreach ($limits as $limit)
                        <option value="{{ $limit }}"<?php if ($paginator->perPage() == $limit) { echo ' selected="slected"'; } ?>>{{ $limit }}</option>
                    @endforeach
                </select>
                <label for="limit-{{ $l }}" class="d-inline">@lang('pagination.per page')</label>
            @endif
        </p>
    </nav>
@endif
