@extends('layouts.app')

@section('title', 'Kualitas Data')

@section('content')
<div class="page feature-page quality-page">
    @include('features.page-heading', ['eyebrow'=>'VALIDASI DATA', 'title'=>'Kualitas Data', 'description'=>'Pantau kelengkapan dan harga nol sebelum data digunakan untuk analisis serta model prediksi.'])
    @include('quality.partials.filters')
    @include('quality.partials.summary')
    <section class="quality-analysis-grid">
        @include('quality.partials.commodity-issues')
        @include('quality.partials.market-quality')
    </section>
    <section class="quality-analysis-grid lower-grid">
        @include('quality.partials.lowest-dates')
        @include('quality.partials.guidance')
    </section>
    @include('quality.partials.records')
</div>
@endsection
