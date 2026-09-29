<form class="filter-card" method="GET">
    <div class="filter-title"><span>&#8767;</span><div><strong>Filter data</strong><small>Sesuaikan informasi yang ingin ditampilkan</small></div></div>
    <label><span>Tanggal harga</span><input type="date" name="date" value="{{ $selectedDate ?? '2026-07-23' }}"></label>
    <label><span>Pasar</span><select name="market" id="marketFilter"><option value="">Semua Pasar</option>@foreach(($markets ?? []) as $market)<option value="{{ $market->id }}" @selected(($selectedMarket ?? null) == $market->id)>{{ $market->name }}</option>@endforeach</select></label>
    <label><span>Kategori</span><select name="category" id="commodityFilter"><option value="">Semua Kategori</option>@foreach(($categories ?? []) as $category)<option value="{{ $category->id }}" @selected(($selectedCategory ?? null) == $category->id)>{{ $category->name }}</option>@endforeach</select></label>
    <button type="submit" class="filter-button">Terapkan Filter</button>
</form>
