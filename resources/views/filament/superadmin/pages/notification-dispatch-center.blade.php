<x-filament-panels::page>
    <style>
        .dispatch-shell {
            --dispatch-bg: linear-gradient(180deg, #fffaf3 0%, #ffffff 100%);
            --dispatch-border: rgba(148, 163, 184, 0.22);
            --dispatch-text: #0f172a;
            --dispatch-muted: #64748b;
            --dispatch-panel: rgba(255, 255, 255, 0.92);
            --dispatch-shadow: 0 18px 50px rgba(15, 23, 42, 0.08);
            --dispatch-success: #15803d;
            --dispatch-warning: #b45309;
            --dispatch-danger: #b91c1c;
            --dispatch-info: #0369a1;
        }

        .dark .dispatch-shell {
            --dispatch-bg: linear-gradient(180deg, #0b1220 0%, #111827 100%);
            --dispatch-border: rgba(71, 85, 105, 0.5);
            --dispatch-text: #f8fafc;
            --dispatch-muted: #94a3b8;
            --dispatch-panel: rgba(15, 23, 42, 0.88);
            --dispatch-shadow: 0 22px 56px rgba(2, 6, 23, 0.4);
            --dispatch-success: #4ade80;
            --dispatch-warning: #fbbf24;
            --dispatch-danger: #f87171;
            --dispatch-info: #38bdf8;
        }

        .dispatch-shell {
            display: grid;
            gap: 1.5rem;
            padding: 1.25rem;
            border-radius: 1.75rem;
            background: var(--dispatch-bg);
            color: var(--dispatch-text);
        }

        .dispatch-hero {
            display: grid;
            gap: 1.25rem;
            padding: 1.5rem;
            border-radius: 1.5rem;
            border: 1px solid var(--dispatch-border);
            background: linear-gradient(135deg, rgba(251, 191, 36, 0.12), rgba(14, 165, 233, 0.08)), var(--dispatch-panel);
            box-shadow: var(--dispatch-shadow);
        }

        .dispatch-hero-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
        }

        .dispatch-kicker {
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: var(--dispatch-muted);
        }

        .dispatch-title {
            margin-top: 0.4rem;
            font-size: clamp(1.7rem, 3vw, 2.4rem);
            font-weight: 800;
            line-height: 1.05;
        }

        .dispatch-subtitle {
            margin-top: 0.65rem;
            max-width: 58rem;
            font-size: 0.98rem;
            color: var(--dispatch-muted);
        }

        .dispatch-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.6rem 0.9rem;
            border-radius: 999px;
            border: 1px solid var(--dispatch-border);
            background: rgba(255, 255, 255, 0.72);
            font-size: 0.82rem;
            font-weight: 700;
            color: var(--dispatch-text);
        }

        .dark .dispatch-badge {
            background: rgba(15, 23, 42, 0.72);
        }

        .dispatch-grid {
            display: grid;
            gap: 1rem;
        }

        .dispatch-grid.cards {
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        }

        .dispatch-card {
            padding: 1rem 1.05rem;
            border-radius: 1.25rem;
            border: 1px solid var(--dispatch-border);
            background: var(--dispatch-panel);
            box-shadow: var(--dispatch-shadow);
        }

        .dispatch-card-label {
            font-size: 0.76rem;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--dispatch-muted);
        }

        .dispatch-card-value {
            margin-top: 0.6rem;
            font-size: 2rem;
            font-weight: 800;
            line-height: 1;
        }

        .dispatch-card-hint {
            margin-top: 0.6rem;
            font-size: 0.9rem;
            color: var(--dispatch-muted);
        }

        .dispatch-card[data-tone="danger"] .dispatch-card-value { color: var(--dispatch-danger); }
        .dispatch-card[data-tone="warning"] .dispatch-card-value { color: var(--dispatch-warning); }
        .dispatch-card[data-tone="success"] .dispatch-card-value { color: var(--dispatch-success); }
        .dispatch-card[data-tone="info"] .dispatch-card-value { color: var(--dispatch-info); }

        .dispatch-layout {
            display: grid;
            gap: 1.5rem;
            grid-template-columns: minmax(0, 1.6fr) minmax(320px, 0.95fr);
        }

        .dispatch-stack {
            display: grid;
            gap: 1.5rem;
        }

        .dispatch-panel {
            padding: 1.25rem;
            border-radius: 1.5rem;
            border: 1px solid var(--dispatch-border);
            background: var(--dispatch-panel);
            box-shadow: var(--dispatch-shadow);
        }

        .dispatch-panel-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .dispatch-panel-title {
            font-size: 1.05rem;
            font-weight: 800;
        }

        .dispatch-panel-copy {
            margin-top: 0.25rem;
            font-size: 0.92rem;
            color: var(--dispatch-muted);
        }

        .dispatch-chip-row {
            display: flex;
            flex-wrap: wrap;
            gap: 0.55rem;
        }

        .dispatch-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.42rem 0.72rem;
            border-radius: 999px;
            font-size: 0.78rem;
            font-weight: 700;
            border: 1px solid var(--dispatch-border);
            background: rgba(148, 163, 184, 0.1);
        }

        .dispatch-chip[data-tone="danger"] { color: var(--dispatch-danger); }
        .dispatch-chip[data-tone="warning"] { color: var(--dispatch-warning); }
        .dispatch-chip[data-tone="success"] { color: var(--dispatch-success); }
        .dispatch-chip[data-tone="info"] { color: var(--dispatch-info); }

        .dispatch-list {
            display: grid;
            gap: 0.85rem;
        }

        .dispatch-item {
            padding: 0.95rem 1rem;
            border-radius: 1.15rem;
            border: 1px solid var(--dispatch-border);
            background: rgba(255, 255, 255, 0.66);
        }

        .dark .dispatch-item {
            background: rgba(15, 23, 42, 0.6);
        }

        .dispatch-item-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 0.85rem;
        }

        .dispatch-item-title {
            font-size: 0.98rem;
            font-weight: 700;
        }

        .dispatch-item-meta {
            margin-top: 0.2rem;
            font-size: 0.85rem;
            color: var(--dispatch-muted);
        }

        .dispatch-item-id {
            font-size: 0.76rem;
            font-weight: 700;
            color: var(--dispatch-muted);
        }

        .dispatch-status {
            display: inline-flex;
            align-items: center;
            padding: 0.32rem 0.65rem;
            border-radius: 999px;
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            border: 1px solid currentColor;
        }

        .dispatch-status[data-tone="danger"] { color: var(--dispatch-danger); }
        .dispatch-status[data-tone="warning"] { color: var(--dispatch-warning); }
        .dispatch-status[data-tone="success"] { color: var(--dispatch-success); }
        .dispatch-status[data-tone="info"] { color: var(--dispatch-info); }

        .dispatch-split {
            display: grid;
            gap: 1rem;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .dispatch-mini-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.65rem;
        }

        .dispatch-progress {
            display: grid;
            gap: 0.55rem;
            margin-top: 0.85rem;
        }

        .dispatch-progress-row {
            display: grid;
            gap: 0.35rem;
        }

        .dispatch-progress-top {
            display: flex;
            justify-content: space-between;
            gap: 0.75rem;
            font-size: 0.8rem;
            color: var(--dispatch-muted);
        }

        .dispatch-bar {
            overflow: hidden;
            height: 0.55rem;
            border-radius: 999px;
            background: rgba(148, 163, 184, 0.16);
        }

        .dispatch-bar-fill {
            height: 100%;
            border-radius: inherit;
            background: linear-gradient(90deg, #f59e0b, #ef4444);
        }

        .dispatch-bar-fill.success {
            background: linear-gradient(90deg, #10b981, #22c55e);
        }

        .dispatch-empty {
            padding: 1rem 1.1rem;
            border-radius: 1rem;
            border: 1px dashed var(--dispatch-border);
            color: var(--dispatch-muted);
            font-size: 0.9rem;
        }

        @media (max-width: 1100px) {
            .dispatch-layout,
            .dispatch-split {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="dispatch-shell" wire:poll.30s>
        <section class="dispatch-hero">
            <div class="dispatch-hero-top">
                <div>
                    <p class="dispatch-kicker">Centrum wysyłki</p>
                    <h1 class="dispatch-title">Centrum dispatchu</h1>
                    <p class="dispatch-subtitle">
                        Widok operacyjny dla wysyłki treści, przypomnień mszalnych i ponawiania e-maili.
                        Priorytetem nie jest historia, tylko to, co wymaga reakcji teraz.
                    </p>
                </div>
                <div class="dispatch-badge">
                    <span>Automatyczne odświeżanie</span>
                    <strong>30s</strong>
                </div>
            </div>

            <div class="dispatch-grid cards">
                @foreach ($this->heroCards as $card)
                    <article class="dispatch-card" data-tone="{{ $card['tone'] }}">
                        <p class="dispatch-card-label">{{ $card['label'] }}</p>
                        <p class="dispatch-card-value">{{ $card['value'] }}</p>
                        <p class="dispatch-card-hint">{{ $card['hint'] }}</p>
                    </article>
                @endforeach
            </div>
        </section>

        <section class="dispatch-grid cards">
            @foreach ($this->quickStats as $card)
                <article class="dispatch-card" data-tone="{{ $card['tone'] }}">
                    <p class="dispatch-card-label">{{ $card['label'] }}</p>
                    <p class="dispatch-card-value" style="font-size: 1.55rem;">{{ $card['value'] }}</p>
                    <p class="dispatch-card-hint">{{ $card['meta'] }}</p>
                </article>
            @endforeach
        </section>

        <div class="dispatch-layout">
            <div class="dispatch-stack">
                <section class="dispatch-panel">
                    <div class="dispatch-panel-head">
                        <div>
                            <h2 class="dispatch-panel-title">Backlog wysyłki treści</h2>
                            <p class="dispatch-panel-copy">
                                Rekordy, które kwalifikują się już do opóźnionej wysyłki i nadal nie mają kompletu dostarczeń.
                            </p>
                        </div>
                        <div class="dispatch-mini-actions">
                            <x-filament::button color="primary" size="sm" wire:click="mountAction('run_delayed_content_dispatch')">
                                Uruchom teraz
                            </x-filament::button>
                        </div>
                    </div>

                    <div class="dispatch-split">
                        <div class="dispatch-list">
                            <div class="dispatch-chip-row">
                                <span class="dispatch-chip" data-tone="warning">Aktualności oczekujące: {{ count($this->pendingNews) }}</span>
                            </div>

                            @forelse ($this->pendingNews as $row)
                                <article class="dispatch-item">
                                    <div class="dispatch-item-top">
                                        <div>
                                            <div class="dispatch-item-title">
                                                <a href="{{ $row['url'] }}" class="hover:underline">
                                                    {{ $row['title'] }}
                                                </a>
                                            </div>
                                            <div class="dispatch-item-meta">{{ $row['parish'] }} · publikacja {{ $row['published_at'] }}</div>
                                        </div>
                                        <div class="text-right">
                                            <div class="dispatch-item-id">#{{ $row['id'] }}</div>
                                            <span class="dispatch-status" data-tone="{{ $row['status']['tone'] }}">{{ $row['status']['label'] }}</span>
                                        </div>
                                    </div>
                                    <div class="dispatch-item-meta mt-3">Opóźnienie: {{ number_format($row['delay_minutes'], 0, ',', ' ') }} min</div>
                                    <div class="dispatch-mini-actions mt-4">
                                        <x-filament::button tag="a" size="sm" color="gray" :href="$row['url']">
                                            Otwórz rekord
                                        </x-filament::button>
                                    </div>
                                </article>
                            @empty
                                <div class="dispatch-empty">Brak aktualności wymagających wysyłki.</div>
                            @endforelse
                        </div>

                        <div class="dispatch-list">
                            <div class="dispatch-chip-row">
                                <span class="dispatch-chip" data-tone="warning">Ogłoszenia oczekujące: {{ count($this->pendingAnnouncementSets) }}</span>
                            </div>

                            @forelse ($this->pendingAnnouncementSets as $row)
                                <article class="dispatch-item">
                                    <div class="dispatch-item-top">
                                        <div>
                                            <div class="dispatch-item-title">
                                                <a href="{{ $row['url'] }}" class="hover:underline">
                                                    {{ $row['title'] }}
                                                </a>
                                            </div>
                                            <div class="dispatch-item-meta">{{ $row['parish'] }} · publikacja {{ $row['published_at'] }}</div>
                                        </div>
                                        <div class="text-right">
                                            <div class="dispatch-item-id">#{{ $row['id'] }}</div>
                                            <span class="dispatch-status" data-tone="{{ $row['status']['tone'] }}">{{ $row['status']['label'] }}</span>
                                        </div>
                                    </div>
                                    <div class="dispatch-item-meta mt-3">Opóźnienie: {{ number_format($row['delay_minutes'], 0, ',', ' ') }} min</div>
                                    <div class="dispatch-mini-actions mt-4">
                                        <x-filament::button tag="a" size="sm" color="gray" :href="$row['url']">
                                            Otwórz rekord
                                        </x-filament::button>
                                    </div>
                                </article>
                            @empty
                                <div class="dispatch-empty">Brak zestawów ogłoszeń wymagających wysyłki.</div>
                            @endforelse
                        </div>
                    </div>
                </section>

                <section class="dispatch-panel">
                    <div class="dispatch-panel-head">
                        <div>
                            <h2 class="dispatch-panel-title">Przypomnienia mszalne</h2>
                            <p class="dispatch-panel-copy">
                                Najbliższe Msze, zaległe okna 24 h / 8 h / 1 h oraz poranny skrót o 5:00 dla użytkownika.
                            </p>
                        </div>
                        <div class="dispatch-mini-actions">
                            <x-filament::button color="info" size="sm" wire:click="mountAction('run_mass_push_dispatch')">
                                Wyślij przypomnienia push
                            </x-filament::button>
                            <x-filament::button color="gray" size="sm" wire:click="mountAction('run_mass_digest_dispatch')">
                                Wyślij skrót 5:00
                            </x-filament::button>
                        </div>
                    </div>

                    @php
                        $massSummary = $this->massReminderSummary;
                    @endphp
                    <div class="dispatch-chip-row">
                        <span class="dispatch-chip" data-tone="warning">24h oczekujące: {{ $massSummary['due_24h'] }}</span>
                        <span class="dispatch-chip" data-tone="warning">8h oczekujące: {{ $massSummary['due_8h'] }}</span>
                        <span class="dispatch-chip" data-tone="danger">1h oczekujące: {{ $massSummary['due_1h'] }}</span>
                        <span class="dispatch-chip" data-tone="info">Użytkownicy skrótu: {{ $massSummary['due_digest_users'] }}</span>
                    </div>

                    <div class="dispatch-list mt-4">
                        @forelse ($this->upcomingMasses as $row)
                            @php
                                $participants = max(1, $row['participants']);
                                $totalDue = $row['due_24h'] + $row['due_8h'] + $row['due_1h'];
                                $completion24h = (int) round(($row['sent_24h'] / $participants) * 100);
                                $completion8h = (int) round(($row['sent_8h'] / $participants) * 100);
                                $completion1h = (int) round(($row['sent_1h'] / $participants) * 100);
                                $completionDigest = (int) round(($row['digest_sent'] / $participants) * 100);
                            @endphp
                            <article class="dispatch-item">
                                <div class="dispatch-item-top">
                                    <div>
                                        <div class="dispatch-item-title">
                                            <a href="{{ $row['url'] }}" class="hover:underline">
                                                {{ $row['title'] ?: 'Msza bez tytułu intencji' }}
                                            </a>
                                        </div>
                                        <div class="dispatch-item-meta">{{ $row['parish'] }} · {{ $row['celebration_at'] }} · uczestnicy {{ $row['participants'] }}</div>
                                    </div>
                                    <div class="text-right">
                                        <div class="dispatch-item-id">#{{ $row['id'] }}</div>
                                        <span class="dispatch-status" data-tone="{{ $totalDue > 0 ? 'warning' : 'success' }}">
                                            {{ $totalDue > 0 ? 'Oczekuje' : 'Czysto' }}
                                        </span>
                                    </div>
                                </div>

                                <div class="dispatch-chip-row mt-3">
                                    <span class="dispatch-chip" data-tone="warning">24h oczekujące {{ $row['due_24h'] }}</span>
                                    <span class="dispatch-chip" data-tone="warning">8h oczekujące {{ $row['due_8h'] }}</span>
                                    <span class="dispatch-chip" data-tone="danger">1h oczekujące {{ $row['due_1h'] }}</span>
                                    <span class="dispatch-chip" data-tone="info">skrót oczekujący {{ $row['digest_pending'] }}</span>
                                </div>

                                <div class="dispatch-progress">
                                    <div class="dispatch-progress-row">
                                        <div class="dispatch-progress-top"><span>24h</span><span>{{ $row['sent_24h'] }}/{{ $row['participants'] }}</span></div>
                                        <div class="dispatch-bar"><div class="dispatch-bar-fill {{ $completion24h >= 100 ? 'success' : '' }}" style="width: {{ max(4, $completion24h) }}%;"></div></div>
                                    </div>
                                    <div class="dispatch-progress-row">
                                        <div class="dispatch-progress-top"><span>8h</span><span>{{ $row['sent_8h'] }}/{{ $row['participants'] }}</span></div>
                                        <div class="dispatch-bar"><div class="dispatch-bar-fill {{ $completion8h >= 100 ? 'success' : '' }}" style="width: {{ max(4, $completion8h) }}%;"></div></div>
                                    </div>
                                    <div class="dispatch-progress-row">
                                        <div class="dispatch-progress-top"><span>1h</span><span>{{ $row['sent_1h'] }}/{{ $row['participants'] }}</span></div>
                                        <div class="dispatch-bar"><div class="dispatch-bar-fill {{ $completion1h >= 100 ? 'success' : '' }}" style="width: {{ max(4, $completion1h) }}%;"></div></div>
                                    </div>
                                    <div class="dispatch-progress-row">
                                        <div class="dispatch-progress-top"><span>Digest 5:00</span><span>{{ $row['digest_sent'] }}/{{ $row['participants'] }}</span></div>
                                        <div class="dispatch-bar"><div class="dispatch-bar-fill {{ $completionDigest >= 100 ? 'success' : '' }}" style="width: {{ max(4, $completionDigest) }}%;"></div></div>
                                    </div>
                                </div>
                                <div class="dispatch-mini-actions mt-4">
                                    <x-filament::button tag="a" size="sm" color="gray" :href="$row['url']">
                                        Otwórz mszę
                                    </x-filament::button>
                                </div>
                            </article>
                        @empty
                            <div class="dispatch-empty">Brak nadchodzących mszy w horyzoncie 3 dni.</div>
                        @endforelse
                    </div>
                </section>
            </div>

            <div class="dispatch-stack">
                <section class="dispatch-panel">
                    <div class="dispatch-panel-head">
                        <div>
                            <h2 class="dispatch-panel-title">Nieudane zadania e-mail</h2>
                            <p class="dispatch-panel-copy">
                                Szybki widok problemów z e-mailami. Ponowienie i usunięcie wpisu są dostępne bez wychodzenia z tej strony.
                            </p>
                        </div>
                        <div class="dispatch-mini-actions">
                            <x-filament::button color="warning" size="sm" wire:click="mountAction('retry_all_failed_mail_jobs')">
                                Ponów wszystkie
                            </x-filament::button>
                        </div>
                    </div>

                    <div class="dispatch-chip-row">
                        @forelse ($this->failedMailStats as $row)
                            <span class="dispatch-chip" data-tone="danger">{{ $row['type'] }}: {{ $row['count'] }}</span>
                        @empty
                            <span class="dispatch-chip" data-tone="success">Brak nieudanych zadań e-mail</span>
                        @endforelse
                    </div>

                    <div class="dispatch-list mt-4">
                        @forelse ($this->failedMailJobs as $row)
                            <article class="dispatch-item">
                                <div class="dispatch-item-top">
                                    <div>
                                        <div class="dispatch-item-title">{{ $row['type'] }}</div>
                                        <div class="dispatch-item-meta">zadanie #{{ $row['id'] }} · kolejka {{ $row['queue'] }} · {{ $row['failed_at'] }}</div>
                                    </div>
                                    <span class="dispatch-status" data-tone="danger">Błąd</span>
                                </div>

                                <div class="dispatch-item-meta mt-3">{{ $row['exception_headline'] }}</div>

                                <div class="dispatch-mini-actions mt-4">
                                    <x-filament::button color="warning" size="sm" wire:click="retryFailedJob({{ $row['id'] }})">
                                        Ponów
                                    </x-filament::button>
                                    <x-filament::button color="gray" size="sm" wire:click="forgetFailedJob({{ $row['id'] }})">
                                        Usuń wpis
                                    </x-filament::button>
                                </div>
                            </article>
                        @empty
                            <div class="dispatch-empty">Brak nieudanych zadań e-mail.</div>
                        @endforelse
                    </div>
                </section>

                <section class="dispatch-panel">
                    <div class="dispatch-panel-head">
                        <div>
                            <h2 class="dispatch-panel-title">Ostatnio zamknięte wysyłki</h2>
                            <p class="dispatch-panel-copy">
                                Ostatnie rekordy treści, dla których wysyłka push została już zamknięta.
                            </p>
                        </div>
                    </div>

                    <div class="dispatch-list">
                        @forelse ($this->recentlyDispatchedContent as $row)
                            <article class="dispatch-item">
                                <div class="dispatch-item-top">
                                    <div>
                                        <div class="dispatch-item-title">
                                            <a href="{{ $row['url'] }}" class="hover:underline">
                                                {{ $row['title'] }}
                                            </a>
                                        </div>
                                        <div class="dispatch-item-meta">{{ $row['parish'] }} · {{ $row['dispatched_at'] }}</div>
                                    </div>
                                    <span class="dispatch-status" data-tone="success">{{ $row['type'] }}</span>
                                </div>
                                <div class="dispatch-mini-actions mt-4">
                                    <x-filament::button tag="a" size="sm" color="gray" :href="$row['url']">
                                        Otwórz rekord
                                    </x-filament::button>
                                </div>
                            </article>
                        @empty
                            <div class="dispatch-empty">Brak świeżo zakończonych wysyłek treści.</div>
                        @endforelse
                    </div>
                </section>
            </div>
        </div>
    </div>
</x-filament-panels::page>
