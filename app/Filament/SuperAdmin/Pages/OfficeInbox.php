<?php

namespace App\Filament\SuperAdmin\Pages;

use App\Models\OfficeConversation;
use App\Models\OfficeMessage;
use App\Models\Parish;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\WithFileUploads;

/**
 * Globalna skrzynka kancelarii online dla superadministratora.
 *
 * Superadmin widzi wszystkie konwersacje wszystkich parafii, moze filtrowac je
 * po parafii i proboszczu oraz interweniowac bez ograniczen tenantowych.
 */
class OfficeInbox extends Page
{
    use WithFileUploads;

    protected static ?string $title = 'Centrum konwersacji online';

    protected static string | \BackedEnum | null $navigationIcon = null;

    protected static ?string $navigationLabel = 'Centrum konwersacji';

    protected static string | \UnitEnum | null $navigationGroup = 'Komunikacja i kampanie';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.superadmin.pages.office-inbox';

    public ?int $selectedConversationId = null;

    public string $conversationFilter = 'open';

    public string $search = '';

    public ?int $parishFilterId = null;

    public ?int $priestFilterId = null;

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

        return $user instanceof User && $user->isSuperAdmin();
    }

    public static function getNavigationBadge(): ?string
    {
        $count = OfficeConversation::query()
            ->where('status', OfficeConversation::STATUS_OPEN)
            ->whereHas('messages', fn (Builder $query) => $query
                ->whereNull('read_by_priest_at')
                ->whereColumn('sender_user_id', '!=', 'office_conversations.priest_user_id'))
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

    public function getParishFilterOptionsProperty(): array
    {
        return Parish::query()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    public function getPriestFilterOptionsProperty(): array
    {
        return User::query()
            ->where('role', '>=', 1)
            ->orderByRaw("COALESCE(NULLIF(full_name, ''), name, email)")
            ->get()
            ->mapWithKeys(fn (User $user): array => [
                $user->getKey() => $user->full_name ?: $user->name ?: $user->email,
            ])
            ->all();
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
                'priest:id,name,full_name,email',
                'priest.media',
                'parish:id,name,short_name,settings,slug',
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

    public function updatedParishFilterId(): void
    {
        $this->ensureSelectedConversationInList();
    }

    public function updatedPriestFilterId(): void
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
        $this->dispatch('office-scroll-bottom');
    }

    public function refreshThread(): void
    {
        if ($this->selectedConversationId) {
            $this->captureFirstUnreadMessageId($this->selectedConversationId);
        }
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
            event: 'office_conversation_closed_by_superadmin',
            conversation: $conversation,
            description: 'Superadmin zamknal konwersacje kancelarii online.',
        );
    }

    public function reopenConversation(): void
    {
        $conversation = $this->selectedConversation;

        if (! $conversation instanceof OfficeConversation) {
            return;
        }

        if ($conversation->status === OfficeConversation::STATUS_OPEN) {
            return;
        }

        $conversation->reopen();

        $this->logOfficeEvent(
            event: 'office_conversation_reopened_by_superadmin',
            conversation: $conversation,
            description: 'Superadmin ponownie otworzyl konwersacje kancelarii online.',
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

        // Nawet superadmin respektuje flagi parafii dla uploadu plikow, zeby
        // nie obchodzic swiadomie ograniczen ustawionych w usludze kancelarii.
        $uploadsEnabled = (bool) $conversation->parish->getSetting('office_file_upload_enabled', true);

        if (! $uploadsEnabled && count($this->attachments) > 0) {
            $this->addError('attachments', 'Wysyłanie załączników jest wyłączone dla tej parafii.');

            return;
        }

        $message = OfficeMessage::create([
            'office_conversation_id' => $conversation->getKey(),
            'sender_user_id' => $this->getActorId(),
            'body' => $trimmedBody !== '' ? $trimmedBody : null,
            'has_attachments' => false,
            'read_by_parishioner_at' => null,
            'read_by_priest_at' => null,
        ]);

        if ($uploadsEnabled && count($this->attachments) > 0) {
            foreach ($this->attachments as $uploadedFile) {
                $message
                    ->addMedia($uploadedFile)
                    ->withCustomProperties([
                        'visibility' => 'private',
                        'parish_id' => $conversation->parish_id,
                        'conversation_id' => $conversation->getKey(),
                        'sender_user_id' => $this->getActorId(),
                        'sender_role' => 'superadmin',
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
            event: 'office_message_sent_by_superadmin',
            conversation: $conversation,
            description: 'Superadmin wysłał wiadomość w kancelarii online.',
            properties: [
                'office_message_id' => $message->getKey(),
                'has_body' => filled($message->body),
                'attachments_count' => $message->getMedia('attachments')->count(),
            ],
        );

        $this->reset(['body', 'attachments']);
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
                'priest:id,name,full_name,email',
                'parish:id,name,short_name',
                'latestMessage' => fn ($innerQuery) => $innerQuery->select([
                    'office_messages.id',
                    'office_messages.office_conversation_id',
                    'office_messages.sender_user_id',
                    'office_messages.body',
                    'office_messages.has_attachments',
                    'office_messages.created_at',
                ]),
            ])
            ->withCount([
                'messages as unread_for_priest_count' => fn ($innerQuery) => $innerQuery
                    ->whereNull('read_by_priest_at')
                    ->whereColumn('sender_user_id', '!=', 'office_conversations.priest_user_id'),
            ]);

        if ($this->conversationFilter === 'open') {
            $query->where('status', OfficeConversation::STATUS_OPEN);
        }

        if ($this->conversationFilter === 'closed') {
            $query->where('status', OfficeConversation::STATUS_CLOSED);
        }

        if ($this->parishFilterId) {
            $query->where('parish_id', $this->parishFilterId);
        }

        if ($this->priestFilterId) {
            $query->where('priest_user_id', $this->priestFilterId);
        }

        if ($this->search !== '') {
            $search = mb_strtolower(trim($this->search));

            $query->where(function (Builder $outerQuery) use ($search): void {
                $outerQuery
                    ->whereHas('parishioner', fn (Builder $innerQuery) => $innerQuery
                        ->whereRaw("LOWER(COALESCE(full_name, '')) like ?", ["%{$search}%"])
                        ->orWhereRaw("LOWER(COALESCE(name, '')) like ?", ["%{$search}%"])
                        ->orWhereRaw('LOWER(email) like ?', ["%{$search}%"]))
                    ->orWhereHas('priest', fn (Builder $innerQuery) => $innerQuery
                        ->whereRaw("LOWER(COALESCE(full_name, '')) like ?", ["%{$search}%"])
                        ->orWhereRaw("LOWER(COALESCE(name, '')) like ?", ["%{$search}%"])
                        ->orWhereRaw('LOWER(email) like ?', ["%{$search}%"]))
                    ->orWhereHas('parish', fn (Builder $innerQuery) => $innerQuery
                        ->whereRaw("LOWER(COALESCE(name, '')) like ?", ["%{$search}%"])
                        ->orWhereRaw("LOWER(COALESCE(short_name, '')) like ?", ["%{$search}%"]));
            });
        }

        return $query
            ->orderByRaw('COALESCE(last_message_at, created_at) desc')
            ->limit(150);
    }

    protected function getAccessibleConversationsQuery(): Builder
    {
        return OfficeConversation::query();
    }

    protected function selectDefaultConversation(): void
    {
        $firstConversationId = $this->getConversationsListQuery()->value('id');

        $this->selectedConversationId = $firstConversationId ? (int) $firstConversationId : null;
        $this->firstUnreadMessageId = null;

        if ($this->selectedConversationId) {
            $this->captureFirstUnreadMessageId($this->selectedConversationId);
        }
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
        $conversation = OfficeConversation::query()->find($conversationId);

        if (! $conversation instanceof OfficeConversation) {
            $this->firstUnreadMessageId = null;

            return;
        }

        $this->firstUnreadMessageId = OfficeMessage::query()
            ->where('office_conversation_id', $conversationId)
            ->where('sender_user_id', '!=', $conversation->priest_user_id)
            ->whereNull('read_by_priest_at')
            ->orderBy('id')
            ->value('id');
    }

    protected function getActorId(): int
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
                'superadmin_user_id' => $actor->getKey(),
            ], $properties))
            ->log($description);
    }
}
