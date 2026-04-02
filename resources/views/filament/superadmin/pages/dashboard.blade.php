<x-filament-panels::page>
    <style>
        .sa-ct-shell {
            --sa-ct-accent: #c2410c;
            --sa-ct-accent-soft: rgba(194, 65, 12, 0.14);
            --sa-ct-panel: linear-gradient(135deg, #fff7ed 0%, #fff1f2 45%, #eff6ff 100%);
            display: grid;
            gap: 1.25rem;
        }

        .dark .sa-ct-shell {
            --sa-ct-accent-soft: rgba(249, 115, 22, 0.2);
            --sa-ct-panel: linear-gradient(135deg, rgba(120, 53, 15, 0.34) 0%, rgba(127, 29, 29, 0.28) 48%, rgba(30, 58, 138, 0.24) 100%);
        }

        .sa-ct-hero {
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(194, 65, 12, 0.18);
            border-radius: 1.5rem;
            padding: 1.15rem;
            background: var(--sa-ct-panel);
            box-shadow: 0 14px 42px rgba(15, 23, 42, 0.08);
        }

        .dark .sa-ct-hero {
            box-shadow: none;
            border-color: rgba(249, 115, 22, 0.28);
        }

        .sa-ct-hero::after {
            content: '';
            position: absolute;
            right: -3rem;
            top: -3rem;
            width: 12rem;
            height: 12rem;
            border-radius: 999px;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.7) 0%, rgba(255, 255, 255, 0) 70%);
            pointer-events: none;
        }

        .dark .sa-ct-hero::after {
            background: radial-gradient(circle, rgba(251, 191, 36, 0.16) 0%, rgba(251, 191, 36, 0) 72%);
        }

        .sa-ct-hero-grid {
            display: grid;
            gap: 1rem;
        }

        @media (min-width: 1200px) {
            .sa-ct-hero-grid {
                grid-template-columns: minmax(0, 1.3fr) minmax(0, 0.9fr);
                align-items: end;
            }
        }

        .sa-ct-title {
            font-size: 1.5rem;
            font-weight: 800;
            color: #111827;
            letter-spacing: -0.02em;
        }

        .dark .sa-ct-title {
            color: #f9fafb;
        }

        .sa-ct-copy {
            margin-top: 0.5rem;
            max-width: 56rem;
            line-height: 1.55;
            font-size: 0.95rem;
            color: #4b5563;
        }

        .dark .sa-ct-copy {
            color: #d1d5db;
        }

        .sa-ct-stamp {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            margin-top: 0.85rem;
            padding: 0.35rem 0.65rem;
            border: 1px solid rgba(194, 65, 12, 0.18);
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.72);
            color: #9a3412;
            font-size: 0.74rem;
            font-weight: 600;
        }

        .dark .sa-ct-stamp {
            background: rgba(15, 23, 42, 0.45);
            color: #fdba74;
            border-color: rgba(249, 115, 22, 0.24);
        }

        .sa-ct-grid {
            display: grid;
            gap: 0.9rem;
        }

        .sa-ct-grid.hero-cards {
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        }

        .sa-ct-grid.alerts {
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        }

        .sa-ct-grid.links {
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
        }

        .sa-ct-grid.columns-2 {
            grid-template-columns: repeat(1, minmax(0, 1fr));
        }

        .sa-ct-grid.columns-3 {
            grid-template-columns: repeat(1, minmax(0, 1fr));
        }

        @media (min-width: 1280px) {
            .sa-ct-grid.columns-2 {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .sa-ct-grid.columns-3 {
                grid-template-columns: 1.1fr 1fr 0.9fr;
            }
        }

        .sa-ct-card {
            border: 1px solid #e5e7eb;
            border-radius: 1.2rem;
            background: #fff;
            padding: 0.95rem;
            position: relative;
            overflow: hidden;
        }

        .dark .sa-ct-card {
            border-color: #374151;
            background: #111827;
        }

        .sa-ct-card::before {
            content: '';
            position: absolute;
            inset: 0 auto auto 0;
            width: 100%;
            height: 3px;
            background: var(--sa-ct-accent-soft);
        }

        .sa-ct-label {
            font-size: 0.73rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #6b7280;
            font-weight: 700;
        }

        .dark .sa-ct-label {
            color: #9ca3af;
        }

        .sa-ct-value {
            margin-top: 0.35rem;
            font-size: 1.7rem;
            line-height: 1;
            font-weight: 800;
            color: #111827;
        }

        .dark .sa-ct-value {
            color: #f9fafb;
        }

        .sa-ct-hint {
            margin-top: 0.45rem;
            font-size: 0.78rem;
            line-height: 1.45;
            color: #6b7280;
        }

        .dark .sa-ct-hint {
            color: #9ca3af;
        }

        .sa-ct-tone-success { --sa-tone: #166534; --sa-tone-bg: #dcfce7; --sa-tone-border: #86efac; }
        .sa-ct-tone-warning { --sa-tone: #b45309; --sa-tone-bg: #fef3c7; --sa-tone-border: #fcd34d; }
        .sa-ct-tone-danger { --sa-tone: #b91c1c; --sa-tone-bg: #fee2e2; --sa-tone-border: #fca5a5; }
        .sa-ct-tone-info { --sa-tone: #1d4ed8; --sa-tone-bg: #dbeafe; --sa-tone-border: #93c5fd; }
        .sa-ct-tone-primary { --sa-tone: #9a3412; --sa-tone-bg: #ffedd5; --sa-tone-border: #fdba74; }
        .sa-ct-tone-gray { --sa-tone: #374151; --sa-tone-bg: #f3f4f6; --sa-tone-border: #d1d5db; }

        .dark .sa-ct-tone-success { --sa-tone: #86efac; --sa-tone-bg: rgba(22, 101, 52, 0.24); --sa-tone-border: rgba(134, 239, 172, 0.45); }
        .dark .sa-ct-tone-warning { --sa-tone: #fcd34d; --sa-tone-bg: rgba(180, 83, 9, 0.24); --sa-tone-border: rgba(252, 211, 77, 0.45); }
        .dark .sa-ct-tone-danger { --sa-tone: #fca5a5; --sa-tone-bg: rgba(185, 28, 28, 0.24); --sa-tone-border: rgba(252, 165, 165, 0.45); }
        .dark .sa-ct-tone-info { --sa-tone: #93c5fd; --sa-tone-bg: rgba(29, 78, 216, 0.24); --sa-tone-border: rgba(147, 197, 253, 0.45); }
        .dark .sa-ct-tone-primary { --sa-tone: #fdba74; --sa-tone-bg: rgba(154, 52, 18, 0.24); --sa-tone-border: rgba(253, 186, 116, 0.45); }
        .dark .sa-ct-tone-gray { --sa-tone: #d1d5db; --sa-tone-bg: rgba(55, 65, 81, 0.42); --sa-tone-border: rgba(209, 213, 219, 0.34); }

        .sa-ct-tone-success,
        .sa-ct-tone-warning,
        .sa-ct-tone-danger,
        .sa-ct-tone-info,
        .sa-ct-tone-primary,
        .sa-ct-tone-gray {
            color: var(--sa-tone);
        }

        .sa-ct-pill {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 0.22rem 0.5rem;
            font-size: 0.68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--sa-tone);
            background: var(--sa-tone-bg);
            border: 1px solid var(--sa-tone-border);
        }

        .sa-ct-link-card {
            display: block;
            text-decoration: none;
            transition: transform .16s ease, border-color .16s ease, box-shadow .16s ease;
        }

        .sa-ct-link-card:hover {
            transform: translateY(-2px);
            border-color: rgba(194, 65, 12, 0.32);
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
        }

        .dark .sa-ct-link-card:hover {
            border-color: rgba(249, 115, 22, 0.35);
            box-shadow: none;
        }

        .sa-ct-list {
            display: grid;
            gap: 0.7rem;
        }

        .sa-ct-row {
            border: 1px solid #e5e7eb;
            border-radius: 1rem;
            background: #fff;
            padding: 0.8rem 0.9rem;
        }

        .dark .sa-ct-row {
            border-color: #374151;
            background: #111827;
        }

        .sa-ct-row-title {
            font-size: 0.92rem;
            font-weight: 700;
            color: #111827;
        }

        .dark .sa-ct-row-title {
            color: #f9fafb;
        }

        .sa-ct-row-meta {
            margin-top: 0.22rem;
            font-size: 0.78rem;
            line-height: 1.45;
            color: #6b7280;
        }

        .dark .sa-ct-row-meta {
            color: #9ca3af;
        }

        .sa-ct-row-top {
            display: flex;
            justify-content: space-between;
            gap: 0.8rem;
            align-items: start;
        }

        .sa-ct-row-link {
            color: inherit;
            text-decoration: none;
        }

        .sa-ct-row-link:hover .sa-ct-row-title {
            color: #c2410c;
        }

        .dark .sa-ct-row-link:hover .sa-ct-row-title {
            color: #fdba74;
        }

        .sa-ct-snapshot {
            display: grid;
            gap: 0.55rem;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        }

        .sa-ct-trend-panel {
            display: grid;
            gap: 1rem;
        }

        .sa-ct-range-switch {
            display: inline-flex;
            flex-wrap: wrap;
            gap: 0.45rem;
        }

        .sa-ct-trend-card {
            border: 1px solid #e5e7eb;
            border-radius: 1rem;
            padding: 0.85rem;
            background: #fff;
        }

        .dark .sa-ct-trend-card {
            border-color: #374151;
            background: #111827;
        }

        .sa-ct-range-btn,
        .sa-ct-legend-btn {
            appearance: none;
            border: 1px solid #d1d5db;
            background: #fff;
            color: #374151;
            border-radius: 999px;
            padding: 0.42rem 0.72rem;
            font-size: 0.76rem;
            font-weight: 700;
            line-height: 1;
            transition: all .16s ease;
        }

        .dark .sa-ct-range-btn,
        .dark .sa-ct-legend-btn {
            border-color: #4b5563;
            background: #111827;
            color: #d1d5db;
        }

        .sa-ct-range-btn.is-active,
        .sa-ct-legend-btn.is-active {
            border-color: rgba(194, 65, 12, 0.42);
            background: #fff7ed;
            color: #9a3412;
        }

        .dark .sa-ct-range-btn.is-active,
        .dark .sa-ct-legend-btn.is-active {
            border-color: rgba(249, 115, 22, 0.42);
            background: rgba(154, 52, 18, 0.22);
            color: #fdba74;
        }

        .sa-ct-legend-btn.is-hidden {
            opacity: 0.52;
            filter: grayscale(0.15);
        }

        .sa-ct-trend-top {
            display: flex;
            align-items: start;
            justify-content: space-between;
            gap: 0.75rem;
        }

        .sa-ct-trend-meta {
            display: flex;
            gap: 0.4rem;
            flex-wrap: wrap;
            margin-top: 0.35rem;
        }

        .sa-ct-legend-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 0.85rem;
        }

        .sa-ct-trend-svg {
            width: 100%;
            height: 88px;
            margin-top: 0.75rem;
            display: block;
        }

        .sa-ct-trend-axis {
            display: flex;
            justify-content: space-between;
            gap: 0.5rem;
            margin-top: 0.3rem;
            font-size: 0.7rem;
            color: #6b7280;
        }

        .dark .sa-ct-trend-axis {
            color: #9ca3af;
        }
    </style>

    <div class="sa-ct-shell" wire:poll.30s>
        <section class="sa-ct-hero">
            <div class="sa-ct-hero-grid">
                <div>
                    <div class="sa-ct-label">Control Tower</div>
                    <h1 class="sa-ct-title">Centrum dowodzenia platformy</h1>
                    <p class="sa-ct-copy">
                        Jeden ekran dla superadministratora: stan platformy, zalegly dispatch, push, kancelaria online,
                        kolejki oraz szybkie przejscie do kluczowych modułów bez przeklikiwania się przez całe menu.
                    </p>
                    <div class="sa-ct-stamp">
                        Odswiezanie automatyczne co 30 sekund
                    </div>
                </div>

                <div class="sa-ct-grid hero-cards">
                    @foreach ($this->heroCards as $card)
                        <article class="sa-ct-card">
                            <div class="sa-ct-label">{{ $card['label'] }}</div>
                            <div class="sa-ct-value">{{ $card['value'] }}</div>
                            <div class="sa-ct-hint">{{ $card['hint'] }}</div>
                            <div class="mt-3">
                                <span class="sa-ct-pill sa-ct-tone-{{ $card['tone'] }}">{{ strtoupper($card['tone']) }}</span>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="sa-ct-grid alerts">
            @foreach ($this->alertCards as $card)
                <a href="{{ $card['url'] }}" class="sa-ct-card sa-ct-link-card">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <div class="sa-ct-label">{{ $card['label'] }}</div>
                            <div class="sa-ct-value">{{ number_format($card['value'], 0, ',', ' ') }}</div>
                            <div class="sa-ct-hint">{{ $card['description'] }}</div>
                        </div>
                        <span class="sa-ct-pill sa-ct-tone-{{ $card['tone'] }}">{{ strtoupper($card['tone']) }}</span>
                    </div>
                </a>
            @endforeach
        </section>

        <x-filament::section
            heading="Trendy operacyjne"
            description="Przelaczaj zakres czasu, ukrywaj serie i obserwuj osobno platforme, komunikacje, push oraz kancelarie."
        >
            <div class="sa-ct-range-switch">
                @foreach ($this->trendRangeOptions as $value => $label)
                    <button
                        type="button"
                        wire:click="setTrendRange('{{ $value }}')"
                        class="sa-ct-range-btn {{ $this->trendRange === $value ? 'is-active' : '' }}"
                    >
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </x-filament::section>

        <div class="sa-ct-grid columns-2">
            @foreach ($this->trendPanels as $panel)
                <x-filament::section
                    :heading="$panel['title']"
                    :description="$panel['description'].' · zakres '.$panel['range_title']"
                >
                    <div class="sa-ct-trend-panel">
                        <div class="sa-ct-trend-card">
                            <div class="sa-ct-trend-top">
                                <div>
                                    <div class="sa-ct-row-title">{{ $panel['title'] }}</div>
                                    <div class="sa-ct-row-meta">{{ $panel['description'] }}</div>
                                </div>
                                <span class="sa-ct-pill sa-ct-tone-primary">{{ strtoupper($panel['range_title']) }}</span>
                            </div>

                            <div class="sa-ct-legend-grid">
                                @foreach ($panel['series'] as $series)
                                    @php $isHidden = ! in_array($series['key'], array_column($panel['visible_series'], 'key'), true); @endphp
                                    <button
                                        type="button"
                                        wire:click="toggleTrendSeries('{{ $panel['key'] }}', '{{ $series['key'] }}')"
                                        class="sa-ct-legend-btn {{ $isHidden ? 'is-hidden' : 'is-active' }}"
                                    >
                                        {{ $series['label'] }} · {{ number_format($series['total'], 0, ',', ' ') }}
                                    </button>
                                @endforeach
                            </div>

                            @if ($panel['has_visible_series'])
                                <svg class="sa-ct-trend-svg" viewBox="0 0 320 88" preserveAspectRatio="none" aria-hidden="true">
                                    <line x1="0" y1="80" x2="320" y2="80" stroke="rgba(156, 163, 175, 0.35)" stroke-width="1" />
                                    <line x1="0" y1="44" x2="320" y2="44" stroke="rgba(156, 163, 175, 0.14)" stroke-width="1" stroke-dasharray="4 4" />
                                    @foreach ($panel['visible_series'] as $series)
                                        <polyline
                                            points="{{ $series['points'] }}"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="3"
                                            class="sa-ct-tone-{{ $series['tone'] }}"
                                            vector-effect="non-scaling-stroke"
                                        />
                                    @endforeach
                                </svg>

                                <div class="sa-ct-trend-meta">
                                    @foreach ($panel['visible_series'] as $series)
                                        <span class="sa-ct-pill sa-ct-tone-{{ $series['tone'] }}">
                                            {{ $series['label'] }} · ostatni {{ $series['latest'] }} · peak {{ $series['peak'] }}
                                        </span>
                                    @endforeach
                                </div>
                            @else
                                <div class="mt-4 rounded-xl border border-dashed border-gray-300 px-4 py-6 text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                                    Wszystkie serie sa ukryte. Kliknij legende powyzej, aby ponownie pokazac wykres.
                                </div>
                            @endif

                            <div class="sa-ct-trend-axis">
                                <span>{{ $panel['start_label'] }}</span>
                                <span>{{ $panel['end_label'] }}</span>
                            </div>
                        </div>
                    </div>
                </x-filament::section>
            @endforeach
        </div>

        <x-filament::section
            heading="Szybkie przejścia"
            description="Najczęściej potrzebne moduły i obszary operacyjne superadministratora."
        >
            <div class="sa-ct-grid links">
                @foreach ($this->quickLinks as $link)
                    <a href="{{ $link['url'] }}" class="sa-ct-card sa-ct-link-card">
                        <div class="sa-ct-row-title">{{ $link['label'] }}</div>
                        <div class="sa-ct-row-meta">{{ $link['description'] }}</div>
                    </a>
                @endforeach
            </div>
        </x-filament::section>

        <div class="sa-ct-grid columns-3">
            <x-filament::section
                heading="Użytkownicy wymagający uwagi"
                description="Najnowsi oczekujący na zatwierdzenie."
            >
                <div class="sa-ct-list">
                    @forelse ($this->pendingUsers as $row)
                        <a href="{{ $row['url'] }}" class="sa-ct-row sa-ct-row-link">
                            <div class="sa-ct-row-top">
                                <div>
                                    <div class="sa-ct-row-title">{{ $row['name'] }}</div>
                                    <div class="sa-ct-row-meta">{{ $row['email'] }} · {{ $row['parish'] }}</div>
                                </div>
                                <span class="sa-ct-pill sa-ct-tone-warning">OCZEKUJĄCY</span>
                            </div>
                            <div class="sa-ct-row-meta">Utworzono: {{ $row['created_at'] }}</div>
                        </a>
                    @empty
                        <p class="text-sm text-gray-500 dark:text-gray-400">Brak użytkowników oczekujących na zatwierdzenie.</p>
                    @endforelse
                </div>
            </x-filament::section>

            <x-filament::section
                heading="Radar dispatchu"
                description="Elementy, które powinny zostać już wysłane lub sprawdzone."
            >
                <div class="sa-ct-list">
                    @forelse ($this->dueDispatchItems as $row)
                        <a href="{{ $row['url'] }}" class="sa-ct-row sa-ct-row-link">
                            <div class="sa-ct-row-top">
                                <div>
                                    <div class="sa-ct-row-title">{{ $row['title'] }}</div>
                                    <div class="sa-ct-row-meta">{{ $row['parish'] }} · {{ $row['when'] }}</div>
                                </div>
                                <span class="sa-ct-pill sa-ct-tone-warning">{{ $row['type'] }}</span>
                            </div>
                        </a>
                    @empty
                        <p class="text-sm text-gray-500 dark:text-gray-400">Brak zaległych elementów wysyłki.</p>
                    @endforelse
                </div>
            </x-filament::section>

            <x-filament::section
                heading="Push radar"
                description="Ostatnie nieudane dostarczenia push."
            >
                <div class="sa-ct-list">
                    @forelse ($this->recentFailedPushes as $row)
                        <a href="{{ $row['url'] }}" class="sa-ct-row sa-ct-row-link">
                            <div class="sa-ct-row-top">
                                <div>
                                    <div class="sa-ct-row-title">{{ $row['type'] ?: 'PUSH' }}</div>
                                    <div class="sa-ct-row-meta">{{ $row['user'] }} · {{ $row['platform'] }}</div>
                                </div>
                                <span class="sa-ct-pill sa-ct-tone-danger">FAIL</span>
                            </div>
                            <div class="sa-ct-row-meta">{{ $row['error'] }}</div>
                            <div class="sa-ct-row-meta">{{ $row['created_at'] }}</div>
                        </a>
                    @empty
                        <p class="text-sm text-gray-500 dark:text-gray-400">Brak nieudanych dostarczeń push.</p>
                    @endforelse
                </div>
            </x-filament::section>
        </div>

        <div class="sa-ct-grid columns-2">
            <x-filament::section
                heading="Otwarte konwersacje online"
                description="Najświeższe rozmowy z kancelarii wymagające reakcji."
            >
                <div class="sa-ct-list">
                    @forelse ($this->openConversations as $row)
                        <div class="sa-ct-row">
                            <div class="sa-ct-row-top">
                                <div>
                                    <div class="sa-ct-row-title">{{ $row['subject'] }}</div>
                                    <div class="sa-ct-row-meta">{{ $row['parish'] }} · {{ $row['user'] }}</div>
                                </div>
                                <span class="sa-ct-pill {{ $row['unread'] > 0 ? 'sa-ct-tone-warning' : 'sa-ct-tone-success' }}">
                                    nieprzeczytane {{ $row['unread'] }}
                                </span>
                            </div>
                            <div class="sa-ct-row-meta">Aktualizacja: {{ $row['updated_at'] }}</div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 dark:text-gray-400">Brak otwartych konwersacji.</p>
                    @endforelse
                </div>
            </x-filament::section>

            <x-filament::section
                heading="Najbliższe msze"
                description="Nadchodzące celebracje z liczbą uczestników."
            >
                <div class="sa-ct-list">
                    @forelse ($this->upcomingMasses as $row)
                        <a href="{{ $row['url'] }}" class="sa-ct-row sa-ct-row-link">
                            <div class="sa-ct-row-top">
                                <div>
                                    <div class="sa-ct-row-title">{{ $row['title'] }}</div>
                                    <div class="sa-ct-row-meta">{{ $row['parish'] }} · {{ $row['celebration_at'] }}</div>
                                </div>
                                <span class="sa-ct-pill sa-ct-tone-info">uczestnicy {{ $row['participants'] }}</span>
                            </div>
                        </a>
                    @empty
                        <p class="text-sm text-gray-500 dark:text-gray-400">Brak nadchodzących mszy w horyzoncie 3 dni.</p>
                    @endforelse
                </div>
            </x-filament::section>
        </div>

        <x-filament::section
            heading="Snapshot systemu"
            description="Najważniejsze parametry środowiska i infrastruktury aplikacji."
        >
            <div class="sa-ct-snapshot">
                @foreach ($this->systemSnapshot as $row)
                    <div class="sa-ct-card">
                        <div class="sa-ct-label">{{ $row['label'] }}</div>
                        <div class="mt-2 text-sm font-semibold text-gray-950 dark:text-white">{{ $row['value'] }}</div>
                    </div>
                @endforeach
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
