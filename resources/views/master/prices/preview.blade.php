@extends('layouts.app')
@section('title', 'Preview Import Harga')
@section('content')
<div class="page feature-page master-page">
    @include('features.page-heading', ['eyebrow'=>'LANGKAH 2 DARI 2', 'title'=>'Periksa Data Sebelum Import', 'description'=>'Database belum berubah. Pastikan ringkasan dan contoh data berikut sudah sesuai.'])
    @include('master.partials.tabs')
    <section class="import-summary-grid">
        <article><span>Total baris</span><strong>{{ number_format($import['summary']['total']) }}</strong></article>
        <article class="valid"><span>Data baru</span><strong>{{ number_format($import['summary']['valid']) }}</strong></article>
        <article class="duplicate"><span>Sudah tersedia</span><strong>{{ number_format($import['summary']['duplicate']) }}</strong></article>
        <article class="invalid"><span>Tidak valid</span><strong>{{ number_format($import['summary']['invalid']) }}</strong></article>
        <article class="zero"><span>Harga nol</span><strong>{{ number_format($import['summary']['zeros']) }}</strong></article>
    </section>
    <section class="panel import-preview-panel">
        <header class="master-panel-head"><div><h2>{{ $import['file_name'] }}</h2><p>Menampilkan maksimal 100 baris pertama untuk pemeriksaan</p></div></header>
        <div class="table-scroll"><table class="master-table import-preview-table"><thead><tr><th>Baris</th><th>Tanggal</th><th>Komoditas</th><th>Pasar</th><th>Harga</th><th>Status</th></tr></thead><tbody>
        @foreach($import['preview_rows'] as $row)
            <tr><td>{{ $row['line'] }}</td><td>{{ $row['date'] ?? '-' }}</td><td><strong>{{ $row['commodity'] ?: '-' }}</strong><small>{{ $row['unit'] ?: '-' }}</small></td><td>{{ $row['market'] ?: '-' }}</td><td>{{ $row['price'] === null ? '-' : 'Rp '.number_format($row['price'], 0, ',', '.') }}</td><td><span class="import-status {{ $row['status'] }}">{{ ['valid'=>'Data baru','duplicate'=>'Duplikat','invalid'=>'Tidak valid'][$row['status']] }}</span>@if($row['errors'])<small class="import-error">{{ implode(', ', $row['errors']) }}</small>@endif</td></tr>
        @endforeach
        </tbody></table></div>
        <form class="import-confirm" method="POST" action="{{ route('price-import.store') }}">@csrf<input type="hidden" name="token" value="{{ $import['token'] }}">
            <fieldset><legend>Jika data dengan komoditas, pasar, dan tanggal yang sama sudah tersedia:</legend><label><input type="radio" name="duplicate_mode" value="update" checked><span><strong>Perbarui data lama</strong><small>Harga lama diganti dengan nilai dari file.</small></span></label><label><input type="radio" name="duplicate_mode" value="skip"><span><strong>Lewati duplikat</strong><small>Harga yang sudah ada tidak disentuh.</small></span></label></fieldset>
            <div><a href="{{ route('price-import.create') }}">← Pilih file lain</a><button class="master-primary" type="submit" @disabled(($import['summary']['valid'] + $import['summary']['duplicate']) === 0)>Konfirmasi &amp; Import {{ number_format($import['summary']['valid'] + $import['summary']['duplicate']) }} Data</button></div>
        </form>
    </section>
</div>
@endsection
