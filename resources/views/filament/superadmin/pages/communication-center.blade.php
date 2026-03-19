<x-filament-panels::page>
    <style>
        .comm-page {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .comm-hero {
            border: 1px solid rgba(191, 167, 135, 0.36);
            border-radius: 1.5rem;
            padding: 1.25rem;
            background:
                radial-gradient(circle at top left, rgba(184, 115, 51, 0.15), transparent 35%),
                linear-gradient(135deg, #fffaf5 0%, #f6efe6 100%);
        }

        .dark .comm-hero {
            border-color: rgba(120, 113, 108, 0.55);
            background:
                radial-gradient(circle at top left, rgba(184, 115, 51, 0.18), transparent 35%),
                linear-gradient(135deg, #1f2937 0%, #111827 100%);
        }

        .comm-hero-grid,
        .comm-shell-grid,
        .comm-preview-grid,
        .comm-library-grid,
        .comm-stats {
            display: grid;
            gap: 1rem;
            grid-template-columns: 1fr;
        }

        @media (min-width: 1100px) {
            .comm-hero-grid {
                grid-template-columns: minmax(0, 1.25fr) minmax(320px, 0.75fr);
            }

            .comm-shell-grid {
                grid-template-columns: minmax(0, 1.45fr) minmax(340px, 0.85fr);
            }

            .comm-preview-grid,
            .comm-library-grid {
                grid-template-columns: 1fr 1fr;
            }
        }

        .comm-stats {
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        }

        .comm-stat,
        .comm-panel,
        .comm-list-row,
        .comm-sub-row,
        .comm-campaign-row {
            border: 1px solid rgba(229, 231, 235, 0.95);
            border-radius: 1.1rem;
            background: #ffffff;
        }

        .dark .comm-stat,
        .dark .comm-panel,
        .dark .comm-list-row,
        .dark .comm-sub-row,
        .dark .comm-campaign-row {
            border-color: rgba(55, 65, 81, 0.95);
            background: #111827;
        }

        .comm-stat {
            padding: 0.9rem 1rem;
            backdrop-filter: blur(12px);
        }

        .comm-stat-label,
        .comm-kicker {
            font-size: 0.72rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #78716c;
        }

        .dark .comm-stat-label,
        .dark .comm-kicker {
            color: #cbd5e1;
        }

        .comm-stat-value {
            margin-top: 0.35rem;
            font-size: 1.55rem;
            line-height: 1;
            font-weight: 700;
            color: #1c1917;
        }

        .dark .comm-stat-value {
            color: #f8fafc;
        }

        .comm-title {
            font-family: Georgia, "Times New Roman", serif;
            font-size: clamp(1.8rem, 2.2vw, 2.5rem);
            line-height: 1.05;
            color: #1c1917;
        }

        .dark .comm-title {
            color: #f8fafc;
        }

        .comm-lead {
            max-width: 64ch;
            font-size: 0.96rem;
            line-height: 1.75;
            color: #57534e;
        }

        .dark .comm-lead {
            color: #d6d3d1;
        }

        .comm-action-bar,
        .comm-badges,
        .comm-row-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.55rem;
        }

        .comm-panel {
            padding: 1rem;
        }

        .comm-stack,
        .comm-list,
        .comm-subscribers {
            display: flex;
            flex-direction: column;
            gap: 0.8rem;
        }

        .comm-subscribers {
            max-height: 32rem;
            overflow: auto;
            padding-right: 0.15rem;
        }

        .comm-input-grid {
            display: grid;
            gap: 0.85rem;
            grid-template-columns: 1fr;
        }

        @media (min-width: 800px) {
            .comm-input-grid.two {
                grid-template-columns: 1fr 1fr;
            }

            .comm-input-grid.three {
                grid-template-columns: 1fr 1fr 1fr;
            }
        }

        .comm-list-row,
        .comm-sub-row,
        .comm-campaign-row {
            padding: 0.85rem 0.95rem;
        }

        .comm-row-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .comm-row-title {
            font-size: 0.95rem;
            font-weight: 700;
            color: #111827;
        }

        .dark .comm-row-title {
            color: #f9fafb;
        }

        .comm-row-meta {
            margin-top: 0.3rem;
            font-size: 0.76rem;
            line-height: 1.65;
            color: #6b7280;
        }

        .dark .comm-row-meta {
            color: #94a3b8;
        }

        .comm-toolbar {
            position: sticky;
            top: 0.9rem;
            z-index: 5;
            border: 1px solid rgba(191, 167, 135, 0.34);
            border-radius: 1.2rem;
            padding: 0.9rem;
            background: rgba(255, 250, 245, 0.88);
            backdrop-filter: blur(18px);
        }

        .dark .comm-toolbar {
            border-color: rgba(71, 85, 105, 0.75);
            background: rgba(17, 24, 39, 0.88);
        }

        .comm-preview-frame {
            width: 100%;
            min-height: 860px;
            border: 1px solid rgba(229, 231, 235, 0.95);
            border-radius: 1.15rem;
            background: #f3efe6;
        }

        .dark .comm-preview-frame {
            border-color: rgba(55, 65, 81, 0.95);
            background: #0f172a;
        }

        .comm-preview-text {
            min-height: 860px;
            border: 1px solid rgba(229, 231, 235, 0.95);
            border-radius: 1.15rem;
            background: #ffffff;
            padding: 1rem;
            font-size: 0.82rem;
            line-height: 1.75;
            white-space: pre-wrap;
            color: #334155;
            overflow: auto;
        }

        .dark .comm-preview-text {
            border-color: rgba(55, 65, 81, 0.95);
            background: #111827;
            color: #e2e8f0;
        }

        .comm-helper {
            font-size: 0.78rem;
            line-height: 1.6;
            color: #78716c;
        }

        .dark .comm-helper {
            color: #cbd5e1;
        }
    </style>

    <div class="comm-page">
        <section class="comm-hero">
            <div class="comm-hero-grid">
                <div class="comm-stack">
                    <div class="comm-kicker">Superadmin Communication Center</div>
                    <h1 class="comm-title">Kampanie email i push uporzadkowane wokol jednego buildera.</h1>
                    <p class="comm-lead">
                        Jeden workflow do szkicow, szablonow, segmentacji odbiorcow, testowych wysylek, harmonogramu i preview finalnego maila.
                        Rich text trafia do tego samego shella HTML, ktory obsluguje maile systemowe i parafialne.
                    </p>
                    <div class="comm-action-bar">
                        <x-filament::button wire:click="newCampaign" color="gray" icon="heroicon-m-sparkles">
                            Nowa kampania
                        </x-filament::button>
                        <x-filament::button wire:click="saveDraft" color="gray" icon="heroicon-m-document-text">
                            Zapisz szkic
                        </x-filament::button>
                        <x-filament::button wire:click="saveTemplate" color="info" icon="heroicon-m-bookmark-square">
                            Zapisz jako szablon
                        </x-filament::button>
                        <x-filament::button wire:click="sendTestCampaign" color="warning" icon="heroicon-m-beaker">
                            Wyslij test
                        </x-filament::button>
                        <x-filament::button wire:click="sendCampaign" color="primary" icon="heroicon-m-paper-airplane">
                            Uruchom email
                        </x-filament::button>
                        <x-filament::button wire:click="sendPushCampaign" color="success" icon="heroicon-m-device-phone-mobile">
                            Uruchom push
                        </x-filament::button>
                    </div>
                </div>

                <div class="comm-stats">
                    @foreach ($this->stats as $stat)
                        <div class="comm-stat">
                            <div class="comm-stat-label">{{ $stat['label'] }}</div>
                            <div class="comm-stat-value">{{ number_format($stat['value'], 0, ',', ' ') }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <div class="comm-shell-grid">
            <div class="comm-stack">
                <x-filament::section heading="Builder Kampanii" description="To tutaj ustawiasz branding, tresc, CTA i fallback tekstowy.">
                    <div class="comm-stack">
                        <div class="comm-panel">
                            <div class="comm-input-grid two">
                                <div>
                                    <label class="text-sm font-medium">Nazwa kampanii</label>
                                    <x-filament::input.wrapper>
                                        <x-filament::input wire:model.defer="campaignName" type="text" placeholder="np. Wielkanocna kampania informacyjna" />
                                    </x-filament::input.wrapper>
                                </div>
                                <div>
                                    <label class="text-sm font-medium">Branding parafialny</label>
                                    <x-filament::input.wrapper>
                                        <select wire:model.live="brandingParishId" class="fi-input block w-full rounded-lg border-gray-300 text-sm">
                                            <option value="">-- branding globalny Wspolnoty --</option>
                                            @foreach ($this->parishOptions as $id => $label)
                                                <option value="{{ $id }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </x-filament::input.wrapper>
                                </div>
                            </div>

                            <div class="comm-input-grid two mt-4">
                                <div>
                                    <label class="text-sm font-medium">Temat email</label>
                                    <x-filament::input.wrapper>
                                        <x-filament::input wire:model.defer="subjectLine" type="text" placeholder="Temat kampanii" />
                                    </x-filament::input.wrapper>
                                </div>
                                <div>
                                    <label class="text-sm font-medium">Preheader</label>
                                    <x-filament::input.wrapper>
                                        <x-filament::input wire:model.defer="preheader" type="text" placeholder="Tekst widoczny obok tematu w skrzynce" />
                                    </x-filament::input.wrapper>
                                </div>
                            </div>

                            <div class="comm-input-grid three mt-4">
                                <div>
                                    <label class="text-sm font-medium">Reply-to email</label>
                                    <x-filament::input.wrapper>
                                        <x-filament::input wire:model.defer="replyToEmail" type="email" placeholder="odpowiedzi@wspolnota.app" />
                                    </x-filament::input.wrapper>
                                </div>
                                <div>
                                    <label class="text-sm font-medium">Reply-to name</label>
                                    <x-filament::input.wrapper>
                                        <x-filament::input wire:model.defer="replyToName" type="text" placeholder="Imie i nazwisko / zespol" />
                                    </x-filament::input.wrapper>
                                </div>
                                <div>
                                    <label class="text-sm font-medium">Planowana wysylka</label>
                                    <x-filament::input.wrapper>
                                        <x-filament::input wire:model.defer="scheduledFor" type="datetime-local" />
                                    </x-filament::input.wrapper>
                                </div>
                            </div>
                        </div>

                        <div class="comm-panel">
                            <div class="comm-input-grid three">
                                <div>
                                    <label class="text-sm font-medium">Hero image URL</label>
                                    <x-filament::input.wrapper>
                                        <x-filament::input wire:model.defer="heroImageUrl" type="url" placeholder="https://..." />
                                    </x-filament::input.wrapper>
                                </div>
                                <div>
                                    <label class="text-sm font-medium">CTA label</label>
                                    <x-filament::input.wrapper>
                                        <x-filament::input wire:model.defer="ctaLabel" type="text" placeholder="np. Otworz Wspolnote" />
                                    </x-filament::input.wrapper>
                                </div>
                                <div>
                                    <label class="text-sm font-medium">CTA URL</label>
                                    <x-filament::input.wrapper>
                                        <x-filament::input wire:model.defer="ctaUrl" type="url" placeholder="https://..." />
                                    </x-filament::input.wrapper>
                                </div>
                            </div>

                            <div class="mt-4">
                                <label class="text-sm font-medium">Tekst fallback / skrot kampanii</label>
                                <x-filament::input.wrapper>
                                    <textarea wire:model.defer="messageBody" rows="4" class="fi-input w-full rounded-lg" placeholder="Obowiazkowy skrot tresci. Trafia do plain-text, push i fallbacku."></textarea>
                                </x-filament::input.wrapper>
                                <p class="comm-helper mt-2">
                                    Ten tekst nadal jest potrzebny, ale finalny mail HTML powinien opierac sie glownie o rich content ponizej.
                                </p>
                            </div>
                        </div>

                        <div class="comm-panel">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <div class="comm-kicker">Rich Content</div>
                                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">Kompozycja finalnego maila</h3>
                                </div>
                                <p class="comm-helper">{{ $this->imageEditorHint }}</p>
                            </div>

                            <div class="mt-4">
                                @include('filament.components.quill-livewire', [
                                    'statePath' => 'campaignContentHtml',
                                    'stateValue' => $campaignContentHtml ?? '',
                                    'placeholder' => 'Buduj kampanie z naglowkami, obrazami, cytatami, listami i linkami...',
                                    'minHeight' => 620,
                                    'imageUploadUrl' => $this->campaignInlineImageUploadUrl,
                                    'maxUploadSize' => 8192,
                                ])
                            </div>
                        </div>
                    </div>
                </x-filament::section>
            </div>

            <div class="comm-stack">
                <div class="comm-toolbar">
                    <div class="comm-kicker">Stan roboczy</div>
                    <div class="comm-input-grid three mt-3">
                        <div>
                            <div class="comm-helper">ID kampanii</div>
                            <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ $loadedCampaignId ?: 'nowa wersja robocza' }}</div>
                        </div>
                        <div>
                            <div class="comm-helper">Podglad odbiorcow</div>
                            <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ number_format($this->recipientPreview['count'], 0, ',', ' ') }}</div>
                        </div>
                        <div>
                            <div class="comm-helper">Adres testowy</div>
                            <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ $testRecipientEmail !== '' ? $testRecipientEmail : 'brak' }}</div>
                        </div>
                    </div>

                    <div class="comm-action-bar mt-4">
                        <x-filament::button wire:click="saveDraft" color="gray" size="sm" icon="heroicon-m-document-text">
                            Szkic
                        </x-filament::button>
                        <x-filament::button wire:click="sendTestCampaign" color="warning" size="sm" icon="heroicon-m-beaker">
                            Test
                        </x-filament::button>
                        <x-filament::button wire:click="sendCampaign" color="primary" size="sm" icon="heroicon-m-paper-airplane">
                            Email
                        </x-filament::button>
                    </div>
                </div>

                <x-filament::section heading="Segmentacja i Dostawa" description="Wybierasz grupy odbiorcow i kontrolujesz zachowanie kampanii.">
                    <div class="comm-stack">
                        <div class="comm-panel">
                            <div class="comm-input-grid two">
                                <div>
                                    <label class="text-sm font-medium">Zakres odbiorcow</label>
                                    <x-filament::input.wrapper>
                                        <select wire:model.live="recipientScope" class="fi-input block w-full rounded-lg border-gray-300 text-sm">
                                            @foreach ($this->recipientScopeOptions as $scopeKey => $scopeLabel)
                                                <option value="{{ $scopeKey }}">{{ $scopeLabel }}</option>
                                            @endforeach
                                        </select>
                                    </x-filament::input.wrapper>
                                </div>
                                <div>
                                    <label class="text-sm font-medium">Temat preferencji email</label>
                                    <x-filament::input.wrapper>
                                        <select wire:model.live="emailPreferenceTopic" class="fi-input block w-full rounded-lg border-gray-300 text-sm">
                                            @foreach ($this->emailTopicOptions as $topicKey => $topicLabel)
                                                <option value="{{ $topicKey }}">{{ $topicLabel }}</option>
                                            @endforeach
                                        </select>
                                    </x-filament::input.wrapper>
                                </div>
                            </div>

                            @if ($recipientScope === 'single_users')
                                <div class="mt-4">
                                    <label class="text-sm font-medium">Wybierz odbiorcow</label>
                                    <x-filament::input.wrapper>
                                        <select wire:model="selectedUserIds" multiple size="8" class="fi-input block w-full rounded-lg border-gray-300 text-sm">
                                            @foreach ($this->userOptions as $id => $label)
                                                <option value="{{ $id }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </x-filament::input.wrapper>
                                </div>
                            @endif

                            @if ($recipientScope === 'users_by_parish')
                                <div class="mt-4">
                                    <label class="text-sm font-medium">Wybierz parafie</label>
                                    <x-filament::input.wrapper>
                                        <select wire:model.live="targetParishId" class="fi-input block w-full rounded-lg border-gray-300 text-sm">
                                            <option value="">-- wybierz parafie --</option>
                                            @foreach ($this->parishOptions as $id => $label)
                                                <option value="{{ $id }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </x-filament::input.wrapper>
                                </div>
                            @endif

                            @if ($recipientScope === 'mailing_list')
                                <div class="comm-input-grid two mt-4">
                                    <div>
                                        <label class="text-sm font-medium">Wybierz liste mailingowa</label>
                                        <x-filament::input.wrapper>
                                            <select wire:model.live="targetMailingListId" class="fi-input block w-full rounded-lg border-gray-300 text-sm">
                                                <option value="">-- wybierz liste --</option>
                                                @foreach ($this->mailingListOptions as $id => $label)
                                                    <option value="{{ $id }}">{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </x-filament::input.wrapper>
                                    </div>
                                    <label class="flex items-center gap-2 text-sm">
                                        <input type="checkbox" wire:model="mailingOnlyConfirmed" class="rounded border-gray-300">
                                        tylko potwierdzeni subskrybenci
                                    </label>
                                </div>
                            @endif

                            @if ($recipientScope === 'custom_emails')
                                <div class="mt-4">
                                    <label class="text-sm font-medium">Wlasne emaile</label>
                                    <x-filament::input.wrapper>
                                        <textarea wire:model.defer="customEmails" rows="6" class="fi-input w-full rounded-lg" placeholder="a@example.com&#10;b@example.com"></textarea>
                                    </x-filament::input.wrapper>
                                </div>
                            @endif
                        </div>

                        <div class="comm-panel">
                            <div class="comm-kicker">Filtry i ograniczenia</div>
                            <div class="comm-input-grid two mt-4">
                                <label class="flex items-center gap-2 text-sm">
                                    <input type="checkbox" wire:model="includeInactiveUsers" class="rounded border-gray-300">
                                    uwzglednij konta nieaktywne
                                </label>
                                <label class="flex items-center gap-2 text-sm">
                                    <input type="checkbox" wire:model="onlyVerifiedUsers" class="rounded border-gray-300">
                                    tylko konta zweryfikowane przez parafie
                                </label>
                                <label class="flex items-center gap-2 text-sm">
                                    <input type="checkbox" wire:model="onlyEmailVerifiedUsers" class="rounded border-gray-300">
                                    tylko konta z potwierdzonym email
                                </label>
                                <label class="flex items-center gap-2 text-sm">
                                    <input type="checkbox" wire:model="onlyUsersWithPushDevices" class="rounded border-gray-300">
                                    tylko uzytkownicy z push
                                </label>
                                <label class="flex items-center gap-2 text-sm">
                                    <input type="checkbox" wire:model="respectEmailPreferences" class="rounded border-gray-300">
                                    respektuj preferencje email
                                </label>
                                <label class="flex items-center gap-2 text-sm">
                                    <input type="checkbox" wire:model="sendCopyToMe" class="rounded border-gray-300">
                                    wyslij kopie do mnie po dispatchu
                                </label>
                            </div>

                            <div class="mt-5">
                                <div class="comm-kicker">Przykladowi odbiorcy</div>
                                <div class="comm-badges mt-3">
                                    @forelse ($this->recipientPreview['sample'] as $recipient)
                                        <x-filament::badge color="gray">{{ $recipient['email'] }}</x-filament::badge>
                                    @empty
                                        <span class="text-xs text-gray-500">Brak odbiorcow dla obecnych filtrow.</span>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        <div class="comm-panel">
                            <div class="comm-input-grid two">
                                <div>
                                    <label class="text-sm font-medium">Adres testowy</label>
                                    <x-filament::input.wrapper>
                                        <x-filament::input wire:model.defer="testRecipientEmail" type="email" placeholder="test@example.com" />
                                    </x-filament::input.wrapper>
                                </div>
                                <div class="flex items-end">
                                    <x-filament::button wire:click="sendTestCampaign" color="warning" icon="heroicon-m-beaker" class="w-full justify-center">
                                        Wyslij test
                                    </x-filament::button>
                                </div>
                            </div>
                        </div>
                    </div>
                </x-filament::section>
            </div>
        </div>

        <x-filament::section heading="Preview Kampanii" description="HTML i plain-text z tego samego silnika, bez osobnego skladania podgladu.">
            <div class="comm-preview-grid">
                <div class="comm-panel">
                    <div class="comm-kicker">HTML preview</div>
                    <iframe class="comm-preview-frame mt-4" srcdoc="{{ e($this->campaignPreviewHtml) }}"></iframe>
                </div>

                <div class="comm-panel">
                    <div class="comm-kicker">Plain-text fallback</div>
                    <div class="comm-preview-text mt-4">{{ $this->campaignPreviewText }}</div>
                </div>
            </div>
        </x-filament::section>

        <div class="comm-library-grid">
            <x-filament::section heading="Listy i subskrybenci" description="Wydzielone miejsce na utrzymanie list mailingowych bez mieszania ich z builderem kampanii.">
                <div class="comm-stack">
                    <div class="comm-panel">
                        <div class="comm-input-grid two">
                            <x-filament::input.wrapper>
                                <x-filament::input wire:model.defer="newListName" type="text" placeholder="Nowa lista, np. Newsletter parafialny" />
                            </x-filament::input.wrapper>
                            <x-filament::button wire:click="createList" icon="heroicon-m-plus" color="primary">
                                Dodaj liste
                            </x-filament::button>
                        </div>
                    </div>

                    <div class="comm-list">
                        @forelse ($this->mailingLists as $list)
                            <div class="comm-list-row">
                                <div class="comm-row-top">
                                    <div>
                                        <div class="comm-row-title">{{ $list->name }}</div>
                                        <div class="comm-row-meta">
                                            aktywni: {{ number_format($list->subscribers_active_count, 0, ',', ' ') }} ·
                                            potwierdzeni: {{ number_format($list->subscribers_confirmed_count, 0, ',', ' ') }} ·
                                            lacznie: {{ number_format($list->subscribers_total_count, 0, ',', ' ') }}
                                        </div>
                                    </div>
                                    <div class="comm-row-actions">
                                        <x-filament::button size="xs" color="{{ $selectedListId === $list->id ? 'success' : 'gray' }}" wire:click="selectList({{ $list->id }})">
                                            {{ $selectedListId === $list->id ? 'Wybrana' : 'Wybierz' }}
                                        </x-filament::button>
                                        <x-filament::button size="xs" color="danger" wire:click="deleteList({{ $list->id }})">
                                            Usun
                                        </x-filament::button>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500">Brak list mailingowych. Dodaj pierwsza liste, aby zaczac.</p>
                        @endforelse
                    </div>

                    @if ($selectedListId)
                        <div class="comm-panel">
                            <div class="comm-input-grid two">
                                <x-filament::input.wrapper>
                                    <x-filament::input wire:model.defer="renameListName" type="text" placeholder="Nazwa wybranej listy" />
                                </x-filament::input.wrapper>
                                <x-filament::button wire:click="saveSelectedListName" icon="heroicon-m-pencil-square" color="gray">
                                    Zapisz nazwe listy
                                </x-filament::button>
                            </div>

                            <div class="comm-input-grid two mt-4">
                                <x-filament::input.wrapper>
                                    <x-filament::input wire:model.defer="newSubscriberEmail" type="email" placeholder="email@adres.pl" />
                                </x-filament::input.wrapper>
                                <label class="flex items-center gap-2 text-sm">
                                    <input type="checkbox" wire:model="newSubscriberConfirmed" class="rounded border-gray-300">
                                    Dodaj jako potwierdzonego
                                </label>
                            </div>

                            <x-filament::button wire:click="addSubscriber" icon="heroicon-m-user-plus" color="success" class="mt-4">
                                Dodaj subskrybenta do wybranej listy
                            </x-filament::button>

                            <div class="mt-4">
                                <x-filament::input.wrapper>
                                    <x-filament::input wire:model.live.debounce.250ms="subscriberSearch" type="text" placeholder="Szukaj subskrybenta po email" />
                                </x-filament::input.wrapper>
                            </div>
                        </div>

                        <div class="comm-subscribers">
                            @forelse ($this->subscribers as $subscriber)
                                <div class="comm-sub-row">
                                    <div class="comm-row-top">
                                        <div>
                                            <div class="comm-row-title">{{ $subscriber->email }}</div>
                                            <div class="comm-row-meta">
                                                lista: {{ $subscriber->mailingList?->name ?? 'brak' }} ·
                                                dodano: {{ $subscriber->created_at?->format('d.m.Y H:i') }}
                                            </div>
                                        </div>
                                        <x-filament::badge :color="$subscriber->deleted_at ? 'danger' : ($subscriber->confirmed_at ? 'success' : 'warning')">
                                            @if ($subscriber->deleted_at)
                                                zarchiwizowany
                                            @elseif ($subscriber->confirmed_at)
                                                potwierdzony
                                            @else
                                                oczekuje potwierdzenia
                                            @endif
                                        </x-filament::badge>
                                    </div>
                                    <div class="comm-row-actions mt-3">
                                        <x-filament::button size="xs" color="gray" wire:click="toggleSubscriberConfirmation({{ $subscriber->id }})">
                                            Przelacz potwierdzenie
                                        </x-filament::button>

                                        @if ($subscriber->deleted_at)
                                            <x-filament::button size="xs" color="success" wire:click="restoreSubscriber({{ $subscriber->id }})">
                                                Przywroc
                                            </x-filament::button>
                                            <x-filament::button size="xs" color="danger" wire:click="deleteSubscriberPermanently({{ $subscriber->id }})">
                                                Usun trwale
                                            </x-filament::button>
                                        @else
                                            <x-filament::button size="xs" color="warning" wire:click="removeSubscriber({{ $subscriber->id }})">
                                                Archiwizuj
                                            </x-filament::button>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <p class="text-sm text-gray-500">Brak subskrybentow dla wybranego filtra.</p>
                            @endforelse
                        </div>
                    @endif
                </div>
            </x-filament::section>

            <div class="comm-stack">
                <x-filament::section heading="Ostatnie kampanie" description="Szkice, zaplanowane i zakolejkowane kampanie email.">
                    <div class="comm-list">
                        @forelse ($this->recentCampaigns as $campaign)
                            <div class="comm-campaign-row">
                                <div class="comm-row-top">
                                    <div>
                                        <div class="comm-row-title">{{ $campaign->name }}</div>
                                        <div class="comm-row-meta">
                                            temat: {{ $campaign->subject_line ?: 'brak' }} ·
                                            aktualizacja: {{ $campaign->updated_at?->format('d.m.Y H:i') }}
                                        </div>
                                    </div>
                                    <x-filament::badge :color="$this->campaignStatusColor($campaign->status)">
                                        {{ $this->campaignStatusLabel($campaign->status) }}
                                    </x-filament::badge>
                                </div>
                                <div class="comm-row-meta mt-2">
                                    odbiorcy: {{ number_format($campaign->recipients_total ?? 0, 0, ',', ' ') }} ·
                                    queued: {{ number_format($campaign->queued_count ?? 0, 0, ',', ' ') }} ·
                                    failed: {{ number_format($campaign->failed_count ?? 0, 0, ',', ' ') }}
                                    @if ($campaign->scheduled_for)
                                        · start: {{ $campaign->scheduled_for->format('d.m.Y H:i') }}
                                    @endif
                                </div>
                                <div class="comm-row-actions mt-3">
                                    <x-filament::button size="xs" color="gray" wire:click="loadCampaign({{ $campaign->id }})">
                                        Wczytaj
                                    </x-filament::button>
                                    <x-filament::button size="xs" color="info" wire:click="duplicateCampaign({{ $campaign->id }})">
                                        Duplikuj
                                    </x-filament::button>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500">Brak zapisanych kampanii email.</p>
                        @endforelse
                    </div>
                </x-filament::section>

                <x-filament::section heading="Szablony kampanii" description="Gotowe punkty startowe do szybkiej duplikacji i dalszej edycji.">
                    <div class="comm-list">
                        @forelse ($this->templateCampaigns as $campaign)
                            <div class="comm-campaign-row">
                                <div class="comm-row-top">
                                    <div>
                                        <div class="comm-row-title">{{ $campaign->name }}</div>
                                        <div class="comm-row-meta">
                                            temat: {{ $campaign->subject_line ?: 'brak' }} ·
                                            zapisano: {{ $campaign->updated_at?->format('d.m.Y H:i') }}
                                        </div>
                                    </div>
                                    <x-filament::badge :color="$this->campaignStatusColor($campaign->status)">
                                        {{ $this->campaignStatusLabel($campaign->status) }}
                                    </x-filament::badge>
                                </div>
                                <div class="comm-row-actions mt-3">
                                    <x-filament::button size="xs" color="gray" wire:click="loadCampaign({{ $campaign->id }})">
                                        Wczytaj
                                    </x-filament::button>
                                    <x-filament::button size="xs" color="info" wire:click="duplicateCampaign({{ $campaign->id }})">
                                        Uzyj jako baza
                                    </x-filament::button>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500">Brak zapisanych szablonow kampanii.</p>
                        @endforelse
                    </div>
                </x-filament::section>
            </div>
        </div>
    </div>
</x-filament-panels::page>
