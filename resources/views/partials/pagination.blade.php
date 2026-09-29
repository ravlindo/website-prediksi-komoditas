@if($paginator->hasPages())
<nav class="readable-pagination" aria-label="Navigasi halaman">
    <p>Menampilkan <strong>{{ number_format($paginator->firstItem()) }}&ndash;{{ number_format($paginator->lastItem()) }}</strong> dari <strong>{{ number_format($paginator->total()) }}</strong> data</p>
    <div class="pagination-controls">
        @if($paginator->onFirstPage())<span class="page-nav disabled">&larr; Sebelumnya</span>@else<a class="page-nav" href="{{ $paginator->previousPageUrl() }}" rel="prev">&larr; Sebelumnya</a>@endif

        @if($paginator->currentPage() > 3)
            <a class="page-number" href="{{ $paginator->url(1) }}">1</a>
            @if($paginator->currentPage() > 4)<span class="page-ellipsis">&hellip;</span>@endif
        @endif
        @foreach($paginator->getUrlRange(max(1, $paginator->currentPage() - 2), min($paginator->lastPage(), $paginator->currentPage() + 2)) as $page => $url)
            @if($page === $paginator->currentPage())<span class="page-number active" aria-current="page">{{ $page }}</span>@else<a class="page-number" href="{{ $url }}">{{ $page }}</a>@endif
        @endforeach
        @if($paginator->currentPage() < $paginator->lastPage() - 2)
            @if($paginator->currentPage() < $paginator->lastPage() - 3)<span class="page-ellipsis">&hellip;</span>@endif
            <a class="page-number last-page" href="{{ $paginator->url($paginator->lastPage()) }}" title="Halaman terakhir">{{ $paginator->lastPage() }}</a>
        @endif

        @if($paginator->hasMorePages())<a class="page-nav" href="{{ $paginator->nextPageUrl() }}" rel="next">Berikutnya &rarr;</a>@else<span class="page-nav disabled">Berikutnya &rarr;</span>@endif
    </div>
    <div class="pagination-jump-wrap">
        <span class="page-position">Halaman <b>{{ number_format($paginator->currentPage()) }}</b> dari <b>{{ number_format($paginator->lastPage()) }}</b></span>
        <form class="pagination-jump" method="GET" action="{{ url()->current() }}">
            @foreach(request()->except('page') as $key => $value)@if(is_scalar($value) && $value !== '')<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endif @endforeach
            <label for="jumpPage">Ke halaman</label><input id="jumpPage" type="number" name="page" value="{{ $paginator->currentPage() }}" min="1" max="{{ $paginator->lastPage() }}" inputmode="numeric" required aria-label="Nomor halaman tujuan"><button>Buka</button>
        </form>
    </div>
</nav>
@elseif($paginator->total())
<div class="readable-pagination single-page"><p>Menampilkan seluruh <strong>{{ number_format($paginator->total()) }}</strong> data</p><span class="page-position">Halaman 1 dari 1</span></div>
@endif
