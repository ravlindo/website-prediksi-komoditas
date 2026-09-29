<form class="consumer-filter" method="GET">
    <label><b>Tanggal:</b><input type="date" name="date" value="{{ $selectedDate }}"></label>
    <label><b>Area:</b><select disabled><option>Kabupaten Mojokerto</option></select></label>
    <label><b>Pasar:</b><select name="market"><option value="">- Semua Pasar -</option>@foreach($markets as $market)<option value="{{ $market->id }}" @selected($selectedMarket == $market->id)>{{ $market->name }}</option>@endforeach</select></label>
    <label><b>Kategori:</b><select name="category"><option value="">Semua Kategori</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected($selectedCategory == $category->id)>{{ $category->name }}</option>@endforeach</select></label>
    <button type="submit">Tampilkan</button>
    <a href="{{ route('prices.index') }}">Reset</a>
</form>
