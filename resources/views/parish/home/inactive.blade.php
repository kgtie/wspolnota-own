@extends('layouts.parish')

@section('title', $parish->short_name . ' • Wspólnota')
@section('meta_description', 'Ta parafia nie aktywowała jeszcze swojej publicznej strony we Wspólnocie. Sprawdź podstawowe informacje kontaktowe i delikatnie zasugeruj wdrożenie usługi.')
@section('canonical_url', route('parish.home', ['subdomain' => $parish]))
@section('meta_image', $coverImageUrl ?: $avatarUrl ?: '')
@section('og_type', 'website')
@section('robots', 'noindex,follow,max-snippet:-1,max-image-preview:large,max-video-preview:-1')

@section('content')
    @php
        $helpPoints = collect([
            [
                'title' => 'Jedno spokojne miejsce na bieżące informacje',
                'body' => 'Ogłoszenia, aktualności i najbliższe msze święte trafiają do wiernych w uporządkowanej formie, bez chaosu i bez potrzeby szukania informacji w kilku kanałach.',
            ],
            [
                'title' => 'Mniej pytań organizacyjnych, więcej przejrzystości',
                'body' => 'Dobrze ułożona publiczna strona ogranicza powtarzalne pytania o podstawowe sprawy parafii i porządkuje komunikację wokół tego, co dzieje się teraz.',
            ],
            [
                'title' => 'Nowoczesna obecność parafii bez budowania wszystkiego od zera',
                'body' => 'Wspólnota daje gotową, estetyczną warstwę informacyjną, którą parafia może uruchomić szybciej niż tradycyjną stronę i prowadzić ją bez technologicznego ciężaru.',
            ],
        ]);

        $priestInterestModalOpen = $errors->priestInterest->isNotEmpty();
    @endphp

    <section class="relative overflow-hidden rounded-[2.2rem] border border-white/60 shadow-[0_36px_100px_rgba(58,40,24,0.14)]">
        @if ($coverImageUrl)
            <img src="{{ $coverImageUrl }}" alt="{{ $parish->short_name }}" class="absolute inset-0 h-full w-full object-cover">
        @else
            <div class="parish-hero-fallback-inactive absolute inset-0"></div>
        @endif

        <div class="parish-hero-overlay-inactive absolute inset-0"></div>

        <div class="relative grid gap-8 px-6 py-8 sm:px-8 sm:py-10 lg:grid-cols-[1.18fr_0.82fr] lg:px-10 lg:py-12">
            <div class="max-w-3xl">
                <span class="eyebrow">Parafia w przygotowaniu</span>

                <h1 class="mt-6 font-display text-4xl leading-tight text-stone-950 sm:text-5xl">
                    {{ $parish->short_name }} jeszcze nie uruchomiła swojej strony we Wspólnocie.
                </h1>

                <p class="mt-5 max-w-2xl text-base leading-8 text-stone-600 sm:text-lg">
                    Kiedy parafia aktywuje usługę, w tym miejscu pojawi się spokojna, uporządkowana przestrzeń na ogłoszenia, najbliższe msze święte i aktualności z życia wspólnoty.
                </p>

                <div class="mt-8 flex flex-wrap gap-3">
                    @if ($suggestionMailtoUrl)
                        <a href="{{ $suggestionMailtoUrl }}" class="inline-flex items-center rounded-full px-5 py-3 text-sm font-semibold text-white shadow-lg transition hover:opacity-90" style="background-color: var(--parish-accent);">
                            Delikatnie podpowiedz proboszczowi
                        </a>
                    @endif

                    <button
                        type="button"
                        x-data
                        x-on:click="$dispatch('open-priest-interest')"
                        class="inline-flex items-center rounded-full border border-stone-300 bg-white/85 px-5 py-3 text-sm font-semibold text-stone-700 transition hover:border-stone-950 hover:text-stone-950"
                    >
                        Jestem proboszczem, chcę wiedzieć więcej
                    </button>

                    <a href="{{ route('landing.home') }}" class="inline-flex items-center rounded-full border border-stone-300 bg-white/85 px-5 py-3 text-sm font-semibold text-stone-700 transition hover:border-stone-950 hover:text-stone-950">
                        Zobacz czym jest Wspólnota
                    </a>
                </div>
            </div>

            <div class="flex items-end">
                <div class="w-full rounded-[2rem] border border-white/65 bg-white/76 p-6 shadow-[0_28px_70px_rgba(58,40,24,0.12)] backdrop-blur-xl">
                    <div class="flex items-center gap-4">
                        <div class="flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-[1.6rem] border border-stone-200 bg-stone-50 shadow-sm">
                            @if ($avatarUrl)
                                <img src="{{ $avatarUrl }}" alt="{{ $parish->short_name }}" class="h-full w-full object-cover">
                            @else
                                <span class="font-display text-2xl text-stone-700">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($parish->short_name, 0, 1)) }}</span>
                            @endif
                        </div>

                        <div>
                            <p class="text-xs font-semibold tracking-[0.2em] text-stone-500 uppercase">Profil parafii</p>
                            <h2 class="mt-1 text-2xl font-semibold text-stone-950">{{ $parish->name }}</h2>
                            <p class="text-sm text-stone-600">{{ $parish->city }}</p>
                        </div>
                    </div>

                    <dl class="mt-6 space-y-4 text-sm">
                        @if ($addressLines->isNotEmpty())
                            <div>
                                <dt class="font-semibold text-stone-900">Adres</dt>
                                <dd class="mt-1 leading-7 text-stone-600">{{ $addressLines->implode(', ') }}</dd>
                            </div>
                        @endif

                        @if ($parish->email)
                            <div>
                                <dt class="font-semibold text-stone-900">Email kontaktowy</dt>
                                <dd class="mt-1 text-stone-600">
                                    <a href="mailto:{{ $parish->email }}" class="transition hover:text-stone-950">{{ $parish->email }}</a>
                                </dd>
                            </div>
                        @endif

                        @if ($websiteUrl)
                            <div>
                                <dt class="font-semibold text-stone-900">Dotychczasowa strona</dt>
                                <dd class="mt-1 text-stone-600">
                                    <a href="{{ $websiteUrl }}" target="_blank" rel="noreferrer" class="transition hover:text-stone-950">
                                        {{ preg_replace('#^https?://#', '', $websiteUrl) }}
                                    </a>
                                </dd>
                            </div>
                        @endif
                    </dl>
                </div>
            </div>
        </div>
    </section>

    <div class="mt-10 grid gap-8 lg:grid-cols-[minmax(0,1.1fr)_minmax(320px,0.9fr)]">
        <section class="parish-surface p-6 sm:p-8">
            <div class="border-b border-stone-200/80 pb-6">
                <p class="text-xs font-semibold tracking-[0.22em] text-stone-500 uppercase">Dlaczego warto uruchomić usługę</p>
                <h2 class="mt-2 font-display text-3xl text-stone-950">Lekka strona informacyjna, realna korzyść dla parafii</h2>
                <p class="mt-3 max-w-2xl text-sm leading-7 text-stone-600">
                    Publiczna warstwa Wspólnoty nie zastępuje tożsamości parafii. Ona porządkuje komunikację, daje czytelne miejsce na bieżące informacje i pomaga parafii być obecnej tam, gdzie wierni naprawdę szukają konkretów.
                </p>
            </div>

            <div class="mt-6 grid gap-4 md:grid-cols-3">
                @foreach ($helpPoints as $point)
                    <article class="rounded-[1.8rem] border border-stone-200/80 bg-white/85 p-5 shadow-[0_20px_55px_rgba(58,40,24,0.05)]">
                        <div class="flex h-11 w-11 items-center justify-center rounded-2xl text-sm font-semibold text-white" style="background-color: var(--parish-accent);">
                            {{ str_pad((string) ($loop->iteration), 2, '0', STR_PAD_LEFT) }}
                        </div>
                        <h3 class="mt-4 text-lg font-semibold leading-7 text-stone-950">{{ $point['title'] }}</h3>
                        <p class="mt-3 text-sm leading-7 text-stone-600">{{ $point['body'] }}</p>
                    </article>
                @endforeach
            </div>

            <div class="parish-accent-panel mt-6 overflow-hidden rounded-[1.8rem] p-6">
                <p class="text-xs font-semibold tracking-[0.22em] text-[color:var(--parish-accent)] uppercase">Efekt dla parafii</p>
                <p class="mt-3 max-w-3xl text-sm leading-8 text-stone-700">
                    Z perspektywy proboszcza to nie jest kolejna „technologia do obsługi”. To prostszy sposób, by ogłoszenia były widoczne, najważniejsze informacje nie ginęły, a publiczna obecność parafii wyglądała nowocześnie, spokojnie i wiarygodnie.
                </p>
            </div>
        </section>

        <aside class="space-y-8">
            <section class="parish-accent-panel overflow-hidden rounded-[2rem] p-6 shadow-[0_20px_60px_rgba(58,40,24,0.08)]">
                <p class="text-xs font-semibold tracking-[0.22em] text-[color:var(--parish-accent)] uppercase">Delikatna sugestia</p>
                <h2 class="mt-2 font-display text-2xl text-stone-950">Jeśli należysz do tej parafii, możesz po prostu dać znać.</h2>
                <p class="mt-4 text-sm leading-7 text-stone-700">
                    Bez presji i bez nachalności. Czasem wystarczy zwykła wiadomość do proboszcza, że taka forma komunikacji mogłaby pomóc parafii publikować informacje w bardziej uporządkowany sposób.
                </p>

                @if ($suggestionMailtoUrl)
                    <a href="{{ $suggestionMailtoUrl }}" class="mt-6 inline-flex items-center rounded-full border border-transparent bg-white px-4 py-3 text-sm font-semibold text-stone-900 shadow-sm transition hover:shadow-md">
                        Otwórz wiadomość email
                    </a>
                @endif

                <button
                    type="button"
                    x-data
                    x-on:click="$dispatch('open-priest-interest')"
                    class="mt-3 inline-flex items-center rounded-full border border-stone-300 bg-white px-4 py-3 text-sm font-semibold text-stone-900 shadow-sm transition hover:shadow-md"
                >
                    Proboszcz chce kontaktu
                </button>
            </section>

            <section class="parish-surface p-6">
                <p class="text-xs font-semibold tracking-[0.22em] text-stone-500 uppercase">Kontakt</p>
                <h2 class="mt-2 font-display text-2xl text-stone-950">Podstawowe dane parafii</h2>

                <dl class="mt-6 space-y-5 text-sm">
                    @if ($parish->diocese)
                        <div>
                            <dt class="font-semibold text-stone-900">Diecezja</dt>
                            <dd class="mt-1 text-stone-600">{{ $parish->diocese }}</dd>
                        </div>
                    @endif

                    @if ($parish->decanate)
                        <div>
                            <dt class="font-semibold text-stone-900">Dekanat</dt>
                            <dd class="mt-1 text-stone-600">{{ $parish->decanate }}</dd>
                        </div>
                    @endif

                    @if ($parish->phone)
                        <div>
                            <dt class="font-semibold text-stone-900">Telefon</dt>
                            <dd class="mt-1 text-stone-600">
                                <a href="tel:{{ preg_replace('/\s+/', '', $parish->phone) }}" class="transition hover:text-stone-950">{{ $parish->phone }}</a>
                            </dd>
                        </div>
                    @endif

                    @if ($parish->email)
                        <div>
                            <dt class="font-semibold text-stone-900">Email</dt>
                            <dd class="mt-1 text-stone-600">
                                <a href="mailto:{{ $parish->email }}" class="transition hover:text-stone-950">{{ $parish->email }}</a>
                            </dd>
                        </div>
                    @endif
                </dl>
            </section>
        </aside>
    </div>
