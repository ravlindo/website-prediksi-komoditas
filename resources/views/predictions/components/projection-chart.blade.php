<section class="prediction-chart" data-prediction-chart>
    <header class="prediction-chart-head">
        <div>
            <p><span></span> VISUALISASI PROYEKSI</p>
            <h2>Lintasan harga dan prediksi</h2>
            <small>Rata-rata harga pasar dibandingkan dengan hasil model pada lima rentang prediksi.</small>
        </div>
        <div class="prediction-chart-dates">
            <span><i class="actual"></i> Data aktual <strong>sampai {{ $chart['latest_actual']['label'] }}</strong></span>
        </div>
    </header>

    <div class="prediction-chart-stage">
        <div class="prediction-chart-canvas">
            <svg viewBox="0 0 {{ $chart['width'] }} {{ $chart['height'] }}" role="img" aria-label="Grafik harga aktual dan prediksi {{ $selectedCommodity->name }}">
                <defs>
                    <linearGradient id="predictionActualArea" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0" stop-color="#2b75df" stop-opacity=".24"/>
                        <stop offset="1" stop-color="#2b75df" stop-opacity="0"/>
                    </linearGradient>
                    <filter id="predictionPointGlow" x="-100%" y="-100%" width="300%" height="300%">
                        <feGaussianBlur stdDeviation="4" result="blur"/>
                        <feMerge><feMergeNode in="blur"/><feMergeNode in="SourceGraphic"/></feMerge>
                    </filter>
                </defs>

                @foreach($chart['y_ticks'] as $tick)
                    <line class="prediction-grid-line" x1="{{ $chart['left'] }}" x2="{{ $chart['right'] }}" y1="{{ $tick['y'] }}" y2="{{ $tick['y'] }}"/>
                    <text class="prediction-y-label" x="{{ $chart['left'] - 12 }}" y="{{ $tick['y'] + 4 }}" text-anchor="end">{{ $tick['label'] }}</text>
                @endforeach
                @foreach($chart['x_ticks'] as $tick)
                    <text class="prediction-x-label" x="{{ $tick['x'] }}" y="322" text-anchor="middle">{{ $tick['label'] }}</text>
                @endforeach

                @if($chart['actual_area'])
                    <polygon class="prediction-actual-area" points="{{ $chart['actual_area'] }}"/>
                    <polyline class="prediction-actual-line" points="{{ $chart['actual_polyline'] }}"/>
                @endif
                @if($chart['forecast_polyline'])
                    <polyline class="prediction-forecast-line" points="{{ $chart['forecast_polyline'] }}"/>
                @endif

                @foreach($chart['actual'] as $point)
                    <circle class="prediction-actual-point" cx="{{ $point['x'] }}" cy="{{ $point['y'] }}" r="3.2" tabindex="0"
                        data-chart-focus data-kind="Harga aktual" data-date="{{ $point['date_label'] }}"
                        data-value="{{ $point['value_label'] }}" data-status="Rata-rata seluruh pasar"
                        aria-label="Harga aktual {{ $point['date_label'] }} {{ $point['value_label'] }}"/>
                @endforeach

                @foreach($chart['forecasts'] as $point)
                    @if($point['visible'])
                        <line class="prediction-range-line {{ $point['published'] ? 'published' : 'withheld' }}" x1="{{ $point['x'] }}" x2="{{ $point['x'] }}" y1="{{ $point['upper_y'] }}" y2="{{ $point['lower_y'] }}"/>
                        <line class="prediction-range-cap" x1="{{ $point['x'] - 5 }}" x2="{{ $point['x'] + 5 }}" y1="{{ $point['upper_y'] }}" y2="{{ $point['upper_y'] }}"/>
                        <line class="prediction-range-cap" x1="{{ $point['x'] - 5 }}" x2="{{ $point['x'] + 5 }}" y1="{{ $point['lower_y'] }}" y2="{{ $point['lower_y'] }}"/>
                        <circle class="prediction-forecast-point {{ $point['published'] ? 'published' : 'withheld' }} {{ $point['selected'] ? 'selected' : '' }}" cx="{{ $point['x'] }}" cy="{{ $point['y'] }}" r="{{ $point['selected'] ? 8 : 6 }}" tabindex="0"
                            data-chart-focus data-kind="Proyeksi {{ $point['horizon'] }} hari" data-date="{{ $point['date_label'] }}"
                            data-value="{{ $point['value_label'] }}" data-status="{{ $point['status_label'] }}"
                            data-range="{{ $point['lower_label'] }} – {{ $point['upper_label'] }}"
                            aria-label="Proyeksi {{ $point['horizon'] }} hari {{ $point['date_label'] }} {{ $point['value_label'] }} {{ $point['status_label'] }}"/>
                    @endif
                @endforeach
            </svg>

            <aside class="prediction-chart-tooltip is-hidden" data-chart-tooltip aria-live="polite" aria-hidden="true">
                <span data-tooltip-kind>Informasi harga</span>
                <small data-tooltip-date>-</small>
                <strong data-tooltip-value>-</strong>
                <p data-tooltip-status></p>
                <em data-tooltip-range class="is-hidden"></em>
            </aside>
        </div>

        <div class="prediction-chart-side">
            <div class="prediction-chart-insight">
                <span>KOMODITAS TERPILIH</span>
                <strong>{{ $selectedCommodity->name }}</strong>
                <small>{{ $selectedCommodity->category->name ?? 'Komoditas' }} · {{ $selectedCommodity->unit }}</small>
            </div>
            <div class="prediction-chart-legend">
                <span><i class="actual"></i> Harga aktual</span>
                <span><i class="forecast"></i> Prediksi layak</span>
                @auth<span><i class="withheld"></i> Prediksi ditahan</span>@endauth
                <span><i class="range"></i> Rentang estimasi</span>
            </div>
            <p>Tekan titik grafik atau kartu horizon 1, 3, 7, 14, dan 30 hari untuk menampilkan harga serta status prediksinya.</p>
        </div>
    </div>

    <div class="prediction-chart-horizons" aria-label="Ringkasan proyeksi per horizon">
        @foreach($chart['forecasts'] as $point)
            <button type="button" @class(['active' => $point['selected'], 'withheld' => ! $point['published']])
                data-chart-focus data-kind="Proyeksi {{ $point['horizon'] }} hari" data-date="{{ $point['date_label'] }}"
                data-value="{{ $point['value_label'] }}" data-status="{{ $point['status_label'] }}"
                data-range="{{ $point['visible'] ? $point['lower_label'].' – '.$point['upper_label'] : '' }}">
                <span>{{ $point['horizon'] }} HARI</span>
                <strong>{{ $point['published'] ? $point['value_label'] : 'Ditahan' }}</strong>
                <small>{{ $point['date_label'] }} · {{ $point['status_label'] }}</small>
            </button>
        @endforeach
    </div>
</section>
