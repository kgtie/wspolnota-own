@php
    use App\Models\NewsPost;

    /** @var NewsPost $record */
    $record = $this->getRecord();
    $statusLabel = NewsPost::getStatusOptions()[$record->status] ?? $record->status;
    $statusColorClass = match ($record->status) {
        'published' => 'is-success',
        'scheduled' => 'is-warning',
        'archived' => 'is-muted',
        default => 'is-draft',
    };
    $savedAt = $record->updated_at?->timezone(config('app.timezone'))->format('H:i');
@endphp

<x-filament-panels::page>
    <style>
        .news-post-workbench {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }

        .news-post-workbench__hero {
            display: grid;
            grid-template-columns: minmax(0, 1.8fr) minmax(18rem, 0.9fr);
            gap: 1rem;
            padding: 1.35rem;
            border-radius: 1.75rem;
            border: 1px solid rgba(148, 163, 184, 0.2);
            background:
                radial-gradient(circle at top left, rgba(186, 230, 253, 0.4), transparent 28%),
                linear-gradient(135deg, rgba(248, 250, 252, 0.96) 0%, rgba(255, 255, 255, 0.98) 100%);
            box-shadow: 0 20px 40px rgba(15, 23, 42, 0.06);
        }

        .news-post-workbench__eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            margin-bottom: 0.85rem;
            padding: 0.35rem 0.65rem;
            border-radius: 999px;
            background: rgba(226, 232, 240, 0.75);
            color: #334155;
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .news-post-workbench__title {
            margin: 0;
            font-size: clamp(1.5rem, 2vw, 2.15rem);
            line-height: 1.05;
            letter-spacing: -0.04em;
            color: #0f172a;
        }

        .news-post-workbench__copy {
            margin: 0.75rem 0 0;
            max-width: 52rem;
            color: #475569;
            line-height: 1.65;
        }

        .news-post-workbench__meta {
            display: grid;
            gap: 0.75rem;
            align-content: start;
            padding: 1rem;
            border-radius: 1.35rem;
            background: rgba(255, 255, 255, 0.78);
            border: 1px solid rgba(226, 232, 240, 0.95);
        }

        .news-post-workbench__badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: fit-content;
            padding: 0.35rem 0.7rem;
            border-radius: 999px;
            font-size: 0.78rem;
            font-weight: 700;
        }

        .news-post-workbench__badge.is-draft {
            background: rgba(224, 242, 254, 0.9);
            color: #0c4a6e;
        }

        .news-post-workbench__badge.is-warning {
            background: rgba(254, 249, 195, 0.92);
            color: #854d0e;
        }

        .news-post-workbench__badge.is-success {
            background: rgba(220, 252, 231, 0.94);
            color: #166534;
        }

        .news-post-workbench__badge.is-muted {
            background: rgba(226, 232, 240, 0.95);
            color: #334155;
        }

        .news-post-workbench__meta-grid {
            display: grid;
            gap: 0.85rem;
        }

        .news-post-workbench__meta-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            font-size: 0.92rem;
        }

        .news-post-workbench__meta-label {
            color: #64748b;
        }

        .news-post-workbench__meta-value {
            color: #0f172a;
            font-weight: 600;
            text-align: right;
        }

        .news-post-workbench__autosave[data-state="saving"] .news-post-workbench__meta-value {
            color: #0f766e;
        }

        .news-post-workbench__autosave[data-state="pending"] .news-post-workbench__meta-value {
            color: #9a3412;
        }

        .news-post-workbench__autosave[data-state="manual"] .news-post-workbench__meta-value {
            color: #7c2d12;
        }

        .news-post-workbench .news-post-form-card .fi-section {
            border-radius: 1.6rem;
            border: 1px solid rgba(226, 232, 240, 0.96);
            background:
                linear-gradient(180deg, rgba(255, 255, 255, 0.98) 0%, rgba(248, 250, 252, 0.9) 100%);
            box-shadow: 0 14px 30px rgba(15, 23, 42, 0.05);
        }

        .news-post-workbench .news-post-form-card .fi-section-header {
            padding: 1.15rem 1.35rem 1rem;
        }

        .news-post-workbench .news-post-form-card .fi-section-header-heading {
            font-size: 1.03rem;
            font-weight: 700;
            color: #0f172a;
            letter-spacing: -0.02em;
        }

        .news-post-workbench .news-post-form-card .fi-section-header-description {
            margin-top: 0.25rem;
            color: #64748b;
            line-height: 1.5;
        }

        .news-post-workbench .news-post-form-card .fi-section-content {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            padding: 0 1.35rem 1.35rem;
        }

        .news-post-workbench .news-post-form-card--masthead .fi-input-wrp {
            border-radius: 1rem;
        }

        .news-post-workbench .news-post-form-card--publication,
        .news-post-workbench .news-post-form-card--media {
            align-self: start;
        }

        .news-post-workbench .news-post-form-card--publication {
            position: sticky;
            top: 1rem;
        }

        .news-post-workbench .news-post-form-card--content .fi-section-content {
            gap: 0.65rem;
        }

        .news-post-workbench .fi-section-content-ctn .fi-ac {
            padding-inline: 1.35rem;
            padding-bottom: 1.2rem;
        }

        @media (max-width: 90rem) {
            .news-post-workbench__hero {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 80rem) {
            .news-post-workbench .news-post-form-card--publication {
                position: static;
            }
        }
    </style>

    <script>
        window.newsPostWorkbench = window.newsPostWorkbench || ((config) => ({
            livewire: config.livewire,
            pendingChanges: false,
            autosaveState: config.initialAutosaveState,
            lastSavedAt: config.lastSavedAt,
            autosaveInterval: null,

            init() {
                this.autosaveInterval = window.setInterval(() => {
                    if (! this.pendingChanges) {
                        return;
                    }

                    this.autosaveState = 'saving';
                    this.livewire.saveDraftSilently();
                }, 7000);
            },

            markDirty() {
                this.pendingChanges = true;

                if (this.autosaveState !== 'manual') {
                    this.autosaveState = 'pending';
                }
            },

            markSaved(savedAt) {
                this.pendingChanges = false;
                this.autosaveState = 'saved';
                this.lastSavedAt = savedAt;
            },

            setManualMode() {
                this.pendingChanges = false;
                this.autosaveState = 'manual';
            },
        }));
    </script>

    <div
        x-data="newsPostWorkbench({
            livewire: $wire,
            initialAutosaveState: @js($record->status === 'draft' ? 'saved' : 'manual'),
            lastSavedAt: @js($savedAt),
        })"
        x-init="init()"
        x-on:news-post-autosaved.window="markSaved($event.detail.savedAt)"
        x-on:news-post-autosave-mode.window="setManualMode()"
        x-on:news-post-editor-dirty.window="markDirty()"
        x-on:input.debounce.350ms="markDirty()"
        x-on:change="markDirty()"
        class="news-post-workbench"
    >
        <section class="news-post-workbench__hero">
            <div>
                <span class="news-post-workbench__eyebrow">Panel redakcyjny</span>
                <h1 class="news-post-workbench__title">Pracuj na wpisie jak na gotowym szkicu, nie jak na pustym formularzu.</h1>
                <p class="news-post-workbench__copy">
                    Wpis istnieje w bazie od momentu wejscia do edycji, dlatego media i obrazy osadzane w tresci sa dostepne od razu.
                    Uklad strony celowo eksponuje tresc jako glowny obszar pracy, a publikacje i media trzyma w osobnym panelu bocznym.
                </p>
            </div>

            <aside class="news-post-workbench__meta">
                <span class="news-post-workbench__badge {{ $statusColorClass }}">{{ $statusLabel }}</span>

                <div class="news-post-workbench__meta-grid">
                    <div class="news-post-workbench__meta-row news-post-workbench__autosave" x-bind:data-state="autosaveState">
                        <span class="news-post-workbench__meta-label">Zapis roboczy</span>
                        <span class="news-post-workbench__meta-value" x-text="
                            autosaveState === 'saving'
                                ? 'Trwa zapisywanie...'
                                : autosaveState === 'pending'
                                    ? 'Czekaja zmiany'
                                    : autosaveState === 'manual'
                                        ? 'Tryb reczny'
                                        : (lastSavedAt ? `Zapisano o ${lastSavedAt}` : 'Gotowe')
                        "></span>
                    </div>

                    <div class="news-post-workbench__meta-row">
                        <span class="news-post-workbench__meta-label">Osadzone obrazy</span>
                        <span class="news-post-workbench__meta-value">{{ $record->getMedia('content_images')->count() }}</span>
                    </div>

                    <div class="news-post-workbench__meta-row">
                        <span class="news-post-workbench__meta-label">Utworzono</span>
                        <span class="news-post-workbench__meta-value">{{ $record->created_at?->timezone(config('app.timezone'))->format('d.m.Y H:i') }}</span>
                    </div>
                </div>
            </aside>
        </section>

        {{ $this->content }}
    </div>
</x-filament-panels::page>
