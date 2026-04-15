@php
    $fieldWrapperView = $getFieldWrapperView();
    $isDisabled = $isDisabled();
    $livewireKey = $getLivewireKey();
    $statePath = $getStatePath();
    $toolbar = $getToolbar();
    $placeholder = $getPlaceholder();
    $minHeight = $getMinHeight();
    $imageUploadUrl = $getImageUploadUrl();
    $maxUploadSize = $getMaxUploadSize();
@endphp

@once
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.snow.css" />

    <style>
        .wsp-quill-editor {
            border: 1px solid rgba(15, 23, 42, 0.1);
            border-radius: 20px;
            overflow: hidden;
            background:
                radial-gradient(circle at top left, rgba(191, 219, 254, 0.22), transparent 22%),
                linear-gradient(180deg, #fbfdff 0%, #ffffff 26%);
            box-shadow:
                0 18px 40px rgba(15, 23, 42, 0.06),
                inset 0 1px 0 rgba(255, 255, 255, 0.7);
        }

        .wsp-quill-editor:focus-within {
            border-color: rgba(14, 116, 144, 0.35);
            box-shadow:
                0 22px 48px rgba(14, 116, 144, 0.12),
                0 0 0 4px rgba(125, 211, 252, 0.16);
        }

        .wsp-quill-editor .ql-toolbar.ql-snow {
            position: sticky;
            top: 0;
            z-index: 20;
            border: 0;
            border-bottom: 1px solid rgba(15, 23, 42, 0.06);
            background:
                linear-gradient(180deg, rgba(248, 250, 252, 0.98) 0%, rgba(241, 245, 249, 0.96) 100%);
            backdrop-filter: blur(10px);
            padding: 0.85rem 1rem;
            display: flex;
            gap: 0.35rem;
            flex-wrap: wrap;
        }

        .wsp-quill-editor .ql-toolbar.ql-snow .ql-formats {
            display: inline-flex;
            align-items: center;
            gap: 0.2rem;
            margin-right: 0.35rem;
            padding-right: 0.45rem;
            border-right: 1px solid rgba(148, 163, 184, 0.2);
        }

        .wsp-quill-editor .ql-toolbar.ql-snow .ql-formats:last-child {
            margin-right: 0;
            padding-right: 0;
            border-right: 0;
        }

        .wsp-quill-editor .ql-toolbar button,
        .wsp-quill-editor .ql-toolbar .ql-picker-label {
            border-radius: 10px;
            transition: background-color 140ms ease, color 140ms ease, box-shadow 140ms ease;
        }

        .wsp-quill-editor .ql-toolbar button:hover,
        .wsp-quill-editor .ql-toolbar .ql-picker-label:hover,
        .wsp-quill-editor .ql-toolbar button.ql-active,
        .wsp-quill-editor .ql-toolbar .ql-picker-label.ql-active {
            background: rgba(14, 116, 144, 0.08);
            color: #0f172a;
            box-shadow: inset 0 0 0 1px rgba(14, 116, 144, 0.12);
        }

        .wsp-quill-editor .ql-container.ql-snow {
            border: 0;
        }

        .wsp-quill-editor .ql-editor {
            font-size: 1.05rem;
            line-height: 1.8;
            color: #0f172a;
            padding: 2rem 2.1rem 2.4rem;
            background:
                linear-gradient(180deg, rgba(255, 255, 255, 0.98) 0%, rgba(249, 250, 251, 0.5) 100%);
        }

        .wsp-quill-editor .ql-editor > *:first-child {
            margin-top: 0;
        }

        .wsp-quill-editor .ql-editor p {
            margin: 0 0 1.05rem;
        }

        .wsp-quill-editor .ql-editor h1,
        .wsp-quill-editor .ql-editor h2,
        .wsp-quill-editor .ql-editor h3 {
            font-weight: 700;
            letter-spacing: -0.03em;
            line-height: 1.18;
            color: #0f172a;
        }

        .wsp-quill-editor .ql-editor h1 {
            font-size: 2rem;
            margin: 1.65rem 0 0.9rem;
        }

        .wsp-quill-editor .ql-editor h2 {
            font-size: 1.55rem;
            margin: 1.5rem 0 0.8rem;
        }

        .wsp-quill-editor .ql-editor h3 {
            font-size: 1.25rem;
            margin: 1.35rem 0 0.7rem;
        }

        .wsp-quill-editor .ql-editor ul,
        .wsp-quill-editor .ql-editor ol {
            margin: 0 0 1.15rem;
            padding-left: 1.55rem;
        }

        .wsp-quill-editor .ql-editor li {
            margin-bottom: 0.4rem;
        }

        .wsp-quill-editor .ql-editor blockquote {
            margin: 1.5rem 0;
            padding: 1rem 1.2rem;
            border-left: 4px solid #0f766e;
            border-radius: 0 16px 16px 0;
            background: linear-gradient(90deg, rgba(204, 251, 241, 0.7) 0%, rgba(240, 253, 250, 0.92) 100%);
            color: #134e4a;
            font-style: normal;
        }

        .wsp-quill-editor .ql-editor a {
            color: #0f766e;
            text-decoration: underline;
            text-decoration-thickness: 0.08em;
            text-underline-offset: 0.16em;
        }

        .wsp-quill-editor .ql-editor code {
            font-family: "SFMono-Regular", ui-monospace, monospace;
            font-size: 0.9em;
            background: rgba(226, 232, 240, 0.85);
            border-radius: 8px;
            padding: 0.15rem 0.35rem;
        }

        .wsp-quill-editor .ql-editor pre.ql-syntax {
            margin: 1.4rem 0;
            padding: 1rem 1.15rem;
            border-radius: 18px;
            background: #0f172a;
            color: #e2e8f0;
            overflow-x: auto;
        }

        .wsp-quill-editor .ql-editor img {
            display: block;
            border-radius: 18px;
            margin: 1.35rem auto;
            max-width: 100%;
            height: auto;
            box-shadow: 0 14px 28px rgba(15, 23, 42, 0.12);
        }

        .wsp-quill-editor .ql-editor .ql-align-center img {
            margin-left: auto;
            margin-right: auto;
        }

        .wsp-quill-editor .ql-editor .ql-align-right img {
            margin-left: auto;
            margin-right: 0;
        }

        .wsp-quill-status {
            margin-top: 0.6rem;
            font-size: 0.83rem;
            color: #334155;
        }

        .wsp-quill-status[data-type="error"] {
            color: #b91c1c;
        }

        .wsp-quill-status[data-type="success"] {
            color: #166534;
        }
    </style>

    <script>
        window.wspolnotaLoadQuillAssets = window.wspolnotaLoadQuillAssets || (() => {
            let loaderPromise = null;

            return () => {
                if (window.Quill) {
                    return Promise.resolve();
                }

                if (loaderPromise) {
                    return loaderPromise;
                }

                loaderPromise = new Promise((resolve, reject) => {
                    const script = document.createElement('script');
                    script.src = 'https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.min.js';
                    script.defer = true;
                    script.onload = () => resolve();
                    script.onerror = () => reject(new Error('Nie udalo sie zaladowac Quill.'));
                    document.head.appendChild(script);
                });

                return loaderPromise;
            };
        })();

        window.wspolnotaQuillEditorComponent = (config) => ({
            quill: null,
            state: config.state,
            isDisabled: config.isDisabled,
            statusMessage: null,
            statusType: 'info',
            isUploading: false,
            statusTimeout: null,

            async init() {
                await window.wspolnotaLoadQuillAssets();
                this.initializeEditor(config);
            },

            initializeEditor(config) {
                const normalizedToolbar = this.normalizeToolbar(config.toolbar);

                this.quill = new window.Quill(this.$refs.editor, {
                    theme: 'snow',
                    placeholder: config.placeholder ?? '',
                    readOnly: this.isDisabled,
                    modules: {
                        toolbar: normalizedToolbar,
                    },
                });

                this.syncEditorWithState(this.state);

                this.quill.on('text-change', () => {
                    this.updateStateFromEditor();
                });

                const toolbar = this.quill.getModule('toolbar');

                if (toolbar) {
                    toolbar.addHandler('image', () => this.selectAndUploadImage(config));
                }

                this.quill.root.addEventListener('paste', (event) => this.handlePaste(event, config));
                this.quill.root.addEventListener('drop', (event) => this.handleDrop(event, config));
                this.quill.root.addEventListener('dragover', (event) => event.preventDefault());

                this.$watch('state', (value) => {
                    this.syncEditorWithState(value);
                });
            },

            normalizeToolbar(toolbar) {
                if (! Array.isArray(toolbar)) {
                    return toolbar;
                }

                return toolbar.map((group) => {
                    if (! Array.isArray(group)) {
                        return group;
                    }

                    return group.map((tool) => {
                        return ({
                            'list-ordered': { list: 'ordered' },
                            'list-bullet': { list: 'bullet' },
                            'header-1': { header: 1 },
                            'header-2': { header: 2 },
                        })[tool] ?? tool;
                    });
                });
            },

            syncEditorWithState(value) {
                if (! this.quill) {
                    return;
                }

                const normalized = value ?? '';
                const current = this.quill.root.innerHTML;

                if (current === normalized) {
                    return;
                }

                this.quill.root.innerHTML = normalized;
            },

            updateStateFromEditor() {
                if (! this.quill) {
                    return;
                }

                const html = this.quill.root.innerHTML;
                this.state = html === '<p><br></p>' ? null : html;
                this.announceChange();
            },

            async selectAndUploadImage(config) {
                if (this.isDisabled || this.isUploading) {
                    return;
                }

                const input = document.createElement('input');
                input.type = 'file';
                input.accept = 'image/png,image/jpeg,image/webp';
                input.click();

                input.addEventListener('change', async () => {
                    const file = input.files?.[0];

                    if (! file) {
                        return;
                    }

                    await this.uploadAndInsertImage(file, config);
                });
            },

            async handlePaste(event, config) {
                const clipboardItems = event.clipboardData?.items ?? [];
                const imageItem = Array.from(clipboardItems).find((item) => item.type.startsWith('image/'));

                if (! imageItem) {
                    const html = event.clipboardData?.getData('text/html') ?? '';

                    if (html.includes('data:image/')) {
                        event.preventDefault();
                        this.showStatus('Wklejanie obrazow base64 zostalo zablokowane. Uzyj przycisku obrazu w edytorze.', 'error');
                    }

                    return;
                }

                const file = imageItem.getAsFile();

                if (! file) {
                    return;
                }

                event.preventDefault();
                await this.uploadAndInsertImage(file, config);
            },

            async handleDrop(event, config) {
                const files = Array.from(event.dataTransfer?.files ?? []);
                const imageFile = files.find((file) => file.type.startsWith('image/'));

                if (! imageFile) {
                    return;
                }

                event.preventDefault();
                await this.uploadAndInsertImage(imageFile, config);
            },

            async uploadAndInsertImage(file, config) {
                if (! config.imageUploadUrl) {
                    this.showStatus('Najpierw zapisz wpis, na przykład jako szkic, aby osadzać obrazy w treści.', 'error');
                    return;
                }

                const maxUploadBytes = config.maxUploadSizeKb * 1024;

                if (file.size > maxUploadBytes) {
                    this.showStatus(`Plik jest zbyt duży. Maksymalny rozmiar: ${config.maxUploadSizeKb / 1024} MB.`, 'error');
                    return;
                }

                const formData = new FormData();
                formData.append('image', file);

                try {
                    this.isUploading = true;
                    this.showStatus('Trwa wysyłanie obrazu...', 'info');

                    const response = await fetch(config.imageUploadUrl, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': config.csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: formData,
                        credentials: 'same-origin',
                    });

                    if (response.status === 413) {
                        this.showStatus('Obraz jest zbyt duży dla konfiguracji serwera. Zmniejsz plik i spróbuj ponownie.', 'error');
                        return;
                    }

                    const payload = await response.json();

                    if (! response.ok || ! payload?.url) {
                        const message = payload?.message ?? 'Nie udało się przesłać obrazu.';
                        this.showStatus(message, 'error');
                        return;
                    }

                    const selection = this.quill.getSelection(true);
                    const index = selection?.index ?? this.quill.getLength();

                    this.quill.insertEmbed(index, 'image', payload.url, 'user');
                    this.quill.setSelection(index + 1, 0, 'silent');
                    this.updateStateFromEditor();
                    this.showStatus('Obraz został osadzony w treści.', 'success');
                } catch (error) {
                    this.showStatus('Wystąpił błąd podczas wysyłania obrazu.', 'error');
                } finally {
                    this.isUploading = false;
                }
            },

            showStatus(message, type = 'info') {
                this.statusMessage = message;
                this.statusType = type;

                clearTimeout(this.statusTimeout);
                this.statusTimeout = setTimeout(() => {
                    this.statusMessage = null;
                }, 5000);
            },

            announceChange() {
                this.$root.dispatchEvent(new CustomEvent('news-post-editor-dirty', {
                    bubbles: true,
                }));
            },
        });
    </script>
