@extends('layouts.app')
@section('title', 'Tempat Sampah Data Harga')
@section('content')
<div class="page feature-page master-page">
    @include('features.page-heading', ['eyebrow'=>'ARSIP PEMULIHAN', 'title'=>'Tempat Sampah Data Harga', 'description'=>'Data yang dihapus tetap tersimpan di MySQL dan dapat dipulihkan sebelum dihapus permanen.'])
    @include('master.partials.tabs')
    @include('master.partials.alerts')

    <section class="archive-summary">
        <div class="archive-summary-icon"><svg viewBox="0 0 24 24"><path d="M4 7h16M9 11v6M15 11v6M6 7l1 14h10l1-14M9 7V4h6v3"/></svg></div>
        <div><span>TOTAL DATA DALAM ARSIP</span><strong>{{ number_format($trashCount) }} data</strong><p>Penghapusan biasa tidak lagi langsung menghilangkan data dari database.</p></div>
        <a href="{{ route('master-prices.index') }}">Kembali ke Data Harga</a>
    </section>

    <section class="trash-purge-zone" aria-labelledby="trash-purge-title">
        <div class="trash-purge-symbol" aria-hidden="true">
            <svg viewBox="0 0 24 24"><path d="M4 7h16M9 11v6M15 11v6M6 7l1 14h10l1-14M9 7V4h6v3"/></svg>
        </div>
        <div class="trash-purge-copy">
            <span>ZONA PENGHAPUSAN PERMANEN</span>
            <strong id="trash-purge-title">Kosongkan seluruh tempat sampah</strong>
            <p>Semua {{ number_format($trashCount) }} data arsip akan dihapus dari MySQL dan tidak dapat dipulihkan kembali.</p>
        </div>
        <form method="POST" action="{{ route('master-prices.purge-trash') }}" data-confirm-form data-confirm-title="Kosongkan seluruh tempat sampah?" data-confirm-message="Seluruh {{ number_format($trashCount) }} data dalam tempat sampah akan dihapus permanen dari MySQL. Tindakan ini tidak dapat dibatalkan.">
            @csrf
            @method('DELETE')
            <button type="submit" class="trash-purge-button" @disabled($trashCount === 0)>
                <span>Hapus Semua Permanen</span>
                <b>{{ number_format($trashCount) }}</b>
            </button>
        </form>
    </section>

    <section class="panel master-panel">
        <header class="master-panel-head price-master-head">
            <div><h2>Riwayat Data Terhapus</h2><p>{{ number_format($prices->total()) }} data sesuai filter</p></div>
            <form class="master-search" method="GET">
                <select name="commodity"><option value="">Semua komoditas</option>@foreach($commodities as $commodity)<option value="{{ $commodity->id }}" @selected((string)request('commodity') === (string)$commodity->id)>{{ $commodity->name }}</option>@endforeach</select>
                <select name="market"><option value="">Semua pasar</option>@foreach($markets as $market)<option value="{{ $market->id }}" @selected((string)request('market') === (string)$market->id)>{{ $market->name }}</option>@endforeach</select>
                <input type="date" name="date" value="{{ request('date') }}"><button>Filter</button>
            </form>
        </header>
        <div class="table-scroll"><table class="master-table archive-table">
            <thead><tr><th>No</th><th>Data Harga</th><th>Pasar</th><th>Harga</th><th>Dihapus</th><th>Penghapus</th><th>Aksi</th></tr></thead>
            <tbody>@forelse($prices as $price)
                <tr>
                    <td>{{ $prices->firstItem() + $loop->index }}</td>
                    <td><strong>{{ $price->commodity->name }}</strong><small>{{ $price->price_date->translatedFormat('d F Y') }} &bull; {{ $price->commodity->unit }}</small></td>
                    <td>{{ $price->market->name }}</td><td><b class="price-amount">Rp {{ number_format($price->price, 0, ',', '.') }}</b></td>
                    <td><strong>{{ $price->deleted_at->translatedFormat('d M Y, H:i') }}</strong><small>{{ $price->deleted_at->diffForHumans() }}</small></td>
                    <td><strong>{{ $price->deleted_by ?? 'Sistem' }}</strong><small>IP {{ $price->deleted_ip ?? '-' }}</small></td>
                    <td class="archive-actions">
                        <form method="POST" action="{{ route('master-prices.restore', $price->id) }}">@csrf @method('PATCH')<button class="restore-button">Pulihkan</button></form>
                        <form method="POST" action="{{ route('master-prices.force-destroy', $price->id) }}" data-confirm-form data-confirm-title="Hapus permanen?" data-confirm-message="Data {{ $price->commodity->name }} tanggal {{ $price->price_date->translatedFormat('d F Y') }} akan dihapus permanen dari MySQL dan tidak dapat dipulihkan.">@csrf @method('DELETE')<button class="force-delete-button">Hapus Permanen</button></form>
                    </td>
                </tr>
            @empty<tr><td class="master-empty" colspan="7">Tempat sampah masih kosong.</td></tr>@endforelse</tbody>
        </table></div>
        <div class="master-pagination">@include('partials.pagination', ['paginator' => $prices])</div>
    </section>
</div>
@endsection
