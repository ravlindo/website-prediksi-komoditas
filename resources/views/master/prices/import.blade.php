@extends('layouts.app')
@section('title', 'Import Data Harga')
@section('content')
<div class="page feature-page master-page">
    @include('features.page-heading', ['eyebrow'=>'IMPORT MASSAL', 'title'=>'Import Data Harga', 'description'=>'Unggah Excel hasil SISKAPERBAPO atau CSV template untuk menambahkan banyak harga sekaligus.'])
    @include('master.partials.tabs')
    @include('master.partials.alerts')
    <section class="template-download-card">
        <div class="template-download-icon">
            <svg viewBox="0 0 24 24"><path d="M7 3h7l4 4v14H7zM14 3v5h5M9.5 13h6M9.5 17h4"/></svg>
        </div>
        <div class="template-download-copy"><span>TEMPLATE RESMI</span><strong>Mulai dari format yang sudah sesuai</strong><p>Berisi 60 komoditas, satuan, tanggal berikutnya, dan harga awal 0.</p></div>
        <form class="template-download-form" method="GET" action="{{ route('price-import.template') }}">
            <label><span>Cakupan pasar</span><select name="market"><option value="">Semua pasar ({{ $markets->count() * 60 }} baris)</option>@foreach($markets as $market)<option value="{{ $market->id }}">{{ $market->name }} (60 baris)</option>@endforeach</select></label>
            <button type="submit"><svg viewBox="0 0 24 24"><path d="M12 3v12m0 0 4-4m-4 4-4-4M5 20h14"/></svg>Download Template XLSX</button>
        </form>
    </section>
    <section class="import-layout">
        <article class="panel import-card">
            <div class="import-step"><span>1</span><div><strong>Pilih file sumber</strong><small>Maksimal 25 MB · XLSX atau CSV</small></div></div>
            <form method="POST" action="{{ route('price-import.preview') }}" enctype="multipart/form-data" id="importForm">
                @csrf
                <label class="file-dropzone" id="fileDropzone">
                    <input type="file" name="file" id="importFile" accept=".xlsx,.csv" required>
                    <span class="upload-icon"><svg viewBox="0 0 24 24"><path d="M12 16V4M7 9l5-5 5 5M5 14v5h14v-5"/></svg></span>
                    <strong>Tarik file ke sini atau klik untuk memilih</strong>
                    <small id="fileName">File Excel SISKAPERBAPO atau CSV dengan kolom yang sesuai</small>
                </label>
                <div class="import-actions"><a href="{{ route('master-prices.index') }}">Batal</a><button class="master-primary" type="submit">Periksa &amp; Tampilkan Preview →</button></div>
            </form>
        </article>
        <aside class="panel import-guide">
            <p class="eyebrow">FORMAT YANG DIDUKUNG</p><h2>Dua jenis file dapat dibaca</h2>
            <div class="format-option"><b>01</b><div><strong>Excel asli SISKAPERBAPO</strong><span>Boleh berisi satu pasar atau semua pasar. Sheet <code>SEMUA DATA</code> diprioritaskan; jika tidak ada, sistem membaca sheet pertama.</span></div></div>
            <div class="format-option"><b>02</b><div><strong>CSV sederhana</strong><span>Kolom: tanggal, pasar, komoditas, satuan, harga.</span></div></div>
            <ul><li>Harga 0 tetap diterima</li><li>Database belum berubah saat preview</li><li>Duplikat dapat diperbarui atau dilewati</li><li>File sumber disimpan sebagai arsip</li></ul>
        </aside>
    </section>
</div>
@endsection
