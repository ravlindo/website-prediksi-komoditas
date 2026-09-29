@extends('layouts.app')
@section('title', $price->exists ? 'Ubah Data Harga' : 'Tambah Data Harga')
@section('content')
<div class="page feature-page master-page">
    @include('features.page-heading', ['eyebrow'=>'DATA HARGA HARIAN', 'title'=>$price->exists ? 'Ubah Data Harga' : 'Tambah Data Harga Hari Berikutnya', 'description'=>'Satu komoditas hanya boleh memiliki satu harga untuk setiap pasar pada tanggal yang sama. Harga nol tetap diperbolehkan.'])
    @include('master.partials.tabs')
    @include('master.partials.alerts')
    <section class="panel master-form-card"><form method="POST" action="{{ $price->exists ? route('master-prices.update', $price) : route('master-prices.store') }}">
        @csrf @if($price->exists) @method('PUT') @endif
        <div class="master-form-grid">
            <label><span>Komoditas <b>*</b></span><select name="commodity_id" required><option value="">Pilih komoditas</option>@foreach($commodities as $commodity)<option value="{{ $commodity->id }}" @selected((string)old('commodity_id', $price->commodity_id) === (string)$commodity->id)>{{ $commodity->name }} — {{ $commodity->category->name }}</option>@endforeach</select></label>
            <label><span>Pasar <b>*</b></span><select name="market_id" required><option value="">Pilih pasar</option>@foreach($markets as $market)<option value="{{ $market->id }}" @selected((string)old('market_id', $price->market_id) === (string)$market->id)>{{ $market->name }}</option>@endforeach</select></label>
            <label><span>Tanggal harga <b>*</b></span><input type="date" name="price_date" value="{{ old('price_date', $price->price_date?->format('Y-m-d')) }}" required></label>
            <label><span>Harga (Rp) <b>*</b></span><input type="number" name="price" value="{{ old('price', $price->price) }}" min="0" step="1" required placeholder="Contoh: 15000"></label>
            <label class="wide-field"><span>Sumber data <b>*</b></span><input name="source" value="{{ old('source', $price->source) }}" maxlength="255" required placeholder="Contoh: Input manual"></label>
        </div>
        <p class="field-help">Untuk empat pasar pada hari yang sama, masukkan satu baris untuk setiap pasar. Nilai harga 0 tetap disimpan.</p>
        <div class="master-form-actions"><a href="{{ route('master-prices.index', ['commodity' => old('commodity_id', $price->commodity_id)]) }}">Batal</a><button class="master-primary">Simpan Data Harga</button></div>
    </form></section>
</div>
@endsection
