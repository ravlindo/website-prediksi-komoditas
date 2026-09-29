<section class="page-heading">
    <div>
        @isset($eyebrow)
            <p class="eyebrow">{{ $eyebrow }}</p>
        @endisset
        <h1 class="feature-title">
            @if(($titleIcon ?? null) === 'prediction')
                <span class="feature-title-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none">
                        <path d="M4 18V6M4 18H20"/>
                        <path d="M7 15L11 11L14 13L20 7"/>
                        <path d="M16 7H20V11"/>
                    </svg>
                </span>
            @endif
            {{ $title }}
        </h1>
        <p>{{ $description }}</p>
    </div>
    <div class="page-heading-actions">
        <button class="back-previous" id="pageBack" type="button" data-fallback="{{ route('dashboard') }}">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m14 6-6 6 6 6"/></svg>
            <span>Kembali</span>
        </button>
        <a class="back-dashboard" href="{{ route('dashboard') }}">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 11.5 12 5l8 6.5M6 10v9h12v-9M10 19v-5h4v5"/></svg>
            <span>Dashboard</span>
        </a>
    </div>
</section>
