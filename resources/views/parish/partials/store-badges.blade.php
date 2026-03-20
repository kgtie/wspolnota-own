@php
    $compact = $compact ?? false;
@endphp

<div class="{{ $wrapperClass ?? 'grid gap-3 sm:grid-cols-2' }}">
    <a href="#" rel="nofollow" class="parish-store-card {{ $compact ? 'px-3 py-3' : 'px-4 py-4' }}">
        <span class="parish-store-icon">
            <img src="{{ asset('images/store/google-play-icon.png') }}" alt="" class="h-7 w-7 object-contain" loading="lazy">
        </span>
        <span class="min-w-0">
            <span class="block text-[0.65rem] font-semibold uppercase tracking-[0.22em] text-white/55">Pobierz z</span>
            <span class="mt-1 block text-lg font-extrabold tracking-tight text-white {{ $compact ? 'sm:text-[1.05rem]' : 'sm:text-xl' }}">Google Play</span>
        </span>
    </a>

    <a href="#" rel="nofollow" class="parish-store-card {{ $compact ? 'px-3 py-3' : 'px-4 py-4' }}">
        <span class="parish-store-icon">
            <img src="{{ asset('images/store/apple-icon.png') }}" alt="" class="h-7 w-7 object-contain" loading="lazy">
        </span>
        <span class="min-w-0">
            <span class="block text-[0.65rem] font-semibold uppercase tracking-[0.22em] text-white/55">Pobierz w</span>
            <span class="mt-1 block text-lg font-extrabold tracking-tight text-white {{ $compact ? 'sm:text-[1.05rem]' : 'sm:text-xl' }}">App Store</span>
        </span>
    </a>
</div>
