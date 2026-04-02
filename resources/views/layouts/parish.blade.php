<!DOCTYPE html>
<html lang="pl">

<head>
    @php
        $pageTitle = trim($__env->yieldContent('title', $parish->short_name . ' • Wspólnota'));
        $pageDescription = trim($__env->yieldContent('meta_description', 'Publiczna strona parafii we Wspólnocie.'));
        $pageCanonicalUrl = trim($__env->yieldContent('canonical_url', url()->current()));
        $pageRobots = trim($__env->yieldContent('robots', 'index,follow,max-snippet:-1,max-image-preview:large,max-video-preview:-1'));
        $pageOgType = trim($__env->yieldContent('og_type', 'website'));
        $pageImage = trim($__env->yieldContent('meta_image', $coverImageUrl ?: $avatarUrl ?: ''));
    @endphp

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="{{ $pageDescription }}">
    <meta name="robots" content="{{ $pageRobots }}">
    <meta name="theme-color" content="#f4efe7">
    <link rel="canonical" href="{{ $pageCanonicalUrl }}">
    @include('partials.favicon')

    <meta property="og:locale" content="pl_PL">
    <meta property="og:type" content="{{ $pageOgType }}">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $pageDescription }}">
    <meta property="og:url" content="{{ $pageCanonicalUrl }}">
    <meta property="og:site_name" content="Wspólnota">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $pageTitle }}">
    <meta name="twitter:description" content="{{ $pageDescription }}">

    @if ($pageImage !== '')
        <meta property="og:image" content="{{ $pageImage }}">
        <meta name="twitter:image" content="{{ $pageImage }}">
    @endif

    <title>{{ $pageTitle }}</title>

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

    @vite(['resources/css/landing.css', 'resources/js/app.js'])

    <style>
        :root {
            --parish-accent: {{ $accentColor }};
        }
    </style>
</head>

<body class="min-h-screen bg-[#f4efe7] text-stone-900 antialiased transition-colors duration-300 dark:bg-[#14110f] dark:text-stone-100">
    <div class="pointer-events-none fixed inset-0 -z-10 overflow-hidden">
        <div class="parish-top-glow absolute inset-x-0 top-0 h-[26rem]"></div>
        <div class="parish-orb absolute -left-20 top-20 h-72 w-72 rounded-full blur-3xl"></div>
        <div class="parish-orb absolute bottom-0 right-0 h-96 w-96 rounded-full blur-3xl opacity-80"></div>
    </div>

    <div class="mx-auto flex min-h-screen max-w-7xl flex-col px-4 sm:px-6 lg:px-8">
        <header class="py-5 sm:py-6">
            <div class="parish-glass flex flex-col gap-4 px-5 py-4 sm:px-6 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex items-center gap-4">
                    <div class="flex h-14 w-14 items-center justify-center overflow-hidden rounded-2xl border border-white/70 bg-white/80 shadow-sm">
                        @if ($avatarUrl)
                            <img src="{{ $avatarUrl }}" alt="{{ $parish->short_name }}" class="h-full w-full object-cover">
                        @else
                            <span class="font-display text-xl text-stone-700">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($parish->short_name, 0, 1)) }}</span>
                        @endif
                    </div>

                    <div>
                        <a href="{{ url('/') }}" class="text-sm font-semibold tracking-[0.22em] text-stone-500 uppercase transition hover:text-stone-900">
                            Portal parafii
                        </a>
                        <p class="mt-1 text-lg font-semibold text-stone-950">{{ $parish->short_name }}</p>
                        <p class="text-sm text-stone-600">{{ $parish->city }} @if ($parish->diocese) • {{ $parish->diocese }} @endif</p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3">
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

                    @if ($websiteUrl)
                        <a href="{{ $websiteUrl }}" target="_blank" rel="noreferrer" class="rounded-full border border-stone-300 bg-white/80 px-4 py-2 text-sm font-semibold text-stone-700 transition hover:border-stone-950 hover:text-stone-950">
                            Strona parafii
                        </a>
                    @endif

                    <a href="{{ route('landing.home') }}" class="inline-flex items-center rounded-full px-4 py-2 text-sm font-semibold text-white shadow-lg transition hover:opacity-90" style="background-color: var(--parish-accent);">
                        Wspólnota
                    </a>
                </div>
            </div>
        </header>

        <main class="flex-1 pb-14">
            @if (session('status'))
                <div class="mb-6">
                    <div class="landing-success-banner parish-glass px-5 py-4 text-sm font-medium sm:px-6">
                        {{ session('status') }}
                    </div>
                </div>
            @endif

            @yield('content')
        </main>

        <footer class="pb-8">
            <div class="parish-glass flex flex-col gap-5 px-5 py-5 text-sm text-stone-600 sm:px-6 lg:flex-row lg:items-end lg:justify-between">
                <div class="max-w-2xl">
                    <p class="font-semibold text-stone-900">{{ $parish->name }}</p>
                    <p class="mt-1">Publiczna warstwa parafii w usłudze Wspólnota. Spokojne miejsce na ogłoszenia, msze święte i codzienną komunikację.</p>
                </div>

                <div class="flex flex-col gap-2 text-sm lg:items-end">
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

                    @if ($addressLines->isNotEmpty())
                        <p>{{ $addressLines->implode(', ') }}</p>
                    @endif
                    <div class="flex flex-wrap gap-x-4 gap-y-2">
                        @if ($publicEmail)
                            <a href="mailto:{{ $publicEmail }}" class="transition hover:text-stone-950">{{ $publicEmail }}</a>
                        @endif
                        @if ($publicPhone)
                            <a href="tel:{{ preg_replace('/\s+/', '', $publicPhone) }}" class="transition hover:text-stone-950">{{ $publicPhone }}</a>
                        @endif
                    </div>
                </div>
            </div>
        </footer>
    </div>

    @yield('modals')
    @yield('structured_data')
</body>

</html>
