@extends('layouts.app')

@section('title', 'Master Kategori')

@section('content')
<div class="page feature-page master-page">
    @include('features.page-heading', ['eyebrow'=>'PENGELOLAAN DATA', 'title'=>'Master Data', 'description'=>'Kelola kelompok kategori dan daftar komoditas yang digunakan oleh seluruh halaman.'])
    @include('master.partials.tabs')
    @include('master.partials.alerts')

    <section class="panel master-panel">
        <header class="master-panel-head">
            <div><h2>Daftar Kategori</h2><p>{{ $categories->total() }} kategori tersimpan</p></div>
            <form class="master-search" method="GET"><input name="search" value="{{ request('search') }}" placeholder="Cari kategori..."><button>Cari</button></form>
            <a class="master-primary" href="{{ route('categories.create') }}">+ Tambah Kategori</a>
        </header>
        <div class="table-scroll">
            <table class="master-table">
                <thead><tr><th>No</th><th>Nama Kategori</th><th>Jumlah Komoditas</th><th>Status Hapus</th><th>Aksi</th></tr></thead>
                <tbody>
                @forelse($categories as $category)
                    <tr>
                        <td>{{ $categories->firstItem() + $loop->index }}</td>
                        <td><strong>{{ $category->name }}</strong><small>{{ $category->slug }}</small></td>
                        <td><b>{{ number_format($category->commodities_count) }}</b> komoditas</td>
                        <td><span class="master-badge {{ $category->commodities_count ? 'locked' : 'ready' }}">{{ $category->commodities_count ? 'Dilindungi' : 'Dapat dihapus' }}</span></td>
                        <td class="master-actions">
                            <a href="{{ route('categories.edit', $category) }}">Ubah</a>
                            <form method="POST" action="{{ route('categories.destroy', $category) }}" data-confirm-form data-confirm-title="Hapus kategori?" data-confirm-message="Kategori {{ $category->name }} akan dihapus permanen. Tindakan ini tidak dapat dibatalkan.">@csrf @method('DELETE')<button class="danger" @disabled($category->commodities_count)>Hapus</button></form>
                        </td>
                    </tr>
                @empty
                    <tr><td class="master-empty" colspan="5">Kategori tidak ditemukan.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="master-pagination">@include('partials.pagination', ['paginator' => $categories])</div>
    </section>
</div>
@endsection
