<?php

namespace App\Filament\SuperAdmin\Pages;

use App\Mail\CommunicationBroadcastMessage;
use App\Models\MailingList;
use App\Models\MailingMail;
use App\Models\Parish;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;

class CommunicationCenter extends Page
{
    protected static ?string $title = 'Centrum komunikacji';

    protected static ?string $navigationLabel = 'Centrum komunikacji';

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-envelope-open';

    protected static string | \UnitEnum | null $navigationGroup = 'Komunikacja';

    protected static ?int $navigationSort = 1;

    protected ?string $subheading = 'Jedno, globalne narzedzie do list mailingowych, subskrybentow i kampanii email.';

    protected string $view = 'filament.superadmin.pages.communication-center';

    public string $newListName = '';

    public ?int $selectedListId = null;

    public string $renameListName = '';

    public string $subscriberSearch = '';

    public string $newSubscriberEmail = '';

    public bool $newSubscriberConfirmed = true;

    public string $recipientScope = 'single_users';

    public array $selectedUserIds = [];

    public ?int $targetParishId = null;

    public ?int $targetMailingListId = null;

    public bool $mailingOnlyConfirmed = true;

    public bool $includeInactiveUsers = false;

    public bool $onlyVerifiedUsers = false;

    public string $customEmails = '';

    public string $subjectLine = '';

    public string $messageBody = '';

    public bool $sendCopyToMe = false;

    public function mount(): void
    {
        $this->selectedListId = MailingList::query()->orderBy('name')->value('id');
        $this->targetMailingListId = $this->selectedListId;

        if ($this->selectedListId) {
            $this->renameListName = (string) MailingList::query()->whereKey($this->selectedListId)->value('name');
        }
    }

    public function updatedRecipientScope(): void
    {
        if ($this->recipientScope !== 'mailing_list') {
            $this->targetMailingListId = null;
        }

        if ($this->recipientScope !== 'users_by_parish') {
            $this->targetParishId = null;
        }

        if ($this->recipientScope !== 'single_users') {
            $this->selectedUserIds = [];
        }

        if ($this->recipientScope !== 'custom_emails') {
            $this->customEmails = '';
        }
    }

    public function getStatsProperty(): array
    {
        $lists = MailingList::query()->count();
        $subscribersAll = MailingMail::withTrashed()->count();
        $subscribersActive = MailingMail::query()->count();
        $subscribersConfirmed = MailingMail::query()->whereNotNull('confirmed_at')->count();
        $parishioners = User::query()->where('role', 0)->count();
        $admins = User::query()->where('role', 1)->count();
        $superadmins = User::query()->where('role', 2)->count();

        return [
            ['label' => 'Listy mailingowe', 'value' => $lists],
            ['label' => 'Subskrybenci aktywni', 'value' => $subscribersActive],
            ['label' => 'Subskrybenci potwierdzeni', 'value' => $subscribersConfirmed],
            ['label' => 'Wszyscy subskrybenci (z arch.)', 'value' => $subscribersAll],
            ['label' => 'Parafianie', 'value' => $parishioners],
            ['label' => 'Administratorzy', 'value' => $admins],
            ['label' => 'Superadmini', 'value' => $superadmins],
        ];
    }

    public function getMailingListsProperty(): Collection
    {
        return MailingList::query()
            ->withCount([
                'mails as subscribers_total_count' => fn ($query) => $query->withTrashed(),
                'mails as subscribers_active_count',
                'mails as subscribers_confirmed_count' => fn ($query) => $query->whereNotNull('confirmed_at'),
            ])
            ->orderBy('name')
            ->get();
    }

    public function getSubscribersProperty(): Collection
    {
        return MailingMail::query()
            ->withTrashed()
            ->with('mailingList:id,name')
            ->when($this->selectedListId, fn ($query) => $query->where('mailing_list_id', $this->selectedListId))
            ->when($this->subscriberSearch !== '', function ($query): void {
                $search = trim(mb_strtolower($this->subscriberSearch));
                $query->whereRaw('LOWER(email) like ?', ["%{$search}%"]);
            })
            ->orderByDesc('confirmed_at')
            ->orderByDesc('created_at')
            ->limit(180)
            ->get();
    }

