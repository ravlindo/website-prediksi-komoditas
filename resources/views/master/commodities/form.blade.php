@extends('layouts.app')

@section('title', $commodity->exists ? 'Ubah Komoditas' : 'Tambah Komoditas')

@section('content')
<div class="page feature-page master-page">
    @include('features.page-heading', ['eyebrow'=>'MASTER KOMODITAS', 'title'=>$commodity->exists ? 'Ubah Komoditas' : 'Tambah Komoditas', 'description'=>'Data ini menjadi acuan harga, grafik, laporan, dan model prediksi.'])
    @include('master.partials.tabs')
    @include('master.partials.alerts')
    <section class="panel master-form-card">
        <form method="POST" action="{{ $commodity->exists ? route('commodities.update', $commodity) : route('commodities.store') }}">
            @csrf @if($commodity->exists) @method('PUT') @endif
            <div class="master-form-grid">
                <label><span>Nama komoditas <b>*</b></span><input name="name" value="{{ old('name', $commodity->name) }}" maxlength="150" required placeholder="Contoh: Beras Premium"></label>
                <label><span>Kategori <b>*</b></span><select name="category_id" required><option value="">Pilih kategori</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected((string)old('category_id', $commodity->category_id) === (string)$category->id)>{{ $category->name }}</option>@endforeach</select></label>
                <label><span>Satuan <b>*</b></span><input name="unit" value="{{ old('unit', $commodity->unit) }}" maxlength="30" required placeholder="kg, liter, batang"></label>
                <label class="master-check"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @checked((bool)old('is_active', $commodity->exists ? $commodity->is_active : true))><span><strong>Komoditas aktif</strong><small>Tampilkan dalam pilihan data operasional.</small></span></label>
            </div>
            <div class="master-form-actions"><a href="{{ route('commodities.index') }}">Batal</a><button class="master-primary" type="submit">Simpan Komoditas</button></div>
        </form>
    </section>
</div>
@endsection
