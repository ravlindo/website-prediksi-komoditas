<div class="consumer-table-content">
    <div class="consumer-table-heading">
        <div><h1>Harga Rata-Rata Kabupaten Mojokerto di Tingkat Konsumen</h1><p>Tanggal {{ \Carbon\Carbon::parse($selectedDate)->translatedFormat('d F Y') }} &bull; Sumber: Siskaperbapo Jawa Timur</p></div>
        <label class="consumer-search">Cari: <input id="tableSearch" type="search" placeholder="Nama komoditas"></label>
    </div>

    <div class="table-scroll">
        <table class="siskaper-table">
            <thead><tr><th>NO</th><th>NAMA BAHAN POKOK</th><th>SATUAN</th><th>HARGA KEMARIN</th><th>HARGA SEKARANG</th><th>PERUBAHAN (Rp)</th><th>PERUBAHAN (%)</th></tr></thead>
            <tbody id="priceTable">
                @forelse($commodities->groupBy('category') as $categoryName => $categoryCommodities)
                    <tr class="category-row"><td>{{ str_pad($categoryCommodities->first()['category_number'], 2, '0', STR_PAD_LEFT) }}</td><td>{{ strtoupper($categoryName) }}</td><td></td><td>0</td><td>0</td><td></td><td></td></tr>
                    @foreach($categoryCommodities as $commodity)
                        @if($commodity['is_section'])
                        <tr class="category-row subsection-row"><td></td><td>{{ $commodity['name'] }}</td><td></td><td>0</td><td>0</td><td></td><td></td></tr>
                        @else
                        <tr class="commodity-row">
                            <td></td>
                            <td><a href="{{ route('prices.show', $commodity['slug']) }}">- {{ $commodity['name'] }}</a></td>
                            <td>{{ $commodity['unit'] }}</td>
                            <td class="yesterday-price">{{ str_replace('Rp ', '', $commodity['previous']) }}</td>
                            <td class="today-price">{{ str_replace('Rp ', '', $commodity['current']) }}</td>
                            <td class="{{ $commodity['trend'] === 'up' ? 'increase' : ($commodity['trend'] === 'down' ? 'decrease' : '') }}">{{ str_replace('Rp ', '', $commodity['change']) }}</td>
                            <td class="percent-cell">{{ $commodity['percent'] }} <span class="price-arrow {{ $commodity['trend'] }}">{{ $commodity['trend'] === 'up' ? '◆' : ($commodity['trend'] === 'down' ? '◆' : '—') }}</span></td>
                        </tr>
                        @endif
                    @endforeach
                @empty
                    <tr><td colspan="7" class="consumer-empty">Data harga tidak tersedia untuk filter yang dipilih.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="consumer-table-footer"><span>Menampilkan {{ $commodities->count() }} komoditas</span><a class="consumer-download-button" href="{{ route('prices.export',['date'=>$selectedDate,'market'=>$selectedMarket,'category'=>$selectedCategory]) }}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v12m-4-4 4 4 4-4M5 20h14"/></svg> Unduh CSV</a></div>
</div>
