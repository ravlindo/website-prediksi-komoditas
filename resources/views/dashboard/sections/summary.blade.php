@php
    $summaries = [
        ['tone'=>'blue','icon'=>$summary['commodities'],'label'=>'Komoditas Dipantau','value'=>$summary['commodities'],'note'=>$summary['categories'].' kategori bahan pokok'],
        ['tone'=>'red','icon'=>'&#8599;','label'=>'Harga Naik','value'=>$summary['up'],'note'=>'Dibandingkan kemarin'],
        ['tone'=>'green','icon'=>'&#8600;','label'=>'Harga Turun','value'=>$summary['down'],'note'=>'Dibandingkan kemarin'],
        ['tone'=>'gray','icon'=>'&#8594;','label'=>'Harga Stabil','value'=>$summary['stable'],'note'=>'Tidak ada perubahan'],
        ['tone'=>'orange','icon'=>'!','label'=>'Belum Dibandingkan','value'=>$summary['unavailable'],'note'=>'Harga 0 atau pasangan data belum ada'],
        ['tone'=>'purple','icon'=>$summary['markets'],'label'=>'Pasar Dipantau','value'=>$summary['markets'],'note'=>'Di Kabupaten Mojokerto'],
    ];
@endphp
<section class="summary-grid">
    @foreach($summaries as $item)
        <article class="summary-card {{ $item['tone'] }}"><div class="summary-icon">{!! $item['icon'] !!}</div><div><span>{{ $item['label'] }}</span><strong>{{ $item['value'] }}</strong><small>{{ $item['note'] }}</small></div></article>
    @endforeach
</section>
