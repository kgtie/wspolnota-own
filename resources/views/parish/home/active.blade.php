@extends('layouts.parish')

@section('title', $parish->short_name . ' • Portal parafii')
@section('meta_description', 'Aktualne ogłoszenia, najbliższe msze święte i najświeższe informacje z życia parafii ' . $parish->short_name . '.')
@section('canonical_url', route('parish.home', ['subdomain' => $parish]))
@section('meta_image', $coverImageUrl ?: $avatarUrl ?: '')
@section('og_type', 'website')
@section('robots', 'index,follow,max-snippet:-1,max-image-preview:large,max-video-preview:-1')

@section('content')
    @php
        $heroDescription = collect([
            'Miejsce, w którym parafia może spokojnie publikować bieżące informacje dla swojej wspólnoty.',
            $currentAnnouncement?->lead,
        ])->filter()->first();

        $quickStats = collect([
            [
                'label' => 'Ogłoszenia',
                'value' => $currentAnnouncement?->items?->count() ?? 0,
                'description' => $currentAnnouncement ? 'na bieżący tydzień' : 'wkrótce pojawią się tutaj',
            ],
            [
                'label' => 'Msze przed nami',
                'value' => $nextMasses->count(),
                'description' => 'najbliższe celebracje',
            ],
            [
                'label' => 'Aktualności',
                'value' => $latestNews->count(),
                'description' => 'ostatnie wpisy parafii',
            ],
        ]);
    @endphp

    <section class="relative overflow-hidden rounded-[2.2rem] border border-white/60 shadow-[0_36px_100px_rgba(58,40,24,0.14)]">
        @if ($coverImageUrl)
            <img src="{{ $coverImageUrl }}" alt="{{ $parish->short_name }}" class="absolute inset-0 h-full w-full object-cover">
        @else
            <div class="parish-hero-fallback-active absolute inset-0"></div>
        @endif

        <div class="parish-hero-overlay-active absolute inset-0"></div>

        <div class="relative grid gap-8 px-6 py-8 sm:px-8 sm:py-10 lg:grid-cols-[1.3fr_0.7fr] lg:px-10 lg:py-12">
            <div class="max-w-3xl text-white">
                <span class="inline-flex rounded-full border border-white/20 bg-white/10 px-4 py-2 text-xs font-semibold tracking-[0.24em] uppercase text-white/90 backdrop-blur">
                    Publiczna strona parafii
                </span>

                <h1 class="mt-6 font-display text-4xl leading-tight sm:text-5xl lg:text-6xl">
                    {{ $parish->name }}
                </h1>

                <p class="mt-5 max-w-2xl text-base leading-8 text-stone-100 sm:text-lg">
                    {{ $heroDescription }}
                </p>

                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="#ogloszenia" class="inline-flex items-center rounded-full px-5 py-3 text-sm font-semibold text-white shadow-lg transition hover:opacity-90" style="background-color: var(--parish-accent);">
                        Zobacz ogłoszenia
                    </a>

                    @if ($publicEmail)
                        <a href="mailto:{{ $publicEmail }}" class="inline-flex items-center rounded-full border border-white/30 bg-white/10 px-5 py-3 text-sm font-semibold text-white backdrop-blur transition hover:bg-white/16">
                            Kontakt z parafią
                        </a>
                    @endif
                </div>

                <div class="mt-8 grid gap-3 sm:grid-cols-3">
                    @foreach ($quickStats as $stat)
                        <div class="rounded-3xl border border-white/14 bg-white/10 px-4 py-4 backdrop-blur-md">
                            <p class="text-xs font-semibold tracking-[0.22em] text-white/70 uppercase">{{ $stat['label'] }}</p>
                            <p class="mt-3 text-3xl font-semibold text-white">{{ $stat['value'] }}</p>
                            <p class="mt-1 text-sm text-white/75">{{ $stat['description'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="flex items-end">
                <div class="w-full rounded-[2rem] border border-white/18 bg-white/12 p-6 text-white shadow-[0_30px_90px_rgba(15,23,42,0.16)] backdrop-blur-xl">
                    <div class="flex items-center gap-4">
                        <div class="flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-[1.6rem] border border-white/20 bg-white/80 text-stone-900 shadow-sm">
                            @if ($avatarUrl)
                                <img src="{{ $avatarUrl }}" alt="{{ $parish->short_name }}" class="h-full w-full object-cover">
                            @else
                                <span class="font-display text-2xl">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($parish->short_name, 0, 1)) }}</span>
                            @endif
                        </div>

                        <div>
                            <p class="text-xs font-semibold tracking-[0.2em] text-white/65 uppercase">Parafia we Wspólnocie</p>
                            <h2 class="mt-1 text-2xl font-semibold">{{ $parish->short_name }}</h2>
                            <p class="text-sm text-white/75">{{ $parish->city }}</p>
                        </div>
                    </div>

                    <dl class="mt-6 space-y-4 text-sm">
                        @if ($publicAddressLines->isNotEmpty())
                            <div>
                                <dt class="text-white/60">Adres</dt>
                                <dd class="mt-1 leading-7">{{ $publicAddressLines->implode(', ') }}</dd>
                            </div>
                        @endif

                        @if ($parish->diocese)
                            <div>
                                <dt class="text-white/60">Diecezja</dt>
                                <dd class="mt-1">{{ $parish->diocese }}</dd>
                            </div>
                        @endif

                        @if ($parish->decanate)
                            <div>
                                <dt class="text-white/60">Dekanat</dt>
                                <dd class="mt-1">{{ $parish->decanate }}</dd>
                            </div>
                        @endif

                        @if ($parish->activated_at)
                            <div>
                                <dt class="text-white/60">Obecna we Wspólnocie od</dt>
                                <dd class="mt-1">{{ $parish->activated_at->translatedFormat('j F Y') }}</dd>
                            </div>
                        @endif
                    </dl>
                </div>
            </div>
        </div>
    </section>

    <div class="mt-10 grid gap-8 lg:grid-cols-[minmax(0,1.35fr)_minmax(320px,0.8fr)]">
        <div class="space-y-8">
            <section id="ogloszenia" class="parish-surface p-6 sm:p-8">
                <div class="flex flex-col gap-4 border-b border-stone-200/80 pb-6 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-xs font-semibold tracking-[0.22em] text-stone-500 uppercase">Ogłoszenia duszpasterskie</p>
                        <h2 class="mt-2 font-display text-3xl text-stone-950">To, co najważniejsze na teraz</h2>
                    </div>

                    @if ($currentAnnouncement)
                        <div class="rounded-2xl border border-stone-200 bg-stone-50 px-4 py-3 text-sm text-stone-600">
                            <p class="font-semibold text-stone-900">{{ $currentAnnouncement->week_label ?: $currentAnnouncement->title }}</p>
                            <p class="mt-1">
                                {{ optional($currentAnnouncement->effective_from)?->translatedFormat('j F') }}
                                @if ($currentAnnouncement->effective_to)
                                    – {{ $currentAnnouncement->effective_to->translatedFormat('j F Y') }}
                                @endif
                            </p>
                        </div>
                    @endif
                </div>

                @if ($currentAnnouncement)
                    <div class="parish-accent-panel mt-6 rounded-[1.8rem] p-5">
                        <p class="text-sm font-semibold tracking-[0.18em] text-[color:var(--parish-accent)] uppercase">Wprowadzenie</p>
                        <p class="mt-3 text-sm leading-7 text-stone-700">
                            {{ $currentAnnouncement->summary_ai ?: ($currentAnnouncement->lead ?: 'Parafia przygotowała aktualny zestaw informacji na bieżący tydzień.') }}
                        </p>
                    </div>

                    @if ($currentAnnouncement->items->isNotEmpty())
                        <div class="mt-4 flex flex-wrap gap-3">
                            <a
                                href="{{ route('parish.announcements.pdf', ['subdomain' => $parish]) }}"
                                class="inline-flex items-center rounded-full px-5 py-3 text-sm font-semibold text-white shadow-lg transition hover:opacity-90"
                                style="background-color: var(--parish-accent);"
                            >
                                Pobierz ogłoszenia w PDF
                            </a>
                            <p class="flex items-center text-sm text-stone-500">
                                Przydatne do wydruku, udostępnienia lub archiwizacji bieżącego zestawu.
                            </p>
                        </div>
                    @endif

                    <div class="mt-6 grid gap-4">
                        @forelse ($currentAnnouncement->items as $item)
                            <article class="rounded-[1.8rem] border border-stone-200/80 bg-white/80 p-5 shadow-[0_20px_55px_rgba(58,40,24,0.05)]">
                                <div class="flex gap-4">
                                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-stone-100 text-sm font-bold text-stone-500">
                                        {{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-3">
                                            <h3 class="text-lg font-semibold text-stone-950">
                                                {{ $item->title ?: 'Ogłoszenie parafialne' }}
                                            </h3>

                                            @if ($item->is_important)
                                                <span class="rounded-full px-3 py-1 text-xs font-semibold uppercase text-white" style="background-color: var(--parish-accent);">
                                                    Ważne
                                                </span>
                                            @endif
                                        </div>

                                        @if ($item->content)
                                            <p class="mt-3 text-sm leading-7 text-stone-600">
                                                {{ \Illuminate\Support\Str::limit(\Illuminate\Support\Str::of(strip_tags($item->content))->squish()->toString(), 340) }}
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            </article>
                        @empty
                            <div class="rounded-[1.8rem] border border-dashed border-stone-300 bg-stone-50 px-5 py-6 text-sm leading-7 text-stone-600">
                                Ten zestaw ogłoszeń został już przygotowany, ale pojedyncze wpisy nie są jeszcze opublikowane.
                            </div>
                        @endforelse
                    </div>

                    @if ($currentAnnouncement->footer_notes)
                        <div class="mt-6 rounded-[1.8rem] border border-dashed border-stone-300 bg-stone-50 px-5 py-5">
                            <p class="text-xs font-semibold tracking-[0.22em] text-stone-500 uppercase">Dopowiedzenie</p>
                            <p class="mt-3 text-sm leading-7 text-stone-600">
                                {{ \Illuminate\Support\Str::limit(\Illuminate\Support\Str::of(strip_tags($currentAnnouncement->footer_notes))->squish()->toString(), 360) }}
                            </p>
                        </div>
                    @endif
                @else
                    <div class="mt-6 rounded-[1.8rem] border border-dashed border-stone-300 bg-stone-50 px-6 py-8">
                        <p class="text-lg font-semibold text-stone-900">Parafia przygotowuje ogłoszenia.</p>
                        <p class="mt-3 max-w-2xl text-sm leading-7 text-stone-600">
                            Gdy pojawi się nowy zestaw informacji, właśnie tutaj będą widoczne najważniejsze wiadomości na bieżący tydzień.
                        </p>
                    </div>
                @endif
            </section>

            <section class="parish-surface p-6 sm:p-8" x-data="{ open: false, selected: null }" x-on:keydown.escape.window="open = false">
                <div class="flex flex-col gap-3 border-b border-stone-200/80 pb-6 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-xs font-semibold tracking-[0.22em] text-stone-500 uppercase">Aktualności</p>
                        <h2 class="mt-2 font-display text-3xl text-stone-950">Co dzieje się w parafii</h2>
                    </div>
                    <p class="max-w-xl text-sm leading-7 text-stone-600">
                        Lżejsza warstwa informacyjna parafii: krótkie wpisy, najnowsze wiadomości i ważne momenty z życia wspólnoty.
                    </p>
                </div>

                @if ($latestNews->isNotEmpty())
                    <div class="mt-6 grid gap-5 md:grid-cols-2">
                        @foreach ($latestNews as $post)
                            @php
                                $newsImageUrl = $post->getFirstMediaUrl('featured_image', 'preview') ?: null;
                                $newsExcerpt = \Illuminate\Support\Str::limit(\Illuminate\Support\Str::of(strip_tags($post->content))->squish()->toString(), 220);
                                $publishedAt = $post->published_at ?? $post->created_at;
                            @endphp

                            <article class="overflow-hidden rounded-[1.8rem] border border-stone-200/80 bg-white/85 shadow-[0_22px_60px_rgba(58,40,24,0.06)]">
                                <button
                                    type="button"
                                    class="block w-full text-left"
                                    aria-label="Czytaj aktualność: {{ $post->title }}"
                                    @click="selected = {{ (int) $post->getKey() }}; open = true"
                                >
                                    <div class="relative h-52 overflow-hidden bg-[linear-gradient(135deg,_rgba(76,94,63,0.18),_rgba(184,115,51,0.22))]">
                                        @if ($newsImageUrl)
                                            <img src="{{ $newsImageUrl }}" alt="{{ $post->title }}" class="h-full w-full object-cover">
                                        @else
                                            <div class="flex h-full items-end p-5">
                                                <span class="rounded-full border border-white/70 bg-white/75 px-3 py-1 text-xs font-semibold tracking-[0.18em] text-stone-700 uppercase">
                                                    {{ $parish->short_name }}
                                                </span>
                                            </div>
                                        @endif
                                    </div>

                                    <div class="p-5">
                                        <div class="flex flex-wrap items-center gap-2 text-xs font-semibold tracking-[0.18em] text-stone-500 uppercase">
                                            <span>{{ $publishedAt?->translatedFormat('j F Y') }}</span>
                                            @if ($post->is_pinned)
                                                <span class="rounded-full px-2.5 py-1 text-white" style="background-color: var(--parish-accent);">
                                                    Wyróżnione
                                                </span>
                                            @endif
                                        </div>

                                        <h3 class="mt-4 text-xl font-semibold text-stone-950">{{ $post->title }}</h3>

                                        <p class="mt-3 text-sm leading-7 text-stone-600">
                                            {{ $newsExcerpt ?: 'Nowa wiadomość z życia parafii pojawiła się właśnie na stronie parafialnej.' }}
                                        </p>

                                        <div class="mt-5 flex items-center justify-between text-sm text-stone-500">
                                            <span>{{ $post->comments_count }} komentarzy</span>
                                            <span class="font-semibold text-stone-700">Czytaj całość</span>
                                        </div>
                                    </div>
                                </button>
                            </article>
                        @endforeach
                    </div>
                @else
                    <div class="mt-6 rounded-[1.8rem] border border-dashed border-stone-300 bg-stone-50 px-6 py-8">
                        <p class="text-lg font-semibold text-stone-900">Aktualności jeszcze przed nami.</p>
                        <p class="mt-3 max-w-2xl text-sm leading-7 text-stone-600">
                            Gdy parafia opublikuje pierwsze wpisy, pojawią się tutaj w układzie lekkiego portalu informacyjnego.
                        </p>
                    </div>
                @endif

                <div
                    x-cloak
                    x-show="open"
                    x-transition.opacity.duration.200ms
                    class="fixed inset-0 z-50 flex items-center justify-center px-4 py-6 sm:px-6"
                >
                    <div class="absolute inset-0 bg-stone-950/60 backdrop-blur-sm" @click="open = false"></div>

                    <div
                        x-show="open"
                        x-transition.scale.duration.200ms
                        class="parish-surface relative z-10 max-h-[84vh] w-full max-w-3xl overflow-hidden"
                    >
                        <template x-for="postId in [selected]" :key="postId">
                            <div class="max-h-[84vh] overflow-y-auto">
                                @foreach ($latestNews as $post)
                                    @php
                                        $newsImageUrl = $post->getFirstMediaUrl('featured_image', 'preview') ?: null;
                                        $publishedAt = $post->published_at ?? $post->created_at;
                                    @endphp

                                    <article x-show="postId === {{ (int) $post->getKey() }}" class="p-0">
                                        @if ($newsImageUrl)
                                            <div class="h-48 overflow-hidden sm:h-60">
                                                <img src="{{ $newsImageUrl }}" alt="{{ $post->title }}" class="h-full w-full object-cover">
                                            </div>
                                        @endif

                                        <div class="p-5 sm:p-6">
                                            <div class="flex items-start justify-between gap-4">
                                                <div>
                                                    <p class="text-xs font-semibold tracking-[0.18em] text-stone-500 uppercase">
                                                        Aktualność parafii • {{ $publishedAt?->translatedFormat('j F Y') }}
                                                    </p>
                                                    <h3 class="mt-2 font-display text-2xl leading-tight text-stone-950 sm:text-3xl">{{ $post->title }}</h3>
                                                </div>

                                                <button type="button" @click="open = false" class="rounded-full border border-stone-300 bg-white/80 px-3 py-2 text-sm font-semibold text-stone-700 transition hover:border-stone-950 hover:text-stone-950">
                                                    Zamknij
                                                </button>
                                            </div>

                                            <div class="parish-richtext mt-6 text-[0.98rem]">
                                                {!! $post->content !!}
                                            </div>

                                            <div class="parish-accent-panel mt-6 rounded-[1.5rem] p-4">
                                                <p class="text-sm font-semibold text-stone-900">
                                                    Komentarze i galeria do tej aktualności są dostępne w aplikacji mobilnej Wspólnota.
                                                </p>
                                                <p class="mt-1.5 text-sm leading-7 text-stone-600">
                                                    Na stronie publicznej pokazujemy samą treść wpisu, a pełniejsze doświadczenie parafii jest dostępne w aplikacji.
                                                </p>
                                                <div class="mt-3">
                                                    @include('parish.partials.store-badges', [
                                                        'wrapperClass' => 'grid gap-2 sm:grid-cols-2',
                                                        'compact' => true,
                                                    ])
                                                </div>
                                            </div>
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                        </template>
                    </div>
                </div>
            </section>
        </div>

        <aside class="space-y-8">
            <section class="parish-surface p-6">
                <p class="text-xs font-semibold tracking-[0.22em] text-stone-500 uppercase">Informacje parafialne</p>
                <h2 class="mt-2 font-display text-2xl text-stone-950">Kontakt i podstawowe dane</h2>

                <dl class="mt-6 space-y-5 text-sm">

                    @if ($publicEmail)
                        <div>
                            <dt class="font-semibold text-stone-900">Email</dt>
                            <dd class="mt-1 text-stone-600">
                                <a href="mailto:{{ $publicEmail }}" class="transition hover:text-stone-950">{{ $publicEmail }}</a>
                            </dd>
                        </div>
                    @endif

                    @if ($publicPhone)
                        <div>
                            <dt class="font-semibold text-stone-900">Telefon</dt>
                            <dd class="mt-1 text-stone-600">
                                <a href="tel:{{ preg_replace('/\s+/', '', $publicPhone) }}" class="transition hover:text-stone-950">{{ $publicPhone }}</a>
                            </dd>
                        </div>
                    @endif

                    @if ($publicWebsiteUrl)
                        <div>
                            <dt class="font-semibold text-stone-900">Strona WWW</dt>
                            <dd class="mt-1 text-stone-600">
                                <a href="{{ $publicWebsiteUrl }}" target="_blank" rel="noreferrer" class="transition hover:text-stone-950">
                                    {{ preg_replace('#^https?://#', '', $publicWebsiteUrl) }}
                                </a>
                            </dd>
                        </div>
                    @endif
                </dl>
            </section>

            @if (! empty($parish->staff_members_list))
                <section class="parish-surface p-6">
                    <p class="text-xs font-semibold tracking-[0.22em] text-stone-500 uppercase">Osoby w parafii</p>
                    <h2 class="mt-2 font-display text-2xl text-stone-950">Duszpasterze i posługa</h2>
                    <p class="mt-4 text-sm leading-7 text-stone-600">
                        Publicznie wskazane osoby pełniące konkretne role w parafii.
                    </p>

                    <div class="mt-5 space-y-3">
                        @foreach ($parish->staff_members_list as $member)
                            <article class="rounded-[1.6rem] border border-stone-200/80 bg-white/80 px-4 py-4 shadow-[0_16px_36px_rgba(58,40,24,0.05)]">
                                <p class="text-base font-semibold text-stone-950">{{ $member['name'] }}</p>
                                <p class="mt-1 text-sm text-stone-600">{{ $member['title'] }}</p>
                            </article>
                        @endforeach
                    </div>
                </section>
            @endif

            <section class="parish-surface p-6">
                <div class="flex items-center justify-between gap-4 border-b border-stone-200/80 pb-5">
                    <div>
                        <p class="text-xs font-semibold tracking-[0.22em] text-stone-500 uppercase">Najbliższe msze święte</p>
                        <h2 class="mt-2 font-display text-2xl text-stone-950">Najbliższy kalendarz</h2>
                    </div>
                    <div class="rounded-2xl bg-stone-100 px-4 py-3 text-center">
                        <p class="text-2xl font-semibold text-stone-950">{{ $nextMasses->count() }}</p>
                        <p class="text-xs text-stone-500">pozycji</p>
                    </div>
                </div>

                @if ($nextMasses->isNotEmpty())
                    <div class="mt-5 space-y-4">
                        @foreach ($nextMasses as $mass)
                            <article class="rounded-[1.6rem] border border-stone-200/80 bg-white/80 p-4">
                                <div class="flex gap-4">
                                    <div class="rounded-2xl bg-stone-100 px-4 py-3 text-center">
                                        <p class="text-xs font-semibold tracking-[0.16em] text-stone-500 uppercase">{{ $mass->celebration_at?->translatedFormat('D') }}</p>
                                        <p class="mt-1 text-2xl font-semibold text-stone-950">{{ $mass->celebration_at?->format('d') }}</p>
                                        <p class="text-xs text-stone-500">{{ $mass->celebration_at?->translatedFormat('M') }}</p>
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-semibold text-[color:var(--parish-accent)]">
                                            {{ $mass->celebration_at?->format('H:i') }} @if ($mass->location) • {{ $mass->location }} @endif
                                        </p>
                                        <h3 class="mt-2 text-base font-semibold leading-7 text-stone-950">
                                            {{ $mass->intention_title ?: 'Msza święta' }}
                                        </h3>
                                        @if ($mass->intention_details)
                                            <p class="mt-2 text-sm leading-7 text-stone-600">
                                                {{ \Illuminate\Support\Str::limit(\Illuminate\Support\Str::of(strip_tags($mass->intention_details))->squish()->toString(), 150) }}
                                            </p>
                                        @endif
                                        <div class="mt-3 flex flex-wrap gap-2 text-xs text-stone-500">
                                            @if ($mass->mass_kind)
                                                <span class="rounded-full bg-stone-100 px-3 py-1">{{ \App\Models\Mass::MASS_KIND_OPTIONS[$mass->mass_kind] ?? 'Msza święta' }}</span>
                                            @endif
                                            @if ($mass->celebrant_name)
                                                <span class="rounded-full bg-stone-100 px-3 py-1">{{ $mass->celebrant_name }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @else
                    <div class="mt-5 rounded-[1.6rem] border border-dashed border-stone-300 bg-stone-50 px-5 py-6 text-sm leading-7 text-stone-600">
                        Parafia nie opublikowała jeszcze najbliższych mszy świętych w tym kanale.
                    </div>
                @endif
            </section>

            <section class="parish-accent-panel overflow-hidden rounded-[2rem] p-6 shadow-[0_20px_60px_rgba(58,40,24,0.08)]">
                <p class="text-xs font-semibold tracking-[0.22em] text-[color:var(--parish-accent)] uppercase">Usługa Wspólnota</p>
                <h2 class="mt-2 font-display text-2xl text-stone-950">Jedna parafia, jeden spokojny kanał informacji</h2>
                <p class="mt-4 text-sm leading-7 text-stone-700">
                    Ta strona pokazuje publiczną warstwę obecności parafii w usłudze Wspólnota: aktualne komunikaty, najbliższe wydarzenia i podstawowy kontakt w lekkiej, czytelnej formie.
                </p>
            </section>

            <section class="parish-surface p-6">
                <p class="text-xs font-semibold tracking-[0.22em] text-stone-500 uppercase">Aplikacja mobilna</p>
                <h2 class="mt-2 font-display text-2xl text-stone-950">Wspólnota jest dostępna także w sklepach mobilnych</h2>
                <p class="mt-4 text-sm leading-7 text-stone-600">
                    W aplikacji parafianie zobaczą dodatkowe elementy doświadczenia, takie jak komentarze pod aktualnościami czy galerie zdjęć.
                </p>

                <div class="mt-5">
                    @include('parish.partials.store-badges', [
                        'wrapperClass' => 'grid gap-2 sm:grid-cols-2',
                    ])
                </div>
            </section>
        </aside>
    </div>
@endsection

@section('structured_data')
    @php
        $structuredData = [
            '@context' => 'https://schema.org',
            '@graph' => array_values(array_filter([
                [
                    '@type' => 'Church',
                    '@id' => route('parish.home', ['subdomain' => $parish]).'#church',
                    'name' => $parish->name,
                    'url' => route('parish.home', ['subdomain' => $parish]),
                    'description' => 'Publiczna strona parafii z aktualnymi ogłoszeniami, mszami świętymi i aktualnościami.',
                    'email' => $publicEmail,
                    'telephone' => $publicPhone,
                    'image' => array_values(array_filter([$coverImageUrl ?: null, $avatarUrl ?: null])),
                    'address' => $publicAddressLines->isNotEmpty()
                        ? [
                            '@type' => 'PostalAddress',
                            'streetAddress' => $parish->street,
                            'postalCode' => $parish->postal_code,
                            'addressLocality' => $parish->city,
                            'addressCountry' => 'PL',
                        ]
                        : null,
                ],
                [
                    '@type' => 'CollectionPage',
                    '@id' => route('parish.home', ['subdomain' => $parish]).'#webpage',
                    'url' => route('parish.home', ['subdomain' => $parish]),
                    'name' => $parish->short_name.' - ogłoszenia, msze i aktualności',
                    'description' => trim($__env->yieldContent('meta_description')),
                    'isPartOf' => [
                        '@type' => 'WebSite',
                        'name' => 'Wspólnota',
                        'url' => route('landing.home'),
                    ],
                    'about' => [
                        '@id' => route('parish.home', ['subdomain' => $parish]).'#church',
                    ],
                ],
                $currentAnnouncement ? [
                    '@type' => 'CreativeWork',
                    '@id' => route('parish.home', ['subdomain' => $parish]).'#announcements',
                    'name' => $currentAnnouncement->title,
                    'datePublished' => optional($currentAnnouncement->published_at)?->toIso8601String(),
                    'dateModified' => optional($currentAnnouncement->updated_at)?->toIso8601String(),
                    'inLanguage' => 'pl-PL',
                    'isAccessibleForFree' => true,
                    'encodingFormat' => 'application/pdf',
                    'url' => route('parish.announcements.pdf', ['subdomain' => $parish]),
                ] : null,
            ])),
        ];

        if ($latestNews->isNotEmpty()) {
            foreach ($latestNews as $post) {
                $structuredData['@graph'][] = [
                    '@type' => 'NewsArticle',
                    '@id' => route('parish.home', ['subdomain' => $parish]).'#news-'.$post->getKey(),
                    'headline' => $post->title,
                    'datePublished' => optional($post->published_at ?? $post->created_at)?->toIso8601String(),
                    'dateModified' => optional($post->updated_at)?->toIso8601String(),
                    'author' => [
                        '@type' => 'Organization',
                        'name' => $parish->name,
                    ],
                    'publisher' => [
                        '@type' => 'Organization',
                        'name' => $parish->name,
                    ],
                    'articleBody' => (string) \Illuminate\Support\Str::of(strip_tags((string) $post->content))->squish()->limit(5000),
                    'image' => array_values(array_filter([$post->getFirstMediaUrl('featured_image', 'preview') ?: null, $coverImageUrl ?: null])),
                    'mainEntityOfPage' => route('parish.home', ['subdomain' => $parish]),
                    'inLanguage' => 'pl-PL',
                ];
            }
        }
    @endphp

    <script type="application/ld+json">
        {!! json_encode($structuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
    </script>
@endsection
