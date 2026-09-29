<aside class="sidebar" id="sidebar">
    <div class="brand">
        <div class="brand-mark brand-logo-frame">
            <img src="{{ asset(config('branding.logo')) }}" alt="Logo Kabupaten Mojokerto">
        </div>
        <div class="brand-copy brand-tagline">
            <img src="{{ asset(config('branding.tagline')) }}" alt="Kabupaten Mojokerto — Full of Majapahit Greatness">
        </div>
        <button class="sidebar-collapse-button" id="sidebarCollapse" type="button" aria-label="Perkecil sidebar" title="Buka/tutup sidebar">
            <svg viewBox="0 0 24 24"><path d="m14 7-5 5 5 5"/></svg>
        </button>
    </div>

    <nav class="nav-menu" aria-label="Navigasi utama">
        <p class="nav-label">MENU UTAMA</p>
        <a class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
            <span class="nav-icon"><svg viewBox="0 0 24 24"><path d="M3 11.5 12 4l9 7.5"/><path d="M5.5 10v10h13V10M9.5 20v-6h5v6"/></svg></span><span>Dashboard</span>
        </a>
        <a class="nav-item {{ request()->routeIs('prices.*') ? 'active' : '' }}" href="{{ route('prices.index') }}">
            <span class="nav-icon"><svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="16" rx="1"/><path d="M3 9h18M8 4v16M14 9v11"/></svg></span><span>Harga Komoditas</span>
        </a>
        <a class="nav-item {{ request()->routeIs('trends.*') ? 'active' : '' }}" href="{{ route('trends.index') }}">
            <span class="nav-icon"><svg viewBox="0 0 24 24"><path d="M3 17c3-8 6 1 9-7s6 5 9-3"/></svg></span><span>Grafik &amp; Tren</span>
        </a>
        <a class="nav-item {{ request()->routeIs('infographics.*') ? 'active' : '' }}" href="{{ route('infographics.index') }}">
            <span class="nav-icon"><svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="8" cy="9" r="1.5"/><path d="m5 17 4-4 3 3 3-4 4 5"/></svg></span><span>Infografis</span>
        </a>
        <a class="nav-item {{ request()->routeIs('markets.*') ? 'active' : '' }}" href="{{ route('markets.index') }}">
            <span class="nav-icon"><svg viewBox="0 0 24 24"><path d="M4 8h16M17 5l3 3-3 3M20 16H4M7 13l-3 3 3 3"/></svg></span><span>Perbandingan Pasar</span>
        </a>
        <a class="nav-item {{ request()->routeIs('predictions.*') ? 'active' : '' }}" href="{{ route('predictions.index') }}">
            <span class="nav-icon"><svg viewBox="0 0 24 24"><path d="M4 19V5M4 19h16M7 15l4-4 3 2 6-6"/><path d="M16 7h4v4"/></svg></span><span>Prediksi Harga</span>
        </a>
        <a class="nav-item {{ request()->routeIs('reports.*') ? 'active' : '' }}" href="{{ route('reports.index') }}">
            <span class="nav-icon"><svg viewBox="0 0 24 24"><path d="M6 3h9l4 4v14H6z"/><path d="M14 3v5h5M9 12h7M9 16h7"/></svg></span><span>Laporan</span>
        </a>

        <p class="nav-label admin-label">PENGELOLAAN</p>
        @auth
        <div class="sidebar-admin-actions">
            <a class="sidebar-password-top" href="{{ route('password.edit') }}">
                <span class="nav-icon"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .4 1.9l-2.9 2.9a1.7 1.7 0 0 0-1.9-.4 1.7 1.7 0 0 0-1 1.6h-4A1.7 1.7 0 0 0 9 19.4a1.7 1.7 0 0 0-1.9.4l-2.9-2.9a1.7 1.7 0 0 0 .4-1.9A1.7 1.7 0 0 0 3 14v-4a1.7 1.7 0 0 0 1.6-1 1.7 1.7 0 0 0-.4-1.9l2.9-2.9A1.7 1.7 0 0 0 9 4.6 1.7 1.7 0 0 0 10 3h4a1.7 1.7 0 0 0 1 1.6 1.7 1.7 0 0 0 1.9-.4l2.9 2.9a1.7 1.7 0 0 0-.4 1.9 1.7 1.7 0 0 0 1.6 1v4a1.7 1.7 0 0 0-1.6 1Z"/></svg></span>
                <span>Ganti Sandi</span>
            </a>
            <form class="sidebar-logout-form" method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="sidebar-logout-top" type="submit">
                    <span class="nav-icon"><svg viewBox="0 0 24 24"><path d="M10 17l5-5-5-5M15 12H3M14 4h5a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-5"/></svg></span>
                    <span>Keluar</span>
                </button>
            </form>
        </div>
        <a class="nav-item {{ request()->routeIs('categories.*', 'commodities.*', 'master-prices.*', 'price-import.*') ? 'active' : '' }}" href="{{ route('commodities.index') }}">
            <span class="nav-icon"><svg viewBox="0 0 24 24"><path d="M4 5h16v14H4zM4 10h16M10 5v14"/><path d="M13.5 14h3M15 12.5v3"/></svg></span><span>Master Data</span>
        </a>
        <a class="nav-item {{ request()->routeIs('admin-infographics.*') ? 'active' : '' }}" href="{{ route('admin-infographics.index') }}">
            <span class="nav-icon"><svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M7 15v2M11 12v5M15 9v8M19 6v11"/></svg></span><span>Kelola Infografis</span>
        </a>
        <a class="nav-item {{ request()->routeIs('admin-predictions.*') ? 'active' : '' }}" href="{{ route('admin-predictions.index') }}">
            <span class="nav-icon"><svg viewBox="0 0 24 24"><path d="M4 19V5M4 19h16M7 15l4-4 3 2 6-6"/></svg></span><span>Kelola Prediksi</span>
        </a>
        <a class="nav-item {{ request()->routeIs('synchronization.*') ? 'active' : '' }}" href="{{ route('synchronization.index') }}">
            <span class="nav-icon"><svg viewBox="0 0 24 24"><path d="M20 7v5h-5M4 17v-5h5"/><path d="M6.1 8A7 7 0 0 1 19 10M17.9 16A7 7 0 0 1 5 14"/></svg></span><span>Sinkronisasi Data</span>
        </a>
        <a class="nav-item {{ request()->routeIs('quality.*') ? 'active' : '' }}" href="{{ route('quality.index') }}">
            <span class="nav-icon"><svg viewBox="0 0 24 24"><path d="m5 12 4 4L19 6"/></svg></span><span>Kualitas Data</span>
        </a>
        <a class="nav-item {{ request()->routeIs('activity.*') ? 'active' : '' }}" href="{{ route('activity.index') }}"><span class="nav-icon"><svg viewBox="0 0 24 24"><path d="M12 8v5l3 2"/><circle cx="12" cy="12" r="9"/></svg></span><span>Riwayat Aktivitas</span></a>
        <a class="nav-item {{ request()->routeIs('backups.*') ? 'active' : '' }}" href="{{ route('backups.index') }}"><span class="nav-icon"><svg viewBox="0 0 24 24"><path d="M5 4h12l2 2v14H5zM8 4v6h8V4M8 16h8"/></svg></span><span>Backup Database</span></a>
        <a class="nav-item {{ request()->routeIs('master-archive.*') ? 'active' : '' }}" href="{{ route('master-archive.index') }}"><span class="nav-icon"><svg viewBox="0 0 24 24"><path d="M4 7h16v13H4zM3 4h18v3H3zM9 11h6"/></svg></span><span>Arsip Master</span></a>
        @else
        <a class="nav-item {{ request()->routeIs('login') ? 'active' : '' }}" href="{{ route('login') }}">
            <span class="nav-icon"><svg viewBox="0 0 24 24"><path d="M10 5H5v14h5M14 8l4 4-4 4M8 12h10"/></svg></span><span>Login Admin</span>
        </a>
        @endauth
    </nav>

    <div class="sidebar-status">
        <span class="status-dot"></span>
        <div><span>DATA TERAKHIR</span><strong>{{ $latestDataDate?->translatedFormat('d M Y') ?? 'Belum tersedia' }}</strong></div>
    </div>

    @auth
    <div class="profile admin-profile">
        <div class="avatar">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 2)) }}</div>
        <div><a href="{{ route('profile.edit') }}"><strong>{{ auth()->user()->name }}</strong><span>{{ auth()->user()->username }} · Profil</span></a></div>
    </div>
    @else
    <div class="profile guest-profile">
        <div class="avatar">GU</div><div><strong>Pengunjung</strong><span>Akses informasi publik</span></div>
        <a href="{{ route('login') }}" aria-label="Login admin" title="Login admin">→</a>
    </div>
    @endauth
</aside>
