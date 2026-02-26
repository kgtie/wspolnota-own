<?php

namespace App\Filament\Admin\Pages;

use App\Models\OfficeConversation;
use App\Models\OfficeMessage;
use App\Models\Parish;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\WithFileUploads;

class OfficeInbox extends Page
{
    use WithFileUploads;

    protected static ?string $title = 'Kancelaria online';

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationLabel = 'Kancelaria online';

    protected static string | \UnitEnum | null $navigationGroup = 'Komunikacja';

    protected static ?int $navigationSort = 50;

    protected string $view = 'filament.admin.pages.office-inbox';

    public ?int $selectedConversationId = null;

    public string $conversationFilter = 'open';

    public string $search = '';

    public string $body = '';

    public array $attachments = [];

    public ?int $firstUnreadMessageId = null;

    public function mount(): void
    {
        $this->selectDefaultConversation();
    }

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();
        $tenant = Filament::getTenant();

        if (! $user instanceof User) {
            return false;
        }

        if (! $tenant instanceof Parish) {
            return false;
        }

        if (! $user->isAdmin()) {
            return false;
        }

        return $user->managedParishes()
            ->wherePivot('is_active', true)
            ->whereKey($tenant->getKey())
            ->exists();
    }

    public static function getNavigationBadge(): ?string
    {
        $tenant = Filament::getTenant();
        $user = Filament::auth()->user();

        if (! $tenant instanceof Parish || ! $user instanceof User) {
            return null;
        }

        $count = OfficeConversation::query()
            ->where('parish_id', $tenant->getKey())
            ->where('priest_user_id', $user->getKey())
            ->whereHas('messages', fn (Builder $query) => $query
                ->whereNull('read_by_priest_at')
                ->where('sender_user_id', '!=', $user->getKey()))
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public function getConversationsProperty(): Collection
    {
        return $this->getConversationsListQuery()->get();
    }

    public function getSelectedConversationProperty(): ?OfficeConversation
    {
        if (! $this->selectedConversationId) {
            return null;
        }

        return $this->getAccessibleConversationsQuery()
            ->with([
                'parishioner:id,name,full_name,email',
                'parishioner.media',
                'parish:id,name,settings,slug',
                'messages' => fn ($query) => $query
                    ->with(['sender:id,name,full_name,email', 'sender.media', 'media'])
                    ->orderBy('created_at'),
            ])
            ->whereKey($this->selectedConversationId)
            ->first();
    }

    public function updatedConversationFilter(): void
    {
        if (! in_array($this->conversationFilter, ['open', 'closed', 'all'], true)) {
            $this->conversationFilter = 'open';
        }

        $this->ensureSelectedConversationInList();
    }

    public function updatedSearch(): void
    {
        $this->ensureSelectedConversationInList();
    }

    public function selectConversation(int $conversationId): void
    {
        $conversationExists = $this->getAccessibleConversationsQuery()
            ->whereKey($conversationId)
            ->exists();

        if (! $conversationExists) {
            return;
        }

        $this->selectedConversationId = $conversationId;
        $this->captureFirstUnreadMessageId($conversationId);
        $this->markSelectedConversationAsRead();
        $this->dispatch('office-scroll-bottom');
    }

    public function refreshThread(): void
    {
        if ($this->selectedConversationId) {
            $this->captureFirstUnreadMessageId($this->selectedConversationId);
        }

        $this->markSelectedConversationAsRead();
    }

    public function closeConversation(): void
    {
        $conversation = $this->selectedConversation;

        if (! $conversation instanceof OfficeConversation) {
            return;
        }

        if ($conversation->status === OfficeConversation::STATUS_CLOSED) {
            return;
        }

        $conversation->close();

        $this->logOfficeEvent(
            event: 'office_conversation_closed_by_priest',
            conversation: $conversation,
            description: 'Proboszcz zamknal konwersacje kancelarii online.',
        );
    }

    public function reopenConversation(): void
    {
        // Celowo zablokowane: proboszcz nie moze ponownie otworzyc zamknietej konwersacji.
        $conversation = $this->selectedConversation;
        if (! $conversation instanceof OfficeConversation) {
            return;
        }

        $this->logOfficeEvent(
            event: 'office_conversation_reopen_blocked',
            conversation: $conversation,
            description: 'Zablokowano probe ponownego otwarcia zamknietej konwersacji przez proboszcza.',
        );
    }

    public function sendMessage(): void
    {
        $conversation = $this->selectedConversation;

        if (! $conversation instanceof OfficeConversation) {
            return;
        }

        if ($conversation->status !== OfficeConversation::STATUS_OPEN) {
            $this->addError('body', 'Nie mozna odpowiedziec w zamknietej konwersacji.');

            return;
        }

        $this->validate([
            'body' => ['nullable', 'string', 'max:12000'],
            'attachments' => ['array', 'max:5'],
            'attachments.*' => ['file', 'max:10240'],
        ]);

        $trimmedBody = trim($this->body);

        if ($trimmedBody === '' && count($this->attachments) === 0) {
            $this->addError('body', 'Wiadomosc nie moze byc pusta.');

            return;
        }

        $uploadsEnabled = (bool) $conversation->parish->getSetting('office_file_upload_enabled', true);

        if (! $uploadsEnabled && count($this->attachments) > 0) {
            $this->addError('attachments', 'Wysylanie zalacznikow jest wylaczone dla tej parafii.');

            return;
        }

        $message = OfficeMessage::create([
            'office_conversation_id' => $conversation->getKey(),
            'sender_user_id' => $this->getPriestId(),
            'body' => $trimmedBody !== '' ? $trimmedBody : null,
            'has_attachments' => false,
            'read_by_parishioner_at' => null,
            'read_by_priest_at' => now(),
        ]);

        if ($uploadsEnabled && count($this->attachments) > 0) {
            foreach ($this->attachments as $uploadedFile) {
                $message
                    ->addMedia($uploadedFile)
                    ->withCustomProperties([
                        'visibility' => 'private',
                        'parish_id' => $conversation->parish_id,
                        'conversation_id' => $conversation->getKey(),
                        'sender_user_id' => $this->getPriestId(),
                    ])
                    ->toMediaCollection('attachments', 'office');
            }

            $message->update(['has_attachments' => true]);
        }

        $conversation->update([
            'last_message_at' => now(),
            'status' => OfficeConversation::STATUS_OPEN,
            'closed_at' => null,
        ]);

        $this->logOfficeEvent(
            event: 'office_message_sent_by_priest',
            conversation: $conversation,
            description: 'Proboszcz wyslal wiadomosc w kancelarii online.',
            properties: [
                'office_message_id' => $message->getKey(),
                'has_body' => filled($message->body),
                'attachments_count' => $message->getMedia('attachments')->count(),
            ],
        );

        $this->reset(['body', 'attachments']);
        $this->markSelectedConversationAsRead();
        $this->dispatch('office-scroll-bottom');
    }

    public function hasOpenConversation(): bool
    {
        $conversation = $this->selectedConversation;

        return $conversation instanceof OfficeConversation
            && $conversation->status === OfficeConversation::STATUS_OPEN;
    }

    public function uploadsEnabledForSelectedConversation(): bool
    {
        $conversation = $this->selectedConversation;

        if (! $conversation instanceof OfficeConversation) {
            return false;
        }

        return (bool) $conversation->parish->getSetting('office_file_upload_enabled', true);
    }

    protected function getConversationsListQuery(): Builder
    {
        $query = $this->getAccessibleConversationsQuery()
            ->with([
                'parishioner:id,name,full_name,email',
                'parishioner.media',
                'latestMessage' => fn ($query) => $query->select([
                    'office_messages.id',
                    'office_messages.office_conversation_id',
                    'office_messages.sender_user_id',
                    'office_messages.body',
                    'office_messages.has_attachments',
                    'office_messages.created_at',
                ]),
            ])
            ->withCount([
                'messages as unread_for_priest_count' => fn ($query) => $query
                    ->whereNull('read_by_priest_at')
                    ->where('sender_user_id', '!=', $this->getPriestId()),
            ]);

        if ($this->conversationFilter === 'open') {
            $query->where('status', OfficeConversation::STATUS_OPEN);
        }

        if ($this->conversationFilter === 'closed') {
            $query->where('status', OfficeConversation::STATUS_CLOSED);
        }

        if ($this->search !== '') {
            $search = mb_strtolower(trim($this->search));

            $query->whereHas('parishioner', fn (Builder $innerQuery) => $innerQuery
                ->whereRaw("LOWER(COALESCE(full_name, '')) like ?", ["%{$search}%"])
                ->orWhereRaw("LOWER(COALESCE(name, '')) like ?", ["%{$search}%"])
                ->orWhereRaw('LOWER(email) like ?', ["%{$search}%"]));
        }

        return $query
            ->orderByRaw('COALESCE(last_message_at, created_at) desc')
            ->limit(100);
    }

    protected function getAccessibleConversationsQuery(): Builder
    {
        $tenant = Filament::getTenant();

        return OfficeConversation::query()
            ->when($tenant instanceof Parish, fn (Builder $query) => $query->where('parish_id', $tenant->getKey()))
            ->where('priest_user_id', $this->getPriestId());
    }

    protected function selectDefaultConversation(): void
    {
        $firstConversationId = $this->getConversationsListQuery()->value('id');

        $this->selectedConversationId = $firstConversationId ? (int) $firstConversationId : null;
        $this->firstUnreadMessageId = null;

        if ($this->selectedConversationId) {
            $this->captureFirstUnreadMessageId($this->selectedConversationId);
        }

        $this->markSelectedConversationAsRead();
    }

    protected function ensureSelectedConversationInList(): void
    {
        if (! $this->selectedConversationId) {
            $this->selectDefaultConversation();

            return;
        }

        $existsInCurrentList = $this->getConversationsListQuery()
            ->whereKey($this->selectedConversationId)
            ->exists();

        if ($existsInCurrentList) {
            return;
        }

        $this->selectDefaultConversation();
    }

    protected function captureFirstUnreadMessageId(int $conversationId): void
    {
        $this->firstUnreadMessageId = OfficeMessage::query()
            ->where('office_conversation_id', $conversationId)
            ->where('sender_user_id', '!=', $this->getPriestId())
            ->whereNull('read_by_priest_at')
            ->orderBy('id')
            ->value('id');
    }

    protected function markSelectedConversationAsRead(): void
    {
        if (! $this->selectedConversationId) {
            return;
        }

        OfficeMessage::query()
            ->where('office_conversation_id', $this->selectedConversationId)
            ->where('sender_user_id', '!=', $this->getPriestId())
            ->whereNull('read_by_priest_at')
            ->update(['read_by_priest_at' => now()]);
    }

    protected function getPriestId(): int
    {
        $user = Filament::auth()->user();

        return $user instanceof User ? (int) $user->getKey() : 0;
    }

    protected function logOfficeEvent(
        string $event,
        OfficeConversation $conversation,
        string $description,
        array $properties = [],
    ): void {
        $actor = Filament::auth()->user();

        if (! $actor instanceof User) {
            return;
        }

        activity('office-conversations')
            ->causedBy($actor)
            ->performedOn($conversation)
            ->event($event)
            ->withProperties(array_merge([
                'parish_id' => $conversation->parish_id,
                'office_conversation_id' => $conversation->getKey(),
                'parishioner_user_id' => $conversation->parishioner_user_id,
                'priest_user_id' => $conversation->priest_user_id,
            ], $properties))
            ->log($description);
    }
}
