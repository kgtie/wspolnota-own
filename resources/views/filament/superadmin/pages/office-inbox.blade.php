<x-filament-panels::page>
    <style>
        .office-inbox-layout {
            display: grid;
            grid-template-columns: minmax(19rem, 23rem) minmax(0, 1fr);
            gap: 1rem;
            align-items: start;
        }

        @media (max-width: 72rem) {
            .office-inbox-layout {
                grid-template-columns: 1fr;
            }
        }

        .office-inbox-sidebar .fi-section-content,
        .office-thread-section .fi-section-content {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .office-inbox-filters {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .office-conversation-list {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            max-height: 65vh;
            overflow-y: auto;
            padding-right: 0.125rem;
        }

        .office-conversation-item {
            width: 100%;
            text-align: left;
            border: 1px solid var(--gray-200);
            border-radius: 0.75rem;
            background: var(--color-white);
            padding: 0.75rem;
            transition: border-color 120ms ease, background-color 120ms ease;
        }

        .office-conversation-item:hover {
            border-color: var(--primary-400);
            background: var(--primary-50);
        }

        .office-conversation-item.is-selected {
            border-color: var(--primary-500);
            background: var(--primary-50);
        }

        .dark .office-conversation-item {
            border-color: var(--gray-700);
            background: color-mix(in oklab, var(--gray-900) 70%, transparent);
        }

        .dark .office-conversation-item:hover,
        .dark .office-conversation-item.is-selected {
            border-color: var(--primary-500);
            background: color-mix(in oklab, var(--primary-900) 30%, transparent);
        }

        .office-conversation-item__header {
            display: flex;
            justify-content: space-between;
            gap: 0.5rem;
            align-items: flex-start;
        }

        .office-conversation-item__identity {
            display: flex;
            align-items: center;
            gap: 0.625rem;
            min-width: 0;
        }

        .office-avatar {
            width: 2.25rem;
            height: 2.25rem;
            border-radius: 9999px;
            object-fit: cover;
            flex-shrink: 0;
            border: 1px solid var(--gray-200);
            background: var(--gray-100);
        }

        .dark .office-avatar {
            border-color: var(--gray-700);
            background: var(--gray-800);
        }

        .office-conversation-item__identity-text {
            min-width: 0;
        }

        .office-conversation-item__name {
            font-size: 0.925rem;
            font-weight: 600;
            color: var(--gray-900);
            line-height: 1.2;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .dark .office-conversation-item__name {
            color: var(--color-white);
        }

        .office-conversation-item__email,
        .office-conversation-item__time {
            font-size: 0.75rem;
            color: var(--gray-500);
        }

        .dark .office-conversation-item__email,
        .dark .office-conversation-item__time {
            color: var(--gray-400);
        }

        .office-conversation-item__preview {
            margin-top: 0.5rem;
            font-size: 0.8rem;
            color: var(--gray-700);
            line-height: 1.35;
        }

        .dark .office-conversation-item__preview {
            color: var(--gray-300);
        }

        .office-conversation-item__badges {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            flex-shrink: 0;
        }

        .office-thread-toolbar {
            display: flex;
            gap: 0.5rem;
        }

        .office-thread-status {
            display: flex;
            align-items: center;
        }

        .office-thread-messages {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            max-height: 58vh;
            overflow-y: auto;
            padding-right: 0.125rem;
            scroll-behavior: smooth;
        }

        .office-thread-row {
            display: flex;
            align-items: flex-end;
            gap: 0.5rem;
        }

        .office-thread-row.is-mine {
            justify-content: flex-end;
        }

        .office-thread-row.is-mine .office-thread-avatar {
            order: 2;
        }

        .office-thread-avatar {
            width: 2rem;
            height: 2rem;
            border-radius: 9999px;
            object-fit: cover;
            flex-shrink: 0;
            border: 1px solid var(--gray-200);
            background: var(--gray-100);
        }

        .dark .office-thread-avatar {
            border-color: var(--gray-700);
            background: var(--gray-800);
        }

        .office-thread-bubble {
            max-width: min(50rem, 82%);
            border: 1px solid var(--gray-200);
            border-radius: 0.875rem;
            background: var(--gray-50);
            padding: 0.75rem 0.875rem;
            font-size: 0.875rem;
            line-height: 1.45;
            color: var(--gray-900);
        }

        .office-thread-row.is-mine .office-thread-bubble {
            border-color: var(--primary-300);
            background: var(--primary-50);
        }

        .dark .office-thread-bubble {
            border-color: var(--gray-700);
            background: color-mix(in oklab, var(--gray-900) 75%, transparent);
            color: var(--gray-100);
        }

        .dark .office-thread-row.is-mine .office-thread-bubble {
            border-color: var(--primary-700);
            background: color-mix(in oklab, var(--primary-900) 35%, transparent);
            color: var(--gray-100);
        }

        .office-thread-meta {
            margin-top: 0.5rem;
            font-size: 0.7rem;
            color: var(--gray-500);
        }

        .dark .office-thread-meta {
            color: var(--gray-400);
        }

        .office-thread-attachments {
            margin-top: 0.5rem;
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .office-unread-separator {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin: 0.25rem 0;
        }

        .office-unread-separator::before,
        .office-unread-separator::after {
            content: '';
            flex: 1 1 auto;
            border-top: 1px solid var(--warning-300);
        }

        .dark .office-unread-separator::before,
        .dark .office-unread-separator::after {
            border-top-color: var(--warning-700);
        }

        .office-reply-form {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            border-top: 1px solid var(--gray-200);
            padding-top: 0.75rem;
            margin-top: 0.25rem;
        }

        .dark .office-reply-form {
            border-top-color: var(--gray-700);
        }

        .office-reply-textarea {
            width: 100%;
            min-height: 7rem;
            resize: vertical;
        }

        .office-validation-error {
            font-size: 0.75rem;
            color: var(--danger-600);
        }

        .office-reply-loading {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.75rem;
            color: var(--gray-500);
        }

        .dark .office-reply-loading {
            color: var(--gray-400);
        }

        .dark .office-validation-error {
            color: var(--danger-400);
        }
    </style>

    @php
        $selectedConversation = $this->selectedConversation;
        $parishioner = $selectedConversation?->parishioner;
        $assignedPriest = $selectedConversation?->priest;
        $threadName = $parishioner?->full_name ?: $parishioner?->name ?: 'Uzytkownik usuniety';
        $threadEmail = $parishioner?->email ?: 'brak adresu email';
        $threadParish = $selectedConversation?->parish?->name ?: 'Parafia usunieta';
        $threadPriest = $assignedPriest?->full_name ?: $assignedPriest?->name ?: $assignedPriest?->email ?: 'Brak przypisanego administratora';
        $actorId = auth()->id();
    @endphp

    <div class="office-inbox-layout" wire:poll.8s="refreshThread">
        <x-filament::section class="office-inbox-sidebar" heading="Konwersacje" description="Watki rozpoczęte przez parafian.">
            <div class="office-inbox-filters">
                <x-filament::button size="sm" :outlined="$conversationFilter !== 'open'" wire:click="$set('conversationFilter', 'open')">
                    Otwarte
                </x-filament::button>
                <x-filament::button size="sm" color="gray" :outlined="$conversationFilter !== 'closed'" wire:click="$set('conversationFilter', 'closed')">
                    Zamkniete
                </x-filament::button>
                <x-filament::button size="sm" color="gray" :outlined="$conversationFilter !== 'all'" wire:click="$set('conversationFilter', 'all')">
                    Wszystkie
                </x-filament::button>
            </div>

            <x-filament::input.wrapper>
                <x-filament::input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Szukaj po parafianinie, parafii lub adminie"
                />
            </x-filament::input.wrapper>

            <x-filament::input.wrapper>
                <select wire:model.live="parishFilterId" class="fi-input block w-full rounded-lg border-gray-300 text-sm">
                    <option value="">Wszystkie parafie</option>
                    @foreach ($this->parishFilterOptions as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </x-filament::input.wrapper>

            <x-filament::input.wrapper>
                <select wire:model.live="priestFilterId" class="fi-input block w-full rounded-lg border-gray-300 text-sm">
                    <option value="">Wszyscy administratorzy</option>
                    @foreach ($this->priestFilterOptions as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </x-filament::input.wrapper>

            <div class="office-conversation-list">
                @forelse ($this->conversations as $conversation)
                    @php
                        $parishioner = $conversation->parishioner;
                        $parishionerLabel = $parishioner?->full_name ?: $parishioner?->name ?: 'Uzytkownik usuniety';
                        $parishionerEmail = $parishioner?->email ?: 'brak adresu email';
                        $parishionerAvatar = $parishioner?->avatar_url;
                        $isSelected = $selectedConversationId === $conversation->id;
                        $latest = $conversation->latestMessage;
                        $latestPreview = $latest?->body ?: ($latest?->has_attachments ? 'Zalacznik' : 'Brak tresci');
                        $conversationParish = $conversation->parish?->short_name ?: $conversation->parish?->name ?: 'Parafia usunieta';
                    @endphp

                    <button
                        type="button"
                        wire:key="conversation-{{ $conversation->id }}"
                        wire:click="selectConversation({{ $conversation->id }})"
                        class="office-conversation-item {{ $isSelected ? 'is-selected' : '' }}"
                    >
                        <div class="office-conversation-item__header">
                            <div class="office-conversation-item__identity">
                                @if ($parishionerAvatar)
                                    <img src="{{ $parishionerAvatar }}" alt="{{ $parishionerLabel }}" class="office-avatar" loading="lazy">
                                @endif
                                <div class="office-conversation-item__identity-text">
                                    <p class="office-conversation-item__name">{{ $parishionerLabel }}</p>
                                    <p class="office-conversation-item__email">{{ $parishionerEmail }} · {{ $conversationParish }}</p>
                                </div>
                            </div>
                            <div class="office-conversation-item__badges">
                                <x-filament::badge :color="$conversation->status === \App\Models\OfficeConversation::STATUS_OPEN ? 'success' : 'gray'" size="sm">
                                    {{ $conversation->status === \App\Models\OfficeConversation::STATUS_OPEN ? 'OTWARTA' : 'ZAMKNIETA' }}
                                </x-filament::badge>

                                @if ($conversation->unread_for_priest_count > 0)
                                    <x-filament::badge color="warning" size="sm">
                                        {{ $conversation->unread_for_priest_count }}
                                    </x-filament::badge>
                                @endif
                            </div>
                        </div>

                        <p class="office-conversation-item__preview">
                            {{ \Illuminate\Support\Str::limit($latestPreview, 130) }}
                        </p>

                        <p class="office-conversation-item__time">
                            {{ optional($conversation->last_message_at)->diffForHumans() ?: optional($conversation->created_at)->diffForHumans() }}
                        </p>
                    </button>
                @empty
                    <x-filament::empty-state
                        icon="heroicon-o-chat-bubble-left-right"
                        heading="Brak konwersacji"
                        description="Dla wybranych filtrow nie ma rozmow."
                    />
                @endforelse
            </div>
        </x-filament::section>

        <x-filament::section
            class="office-thread-section"
            :heading="$selectedConversation ? $threadName : 'Wybierz konwersacje'"
            :description="$selectedConversation ? ($threadEmail . ' · ' . $threadParish . ' · admin: ' . $threadPriest) : 'Po wybraniu konwersacji zobaczysz tresc rozmowy i formularz odpowiedzi.'"
        >
            @if ($selectedConversation)
                <x-slot name="afterHeader">
                    <div class="office-thread-toolbar">
                        @if ($selectedConversation->status === \App\Models\OfficeConversation::STATUS_OPEN)
                            <x-filament::button size="sm" color="gray" outlined wire:click="closeConversation">
                                Zamknij konwersacje
                            </x-filament::button>
                        @else
                            <x-filament::button size="sm" color="success" outlined wire:click="reopenConversation">
                                Otworz ponownie
                            </x-filament::button>
                        @endif
                    </div>
                </x-slot>

                <div
                    class="office-thread-messages"
                    x-data="{ scrollToBottom() { this.$el.scrollTop = this.$el.scrollHeight } }"
                    x-init="requestAnimationFrame(() => scrollToBottom())"
                    x-on:office-scroll-bottom.window="requestAnimationFrame(() => scrollToBottom())"
                >
                    @forelse ($selectedConversation->messages as $message)
                        @php
                            $isPriestMessage = (int) $message->sender_user_id === (int) $actorId;
                            $sender = $message->sender;
                            $senderLabel = $sender?->full_name ?: $sender?->name ?: ($isPriestMessage ? 'Superadmin' : 'Uzytkownik');
                            $senderAvatar = $sender?->avatar_url
                                ?: ($isPriestMessage ? auth()->user()?->avatar_url : $parishioner?->avatar_url);
                        @endphp

                        @if ($firstUnreadMessageId && ((int) $message->id === (int) $firstUnreadMessageId))
                            <div class="office-unread-separator">
                                <x-filament::badge color="warning" size="sm">Nowe</x-filament::badge>
                            </div>
                        @endif

                        <div class="office-thread-row {{ $isPriestMessage ? 'is-mine' : '' }}">
                            @if ($senderAvatar)
                                <img src="{{ $senderAvatar }}" alt="{{ $senderLabel }}" class="office-thread-avatar" loading="lazy">
                            @endif
                            <div class="office-thread-bubble">
                                @if ($message->body)
                                    <p style="white-space: pre-line;">{{ $message->body }}</p>
                                @endif

                                @if ($message->has_attachments)
                                    <div class="office-thread-attachments">
                                        @foreach ($message->getMedia('attachments') as $media)
                                            <x-filament::link
                                                :href="route('office.attachments.download', ['media' => $media])"
                                                icon="heroicon-m-paper-clip"
                                                size="sm"
                                            >
                                                Pobierz: {{ $media->file_name }}
                                            </x-filament::link>
                                        @endforeach
                                    </div>
                                @endif

                                <p class="office-thread-meta">
                                    {{ $senderLabel }} · {{ $message->created_at?->format('d.m.Y H:i') }}
                                </p>
                            </div>
                        </div>
                    @empty
                        <x-filament::empty-state
                            compact
                            icon="heroicon-o-chat-bubble-left-right"
                            heading="Brak wiadomosci"
                            description="Ta konwersacja nie zawiera jeszcze zadnej tresci."
                        />
                    @endforelse
                </div>

                <div class="office-reply-form">
                    @if ($this->hasOpenConversation())
                        <x-filament::input.wrapper>
                            <textarea
                                wire:model.defer="body"
                                rows="4"
                                class="fi-input office-reply-textarea"
                                placeholder="Wpisz odpowiedz..."
                                wire:loading.attr="disabled"
                                wire:target="sendMessage"
                            ></textarea>
                        </x-filament::input.wrapper>

                        @error('body')
                            <p class="office-validation-error">{{ $message }}</p>
                        @enderror

                        @if ($this->uploadsEnabledForSelectedConversation())
                            <x-filament::input.wrapper>
                                <input
                                    type="file"
                                    wire:model="attachments"
                                    multiple
                                    class="fi-input"
                                    wire:loading.attr="disabled"
                                    wire:target="sendMessage,attachments"
                                />
                            </x-filament::input.wrapper>

                            @error('attachments')
                                <p class="office-validation-error">{{ $message }}</p>
                            @enderror

                            @error('attachments.*')
                                <p class="office-validation-error">{{ $message }}</p>
                            @enderror
                        @else
                            <x-filament::badge color="gray" size="sm">
                                Wysylanie zalacznikow jest aktualnie wylaczone dla tej parafii.
                            </x-filament::badge>
                        @endif

                        <div>
                            <x-filament::button
                                wire:click="sendMessage"
                                wire:loading.attr="disabled"
                                wire:target="sendMessage"
                                icon="heroicon-m-paper-airplane"
                            >
                                Wyslij odpowiedz
                            </x-filament::button>
                        </div>

                        <div wire:loading.flex wire:target="sendMessage" class="office-reply-loading">
                            <x-filament::loading-indicator class="h-4 w-4" />
                            <span>Wysylanie wiadomosci...</span>
                        </div>
                    @else
                        <x-filament::empty-state
                            compact
                            icon="heroicon-o-lock-closed"
                            heading="Konwersacja zamknieta"
                            description="Aby odpowiedziec, uzyj akcji Otworz ponownie."
                        />
                    @endif
                </div>
            @else
                <x-filament::empty-state
                    icon="heroicon-o-chat-bubble-left-right"
                    heading="Wybierz konwersacje"
                    description="Kliknij rozmowe z listy po lewej stronie, aby wyswietlic szczegoly."
                />
            @endif
        </x-filament::section>
    </div>
</x-filament-panels::page>
