<article class="panel chart-panel">
    <div class="panel-head">
        <div><h2>Tren Harga Komoditas</h2><p>Rata-rata harga {{ $chartDays }} hari terakhir{{ $selectedMarket ? ' pada pasar terpilih' : ' di seluruh pasar' }}</p></div>
        <form method="GET" class="chart-selector">
            <input type="hidden" name="date" value="{{ $selectedDate }}">
            @if($selectedMarket)<input type="hidden" name="market" value="{{ $selectedMarket }}">@endif
            @if($selectedCategory)<input type="hidden" name="category" value="{{ $selectedCategory }}">@endif
            <label><span>Komoditas</span><select name="chart_commodity" onchange="this.form.submit()">@foreach($commodityOptions as $option)<option value="{{ $option->id }}" @selected($selectedCommodity?->id === $option->id)>{{ $option->name }}</option>@endforeach</select></label>
            <label><span>Periode</span><select name="chart_days" onchange="this.form.submit()">@foreach(($chartDayOptions ?? [10,15,30]) as $dayOption)<option value="{{ $dayOption }}" @selected($chartDays === $dayOption)>{{ $dayOption }} hari</option>@endforeach</select></label>
            <label><span>Skala grafik</span><select name="chart_scale" onchange="this.form.submit()"><option value="focus" @selected($chartScale === 'focus')>Fokus perubahan</option><option value="zero" @selected($chartScale === 'zero')>Mulai Rp0</option></select></label>
        </form>
    </div>
    <div class="legend"><span><i class="actual-dot"></i>Harga aktual</span></div>
    @if($chartHistory->isNotEmpty())
        <div class="chart-wrap dynamic-chart" id="interactivePriceChart">
            <div class="axis-labels"><span>Rp {{ number_format($chartMaximum, 0, ',', '.') }}</span><span>Rp {{ number_format(($chartMaximum + $chartMinimum) / 2, 0, ',', '.') }}</span><span>Rp {{ number_format($chartMinimum, 0, ',', '.') }}</span></div>
            <svg class="price-chart" viewBox="0 0 760 260" preserveAspectRatio="none" role="img" aria-label="Grafik tren {{ $selectedCommodity?->name }}">
                <defs><linearGradient id="chartFill" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#2f72dc" stop-opacity=".24"/><stop offset="1" stop-color="#2f72dc" stop-opacity="0"/></linearGradient></defs>
                <g class="grid-lines"><line x1="0" y1="30" x2="760" y2="30"/><line x1="0" y1="127" x2="760" y2="127"/><line x1="0" y1="225" x2="760" y2="225"/></g>
                <g class="holiday-guides" aria-hidden="true">
                    @foreach($chartCoordinates as $point)
                        @if($point['holiday'])
                            <line class="holiday-guide {{ $point['holiday']['type'] === 'collective_leave' ? 'collective-leave' : 'national-holiday' }}" x1="{{ $point['x'] }}" y1="30" x2="{{ $point['x'] }}" y2="225"/>
                        @endif
                    @endforeach
                </g>
                <line class="selected-date-line" id="selectedDateLine" x1="0" y1="30" x2="0" y2="225" hidden/>
                @if($chartCoordinates->count() > 1)
                    <polygon class="chart-area" points="0,225 {{ $chartPoints }} 760,225"/>
                @endif
                <polyline class="actual-line" points="{{ $chartPoints }}"/>
                <g class="daily-points">
                    @foreach($chartCoordinates as $point)
                        <circle cx="{{ $point['x'] }}" cy="{{ $point['y'] }}" r="{{ $loop->last ? 5.8 : 3.2 }}" class="chart-point daily-point {{ $point['holiday'] ? 'holiday-point' : '' }} {{ $loop->last ? 'latest-point' : '' }}" tabindex="0" role="button" data-chart-point="{{ $loop->index }}" data-x="{{ $point['x'] }}" data-y="{{ $point['y'] }}" data-date="{{ $point['date']->translatedFormat('d F Y') }}" data-price="Rp {{ number_format($point['price'], 0, ',', '.') }}" data-holiday-name="{{ $point['holiday']['name'] ?? '' }}" data-holiday-type="{{ $point['holiday']['type_label'] ?? '' }}" aria-label="Pilih {{ $point['date']->translatedFormat('d F Y') }}, harga Rp {{ number_format($point['price'], 0, ',', '.') }}{{ $point['holiday'] ? ', '.$point['holiday']['type_label'].': '.$point['holiday']['name'] : '' }}">
                            <title>{{ $point['date']->translatedFormat('d F Y') }} — Rp {{ number_format($point['price'], 0, ',', '.') }}{{ $point['holiday'] ? ' · '.$point['holiday']['name'] : '' }}</title>
                        </circle>
                    @endforeach
                </g>
            </svg>
            <div class="chart-point-popup" id="chartPointPopup" hidden><small id="chartPopupDate"></small><strong id="chartPopupPrice"></strong><div class="chart-popup-holiday" id="chartPopupHoliday" hidden><i>✦</i><div><b id="chartPopupHolidayType"></b><span id="chartPopupHolidayName"></span></div></div><span class="chart-popup-hint">Klik kartu yang sama untuk menutup</span></div>
        </div>
        <div class="chart-date-section">
            <div class="date-section-heading"><span>Linimasa {{ $chartDays }} hari</span><small>{{ $chartCoordinates->first()['date']->translatedFormat('d M') }} — {{ $chartCoordinates->last()['date']->translatedFormat('d M Y') }}</small></div>
            @if(($chartCalendar['holidays'] ?? collect())->isNotEmpty())
                <div class="holiday-calendar-banner has-events">
                    <div class="holiday-calendar-icon"><span></span><strong>{{ $chartCalendar['holidays']->count() }}</strong></div>
                    <div class="holiday-calendar-copy"><small>KALENDER PERIODE</small><strong>{{ $chartCalendar['holidays']->count() }} tanggal merah tercatat</strong><span>Klik agenda untuk menuju titik harga pada tanggal tersebut.</span></div>
                    <div class="holiday-event-list">
                        @foreach($chartCalendar['holidays']->take(3) as $holiday)
                            <button type="button" data-holiday-jump="{{ $holiday['point_index'] }}" class="{{ $holiday['type'] === 'collective_leave' ? 'collective-leave' : '' }}"><b>{{ $holiday['date']->translatedFormat('d M') }}</b><span>{{ $holiday['name'] }}</span><i>→</i></button>
                        @endforeach
                        @if($chartCalendar['holidays']->count() > 3)<em>+{{ $chartCalendar['holidays']->count() - 3 }} lainnya</em>@endif
                    </div>
                </div>
            @elseif($nextChartHoliday ?? null)
                <div class="holiday-calendar-banner upcoming-event">
                    <div class="holiday-calendar-icon"><span></span><strong>{{ $nextChartHoliday['date']->format('d') }}</strong></div>
                    <div class="holiday-calendar-copy"><small>KALENDER RESMI AKTIF</small><strong>Tidak ada tanggal merah pada periode ini</strong><span>Linimasa tetap dipantau terhadap kalender libur nasional.</span></div>
                    <div class="next-holiday-card"><small>LIBUR BERIKUTNYA</small><strong>{{ $nextChartHoliday['date']->translatedFormat('d F Y') }}</strong><span>{{ $nextChartHoliday['name'] }}</span></div>
                </div>
            @endif
            <div class="chart-date-rail" style="--timeline-count: {{ $chartCoordinates->count() }}" aria-label="Tanggal data grafik">
                @foreach($chartCoordinates as $point)
                    @php($isWeekend = $point['date']->isWeekend())
                    <button type="button" class="date-chip {{ $isWeekend ? 'weekend' : '' }} {{ $point['holiday'] ? 'holiday '.($point['holiday']['type'] === 'collective_leave' ? 'collective-leave' : 'national-holiday') : '' }} {{ $loop->last ? 'latest' : '' }}" data-date-chip="{{ $loop->index }}" title="{{ $point['date']->translatedFormat('l, d F Y') }} · Rp {{ number_format($point['price'], 0, ',', '.') }}{{ $point['holiday'] ? ' · '.$point['holiday']['type_label'].': '.$point['holiday']['name'] : '' }}" aria-pressed="false">
                        @if($point['holiday'])<em class="holiday-chip-badge">{{ $point['holiday']['type'] === 'collective_leave' ? 'CUTI' : 'LIBUR' }}</em>@endif
                        <small>{{ mb_strtoupper(mb_substr($point['date']->translatedFormat('D'), 0, 3)) }}</small>
                        <strong>{{ $point['date']->format('d') }}</strong>
                        <span>{{ mb_strtoupper($point['date']->translatedFormat('M')) }}</span>
                    </button>
                @endforeach
            </div>
            <div class="date-rail-footer">
                <div class="date-rail-metrics"><span><b>{{ $chartCalendar['working_days'] ?? 0 }}</b> hari kerja</span><span><b>{{ $chartCalendar['weekends'] ?? 0 }}</b> akhir pekan</span><span><b>{{ ($chartCalendar['national'] ?? 0) + ($chartCalendar['collective_leave'] ?? 0) }}</b> hari libur</span></div>
                <div class="date-rail-legend"><span><i></i> Hari kerja</span><span><i class="weekend-dot"></i> Akhir pekan</span><span><i class="holiday-dot"></i> Libur nasional</span><span><i class="leave-dot"></i> Cuti bersama</span><span><i class="latest-dot"></i> Data terbaru</span></div>
            </div>
        </div>
        <div class="chart-highlight"><div><span>Harga terakhir</span><strong>Rp {{ number_format($latestChartPrice, 0, ',', '.') }}</strong></div><div><span>Perubahan harian</span><strong class="{{ $chartChange > 0 ? 'increase' : ($chartChange < 0 ? 'decrease' : '') }}">{{ $chartChange > 0 ? '+' : '' }}Rp {{ number_format($chartChange, 0, ',', '.') }}</strong></div><div><span>Persentase</span><strong>{{ $chartPercentage > 0 ? '+' : '' }}{{ number_format($chartPercentage, 2, ',', '.') }}%</strong></div></div>
    @else
        <div class="chart-empty">Belum ada riwayat harga untuk pilihan ini.</div>
    @endif
</article>
