@extends('layouts.app')

@section('title', 'Dashboard Harga Mojokerto')

@section('content')
<div class="page" id="dashboard">
    @include('dashboard.sections.heading')
    @include('dashboard.sections.filters')
    @include('dashboard.sections.summary')
    @include('dashboard.sections.analytics')
    @include('dashboard.sections.prediction')
    @include('dashboard.sections.price-table')
    <footer>Sumber: Siskaperbapo Jawa Timur &bull; Periode data terakhir {{ \Carbon\Carbon::parse($latestDate)->translatedFormat('d F Y') }}</footer>
</div>
@endsection
