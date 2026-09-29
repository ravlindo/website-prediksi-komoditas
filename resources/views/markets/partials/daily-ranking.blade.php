@php
    $averagePrice = $positive->isNotEmpty() ? (int) round($positive->avg('price')) : 0;
    $minimumPrice = (int) ($positive->min('price') ?? 0);
    $maximumPrice = (int) ($positive->max('price') ?? 0);
    $priceSpread = max(0, $maximumPrice - $minimumPrice);
    $spreadPercentage = $minimumPrice > 0 ? ($priceSpread / $minimumPrice) * 100 : 0;
    $lowestRow = $rows->first(fn ($row) => $row->price > 0 && $row->price === $minimumPrice);
    $highestRow = $rows->first(fn ($row) => $row->price > 0 && $row->price === $maximumPrice);
    $sourceNames = $rows->pluck('source')->filter()->unique()->implode(', ');
    $formattedDate = $date ? \Carbon\Carbon::parse($date)->translatedFormat('d F Y') : 'Tanggal belum tersedia';
@endphp

<section class="market-daily-comparison">
    <header class="market-daily-heading">
        <div class="market-daily-title">
            <p><span></span> ANALISIS HARGA AKTUAL</p>
            <h2>Posisi harga di empat pasar</h2>
            <small>
                Peringkat <strong>{{ $commodity?->name ?? 'komoditas' }}</strong> berdasarkan harga per
                <strong>{{ $commodity?->unit ?? 'satuan' }}</strong> pada <strong>{{ $formattedDate }}</strong>.
            </small>
        </div>
        <div class="market-ranking-method">
            <span>METODE PEMERINGKATAN</span>
            <strong>Terendah <b>&rarr;</b> tertinggi</strong>
            <small>Harga Rp0 dianggap belum tercatat dan tidak dihitung.</small>
        </div>
    </header>

    @if($positive->isNotEmpty())
        <div class="market-ranking-summary">
            <article>
                <span>Rata-rata 4 pasar</span>
                <strong>Rp {{ number_format($averagePrice, 0, ',', '.') }}</strong>
                <small>Nilai pembanding pada tanggal terpilih</small>
            </article>
            <article>
                <span>Rentang harga</span>
                <strong>Rp {{ number_format($priceSpread, 0, ',', '.') }}</strong>
                <small>{{ number_format($spreadPercentage, 2, ',', '.') }}% dari harga terendah</small>
            </article>
            <article class="lowest">
                <span>Harga terendah</span>
                <strong>{{ $lowestRow?->market?->name ?? '-' }}</strong>
                <small>Rp {{ number_format($minimumPrice, 0, ',', '.') }}</small>
            </article>
            <article class="highest">
                <span>Harga tertinggi</span>
                <strong>{{ $highestRow?->market?->name ?? '-' }}</strong>
                <small>Rp {{ number_format($maximumPrice, 0, ',', '.') }}</small>
            </article>
        </div>
    @endif

    <div class="market-ranking-context">
        <div><span>Komoditas</span><strong>{{ $commodity?->name ?? '-' }}</strong></div>
        <div><span>Tanggal acuan</span><strong>{{ $formattedDate }}</strong></div>
        <div><span>Sumber data</span><strong>{{ $sourceNames ?: 'Database harga Kabupaten Mojokerto' }}</strong></div>
        <div><span>Cakupan</span><strong>{{ $positive->count() }} dari 4 pasar memiliki harga</strong></div>
    </div>

    <div class="market-comparison-grid">
        @forelse($rows as $row)
            @php
                $isAvailable = $row->price > 0;
                $isLowest = $isAvailable && $row->price === $minimumPrice;
                $isHighest = $isAvailable && $row->price === $maximumPrice && !$isLowest;
                $difference = $isAvailable ? (int) $row->price - $averagePrice : 0;
                $differencePercentage = $averagePrice > 0 ? ($difference / $averagePrice) * 100 : 0;
                $position = $isAvailable && $priceSpread > 0
                    ? 12 + (((int) $row->price - $minimumPrice) / $priceSpread * 88)
                    : ($isAvailable ? 50 : 0);
                $status = !$isAvailable
                    ? 'Belum tercatat'
                    : ($isLowest ? 'Termurah' : ($isHighest ? 'Tertinggi' : ($difference < 0 ? 'Di bawah rata-rata' : 'Di atas rata-rata')));
            @endphp
            <article class="market-comparison-card {{ $isLowest ? 'best' : '' }} {{ $isHighest ? 'highest' : '' }} {{ !$isAvailable ? 'empty-price' : '' }}">
                <div class="market-rank-number">
                    <span>{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                    <small>PERINGKAT</small>
                </div>
                <div class="market-rank-content">
                    <div class="market-rank-name">
                        <div>
                            <strong>{{ $row->market->name }}</strong>
                            <small>{{ $row->market->district ?: 'Kabupaten Mojokerto' }}</small>
                        </div>
                        <em class="{{ $isLowest ? 'lowest' : ($isHighest ? 'highest' : '') }}">{{ $status }}</em>
                    </div>
                    <div class="market-rank-price">
                        <strong>Rp {{ number_format($row->price, 0, ',', '.') }}</strong>
                        @if($isAvailable)
                            <span class="{{ $difference > 0 ? 'above' : ($difference < 0 ? 'below' : '') }}">
                                {{ $difference > 0 ? '+' : '' }}Rp {{ number_format($difference, 0, ',', '.') }}
                                <small>({{ $difference > 0 ? '+' : '' }}{{ number_format($differencePercentage, 2, ',', '.') }}%) dari rata-rata</small>
                            </span>
                        @else
                            <span>Belum masuk perhitungan</span>
                        @endif
                    </div>
                    <div class="market-rank-scale" aria-label="Posisi harga dalam rentang empat pasar">
                        <span style="width: {{ min(100, max(0, $position)) }}%"></span>
                        @if($isAvailable)<i style="left: {{ min(100, max(0, $position)) }}%"></i>@endif
                    </div>
                    <div class="market-rank-scale-labels"><small>Rp {{ number_format($minimumPrice, 0, ',', '.') }}</small><small>Rentang empat pasar</small><small>Rp {{ number_format($maximumPrice, 0, ',', '.') }}</small></div>
                </div>
            </article>
        @empty
            <div class="market-ranking-empty">
                <strong>Belum ada data untuk dibandingkan</strong>
                <p>Pilih komoditas atau tanggal lain untuk melihat posisi harga empat pasar.</p>
            </div>
        @endforelse
    </div>

    <footer class="market-ranking-footnote">
        <span><i></i>Urutan hanya membandingkan harga pada tanggal dan komoditas yang sama.</span>
        <span>Harga termurah bukan penilaian kualitas barang atau pasar.</span>
    </footer>
</section>
