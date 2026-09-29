<article class="panel movement-panel">
    <div class="panel-head"><div><h2>Pergerakan Tertinggi</h2><p>Dibandingkan harga sebelumnya</p></div><a href="{{ route('prices.index', ['date'=>$selectedDate, 'market'=>$selectedMarket, 'category'=>$selectedCategory]) }}">Lihat semua</a></div>
    <div class="movement-tabs"><button class="active" data-movement="up">Harga Naik ({{ $upMovements->count() }})</button><button data-movement="down">Harga Turun ({{ $downMovements->count() }})</button></div>
    @foreach(['up'=>$upMovements, 'down'=>$downMovements] as $direction=>$items)
        <div class="movement-list" data-movement-list="{{ $direction }}" @if($direction === 'down') hidden @endif>
            @forelse($items as $item)
                <div><span class="rank">{{ $loop->iteration }}</span><span class="food-icon {{ $direction === 'up' ? 'red-bg' : 'green-bg' }}">{{ $direction === 'up' ? '↑' : '↓' }}</span><p><strong>{{ $item['name'] }}</strong><small>{{ $item['current'] }} / {{ $item['unit'] }}</small></p><em class="{{ $direction }}">{{ $item['percent'] }}</em></div>
            @empty
                <p class="movement-empty">Tidak ada harga {{ $direction === 'up' ? 'naik' : 'turun' }}.</p>
            @endforelse
        </div>
    @endforeach
</article>
