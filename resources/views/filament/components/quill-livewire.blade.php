@php
    $statePath = $statePath ?? 'campaignContentHtml';
    $stateValue = $stateValue ?? '';
    $placeholder = $placeholder ?? '';
    $minHeight = $minHeight ?? 420;
    $imageUploadUrl = $imageUploadUrl ?? null;
    $maxUploadSize = $maxUploadSize ?? 8192;
    $toolbar = $toolbar ?? [
        ['header-1', 'header-2'],
        ['bold', 'italic', 'underline', 'strike'],
        ['blockquote'],
        ['color', 'background'],
        ['link', 'image'],
        ['list-ordered', 'list-bullet'],
        ['clean'],
    ];
@endphp

@once
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.snow.css" />

    <style>
        .wsp-quill-editor {
            border: 1px solid rgba(15, 23, 42, 0.12);
            border-radius: 14px;
            overflow: hidden;
            background: linear-gradient(180deg, #fcfdff 0%, #ffffff 24%);
            box-shadow: 0 10px 22px rgba(15, 23, 42, 0.04);
        }

        .wsp-quill-editor .ql-toolbar.ql-snow {
            position: sticky;
            top: 0;
            z-index: 20;
            border: 0;
            border-bottom: 1px solid rgba(15, 23, 42, 0.08);
            background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
            padding: 10px 12px;
        }

        .wsp-quill-editor .ql-container.ql-snow {
            border: 0;
        }

        .wsp-quill-editor .ql-editor {
            font-size: 1rem;
            line-height: 1.7;
            color: #0f172a;
            padding: 1.35rem 1.25rem 1.5rem;
        }

        .wsp-quill-editor .ql-editor h1,
        .wsp-quill-editor .ql-editor h2,
        .wsp-quill-editor .ql-editor h3 {
            line-height: 1.3;
            margin-top: 1rem;
            margin-bottom: .5rem;
        }

        .wsp-quill-editor .ql-editor img {
            border-radius: 10px;
            margin: 1rem 0;
            max-width: 100%;
            height: auto;
        }

        .wsp-quill-status {
            margin-top: .45rem;
            font-size: .82rem;
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

        window.wspolnotaQuillEditorComponent = window.wspolnotaQuillEditorComponent || ((config) => ({
            quill: null,
            state: config.initialState ?? '',
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
                this.$wire.$set(config.statePath, this.state, false);
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
                    this.showStatus('Najpierw zapisz kampanię jako szkic, aby osadzać obrazy w treści.', 'error');
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
                        this.showStatus(payload?.message ?? 'Nie udało się przesłać obrazu.', 'error');
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
        }));
    </script>
@endonce

<div
    x-data="wspolnotaQuillEditorComponent({
        initialState: @js($stateValue),
        statePath: @js($statePath),
        isDisabled: false,
        placeholder: @js($placeholder),
        toolbar: @js($toolbar),
        imageUploadUrl: @js($imageUploadUrl),
        maxUploadSizeKb: @js($maxUploadSize),
        csrfToken: @js(csrf_token()),
    })"
    wire:key="quill-livewire-{{ md5($statePath.($imageUploadUrl ?? '').$minHeight.(string) $stateValue) }}"
>
    <div wire:ignore>
        <div class="wsp-quill-editor">
            <div x-ref="editor" class="fi-prose max-w-none" style="min-height: {{ $minHeight }}px;"></div>
        </div>
    </div>

    <p
        x-show="statusMessage"
        x-text="statusMessage"
        x-bind:data-type="statusType"
        class="wsp-quill-status"
    ></p>
</div>
