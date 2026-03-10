@extends('layouts.landing')

@section('title', 'Wspólnota | Aplikacja i panel dla parafii, mszy, ogłoszeń i kancelarii online')
@section('meta_description', 'Wspólnota to bezpieczna usługa dla parafii: panel dla proboszcza, aplikacja dla wiernych, ogłoszenia, msze, aktualności i kancelaria online w jednym miejscu.')
@section('canonical', route('landing.home'))
@section('schema_type', 'Service')
@section('structured_data')
    @php
        $serviceSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'Service',
            'name' => 'Wspólnota',
            'serviceType' => 'Usługa cyfrowa dla parafii',
            'description' => 'Panel dla proboszcza i aplikacja dla wiernych: msze, ogłoszenia, aktualności oraz kancelaria online w jednym miejscu.',
            'provider' => [
                '@type' => 'Organization',
                'name' => config('app.name', 'Wspólnota'),
                'url' => rtrim(config('app.url'), '/'),
            ],
            'areaServed' => [
                '@type' => 'Country',
                'name' => 'Polska',
            ],
            'audience' => [
                '@type' => 'Audience',
                'audienceType' => 'Parafie katolickie i parafianie',
            ],
            'url' => route('landing.home'),
        ];
    @endphp
    <script type="application/ld+json">@json($serviceSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)</script>
@endsection

