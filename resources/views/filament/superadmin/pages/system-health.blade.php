<x-filament-panels::page>
    <style>
        .sa-health-shell {
            --sa-accent: #d9480f;
            --sa-accent-soft: rgba(217, 72, 15, 0.14);
            --sa-panel-bg: linear-gradient(135deg, #fff7ed 0%, #fff1f2 44%, #eff6ff 100%);
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }

        .dark .sa-health-shell {
            --sa-accent-soft: rgba(251, 146, 60, 0.22);
            --sa-panel-bg: linear-gradient(135deg, rgba(120, 53, 15, 0.35) 0%, rgba(127, 29, 29, 0.28) 48%, rgba(30, 58, 138, 0.22) 100%);
        }

        .sa-health-hero {
            border: 1px solid rgba(217, 72, 15, 0.22);
            border-radius: 1rem;
            padding: 1rem 1.1rem;
            background: var(--sa-panel-bg);
            box-shadow: 0 6px 20px rgba(15, 23, 42, 0.08);
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
        }

        .dark .sa-health-hero {
            border-color: rgba(251, 146, 60, 0.35);
            box-shadow: none;
        }

        .sa-health-hero-title {
            font-size: 1.05rem;
            font-weight: 700;
            letter-spacing: 0.01em;
            color: #111827;
        }

        .dark .sa-health-hero-title {
            color: #f9fafb;
        }

        .sa-health-hero-sub {
            margin-top: 0.3rem;
            font-size: 0.82rem;
            color: #4b5563;
        }

        .dark .sa-health-hero-sub {
            color: #d1d5db;
        }

        .sa-health-refresh {
            border: 1px solid rgba(217, 72, 15, 0.32);
            background: #ffffffc9;
            border-radius: 999px;
            padding: 0.35rem 0.65rem;
            font-size: 0.75rem;
            color: #9a3412;
            white-space: nowrap;
        }

        .dark .sa-health-refresh {
            background: rgba(15, 23, 42, 0.5);
            color: #fdba74;
            border-color: rgba(251, 146, 60, 0.4);
        }

        .sa-health-grid {
            display: grid;
            gap: 0.9rem;
        }

        .sa-health-grid.overview {
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        }

        .sa-health-grid.infra {
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        }

        .sa-health-card {
            border: 1px solid #e5e7eb;
            border-radius: 1rem;
            padding: 0.85rem 0.9rem;
            background: #fff;
            position: relative;
            overflow: hidden;
        }

        .dark .sa-health-card {
            border-color: #374151;
            background: #111827;
        }

        .sa-health-card::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            right: 0;
            height: 3px;
            background: var(--sa-accent-soft);
        }

        .sa-health-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.4rem;
        }

        .sa-health-label {
            font-size: 0.76rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #6b7280;
        }

        .dark .sa-health-label {
            color: #9ca3af;
        }

        .sa-health-value {
            margin-top: 0.35rem;
            font-size: 1.62rem;
            line-height: 1.05;
            font-weight: 700;
            color: #111827;
        }

        .dark .sa-health-value {
            color: #f9fafb;
        }

        .sa-health-hint {
            margin-top: 0.35rem;
            font-size: 0.76rem;
            color: #6b7280;
            line-height: 1.4;
        }

        .dark .sa-health-hint {
            color: #9ca3af;
        }

        .sa-health-pill {
            display: inline-flex;
            align-items: center;
            padding: 0.2rem 0.45rem;
            border-radius: 999px;
            font-size: 0.67rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            border: 1px solid transparent;
        }

        .sa-health-tone-success { --sa-tone: #166534; --sa-tone-bg: #dcfce7; --sa-tone-border: #86efac; }
        .sa-health-tone-warning { --sa-tone: #b45309; --sa-tone-bg: #fef3c7; --sa-tone-border: #fcd34d; }
        .sa-health-tone-danger { --sa-tone: #b91c1c; --sa-tone-bg: #fee2e2; --sa-tone-border: #fca5a5; }
        .sa-health-tone-info { --sa-tone: #1d4ed8; --sa-tone-bg: #dbeafe; --sa-tone-border: #93c5fd; }
        .sa-health-tone-primary { --sa-tone: #9a3412; --sa-tone-bg: #ffedd5; --sa-tone-border: #fdba74; }
        .sa-health-tone-gray { --sa-tone: #374151; --sa-tone-bg: #f3f4f6; --sa-tone-border: #d1d5db; }

        .sa-health-pill,
        .sa-health-card.sa-health-tone-success::before,
        .sa-health-card.sa-health-tone-warning::before,
        .sa-health-card.sa-health-tone-danger::before,
        .sa-health-card.sa-health-tone-info::before,
        .sa-health-card.sa-health-tone-primary::before,
        .sa-health-card.sa-health-tone-gray::before {
            background: var(--sa-tone-bg);
            color: var(--sa-tone);
            border-color: var(--sa-tone-border);
        }

        .sa-health-card.sa-health-tone-success::before,
        .sa-health-card.sa-health-tone-warning::before,
        .sa-health-card.sa-health-tone-danger::before,
        .sa-health-card.sa-health-tone-info::before,
        .sa-health-card.sa-health-tone-primary::before,
        .sa-health-card.sa-health-tone-gray::before {
            border: 0;
            height: 3px;
        }

        .dark .sa-health-tone-success { --sa-tone: #86efac; --sa-tone-bg: rgba(22, 101, 52, 0.24); --sa-tone-border: rgba(134, 239, 172, 0.45); }
        .dark .sa-health-tone-warning { --sa-tone: #fcd34d; --sa-tone-bg: rgba(180, 83, 9, 0.24); --sa-tone-border: rgba(252, 211, 77, 0.45); }
        .dark .sa-health-tone-danger { --sa-tone: #fca5a5; --sa-tone-bg: rgba(185, 28, 28, 0.24); --sa-tone-border: rgba(252, 165, 165, 0.45); }
        .dark .sa-health-tone-info { --sa-tone: #93c5fd; --sa-tone-bg: rgba(29, 78, 216, 0.24); --sa-tone-border: rgba(147, 197, 253, 0.45); }
        .dark .sa-health-tone-primary { --sa-tone: #fdba74; --sa-tone-bg: rgba(154, 52, 18, 0.24); --sa-tone-border: rgba(253, 186, 116, 0.45); }
        .dark .sa-health-tone-gray { --sa-tone: #d1d5db; --sa-tone-bg: rgba(55, 65, 81, 0.42); --sa-tone-border: rgba(209, 213, 219, 0.34); }

        .sa-health-panels {
            display: grid;
            grid-template-columns: repeat(1, minmax(0, 1fr));
            gap: 1rem;
        }

        @media (min-width: 1280px) {
            .sa-health-panels {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        .sa-health-list {
            display: flex;
            flex-direction: column;
            gap: 0.55rem;
        }

        .sa-health-row {
            border: 1px solid #e5e7eb;
            border-radius: 0.85rem;
            padding: 0.55rem 0.65rem;
            background: #fff;
        }

        .dark .sa-health-row {
            border-color: #374151;
            background: #111827;
        }

        .sa-health-row-top {
            display: flex;
            justify-content: space-between;
            gap: 0.7rem;
            align-items: center;
            font-size: 0.8rem;
        }

        .sa-health-row-name {
            font-weight: 600;
            color: #111827;
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .dark .sa-health-row-name {
            color: #f9fafb;
        }

        .sa-health-row-meta {
            color: #6b7280;
            font-size: 0.73rem;
            white-space: nowrap;
        }

        .dark .sa-health-row-meta {
            color: #9ca3af;
        }

        .sa-health-bar {
            margin-top: 0.42rem;
            height: 6px;
            border-radius: 999px;
            background: #e5e7eb;
            overflow: hidden;
        }

        .dark .sa-health-bar {
            background: #1f2937;
        }

        .sa-health-bar-fill {
            height: 100%;
            border-radius: inherit;
            background: linear-gradient(90deg, #ea580c 0%, #f59e0b 100%);
        }

        .sa-health-row-meta-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.12rem 0.4rem;
            border-radius: 999px;
            font-size: 0.66rem;
            border: 1px solid #d1d5db;
            color: #4b5563;
            background: #f9fafb;
        }

        .dark .sa-health-row-meta-chip {
            border-color: #4b5563;
            color: #d1d5db;
            background: #1f2937;
        }

        .sa-health-snapshot {
            display: grid;
            gap: 0.55rem;
            grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
        }

        .sa-health-snapshot-item {
            border: 1px solid #e5e7eb;
            border-radius: 0.8rem;
            padding: 0.5rem 0.6rem;
            background: #fff;
        }

        .dark .sa-health-snapshot-item {
            border-color: #374151;
            background: #111827;
        }

        .sa-health-snapshot-k {
            font-size: 0.65rem;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: #6b7280;
        }

        .dark .sa-health-snapshot-k {
            color: #9ca3af;
        }

        .sa-health-snapshot-v {
            margin-top: 0.22rem;
            font-size: 0.83rem;
            font-weight: 600;
            color: #111827;
            word-break: break-word;
        }

        .dark .sa-health-snapshot-v {
            color: #f9fafb;
        }
    </style>

    @php
        $toneText = [
            'success' => 'stabilnie',
            'warning' => 'uwaga',
            'danger' => 'krytyczne',
            'info' => 'ruch',
            'primary' => 'core',
            'gray' => 'neutral',
        ];

        $activityMax = max(1, (int) collect($this->activityBreakdown)->max('total'));
        $mediaMax = max(1, (int) collect($this->mediaBreakdown)->max('total_size'));
        $queueMax = max(1, (int) collect($this->queueBreakdown)->map(fn ($row) => max((int) $row['pending'], (int) $row['failed']))->max());
        $hotspotMax = max(1, (int) collect($this->conversationHotspots)->max('unread_for_priest'));
    @endphp

    <div class="sa-health-shell" wire:poll.30s>
        <div class="sa-health-hero">
            <div>
                <p class="sa-health-hero-title">Centrum nadzoru platformy</p>
                <p class="sa-health-hero-sub">Pelny, globalny podglad kondycji aplikacji, tenantow, tresci i obciazenia runtime.</p>
            </div>
            <div class="sa-health-refresh">Auto-refresh co 30s · {{ now()->format('d.m.Y H:i:s') }}</div>
        </div>

        <x-filament::section
            heading="Globalny przeglad KPI"
            description="Najwazniejsze liczby dla calej platformy."
        >
            <div class="sa-health-grid overview">
                @foreach ($this->overviewCards as $card)
                    <div class="sa-health-card sa-health-tone-{{ $card['color'] }}">
                        <div class="sa-health-top">
                            <p class="sa-health-label">{{ $card['label'] }}</p>
                            <span class="sa-health-pill">{{ $toneText[$card['color']] ?? $card['color'] }}</span>
                        </div>
                        <p class="sa-health-value">{{ $card['value'] }}</p>
                        <p class="sa-health-hint">{{ $card['hint'] }}</p>
                    </div>
                @endforeach
            </div>
        </x-filament::section>

        <x-filament::section
            heading="Infrastruktura i runtime"
            description="Baza danych, kolejki i fundamenty runtime."
        >
            <div class="sa-health-grid infra">
                @foreach ($this->infrastructureCards as $card)
                    <div class="sa-health-card sa-health-tone-{{ $card['color'] }}">
                        <div class="sa-health-top">
                            <p class="sa-health-label">{{ $card['label'] }}</p>
                            <span class="sa-health-pill">{{ $toneText[$card['color']] ?? $card['color'] }}</span>
                        </div>
                        <p class="sa-health-value" style="font-size: 1.25rem;">{{ $card['value'] }}</p>
                        <p class="sa-health-hint">{{ $card['hint'] }}</p>
                    </div>
                @endforeach
            </div>
        </x-filament::section>

        <div class="sa-health-panels">
            <x-filament::section
                heading="Top eventy (24h)"
                description="Najczestsze zdarzenia z activity_log."
            >
                <div class="sa-health-list">
                    @forelse ($this->activityBreakdown as $row)
                        @php
                            $pct = (int) round(($row['total'] / $activityMax) * 100);
                        @endphp
                        <div class="sa-health-row">
                            <div class="sa-health-row-top">
                                <p class="sa-health-row-name">{{ $row['event'] }}</p>
                                <p class="sa-health-row-meta">{{ number_format($row['total'], 0, ',', ' ') }}</p>
                            </div>
                            <div class="sa-health-bar">
                                <div class="sa-health-bar-fill" style="width: {{ max(3, $pct) }}%;"></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">Brak danych w activity_log (ostatnie 24h).</p>
                    @endforelse
                </div>
            </x-filament::section>

            <x-filament::section
                heading="Media per dysk"
                description="Pliki i laczny rozmiar per storage disk."
            >
                <div class="sa-health-list">
                    @forelse ($this->mediaBreakdown as $row)
                        @php
                            $pct = (int) round(($row['total_size'] / $mediaMax) * 100);
                        @endphp
                        <div class="sa-health-row">
                            <div class="sa-health-row-top">
                                <p class="sa-health-row-name">{{ $row['disk'] }}</p>
                                <p class="sa-health-row-meta">{{ $row['total_size_human'] }}</p>
                            </div>
                            <div class="mt-1 flex items-center gap-2">
                                <span class="sa-health-row-meta-chip">pliki: {{ number_format($row['files_count'], 0, ',', ' ') }}</span>
                            </div>
                            <div class="sa-health-bar">
                                <div class="sa-health-bar-fill" style="width: {{ max(3, $pct) }}%;"></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">Brak rekordow w tabeli media.</p>
                    @endforelse
                </div>
            </x-filament::section>
        </div>

        <div class="sa-health-panels">
            <x-filament::section
                heading="Kolejki per nazwa"
                description="Pending i failed jobs pogrupowane po queue."
            >
                <div class="sa-health-list">
                    @forelse ($this->queueBreakdown as $row)
                        @php
                            $pendingPct = (int) round(($row['pending'] / $queueMax) * 100);
                            $failedPct = (int) round(($row['failed'] / $queueMax) * 100);
                        @endphp
                        <div class="sa-health-row">
                            <div class="sa-health-row-top">
                                <p class="sa-health-row-name">{{ $row['queue'] }}</p>
                                <p class="sa-health-row-meta">pending {{ number_format($row['pending'], 0, ',', ' ') }} · failed {{ number_format($row['failed'], 0, ',', ' ') }}</p>
                            </div>
                            <div class="mt-2 space-y-1">
                                <div class="sa-health-bar"><div class="sa-health-bar-fill" style="width: {{ max(2, $pendingPct) }}%;"></div></div>
                                <div class="sa-health-bar"><div class="sa-health-bar-fill" style="width: {{ max($row['failed'] > 0 ? 2 : 0, $failedPct) }}%; background: linear-gradient(90deg, #dc2626 0%, #f97316 100%);"></div></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">Brak danych kolejkowych.</p>
                    @endforelse
                </div>
            </x-filament::section>

            <x-filament::section
                heading="Hotspoty konwersacji"
                description="Parafie z najwyzsza aktywnoscia kancelarii online."
            >
                <div class="sa-health-list">
                    @forelse ($this->conversationHotspots as $row)
                        @php
                            $pct = (int) round(($row['unread_for_priest'] / $hotspotMax) * 100);
                        @endphp
                        <div class="sa-health-row">
                            <div class="sa-health-row-top">
                                <p class="sa-health-row-name">{{ $row['parish_name'] }}</p>
                                <p class="sa-health-row-meta">nieprzeczytane {{ number_format($row['unread_for_priest'], 0, ',', ' ') }}</p>
                            </div>
                            <div class="mt-1 flex items-center gap-2">
                                <span class="sa-health-row-meta-chip">watki {{ number_format($row['conversations_count'], 0, ',', ' ') }}</span>
                                <span class="sa-health-row-meta-chip">otwarte {{ number_format($row['open_count'], 0, ',', ' ') }}</span>
                            </div>
                            <div class="sa-health-bar">
                                <div class="sa-health-bar-fill" style="width: {{ max($row['unread_for_priest'] > 0 ? 3 : 0, $pct) }}%;"></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">Brak konwersacji online.</p>
                    @endforelse
                </div>
            </x-filament::section>
        </div>

        <x-filament::section
            heading="Snapshot konfiguracji"
            description="Biezace parametry runtime i konfiguracji aplikacji."
        >
            <div class="sa-health-snapshot">
                @foreach ($this->systemSnapshot as $row)
                    <div class="sa-health-snapshot-item">
                        <p class="sa-health-snapshot-k">{{ $row['label'] }}</p>
                        <p class="sa-health-snapshot-v">{{ $row['value'] }}</p>
                    </div>
                @endforeach
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
