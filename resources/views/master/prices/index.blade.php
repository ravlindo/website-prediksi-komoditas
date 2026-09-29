@extends('layouts.app')
@section('title', 'Master Data Harga')
@section('content')
<div class="page feature-page master-page">
    @include('features.page-heading', ['eyebrow'=>'PENGELOLAAN DATA', 'title'=>'Master Data', 'description'=>'Tambah, perbarui, dan hapus harga harian untuk setiap komoditas dan pasar.'])
    @include('master.partials.tabs')
    @include('master.partials.alerts')
    <section class="panel master-panel">
        <header class="master-panel-head price-master-head">
            <div class="price-master-identity">
                <span class="price-master-identity-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="M4 7h16M4 12h16M4 17h16M8 4v16"/></svg>
                </span>
                <div>
                    <small>DATABASE HARGA</small>
                    <h2>Data Harga Harian</h2>
                    <p><strong>{{ number_format($prices->total()) }}</strong> data ditemukan sesuai filter</p>
                </div>
            </div>

            <div class="price-master-actions">
                <a class="archive-button" href="{{ route('master-prices.trash') }}"><span aria-hidden="true">&#8634;</span> Tempat Sampah</a>
                <a class="template-button" href="{{ route('price-import.template') }}" title="Unduh template untuk semua pasar"><span aria-hidden="true">&#8681;</span> Template</a>
                <a class="import-button" href="{{ route('price-import.create') }}"><span aria-hidden="true">&#8679;</span> Import Excel</a>
                <a class="master-primary" href="{{ route('master-prices.create', ['commodity' => request('commodity')]) }}"><span aria-hidden="true">+</span> Tambah Manual</a>
            </div>

            <form class="master-search price-master-filter" method="GET">
                <label><span>Komoditas</span><select name="commodity"><option value="">Semua komoditas</option>@foreach($commodities as $commodity)<option value="{{ $commodity->id }}" @selected((string)request('commodity') === (string)$commodity->id)>{{ $commodity->name }}</option>@endforeach</select></label>
                <label><span>Pasar</span><select name="market"><option value="">Semua pasar</option>@foreach($markets as $market)<option value="{{ $market->id }}" @selected((string)request('market') === (string)$market->id)>{{ $market->name }}</option>@endforeach</select></label>
                <label><span>Status perubahan</span><select name="change_status"><option value="">Semua status</option><option value="original" @selected(request('change_status') === 'original')>Masih asli</option><option value="edited" @selected(request('change_status') === 'edited')>Sudah diubah</option></select></label>
                <label><span>Tanggal harga</span><input type="date" name="date" value="{{ request('date') }}"></label>
                <button type="submit">Terapkan Filter</button>
                @if(request()->hasAny(['commodity', 'market', 'change_status', 'date']))<a href="{{ route('master-prices.index') }}">Reset</a>@endif
            </form>
        </header>
        <div class="bulk-toolbar" id="bulkToolbar">
            <div><span class="bulk-icon">✓</span><p><strong id="selectedCount">0 data dipilih</strong><small>Centang data yang ingin dihapus sekaligus</small></p></div>
            <form id="bulkSelectedForm" method="POST" action="{{ route('master-prices.bulk-destroy') }}" data-confirm-form data-confirm-title="Pindahkan data terpilih?" data-confirm-message="Semua data yang dicentang akan dipindahkan ke tempat sampah dan masih dapat dipulihkan.">@csrf @method('DELETE')<input type="hidden" name="scope" value="selected"><button class="bulk-delete-button" id="deleteSelectedButton" disabled>Pindahkan yang Dipilih</button></form>
            <form method="POST" action="{{ route('master-prices.bulk-destroy') }}" data-confirm-form data-confirm-title="Arsipkan semua hasil filter?" data-confirm-message="Seluruh {{ number_format($prices->total()) }} data sesuai filter akan dipindahkan ke tempat sampah dan masih dapat dipulihkan.">@csrf @method('DELETE')<input type="hidden" name="scope" value="filtered">@if(request('commodity'))<input type="hidden" name="commodity" value="{{ request('commodity') }}">@endif @if(request('market'))<input type="hidden" name="market" value="{{ request('market') }}">@endif @if(request('date'))<input type="hidden" name="date" value="{{ request('date') }}">@endif @if(request('change_status'))<input type="hidden" name="change_status" value="{{ request('change_status') }}">@endif<button class="bulk-filter-delete" @disabled(!request('commodity') && !request('market') && !request('date') && !request('change_status'))>Arsipkan Hasil Filter ({{ number_format($prices->total()) }})</button></form>
        </div>
        <div class="table-scroll"><table class="master-table price-master-table">
            <thead><tr><th class="check-column"><input id="selectAllPrices" type="checkbox" aria-label="Pilih semua data pada halaman ini"></th><th>No</th><th>Tanggal</th><th>Komoditas</th><th>Pasar</th><th>Harga</th><th>Sumber</th><th>Status Perubahan</th><th>Aksi</th></tr></thead><tbody>
            @forelse($prices as $price)
                <tr>
                    <td class="check-column"><input class="price-row-check" type="checkbox" name="ids[]" value="{{ $price->id }}" form="bulkSelectedForm" aria-label="Pilih {{ $price->commodity->name }} tanggal {{ $price->price_date->format('d-m-Y') }}"></td>
                    <td>{{ $prices->firstItem() + $loop->index }}</td>
                    <td><strong>{{ $price->price_date->translatedFormat('d M Y') }}</strong></td>
                    <td><strong>{{ $price->commodity->name }}</strong><small>{{ $price->commodity->category->name }} · {{ $price->commodity->unit }}</small></td>
                    <td>{{ $price->market->name }}</td>
                    <td><b class="price-amount">Rp {{ number_format($price->price, 0, ',', '.') }}</b></td>
                    <td>{{ $price->source }}</td>
                    <td class="change-status-cell">
                        @if($price->latestChange)
                            <span class="change-badge edited">Diubah</span>
                            <strong>{{ $price->latestChange->user_name }}</strong>
                            <small>{{ $price->latestChange->created_at->translatedFormat('d M Y, H:i') }}</small>
                            @if($price->latestChange->old_price !== $price->latestChange->new_price)
                                <em>Rp {{ number_format($price->latestChange->old_price, 0, ',', '.') }} → Rp {{ number_format($price->latestChange->new_price, 0, ',', '.') }}</em>
                            @endif
                        @else
                            <span class="change-badge original">Asli</span><small>Belum pernah diubah</small>
                        @endif
                    </td>
                    <td class="master-actions"><a href="{{ route('master-prices.edit', $price) }}">Ubah</a><form method="POST" action="{{ route('master-prices.destroy', $price) }}" data-confirm-form data-confirm-title="Pindahkan ke tempat sampah?" data-confirm-message="Harga {{ $price->commodity->name }} di {{ $price->market->name }} tanggal {{ $price->price_date->translatedFormat('d F Y') }} masih dapat dipulihkan dari tempat sampah.">@csrf @method('DELETE')<button class="delete-permanent">Hapus</button></form></td>
                </tr>
            @empty<tr><td class="master-empty" colspan="9">Data harga tidak ditemukan.</td></tr>@endforelse
            </tbody>
        </table></div>
        <div class="master-pagination">@include('partials.pagination', ['paginator' => $prices])</div>
    </section>
</div>
@endsection
