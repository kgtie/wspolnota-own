@extends('layouts.landing')

@section('title', 'Wspólnota | Nowoczesna strona główna usługi dla parafii')
@section('meta_description', 'Wspólnota porządkuje komunikację parafii: msze, ogłoszenia, aktualności, kancelaria online i panel proboszcza w jednym miejscu.')

@section('content')
    <section class="grid gap-8 pb-10 pt-4 lg:grid-cols-[1.1fr_0.9fr] lg:items-center lg:pb-16 lg:pt-10">
        <div class="space-y-8">
            <span class="eyebrow">Usługa dla parafii, które chcą być bliżej ludzi</span>

            <div class="space-y-5">
                <h1 class="font-display text-5xl leading-[0.95] text-balance text-stone-950 sm:text-6xl lg:text-7xl">
                    Parafia, która nadąża za codziennością.
                </h1>
                <p class="max-w-2xl text-lg leading-8 text-stone-600 sm:text-xl">
                    Wspólnota łączy panel administratora dla proboszcza z prostą aplikacją PWA dla parafian. Msze, ogłoszenia, aktualności i kancelaria online trafiają tam, gdzie naprawdę dzieje się życie: do telefonu i przeglądarki.
                </p>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row">
                <a href="{{ route('landing.contact') }}" class="inline-flex items-center justify-center rounded-full bg-stone-950 px-6 py-3 text-base font-semibold text-white transition hover:bg-[#b87333]">
                    Porozmawiajmy o wdrożeniu
                </a>
                <a href="{{ route('login') }}" class="inline-flex items-center justify-center rounded-full border border-stone-300 px-6 py-3 text-base font-semibold text-stone-700 transition hover:border-stone-900 hover:text-stone-950">
                    Wejście do panelu proboszcza
                </a>
            </div>

            <dl class="grid gap-4 sm:grid-cols-3">
                <div class="panel px-5 py-4">
                    <dt class="text-sm text-stone-500">Dla parafii</dt>
                    <dd class="mt-2 text-2xl font-extrabold text-stone-950">1 panel</dd>
                    <p class="mt-2 text-sm leading-6 text-stone-600">Jedno miejsce do zarządzania mszami, ogłoszeniami, aktualnościami i wiadomościami.</p>
                </div>
                <div class="panel px-5 py-4">
                    <dt class="text-sm text-stone-500">Dla parafian</dt>
                    <dd class="mt-2 text-2xl font-extrabold text-stone-950">PWA</dd>
                    <p class="mt-2 text-sm leading-6 text-stone-600">Działa w przeglądarce i na telefonie, bez ciężkiego wdrożenia po stronie użytkownika.</p>
                </div>
                <div class="panel px-5 py-4">
                    <dt class="text-sm text-stone-500">Komunikacja</dt>
                    <dd class="mt-2 text-2xl font-extrabold text-stone-950">24/7</dd>
                    <p class="mt-2 text-sm leading-6 text-stone-600">Ogłoszenia, newsy i kontakt z kancelarią są dostępne wtedy, gdy parafianin ich potrzebuje.</p>
                </div>
            </dl>
        </div>

        <div class="panel relative overflow-hidden px-6 py-6 sm:px-8 sm:py-8">
            <div class="absolute inset-x-8 top-0 h-px bg-gradient-to-r from-transparent via-[#b8733380] to-transparent"></div>
            <div class="grid gap-4">
                <div class="landing-elevated-card p-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-semibold text-stone-900">Panel administratora</p>
                            <p class="mt-1 text-sm text-stone-500">Kalendarz, ogłoszenia, kancelaria online</p>
                        </div>
                        <span class="landing-status-pill px-3 py-1 text-xs font-bold uppercase tracking-[0.2em]">Live</span>
                    </div>
                    <div class="mt-5 grid gap-3 sm:grid-cols-2">
                        <div class="rounded-2xl bg-stone-950 px-4 py-4 text-white">
                            <p class="text-xs uppercase tracking-[0.24em] text-stone-300">Najbliższy tydzień</p>
                            <p class="mt-3 text-2xl font-bold">12 mszy</p>
                            <p class="mt-2 text-sm text-stone-300">Gotowe do publikacji wraz z intencjami.</p>
                        </div>
                        <div class="landing-metric-card px-4 py-4 text-white">
                            <p class="text-xs uppercase tracking-[0.24em] text-white/70">Komunikacja</p>
                            <p class="mt-3 text-2xl font-bold">8 rozmów</p>
                            <p class="mt-2 text-sm text-white/80">Kancelaria online porządkuje zapytania bez chaosu w skrzynce.</p>
                        </div>
                    </div>
                </div>

                <div class="landing-soft-card p-6">
                    <p class="text-xs font-bold uppercase tracking-[0.28em] text-stone-500">Wspólnota w praktyce</p>
                    <ul class="mt-4 space-y-3 text-sm leading-6 text-stone-600">
                        <li>Parafianin sprawdza najbliższe msze i zapisuje swoją obecność.</li>
                        <li>Proboszcz publikuje ogłoszenia raz, a parafianie dostają je od razu w aplikacji.</li>
                        <li>Aktualności i kancelaria online porządkują kontakt bez mnożenia kanałów.</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <section id="funkcje" class="space-y-6 py-10 lg:py-14">
        <div class="max-w-3xl space-y-3">
            <span class="eyebrow">Co dokładnie robi Wspólnota</span>
            <h2 class="font-display text-4xl text-stone-950 sm:text-5xl">Usługa, która porządkuje rytm parafii zamiast dokładać kolejny system.</h2>
            <p class="text-lg leading-8 text-stone-600">
                Zamiast rozproszonych komunikatów, papierowych list i wiadomości rozsianych po kilku kanałach, parafia dostaje jedno centrum pracy. Parafianin zyskuje prostą drogę do informacji. Proboszcz odzyskuje czas.
            </p>
        </div>

        <div class="grid gap-5 lg:grid-cols-3">
            <article class="feature-card">
                <p class="feature-index">01</p>
                <h3 class="mt-4 text-2xl font-bold text-stone-950">Msze i intencje bez chaosu</h3>
                <p class="mt-3 text-sm leading-7 text-stone-600">
                    Kalendarz mszy, szczegóły celebracji, zapisy obecności i gotowe wydruki do wykorzystania w parafii. Wszystko spójne dla administratora i czytelne dla parafian.
                </p>
            </article>
            <article class="feature-card">
                <p class="feature-index">02</p>
                <h3 class="mt-4 text-2xl font-bold text-stone-950">Ogłoszenia, które naprawdę docierają</h3>
                <p class="mt-3 text-sm leading-7 text-stone-600">
                    Tygodniowe zestawy ogłoszeń, ich streszczenia, możliwość udostępniania i wygodny podgląd historii. Jedna publikacja, wiele punktów styku z wiernymi.
                </p>
            </article>
            <article class="feature-card">
                <p class="feature-index">03</p>
                <h3 class="mt-4 text-2xl font-bold text-stone-950">Kancelaria online z ludzkim tempem</h3>
                <p class="mt-3 text-sm leading-7 text-stone-600">
                    Prywatne rozmowy i bezpieczne załączniki porządkują sprawy urzędowe, bez konieczności przerzucania wszystkiego na prywatne komunikatory czy przypadkowe maile.
                </p>
            </article>
        </div>
    </section>

    <section class="grid gap-6 py-10 lg:grid-cols-[0.95fr_1.05fr] lg:items-start lg:py-14">
        <div class="panel px-6 py-6 sm:px-8">
            <span class="eyebrow">Dla proboszcza i zespołu parafii</span>
            <h2 class="mt-4 font-display text-4xl text-stone-950">Panel, który nie wymaga instrukcji obsługi.</h2>
            <p class="mt-4 text-base leading-8 text-stone-600">
                Dashboard pokazuje najważniejsze sprawy parafii, przypomina o publikacjach i porządkuje codzienną administrację. Wspólnota nie próbuje zastąpić duszpasterstwa. Ona usuwa tarcie organizacyjne.
            </p>
            <ul class="mt-6 space-y-3 text-sm leading-7 text-stone-600">
                <li>Publikacja mszy, ogłoszeń i aktualności z jednego miejsca.</li>
                <li>Podgląd zgłoszeń i rozmów z kancelarii online.</li>
                <li>Wygodne zarządzanie użytkownikami i statystykami parafii.</li>
            </ul>
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <div class="panel px-6 py-6">
                <p class="text-xs font-bold uppercase tracking-[0.26em] text-stone-500">Aktualności</p>
                <h3 class="mt-3 text-2xl font-bold text-stone-950">Blog parafialny z komentarzami i mediami</h3>
                <p class="mt-3 text-sm leading-7 text-stone-600">Wpisy działają jak newsroom parafii: od krótkiej informacji po większy artykuł ze zdjęciami lub plikami.</p>
            </div>
            <div class="panel px-6 py-6">
                <p class="text-xs font-bold uppercase tracking-[0.26em] text-stone-500">Powiadomienia</p>
                <h3 class="mt-3 text-2xl font-bold text-stone-950">Przypomnienia i komunikaty trafiają na czas</h3>
                <p class="mt-3 text-sm leading-7 text-stone-600">Usługa wspiera parafię w wysyłce informacji o mszach, ogłoszeniach i nowych treściach bez ręcznego przypominania każdemu z osobna.</p>
            </div>
            <div class="panel px-6 py-6 sm:col-span-2">
                <p class="text-xs font-bold uppercase tracking-[0.26em] text-stone-500">Ton usługi</p>
                <blockquote class="mt-3 max-w-3xl font-display text-3xl leading-tight text-stone-950">
                    „Technologia ma być tu cicha. Ma zostawić więcej miejsca na obecność, rozmowę i porządek”.
                </blockquote>
            </div>
        </div>
    </section>

    <section id="ekrany" class="space-y-6 py-10 lg:py-14">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
            <div class="max-w-3xl">
                <span class="eyebrow">Miejsca na screeny</span>
                <h2 class="mt-3 font-display text-4xl text-stone-950 sm:text-5xl">Tu możesz wstawić kluczowe widoki usługi.</h2>
                <p class="mt-3 text-lg leading-8 text-stone-600">
                    Zostawiłem gotowe boksy na zrzuty ekranu, aby można było później podmienić je na prawdziwe widoki aplikacji i panelu bez przebudowy układu strony.
                </p>
            </div>
            <a href="{{ route('login') }}" class="inline-flex rounded-full border border-stone-300 px-5 py-3 text-sm font-semibold text-stone-700 transition hover:border-stone-900 hover:text-stone-950">
                Zobacz wejście do panelu
            </a>
        </div>

        <div class="grid gap-5 lg:grid-cols-[1.15fr_0.85fr]">
            <div class="screen-placeholder min-h-[22rem]">
                <div>
                    <p class="screen-label">Screen 01</p>
                    <h3 class="mt-3 text-3xl font-bold text-stone-950">Panel administratora</h3>
                    <p class="mt-2 max-w-md text-sm leading-7 text-stone-600">Miejsce na widok dashboardu proboszcza z modułami mszy, ogłoszeń i kancelarii online.</p>
                </div>
            </div>
            <div class="grid gap-5">
                <div class="screen-placeholder min-h-[10.5rem]">
                    <div>
                        <p class="screen-label">Screen 02</p>
                        <h3 class="mt-3 text-2xl font-bold text-stone-950">Aplikacja PWA</h3>
                        <p class="mt-2 text-sm leading-7 text-stone-600">Miejsce na ekran główny dla parafian z mszami i ogłoszeniami.</p>
                    </div>
                </div>
                <div class="screen-placeholder min-h-[10.5rem]">
                    <div>
                        <p class="screen-label">Screen 03</p>
                        <h3 class="mt-3 text-2xl font-bold text-stone-950">Kancelaria online</h3>
                        <p class="mt-2 text-sm leading-7 text-stone-600">Miejsce na konwersację użytkownika z administracją parafii.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="cennik" class="space-y-6 py-10 lg:py-14">
        <div class="max-w-3xl">
            <span class="eyebrow">Pricing</span>
            <h2 class="mt-3 font-display text-4xl text-stone-950 sm:text-5xl">Model wdrożenia dopasowany do etapu parafii.</h2>
            <p class="mt-3 text-lg leading-8 text-stone-600">
                W dokumentacji usługi zakładany jest start od wdrożeń pilotażowych, a następnie przejście do modelu abonamentowego. Sekcja poniżej komunikuje to w prosty i sprzedażowy sposób, bez obiecywania więcej niż gotowy etap produktu.
            </p>
        </div>

        <div class="grid gap-5 xl:grid-cols-3">
            <article class="pricing-card">
                <p class="text-sm font-semibold text-stone-500">Pilotaż</p>
                <h3 class="mt-3 text-3xl font-bold text-stone-950">0 zł</h3>
                <p class="mt-3 text-sm leading-7 text-stone-600">Dla parafii testowych uczestniczących w uruchomieniu usługi.</p>
                <ul class="mt-6 space-y-3 text-sm leading-6 text-stone-700">
                    <li>Panel administratora dla proboszcza</li>
                    <li>Podstawowe moduły aplikacji PWA</li>
                    <li>Wspólne dopracowanie wdrożenia</li>
                </ul>
            </article>

            <article class="pricing-card pricing-card-featured">
                <p class="text-sm font-semibold text-stone-700">Abonament parafialny</p>
                <h3 class="mt-3 text-3xl font-bold text-stone-950">Po premierze</h3>
                <p class="mt-3 text-sm leading-7 text-stone-700">Docelowy model SaaS dla parafii, które chcą stałego dostępu do usługi i rozwoju funkcji.</p>
                <ul class="mt-6 space-y-3 text-sm leading-6 text-stone-800">
                    <li>Pełny pakiet komunikacji z parafianami</li>
                    <li>Rozwój wraz z kolejnymi modułami</li>
                    <li>Wsparcie wdrożeniowe i aktualizacje</li>
                </ul>
            </article>

            <article class="pricing-card">
                <p class="text-sm font-semibold text-stone-500">Wdrożenie indywidualne</p>
                <h3 class="mt-3 text-3xl font-bold text-stone-950">Kontakt</h3>
                <p class="mt-3 text-sm leading-7 text-stone-600">Dla większych struktur, kilku parafii lub wdrożeń wymagających niestandardowego zakresu prac.</p>
                <ul class="mt-6 space-y-3 text-sm leading-6 text-stone-700">
                    <li>Ustalenie zakresu i harmonogramu</li>
                    <li>Pomoc przy materiałach i konfiguracji</li>
                    <li>Priorytetowy kontakt roboczy</li>
                </ul>
            </article>
        </div>
    </section>

    <section class="py-10 lg:py-14">
        <div class="panel overflow-hidden px-6 py-8 sm:px-8 lg:px-10 lg:py-10">
            <div class="grid gap-8 lg:grid-cols-[1fr_auto] lg:items-center">
                <div class="max-w-3xl">
                    <span class="eyebrow">Gotowi na rozmowę</span>
                    <h2 class="mt-3 font-display text-4xl text-stone-950 sm:text-5xl">Jeśli Twoja parafia chce wejść w cyfrową codzienność bez zadęcia, zacznijmy od prostej rozmowy.</h2>
                    <p class="mt-4 text-lg leading-8 text-stone-600">
                        Pokażemy kierunek wdrożenia, omówimy potrzeby parafii i zaplanujemy pierwsze materiały, w tym screeny do uzupełnienia na tej stronie.
                    </p>
                </div>

                <div class="flex flex-col gap-3">
                    <a href="{{ route('landing.contact') }}" class="inline-flex items-center justify-center rounded-full bg-stone-950 px-6 py-3 text-base font-semibold text-white transition hover:bg-[#b87333]">
                        Przejdź do kontaktu
                    </a>
                    <a href="{{ route('landing.privacy') }}" class="inline-flex items-center justify-center rounded-full border border-stone-300 px-6 py-3 text-base font-semibold text-stone-700 transition hover:border-stone-900 hover:text-stone-950">
                        Polityka prywatności
                    </a>
                </div>
            </div>
        </div>
    </section>
@endsection
