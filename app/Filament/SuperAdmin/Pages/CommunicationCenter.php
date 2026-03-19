<?php

namespace App\Filament\SuperAdmin\Pages;

use App\Jobs\DispatchCommunicationCampaignJob;
use App\Mail\CommunicationBroadcastMessage;
use App\Models\CommunicationCampaign;
use App\Models\MailingList;
use App\Models\MailingMail;
use App\Models\Parish;
use App\Models\User;
use App\Support\Mail\CampaignContentRenderer;
use App\Support\Mail\EmailComposer;
use App\Support\SuperAdmin\CommunicationAudienceResolver;
use App\Support\SuperAdmin\InstantCommunicationService;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;

class CommunicationCenter extends Page
{
    protected static ?string $title = 'Centrum komunikacji';

    protected static ?string $navigationLabel = 'Centrum komunikacji';

    protected static string | \BackedEnum | null $navigationIcon = null;

    protected static string | \UnitEnum | null $navigationGroup = 'Komunikacja i kampanie';

    protected static ?int $navigationSort = 1;

    protected ?string $subheading = 'Listy mailingowe, subskrybenci, szkice kampanii i wysylka email / push w jednym miejscu.';

    protected string $view = 'filament.superadmin.pages.communication-center';

    public string $newListName = '';

    public ?int $selectedListId = null;

    public string $renameListName = '';

    public string $subscriberSearch = '';

    public string $newSubscriberEmail = '';

    public bool $newSubscriberConfirmed = true;

    public ?int $loadedCampaignId = null;

    public string $campaignName = '';

    public string $recipientScope = 'single_users';

    public array $selectedUserIds = [];

    public ?int $targetParishId = null;

    public ?int $targetMailingListId = null;

    public bool $mailingOnlyConfirmed = true;

    public bool $includeInactiveUsers = false;

    public bool $onlyVerifiedUsers = false;

    public bool $onlyEmailVerifiedUsers = false;

    public bool $onlyUsersWithPushDevices = false;

    public bool $respectEmailPreferences = false;

    public string $emailPreferenceTopic = 'announcements';

    public string $customEmails = '';

    public ?int $brandingParishId = null;

    public string $subjectLine = '';

    public string $preheader = '';

    public string $messageBody = '';

    public ?string $campaignContentHtml = '';

    public string $heroImageUrl = '';

    public string $ctaLabel = '';

    public string $ctaUrl = '';

    public string $replyToEmail = '';

    public string $replyToName = '';

    public bool $sendCopyToMe = false;

    public ?string $scheduledFor = null;

    public string $testRecipientEmail = '';

    public static function getNavigationBadge(): ?string
    {
        $count = MailingMail::query()->whereNotNull('confirmed_at')->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'info';
    }

