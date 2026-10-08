@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination Navigation" class="flex justify-end mt-6">
        <ul class="inline-flex items-center -space-x-px text-sm font-medium text-gray-700">
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <li aria-disabled="true" aria-label="@lang('pagination.previous')">
                    <span class="px-3 py-2 ml-0 leading-tight text-gray-400 bg-white border border-gray-300 rounded-l-lg cursor-not-allowed" aria-hidden="true">&lsaquo;</span>
                </li>
            @else
                <li>
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="px-3 py-2 ml-0 leading-tight text-purple-600 bg-white border border-gray-300 rounded-l-lg hover:bg-purple-50 hover:text-purple-700 focus:outline-hidden focus:ring-2 focus:ring-purple-500" aria-label="@lang('pagination.previous')">&lsaquo;</a>
                </li>
            @endif

            {{-- Pagination Elements --}}
            @foreach ($elements as $element)
                {{-- "Three Dots" Separator --}}
                @if (is_string($element))
                    <li aria-disabled="true"><span class="px-3 py-2 leading-tight text-gray-400 bg-white border border-gray-300 cursor-default">{{ $element }}</span></li>
                @endif

                {{-- Array Of Links --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li aria-current="page"><span class="px-3 py-2 leading-tight text-white bg-purple-600 border border-purple-600 cursor-default">{{ $page }}</span></li>
                        @else
                            <li><a href="{{ $url }}" class="px-3 py-2 leading-tight text-purple-600 bg-white border border-gray-300 hover:bg-purple-50 hover:text-purple-700 focus:outline-hidden focus:ring-2 focus:ring-purple-500">{{ $page }}</a></li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <li>
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="px-3 py-2 leading-tight text-purple-600 bg-white border border-gray-300 rounded-r-lg hover:bg-purple-50 hover:text-purple-700 focus:outline-hidden focus:ring-2 focus:ring-purple-500" aria-label="@lang('pagination.next')">&rsaquo;</a>
                </li>
            @else
                <li aria-disabled="true" aria-label="@lang('pagination.next')">
                    <span class="px-3 py-2 leading-tight text-gray-400 bg-white border border-gray-300 rounded-r-lg cursor-not-allowed" aria-hidden="true">&rsaquo;</span>
                </li>
            @endif
        </ul>
    </nav>
@endif
