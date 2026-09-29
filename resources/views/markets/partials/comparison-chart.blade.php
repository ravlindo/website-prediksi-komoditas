<section class="market-lines-command" data-market-comparison-chart>
    <header class="market-lines-heading">
        <div>
            <p><span></span> PERBANDINGAN LANGSUNG</p>
            <h2>Empat pasar.<br><em>Empat garis dalam satu pandangan.</em></h2>
            <small>Setiap warna mewakili satu pasar. Klik nama pasar untuk menonaktifkan atau menampilkan kembali garisnya.</small>
        </div>
        <div class="market-lines-period">
            <span>CAKUPAN GRAFIK</span>
            <strong>{{ $period }} hari</strong>
            <small>{{ $chart['market_count'] }} pasar · {{ number_format($chart['positive_points']) }} titik harga positif</small>
        </div>
    </header>

    <form method="GET" class="market-lines-filter">
        <label>
            <span>Komoditas</span>
            <select name="commodity">
                @foreach($commodities as $item)
                    <option value="{{ $item->id }}" @selected($commodity?->id === $item->id)>{{ $item->name }}</option>
                @endforeach
            </select>
        </label>
        <label>
            <span>Sampai tanggal</span>
            <input type="date" name="date" value="{{ $date }}">
        </label>
        <label>
            <span>Periode</span>
            <select name="period">
                @foreach($periodOptions as $option)
                    <option value="{{ $option }}" @selected($period === $option)>{{ $option }} hari</option>
                @endforeach
            </select>
        </label>
        <button type="submit">Tampilkan perbandingan <b>→</b></button>
    </form>

    @if($chart['available'])
        <div class="market-lines-panel">
            <div class="market-lines-toolbar">
                <div>
                    <span>GARIS PASAR</span>
                    <small><b data-market-active-count>{{ $chart['series']->count() }}</b> dari {{ $chart['series']->count() }} garis aktif</small>
                </div>
                <div class="market-lines-legend" role="group" aria-label="Atur garis pasar">
                    @foreach($chart['series'] as $series)
                        <button type="button" class="market-line-toggle" data-market-line-toggle="{{ $series['key'] }}" aria-pressed="true" style="--series-color: {{ $series['color'] }}">
                            <i></i><span>{{ $series['name'] }}</span><b aria-hidden="true">✓</b>
                        </button>
                    @endforeach
                    <button type="button" class="market-show-all" data-market-show-all hidden>Tampilkan semua</button>
                </div>
            </div>

            <div class="market-chart-wrap">
                <svg class="market-four-line-chart" viewBox="0 0 1040 330" role="img" aria-label="Grafik perbandingan {{ $commodity?->name }} pada empat pasar">
                    <g class="market-chart-grid">
                        @foreach($chart['ticks'] as $tick)
                            <line x1="72" y1="{{ $tick['y'] }}" x2="1000" y2="{{ $tick['y'] }}"/>
                            <text x="62" y="{{ $tick['y'] + 4 }}" text-anchor="end">Rp {{ number_format($tick['value'], 0, ',', '.') }}</text>
                        @endforeach
                    </g>

                    <line class="market-date-selection-line" data-market-date-selection-line x1="0" y1="34" x2="0" y2="280" hidden/>

                    @foreach($chart['series'] as $series)
                        <g class="market-chart-series" data-market-series="{{ $series['key'] }}" style="--series-color: {{ $series['color'] }}">
                            @foreach($series['segments'] as $segment)
                                <polyline points="{{ $segment }}"/>
                            @endforeach
                            @foreach($series['points'] as $point)
                                @if($point['price'] > 0)
                                    <circle cx="{{ $point['x'] }}" cy="{{ $point['y'] }}" r="{{ $series['latest_date'] && $point['date']->equalTo($series['latest_date']) ? 4.6 : 2.4 }}" tabindex="0" role="button"
                                        data-market-point
                                        data-market-name="{{ $series['name'] }}"
                                        data-market-color="{{ $series['color'] }}"
                                        data-market-date="{{ $point['date']->translatedFormat('d F Y') }}"
                                        data-market-price="Rp {{ number_format($point['price'], 0, ',', '.') }}"
                                        data-market-date-index="{{ $loop->index }}"
                                        aria-label="{{ $series['name'] }}, {{ $point['date']->translatedFormat('d F Y') }}, harga Rp {{ number_format($point['price'], 0, ',', '.') }}">
                                        <title>{{ $series['name'] }} · {{ $point['date']->translatedFormat('d F Y') }} · Rp {{ number_format($point['price'], 0, ',', '.') }}</title>
                                    </circle>
                                @endif
                            @endforeach
                        </g>
                    @endforeach

                    <g class="market-chart-dates">
                        @foreach($chart['date_ticks'] as $tick)
                            <text x="{{ $tick['x'] }}" y="315" text-anchor="middle">{{ $tick['date']->translatedFormat('d M') }}</text>
                        @endforeach
                    </g>
                </svg>

                <div class="market-chart-tooltip" data-market-tooltip hidden>
                    <i data-market-tooltip-color></i>
                    <div><span data-market-tooltip-name></span><small data-market-tooltip-date></small><strong data-market-tooltip-price></strong></div>
                </div>
                <div class="market-date-price-popup" data-market-date-price-popup hidden>
                    <header><div><small>HARGA EMPAT PASAR</small><strong data-market-date-price-title></strong></div><span>4 PASAR</span></header>
                    <div data-market-date-price-list></div>
                    <footer>Klik kartu tanggal yang sama untuk menutup</footer>
                </div>
            </div>

            @php
                $holidayCount = $chart['timeline']->whereNotNull('holiday')->count();
                $weekendCount = $chart['timeline']->filter(fn ($item) => $item['is_weekend'] && !$item['holiday'])->count();
                $workingDayCount = max(0, $chart['timeline']->count() - $holidayCount - $weekendCount);
            @endphp
            <section class="market-date-timeline" aria-label="Linimasa tanggal perbandingan empat pasar">
                <header>
                    <div><span>LINIMASA {{ $period }} HARI</span><small>Klik kartu tanggal untuk menyorot posisi keempat pasar pada grafik.</small></div>
                    <strong>{{ $chart['start']->translatedFormat('d M') }} &mdash; {{ $chart['end']->translatedFormat('d M Y') }}</strong>
                </header>
                <div class="market-date-rail" style="--market-timeline-count: {{ $chart['timeline']->count() }}">
                    @foreach($chart['timeline'] as $timelineDate)
                        <button type="button"
                            class="market-date-chip {{ $timelineDate['is_weekend'] ? 'weekend' : '' }} {{ $timelineDate['holiday'] ? 'holiday' : '' }} {{ $timelineDate['is_latest'] ? 'latest' : '' }} {{ $timelineDate['available_markets'] === 0 ? 'no-data' : '' }}"
                            data-market-date-chip="{{ $loop->index }}"
                            data-market-date-x="{{ $timelineDate['x'] }}"
                            data-market-date-label="{{ $timelineDate['date']->translatedFormat('l, d F Y') }}"
                            aria-pressed="false"
                            title="{{ $timelineDate['date']->translatedFormat('l, d F Y') }} · {{ $timelineDate['available_markets'] }} pasar memiliki harga{{ $timelineDate['holiday'] ? ' · '.$timelineDate['holiday']['name'] : '' }}">
                            @if($timelineDate['holiday'])<em>LIBUR</em>@endif
                            <i></i>
                            <small>{{ mb_strtoupper(mb_substr($timelineDate['date']->translatedFormat('D'), 0, 3)) }}</small>
                            <strong>{{ $timelineDate['date']->format('d') }}</strong>
                            <span>{{ mb_strtoupper($timelineDate['date']->translatedFormat('M')) }}</span>
                            <b>{{ $timelineDate['available_markets'] }}/4</b>
                        </button>
                    @endforeach
                </div>
                <div hidden aria-hidden="true">
                    @foreach($chart['timeline'] as $timelineDate)
                        <template data-market-date-price-template="{{ $loop->index }}">
                            @foreach($timelineDate['prices'] as $marketPrice)
                                <div class="market-date-price-row">
                                    <i style="--market-price-color: {{ $marketPrice['color'] }}"></i>
                                    <span>{{ $marketPrice['market'] }}</span>
                                    <strong class="{{ $marketPrice['price'] > 0 ? '' : 'missing' }}">
                                        {{ $marketPrice['price'] > 0 ? 'Rp '.number_format($marketPrice['price'], 0, ',', '.') : 'Belum tercatat' }}
                                    </strong>
                                </div>
                            @endforeach
                        </template>
                    @endforeach
                </div>
                <footer>
                    <div><span><i></i>{{ $workingDayCount }} hari kerja</span><span><i class="weekend"></i>{{ $weekendCount }} akhir pekan</span><span><i class="holiday"></i>{{ $holidayCount }} hari libur</span></div>
                    <small>Angka 4/4 berarti seluruh pasar memiliki harga pada tanggal tersebut.</small>
                </footer>
            </section>

            <div class="market-series-cards">
                @foreach($chart['series'] as $series)
                    <button type="button" class="market-series-card" data-market-card-toggle="{{ $series['key'] }}" aria-pressed="true" style="--series-color: {{ $series['color'] }}">
                        <span><i></i>{{ $series['name'] }}</span>
                        <strong>Rp {{ number_format($series['latest'], 0, ',', '.') }}</strong>
                        <small>{{ $series['latest_date']?->translatedFormat('d M Y') ?? 'Belum ada harga' }}</small>
                        <div>
                            <b class="{{ $series['change'] > 0 ? 'up' : ($series['change'] < 0 ? 'down' : '') }}">{{ $series['change'] > 0 ? '+' : '' }}Rp {{ number_format($series['change'], 0, ',', '.') }}</b>
                            <em>{{ number_format($series['coverage'], 1, ',', '.') }}% data tersedia</em>
                        </div>
                    </button>
                @endforeach
            </div>

            <footer class="market-chart-note">
                <span><i></i>Harga positif</span>
                <span><i class="gap"></i>Harga nol/kosong menjadi jeda garis</span>
                <small>{{ $chart['start']->translatedFormat('d F Y') }}—{{ $chart['end']->translatedFormat('d F Y') }}</small>
            </footer>
        </div>
    @else
        <div class="market-lines-empty">
            <strong>Belum ada harga positif pada periode ini</strong>
            <p>Pilih komoditas, tanggal akhir, atau periode lain untuk menampilkan empat garis pasar.</p>
        </div>
    @endif
</section>
