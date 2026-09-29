<nav class="prediction-tabs" aria-label="Bagian prediksi harga">
    <a href="{{ route('predictions.index') }}" class="{{ request()->routeIs('predictions.index') ? 'active' : '' }}">
        <span class="prediction-tab-icon">↗</span>
        <span><strong>Prediksi Harga</strong><small>Proyeksi 1, 3, 7, 14, dan 30 hari</small></span>
    </a>
    <a href="{{ route('predictions.market-evaluation') }}" class="{{ request()->routeIs('predictions.market-evaluation') ? 'active' : '' }}">
        <span class="prediction-tab-icon">≋</span>
        <span><strong>Perbandingan Harga 2 Pasar Utama</strong><small>Mojosari dan Kedungmaling</small></span>
    </a>
</nav>
