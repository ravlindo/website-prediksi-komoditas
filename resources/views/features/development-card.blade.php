<section class="panel development-card">
    @isset($icon)
        <div class="development-icon">{{ $icon }}</div>
    @endisset
    <div><p class="eyebrow">FITUR BERIKUTNYA</p><h2>{{ $title }}</h2><p>Struktur halaman sudah dipisahkan. Implementasi data asli akan ditambahkan setelah desain dan sumber data disetujui.</p></div>
    <ul>@foreach($items as $item)<li><span>✓</span>{{ $item }}</li>@endforeach</ul>
</section>
