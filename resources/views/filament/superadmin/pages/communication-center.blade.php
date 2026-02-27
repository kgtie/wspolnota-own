<x-filament-panels::page>
    <style>
        .comm-center-shell {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .comm-stats {
            display: grid;
            gap: 0.7rem;
            grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
        }

        .comm-stat {
            border: 1px solid #e5e7eb;
            border-radius: 0.9rem;
            padding: 0.7rem 0.8rem;
            background: #fff;
        }

        .dark .comm-stat {
            border-color: #374151;
            background: #111827;
        }

        .comm-stat-k {
            font-size: 0.7rem;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: #6b7280;
        }

        .dark .comm-stat-k {
            color: #9ca3af;
        }

        .comm-stat-v {
            margin-top: 0.25rem;
            font-size: 1.35rem;
            line-height: 1;
            font-weight: 700;
            color: #111827;
        }

        .dark .comm-stat-v {
            color: #f9fafb;
        }

        .comm-layout {
            display: grid;
            gap: 1rem;
            grid-template-columns: 1fr;
        }

        @media (min-width: 1200px) {
            .comm-layout {
                grid-template-columns: minmax(0, 1.05fr) minmax(0, 1.2fr);
            }
        }

        .comm-list-grid {
            display: grid;
            gap: 0.6rem;
        }

        .comm-list-row {
            border: 1px solid #e5e7eb;
            border-radius: 0.75rem;
            padding: 0.55rem 0.65rem;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.7rem;
        }

        .dark .comm-list-row {
            border-color: #374151;
            background: #111827;
        }

        .comm-list-meta {
            font-size: 0.72rem;
            color: #6b7280;
        }

        .dark .comm-list-meta {
            color: #9ca3af;
        }

        .comm-subscribers {
            max-height: 34rem;
            overflow: auto;
            display: flex;
            flex-direction: column;
            gap: 0.45rem;
            padding-right: 0.15rem;
        }

        .comm-sub-row {
            border: 1px solid #e5e7eb;
            border-radius: 0.75rem;
            padding: 0.55rem 0.65rem;
            background: #fff;
        }

        .dark .comm-sub-row {
            border-color: #374151;
            background: #111827;
        }

        .comm-sub-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .comm-sub-email {
            font-size: 0.82rem;
            font-weight: 600;
            color: #111827;
            word-break: break-word;
        }

        .dark .comm-sub-email {
            color: #f9fafb;
        }

        .comm-sub-meta {
            margin-top: 0.2rem;
            font-size: 0.69rem;
            color: #6b7280;
        }

        .dark .comm-sub-meta {
            color: #9ca3af;
        }

        .comm-sub-actions {
            margin-top: 0.45rem;
            display: flex;
            flex-wrap: wrap;
            gap: 0.35rem;
        }

        .comm-input-grid {
            display: grid;
            gap: 0.65rem;
            grid-template-columns: 1fr;
        }

        @media (min-width: 700px) {
            .comm-input-grid.two {
                grid-template-columns: 1fr 1fr;
            }
        }

        .comm-preview {
            border: 1px dashed #d1d5db;
            border-radius: 0.75rem;
            padding: 0.65rem;
            background: #fafafa;
        }

        .dark .comm-preview {
            border-color: #4b5563;
            background: #111827;
        }

        .comm-preview-list {
            margin-top: 0.45rem;
            display: flex;
            flex-wrap: wrap;
            gap: 0.3rem;
        }
    </style>

    <div class="comm-center-shell">
        <x-filament::section heading="Globalne centrum komunikacji" description="Listy mailingowe, subskrybenci i kampanie email w jednym module.">
            <div class="comm-stats">
                @foreach ($this->stats as $stat)
                    <div class="comm-stat">
                        <p class="comm-stat-k">{{ $stat['label'] }}</p>
                        <p class="comm-stat-v">{{ number_format($stat['value'], 0, ',', ' ') }}</p>
                    </div>
                @endforeach
            </div>
        </x-filament::section>

        <div class="comm-layout">
            <x-filament::section heading="Listy i subskrybenci" description="Zarzadzanie listami mailingowymi i rekordami subskrypcji.">
                <div class="comm-center-shell">
                    <div class="comm-input-grid two">
                        <x-filament::input.wrapper>
                            <x-filament::input wire:model.defer="newListName" type="text" placeholder="Nowa lista, np. Newsletter parafialny" />
                        </x-filament::input.wrapper>
                        <x-filament::button wire:click="createList" icon="heroicon-m-plus" color="primary">
                            Dodaj liste
                        </x-filament::button>
                    </div>

                    @error('newListName')
                        <p class="text-xs text-danger-600">{{ $message }}</p>
                    @enderror

                    <div class="comm-list-grid">
                        @forelse ($this->mailingLists as $list)
                            <div class="comm-list-row">
                                <div>
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $list->name }}</p>
                                    <p class="comm-list-meta">
                                        aktywni: {{ number_format($list->subscribers_active_count, 0, ',', ' ') }} ·
                                        potwierdzeni: {{ number_format($list->subscribers_confirmed_count, 0, ',', ' ') }} ·
                                        lacznie: {{ number_format($list->subscribers_total_count, 0, ',', ' ') }}
                                    </p>
                                </div>
                                <div class="flex flex-wrap gap-1">
                                    <x-filament::button size="xs" color="{{ $selectedListId === $list->id ? 'success' : 'gray' }}" wire:click="selectList({{ $list->id }})">
                                        {{ $selectedListId === $list->id ? 'Wybrana' : 'Wybierz' }}
                                    </x-filament::button>
                                    <x-filament::button size="xs" color="danger" wire:click="deleteList({{ $list->id }})">
                                        Usun
                                    </x-filament::button>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500">Brak list mailingowych. Dodaj pierwsza liste, aby zaczac.</p>
                        @endforelse
                    </div>

                    @if ($selectedListId)
                        <div class="comm-input-grid two">
                            <x-filament::input.wrapper>
                                <x-filament::input wire:model.defer="renameListName" type="text" placeholder="Nazwa wybranej listy" />
                            </x-filament::input.wrapper>
                            <x-filament::button wire:click="saveSelectedListName" icon="heroicon-m-pencil-square" color="gray">
                                Zapisz nazwe listy
                            </x-filament::button>
                        </div>

                        <div class="comm-input-grid two">
                            <x-filament::input.wrapper>
                                <x-filament::input wire:model.defer="newSubscriberEmail" type="email" placeholder="email@adres.pl" />
                            </x-filament::input.wrapper>
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" wire:model="newSubscriberConfirmed" class="rounded border-gray-300">
                                Dodaj jako potwierdzonego
                            </label>
                        </div>
                        <x-filament::button wire:click="addSubscriber" icon="heroicon-m-user-plus" color="success">
                            Dodaj subskrybenta do wybranej listy
                        </x-filament::button>

                        @error('newSubscriberEmail')
                            <p class="text-xs text-danger-600">{{ $message }}</p>
                        @enderror

                        <x-filament::input.wrapper>
                            <x-filament::input wire:model.live.debounce.250ms="subscriberSearch" type="text" placeholder="Szukaj subskrybenta po email" />
                        </x-filament::input.wrapper>

                        <div class="comm-subscribers">
                            @forelse ($this->subscribers as $subscriber)
                                <div class="comm-sub-row">
                                    <div class="comm-sub-top">
                                        <p class="comm-sub-email">{{ $subscriber->email }}</p>
                                        <div class="flex flex-wrap gap-1">
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
                                    </div>
                                    <p class="comm-sub-meta">
                                        lista: {{ $subscriber->mailingList?->name ?? 'brak' }} ·
                                        dodano: {{ $subscriber->created_at?->format('d.m.Y H:i') }}
                                    </p>
                                    <div class="comm-sub-actions">
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
                    @else
                        <p class="text-sm text-gray-500">Wybierz liste mailingowa, aby zarzadzac subskrybentami.</p>
                    @endif
                </div>
            </x-filament::section>

            <x-filament::section heading="Kampania email" description="Wysylka do pojedynczych osob, grup parafian, administratorow oraz list mailingowych.">
                <div class="comm-center-shell">
                    <div class="comm-input-grid two">
                        <label class="text-sm font-medium">Zakres odbiorcow</label>
                        <x-filament::input.wrapper>
                            <select wire:model.live="recipientScope" class="fi-input block w-full rounded-lg border-gray-300 text-sm">
                                @foreach ($this->recipientScopeOptions as $scopeKey => $scopeLabel)
                                    <option value="{{ $scopeKey }}">{{ $scopeLabel }}</option>
                                @endforeach
                            </select>
                        </x-filament::input.wrapper>
                    </div>

                    @if ($recipientScope === 'single_users')
                        <div>
                            <label class="text-sm font-medium">Wybierz odbiorcow (max 500 z podpowiedzi)</label>
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
                        <div>
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
                        <div class="comm-input-grid two">
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
                        <div>
                            <label class="text-sm font-medium">Wlasne emaile (oddziel przecinkiem, srednikiem lub nowa linia)</label>
                            <x-filament::input.wrapper>
                                <textarea wire:model.defer="customEmails" rows="6" class="fi-input w-full rounded-lg" placeholder="a@example.com&#10;b@example.com"></textarea>
                            </x-filament::input.wrapper>
                        </div>
                    @endif

                    <div class="comm-input-grid two">
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" wire:model="includeInactiveUsers" class="rounded border-gray-300">
                            uwzglednij konta nieaktywne
                        </label>
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" wire:model="onlyVerifiedUsers" class="rounded border-gray-300">
                            tylko konta zweryfikowane przez parafie
                        </label>
                    </div>

                    <div>
                        <label class="text-sm font-medium">Temat</label>
                        <x-filament::input.wrapper>
                            <x-filament::input wire:model.defer="subjectLine" type="text" placeholder="Temat kampanii" />
                        </x-filament::input.wrapper>
                        @error('subjectLine')
                            <p class="text-xs text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="text-sm font-medium">Tresc wiadomosci</label>
                        <x-filament::input.wrapper>
                            <textarea wire:model.defer="messageBody" rows="12" class="fi-input w-full rounded-lg" placeholder="Wpisz tresc maila..."></textarea>
                        </x-filament::input.wrapper>
                        @error('messageBody')
                            <p class="text-xs text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" wire:model="sendCopyToMe" class="rounded border-gray-300">
                        wyslij kopie do mnie
                    </label>

                    <div class="comm-preview">
                        <p class="text-sm font-semibold">Podglad odbiorcow</p>
                        <p class="mt-1 text-xs text-gray-500">Lacznie: {{ number_format($this->recipientPreview['count'], 0, ',', ' ') }}</p>

                        <div class="comm-preview-list">
                            @forelse ($this->recipientPreview['sample'] as $recipient)
                                <x-filament::badge color="gray">{{ $recipient['email'] }}</x-filament::badge>
                            @empty
                                <span class="text-xs text-gray-500">Brak odbiorcow dla obecnych filtrow.</span>
                            @endforelse
                        </div>
                    </div>

                    <x-filament::button wire:click="sendCampaign" wire:loading.attr="disabled" wire:target="sendCampaign" icon="heroicon-m-paper-airplane" color="primary" class="w-full justify-center">
                        Wyslij kampanie email
                    </x-filament::button>
                </div>
            </x-filament::section>
        </div>
    </div>
</x-filament-panels::page>
