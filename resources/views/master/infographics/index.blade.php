@extends('layouts.app')

@section('title', 'Kelola Infografis')

@section('content')
<div class="page feature-page master-page infographic-admin-page">
    @include('features.page-heading', ['eyebrow'=>'MANAJEMEN KONTEN', 'title'=>'Kelola Infografis', 'description'=>'Atur koleksi visual, urutan tampilan, dan status publikasi infografis.'])
    @include('master.partials.alerts')

    <section class="infographic-admin-summary">
        <div><span>Total koleksi</span><strong>{{ number_format($infographics->total()) }}</strong></div>
        <div><span>Status</span><strong>Konten visual</strong></div>
        <a href="{{ route('infographics.index') }}" target="_blank">Lihat galeri publik <b>↗</b></a>
    </section>

    <section class="panel master-panel">
        <header class="master-panel-head">
            <div><h2>Daftar Infografis</h2><p>Gambar aktif langsung tampil pada halaman publik</p></div>
            <form class="master-search" method="GET">
                <input name="search" value="{{ request('search') }}" placeholder="Cari infografis...">
                <select name="status"><option value="">Semua status</option><option value="published" @selected(request('status')==='published')>Dipublikasikan</option><option value="draft" @selected(request('status')==='draft')>Disembunyikan</option></select>
                <button>Cari</button>
            </form>
            <a class="master-primary" href="{{ route('admin-infographics.create') }}">+ Tambah Infografis</a>
        </header>
        <div class="table-scroll">
            <table class="master-table infographic-admin-table">
                <thead><tr><th>Urutan</th><th>Visual</th><th>Informasi</th><th>Status</th><th>Terakhir diubah</th><th>Aksi</th></tr></thead>
                <tbody>
                @forelse($infographics as $item)
                    <tr>
                        <td><strong class="sort-number">{{ str_pad($item->sort_order, 2, '0', STR_PAD_LEFT) }}</strong></td>
                        <td><img src="{{ Storage::url($item->image_path) }}" alt="" class="admin-infographic-thumb"></td>
                        <td><strong>{{ $item->title }}</strong><small>{{ $item->agency ?: 'Sumber belum ditentukan' }} · {{ $item->publication_year ?: 'Tanpa tahun' }}</small></td>
                        <td><span class="publication-status {{ $item->is_published ? 'published' : 'draft' }}"><i></i>{{ $item->is_published ? 'Tayang' : 'Disembunyikan' }}</span></td>
                        <td><strong class="editor-name">{{ $item->editor?->name ?: 'Administrator' }}</strong><small>{{ $item->updated_at->translatedFormat('d M Y, H:i') }}</small></td>
                        <td class="master-actions">
                            <a href="{{ route('admin-infographics.edit', $item) }}">Ubah</a>
                            <form method="POST" action="{{ route('admin-infographics.status', $item) }}">@csrf @method('PATCH')<button class="{{ $item->is_published ? '' : 'activate' }}">{{ $item->is_published ? 'Sembunyikan' : 'Tayangkan' }}</button></form>
                            <form method="POST" action="{{ route('admin-infographics.destroy', $item) }}" data-confirm-form data-confirm-title="Hapus infografis?" data-confirm-message="{{ $item->title }} dan file gambarnya akan dihapus permanen.">@csrf @method('DELETE')<button class="danger">Hapus</button></form>
                        </td>
                    </tr>
                @empty
                    <tr><td class="master-empty" colspan="6">Belum ada infografis. Klik “Tambah Infografis” untuk memulai.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="master-pagination">@include('partials.pagination', ['paginator' => $infographics])</div>
    </section>
</div>
@endsection
