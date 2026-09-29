@if(isset($dashboardPrediction) && $dashboardPrediction)
<section class="prediction-strip prediction-ready" id="prediksi">
    <div class="prediction-copy"><div><span class="prediction-kicker">PREDIKSI 7 HARI · {{ $dashboardPrediction->model_version }}</span><h2>{{ $dashboardPrediction->commodity->name }}</h2><p>Berdasarkan data sampai {{ $dashboardPrediction->data_last_date->translatedFormat('d F Y') }}. Nilai ini merupakan estimasi, bukan harga pasti.</p></div></div>
    <a href="{{ route('predictions.index', ['commodity'=>$dashboardPrediction->commodity->slug, 'horizon'=>7]) }}">Buka rincian &rarr;</a>
    <div class="prediction-value">
        <span>Estimasi harga</span>
        <strong>Rp {{ number_format($dashboardPrediction->predicted_price, 0, ',', '.') }}</strong>
        <small>
            @auth
                {{ $dashboardPrediction->model_name }} · Skor {{ number_format($dashboardPrediction->accuracy_score, 2, ',', '.') }}/100 · MAPE {{ number_format($dashboardPrediction->mape, 2, ',', '.') }}%
            @else
                Model {{ $dashboardPrediction->model_name }} · estimasi 7 hari mendatang
            @endauth
        </small>
    </div>
</section>
@else
<section class="prediction-strip prediction-pending" id="prediksi"><div class="prediction-copy"><div><h2>Prediksi Harga Belum Tersedia</h2><p>Belum ada hasil model yang lolos penyaringan untuk ditampilkan.</p></div></div><a href="{{ route('predictions.index') }}">Lihat status model &rarr;</a><div class="prediction-value"><span>Status</span><strong>Menunggu pengujian</strong><small>Tidak ada angka simulasi yang ditampilkan</small></div></section>
@endif