@section('content')
    <section class="grid gap-8 pb-10 pt-4 lg:grid-cols-[1.1fr_0.9fr] lg:items-center lg:pb-16 lg:pt-10">
        <div class="space-y-8">
            <span class="eyebrow">Usługa dla parafii, które chcą być bliżej ludzi</span>

            <div class="space-y-5">
                <h1 class="font-display text-5xl leading-[0.95] text-balance text-stone-950 sm:text-6xl lg:text-7xl">
                    Parafia, która nadąża za codziennością.
                </h1>
                <p class="max-w-2xl text-lg leading-8 text-stone-600 sm:text-xl">
                    Wspólnota pomaga parafii lepiej informować wiernych i spokojniej organizować codzienne sprawy. Msze, ogłoszenia, aktualności i kancelaria online są zebrane w jednym miejscu, prostym dla księdza i wygodnym dla parafian.
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
                    <p class="mt-2 text-sm leading-6 text-stone-600">Jedno miejsce do prowadzenia mszy, ogłoszeń, aktualności i kontaktu z wiernymi.</p>
                </div>
                <div class="panel px-5 py-4">
                    <dt class="text-sm text-stone-500">Dla parafian</dt>
                    <dd class="mt-2 text-2xl font-extrabold text-stone-950">PWA</dd>
                    <p class="mt-2 text-sm leading-6 text-stone-600">Działa w telefonie i przeglądarce, bez skomplikowanego wdrażania po stronie użytkownika.</p>
                </div>
                <div class="panel px-5 py-4">
                    <dt class="text-sm text-stone-500">Komunikacja</dt>
                    <dd class="mt-2 text-2xl font-extrabold text-stone-950">24/7</dd>
                    <p class="mt-2 text-sm leading-6 text-stone-600">Wierni widzą najważniejsze informacje wtedy, kiedy naprawdę ich potrzebują.</p>
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
                            <p class="mt-1 text-sm text-stone-500">Msze, ogłoszenia, aktualności i kancelaria online</p>
                        </div>
                        <span class="landing-status-pill px-3 py-1 text-xs font-bold uppercase tracking-[0.2em]">Live</span>
                    </div>
                    <div class="mt-5 grid gap-3 sm:grid-cols-2">
                        <div class="rounded-2xl bg-stone-950 px-4 py-4 text-white">
                            <p class="text-xs uppercase tracking-[0.24em] text-stone-300">Najbliższy tydzień</p>
                            <p class="mt-3 text-2xl font-bold">12 mszy</p>
                            <p class="mt-2 text-sm text-stone-300">Gotowe do pokazania wiernym razem z intencjami.</p>
                        </div>
                        <div class="landing-metric-card px-4 py-4 text-white">
                            <p class="text-xs uppercase tracking-[0.24em] text-white/70">Komunikacja</p>
                            <p class="mt-3 text-2xl font-bold">8 rozmów</p>
                            <p class="mt-2 text-sm text-white/80">Kancelaria online pomaga odpowiadać bez chaosu w telefonach i wiadomościach.</p>
                        </div>
                    </div>
                </div>

                <div class="landing-soft-card p-6">
                    <p class="text-xs font-bold uppercase tracking-[0.28em] text-stone-500">Wspólnota w praktyce</p>
                    <ul class="mt-4 space-y-3 text-sm leading-6 text-stone-600">
                        <li>Parafianin sprawdza godziny mszy i najważniejsze informacje bez szukania po kilku miejscach.</li>
                        <li>Ksiądz publikuje ogłoszenia jeden raz, a wierni od razu widzą je w aplikacji.</li>
                        <li>Kancelaria online i aktualności porządkują kontakt z parafią bez niepotrzebnego zamieszania.</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <section id="funkcje" class="space-y-6 py-10 lg:py-14">
        <div class="max-w-3xl space-y-3">
            <span class="eyebrow">Co dokładnie robi Wspólnota</span>
            <h2 class="font-display text-4xl text-stone-950 sm:text-5xl">Usługa, która pomaga w codziennym prowadzeniu parafii, zamiast dokładać kolejne obowiązki.</h2>
            <p class="text-lg leading-8 text-stone-600">
                Zamiast osobnych kartek, wiadomości, telefonów i pytań o rzeczy podstawowe, parafia dostaje jedno uporządkowane miejsce pracy. Wierni łatwiej znajdują informacje, a ksiądz ma większy porządek w komunikacji.
            </p>
        </div>

        <div class="grid gap-5 lg:grid-cols-3">
            <article class="feature-card">
                <p class="feature-index">01</p>
                <h3 class="mt-4 text-2xl font-bold text-stone-950">Msze i intencje bez chaosu</h3>
                <p class="mt-3 text-sm leading-7 text-stone-600">
                    Godziny mszy, intencje i najważniejsze szczegóły są zebrane przejrzyście. Parafia łatwiej panuje nad kalendarzem, a wierni szybciej znajdują potrzebne informacje.
                </p>
            </article>
            <article class="feature-card">
                <p class="feature-index">02</p>
                <h3 class="mt-4 text-2xl font-bold text-stone-950">Ogłoszenia, które naprawdę docierają</h3>
                <p class="mt-3 text-sm leading-7 text-stone-600">
                    Ogłoszenia można przygotować raz i przekazać wiernym w uporządkowany sposób. Łatwiej wrócić do wcześniejszych treści i uniknąć sytuacji, w której coś „przepada”.
                </p>
            </article>
            <article class="feature-card">
                <p class="feature-index">03</p>
                <h3 class="mt-4 text-2xl font-bold text-stone-950">Kancelaria online z ludzkim tempem</h3>
                <p class="mt-3 text-sm leading-7 text-stone-600">
                    Sprawy kancelaryjne mogą być prowadzone spokojniej i czytelniej, bez przenoszenia wszystkiego na prywatne komunikatory albo przypadkowe maile.
                </p>
            </article>
        </div>
    </section>

    <section class="grid gap-6 py-10 lg:grid-cols-[0.95fr_1.05fr] lg:items-start lg:py-14">
        <div class="panel px-6 py-6 sm:px-8">
            <span class="eyebrow">Dla proboszcza i zespołu parafii</span>
            <h2 class="mt-4 font-display text-4xl text-stone-950">Panel, który ma pomagać, a nie wymagać długiego uczenia się.</h2>
            <p class="mt-4 text-base leading-8 text-stone-600">
                Panel pokazuje najważniejsze sprawy parafii, przypomina o tym, co trzeba uzupełnić, i pomaga utrzymać porządek w codziennych obowiązkach. Wspólnota nie zastępuje duszpasterstwa, tylko odciąża od organizacyjnego chaosu.
            </p>
            <ul class="mt-6 space-y-3 text-sm leading-7 text-stone-600">
                <li>Jedno miejsce do publikacji mszy, ogłoszeń i aktualności.</li>
                <li>Łatwiejszy podgląd wiadomości i spraw prowadzonych przez kancelarię online.</li>
                <li>Większy porządek w użytkownikach, zgłoszeniach i podstawowych statystykach parafii.</li>
            </ul>
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <div class="panel px-6 py-6">
                <p class="text-xs font-bold uppercase tracking-[0.26em] text-stone-500">Aktualności</p>
                <h3 class="mt-3 text-2xl font-bold text-stone-950">Blog parafialny z komentarzami i mediami</h3>
                <p class="mt-3 text-sm leading-7 text-stone-600">Od prostego komunikatu po dłuższą relację ze zdjęciami. Parafia może publikować treści w bardziej uporządkowany sposób niż tylko przez pojedyncze ogłoszenia.</p>
            </div>
            <div class="panel px-6 py-6">
                <p class="text-xs font-bold uppercase tracking-[0.26em] text-stone-500">Powiadomienia</p>
                <h3 class="mt-3 text-2xl font-bold text-stone-950">Przypomnienia i komunikaty trafiają na czas</h3>
                <p class="mt-3 text-sm leading-7 text-stone-600">Usługa pomaga przekazywać informacje o mszach, ogłoszeniach i nowych treściach bez konieczności przypominania każdemu osobno.</p>
            </div>
            <div class="panel px-6 py-6 sm:col-span-2">
                <p class="text-xs font-bold uppercase tracking-[0.26em] text-stone-500">Ton usługi</p>
                <blockquote class="mt-3 max-w-3xl font-display text-3xl leading-tight text-stone-950">
                    „Technologia ma tu służyć parafii po cichu. Ma dawać więcej porządku, spokoju i czasu dla ludzi”.
                </blockquote>
            </div>
        </div>
    </section>

    <section id="korzysci" class="space-y-6 py-10 lg:py-14">
        <div class="max-w-3xl">
            <span class="eyebrow">Korzyści dla parafii</span>
            <h2 class="mt-3 font-display text-4xl text-stone-950 sm:text-5xl">Mniej improwizacji, mniej niepotrzebnych pytań, więcej porządku.</h2>
            <p class="mt-3 text-lg leading-8 text-stone-600">
                Wspólnota nie ma być tylko kolejną stroną z informacjami. To narzędzie, które pomaga parafii lepiej poukładać tydzień pracy, uporządkować kontakt z wiernymi i ograniczyć liczbę spraw załatwianych „na szybko” albo „na pamięć”.
            </p>
        </div>

        <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
            <article class="feature-card">
                <p class="feature-index">01</p>
                <h3 class="mt-4 text-2xl font-bold text-stone-950">Mniej telefonów i pytań o podstawy</h3>
                <p class="mt-3 text-sm leading-7 text-stone-600">
                    Gdy wierni mają łatwy dostęp do godzin mszy, ogłoszeń i aktualności, rzadziej trzeba odpowiadać kilka razy na te same podstawowe pytania.
                </p>
            </article>
            <article class="feature-card">
                <p class="feature-index">02</p>
                <h3 class="mt-4 text-2xl font-bold text-stone-950">Lepszy rytm tygodnia parafii</h3>
                <p class="mt-3 text-sm leading-7 text-stone-600">
                    Łatwiej przygotować msze, opublikować ogłoszenia i zapanować nad bieżącym kontaktem, gdy wszystko jest zebrane w jednym panelu.
                </p>
            </article>
            <article class="feature-card">
                <p class="feature-index">03</p>
                <h3 class="mt-4 text-2xl font-bold text-stone-950">Więcej czasu na duszpasterstwo</h3>
                <p class="mt-3 text-sm leading-7 text-stone-600">
                    Gdy mniej energii idzie na powtarzalne informowanie i porządkowanie wiadomości, więcej czasu zostaje na rozmowę, obecność i realne sprawy parafii.
                </p>
            </article>
            <article class="feature-card">
                <p class="feature-index">04</p>
                <h3 class="mt-4 text-2xl font-bold text-stone-950">Nowoczesny wizerunek parafii</h3>
                <p class="mt-3 text-sm leading-7 text-stone-600">
                    Parafia może komunikować się jasno i nowocześnie, nie tracąc swojego charakteru. Technologia pomaga budować porządek, a nie odbiera relacji.
                </p>
            </article>
        </div>
    </section>

    <section id="technologia" class="grid gap-6 py-10 lg:grid-cols-[1.05fr_0.95fr] lg:items-start lg:py-14">
        <div class="panel px-6 py-6 sm:px-8">
            <span class="eyebrow">Zaawansowanie technologiczne</span>
            <h2 class="mt-4 font-display text-4xl text-stone-950">Nowoczesna technologia, która ma po prostu działać pewnie i spokojnie.</h2>
            <p class="mt-4 text-base leading-8 text-stone-600">
                Wspólnota jest budowana jako jedna spójna usługa: z aplikacją dla wiernych i panelem dla proboszcza. Dla parafii oznacza to mniej rozproszonych narzędzi, większy porządek w danych i łatwiejszy rozwój kolejnych funkcji w przyszłości.
            </p>

            <div class="mt-6 grid gap-4 sm:grid-cols-2">
                <div class="landing-soft-card p-5">
                    <p class="text-xs font-bold uppercase tracking-[0.24em] text-stone-500">Architektura</p>
                    <p class="mt-3 text-xl font-bold text-stone-950">Jedna usługa zamiast wielu porozrzucanych narzędzi</p>
                    <p class="mt-2 text-sm leading-7 text-stone-600">Strona usługi, aplikacja dla wiernych i panel administracyjny są częścią jednego spójnego systemu.</p>
                </div>
                <div class="landing-soft-card p-5">
                    <p class="text-xs font-bold uppercase tracking-[0.24em] text-stone-500">Mobilność</p>
                    <p class="mt-3 text-xl font-bold text-stone-950">Wygoda dla wiernych na telefonie i komputerze</p>
                    <p class="mt-2 text-sm leading-7 text-stone-600">Parafianie mogą korzystać z usługi wygodnie na swoich urządzeniach, bez konieczności uczenia się skomplikowanych rozwiązań.</p>
                </div>
                <div class="landing-soft-card p-5">
                    <p class="text-xs font-bold uppercase tracking-[0.24em] text-stone-500">Automatyzacja</p>
                    <p class="mt-3 text-xl font-bold text-stone-950">Powiadomienia i przypomnienia tam, gdzie pomagają</p>
                    <p class="mt-2 text-sm leading-7 text-stone-600">System może wspierać parafię w przypomnieniach, publikacjach i porządkowaniu treści, żeby mniej rzeczy trzeba było pilnować ręcznie.</p>
                </div>
                <div class="landing-soft-card p-5">
                    <p class="text-xs font-bold uppercase tracking-[0.24em] text-stone-500">Rozwój</p>
                    <p class="mt-3 text-xl font-bold text-stone-950">Gotowość na kolejne potrzeby parafii</p>
                    <p class="mt-2 text-sm leading-7 text-stone-600">Usługa jest przygotowana do dalszego rozwoju, bez potrzeby budowania wszystkiego od nowa przy każdym kolejnym module.</p>
                </div>
            </div>
        </div>

        <div class="grid gap-5">
            <div class="panel px-6 py-6 sm:px-8">
                <span class="eyebrow">Bezpieczeństwo i prywatność</span>
                <h3 class="mt-4 text-3xl font-bold text-stone-950">Dane parafii i parafian muszą być pod właściwą opieką.</h3>
                <p class="mt-4 text-sm leading-7 text-stone-600">
                    Wspólnota jest projektowana z myślą o tym, że nie każda informacja powinna być publiczna i nie każda osoba powinna mieć dostęp do wszystkiego. To szczególnie ważne przy kancelarii online i dokumentach.
                </p>
                <ul class="mt-6 space-y-4 text-sm leading-7 text-stone-600">
                    <li>Dostęp jest rozdzielony pomiędzy zwykłego użytkownika, administratora parafii i superadministratora.</li>
                    <li>Pliki kancelarii online są oddzielone od treści publicznych i wymagają właściwego dostępu.</li>
                    <li>Potwierdzanie kont i kontrola uprawnień pomagają chronić funkcje, które nie powinny być dostępne dla każdego.</li>
                    <li>Jedna centralna usługa ułatwia pilnowanie porządku, bezpieczeństwa i dalszego dostosowania do wymogów europejskich.</li>
                </ul>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div class="panel px-6 py-6">
                    <p class="text-xs font-bold uppercase tracking-[0.24em] text-stone-500">Prywatne dyski</p>
                    <p class="mt-3 text-2xl font-bold text-stone-950">Oddzielenie treści publicznych od prywatnych</p>
                    <p class="mt-3 text-sm leading-7 text-stone-600">Inaczej traktowane są pliki do aktualności, a inaczej dokumenty i załączniki z kancelarii online.</p>
                </div>
                <div class="panel px-6 py-6">
                    <p class="text-xs font-bold uppercase tracking-[0.24em] text-stone-500">Kontrola dostępu</p>
                    <p class="mt-3 text-2xl font-bold text-stone-950">Właściwe funkcje dla właściwych osób</p>
                    <p class="mt-3 text-sm leading-7 text-stone-600">Dostęp do panelu, wiadomości i bardziej wrażliwych funkcji jest ograniczony do tych osób, które naprawdę powinny z nich korzystać.</p>
                </div>
            </div>
        </div>
    </section>

    <section id="cennik" class="space-y-6 py-10 lg:py-14">
        <div class="max-w-3xl">
            <span class="eyebrow">Pricing</span>
            <h2 class="mt-3 font-display text-4xl text-stone-950 sm:text-5xl">Model wdrożenia dopasowany do etapu parafii.</h2>
            <p class="mt-3 text-lg leading-8 text-stone-600">
                Na początku przewidujemy wdrożenia pilotażowe, a docelowo spokojny model abonamentowy dla parafii. Chcemy, żeby wejście do usługi było zrozumiałe i przewidywalne.
            </p>
        </div>

        <div class="grid gap-5 xl:grid-cols-3">
            <article class="pricing-card">
                <p class="text-sm font-semibold text-stone-500">Pilotaż</p>
                <h3 class="mt-3 text-3xl font-bold text-stone-950">0 zł</h3>
                <p class="mt-3 text-sm leading-7 text-stone-600">Dla parafii, które chcą wejść we współpracę na etapie uruchamiania usługi.</p>
                <ul class="mt-6 space-y-3 text-sm leading-6 text-stone-700">
                    <li>Panel dla proboszcza i podstawowe moduły usługi</li>
                    <li>Aplikacja dla wiernych w podstawowym zakresie</li>
                    <li>Wspólne dopracowanie wdrożenia w realiach parafii</li>
                </ul>
            </article>

            <article class="pricing-card pricing-card-featured">
                <p class="text-sm font-semibold text-stone-700">Abonament parafialny</p>
                <h3 class="mt-3 text-3xl font-bold text-stone-950">Po premierze</h3>
                <p class="mt-3 text-sm leading-7 text-stone-700">Docelowy model dla parafii, które chcą korzystać z usługi stale i rozwijać komunikację z wiernymi w uporządkowany sposób.</p>
                <ul class="mt-6 space-y-3 text-sm leading-6 text-stone-800">
                    <li>Pełniejszy pakiet komunikacji parafii z wiernymi</li>
                    <li>Rozwój kolejnych funkcji wraz z usługą</li>
                    <li>Aktualizacje i wsparcie we wdrożeniu</li>
                </ul>
            </article>

            <article class="pricing-card">
                <p class="text-sm font-semibold text-stone-500">Wdrożenie indywidualne</p>
                <h3 class="mt-3 text-3xl font-bold text-stone-950">Kontakt</h3>
                <p class="mt-3 text-sm leading-7 text-stone-600">Dla większych wdrożeń, kilku parafii albo sytuacji, w których zakres prac trzeba ustalić indywidualnie.</p>
                <ul class="mt-6 space-y-3 text-sm leading-6 text-stone-700">
                    <li>Ustalenie potrzeb i harmonogramu krok po kroku</li>
                    <li>Pomoc przy konfiguracji i przygotowaniu materiałów</li>
                    <li>Bezpośredni kontakt roboczy</li>
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
                        Pokażemy, od czego warto zacząć, omówimy potrzeby parafii i dobierzemy taki zakres usługi, który naprawdę pomoże w codziennym funkcjonowaniu.
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
