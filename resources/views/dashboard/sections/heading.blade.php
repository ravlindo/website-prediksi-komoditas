<section class="dashboard-hero">
    <div class="hero-pattern" aria-hidden="true"></div>
    <div class="hero-orbit orbit-one" aria-hidden="true"></div>
    <div class="hero-orbit orbit-two" aria-hidden="true"></div>
    <div class="hero-gate" aria-hidden="true">
        <i class="gate-left"></i><i class="gate-right"></i>
    </div>

    <div class="hero-content">
        <div class="hero-kicker"><span></span> PUSAT INFORMASI HARGA DAERAH <b>2026</b></div>
        <h1>Data yang jelas.<br><strong>Keputusan lebih tepat.</strong></h1>
        <p class="hero-description">Informasi harga bahan pokok dari pasar Kabupaten Mojokerto, disajikan ringkas untuk membantu pengambilan keputusan.</p>

        <div class="hero-actions">
            <a class="hero-button primary" href="{{ route('prices.index') }}">
                Lihat harga komoditas
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
            </a>
            <a class="hero-button secondary" href="{{ route('price-import.create') }}">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v12m0 0 4-4m-4 4-4-4M5 19h14"/></svg>
                Import data
            </a>
        </div>

        <div class="hero-facts" aria-label="Ringkasan cakupan data">
            <div><strong>{{ $summary['commodities'] }}</strong><span>Komoditas</span></div>
            <div><strong>{{ $summary['categories'] }}</strong><span>Kategori</span></div>
            <div><strong>{{ $summary['markets'] }}</strong><span>Pasar aktif</span></div>
            <div class="hero-source"><svg viewBox="0 0 24 24"><path d="m7 12 3 3 7-7"/><circle cx="12" cy="12" r="9"/></svg><span>Sumber terverifikasi<br><b>Siskaperbapo Jatim</b></span></div>
        </div>
    </div>

    <div class="hero-data-card">
        <div class="data-card-head"><span>STATUS DATA</span><em>LIVE</em></div>
        <small>Pembaruan terakhir</small>
        <strong>{{ \Carbon\Carbon::parse($latestDate)->translatedFormat('d F Y') }}</strong>
        <p><i></i> Data siap ditampilkan</p>
        <a href="{{ request()->fullUrl() }}">Perbarui tampilan <b>&#8635;</b></a>
    </div>
</section>
