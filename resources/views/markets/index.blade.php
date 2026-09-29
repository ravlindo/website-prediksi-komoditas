@extends('layouts.app')

@section('title', 'Perbandingan Pasar')

@section('content')
<div class="page feature-page market-lines-page">
    @include('features.page-heading', [
        'eyebrow' => 'ANALISIS EMPAT PASAR',
        'title' => 'Perbandingan Harga Antar Pasar',
        'description' => 'Bandingkan pergerakan harga empat pasar dalam satu grafik dan nonaktifkan garis yang tidak ingin ditampilkan.',
    ])

    @include('markets.partials.comparison-chart')
    @include('markets.partials.daily-ranking')
</div>
@endsection
