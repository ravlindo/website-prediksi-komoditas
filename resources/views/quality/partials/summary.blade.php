<section class="quality-summary-grid">
    <article class="quality-stat primary"><span>Total Data</span><strong>{{ number_format($summary['total'], 0, ',', '.') }}</strong><small>{{ $summary['dates'] }} tanggal &bull; {{ $summary['commodities'] }} komoditas</small></article>
    <article class="quality-stat success"><span>Harga Positif</span><strong>{{ number_format($summary['positive'], 0, ',', '.') }}</strong><small>Layak masuk perhitungan harga</small></article>
    <article class="quality-stat danger"><span>Harga Nol</span><strong>{{ number_format($summary['zero'], 0, ',', '.') }}</strong><small>Perlu dianggap tidak tersedia saat analisis</small></article>
    <article class="quality-stat score"><span>Kelengkapan</span><strong>{{ number_format($summary['completeness'], 2, ',', '.') }}%</strong><div class="quality-progress"><i style="width:{{ min(100, $summary['completeness']) }}%"></i></div></article>
    <article class="quality-stat neutral"><span>Duplikasi</span><strong>{{ number_format($summary['duplicates'], 0, ',', '.') }}</strong><small>Kombinasi tanggal, pasar, komoditas</small></article>
</section>