@endonce

<x-dynamic-component :component="$fieldWrapperView" :field="$field">
    <x-filament::input.wrapper :disabled="$isDisabled" :valid="! $errors->has($statePath)">
        <div
            x-data="wspolnotaQuillEditorComponent({
                state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')", isOptimisticallyLive: false) }},
                isDisabled: @js($isDisabled),
                placeholder: @js($placeholder),
                toolbar: @js($toolbar),
                imageUploadUrl: @js($imageUploadUrl),
                maxUploadSizeKb: @js($maxUploadSize),
                csrfToken: @js(csrf_token()),
            })"
            wire:ignore
            wire:key="{{ $livewireKey }}.{{
                substr(md5(serialize([$isDisabled, $toolbar, $imageUploadUrl, $maxUploadSize])), 0, 64)
            }}"
        >
            <input id="{{ $getId() }}" type="hidden" x-model="state" />

            <div class="wsp-quill-editor">
                <div x-ref="editor" class="fi-prose max-w-none" style="min-height: {{ $minHeight }}px;"></div>
            </div>

            <p
                x-show="statusMessage"
                x-text="statusMessage"
                x-bind:data-type="statusType"
                class="wsp-quill-status"
            ></p>
        </div>
    </x-filament::input.wrapper>
</x-dynamic-component>
