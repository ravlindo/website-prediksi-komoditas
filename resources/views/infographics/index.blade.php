@extends('layouts.app')

@section('title', 'Infografis Harga Mojokerto')

@section('content')
<div class="page infographic-page">
    <header class="infographic-hero">
        <div>
            <p class="infographic-kicker"><span></span> VISUALISASI INFORMASI</p>
            <h1>Infografis Harga<br><em>Kabupaten Mojokerto</em></h1>
            <p>Informasi harga bahan pokok yang disajikan secara visual, ringkas, dan mudah dipahami masyarakat.</p>
        </div>
        <div class="infographic-hero-stat"><strong>{{ number_format($infographics->total()) }}</strong><span>visual tersedia</span></div>
    </header>

    <section class="infographic-toolbar" aria-label="Filter infografis">
        <div><span>KOLEKSI DATA</span><h2>Jelajahi visual terbaru</h2></div>
        <form method="GET">
            <label class="infographic-search"><svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m16 16 4 4"/></svg><input name="search" value="{{ request('search') }}" placeholder="Cari judul atau instansi"></label>
            <select name="year" aria-label="Tahun terbit"><option value="">Semua tahun</option>@foreach($years as $year)<option value="{{ $year }}" @selected((string) request('year') === (string) $year)>{{ $year }}</option>@endforeach</select>
            <button type="submit">Tampilkan</button>
        </form>
    </section>

    <section class="infographic-grid">
        @forelse($infographics as $item)
            <article class="infographic-card" data-infographic-card
                data-image="{{ Storage::url($item->image_path) }}"
                data-title="{{ $item->title }}"
                data-source="{{ $item->agency }}"
                data-year="{{ $item->publication_year ?: 'Terbaru' }}"
                data-description="{{ $item->description }}">
                <button class="infographic-cover" type="button" data-infographic-open aria-label="Buka infografis {{ $item->title }}">
                    <img src="{{ Storage::url($item->image_path) }}" alt="{{ $item->title }}" loading="lazy">
                    <span class="infographic-year">{{ $item->publication_year ?: 'Terbaru' }}</span>
                    <span class="infographic-open"><svg viewBox="0 0 24 24"><path d="M14 5h5v5M19 5l-8 8"/><path d="M18 13v6H5V6h6"/></svg></span>
                    <span class="infographic-cover-caption"><b>Lihat detail</b><small>Klik untuk membuka visual</small></span>
                </button>
                <div class="infographic-card-body">
                    <p>Sumber data diperoleh · {{ $item->agency }}</p>
                    <h2>{{ $item->title }}</h2>
                    @if($item->description)<span>{{ Str::limit($item->description, 115) }}</span>@endif
                    <footer>
                        <button type="button" data-infographic-open>Lihat infografis <b>&rarr;</b></button>
                    </footer>
                </div>
            </article>
        @empty
            <div class="infographic-empty"><span>▧</span><h2>Belum ada infografis</h2><p>Visual yang sudah dipublikasikan akan tampil di sini.</p>@auth<a href="{{ route('admin-infographics.create') }}">Tambahkan infografis pertama</a>@endauth</div>
        @endforelse
    </section>

    <div class="infographic-lightbox" data-infographic-lightbox aria-hidden="true">
        <div class="infographic-lightbox-backdrop" data-infographic-close></div>
        <section class="infographic-lightbox-dialog" role="dialog" aria-modal="true" aria-labelledby="infographicModalTitle">
            <header class="lightbox-topbar">
                <div class="lightbox-brand"><span>MH</span><div><small>VISUALISASI DATA</small><strong>Mojokerto Harga</strong></div></div>
                <div class="lightbox-tools">
                    <button type="button" data-infographic-zoom-out aria-label="Perkecil gambar" title="Perkecil">−</button>
                    <output data-infographic-zoom-label>100%</output>
                    <button type="button" data-infographic-zoom-in aria-label="Perbesar gambar" title="Perbesar">+</button>
                    <a data-infographic-download download aria-label="Unduh gambar" title="Unduh gambar"><svg viewBox="0 0 24 24"><path d="M12 4v11M8 11l4 4 4-4M5 20h14"/></svg></a>
                    <button class="lightbox-close" type="button" data-infographic-close aria-label="Tutup infografis"><svg viewBox="0 0 24 24"><path d="m6 6 12 12M18 6 6 18"/></svg></button>
                </div>
            </header>
            <div class="lightbox-content">
                <div class="lightbox-stage" data-infographic-stage>
                    <div class="lightbox-image-wrap" data-infographic-image-wrap><img data-infographic-image alt=""></div>
                    <button class="lightbox-nav previous" type="button" data-infographic-previous aria-label="Infografis sebelumnya"><svg viewBox="0 0 24 24"><path d="m15 5-7 7 7 7"/></svg></button>
                    <button class="lightbox-nav next" type="button" data-infographic-next aria-label="Infografis berikutnya"><svg viewBox="0 0 24 24"><path d="m9 5 7 7-7 7"/></svg></button>
                    <span class="lightbox-hint">Gunakan roda mouse untuk zoom · seret gambar untuk melihat detail</span>
                </div>
                <aside class="lightbox-details">
                    <div class="lightbox-number"><span data-infographic-position>01 / 01</span><i></i></div>
                    <p class="lightbox-label">INFOGRAFIS TERPILIH</p>
                    <h2 id="infographicModalTitle" data-infographic-title></h2>
                    <p class="lightbox-description" data-infographic-description></p>
                    <dl><div><dt>Sumber data diperoleh</dt><dd data-infographic-source></dd></div><div><dt>Tahun publikasi</dt><dd data-infographic-year></dd></div></dl>
                    <div class="lightbox-accent"><span></span><p>Data ringkas untuk keputusan yang lebih tepat.</p></div>
                </aside>
            </div>
        </section>
    </div>

    <div class="infographic-pagination">@include('partials.pagination', ['paginator' => $infographics])</div>
</div>
@endsection
