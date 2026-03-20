@php
    $threads = $this->getCommentThreads();
    $metrics = $this->getCommentMetrics();
    $postOptions = $this->getPostFilterOptions();
    $currentSearch = $this->getCurrentSearch();
    $currentVisibility = $this->getCurrentVisibilityFilter();
    $currentPost = $this->getCurrentPostFilter();
@endphp

<x-filament-panels::page>
    <style>
        .comments-workbench {
            display: flex;
            flex-direction: column;
            gap: 1.2rem;
        }

        .comments-workbench__hero {
            display: grid;
            grid-template-columns: minmax(0, 1.6fr) minmax(18rem, 0.95fr);
            gap: 1rem;
            padding: 1.4rem;
            border-radius: 1.8rem;
            border: 1px solid rgba(148, 163, 184, 0.22);
            background:
                radial-gradient(circle at top left, rgba(254, 240, 138, 0.3), transparent 24%),
                linear-gradient(135deg, rgba(255, 251, 235, 0.94) 0%, rgba(255, 255, 255, 0.98) 100%);
            box-shadow: 0 24px 42px rgba(15, 23, 42, 0.06);
        }

        .comments-workbench__eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            margin-bottom: 0.9rem;
            padding: 0.35rem 0.7rem;
            border-radius: 999px;
            background: rgba(120, 53, 15, 0.08);
            color: #92400e;
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .comments-workbench__title {
            margin: 0;
            font-size: clamp(1.7rem, 2vw, 2.45rem);
            line-height: 1.05;
            letter-spacing: -0.05em;
            color: #111827;
        }

        .comments-workbench__copy {
            margin: 0.85rem 0 0;
            max-width: 50rem;
            color: #4b5563;
            line-height: 1.68;
        }

        .comments-workbench__hero-note {
            display: grid;
            gap: 0.8rem;
            align-content: start;
            padding: 1rem;
            border-radius: 1.35rem;
            border: 1px solid rgba(251, 191, 36, 0.22);
            background: rgba(255, 255, 255, 0.78);
        }

        .comments-workbench__hero-note-label {
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: #92400e;
        }

        .comments-workbench__hero-note-copy {
            margin: 0;
            color: #4b5563;
            line-height: 1.6;
        }

        .comments-workbench__metrics {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 0.9rem;
        }

        .comments-workbench__metric {
            padding: 1rem 1.05rem;
            border-radius: 1.4rem;
            border: 1px solid rgba(229, 231, 235, 0.94);
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.98) 0%, rgba(249, 250, 251, 0.9) 100%);
            box-shadow: 0 14px 28px rgba(15, 23, 42, 0.05);
        }

        .comments-workbench__metric[data-tone="warm"] {
            background: linear-gradient(180deg, rgba(255, 247, 237, 0.98) 0%, rgba(255, 255, 255, 0.94) 100%);
        }

        .comments-workbench__metric[data-tone="calm"] {
            background: linear-gradient(180deg, rgba(236, 253, 245, 0.98) 0%, rgba(255, 255, 255, 0.94) 100%);
        }

        .comments-workbench__metric[data-tone="cool"] {
            background: linear-gradient(180deg, rgba(239, 246, 255, 0.98) 0%, rgba(255, 255, 255, 0.94) 100%);
        }

        .comments-workbench__metric-label {
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: #6b7280;
        }

        .comments-workbench__metric-value {
            margin-top: 0.35rem;
            font-size: 1.95rem;
            font-weight: 700;
            letter-spacing: -0.05em;
            color: #111827;
        }

        .comments-workbench__metric-copy {
            margin: 0.45rem 0 0;
            color: #4b5563;
            line-height: 1.55;
        }

        .comments-workbench__filters {
            display: grid;
            grid-template-columns: minmax(0, 1.1fr) minmax(16rem, 0.8fr) minmax(12rem, 0.5fr) auto auto;
            gap: 0.75rem;
            align-items: end;
            padding: 1rem;
            border-radius: 1.5rem;
            border: 1px solid rgba(229, 231, 235, 0.94);
            background: rgba(255, 255, 255, 0.92);
            box-shadow: 0 16px 30px rgba(15, 23, 42, 0.05);
        }

        .comments-workbench__field {
            display: grid;
            gap: 0.4rem;
        }

        .comments-workbench__label {
            font-size: 0.77rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: #6b7280;
        }

        .comments-workbench__input,
        .comments-workbench__select {
            min-height: 2.95rem;
            padding: 0.8rem 0.95rem;
            border-radius: 1rem;
            border: 1px solid rgba(209, 213, 219, 0.95);
            background: #fff;
            color: #111827;
            box-shadow: inset 0 1px 2px rgba(15, 23, 42, 0.04);
        }

        .comments-workbench__input:focus,
        .comments-workbench__select:focus {
            outline: none;
            border-color: rgba(217, 119, 6, 0.8);
            box-shadow: 0 0 0 4px rgba(251, 191, 36, 0.14);
        }

        .comments-workbench__button,
        .comments-workbench__link-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 2.95rem;
            padding: 0.8rem 1rem;
            border-radius: 1rem;
            font-weight: 700;
            transition: transform 140ms ease, box-shadow 140ms ease, background-color 140ms ease;
        }

        .comments-workbench__button {
            border: 0;
            background: #111827;
            color: #fff;
            box-shadow: 0 14px 26px rgba(17, 24, 39, 0.16);
        }

        .comments-workbench__button:hover,
        .comments-workbench__link-button:hover {
            transform: translateY(-1px);
        }

        .comments-workbench__link-button {
            border: 1px solid rgba(209, 213, 219, 0.95);
            background: #fff;
            color: #374151;
            text-decoration: none;
        }

        .comments-workbench__threads {
            display: grid;
            gap: 1rem;
        }

        .comments-workbench__empty {
            padding: 1.8rem;
            border-radius: 1.6rem;
            border: 1px dashed rgba(209, 213, 219, 0.95);
            background: rgba(255, 255, 255, 0.8);
            text-align: center;
            color: #6b7280;
        }

        .comments-workbench__pagination {
            padding-top: 0.25rem;
        }

        @media (max-width: 90rem) {
            .comments-workbench__hero,
            .comments-workbench__metrics,
            .comments-workbench__filters {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="comments-workbench">
        <section class="comments-workbench__hero">
            <div>
                <span class="comments-workbench__eyebrow">Moderacja i rozmowa</span>
                <h1 class="comments-workbench__title">Komentarze w ukladzie watkow, z szybka odpowiedzia i kontekstem wpisu.</h1>
                <p class="comments-workbench__copy">
                    Ten ekran ma dzialac jak redakcyjna skrzynka komentarzy, a nie zwykla tabela.
                    Widzisz od razu zaleznosci rodzic-dziecko, stan moderacji i mozesz odpowiedziec bez wychodzenia z listy.
                </p>
            </div>

            <aside class="comments-workbench__hero-note">
                <span class="comments-workbench__hero-note-label">Tryb pracy</span>
                <p class="comments-workbench__hero-note-copy">
                    Najedz na komentarz, aby pokazac akcje. Odpowiedz rozwija sie inline pod konkretnym wpisem, podobnie jak w panelach redakcyjnych pokroju WordPress.
                </p>
            </aside>
        </section>

        <section class="comments-workbench__metrics">
            @foreach ($metrics as $metric)
                <article class="comments-workbench__metric" data-tone="{{ $metric['tone'] }}">
                    <div class="comments-workbench__metric-label">{{ $metric['label'] }}</div>
                    <div class="comments-workbench__metric-value">{{ $metric['value'] }}</div>
                    <p class="comments-workbench__metric-copy">{{ $metric['description'] }}</p>
                </article>
            @endforeach
        </section>

        <form method="GET" class="comments-workbench__filters">
            <label class="comments-workbench__field">
                <span class="comments-workbench__label">Szukaj</span>
                <input
                    type="text"
                    name="search"
                    value="{{ $currentSearch }}"
                    class="comments-workbench__input"
                    placeholder="Tresc komentarza, autor, tytul wpisu..."
                >
            </label>

            <label class="comments-workbench__field">
                <span class="comments-workbench__label">Wpis</span>
                <select name="post" class="comments-workbench__select">
                    <option value="">Wszystkie wpisy</option>
                    @foreach ($postOptions as $postId => $label)
                        <option value="{{ $postId }}" @selected((string) $currentPost === (string) $postId)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>

            <label class="comments-workbench__field">
                <span class="comments-workbench__label">Widocznosc</span>
                <select name="visibility" class="comments-workbench__select">
                    <option value="all" @selected($currentVisibility === 'all')>Wszystkie</option>
                    <option value="visible" @selected($currentVisibility === 'visible')>Widoczne</option>
                    <option value="hidden" @selected($currentVisibility === 'hidden')>Ukryte</option>
                    @if ($this->canSeeTrashedComments())
                        <option value="trashed" @selected($currentVisibility === 'trashed')>Usuniete</option>
                    @endif
                </select>
            </label>

            <button type="submit" class="comments-workbench__button">Filtruj</button>
            <a href="{{ $this->getResetFiltersUrl() }}" class="comments-workbench__link-button">Reset</a>
        </form>

        <section class="comments-workbench__threads">
            @forelse ($threads as $thread)
                @include('filament.support.news-comments.partials.thread-item', [
                    'comment' => $thread,
                    'depth' => 0,
                ])
            @empty
                <div class="comments-workbench__empty">
                    Brak komentarzy pasujacych do aktualnych filtrow.
                </div>
            @endforelse
        </section>

        @if ($threads->hasPages())
            <div class="comments-workbench__pagination">
                {{ $threads->links() }}
            </div>
        @endif
    </div>
</x-filament-panels::page>
