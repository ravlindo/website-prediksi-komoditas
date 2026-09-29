@extends('layouts.app')

@section('title', 'Tabel Harga Konsumen')

@section('content')
<div class="page feature-page reference-price-page">
    @include('prices.partials.hero')
    @include('prices.partials.breadcrumb')

    <section class="consumer-price-panel">
        <header>Tabel Harga Konsumen</header>
        @include('prices.partials.filters')
        @include('prices.partials.table')
    </section>
</div>
@endsection
