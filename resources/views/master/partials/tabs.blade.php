<nav class="master-tabs" aria-label="Jenis master data">
    <a class="{{ request()->routeIs('categories.*') ? 'active' : '' }}" href="{{ route('categories.index') }}">Kategori</a>
    <a class="{{ request()->routeIs('commodities.*') ? 'active' : '' }}" href="{{ route('commodities.index') }}">Komoditas</a>
    <a class="{{ (request()->routeIs('master-prices.*', 'price-import.*') && !request()->routeIs('master-prices.trash')) ? 'active' : '' }}" href="{{ route('master-prices.index') }}">Data Harga</a>
    <a class="trash-tab {{ request()->routeIs('master-prices.trash') ? 'active' : '' }}" href="{{ route('master-prices.trash') }}">Tempat Sampah</a>
</nav>
