<!DOCTYPE html>
@php
    $seoTitle = trim($__env->yieldContent('title', 'Wspólnota | Aplikacja i panel dla parafii'));
    $seoDescription = trim($__env->yieldContent('meta_description', 'Wspólnota to nowoczesna usługa dla parafii: aplikacja PWA dla parafian i panel administratora dla proboszcza.'));
    $seoCanonical = trim($__env->yieldContent('canonical', url()->current()));
    $seoRobots = trim($__env->yieldContent('meta_robots', 'index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1'));
    $seoImage = trim($__env->yieldContent('meta_image', asset('assets/seo/wspolnota-og.svg')));
    $seoType = trim($__env->yieldContent('og_type', 'website'));
    $seoSchemaType = trim($__env->yieldContent('schema_type', 'WebPage'));
    $organizationSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'Organization',
        'name' => config('app.name', 'Wspólnota'),
        'url' => rtrim(config('app.url'), '/'),
        'logo' => asset('assets/seo/wspolnota-og.svg'),
        'email' => 'wspolnota@wspolnota.app',
    ];
    $websiteSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'WebSite',
        'name' => config('app.name', 'Wspólnota'),
        'url' => rtrim(config('app.url'), '/'),
        'inLanguage' => 'pl-PL',
    ];
    $pageSchema = [
        '@context' => 'https://schema.org',
        '@type' => $seoSchemaType,
        'name' => $seoTitle,
        'description' => $seoDescription,
        'url' => $seoCanonical,
        'inLanguage' => 'pl-PL',
        'isPartOf' => [
            '@type' => 'WebSite',
            'name' => config('app.name', 'Wspólnota'),
            'url' => rtrim(config('app.url'), '/'),
        ],
    ];
