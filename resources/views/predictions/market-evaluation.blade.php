@extends('layouts.app')
@section('title', 'Perbandingan Harga 2 Pasar Utama')
@section('content')
@php
    $summary = $evaluation['summary'];
    $result = $evaluation['result'];
    $chart = $evaluation['chart'];
    $comparison = $evaluation['comparison'];
@endphp
<div class="page prediction-page prediction-market-page">
    @include('features.page-heading',[
        'title'=>'Perbandingan Harga 2 Pasar Utama',
        'titleIcon'=>'prediction',
        'description'=>'Perbandingan harga dan hasil model untuk Pasar Mojosari dan Pasar Kedungmaling.'
    ])
    @include('predictions.components.tabs')

    <nav class="market-eval-subtabs" aria-label="Bagian perbandingan dua pasar">
        <a href="{{ route('predictions.market-evaluation', ['commodity'=>$evaluation['selectedCommodity'],'period'=>$evaluation['selectedPeriod']]) }}" class="{{ $evaluationSection==='comparison'?'active':'' }}"><span>01</span><div><strong>Perbandingan & Prediksi Harga</strong><small>Tampilan utama untuk publik dan pemantauan harga</small></div></a>
        <a href="{{ route('predictions.market-evaluation', ['section'=>'model','commodity'=>$evaluation['selectedCommodity'],'market'=>$evaluation['selectedMarket'],'period'=>$evaluation['selectedPeriod']]) }}" class="{{ $evaluationSection==='model'?'active':'' }}"><span>02</span><div><strong>Detail Model & Akurasi</strong><small>Metrik evaluasi dan screening teknis</small></div></a>
    </nav>

    @if($evaluationSection === 'comparison')
        <section class="two-market-command">
            <div><p><span></span> PANTAUAN DUA PASAR UTAMA</p><h2>Satu komoditas.<br><em>Dua pembacaan pasar.</em></h2><small>Garis penuh menunjukkan harga aktual. Garis putus menunjukkan hasil model pada periode evaluasi.</small></div>
            <form method="get"><label><span>Komoditas fokus</span><select name="commodity">@foreach($evaluation['commodities'] as $commodity)<option value="{{ $commodity }}" @selected($commodity===$evaluation['selectedCommodity'])>{{ $commodity }}</option>@endforeach</select></label><label><span>Periode</span><select name="period"><option value="7" @selected($evaluation['selectedPeriod']===7)>7 hari</option><option value="30" @selected($evaluation['selectedPeriod']===30)>30 hari</option><option value="74" @selected($evaluation['selectedPeriod']===74)>Semua data</option></select></label><button>Tampilkan <b>→</b></button></form>
        </section>

        <section class="two-market-kpis">
            @foreach($comparison['series'] as $series)
                @php $latest=$series['latest']; @endphp
                <article style="--market-color:{{ $series['colors']['actual'] }}"><div><span>Harga terakhir · {{ $series['market'] }}</span><strong>Rp {{ number_format($latest['actual'] ?? 0,0,',','.') }}</strong><small>{{ $latest ? \Carbon\Carbon::parse($latest['date'])->translatedFormat('d F Y') : 'Tidak tersedia' }}</small></div><b class="trend-{{ $series['trend'] }}">{{ $series['trend']==='naik'?'↗':($series['trend']==='turun'?'↘':'→') }} {{ ucfirst($series['trend']) }}</b></article>
            @endforeach
            <article class="gap-card"><div><span>Gap harga antar pasar</span><strong>Rp {{ number_format($comparison['gap'],0,',','.') }}</strong><small>Selisih harga terakhir</small></div><b>{{ number_format($comparison['gap_percent'],2,',','.') }}%</b></article>
        </section>

        <section class="two-market-visual" data-two-market-chart>
            <header><div><p>PERBANDINGAN {{ strtoupper($evaluation['selectedCommodity']) }}</p><h2>Aktual dan hasil model kedua pasar</h2><small>Klik titik untuk membuka rincian harga pada tanggal terpilih.</small></div><div class="chart-info"><button type="button" aria-label="Informasi model">i</button><aside><strong>Informasi model</strong>@foreach($comparison['series'] as $series)<p><span>{{ $series['market'] }}</span><b>{{ $series['model'] }} · skor {{ number_format($series['accuracy_score'],2,',','.') }}/100</b></p>@endforeach<small>Skor merupakan 100 − MAPE. Garis putus adalah hasil model pada data uji, bukan harga aktual.</small></aside></div></header>
            <div class="two-market-legend">@foreach($comparison['series'] as $series)<button type="button" data-series-toggle="{{ \Illuminate\Support\Str::slug($series['market']) }}-actual" class="active"><i style="--legend-color:{{ $series['colors']['actual'] }}"></i>{{ $series['market'] }} aktual</button><button type="button" data-series-toggle="{{ \Illuminate\Support\Str::slug($series['market']) }}-forecast" class="active dashed"><i style="--legend-color:{{ $series['colors']['forecast'] }}"></i>{{ $series['market'] }} model</button>@endforeach</div>
            <div class="two-market-canvas">
                <svg viewBox="0 0 1000 350" role="img" aria-label="Perbandingan harga Mojosari dan Kedungmaling">
                    @foreach([75,187,300] as $y)<line class="eval-grid" x1="66" y1="{{ $y }}" x2="934" y2="{{ $y }}"/>@endforeach
                    <text x="4" y="79">Rp {{ number_format($comparison['max'],0,',','.') }}</text><text x="4" y="191">Rp {{ number_format($comparison['mid'],0,',','.') }}</text><text x="4" y="304">Rp {{ number_format($comparison['min'],0,',','.') }}</text>
                    @foreach($comparison['series'] as $series)
                        @php $slug=\Illuminate\Support\Str::slug($series['market']); @endphp
                        <g data-series="{{ $slug }}-actual"><polyline class="two-market-line" style="--line-color:{{ $series['colors']['actual'] }}" points="{{ $series['actual_polyline'] }}"/>@foreach($series['points'] as $point)<g class="two-market-point" tabindex="0" role="button" data-market-point data-market="{{ $series['market'] }}" data-date="{{ \Carbon\Carbon::parse($point['date'])->translatedFormat('d F Y') }}" data-actual="Rp {{ number_format($point['actual'],0,',','.') }}" data-forecast="Rp {{ number_format($point['forecast'],0,',','.') }}" data-difference="{{ ($point['difference']>=0?'+':'').'Rp '.number_format($point['difference'],0,',','.') }}"><circle class="eval-hit" cx="{{ $point['x'] }}" cy="{{ $point['actual_y'] }}" r="9"/><circle cx="{{ $point['x'] }}" cy="{{ $point['actual_y'] }}" r="3.2" fill="{{ $series['colors']['actual'] }}"/></g>@endforeach</g>
                        <g data-series="{{ $slug }}-forecast"><polyline class="two-market-line forecast" style="--line-color:{{ $series['colors']['forecast'] }}" points="{{ $series['forecast_polyline'] }}"/></g>
                    @endforeach
                    <text class="eval-date" x="66" y="338">{{ \Carbon\Carbon::parse($comparison['start'])->translatedFormat('d M Y') }}</text><text class="eval-date" x="934" y="338" text-anchor="end">{{ \Carbon\Carbon::parse($comparison['end'])->translatedFormat('d M Y') }}</text>
                </svg>
                <aside class="two-market-tooltip is-hidden" data-market-tooltip><button type="button" data-market-close>×</button><span data-market-name>Pasar</span><strong data-market-date>Tanggal</strong><div><p>Harga aktual<b data-market-actual>—</b></p><p>Hasil model<b data-market-forecast>—</b></p></div><small>Selisih model <b data-market-difference>—</b></small></aside>
            </div>
        </section>

        <details class="two-market-table" open>
            <summary>
                <div><span>RINCIAN HARIAN</span><strong>Tabel aktual, model, dan gap pasar</strong></div>
                <small>{{ number_format($comparison['table']->total(), 0, ',', '.') }} tanggal <b>⌄</b></small>
            </summary>
            <div class="table-scroll"><table><thead><tr><th>Tanggal</th><th>Harga Mojosari</th><th>Harga Kedungmaling</th><th>Prediksi model</th><th>Selisih pasar</th></tr></thead><tbody>@foreach($comparison['table'] as $row)<tr data-evaluation-date="{{ $row['date'] }}"><td>{{ \Carbon\Carbon::parse($row['date'])->translatedFormat('d M Y') }}</td><td>{{ $row['mojosari']?'Rp '.number_format($row['mojosari']['actual'],0,',','.'):'—' }}</td><td>{{ $row['kedungmaling']?'Rp '.number_format($row['kedungmaling']['actual'],0,',','.'):'—' }}</td><td><span>M {{ $row['mojosari']?'Rp '.number_format($row['mojosari']['forecast'],0,',','.'):'—' }}</span><span>K {{ $row['kedungmaling']?'Rp '.number_format($row['kedungmaling']['forecast'],0,',','.'):'—' }}</span></td><td class="{{ ($row['gap']??0)>0?'positive':(($row['gap']??0)<0?'negative':'') }}">{{ $row['gap']===null?'—':(($row['gap']>0?'+':'').'Rp '.number_format($row['gap'],0,',','.')) }}</td></tr>@endforeach</tbody></table></div>
            <div class="evaluation-pagination">@include('partials.pagination', ['paginator' => $comparison['table']])</div>
        </details>
    @else
        <section class="market-eval-hero technical"><div><p><span></span> RUANG AUDIT MODEL</p><h2>Detail Model<br><em>& Akurasi.</em></h2><small>Ringkasan teknis hasil screening 30 pasangan komoditas–pasar.</small></div><div class="market-eval-score"><span>Rata-rata MAPE</span><strong>{{ number_format($summary['average_mape'],2,',','.') }}%</strong><small>seluruh model terpilih</small></div></section>
        <section class="market-eval-summary"><div><span>Pasangan diuji</span><strong>{{ $summary['pairs'] }}</strong><small>15 komoditas × 2 pasar</small></div><div><span>Titik final bersih</span><strong>{{ number_format($summary['points'],0,',','.') }}</strong><small>tanpa kandidat duplikat</small></div>@foreach($evaluation['models'] as $model)<div><span>{{ $model['name'] }} terpilih</span><strong>{{ $model['pairs'] }}</strong><small>MAPE rata-rata {{ number_format($model['average_mape'],2,',','.') }}%</small></div>@endforeach</section>

        <form class="market-eval-filter" method="get"><input type="hidden" name="section" value="model"><label><span>Komoditas</span><select name="commodity">@foreach($evaluation['commodities'] as $commodity)<option value="{{ $commodity }}" @selected($commodity===$evaluation['selectedCommodity'])>{{ $commodity }}</option>@endforeach</select></label><label><span>Pasar</span><select name="market">@foreach($evaluation['markets'] as $market)<option value="{{ $market }}" @selected($market===$evaluation['selectedMarket'])>{{ $market }}</option>@endforeach</select></label><label><span>Periode grafik</span><select name="period"><option value="30" @selected($evaluation['selectedPeriod']===30)>30 hari</option><option value="60" @selected($evaluation['selectedPeriod']===60)>60 hari</option><option value="74" @selected($evaluation['selectedPeriod']===74)>Semua data</option></select></label><button>Tampilkan evaluasi <b>→</b></button></form>
        <section class="market-eval-result"><header><div><p>MODEL TERPILIH</p><h2>{{ $result['commodity'] }}</h2><small>{{ $result['market'] }}</small></div><div class="market-eval-badge"><span>{{ $result['quality'] }}</span><strong>{{ $result['model'] }}</strong><small>{{ $result['model_detail'] }}</small></div></header><div class="market-eval-metrics"><div><span>Skor akurasi</span><strong>{{ number_format($result['accuracy_score'],2,',','.') }}/100</strong><small>Ringkasan 100 − MAPE</small></div><div><span>MAPE</span><strong>{{ number_format($result['mape'],2,',','.') }}%</strong><small>Galat persentase rata-rata</small></div><div><span>MAE</span><strong>Rp {{ number_format($result['mae'],0,',','.') }}</strong><small>Rata-rata selisih absolut</small></div><div><span>RMSE</span><strong>Rp {{ number_format($result['rmse'],0,',','.') }}</strong><small>Galat besar diberi bobot lebih</small></div></div>
            <div class="market-eval-chart" data-market-evaluation-chart><div class="market-eval-chart-heading"><div><p>AKTUAL VS FORECAST MODEL TERPILIH</p><h3>Ketepatan model sepanjang periode uji</h3></div><div class="market-eval-legend"><span class="actual">Harga aktual</span><span class="forecast">Hasil model</span></div></div><div class="market-eval-canvas"><svg viewBox="0 0 1000 350">@foreach([70,185,300] as $y)<line class="eval-grid" x1="60" y1="{{ $y }}" x2="940" y2="{{ $y }}"/>@endforeach<text x="5" y="74">Rp {{ number_format($chart['max'],0,',','.') }}</text><text x="5" y="189">Rp {{ number_format($chart['mid'],0,',','.') }}</text><text x="5" y="304">Rp {{ number_format($chart['min'],0,',','.') }}</text><polyline class="eval-line actual" points="{{ $chart['actual_polyline'] }}"/><polyline class="eval-line forecast" points="{{ $chart['forecast_polyline'] }}"/>@foreach($chart['points'] as $point)<g class="eval-point" tabindex="0" role="button" data-eval-point data-date="{{ \Carbon\Carbon::parse($point['date'])->translatedFormat('d F Y') }}" data-actual="Rp {{ number_format($point['actual'],0,',','.') }}" data-forecast="Rp {{ number_format($point['forecast'],0,',','.') }}" data-difference="{{ ($point['difference']>=0?'+':'').'Rp '.number_format($point['difference'],0,',','.') }}"><circle class="eval-hit" cx="{{ $point['x'] }}" cy="{{ $point['actual_y'] }}" r="10"/><circle class="eval-dot actual" cx="{{ $point['x'] }}" cy="{{ $point['actual_y'] }}" r="3.2"/><circle class="eval-dot forecast" cx="{{ $point['x'] }}" cy="{{ $point['forecast_y'] }}" r="3.2"/></g>@endforeach<text class="eval-date" x="60" y="336">{{ \Carbon\Carbon::parse($chart['start'])->translatedFormat('d M Y') }}</text><text class="eval-date" x="940" y="336" text-anchor="end">{{ \Carbon\Carbon::parse($chart['end'])->translatedFormat('d M Y') }}</text></svg><aside class="market-eval-tooltip is-hidden" data-eval-tooltip><button type="button" data-eval-close>×</button><span data-eval-date>—</span><div><p>Harga aktual<strong data-eval-actual>—</strong></p><p>Hasil model<strong data-eval-forecast>—</strong></p></div><small>Selisih <b data-eval-difference>—</b></small></aside></div></div>
        </section>

        <section class="model-comparison-table">
            <header><div><p>SCREENING 3 MODEL</p><h2>Perbandingan metrik per pasangan</h2><small>Metrik dihitung ulang dari aktual dan forecast yang tersedia di file keluaran.</small></div><span>{{ $evaluation['modelComparison']->total() }} pasangan</span></header>
            <div class="table-scroll"><table>
                <thead><tr><th rowspan="2">Komoditas & pasar</th><th colspan="3">SARIMA</th><th colspan="3">Prophet</th><th colspan="3">XGBoost</th><th rowspan="2">Model terpilih & alasan</th></tr><tr>@foreach(['SARIMA','Prophet','XGBoost'] as $unused)<th>MAE</th><th>RMSE</th><th>MAPE</th>@endforeach</tr></thead>
                <tbody>
                @foreach($evaluation['modelComparison'] as $pair)
                    <tr>
                        <td><strong>{{ $pair['commodity'] }}</strong><small>{{ $pair['market'] }}</small></td>
                        @foreach(['SARIMA','Prophet','XGBoost'] as $model)
                            @php($metric = $pair['metrics'][$model])
                            @if($metric)
                                <td>{{ number_format($metric['mae'],0,',','.') }}</td><td>{{ number_format($metric['rmse'],0,',','.') }}</td><td>{{ number_format($metric['mape'],2,',','.') }}%</td>
                            @else
                                <td colspan="3" class="unavailable">Tidak diekspor</td>
                            @endif
                        @endforeach
                        <td><b>{{ $pair['selected_model'] }}</b><small>{{ $pair['reason'] }}</small></td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
            <footer>Nilai “Tidak diekspor” berarti file sumber tidak membawa keluaran kandidat tersebut. Sistem tidak mengisi angka perkiraan.</footer>
            <div class="evaluation-pagination">@include('partials.pagination', ['paginator' => $evaluation['modelComparison']])</div>
        </section>
    @endif
</div>
@endsection
