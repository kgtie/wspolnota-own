@if ($includeStyles ?? false)
    <style>
        .admin-dashboard {
            display: flex;
            flex-direction: column;
            gap: 1.35rem;
        }

        .admin-dashboard__hero {
            position: relative;
            display: grid;
            grid-template-columns: minmax(0, 1.7fr) minmax(22rem, 1fr);
            gap: 1.1rem;
            padding: 1.45rem;
            border: 1px solid rgba(148, 163, 184, 0.22);
            border-radius: 2rem;
            overflow: hidden;
            background:
                linear-gradient(135deg, rgba(255, 248, 235, 0.95) 0%, rgba(255, 255, 255, 0.94) 52%, rgba(239, 246, 255, 0.92) 100%),
                radial-gradient(circle at top left, rgba(251, 191, 36, 0.25), transparent 28%);
            box-shadow: 0 24px 48px rgba(15, 23, 42, 0.08);
        }

        .admin-dashboard__hero::after {
            content: "";
            position: absolute;
            inset: 0;
            background:
                linear-gradient(110deg, rgba(255, 255, 255, 0.9) 20%, rgba(255, 255, 255, 0.5) 58%, rgba(15, 23, 42, 0.12) 100%),
                var(--dashboard-cover, none) center / cover no-repeat;
            opacity: 0.45;
            pointer-events: none;
        }

        .admin-dashboard__hero-main,
        .admin-dashboard__hero-side {
            position: relative;
            z-index: 1;
        }

        .admin-dashboard__eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.35rem 0.78rem;
            border-radius: 999px;
            background: rgba(255, 251, 235, 0.92);
            color: #92400e;
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }

        .admin-dashboard__title {
            margin: 0.9rem 0 0;
            max-width: 44rem;
            font-size: clamp(1.85rem, 2.5vw, 3rem);
            line-height: 1.02;
            letter-spacing: -0.06em;
            color: #0f172a;
        }

        .admin-dashboard__lead {
            margin: 0.85rem 0 0;
            max-width: 45rem;
            color: #334155;
            line-height: 1.7;
        }

        .admin-dashboard__meta {
            display: flex;
            flex-wrap: wrap;
            gap: 0.65rem;
            margin-top: 1rem;
        }

        .admin-dashboard__meta-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.5rem 0.8rem;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.84);
            border: 1px solid rgba(226, 232, 240, 0.9);
            color: #334155;
            font-size: 0.84rem;
            font-weight: 600;
        }

        .admin-dashboard__hero-side {
            display: grid;
            gap: 0.95rem;
            align-content: start;
        }

        .admin-dashboard__identity {
            display: grid;
            grid-template-columns: auto minmax(0, 1fr);
            gap: 0.9rem;
            align-items: center;
            padding: 1rem;
            border-radius: 1.5rem;
            background: rgba(255, 255, 255, 0.88);
            border: 1px solid rgba(226, 232, 240, 0.94);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.4);
        }

        .admin-dashboard__avatar {
            width: 4.5rem;
            height: 4.5rem;
            border-radius: 1.2rem;
            object-fit: cover;
            border: 1px solid rgba(226, 232, 240, 0.95);
            background: rgba(248, 250, 252, 0.9);
        }

        .admin-dashboard__identity-label {
            margin: 0;
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: #64748b;
        }

        .admin-dashboard__identity-name {
            margin: 0.25rem 0 0;
            font-size: 1.25rem;
            font-weight: 700;
            color: #0f172a;
            letter-spacing: -0.03em;
        }

        .admin-dashboard__identity-copy {
            margin: 0.35rem 0 0;
            color: #475569;
            line-height: 1.55;
        }

        .admin-dashboard__completion {
            padding: 1rem;
            border-radius: 1.5rem;
            background: rgba(15, 23, 42, 0.95);
            color: #f8fafc;
            box-shadow: 0 18px 34px rgba(15, 23, 42, 0.16);
        }

        .admin-dashboard__completion-top {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            gap: 1rem;
        }

        .admin-dashboard__completion-label {
            font-size: 0.8rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: rgba(248, 250, 252, 0.76);
        }

        .admin-dashboard__completion-value {
            font-size: 2rem;
            font-weight: 700;
            letter-spacing: -0.05em;
        }

        .admin-dashboard__progress {
            height: 0.72rem;
            margin-top: 0.9rem;
            border-radius: 999px;
            background: rgba(148, 163, 184, 0.22);
            overflow: hidden;
        }

        .admin-dashboard__progress-bar {
            height: 100%;
            border-radius: inherit;
            background: linear-gradient(90deg, #f59e0b 0%, #fde68a 48%, #f8fafc 100%);
        }

        .admin-dashboard__completion-copy {
            margin: 0.9rem 0 0;
            color: rgba(241, 245, 249, 0.84);
            line-height: 1.6;
        }

        .admin-dashboard__completion-list {
            display: flex;
            flex-wrap: wrap;
            gap: 0.45rem;
            margin-top: 0.8rem;
        }

        .admin-dashboard__completion-pill {
            display: inline-flex;
            align-items: center;
            padding: 0.35rem 0.65rem;
            border-radius: 999px;
            background: rgba(30, 41, 59, 0.92);
            color: rgba(248, 250, 252, 0.9);
            font-size: 0.78rem;
        }

        .admin-dashboard__grid-title {
            margin: 0;
            font-size: 0.82rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: #64748b;
        }

        .admin-dashboard__section-heading {
            display: flex;
            flex-wrap: wrap;
            align-items: end;
            justify-content: space-between;
            gap: 0.8rem;
            margin-bottom: 0.15rem;
        }

        .admin-dashboard__section-title {
            margin: 0;
            font-size: 1.2rem;
            font-weight: 700;
            letter-spacing: -0.04em;
            color: #0f172a;
        }

        .admin-dashboard__section-copy {
            margin: 0.2rem 0 0;
            color: #475569;
            line-height: 1.6;
        }

        .admin-dashboard__metrics,
        .admin-dashboard__priorities,
        .admin-dashboard__actions,
        .admin-dashboard__areas {
            display: grid;
            gap: 1rem;
        }

        .admin-dashboard__metrics {
            grid-template-columns: repeat(5, minmax(0, 1fr));
        }

        .admin-dashboard__priorities {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .admin-dashboard__actions {
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }

        .admin-dashboard__areas {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .admin-dashboard__metric,
        .admin-dashboard__priority,
        .admin-dashboard__action,
        .admin-dashboard__area {
            position: relative;
            border-radius: 1.55rem;
            border: 1px solid rgba(226, 232, 240, 0.95);
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.98) 0%, rgba(248, 250, 252, 0.9) 100%);
            box-shadow: 0 18px 34px rgba(15, 23, 42, 0.05);
        }

        .admin-dashboard__metric {
            padding: 1rem 1.05rem;
        }

        .admin-dashboard__metric-head {
            display: flex;
            justify-content: space-between;
            gap: 0.85rem;
            align-items: start;
        }

        .admin-dashboard__metric-label {
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: #64748b;
        }

        .admin-dashboard__metric-value {
            margin-top: 0.28rem;
            font-size: 1.9rem;
            font-weight: 700;
            letter-spacing: -0.05em;
            color: #0f172a;
        }

        .admin-dashboard__metric-copy {
            margin: 0.6rem 0 0;
            color: #475569;
            line-height: 1.55;
        }

        .admin-dashboard__icon {
            width: 1.05rem;
            height: 1.05rem;
        }

        .admin-dashboard__tone-dot {
            width: 0.8rem;
            height: 0.8rem;
            border-radius: 999px;
            flex: none;
            margin-top: 0.16rem;
        }

        .admin-dashboard__metric[data-tone="success"] .admin-dashboard__tone-dot,
        .admin-dashboard__priority[data-tone="success"] .admin-dashboard__priority-bar,
        .admin-dashboard__action[data-tone="success"] .admin-dashboard__action-icon,
        .admin-dashboard__area[data-tone="success"] .admin-dashboard__area-pill {
            background: #10b981;
            color: #064e3b;
        }

        .admin-dashboard__metric[data-tone="warning"] .admin-dashboard__tone-dot,
        .admin-dashboard__priority[data-tone="warning"] .admin-dashboard__priority-bar,
        .admin-dashboard__action[data-tone="warning"] .admin-dashboard__action-icon,
        .admin-dashboard__area[data-tone="warning"] .admin-dashboard__area-pill {
            background: #f59e0b;
            color: #78350f;
        }

        .admin-dashboard__metric[data-tone="danger"] .admin-dashboard__tone-dot,
        .admin-dashboard__priority[data-tone="danger"] .admin-dashboard__priority-bar,
        .admin-dashboard__action[data-tone="danger"] .admin-dashboard__action-icon,
        .admin-dashboard__area[data-tone="danger"] .admin-dashboard__area-pill {
            background: #ef4444;
            color: #7f1d1d;
        }

        .admin-dashboard__metric[data-tone="info"] .admin-dashboard__tone-dot,
        .admin-dashboard__priority[data-tone="info"] .admin-dashboard__priority-bar,
        .admin-dashboard__action[data-tone="info"] .admin-dashboard__action-icon,
        .admin-dashboard__area[data-tone="info"] .admin-dashboard__area-pill {
            background: #0ea5e9;
            color: #0c4a6e;
        }

        .admin-dashboard__metric[data-tone="neutral"] .admin-dashboard__tone-dot,
        .admin-dashboard__priority[data-tone="neutral"] .admin-dashboard__priority-bar,
        .admin-dashboard__action[data-tone="neutral"] .admin-dashboard__action-icon,
        .admin-dashboard__area[data-tone="neutral"] .admin-dashboard__area-pill {
            background: #cbd5e1;
            color: #334155;
        }

        .admin-dashboard__priority {
            display: flex;
            flex-direction: column;
            padding: 1.2rem;
            overflow: hidden;
        }

        .admin-dashboard__priority-bar {
            position: absolute;
            inset: 0 auto 0 0;
            width: 0.34rem;
        }

        .admin-dashboard__priority-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-left: 0.5rem;
            color: #64748b;
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .admin-dashboard__priority-title {
            margin: 0.8rem 0 0 0.5rem;
            font-size: 1.08rem;
            line-height: 1.32;
            letter-spacing: -0.03em;
            color: #0f172a;
        }

        .admin-dashboard__priority-copy {
            margin: 0.65rem 0 0 0.5rem;
            color: #475569;
            line-height: 1.65;
            flex: 1;
        }

        .admin-dashboard__priority-meta {
            margin: 0.85rem 0 0 0.5rem;
            color: #64748b;
            font-size: 0.84rem;
            line-height: 1.55;
        }

        .admin-dashboard__priority-link,
        .admin-dashboard__action-link,
        .admin-dashboard__area-link {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            margin-top: 1rem;
            font-weight: 700;
            color: #0f172a;
            text-decoration: none;
        }

        .admin-dashboard__priority-link {
            margin-left: 0.5rem;
        }

        .admin-dashboard__action {
            padding: 1rem;
            transition: transform 140ms ease, box-shadow 140ms ease;
        }

        .admin-dashboard__action:hover {
            transform: translateY(-2px);
            box-shadow: 0 22px 42px rgba(15, 23, 42, 0.09);
        }

        .admin-dashboard__action-link {
            display: block;
            color: inherit;
            text-decoration: none;
            margin-top: 0;
        }

        .admin-dashboard__action-head {
            display: flex;
            align-items: start;
            justify-content: space-between;
            gap: 0.8rem;
        }

        .admin-dashboard__action-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2.65rem;
            height: 2.65rem;
            border-radius: 1rem;
            color: #0f172a;
        }

        .admin-dashboard__action-label {
            margin: 0.75rem 0 0;
            font-size: 1rem;
            font-weight: 700;
            letter-spacing: -0.03em;
            color: #0f172a;
        }

        .admin-dashboard__action-copy {
            margin: 0.45rem 0 0;
            color: #475569;
            line-height: 1.55;
        }

        .admin-dashboard__action-cta {
            margin-top: 0.9rem;
            color: #1e293b;
            font-weight: 700;
        }

        .admin-dashboard__area {
            padding: 1.15rem;
        }

        .admin-dashboard__area-top {
            display: flex;
            align-items: start;
            justify-content: space-between;
            gap: 0.8rem;
        }

        .admin-dashboard__area-title {
            margin: 0;
            font-size: 1.08rem;
            font-weight: 700;
            letter-spacing: -0.03em;
            color: #0f172a;
        }

        .admin-dashboard__area-status {
            margin: 0.4rem 0 0;
            color: #334155;
            font-weight: 600;
        }

        .admin-dashboard__area-copy {
            margin: 0.7rem 0 0;
            color: #475569;
            line-height: 1.62;
        }

        .admin-dashboard__area-pill {
            display: inline-flex;
            align-items: center;
            padding: 0.4rem 0.72rem;
            border-radius: 999px;
            font-size: 0.78rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .admin-dashboard__area-metrics {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 0.8rem;
            margin-top: 1rem;
        }

        .admin-dashboard__area-metric {
            padding: 0.9rem;
            border-radius: 1.15rem;
            background: rgba(248, 250, 252, 0.9);
            border: 1px solid rgba(226, 232, 240, 0.9);
        }

        .admin-dashboard__area-metric-label {
            font-size: 0.76rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: #64748b;
        }

        .admin-dashboard__area-metric-value {
            margin-top: 0.35rem;
            font-size: 1.05rem;
            font-weight: 700;
            color: #0f172a;
            letter-spacing: -0.03em;
        }

        .admin-dashboard__monitoring {
            padding: 0.05rem 0 0.35rem;
        }

        .admin-dashboard__monitoring-copy {
            margin: 0.25rem 0 0;
            color: #64748b;
            line-height: 1.55;
        }

        @media (max-width: 90rem) {
            .admin-dashboard__metrics {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .admin-dashboard__actions {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (max-width: 72rem) {
            .admin-dashboard__hero,
            .admin-dashboard__priorities,
            .admin-dashboard__areas {
                grid-template-columns: 1fr;
            }

            .admin-dashboard__actions {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 48rem) {
            .admin-dashboard__metrics,
            .admin-dashboard__actions,
            .admin-dashboard__area-metrics {
                grid-template-columns: 1fr;
            }

            .admin-dashboard__hero {
                padding: 1.1rem;
                border-radius: 1.5rem;
            }
        }
    </style>
@endif

<div class="admin-dashboard">
    @if ($showHero ?? true)
        @if ($parish)
            <section
                class="admin-dashboard__hero"
                style="--dashboard-cover: url('{{ $parish['cover_url'] }}');"
            >
                <div class="admin-dashboard__hero-main">
                    <span class="admin-dashboard__eyebrow">Centrum dowodzenia parafii</span>

                    <h1 class="admin-dashboard__title">
                        Dashboard ma prowadzić proboszcza przez realną pracę, a nie tylko pokazywać liczby.
                    </h1>

                    <p class="admin-dashboard__lead">
                        W jednym miejscu widać dzisiejszy stan parafii, priorytety na kolejne dni, szybkie przejścia do kluczowych sekcji
                        i ogólną spójność przepływu: od liturgii, przez wspólnotę, po komunikację i ustawienia.
                    </p>

                    <div class="admin-dashboard__meta">
                        <span class="admin-dashboard__meta-chip">
                            <x-filament::icon icon="heroicon-o-map-pin" class="admin-dashboard__icon" />
                            {{ $parish['city'] ?: 'Brak miasta' }}
                        </span>

                        @if ($parish['diocese'])
                            <span class="admin-dashboard__meta-chip">
                                <x-filament::icon icon="heroicon-o-building-office-2" class="admin-dashboard__icon" />
                                {{ $parish['diocese'] }}
                            </span>
                        @endif

                        <span class="admin-dashboard__meta-chip">
                            <x-filament::icon icon="heroicon-o-shield-check" class="admin-dashboard__icon" />
                            Administratorzy: {{ $parish['admins_count'] }}
                        </span>

                        <span class="admin-dashboard__meta-chip">
                            <x-filament::icon icon="heroicon-o-user-group" class="admin-dashboard__icon" />
                            Zweryfikowani: {{ number_format($parish['verified_parishioners'], 0, ',', ' ') }}
                        </span>
                    </div>
                </div>

                <aside class="admin-dashboard__hero-side">
                    <div class="admin-dashboard__identity">
                        <img
                            src="{{ $parish['avatar_url'] }}"
                            alt="{{ $parish['short_name'] }}"
                            class="admin-dashboard__avatar"
                        >

                        <div>
                            <p class="admin-dashboard__identity-label">Aktywna parafia</p>
                            <h2 class="admin-dashboard__identity-name">{{ $parish['short_name'] }}</h2>
                            <p class="admin-dashboard__identity-copy">
                                Główna parafia w usłudze, z priorytetami dobranymi do codziennej pracy proboszcza.
                            </p>
                        </div>
                    </div>

                    <div class="admin-dashboard__completion">
                        <div class="admin-dashboard__completion-top">
                            <span class="admin-dashboard__completion-label">Kompletność profilu</span>
                            <span class="admin-dashboard__completion-value">{{ $parish['profile_completion'] }}%</span>
                        </div>

                        <div class="admin-dashboard__progress" aria-hidden="true">
                            <div
                                class="admin-dashboard__progress-bar"
                                style="width: {{ $parish['profile_completion'] }}%;"
                            ></div>
                        </div>

                        <p class="admin-dashboard__completion-copy">
                            Im bardziej dopięty profil parafii, tym spójniej wygląda aplikacja i tym mniej improwizacji w codziennej obsłudze.
                        </p>

                        @if (! empty($parish['profile_missing']))
                            <div class="admin-dashboard__completion-list">
                                @foreach (array_slice($parish['profile_missing'], 0, 4) as $missing)
                                    <span class="admin-dashboard__completion-pill">{{ $missing }}</span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </aside>
            </section>
        @endif
    @endif

    @if ($showBody ?? true)
        <section>
            <div class="admin-dashboard__section-heading">
                <div>
                    <p class="admin-dashboard__grid-title">Puls dnia</p>
                    <h2 class="admin-dashboard__section-title">Najważniejsze sygnały operacyjne</h2>
                    <p class="admin-dashboard__section-copy">
                        Jeden rzut oka ma wystarczyć, by wiedzieć, czy uwaga proboszcza idzie dziś w liturgię, wspólnotę, komunikację czy ustawienia.
                    </p>
                </div>
            </div>

            <div class="admin-dashboard__metrics">
                @foreach ($heroMetrics as $metric)
                    <article class="admin-dashboard__metric" data-tone="{{ $metric['tone'] }}">
                        <div class="admin-dashboard__metric-head">
                            <div>
                                <div class="admin-dashboard__metric-label">{{ $metric['label'] }}</div>
                                <div class="admin-dashboard__metric-value">{{ $metric['value'] }}</div>
                            </div>

                            <span class="admin-dashboard__tone-dot" aria-hidden="true"></span>
                        </div>

                        <p class="admin-dashboard__metric-copy">{{ $metric['description'] }}</p>
                    </article>
                @endforeach
            </div>
        </section>

        <section>
            <div class="admin-dashboard__section-heading">
                <div>
                    <p class="admin-dashboard__grid-title">Priorytety</p>
                    <h2 class="admin-dashboard__section-title">To wymaga najpierw uwagi</h2>
                    <p class="admin-dashboard__section-copy">
                        Karty mają prowadzić do konkretnej pracy. Każda z nich jest podlinkowana do miejsca, w którym temat faktycznie się domyka.
                    </p>
                </div>
            </div>

            <div class="admin-dashboard__priorities">
                @foreach ($priorityCards as $card)
                    <article class="admin-dashboard__priority" data-tone="{{ $card['tone'] }}">
                        <span class="admin-dashboard__priority-bar" aria-hidden="true"></span>

                        <div class="admin-dashboard__priority-eyebrow">
                            <x-filament::icon :icon="$card['icon']" class="admin-dashboard__icon" />
                            {{ $card['eyebrow'] }}
                        </div>

                        <h3 class="admin-dashboard__priority-title">{{ $card['title'] }}</h3>
                        <p class="admin-dashboard__priority-copy">{{ $card['body'] }}</p>
                        <p class="admin-dashboard__priority-meta">{{ $card['meta'] }}</p>

                        <a href="{{ $card['url'] }}" class="admin-dashboard__priority-link">
                            {{ $card['action_label'] }}
                            <x-filament::icon icon="heroicon-o-arrow-up-right" class="admin-dashboard__icon" />
                        </a>
                    </article>
                @endforeach
            </div>
        </section>

        <section>
            <div class="admin-dashboard__section-heading">
                <div>
                    <p class="admin-dashboard__grid-title">Szybkie przejścia</p>
                    <h2 class="admin-dashboard__section-title">Najkrótsza droga do pracy</h2>
                    <p class="admin-dashboard__section-copy">
                        Zamiast szukać po nawigacji, dashboard ma dawać skróty do najczęściej używanych operacji i kolejek roboczych.
                    </p>
                </div>
            </div>

            <div class="admin-dashboard__actions">
                @foreach ($quickActions as $action)
                    <article class="admin-dashboard__action" data-tone="{{ $action['tone'] }}">
                        <a href="{{ $action['url'] }}" class="admin-dashboard__action-link">
                            <div class="admin-dashboard__action-head">
                                <span class="admin-dashboard__action-icon">
                                    <x-filament::icon :icon="$action['icon']" class="admin-dashboard__icon" />
                                </span>

                                <x-filament::icon icon="heroicon-o-arrow-up-right" class="admin-dashboard__icon" />
                            </div>

                            <h3 class="admin-dashboard__action-label">{{ $action['label'] }}</h3>
                            <p class="admin-dashboard__action-copy">{{ $action['description'] }}</p>
                            <div class="admin-dashboard__action-cta">Przejdź</div>
                        </a>
                    </article>
                @endforeach
            </div>
        </section>

        <section>
            <div class="admin-dashboard__section-heading">
                <div>
                    <p class="admin-dashboard__grid-title">Obszary pracy</p>
                    <h2 class="admin-dashboard__section-title">Czy sposób zarządzania parafią jest spójny?</h2>
                    <p class="admin-dashboard__section-copy">
                        To syntetyczny przegląd całej usługi Wspólnota z perspektywy proboszcza: wspólnota, liturgia, komunikacja i tożsamość parafii.
                    </p>
                </div>
            </div>

            <div class="admin-dashboard__areas">
                @foreach ($areaCards as $area)
                    <article class="admin-dashboard__area" data-tone="{{ $area['tone'] }}">
                        <div class="admin-dashboard__area-top">
                            <div>
                                <h3 class="admin-dashboard__area-title">{{ $area['title'] }}</h3>
                                <p class="admin-dashboard__area-status">{{ $area['status'] }}</p>
                            </div>

                            <span class="admin-dashboard__area-pill">{{ $area['tone_label'] }}</span>
                        </div>

                        <p class="admin-dashboard__area-copy">{{ $area['description'] }}</p>

                        <div class="admin-dashboard__area-metrics">
                            @foreach ($area['metrics'] as $metric)
                                <div class="admin-dashboard__area-metric">
                                    <div class="admin-dashboard__area-metric-label">{{ $metric['label'] }}</div>
                                    <div class="admin-dashboard__area-metric-value">{{ $metric['value'] }}</div>
                                </div>
                            @endforeach
                        </div>

                        <a href="{{ $area['url'] }}" class="admin-dashboard__area-link">
                            {{ $area['action_label'] }}
                            <x-filament::icon icon="heroicon-o-arrow-up-right" class="admin-dashboard__icon" />
                        </a>
                    </article>
                @endforeach
            </div>
        </section>

        <section class="admin-dashboard__monitoring">
            <p class="admin-dashboard__grid-title">Monitoring na żywo</p>
            <h2 class="admin-dashboard__section-title">Widgety operacyjne</h2>
            <p class="admin-dashboard__monitoring-copy">
                Niżej zostaje bieżący monitoring liczb, kontroli i najbliższych terminów. Góra strony służy do decyzji, dół do obserwacji.
            </p>
        </section>
    @endif
</div>
