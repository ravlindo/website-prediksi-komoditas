@extends('layouts.app')
@section('title', $commodity->name)
@section('content')
<div class="page feature-page commodity-detail">
    <section class="detail-heading">
        <div><a href="{{ route('prices.index') }}">&larr; Harga Komoditas</a><p class="eyebrow">{{ strtoupper($commodity->category->name) }}</p><h1>{{ $commodity->name }}</h1><p>Analisis harga satuan {{ $commodity->unit }} di Kabupaten Mojokerto.</p></div>
        <form method="GET" class="detail-filter"><label><span>Tanggal</span><input type="date" name="date" value="{{ $selectedDate }}"></label><label><span>Pasar</span><select name="market"><option value="">Semua Pasar</option>@foreach($markets as $market)<option value="{{ $market->id }}" @selected($selectedMarket == $market->id)>{{ $market->name }}</option>@endforeach</select></label><button class="filter-button">Tampilkan</button></form>
    </section>

    <section class="detail-stats">
        <article><span>Harga Terbaru</span><strong>Rp {{ number_format($latestPrice, 0, ',', '.') }}</strong><small>{{ $selectedDate }}</small></article>
        <article><span>Harga Sebelumnya</span><strong>Rp {{ number_format($previousPrice, 0, ',', '.') }}</strong><small>Perbandingan harian</small></article>
        <article><span>Terendah 30 Hari</span><strong>Rp {{ number_format($minimumPrice, 0, ',', '.') }}</strong><small>Semua pasar</small></article>
        <article><span>Tertinggi 30 Hari</span><strong>Rp {{ number_format($maximumPrice, 0, ',', '.') }}</strong><small>Semua pasar</small></article>
        <article><span>Rata-rata 30 Hari</span><strong>Rp {{ number_format($averagePrice, 0, ',', '.') }}</strong><small>Harga konsumen</small></article>
    </section>

    <section class="detail-grid">
        <article class="panel history-panel"><div class="panel-head"><div><h2>Riwayat Harga 30 Hari</h2><p>Rata-rata harga harian dari seluruh pasar</p></div></div><div class="history-bars">@php($maxChart = max(1, $history->max('average_price') ?? 1))@foreach($history as $point)<div title="{{ $point->price_date }}: Rp {{ number_format($point->average_price, 0, ',', '.') }}"><i style="height:{{ max(8, ($point->average_price / $maxChart) * 100) }}%"></i></div>@endforeach</div><div class="history-axis"><span>{{ optional($history->first())->price_date }}</span><span>{{ optional($history->last())->price_date }}</span></div></article>
        <article class="panel market-ranking"><div class="panel-head"><div><h2>Harga per Pasar</h2><p>Urut dari harga terendah</p></div></div><div class="market-price-list">@forelse($marketPrices as $price)<div><span>{{ $loop->iteration }}</span><p><strong>{{ $price->market->name }}</strong><small>{{ $price->market->district }}</small></p><b>Rp {{ number_format($price->price, 0, ',', '.') }}</b></div>@empty<p class="empty-state">Data pada tanggal ini belum tersedia.</p>@endforelse</div></article>
    </section>

    <section class="panel history-table"><div class="panel-head"><div><h2>Data Harga Harian</h2><p>Ringkasan minimum, rata-rata, dan maksimum</p></div></div><div class="table-scroll"><table><thead><tr><th>Tanggal</th><th>Minimum</th><th>Rata-rata</th><th>Maksimum</th></tr></thead><tbody>@foreach($history->reverse()->take(10) as $point)<tr><td>{{ $point->price_date }}</td><td>Rp {{ number_format($point->minimum_price, 0, ',', '.') }}</td><td><strong>Rp {{ number_format($point->average_price, 0, ',', '.') }}</strong></td><td>Rp {{ number_format($point->maximum_price, 0, ',', '.') }}</td></tr>@endforeach</tbody></table></div></section>
</div>
@endsection
