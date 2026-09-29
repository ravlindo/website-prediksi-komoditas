<form method="GET" class="quality-filter panel">
    <div class="quality-filter-title"><strong>Filter Pemeriksaan</strong><span>Batasi data yang ingin diperiksa</span></div>
    <label><span>Mulai</span><input type="date" name="start_date" value="{{ $filters['start_date'] }}"></label>
    <label><span>Sampai</span><input type="date" name="end_date" value="{{ $filters['end_date'] }}"></label>
    <label><span>Pasar</span><select name="market"><option value="">Semua Pasar</option>@foreach($markets as $market)<option value="{{ $market->id }}" @selected($filters['market_id'] == $market->id)>{{ $market->name }}</option>@endforeach</select></label>
    <label><span>Kategori</span><select name="category"><option value="">Semua Kategori</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected($filters['category_id'] == $category->id)>{{ $category->name }}</option>@endforeach</select></label>
    <label><span>Komoditas</span><select name="commodity"><option value="">Semua Komoditas</option>@foreach($commodities as $commodity)<option value="{{ $commodity->id }}" @selected($filters['commodity_id'] == $commodity->id)>{{ $commodity->name }}</option>@endforeach</select></label>
    <label><span>Status Harga</span><select name="status"><option value="">Semua Status</option><option value="zero" @selected($filters['status'] === 'zero')>Harga Nol</option><option value="positive" @selected($filters['status'] === 'positive')>Harga Positif</option></select></label>
    <div class="quality-filter-actions"><button type="submit">Terapkan</button><a href="{{ route('quality.index') }}">Reset</a></div>
</form>
