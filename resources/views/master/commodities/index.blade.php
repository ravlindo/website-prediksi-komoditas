@extends('layouts.app')

@section('title', 'Master Komoditas')

@section('content')
<div class="page feature-page master-page">
    @include('features.page-heading', ['eyebrow'=>'PENGELOLAAN DATA', 'title'=>'Master Data', 'description'=>'Kelola nama, kategori, satuan, dan status komoditas tanpa menghapus riwayat harga.'])
    @include('master.partials.tabs')
    @include('master.partials.alerts')
    <section class="panel master-panel">
        <header class="master-panel-head">
            <div><h2>Daftar Komoditas</h2><p>{{ $commodities->total() }} komoditas tersimpan</p></div>
            <form class="master-search" method="GET">
                <input name="search" value="{{ request('search') }}" placeholder="Cari komoditas...">
                <select name="category"><option value="">Semua kategori</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected((string)request('category') === (string)$category->id)>{{ $category->name }}</option>@endforeach</select>
                <button>Cari</button>
            </form>
            <a class="master-primary" href="{{ route('commodities.create') }}">+ Tambah Komoditas</a>
        </header>
        <div class="table-scroll">
            <table class="master-table">
                <thead><tr><th>No</th><th>Nama Komoditas</th><th>Kategori</th><th>Satuan</th><th>Riwayat Harga</th><th>Status</th><th>Aksi</th></tr></thead>
                <tbody>
                @forelse($commodities as $commodity)
                    <tr>
                        <td>{{ $commodities->firstItem() + $loop->index }}</td>
                        <td><strong>{{ $commodity->name }}</strong><small>{{ $commodity->slug }}</small></td>
                        <td>{{ $commodity->category->name }}</td><td>{{ $commodity->unit }}</td>
                        <td>{{ number_format($commodity->prices_count) }} data</td>
                        <td><span class="master-badge {{ $commodity->is_active ? 'ready' : 'locked' }}">{{ $commodity->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                        <td class="master-actions">
                            <a class="price-action" href="{{ route('master-prices.index', ['commodity' => $commodity->id]) }}">Data Harga</a>
                            <a href="{{ route('commodities.edit', $commodity) }}">Ubah</a>
                            <form method="POST" action="{{ route('commodities.status', $commodity) }}">@csrf @method('PATCH')<button class="{{ $commodity->is_active ? 'danger' : 'activate' }}">{{ $commodity->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button></form>
                            <form method="POST" action="{{ route('commodities.destroy', $commodity) }}" data-confirm-form data-confirm-title="Hapus komoditas?" data-confirm-message="{{ $commodity->name }} beserta {{ number_format($commodity->prices_count) }} data harganya akan dihapus permanen.">@csrf @method('DELETE')<button class="delete-permanent">Hapus</button></form>
                        </td>
                    </tr>
                @empty
                    <tr><td class="master-empty" colspan="7">Komoditas tidak ditemukan.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="master-pagination">@include('partials.pagination', ['paginator' => $commodities])</div>
    </section>
</div>
@endsection
