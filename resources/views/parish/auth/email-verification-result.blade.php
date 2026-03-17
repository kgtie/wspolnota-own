@extends('layouts.parish')

@php
    $isVerified = $status === 'verified';
    $isAlreadyVerified = $status === 'already_verified';
    $isInvalid = $status === 'invalid';

    $pageTitle = match (true) {
        $isVerified => 'Konto aktywowane • ' . $parish->short_name,
        $isAlreadyVerified => 'Adres email już potwierdzony • ' . $parish->short_name,
        default => 'Link weryfikacyjny jest nieważny • ' . $parish->short_name,
    };

    $pageDescription = match (true) {
        $isVerified => 'Adres email został potwierdzony, a konto aktywowane.',
        $isAlreadyVerified => 'Adres email tego konta był już wcześniej potwierdzony.',
        default => 'Link weryfikacyjny jest nieprawidłowy lub wygasł.',
    };

    $headline = match (true) {
        $isVerified => 'Konto zostało aktywowane.',
        $isAlreadyVerified => 'Adres email był już wcześniej potwierdzony.',
        default => 'Nie udało się potwierdzić adresu email.',
    };

    $body = match (true) {
        $isVerified => 'Adres email został właśnie zweryfikowany. Możesz wrócić do aplikacji i korzystać z konta dalej.',
        $isAlreadyVerified => 'To konto ma już potwierdzony adres email. Możesz bezpiecznie wrócić do aplikacji.',
        default => 'Ten link jest nieprawidłowy albo wygasł. W aplikacji możesz poprosić o wysłanie nowego maila weryfikacyjnego.',
    };
@endphp

@section('title', $pageTitle)
@section('meta_description', $pageDescription)
@section('canonical_url', route('parish.home', ['subdomain' => $parish]))
@section('meta_image', $coverImageUrl ?: $avatarUrl ?: '')
@section('og_type', 'website')
@section('robots', 'noindex,follow,max-snippet:-1,max-image-preview:large,max-video-preview:-1')

@section('content')
    <section class="mx-auto max-w-3xl">
        <div class="overflow-hidden rounded-[2.2rem] border border-white/60 bg-white/88 shadow-[0_36px_100px_rgba(58,40,24,0.14)] backdrop-blur">
            <div class="relative overflow-hidden px-6 py-8 sm:px-8 sm:py-10">
                @if ($coverImageUrl)
                    <img src="{{ $coverImageUrl }}" alt="{{ $parish->short_name }}" class="absolute inset-0 h-full w-full object-cover opacity-14">
                @endif

                <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,_rgba(184,115,51,0.16),_transparent_50%),linear-gradient(180deg,rgba(255,255,255,0.96),rgba(250,247,241,0.94))]"></div>

                <div class="relative">
                    <div class="flex items-center gap-4">
                        <div
                            class="flex h-16 w-16 items-center justify-center rounded-[1.4rem] text-2xl font-semibold text-white shadow-lg"
                            style="background-color: {{ $isInvalid ? '#9f1239' : 'var(--parish-accent)' }};"
                        >
                            @if ($isInvalid)
                                !
                            @else
                                ✓
                            @endif
                        </div>

                        <div>
                            <p class="text-xs font-semibold tracking-[0.22em] text-stone-500 uppercase">Rejestracja użytkownika</p>
                            <h1 class="mt-2 font-display text-3xl text-stone-950 sm:text-4xl">{{ $headline }}</h1>
                        </div>
                    </div>

                    <p class="mt-6 max-w-2xl text-base leading-8 text-stone-600 sm:text-lg">
                        {{ $body }}
                    </p>

                    <div class="mt-8 grid gap-4 rounded-[1.8rem] border border-stone-200/80 bg-stone-50/90 p-5 sm:grid-cols-[1.1fr_0.9fr]">
                        <div>
                            <p class="text-xs font-semibold tracking-[0.22em] text-stone-500 uppercase">Parafia</p>
                            <p class="mt-2 text-lg font-semibold text-stone-950">{{ $parish->name }}</p>
                            <p class="mt-1 text-sm text-stone-600">{{ $parish->city }}</p>

                            <div class="mt-4 text-sm text-stone-600">
                                <p class="font-semibold text-stone-900">{{ $user->email }}</p>
                                @if ($isVerified)
                                    <p class="mt-1">Adres email został potwierdzony przed chwilą.</p>
                                @elseif ($isAlreadyVerified)
                                    <p class="mt-1">Adres email tego konta był już wcześniej aktywny.</p>
                                @else
                                    <p class="mt-1">Aby dokończyć rejestrację, użyj nowego linku wysłanego z aplikacji.</p>
                                @endif
                            </div>
                        </div>

                        <div class="flex flex-col justify-between rounded-[1.6rem] border border-stone-200 bg-white/90 p-5">
                            <div>
                                <p class="text-xs font-semibold tracking-[0.22em] text-stone-500 uppercase">Co dalej</p>
                                <p class="mt-3 text-sm leading-7 text-stone-600">
                                    Wróć do aplikacji mobilnej i kontynuuj. Jeśli potrzebujesz, możesz też przejść na publiczną stronę parafii.
                                </p>
                            </div>

                            <div class="mt-5 flex flex-wrap gap-3">
                                <a
                                    href="{{ route('parish.home', ['subdomain' => $parish]) }}"
                                    class="inline-flex items-center rounded-full px-5 py-3 text-sm font-semibold text-white shadow-lg transition hover:opacity-90"
                                    style="background-color: var(--parish-accent);"
                                >
                                    Strona parafii
                                </a>

                                @if ($websiteUrl)
                                    <a
                                        href="{{ $websiteUrl }}"
                                        target="_blank"
                                        rel="noreferrer"
                                        class="inline-flex items-center rounded-full border border-stone-300 bg-white px-5 py-3 text-sm font-semibold text-stone-700 transition hover:border-stone-950 hover:text-stone-950"
                                    >
                                        Strona WWW parafii
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