@endsection

@section('structured_data')
    @php
        $structuredData = [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'Church',
                    '@id' => route('parish.home', ['subdomain' => $parish]).'#church',
                    'name' => $parish->name,
                    'url' => route('parish.home', ['subdomain' => $parish]),
                    'email' => $parish->email,
                    'telephone' => $parish->phone,
                    'image' => array_values(array_filter([$coverImageUrl ?: null, $avatarUrl ?: null])),
                    'address' => [
                        '@type' => 'PostalAddress',
                        'streetAddress' => $parish->street,
                        'postalCode' => $parish->postal_code,
                        'addressLocality' => $parish->city,
                        'addressCountry' => 'PL',
                    ],
                ],
                [
                    '@type' => 'WebPage',
                    '@id' => route('parish.home', ['subdomain' => $parish]).'#webpage',
                    'url' => route('parish.home', ['subdomain' => $parish]),
                    'name' => $parish->short_name.' - parafia we Wspólnocie',
                    'description' => trim($__env->yieldContent('meta_description')),
                    'about' => [
                        '@id' => route('parish.home', ['subdomain' => $parish]).'#church',
                    ],
                    'isPartOf' => [
                        '@type' => 'WebSite',
                        'name' => 'Wspólnota',
                        'url' => route('landing.home'),
                    ],
                ],
            ],
        ];
    @endphp

    <script type="application/ld+json">
        {!! json_encode($structuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
    </script>
@endsection

@section('modals')
    <div
        x-data="{ open: @js($priestInterestModalOpen) }"
        x-cloak
        x-on:open-priest-interest.window="open = true"
        x-on:keydown.escape.window="open = false"
        x-show="open"
        x-transition.opacity.duration.200ms
        class="fixed inset-0 z-50 flex items-center justify-center px-4 py-6 sm:px-6"
    >
        <div class="absolute inset-0 bg-stone-950/60 backdrop-blur-sm" @click="open = false"></div>

        <div
            x-show="open"
            x-transition.scale.duration.200ms
            class="parish-surface relative z-10 w-full max-w-2xl p-6 sm:p-8"
        >
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold tracking-[0.22em] text-stone-500 uppercase">Dla proboszcza</p>
                    <h2 class="mt-2 font-display text-3xl text-stone-950">Czy na pewno mamy się odezwać?</h2>
                </div>

                <button type="button" @click="open = false" class="rounded-full border border-stone-300 bg-white/80 px-3 py-2 text-sm font-semibold text-stone-700 transition hover:border-stone-950 hover:text-stone-950">
                    Zamknij
                </button>
            </div>

            <div class="mt-6 space-y-4 text-sm leading-7 text-stone-600">
                <p>
                    Jeśli tak, wyślemy sygnał do superadministratora Wspólnoty, że parafia <span class="font-semibold text-stone-900">{{ $parish->short_name }}</span> chce poznać usługę lub porozmawiać o jej uruchomieniu.
                </p>
                <p>
                    Nie trzeba niczego opisywać. Po potwierdzeniu otrzymamy informację i skontaktujemy się samodzielnie, korzystając z danych parafii zapisanych w systemie.
                </p>
            </div>

            <form action="{{ route('parish.interest.store', ['subdomain' => $parish]) }}" method="POST" class="mt-8">
                @csrf
                <input type="hidden" name="confirmation" value="1">

                @if ($errors->priestInterest->has('confirmation'))
                    <p class="landing-error">{{ $errors->priestInterest->first('confirmation') }}</p>
                @endif

                <div class="rounded-[1.6rem] border border-stone-200/80 bg-stone-50 p-5">
                    <p class="text-sm font-semibold text-stone-900">Kontakt zostanie zgłoszony dla:</p>
                    <dl class="mt-4 grid gap-3 text-sm text-stone-600 sm:grid-cols-2">
                        <div>
                            <dt class="font-semibold text-stone-900">Parafia</dt>
                            <dd class="mt-1">{{ $parish->name }}</dd>
                        </div>
                        <div>
                            <dt class="font-semibold text-stone-900">Miejscowość</dt>
                            <dd class="mt-1">{{ $parish->city }}</dd>
                        </div>
                        @if ($parish->email)
                            <div>
                                <dt class="font-semibold text-stone-900">Email</dt>
                                <dd class="mt-1">{{ $parish->email }}</dd>
                            </div>
                        @endif
                        @if ($parish->phone)
                            <div>
                                <dt class="font-semibold text-stone-900">Telefon</dt>
                                <dd class="mt-1">{{ $parish->phone }}</dd>
                            </div>
                        @endif
                    </dl>
                </div>

                <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-end">
                    <button type="button" @click="open = false" class="inline-flex items-center justify-center rounded-full border border-stone-300 bg-white/80 px-5 py-3 text-sm font-semibold text-stone-700 transition hover:border-stone-950 hover:text-stone-950">
                        Jeszcze nie teraz
                    </button>
                    <button type="submit" class="inline-flex items-center justify-center rounded-full px-5 py-3 text-sm font-semibold text-white shadow-lg transition hover:opacity-90" style="background-color: var(--parish-accent);">
                        Tak, proszę o kontakt
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
