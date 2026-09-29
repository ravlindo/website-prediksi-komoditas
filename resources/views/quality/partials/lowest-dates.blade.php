<article class="panel quality-panel compact-panel">
    <div class="panel-head"><div><h2>Tanggal dengan Kelengkapan Terendah</h2><p>Prioritas pemeriksaan data harian</p></div></div>
    <div class="lowest-date-list">@forelse($lowestDates as $item)@php($percent = $item->total_count > 0 ? ($item->positive_count/$item->total_count)*100 : 0)<div><time>{{ \Carbon\Carbon::parse($item->price_date)->translatedFormat('d M Y') }}</time><span>{{ number_format($item->zero_count, 0, ',', '.') }} harga nol</span><b>{{ number_format($percent, 1, ',', '.') }}%</b></div>@empty<p class="quality-empty">Tidak ada data tanggal.</p>@endforelse</div>
</article>
