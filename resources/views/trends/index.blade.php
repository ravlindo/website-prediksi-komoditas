@extends('layouts.app')
@section('title', 'Grafik & Tren')
@section('content')
<div class="page trend-page-premium">
    <section class="trend-command-hero">
        <div class="trend-command-copy"><p><span></span> PUSAT ANALISIS HARGA</p><h1>Pergerakan harga.<br><em>Terlihat lebih jelas.</em></h1><small>Telusuri pola harga komoditas Kabupaten Mojokerto melalui data pasar yang telah tersimpan dan terverifikasi.</small></div>
        <form method="get" class="trend-quick-filter">
            <label><span>Komoditas</span><select name="chart_commodity">@foreach($commodityOptions as $option)<option value="{{ $option->id }}" @selected($selectedCommodity?->id===$option->id)>{{ $option->name }}</option>@endforeach</select></label>
            <label><span>Pasar</span><select name="market"><option value="">Seluruh pasar</option>@foreach($markets as $market)<option value="{{ $market->id }}" @selected($selectedMarket===$market->id)>{{ $market->name }}</option>@endforeach</select></label>
            <input type="hidden" name="chart_days" value="{{ $chartDays }}"><input type="hidden" name="chart_scale" value="{{ $chartScale }}">
            <button>Tampilkan Analisis <b>→</b></button>
        </form>
        <div class="trend-live-badge"><i></i><span>DATA TERAKHIR</span><strong>{{ \Carbon\Carbon::parse($selectedDate)->translatedFormat('d F Y') }}</strong></div>
    </section>
    <section class="trend-stat-ribbon">
        <div><span>Harga terbaru</span><strong>Rp {{ number_format($latestChartPrice,0,',','.') }}</strong><small>{{ $selectedCommodity?->unit }}</small></div>
        <div><span>Rata-rata periode</span><strong>Rp {{ number_format($trendStats['average'],0,',','.') }}</strong><small>{{ $trendStats['observations'] }} titik data</small></div>
        <div><span>Harga terendah</span><strong>Rp {{ number_format($trendStats['minimum'],0,',','.') }}</strong><small>Dalam {{ $chartDays }} hari</small></div>
        <div><span>Harga tertinggi</span><strong>Rp {{ number_format($trendStats['maximum'],0,',','.') }}</strong><small>Dalam {{ $chartDays }} hari</small></div>
        <div class="{{ $trendStats['volatility'] > 10 ? 'hot' : '' }}"><span>Rentang perubahan</span><strong>{{ number_format($trendStats['volatility'],2,',','.') }}%</strong><small>{{ $trendStats['volatility'] > 10 ? 'Perlu perhatian' : 'Relatif terkendali' }}</small></div>
    </section>
    <section class="trend-chart-stage">
        <div class="trend-stage-heading"><div><span>ANALISIS INTERAKTIF</span><h2>{{ $selectedCommodity?->name }}</h2><p>Klik titik grafik atau kartu tanggal untuk melihat nilai pada hari tertentu.</p></div><div class="trend-stage-mark"><i></i>Rata-rata {{ $selectedMarket ? 'pasar terpilih' : 'seluruh pasar' }}</div></div>
        @include('dashboard.components.price-chart')
    </section>
    <section class="trend-insight-grid">
        <article><span>01</span><div><small>ARAH TERBARU</small><h3>{{ $chartChange > 0 ? 'Harga bergerak naik' : ($chartChange < 0 ? 'Harga bergerak turun' : 'Harga tidak berubah') }}</h3><p>Perubahan terakhir {{ $chartChange > 0 ? '+' : '' }}Rp {{ number_format($chartChange,0,',','.') }} dibandingkan titik sebelumnya.</p></div></article>
        <article><span>02</span><div><small>SEBARAN HARGA</small><h3>Selisih Rp {{ number_format($trendStats['maximum']-$trendStats['minimum'],0,',','.') }}</h3><p>Jarak antara harga terendah dan tertinggi dalam periode yang dipilih.</p></div></article>
        <article><span>03</span><div><small>CATATAN DATA</small><h3>{{ $trendStats['observations'] }} hari terpantau</h3><p>Analisis mengikuti data yang tersedia; nilai nol tidak digunakan sebagai harga rata-rata positif.</p></div></article>
    </section>
</div>
@endsection
