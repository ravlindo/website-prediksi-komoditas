<header class="topbar portal-topbar">
    <button class="menu-toggle" id="menuToggle" aria-label="Buka menu">&#9776;</button>

    <div class="portal-identity">
        <span class="portal-emblem portal-logo-frame">
            <img src="{{ asset(config('branding.logo')) }}" alt="Logo Kabupaten Mojokerto">
        </span>
        <div class="portal-brand-copy">
            <small>Portal informasi harga resmi</small>
            <img src="{{ asset(config('branding.tagline')) }}" alt="Kabupaten Mojokerto — Full of Majapahit Greatness">
            <strong class="visually-hidden">Harga Kabupaten Mojokerto</strong>
        </div>
    </div>

    <nav class="portal-nav" aria-label="Navigasi cepat">
        <a class="{{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">Ringkasan</a>
        <a class="{{ request()->routeIs('prices.*') ? 'active' : '' }}" href="{{ route('prices.index') }}">Data Harga</a>
        <a class="{{ request()->routeIs('trends.*') ? 'active' : '' }}" href="{{ route('trends.index') }}">Visualisasi</a>
        <a class="{{ request()->routeIs('reports.*') ? 'active' : '' }}" href="{{ route('reports.index') }}">Publikasi</a>
    </nav>

    <div class="portal-meta">
        <span class="portal-live"><i></i> Data terhubung</span>
        <span class="date-now">{{ $currentDate->translatedFormat('l, d F Y') }}</span>
    </div>
</header>
