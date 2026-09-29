@extends('layouts.app')

@section('title', $category->exists ? 'Ubah Kategori' : 'Tambah Kategori')

@section('content')
<div class="page feature-page master-page">
    @include('features.page-heading', ['eyebrow'=>'MASTER KATEGORI', 'title'=>$category->exists ? 'Ubah Kategori' : 'Tambah Kategori', 'description'=>'Nama kategori akan digunakan untuk mengelompokkan komoditas pada tabel dan filter.'])
    @include('master.partials.tabs')
    @include('master.partials.alerts')
    <section class="panel master-form-card">
        <form method="POST" action="{{ $category->exists ? route('categories.update', $category) : route('categories.store') }}">
            @csrf @if($category->exists) @method('PUT') @endif
            <label><span>Nama kategori <b>*</b></span><input name="name" value="{{ old('name', $category->name) }}" maxlength="100" required autofocus placeholder="Contoh: Beras"></label>
            <p class="field-help">Slug alamat dibuat otomatis dari nama kategori.</p>
            <div class="master-form-actions"><a href="{{ route('categories.index') }}">Batal</a><button class="master-primary" type="submit">Simpan Kategori</button></div>
        </form>
    </section>
</div>
@endsection
