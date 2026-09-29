<section class="panel table-panel" id="harga">
    <div class="panel-head table-head"><div><h2>Harga Komoditas</h2><p>Rata-rata harga konsumen Kabupaten Mojokerto &bull; {{ \Carbon\Carbon::parse($selectedDate)->translatedFormat('d F Y') }}</p></div><div class="table-actions"><label class="search-box">&#8981;<input id="tableSearch" type="search" placeholder="Cari komoditas..."></label></div></div>
    <div class="table-scroll"><table><thead><tr><th>Komoditas</th><th>Satuan</th><th>Harga Kemarin</th><th>Harga Sekarang</th><th>Perubahan</th><th>Tren</th><th>Aksi</th></tr></thead><tbody id="priceTable">
        @foreach($commodities->groupBy('category') as $categoryName => $categoryCommodities)
            <tr class="category-row"><td colspan="7"><strong>{{ strtoupper($categoryName) }}</strong><span>{{ $categoryCommodities->count() }} jenis</span></td></tr>
            @foreach($categoryCommodities as $commodity)
                @continue($commodity['is_section'])
                <tr class="commodity-row" data-category="{{ $categoryName }}"><td><span class="table-icon {{ $commodity['tone'] }}">{{ $commodity['icon'] }}</span><div><a href="{{ route('prices.show', $commodity['slug']) }}"><strong>{{ $commodity['name'] }}</strong></a><small>{{ $commodity['category'] }}</small></div></td><td>{{ $commodity['unit'] }}</td><td>{{ $commodity['previous'] }}</td><td><strong>{{ $commodity['current'] }}</strong></td><td class="{{ $commodity['trend'] === 'up' ? 'increase' : ($commodity['trend'] === 'down' ? 'decrease' : '') }}">{{ $commodity['change'] }} <small>{{ $commodity['percent'] }}</small></td><td><span class="trend {{ $commodity['trend'] }}">{{ $commodity['label'] }}</span></td><td><a class="detail-button" href="{{ route('prices.show', $commodity['slug']) }}">Lihat Detail</a></td></tr>
            @endforeach
        @endforeach
    </tbody></table></div>
    <div class="table-footer"><span>Menampilkan {{ count($commodities) }} komoditas</span><a href="{{ route('prices.index') }}">Lihat semua komoditas &rarr;</a></div>
</section>