    public function mount(): void
    {
        $this->selectedListId = MailingList::query()->orderBy('name')->value('id');
        $this->targetMailingListId = $this->selectedListId;

        if ($this->selectedListId) {
            $this->renameListName = (string) MailingList::query()->whereKey($this->selectedListId)->value('name');
        }

        $actor = Filament::auth()->user();

        if ($actor instanceof User) {
            $this->replyToEmail = (string) ($actor->email ?? '');
            $this->replyToName = (string) ($actor->full_name ?: $actor->name ?: '');
            $this->testRecipientEmail = (string) ($actor->email ?? '');
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
        return [
            ['label' => 'Listy mailingowe', 'value' => MailingList::query()->count()],
            ['label' => 'Subskrybenci aktywni', 'value' => MailingMail::query()->count()],
            ['label' => 'Subskrybenci potwierdzeni', 'value' => MailingMail::query()->whereNotNull('confirmed_at')->count()],
            ['label' => 'Kampanie email', 'value' => CommunicationCampaign::query()->where('channel', 'email')->count()],
            ['label' => 'Szablony kampanii', 'value' => CommunicationCampaign::query()->where('is_template', true)->count()],
            ['label' => 'Parafianie', 'value' => User::query()->where('role', 0)->count()],
            ['label' => 'Administratorzy', 'value' => User::query()->where('role', 1)->count()],
            ['label' => 'Superadmini', 'value' => User::query()->where('role', 2)->count()],
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
            'single_users' => 'Pojedynci uzytkownicy',
            'parishioners_all' => 'Wszyscy parafianie',
            'admins_all' => 'Wszyscy administratorzy',
            'admins_and_superadmins' => 'Administratorzy + superadmini',
            'users_by_parish' => 'Uzytkownicy wybranej parafii',
            'verified_users' => 'Tylko zweryfikowani uzytkownicy',
            'mailing_list' => 'Subskrybenci listy mailingowej',
            'users_with_push_devices' => 'Uzytkownicy z aktywnymi urzadzeniami push',
            'email_topic_opt_in' => 'Uzytkownicy z opt-in email dla kategorii',
            'all_users' => 'Wszyscy uzytkownicy systemu',
            'custom_emails' => 'Wlasna lista emaili',
        ];
    }

    public function getEmailTopicOptionsProperty(): array
    {
        return [
            'announcements' => 'Ogloszenia',
            'news' => 'Aktualnosci',
            'mass_reminders' => 'Przypomnienia o mszach',
            'office_messages' => 'Kancelaria online',
            'parish_approval_status' => 'Status zatwierdzenia parafialnego',
            'auth_security' => 'Bezpieczenstwo konta',
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

    public function getRecentCampaignsProperty(): Collection
    {
        return CommunicationCampaign::query()
            ->where('channel', 'email')
            ->where('is_template', false)
            ->latest('updated_at')
            ->limit(8)
            ->get();
    }

    public function getTemplateCampaignsProperty(): Collection
    {
        return CommunicationCampaign::query()
            ->where('channel', 'email')
            ->where('is_template', true)
            ->latest('updated_at')
            ->limit(6)
            ->get();
    }

    public function getRecipientPreviewProperty(): array
    {
        $recipients = $this->resolveRecipients();

        return [
            'count' => $recipients->count(),
            'sample' => $recipients->take(8)->values()->all(),
        ];
    }

    public function getCampaignInlineImageUploadUrlProperty(): ?string
    {
        if (! $this->loadedCampaignId) {
            return null;
        }

        return route('admin.communication-campaigns.inline-image', ['campaign' => $this->loadedCampaignId]);
    }

    public function getCampaignPreviewHtmlProperty(): string
    {
        $payload = $this->buildCampaignPayload();
        $parish = $this->brandingParishId ? Parish::query()->find($this->brandingParishId) : null;
        $renderer = app(CampaignContentRenderer::class);

        $viewPayload = app(EmailComposer::class)->composeView(
            htmlBodyView: 'mail.html.communication.broadcast-message',
            textBodyView: 'mail.text.communication.broadcast-message',
            bodyData: [
                'subjectLine' => $payload['subject_line'] !== '' ? $payload['subject_line'] : 'Podglad kampanii',
                'messageBody' => $payload['message_body'],
                'senderName' => $this->replyToName !== '' ? $this->replyToName : 'Zespol Wspolnoty',
                'senderEmail' => $this->replyToEmail,
                'contentHtml' => $renderer->renderForEmail($payload['campaign_content_html']),
                'contentText' => $renderer->toPlainText($payload['campaign_content_html']),
                'ctaLabel' => $payload['cta_label'],
                'ctaUrl' => $payload['cta_url'],
                'heroImageUrl' => $payload['hero_image_url'],
                'campaignName' => $payload['campaign_name'],
            ],
            parish: $parish,
            context: [
                'category_label' => $payload['campaign_name'] !== '' ? $payload['campaign_name'] : 'Kampania email',
                'preheader' => $payload['preheader'],
                'mobile_note_variant' => $parish ? 'parish' : 'campaign',
                'footer_note' => 'Podglad kampanii email przygotowanej w centrum komunikacji Wspolnoty.',
            ],
        );

        return view('mail.framework.html', $viewPayload)->render();
    }

    public function getCampaignPreviewTextProperty(): string
    {
        $payload = $this->buildCampaignPayload();
        $parish = $this->brandingParishId ? Parish::query()->find($this->brandingParishId) : null;
        $renderer = app(CampaignContentRenderer::class);

        $viewPayload = app(EmailComposer::class)->composeView(
            htmlBodyView: 'mail.html.communication.broadcast-message',
            textBodyView: 'mail.text.communication.broadcast-message',
            bodyData: [
                'subjectLine' => $payload['subject_line'] !== '' ? $payload['subject_line'] : 'Podglad kampanii',
                'messageBody' => $payload['message_body'],
                'senderName' => $this->replyToName !== '' ? $this->replyToName : 'Zespol Wspolnoty',
                'senderEmail' => $this->replyToEmail,
                'contentHtml' => $renderer->renderForEmail($payload['campaign_content_html']),
                'contentText' => $renderer->toPlainText($payload['campaign_content_html']),
                'ctaLabel' => $payload['cta_label'],
                'ctaUrl' => $payload['cta_url'],
                'heroImageUrl' => $payload['hero_image_url'],
                'campaignName' => $payload['campaign_name'],
            ],
            parish: $parish,
            context: [
                'category_label' => $payload['campaign_name'] !== '' ? $payload['campaign_name'] : 'Kampania email',
                'preheader' => $payload['preheader'],
                'mobile_note_variant' => $parish ? 'parish' : 'campaign',
                'footer_note' => 'Podglad kampanii email przygotowanej w centrum komunikacji Wspolnoty.',
            ],
        );

        return trim(view('mail.framework.text', $viewPayload)->render());
    }

    public function getImageEditorHintProperty(): string
    {
        return $this->loadedCampaignId
            ? 'Mozesz osadzac obrazy bezposrednio w tresci kampanii.'
            : 'Najpierw zapisz kampanie jako szkic, aby odblokowac osadzanie obrazow w edytorze.';
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

    public function newCampaign(): void
    {
        $actor = Filament::auth()->user();

        $this->loadedCampaignId = null;
        $this->campaignName = '';
        $this->recipientScope = 'single_users';
        $this->selectedUserIds = [];
        $this->targetParishId = null;
        $this->targetMailingListId = $this->selectedListId;
        $this->mailingOnlyConfirmed = true;
        $this->includeInactiveUsers = false;
        $this->onlyVerifiedUsers = false;
        $this->onlyEmailVerifiedUsers = false;
        $this->onlyUsersWithPushDevices = false;
        $this->respectEmailPreferences = false;
        $this->emailPreferenceTopic = 'announcements';
        $this->customEmails = '';
        $this->brandingParishId = null;
        $this->subjectLine = '';
        $this->preheader = '';
        $this->messageBody = '';
        $this->campaignContentHtml = '';
        $this->heroImageUrl = '';
        $this->ctaLabel = '';
        $this->ctaUrl = '';
        $this->replyToEmail = $actor instanceof User ? (string) ($actor->email ?? '') : '';
        $this->replyToName = $actor instanceof User ? (string) ($actor->full_name ?: $actor->name ?: '') : '';
        $this->sendCopyToMe = false;
        $this->scheduledFor = null;

        Notification::make()
            ->success()
            ->title('Wyzerowano formularz kampanii.')
            ->send();
    }

    public function loadCampaign(int $campaignId): void
    {
        $campaign = CommunicationCampaign::query()->find($campaignId);

        if (! $campaign) {
            return;
        }

        $this->applyCampaignToState($campaign);

        Notification::make()
            ->success()
            ->title('Wczytano kampanie.')
            ->send();
    }

    public function duplicateCampaign(int $campaignId): void
    {
        $campaign = CommunicationCampaign::query()->find($campaignId);

        if (! $campaign) {
            return;
        }

        $this->applyCampaignToState($campaign);
        $this->loadedCampaignId = null;
        $this->campaignName = trim($this->campaignName.' Kopia');

        Notification::make()
            ->success()
            ->title('Skopiowano ustawienia kampanii do formularza.')
            ->body('To jest nowa robocza wersja. Zapisz ja jako szkic lub szablon.')
            ->send();
    }

    public function saveDraft(): void
    {
        $this->validateDraftRules();

        if (! $this->campaignHasContent()) {
            Notification::make()
                ->danger()
                ->title('Dodaj tresc kampanii.')
                ->body('Wypelnij edytor kampanii lub pole skrotu tekstowego.')
                ->send();

            return;
        }

        $campaign = $this->persistCampaign(
            status: CommunicationCampaign::STATUS_DRAFT,
            isTemplate: false,
        );

        Notification::make()
            ->success()
            ->title('Zapisano szkic kampanii.')
            ->body('ID szkicu: '.$campaign->getKey())
            ->send();
    }

    public function saveTemplate(): void
    {
        $this->validateDraftRules(requireName: true);

        if (! $this->campaignHasContent()) {
            Notification::make()
                ->danger()
                ->title('Dodaj tresc szablonu.')
                ->send();

            return;
        }

        $campaign = $this->persistCampaign(
            status: CommunicationCampaign::STATUS_TEMPLATE,
            isTemplate: true,
        );

        Notification::make()
            ->success()
            ->title('Zapisano szablon kampanii.')
            ->body('ID szablonu: '.$campaign->getKey())
            ->send();
    }

    public function sendTestCampaign(): void
    {
        $this->validateDraftRules(requireSubject: true);
        $this->validate([
            'testRecipientEmail' => ['required', 'email', 'max:255'],
        ]);

        if (! $this->campaignHasContent()) {
            Notification::make()
                ->danger()
                ->title('Dodaj tresc kampanii.')
                ->send();

            return;
        }

        $campaign = $this->persistCampaign(
            status: CommunicationCampaign::STATUS_DRAFT,
            isTemplate: false,
            markTestSent: true,
        );

        $parish = $this->brandingParishId ? Parish::query()->find($this->brandingParishId) : null;
        $actor = Filament::auth()->user();
        $payload = $this->buildCampaignPayload();

        Mail::to($this->testRecipientEmail)->queue(new CommunicationBroadcastMessage(
            subjectLine: '[TEST] '.$payload['subject_line'],
            messageBody: $payload['message_body'],
            senderName: $actor instanceof User ? ($actor->full_name ?: $actor->name) : null,
            senderEmail: $actor instanceof User ? $actor->email : null,
            preheader: $payload['preheader'],
            contentHtml: $payload['campaign_content_html'],
            ctaLabel: $payload['cta_label'],
            ctaUrl: $payload['cta_url'],
            parish: $parish,
            heroImageUrl: $payload['hero_image_url'],
            campaignName: $payload['campaign_name'],
            replyToEmail: $payload['reply_to_email'],
            replyToName: $payload['reply_to_name'],
        ));

        Notification::make()
            ->success()
            ->title('Zakolejkowano test kampanii.')
            ->body('Szkic '.$campaign->getKey().' -> '.$this->testRecipientEmail)
            ->send();
    }

    public function sendCampaign(): void
    {
        $this->validateSendRules();

        if (! $this->campaignHasContent()) {
            Notification::make()
                ->danger()
                ->title('Dodaj tresc kampanii.')
                ->send();

            return;
        }

        $recipients = $this->resolveRecipients();

        if ($recipients->isEmpty()) {
            Notification::make()
                ->danger()
                ->title('Brak odbiorcow dla wybranych kryteriow.')
                ->send();

            return;
        }

        $scheduledAt = $this->parseScheduledFor();
        $status = $scheduledAt && $scheduledAt->isFuture()
            ? CommunicationCampaign::STATUS_SCHEDULED
            : CommunicationCampaign::STATUS_DISPATCHING;

        $campaign = $this->persistCampaign(
            status: $status,
            isTemplate: false,
        );

        $dispatch = DispatchCommunicationCampaignJob::dispatch((int) $campaign->getKey());

        if ($status === CommunicationCampaign::STATUS_SCHEDULED && $scheduledAt) {
            $dispatch->delay($scheduledAt);
        }

        $actor = Filament::auth()->user();

        if ($actor instanceof User) {
            activity('superadmin-communication-center')
                ->causedBy($actor)
                ->event('communication_campaign_saved_for_dispatch')
                ->withProperties([
                    'campaign_id' => $campaign->getKey(),
                    'recipient_scope' => $this->recipientScope,
                    'recipients_total_preview' => $recipients->count(),
                    'subject' => $this->subjectLine,
                    'scheduled_for' => $scheduledAt?->toIso8601String(),
                ])
                ->log('Superadmin przygotowal kampanie email do wysylki.');
        }

        Notification::make()
            ->success()
            ->title($status === CommunicationCampaign::STATUS_SCHEDULED ? 'Zaplanowano kampanie email' : 'Zakolejkowano wysylke kampanii')
            ->body($status === CommunicationCampaign::STATUS_SCHEDULED
                ? 'Kampania '.$campaign->getKey().' zostanie uruchomiona o '.$scheduledAt?->format('d.m.Y H:i')
                : 'Kampania '.$campaign->getKey().' trafila do joba dispatchujacego.')
            ->send();
    }

    public function sendPushCampaign(InstantCommunicationService $service, CommunicationAudienceResolver $resolver, CampaignContentRenderer $renderer): void
    {
        $this->validate([
            'subjectLine' => ['required', 'string', 'max:120'],
            'recipientScope' => ['required', Rule::in(array_keys($this->recipientScopeOptions))],
        ]);

        $body = $this->resolvedPushBody($renderer);

        if ($body === '') {
            Notification::make()
                ->danger()
                ->title('Brak tresci kampanii push.')
                ->body('Uzupelnij edytor kampanii lub skrot tekstowy.')
                ->send();

            return;
        }

        $users = $resolver->resolvePushRecipients($this->buildCampaignPayload());

        if ($users->isEmpty()) {
            Notification::make()
                ->danger()
                ->title('Brak odbiorcow push dla wybranych kryteriow.')
                ->body('Push wymaga rzeczywistych kont uzytkownikow z aktywnymi urzadzeniami.')
                ->send();

            return;
        }

        $result = $service->queuePushToUsers(
            users: $users,
            title: $this->subjectLine,
            body: $body,
            type: 'MANUAL_MESSAGE',
            routingData: [
                'source' => 'communication_center',
            ],
        );

        Notification::make()
            ->success()
            ->title('Zakolejkowano kampanie push')
            ->body("Uzytkownicy: {$result['users']} · urzadzenia: {$result['devices']} · skipped: {$result['skipped']}")
            ->send();
    }

    protected function resolveRecipients(): Collection
    {
        return app(CommunicationAudienceResolver::class)->resolveEmailRecipients($this->buildCampaignPayload());
    }

    private function buildCampaignPayload(): array
    {
        $renderer = app(CampaignContentRenderer::class);
        $contentHtml = $renderer->sanitize($this->campaignContentHtml);
        $contentText = $renderer->toPlainText($contentHtml);
        $messageBody = trim($this->messageBody) !== ''
            ? trim($this->messageBody)
            : (string) Str::of($contentText)->trim()->limit(3500, '');

        return [
            'campaign_name' => trim($this->campaignName),
            'recipient_scope' => $this->recipientScope,
            'selected_user_ids' => array_values($this->selectedUserIds),
            'target_parish_id' => $this->targetParishId,
            'target_mailing_list_id' => $this->targetMailingListId,
            'mailing_only_confirmed' => $this->mailingOnlyConfirmed,
            'include_inactive_users' => $this->includeInactiveUsers,
            'only_verified_users' => $this->onlyVerifiedUsers,
            'only_email_verified_users' => $this->onlyEmailVerifiedUsers,
            'only_users_with_push_devices' => $this->onlyUsersWithPushDevices,
            'respect_email_preferences' => $this->respectEmailPreferences,
            'email_preference_topic' => $this->emailPreferenceTopic,
            'custom_emails' => trim($this->customEmails),
            'subject_line' => trim($this->subjectLine),
            'preheader' => trim($this->preheader),
            'message_body' => $messageBody,
            'campaign_content_html' => $contentHtml,
            'hero_image_url' => trim($this->heroImageUrl),
            'cta_label' => trim($this->ctaLabel),
            'cta_url' => trim($this->ctaUrl),
            'reply_to_email' => trim($this->replyToEmail),
            'reply_to_name' => trim($this->replyToName),
            'send_copy_to_me' => $this->sendCopyToMe,
            'branding_parish_id' => $this->brandingParishId,
        ];
    }

    private function validateDraftRules(bool $requireName = false, bool $requireSubject = false): void
    {
        $this->validate([
            'campaignName' => [$requireName ? 'required' : 'nullable', 'string', 'max:160'],
            'subjectLine' => [$requireSubject ? 'required' : 'nullable', 'string', 'max:200'],
            'preheader' => ['nullable', 'string', 'max:255'],
            'messageBody' => ['nullable', 'string', 'max:12000'],
            'campaignContentHtml' => ['nullable', 'string', 'max:65000'],
            'heroImageUrl' => ['nullable', 'url', 'max:2048'],
            'ctaLabel' => ['nullable', 'string', 'max:80'],
            'ctaUrl' => ['nullable', 'url', 'max:2048'],
            'replyToEmail' => ['nullable', 'email', 'max:255'],
            'replyToName' => ['nullable', 'string', 'max:120'],
            'scheduledFor' => ['nullable', 'string', 'max:40'],
        ]);
    }

    private function validateSendRules(): void
    {
        $this->validateDraftRules(requireName: true, requireSubject: true);

        $this->validate([
            'recipientScope' => ['required', Rule::in(array_keys($this->recipientScopeOptions))],
        ]);
    }

    private function persistCampaign(string $status, bool $isTemplate, bool $markTestSent = false): CommunicationCampaign
    {
        $payload = $this->buildCampaignPayload();
        $actor = Filament::auth()->user();
        $scheduledAt = $this->parseScheduledFor();

        $campaign = $this->loadedCampaignId
            ? CommunicationCampaign::query()->find($this->loadedCampaignId)
            : null;

        if (! $campaign) {
            $campaign = new CommunicationCampaign();
            $campaign->channel = 'email';
            $campaign->created_by_user_id = $actor instanceof User ? (int) $actor->getKey() : null;
        }

        $campaign->fill([
            'name' => $payload['campaign_name'] !== '' ? $payload['campaign_name'] : 'Kampania bez nazwy',
            'channel' => 'email',
            'is_template' => $isTemplate,
            'status' => $status,
            'parish_id' => $this->brandingParishId,
            'subject_line' => $payload['subject_line'],
            'preheader' => $payload['preheader'],
            'builder_payload' => $payload,
            'scheduled_for' => $scheduledAt,
            'last_error' => null,
        ]);

        if ($markTestSent) {
            $campaign->last_test_sent_at = now();
        }

        $campaign->save();

        $this->loadedCampaignId = (int) $campaign->getKey();

        return $campaign;
    }

    private function applyCampaignToState(CommunicationCampaign $campaign): void
    {
        $payload = is_array($campaign->builder_payload) ? $campaign->builder_payload : [];

        $this->loadedCampaignId = (int) $campaign->getKey();
        $this->campaignName = (string) ($payload['campaign_name'] ?? $campaign->name ?? '');
        $this->recipientScope = (string) ($payload['recipient_scope'] ?? 'single_users');
        $this->selectedUserIds = array_values((array) ($payload['selected_user_ids'] ?? []));
        $this->targetParishId = is_numeric($payload['target_parish_id'] ?? null) ? (int) $payload['target_parish_id'] : null;
        $this->targetMailingListId = is_numeric($payload['target_mailing_list_id'] ?? null) ? (int) $payload['target_mailing_list_id'] : null;
        $this->mailingOnlyConfirmed = (bool) ($payload['mailing_only_confirmed'] ?? true);
        $this->includeInactiveUsers = (bool) ($payload['include_inactive_users'] ?? false);
        $this->onlyVerifiedUsers = (bool) ($payload['only_verified_users'] ?? false);
        $this->onlyEmailVerifiedUsers = (bool) ($payload['only_email_verified_users'] ?? false);
        $this->onlyUsersWithPushDevices = (bool) ($payload['only_users_with_push_devices'] ?? false);
        $this->respectEmailPreferences = (bool) ($payload['respect_email_preferences'] ?? false);
        $this->emailPreferenceTopic = (string) ($payload['email_preference_topic'] ?? 'announcements');
        $this->customEmails = (string) ($payload['custom_emails'] ?? '');
        $this->brandingParishId = is_numeric($payload['branding_parish_id'] ?? $campaign->parish_id) ? (int) ($payload['branding_parish_id'] ?? $campaign->parish_id) : null;
        $this->subjectLine = (string) ($payload['subject_line'] ?? $campaign->subject_line ?? '');
        $this->preheader = (string) ($payload['preheader'] ?? $campaign->preheader ?? '');
        $this->messageBody = (string) ($payload['message_body'] ?? '');
        $this->campaignContentHtml = (string) ($payload['campaign_content_html'] ?? '');
        $this->heroImageUrl = (string) ($payload['hero_image_url'] ?? '');
        $this->ctaLabel = (string) ($payload['cta_label'] ?? '');
        $this->ctaUrl = (string) ($payload['cta_url'] ?? '');
        $this->replyToEmail = (string) ($payload['reply_to_email'] ?? '');
        $this->replyToName = (string) ($payload['reply_to_name'] ?? '');
        $this->sendCopyToMe = (bool) ($payload['send_copy_to_me'] ?? false);
        $this->scheduledFor = $campaign->scheduled_for?->format('Y-m-d\TH:i');
    }

    private function campaignHasContent(): bool
    {
        $renderer = app(CampaignContentRenderer::class);

        return trim($this->messageBody) !== ''
            || $renderer->toPlainText($this->campaignContentHtml) !== '';
    }

    private function resolvedPushBody(CampaignContentRenderer $renderer): string
    {
        $plainText = trim($this->messageBody);

        if ($plainText === '') {
            $plainText = $renderer->toPlainText($this->campaignContentHtml);
        }

        return (string) Str::of($plainText)->squish()->limit(220, '');
    }

    private function parseScheduledFor(): ?Carbon
    {
        $value = trim((string) $this->scheduledFor);

        if ($value === '') {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d\TH:i', $value, config('app.timezone'));
        } catch (Throwable) {
            try {
                return Carbon::parse($value, config('app.timezone'));
            } catch (Throwable) {
                return null;
            }
        }
    }

    public function campaignStatusLabel(string $status): string
    {
        return match ($status) {
            CommunicationCampaign::STATUS_DRAFT => 'szkic',
            CommunicationCampaign::STATUS_TEMPLATE => 'szablon',
            CommunicationCampaign::STATUS_SCHEDULED => 'zaplanowana',
            CommunicationCampaign::STATUS_DISPATCHING => 'w przygotowaniu',
            CommunicationCampaign::STATUS_QUEUED => 'zakolejkowana',
            CommunicationCampaign::STATUS_FAILED => 'blad',
            default => $status,
        };
    }

    public function campaignStatusColor(string $status): string
    {
        return match ($status) {
            CommunicationCampaign::STATUS_DRAFT => 'gray',
            CommunicationCampaign::STATUS_TEMPLATE => 'info',
            CommunicationCampaign::STATUS_SCHEDULED => 'warning',
            CommunicationCampaign::STATUS_DISPATCHING => 'primary',
            CommunicationCampaign::STATUS_QUEUED => 'success',
            CommunicationCampaign::STATUS_FAILED => 'danger',
            default => 'gray',
        };
    }
}
