@extends('layouts.app')
@section('title', 'Ganti Kata Sandi')
@section('content')
<div class="page feature-page master-page">
    @include('features.page-heading', ['eyebrow'=>'KEAMANAN AKUN', 'title'=>'Ganti Kata Sandi', 'description'=>'Perbarui kata sandi administrator secara berkala untuk melindungi pengelolaan data.'])
    @include('master.partials.alerts')
    <form class="panel master-form-card password-form" method="POST" action="{{ route('password.update') }}">
        @csrf @method('PUT')
        <label><span>Kata sandi saat ini <b>*</b></span><input type="password" name="current_password" autocomplete="current-password" required></label>
        <label><span>Kata sandi baru <b>*</b></span><input type="password" name="password" minlength="10" autocomplete="new-password" required><p class="field-help">Minimal 10 karakter. Gunakan kombinasi huruf, angka, dan simbol.</p></label>
        <label><span>Konfirmasi kata sandi baru <b>*</b></span><input type="password" name="password_confirmation" minlength="10" autocomplete="new-password" required></label>
        <div class="master-form-actions"><a href="{{ route('master-prices.index') }}">Batal</a><button class="master-primary" type="submit">Simpan Kata Sandi</button></div>
    </form>
</div>
@endsection
