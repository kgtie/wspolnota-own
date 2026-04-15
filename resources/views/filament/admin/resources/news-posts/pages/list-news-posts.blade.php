@php
    $metrics = $this->getNewsroomMetrics();
    $activeTabLabel = $this->getCachedTabs()[$this->activeTab ?? $this->getDefaultActiveTab()]?->getLabel();
@endphp

<x-filament-panels::page>
    <style>
        .newsroom-index {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }

        .newsroom-index__hero {
            display: grid;
            grid-template-columns: minmax(0, 1.75fr) minmax(19rem, 0.95fr);
            gap: 1rem;
            padding: 1.4rem;
            border-radius: 1.8rem;
            border: 1px solid rgba(148, 163, 184, 0.22);
            background:
                radial-gradient(circle at top left, rgba(191, 219, 254, 0.42), transparent 28%),
                linear-gradient(135deg, rgba(248, 250, 252, 0.96) 0%, rgba(255, 255, 255, 0.98) 100%);
            box-shadow: 0 22px 42px rgba(15, 23, 42, 0.06);
        }

        .newsroom-index__eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            margin-bottom: 0.85rem;
            padding: 0.35rem 0.7rem;
            border-radius: 999px;
            background: rgba(224, 242, 254, 0.95);
            color: #075985;
            font-size: 0.79rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .newsroom-index__title {
            margin: 0;
            max-width: 42rem;
            font-size: clamp(1.65rem, 2vw, 2.4rem);
            line-height: 1.04;
            letter-spacing: -0.05em;
            color: #0f172a;
        }

        .newsroom-index__copy {
            margin: 0.85rem 0 0;
            max-width: 46rem;
            color: #475569;
            line-height: 1.68;
        }

        .newsroom-index__pulse {
            display: grid;
            gap: 0.85rem;
            align-content: start;
            padding: 1.05rem;
            border-radius: 1.35rem;
            background: rgba(255, 255, 255, 0.82);
            border: 1px solid rgba(226, 232, 240, 0.95);
        }

        .newsroom-index__pulse-label {
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: #64748b;
        }

        .newsroom-index__pulse-value {
            font-size: 1.3rem;
            font-weight: 700;
            letter-spacing: -0.03em;
            color: #0f172a;
        }

        .newsroom-index__pulse-copy {
            margin: 0;
            color: #475569;
            line-height: 1.55;
        }

        .newsroom-index__metrics {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 0.9rem;
        }

        .newsroom-index__metric {
            padding: 1.05rem 1.1rem;
            border-radius: 1.4rem;
            border: 1px solid rgba(226, 232, 240, 0.94);
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.98) 0%, rgba(248, 250, 252, 0.88) 100%);
            box-shadow: 0 14px 28px rgba(15, 23, 42, 0.05);
        }

        .newsroom-index__metric[data-tone="warm"] {
            background: linear-gradient(180deg, rgba(255, 251, 235, 0.98) 0%, rgba(255, 255, 255, 0.94) 100%);
        }

        .newsroom-index__metric[data-tone="calm"] {
            background: linear-gradient(180deg, rgba(240, 253, 250, 0.98) 0%, rgba(255, 255, 255, 0.94) 100%);
        }

        .newsroom-index__metric[data-tone="cool"] {
            background: linear-gradient(180deg, rgba(239, 246, 255, 0.98) 0%, rgba(255, 255, 255, 0.94) 100%);
        }

        .newsroom-index__metric-label {
            font-size: 0.8rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: #64748b;
        }

        .newsroom-index__metric-value {
            margin-top: 0.35rem;
            font-size: 2rem;
            font-weight: 700;
            letter-spacing: -0.05em;
            color: #0f172a;
        }

        .newsroom-index__metric-copy {
            margin: 0.45rem 0 0;
            color: #475569;
            line-height: 1.55;
        }

        .newsroom-index__table {
            padding: 1rem;
            border-radius: 1.8rem;
            border: 1px solid rgba(226, 232, 240, 0.95);
            background:
                linear-gradient(180deg, rgba(255, 255, 255, 0.98) 0%, rgba(248, 250, 252, 0.92) 100%);
            box-shadow: 0 18px 34px rgba(15, 23, 42, 0.05);
        }

        .newsroom-index__table .fi-sc-tabs {
            margin-bottom: 1rem;
        }

        .newsroom-index__table .fi-tabs {
            border-radius: 1.15rem;
            background: rgba(248, 250, 252, 0.88);
            padding: 0.35rem;
        }

        .newsroom-index__table .fi-tabs-item {
            border-radius: 0.95rem;
        }

        .newsroom-index__table .fi-ta-ctn {
            border-radius: 1.3rem;
            border: 1px solid rgba(226, 232, 240, 0.94);
            overflow: hidden;
            background: rgba(255, 255, 255, 0.88);
        }

        .newsroom-index__table .fi-ta-header-ctn {
            background:
                linear-gradient(180deg, rgba(248, 250, 252, 0.92) 0%, rgba(255, 255, 255, 0.96) 100%);
        }

        .newsroom-index__table .fi-ta-header {
            padding-top: 1rem;
            padding-bottom: 1rem;
        }

        .newsroom-index__table .fi-ta-table th {
            background: rgba(248, 250, 252, 0.96);
            color: #475569;
            font-size: 0.78rem;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .newsroom-index__table .fi-ta-table td {
            padding-top: 1rem;
            padding-bottom: 1rem;
            vertical-align: top;
        }

        .newsroom-index__table .fi-ta-record {
            transition: background-color 140ms ease;
        }

        .newsroom-index__table .fi-ta-record:hover {
            background: rgba(248, 250, 252, 0.88);
        }

        @media (max-width: 90rem) {
            .newsroom-index__hero,
            .newsroom-index__metrics {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="newsroom-index">
        <section class="newsroom-index__hero">
            <div>
                <span class="newsroom-index__eyebrow">Newsroom parafii</span>
                <h1 class="newsroom-index__title">Zamiast suchej tabeli masz redakcyjne centrum dowodzenia dla aktualności.</h1>
                <p class="newsroom-index__copy">
                    Tu najlepiej widać rytm publikacji: co czeka w szkicach, co już żyje na stronie, a co wymaga domknięcia.
                    Kliknięcie rekordu od razu otwiera edytor, więc ta lista ma służyć orientacji i szybkiemu przechodzeniu do pracy.
                </p>
            </div>

            <aside class="newsroom-index__pulse">
                <span class="newsroom-index__pulse-label">Aktualny fokus</span>
                <div class="newsroom-index__pulse-value">{{ $activeTabLabel ?? 'Wszystkie wpisy' }}</div>
                <p class="newsroom-index__pulse-copy">
                    Korzystaj z zakładek poniżej jak z kolejek redakcyjnych. To najszybszy sposób, by przeskakiwać między szkicami, planowaniem i archiwum.
                </p>
            </aside>
        </section>

        <section class="newsroom-index__metrics">
            @foreach ($metrics as $metric)
                <article class="newsroom-index__metric" data-tone="{{ $metric['tone'] }}">
                    <div class="newsroom-index__metric-label">{{ $metric['label'] }}</div>
                    <div class="newsroom-index__metric-value">{{ $metric['value'] }}</div>
                    <p class="newsroom-index__metric-copy">{{ $metric['description'] }}</p>
                </article>
            @endforeach
        </section>

        <section class="newsroom-index__table">
            {{ $this->content }}
        </section>
    </div>
</x-filament-panels::page>