    public function getRecipientScopeOptionsProperty(): array
    {
        return [
            'single_users' => 'Pojedynci uzytkownicy (wskazani recznie)',
            'parishioners_all' => 'Wszyscy parafianie',
            'admins_all' => 'Wszyscy administratorzy',
            'admins_and_superadmins' => 'Administratorzy + superadmini',
            'users_by_parish' => 'Uzytkownicy wybranej parafii',
            'verified_users' => 'Tylko zweryfikowani uzytkownicy',
            'mailing_list' => 'Subskrybenci konkretnej listy mailingowej',
            'all_users' => 'Wszyscy uzytkownicy systemu',
            'custom_emails' => 'Wlasna lista emaili (CSV / nowe linie)',
        ];
    }

    public function getUserOptionsProperty(): array
    {
        return User::query()
            ->orderByRaw("COALESCE(NULLIF(full_name, ''), name, email)")
            ->limit(500)
            ->get()
            ->mapWithKeys(fn (User $user): array => [
                $user->getKey() => sprintf(
                    '%s [%s]',
                    $user->full_name ?: $user->name ?: $user->email,
                    $user->email
                ),
            ])
            ->all();
    }

    public function getParishOptionsProperty(): array
    {
        return Parish::query()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    public function getMailingListOptionsProperty(): array
    {
        return MailingList::query()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    public function getRecipientPreviewProperty(): array
    {
        $recipients = $this->resolveRecipients();

        return [
            'count' => $recipients->count(),
            'sample' => $recipients->take(8)->values()->all(),
        ];
    }

    public function createList(): void
    {
        $data = $this->validate([
            'newListName' => ['required', 'string', 'max:120'],
        ]);

        $normalizedName = trim($data['newListName']);
        $lowerName = mb_strtolower($normalizedName);
        $list = MailingList::withTrashed()
            ->whereRaw('LOWER(name) = ?', [$lowerName])
            ->first();

        if ($list) {
            if ($list->trashed()) {
                $list->restore();
                $list->update(['name' => $normalizedName]);
            }
        } else {
            $list = MailingList::query()->create([
                'name' => $normalizedName,
            ]);
        }

        $this->newListName = '';
        $this->selectedListId = (int) $list->getKey();
        $this->renameListName = (string) $list->name;

        Notification::make()
            ->success()
            ->title('Utworzono liste mailingowa.')
            ->send();
    }

    public function selectList(int $listId): void
    {
        $list = MailingList::query()->find($listId);

        if (! $list) {
            return;
        }

        $this->selectedListId = $listId;
        $this->renameListName = (string) $list->name;

        if ($this->recipientScope === 'mailing_list') {
            $this->targetMailingListId = $listId;
        }
    }

    public function saveSelectedListName(): void
    {
        if (! $this->selectedListId) {
            return;
        }

        $normalized = trim($this->renameListName);

        if ($normalized === '') {
            return;
        }

        $list = MailingList::query()->find($this->selectedListId);

        if (! $list) {
            return;
        }

        $list->update(['name' => $normalized]);

        Notification::make()
            ->success()
            ->title('Zmieniono nazwe listy.')
            ->send();
    }

    public function deleteList(int $listId): void
    {
        $list = MailingList::query()->find($listId);

        if (! $list) {
            return;
        }

        $list->mails()->delete();
        $list->delete();

        if ($this->selectedListId === $listId) {
            $this->selectedListId = MailingList::query()->orderBy('name')->value('id');
            $this->renameListName = $this->selectedListId
                ? (string) MailingList::query()->whereKey($this->selectedListId)->value('name')
                : '';
        }

        if ($this->targetMailingListId === $listId) {
            $this->targetMailingListId = null;
        }

        Notification::make()
            ->success()
            ->title('Usunieto liste oraz subskrybentow z tej listy.')
            ->send();
    }

    public function addSubscriber(): void
    {
        $data = $this->validate([
            'selectedListId' => ['required', 'integer', 'exists:mailing_lists,id'],
            'newSubscriberEmail' => ['required', 'email', 'max:255'],
            'newSubscriberConfirmed' => ['boolean'],
        ]);

        $email = mb_strtolower(trim($data['newSubscriberEmail']));

        $existing = MailingMail::query()
            ->withTrashed()
            ->where('mailing_list_id', $data['selectedListId'])
            ->where('email', $email)
            ->first();

        $attributes = [
            'confirmed_at' => $data['newSubscriberConfirmed'] ? now() : null,
            'confirmation_token' => $data['newSubscriberConfirmed'] ? null : Str::random(48),
            'unsubscribe_token' => Str::random(48),
        ];

        if ($existing) {
            if ($existing->trashed()) {
                $existing->restore();
            }

            $existing->update($attributes);
        } else {
            MailingMail::query()->create(array_merge($attributes, [
                'mailing_list_id' => $data['selectedListId'],
                'email' => $email,
            ]));
        }

        $this->newSubscriberEmail = '';
        $this->newSubscriberConfirmed = true;

        Notification::make()
            ->success()
            ->title('Subskrybent zostal dodany / zaktualizowany.')
            ->send();
    }

    public function toggleSubscriberConfirmation(int $subscriberId): void
    {
        $subscriber = MailingMail::query()->withTrashed()->find($subscriberId);

        if (! $subscriber) {
            return;
        }

        $subscriber->update([
            'confirmed_at' => $subscriber->confirmed_at ? null : now(),
            'confirmation_token' => $subscriber->confirmed_at ? Str::random(48) : null,
        ]);

        Notification::make()
            ->success()
            ->title('Zmieniono status potwierdzenia subskrybenta.')
            ->send();
    }

    public function removeSubscriber(int $subscriberId): void
    {
        $subscriber = MailingMail::query()->find($subscriberId);

        if (! $subscriber) {
            return;
        }

        $subscriber->delete();

        Notification::make()
            ->success()
            ->title('Subskrybent zostal zarchiwizowany.')
            ->send();
    }

    public function restoreSubscriber(int $subscriberId): void
    {
        $subscriber = MailingMail::query()->withTrashed()->find($subscriberId);

        if (! $subscriber || ! $subscriber->trashed()) {
            return;
        }

        $subscriber->restore();

        Notification::make()
            ->success()
            ->title('Subskrybent zostal przywrocony.')
            ->send();
    }

    public function deleteSubscriberPermanently(int $subscriberId): void
    {
        $subscriber = MailingMail::query()->withTrashed()->find($subscriberId);

        if (! $subscriber) {
            return;
        }

        $subscriber->forceDelete();

        Notification::make()
            ->success()
            ->title('Subskrybent zostal trwale usuniety.')
            ->send();
    }

    public function sendCampaign(): void
    {
        $this->validate([
            'subjectLine' => ['required', 'string', 'max:200'],
            'messageBody' => ['required', 'string', 'max:12000'],
            'recipientScope' => ['required', Rule::in(array_keys($this->recipientScopeOptions))],
        ]);

        $recipients = $this->resolveRecipients();

        if ($recipients->isEmpty()) {
            Notification::make()
                ->danger()
                ->title('Brak odbiorcow dla wybranych kryteriow.')
                ->send();

            return;
        }

        $actor = Filament::auth()->user();

        $sent = 0;
        $failed = 0;

        foreach ($recipients as $recipient) {
            try {
                Mail::to((string) $recipient['email'])->send(new CommunicationBroadcastMessage(
                    subjectLine: $this->subjectLine,
                    messageBody: $this->messageBody,
                    senderName: $actor instanceof User ? ($actor->full_name ?: $actor->name) : null,
                    senderEmail: $actor instanceof User ? $actor->email : null,
                ));
                $sent++;
            } catch (Throwable) {
                $failed++;
            }
        }

        if ($this->sendCopyToMe && $actor instanceof User && filled($actor->email)) {
            try {
                Mail::to($actor->email)->send(new CommunicationBroadcastMessage(
                    subjectLine: '[Kopia] '.$this->subjectLine,
                    messageBody: $this->messageBody,
                    senderName: $actor->full_name ?: $actor->name,
                    senderEmail: $actor->email,
                ));
            } catch (Throwable) {
                // Ignorujemy blad kopii.
            }
        }

        if ($actor instanceof User) {
            activity('superadmin-communication-center')
                ->causedBy($actor)
                ->event('communication_campaign_sent')
                ->withProperties([
                    'recipient_scope' => $this->recipientScope,
                    'recipients_total' => $recipients->count(),
                    'sent_count' => $sent,
                    'failed_count' => $failed,
                    'subject' => $this->subjectLine,
                    'message_length' => mb_strlen($this->messageBody),
                    'selected_list_id' => $this->targetMailingListId,
                    'selected_parish_id' => $this->targetParishId,
                    'selected_user_ids' => $this->selectedUserIds,
                    'only_verified_users' => $this->onlyVerifiedUsers,
                    'include_inactive_users' => $this->includeInactiveUsers,
                ])
                ->log('Superadmin wyslal kampanie email z centrum komunikacji.');
        }

        $notification = Notification::make()
            ->title('Wysylka kampanii zakonczona')
            ->body("Wyslano: {$sent} · bledy: {$failed} · odbiorcy: {$recipients->count()}");

        if ($failed > 0) {
            $notification->warning();
        } else {
            $notification->success();
        }

        $notification->send();
    }

    protected function resolveRecipients(): Collection
    {
        $scope = $this->recipientScope;

        $usersQuery = User::query()->select(['id', 'email', 'full_name', 'name', 'status', 'is_user_verified']);

        if (! $this->includeInactiveUsers) {
            $usersQuery->where('status', 'active');
        }

        if ($this->onlyVerifiedUsers) {
            $usersQuery->where('is_user_verified', true);
        }

        $rows = collect();

        if ($scope === 'single_users') {
            if ($this->selectedUserIds === []) {
                return collect();
            }

            $rows = $usersQuery
                ->whereIn('id', $this->selectedUserIds)
                ->whereNotNull('email')
                ->get()
                ->map(fn (User $user): array => [
                    'email' => (string) $user->email,
                    'label' => $user->full_name ?: $user->name ?: $user->email,
                ]);
        }

        if ($scope === 'parishioners_all') {
            $rows = $usersQuery
                ->where('role', 0)
                ->whereNotNull('email')
                ->get()
                ->map(fn (User $user): array => [
                    'email' => (string) $user->email,
                    'label' => $user->full_name ?: $user->name ?: $user->email,
                ]);
        }

        if ($scope === 'admins_all') {
            $rows = $usersQuery
                ->where('role', 1)
                ->whereNotNull('email')
                ->get()
                ->map(fn (User $user): array => [
                    'email' => (string) $user->email,
                    'label' => $user->full_name ?: $user->name ?: $user->email,
                ]);
        }

        if ($scope === 'admins_and_superadmins') {
            $rows = $usersQuery
                ->where('role', '>=', 1)
                ->whereNotNull('email')
                ->get()
                ->map(fn (User $user): array => [
                    'email' => (string) $user->email,
                    'label' => $user->full_name ?: $user->name ?: $user->email,
                ]);
        }

        if ($scope === 'users_by_parish') {
            if (! $this->targetParishId) {
                return collect();
            }

            $rows = $usersQuery
                ->where('home_parish_id', $this->targetParishId)
                ->whereNotNull('email')
                ->get()
                ->map(fn (User $user): array => [
                    'email' => (string) $user->email,
                    'label' => $user->full_name ?: $user->name ?: $user->email,
                ]);
        }

        if ($scope === 'verified_users') {
            $rows = $usersQuery
                ->where('is_user_verified', true)
                ->whereNotNull('email')
                ->get()
                ->map(fn (User $user): array => [
                    'email' => (string) $user->email,
                    'label' => $user->full_name ?: $user->name ?: $user->email,
                ]);
        }

        if ($scope === 'all_users') {
            $rows = $usersQuery
                ->whereNotNull('email')
                ->get()
                ->map(fn (User $user): array => [
                    'email' => (string) $user->email,
                    'label' => $user->full_name ?: $user->name ?: $user->email,
                ]);
        }

        if ($scope === 'mailing_list') {
            if (! $this->targetMailingListId) {
                return collect();
            }

            $rows = MailingMail::query()
                ->where('mailing_list_id', $this->targetMailingListId)
                ->when($this->mailingOnlyConfirmed, fn ($query) => $query->whereNotNull('confirmed_at'))
                ->get(['email'])
                ->map(fn (MailingMail $mail): array => [
                    'email' => (string) $mail->email,
                    'label' => (string) $mail->email,
                ]);
        }

        if ($scope === 'custom_emails') {
            $emails = collect(preg_split('/[\s,;]+/', $this->customEmails) ?: [])
                ->map(fn ($email): string => mb_strtolower(trim((string) $email)))
                ->filter(fn (string $email): bool => filter_var($email, FILTER_VALIDATE_EMAIL) !== false)
                ->unique()
                ->values();

            $rows = $emails->map(fn (string $email): array => [
                'email' => $email,
                'label' => $email,
            ]);
        }

        return $rows
            ->filter(fn (array $recipient): bool => filled($recipient['email']))
            ->unique(fn (array $recipient): string => mb_strtolower((string) $recipient['email']))
            ->values();
    }
}
