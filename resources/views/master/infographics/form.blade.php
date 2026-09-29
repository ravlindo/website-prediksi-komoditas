@extends('layouts.app')

@section('title', $infographic->exists ? 'Ubah Infografis' : 'Tambah Infografis')

@section('content')
<div class="page feature-page master-page infographic-form-page">
    @include('features.page-heading', ['eyebrow'=>'KONTEN INFOGRAFIS', 'title'=>$infographic->exists ? 'Ubah Infografis' : 'Tambah Infografis', 'description'=>'Gunakan visual vertikal berkualitas tinggi agar informasi tetap jelas di layar besar maupun ponsel.'])
    @include('master.partials.alerts')

    <form class="infographic-editor" method="POST" enctype="multipart/form-data" action="{{ $infographic->exists ? route('admin-infographics.update', $infographic) : route('admin-infographics.store') }}">
        @csrf @if($infographic->exists) @method('PUT') @endif
        <section class="panel infographic-fields">
            <div class="editor-section-title"><span>01</span><div><h2>Informasi utama</h2><p>Judul dan sumber data resmi yang tampil di galeri.</p></div></div>
            <div class="master-form-grid">
                <label><span>Judul infografis <b>*</b></span><input name="title" value="{{ old('title', $infographic->title) }}" maxlength="180" required placeholder="Contoh: Perkembangan Harga Beras 2026"></label>
                <label><span>Sumber data diperoleh <b>*</b></span><select name="agency" required><option value="">Pilih sumber data</option>@foreach(config('infographics.data_sources') as $source)<option value="{{ $source }}" @selected(old('agency', $infographic->agency) === $source)>{{ $source }}</option>@endforeach</select></label>
                <label><span>Tahun publikasi</span><input type="number" name="publication_year" value="{{ old('publication_year', $infographic->publication_year ?: now()->year) }}" min="2000" max="{{ now()->year + 1 }}"></label>
                <label><span>Urutan tampilan</span><input type="number" name="sort_order" value="{{ old('sort_order', $infographic->sort_order ?? 0) }}" min="0" max="9999"><small class="field-help">Angka terkecil tampil lebih dahulu.</small></label>
            </div>
            <label class="editor-full"><span>Deskripsi singkat</span><textarea name="description" maxlength="1000" rows="4" placeholder="Ringkasan isi infografis...">{{ old('description', $infographic->description) }}</textarea></label>
        </section>

        <aside class="panel infographic-media-card">
            <div class="editor-section-title"><span>02</span><div><h2>Visual publikasi</h2><p>PNG, JPG, atau WebP · maksimal 8 MB.</p></div></div>
            <label class="infographic-upload" data-infographic-upload>
                <input type="file" name="image" accept="image/png,image/jpeg,image/webp" {{ $infographic->exists ? '' : 'required' }} data-infographic-input>
                <div class="upload-preview {{ $infographic->exists ? 'has-image' : '' }}" data-infographic-preview>
                    @if($infographic->exists)<img src="{{ Storage::url($infographic->image_path) }}" alt="Pratinjau {{ $infographic->title }}">@endif
                    <span class="upload-symbol"><svg viewBox="0 0 24 24"><path d="M12 16V4M7 9l5-5 5 5M5 14v5h14v-5"/></svg></span>
                    <strong>{{ $infographic->exists ? 'Ganti gambar' : 'Pilih gambar infografis' }}</strong><small>Rasio vertikal 4:5 atau 3:4 direkomendasikan</small>
                </div>
            </label>
            <label class="publish-switch"><input type="checkbox" name="is_published" value="1" @checked(old('is_published', $infographic->exists ? $infographic->is_published : true))><span></span><div><strong>Publikasikan sekarang</strong><small>Infografis langsung terlihat oleh pengunjung.</small></div></label>
            <div class="master-form-actions"><a href="{{ route('admin-infographics.index') }}">Batal</a><button class="master-primary" type="submit">{{ $infographic->exists ? 'Simpan Perubahan' : 'Terbitkan Infografis' }}</button></div>
        </aside>
    </form>
</div>
@endsection
