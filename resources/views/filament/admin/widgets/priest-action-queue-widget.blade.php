<x-filament-widgets::widget>
    <style>
        .priest-action-queue {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .priest-action-queue__hero {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: 0.9rem;
            padding: 1.2rem 1.25rem;
            border-radius: 1.8rem;
            border: 1px solid rgba(226, 232, 240, 0.95);
            background:
                linear-gradient(135deg, rgba(255, 247, 237, 0.98) 0%, rgba(255, 255, 255, 0.98) 52%, rgba(239, 246, 255, 0.94) 100%);
            box-shadow: 0 22px 42px rgba(15, 23, 42, 0.06);
        }

        .priest-action-queue__eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.35rem 0.72rem;
            border-radius: 999px;
            background: rgba(255, 251, 235, 0.92);
            color: #92400e;
            font-size: 0.77rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }

        .priest-action-queue__title {
            margin: 0.75rem 0 0;
            font-size: 1.3rem;
            font-weight: 700;
            letter-spacing: -0.04em;
            color: #0f172a;
        }

        .priest-action-queue__copy {
            margin: 0.45rem 0 0;
            color: #475569;
            line-height: 1.62;
            max-width: 46rem;
        }

        .priest-action-queue__legend {
            display: flex;
            flex-wrap: wrap;
            gap: 0.55rem;
            align-content: start;
        }

        .priest-action-queue__legend-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.46rem 0.72rem;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.84);
            border: 1px solid rgba(226, 232, 240, 0.9);
            color: #334155;
            font-size: 0.82rem;
            font-weight: 600;
        }

        .priest-action-queue__grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 1rem;
        }

        .priest-action-queue__card {
            position: relative;
            display: flex;
            flex-direction: column;
            gap: 0.85rem;
            min-height: 100%;
            padding: 1.25rem;
            border-radius: 1.7rem;
            border: 1px solid rgba(226, 232, 240, 0.95);
            box-shadow: 0 18px 34px rgba(15, 23, 42, 0.06);
            overflow: hidden;
        }

        .priest-action-queue__card::after {
            content: "";
            position: absolute;
            inset: auto -10% -30% auto;
            width: 9rem;
            height: 9rem;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.28);
            filter: blur(8px);
            pointer-events: none;
        }

        .priest-action-queue__card[data-tone="danger"] {
            background: linear-gradient(180deg, rgba(254, 242, 242, 0.98) 0%, rgba(255, 255, 255, 0.95) 100%);
        }

        .priest-action-queue__card[data-tone="warning"] {
            background: linear-gradient(180deg, rgba(255, 251, 235, 0.98) 0%, rgba(255, 255, 255, 0.95) 100%);
        }

        .priest-action-queue__card[data-tone="success"] {
            background: linear-gradient(180deg, rgba(236, 253, 245, 0.98) 0%, rgba(255, 255, 255, 0.95) 100%);
        }

        .priest-action-queue__head {
            display: flex;
            justify-content: space-between;
            gap: 0.8rem;
            align-items: start;
        }

        .priest-action-queue__badge {
            display: inline-flex;
            align-items: center;
            padding: 0.38rem 0.72rem;
            border-radius: 999px;
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .priest-action-queue__card[data-tone="danger"] .priest-action-queue__badge {
            background: #fee2e2;
            color: #991b1b;
        }

        .priest-action-queue__card[data-tone="warning"] .priest-action-queue__badge {
            background: #fef3c7;
            color: #92400e;
        }

        .priest-action-queue__card[data-tone="success"] .priest-action-queue__badge {
            background: #d1fae5;
            color: #065f46;
        }

        .priest-action-queue__icon {
            width: 2.8rem;
            height: 2.8rem;
            border-radius: 1rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.75);
            border: 1px solid rgba(255, 255, 255, 0.55);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.5);
        }

        .priest-action-queue__icon svg {
            width: 1.15rem;
            height: 1.15rem;
        }

        .priest-action-queue__card-title {
            margin: 0;
            font-size: 1.1rem;
            line-height: 1.35;
            letter-spacing: -0.03em;
            color: #0f172a;
        }

        .priest-action-queue__card-copy {
            margin: 0;
            color: #475569;
            line-height: 1.62;
            flex: 1;
        }

        .priest-action-queue__meta {
            color: #334155;
            font-size: 0.84rem;
            font-weight: 600;
            line-height: 1.55;
        }

        .priest-action-queue__days {
            display: flex;
            flex-wrap: wrap;
            gap: 0.45rem;
        }

        .priest-action-queue__day {
            display: inline-flex;
            align-items: center;
            padding: 0.38rem 0.6rem;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.88);
            border: 1px solid rgba(226, 232, 240, 0.9);
            color: #334155;
            font-size: 0.78rem;
            font-weight: 600;
        }

        .priest-action-queue__actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.65rem;
            margin-top: auto;
        }

        @media (max-width: 75rem) {
            .priest-action-queue__grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="priest-action-queue">

        <div class="priest-action-queue__grid">
            @foreach ($cards as $index => $card)
                @php
                    $icon = match ($index) {
                        0 => 'heroicon-o-megaphone',
                        1 => 'heroicon-o-calendar',
                        default => 'heroicon-o-clock',
                    };
                    $buttonColor = match ($card['tone']) {
                        'danger' => 'danger',
                        'warning' => 'warning',
                        default => 'success',
                    };
                @endphp

                <article class="priest-action-queue__card" data-tone="{{ $card['tone'] }}">
                    <div class="priest-action-queue__head">
                        <span class="priest-action-queue__badge">{{ $card['state_label'] }}</span>

                        <span class="priest-action-queue__icon" aria-hidden="true">
                            <x-filament::icon :icon="$icon" />
                        </span>
                    </div>

                    <h3 class="priest-action-queue__card-title">{{ $card['title'] }}</h3>
                    <p class="priest-action-queue__card-copy">{{ $card['summary'] }}</p>
                    <div class="priest-action-queue__meta">{{ $card['meta'] }}</div>

                    @if (!empty($card['missing_days']))
                        <div class="priest-action-queue__days">
                            @foreach (array_slice($card['missing_days'], 0, 6) as $day)
                                <span class="priest-action-queue__day">{{ $day['label'] }}, {{ $day['short_date'] }}</span>
                            @endforeach
                        </div>
                    @endif

                    <div class="priest-action-queue__actions">
                        <x-filament::button tag="a" size="sm" :color="$buttonColor" :href="$card['url']">
                            {{ $card['action_label'] }}
                        </x-filament::button>

                        @if (!empty($card['secondary_url']))
                            <x-filament::button tag="a" size="sm" color="gray" :href="$card['secondary_url']">
                                Pelna lista
                            </x-filament::button>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</x-filament-widgets::widget>