@endphp
<html lang="pl-PL">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="{{ $seoDescription }}">
    <meta name="robots" content="{{ $seoRobots }}">
    <meta name="theme-color" content="#f3efe6">
    <meta name="author" content="Wspólnota">
    <meta name="application-name" content="{{ config('app.name', 'Wspólnota') }}">
    <meta name="apple-mobile-web-app-title" content="{{ config('app.name', 'Wspólnota') }}">
    <meta property="og:locale" content="pl_PL">
    <meta property="og:type" content="{{ $seoType }}">
    <meta property="og:site_name" content="{{ config('app.name', 'Wspólnota') }}">
    <meta property="og:title" content="{{ $seoTitle }}">
    <meta property="og:description" content="{{ $seoDescription }}">
    <meta property="og:url" content="{{ $seoCanonical }}">
    <meta property="og:image" content="{{ $seoImage }}">
    <meta property="og:image:alt" content="Wspólnota - aplikacja i panel dla parafii">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $seoTitle }}">
    <meta name="twitter:description" content="{{ $seoDescription }}">
    <meta name="twitter:image" content="{{ $seoImage }}">
    <link rel="canonical" href="{{ $seoCanonical }}">
    <link rel="alternate" hreflang="pl-PL" href="{{ $seoCanonical }}">
    <link rel="alternate" hreflang="x-default" href="{{ $seoCanonical }}">
    @include('partials.favicon')
    <title>{{ $seoTitle }}</title>

    <script>
        (() => {
            const storedPreference = localStorage.getItem('wspolnota_theme') || 'auto';
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            const resolved = storedPreference === 'auto' ? (prefersDark ? 'dark' : 'light') : storedPreference;
            document.documentElement.classList.toggle('dark', resolved === 'dark');
            document.documentElement.dataset.themePreference = storedPreference;
            document.documentElement.style.colorScheme = resolved;
        })();
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script type="application/ld+json">@json($organizationSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)</script>
    <script type="application/ld+json">@json($websiteSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)</script>
    <script type="application/ld+json">@json($pageSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)</script>
    @hasSection('structured_data')
        @yield('structured_data')
    @endif

    @vite(['resources/css/landing.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-[#f3efe6] text-stone-900 transition-colors duration-300 dark:bg-[#14110f] dark:text-stone-100">
    <div class="pointer-events-none fixed inset-0 -z-10 overflow-hidden">
        <div class="floating-orb bg-[radial-gradient(circle_at_center,_rgba(184,115,51,0.22),_transparent_65%)] left-[-10rem] top-[-8rem] h-[28rem] w-[28rem]"></div>
        <div class="floating-orb animation-delay-2s bg-[radial-gradient(circle_at_center,_rgba(76,94,63,0.18),_transparent_68%)] right-[-8rem] top-[8rem] h-[30rem] w-[30rem]"></div>
        <div class="floating-orb animation-delay-4s bg-[radial-gradient(circle_at_center,_rgba(120,53,15,0.16),_transparent_70%)] bottom-[-10rem] left-[30%] h-[32rem] w-[32rem]"></div>
    </div>

    <div class="mx-auto flex min-h-screen max-w-7xl flex-col px-4 sm:px-6 lg:px-8">
        <header class="sticky top-0 z-40 pt-4">
            <div class="panel flex items-center justify-between gap-4 px-4 py-3 sm:px-6">
                <a href="{{ route('landing.home') }}" class="flex items-center gap-3 text-sm font-semibold tracking-[0.24em] text-stone-700 uppercase">
                    <span class="flex h-10 w-10 items-center justify-center rounded-full bg-[rgba(184,115,51,0.14)] text-lg text-[#b87333]">W</span>
                    <span>Wspólnota</span>
                </a>

                <nav class="hidden items-center gap-6 text-sm font-medium text-stone-600 md:flex">
                    <a href="{{ route('landing.home') }}#funkcje" class="transition hover:text-stone-950">Funkcje</a>
                    <a href="{{ route('landing.home') }}#korzysci" class="transition hover:text-stone-950">Korzyści</a>
                    <a href="{{ route('landing.home') }}#technologia" class="transition hover:text-stone-950">Technologia</a>
                    <a href="{{ route('landing.home') }}#cennik" class="transition hover:text-stone-950">Pricing</a>
                    <a href="{{ route('landing.contact') }}" class="transition hover:text-stone-950">Kontakt</a>
                </nav>

                <div class="flex items-center gap-3">
                    <div x-data class="hidden items-center gap-1 rounded-full border border-stone-300/70 bg-white/70 p-1 text-xs font-semibold text-stone-600 shadow-sm dark:border-stone-700/80 dark:bg-stone-900/70 dark:text-stone-300 lg:flex">
                        <button type="button" @click="$store.theme.set('light')" :class="$store.theme.preference === 'light' ? 'bg-stone-950 text-white shadow-sm dark:bg-stone-100 dark:text-stone-950' : 'text-inherit'" class="rounded-full px-3 py-2 transition">
                            Dzień
                        </button>
                        <button type="button" @click="$store.theme.set('dark')" :class="$store.theme.preference === 'dark' ? 'bg-stone-950 text-white shadow-sm dark:bg-stone-100 dark:text-stone-950' : 'text-inherit'" class="rounded-full px-3 py-2 transition">
                            Noc
                        </button>
                        <button type="button" @click="$store.theme.set('auto')" :class="$store.theme.preference === 'auto' ? 'bg-stone-950 text-white shadow-sm dark:bg-stone-100 dark:text-stone-950' : 'text-inherit'" class="rounded-full px-3 py-2 transition">
                            Auto
                        </button>
                    </div>
                    <a href="{{ route('login') }}" class="hidden rounded-full border border-stone-300 px-4 py-2 text-sm font-semibold text-stone-700 transition hover:border-stone-900 hover:text-stone-950 sm:inline-flex">
                        Panel proboszcza
                    </a>
                    <a href="{{ route('landing.contact') }}" class="inline-flex rounded-full bg-stone-950 px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#b87333]">
                        Umów rozmowę
                    </a>
                </div>
            </div>
        </header>

        @if (session('status'))
            <div class="mt-6">
                <div class="landing-success-banner panel px-4 py-3 text-sm font-medium sm:px-6">
                    {{ session('status') }}
                </div>
            </div>
        @endif

        <main class="flex-1 py-8">
            @yield('content')
        </main>

        <footer class="pb-8 pt-12">
            <div class="panel flex flex-col gap-6 px-6 py-6 text-sm text-stone-600 lg:flex-row lg:items-center lg:justify-between">
                <div class="max-w-xl">
                    <p class="font-semibold text-stone-900">Wspólnota</p>
                    <p>Cyfrowa usługa dla parafii, które chcą komunikować się spokojnie, jasno i nowocześnie.</p>
                </div>

                <div class="flex flex-col gap-4 lg:items-end">
                    <div x-data class="flex items-center gap-1 rounded-full border border-stone-300/70 bg-white/70 p-1 text-xs font-semibold text-stone-600 shadow-sm dark:border-stone-700/80 dark:bg-stone-900/70 dark:text-stone-300 lg:hidden">
                        <button type="button" @click="$store.theme.set('light')" :class="$store.theme.preference === 'light' ? 'bg-stone-950 text-white shadow-sm dark:bg-stone-100 dark:text-stone-950' : 'text-inherit'" class="rounded-full px-3 py-2 transition">
                            Dzień
                        </button>
                        <button type="button" @click="$store.theme.set('dark')" :class="$store.theme.preference === 'dark' ? 'bg-stone-950 text-white shadow-sm dark:bg-stone-100 dark:text-stone-950' : 'text-inherit'" class="rounded-full px-3 py-2 transition">
                            Noc
                        </button>
                        <button type="button" @click="$store.theme.set('auto')" :class="$store.theme.preference === 'auto' ? 'bg-stone-950 text-white shadow-sm dark:bg-stone-100 dark:text-stone-950' : 'text-inherit'" class="rounded-full px-3 py-2 transition">
                            Auto
                        </button>
                    </div>

                    <div class="flex flex-wrap gap-x-5 gap-y-3">
                        <a href="{{ route('landing.terms') }}" class="transition hover:text-stone-950">Regulamin</a>
                        <a href="{{ route('landing.privacy') }}" class="transition hover:text-stone-950">Polityka prywatności</a>
                        <a href="{{ route('landing.contact') }}" class="transition hover:text-stone-950">Kontakt</a>
                        <a href="{{ route('login') }}" class="transition hover:text-stone-950">Panel administratora</a>
                    </div>
                </div>
            </div>
        </footer>
    </div>

    <div
        x-data="{ open: localStorage.getItem('wspolnota_cookie_notice') !== 'dismissed' }"
        x-cloak
        x-show="open"
        x-transition.opacity.duration.300ms
        class="fixed inset-x-0 bottom-0 z-50 px-4 pb-4 sm:px-6 lg:px-8"
    >
        <div class="mx-auto max-w-5xl">
            <div class="landing-cookie-banner panel flex flex-col gap-4 px-5 py-4 sm:flex-row sm:items-end sm:justify-between">
                <div class="max-w-3xl">
                    <p class="text-sm font-semibold text-stone-900">Informacja o cookies</p>
                    <p class="mt-1 text-sm leading-6 text-stone-600">
                        Korzystamy z plików cookies niezbędnych do działania serwisu oraz opcjonalnych danych technicznych służących bezpieczeństwu i analizie ruchu. Dalsze korzystanie z serwisu oznacza zapoznanie się z zasadami opisanymi w
                        <a href="{{ route('landing.privacy') }}" class="font-semibold text-stone-900 underline decoration-[#b8733380] underline-offset-4">Polityce prywatności</a>.
                    </p>
                </div>

                <button
                    type="button"
                    @click="localStorage.setItem('wspolnota_cookie_notice', 'dismissed'); open = false"
                    class="inline-flex shrink-0 items-center justify-center rounded-full bg-stone-950 px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#b87333]"
                >
                    Zamknij
                </button>
            </div>
        </div>
    </div>
</body>

</html>
