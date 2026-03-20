<?php

namespace App\Filament\Admin\Resources\NewsPosts\Pages;

use App\Filament\Admin\Resources\NewsComments\NewsCommentResource;
use App\Filament\Admin\Resources\NewsPosts\NewsPostResource;
use App\Models\NewsPost;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Validation\ValidationException;

class EditNewsPost extends EditRecord
{
    protected static string $resource = NewsPostResource::class;

    protected string $view = 'filament.admin.resources.news-posts.pages.edit-news-post';

    protected Width|string|null $maxContentWidth = Width::Full;

    public function getTitle(): string|Htmlable
    {
        return $this->getRecord()->getDisplayTitle();
    }

    public function getSubheading(): ?string
    {
        return 'Szkic istnieje od razu. Obrazy w tresci i pozostale media mozesz dodawac bez wstepnego zapisu.';
    }

    public function getFormActionsAlignment(): string|Alignment
    {
        return Alignment::Between;
    }

    public function areFormActionsSticky(): bool
    {
        return true;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('comments')
                ->label('Komentarze')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->url(fn (): string => NewsCommentResource::getUrl('index', [
                    'filters' => [
                        'news_post_id' => ['value' => $this->getRecord()->getKey()],
                    ],
                ])),
            $this->statusAction('published', 'Opublikuj', 'heroicon-o-check-circle', 'success'),
            $this->statusAction('scheduled', 'Zaplanuj', 'heroicon-o-clock', 'warning'),
            $this->statusAction('draft', 'Ustaw jako szkic', 'heroicon-o-document-text', 'info'),
            $this->statusAction('archived', 'Archiwizuj', 'heroicon-o-archive-box', 'gray'),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    public function saveDraftSilently(): void
    {
        $status = (string) ($this->data['status'] ?? $this->getRecord()->status);

        if ($status !== 'draft') {
            $this->dispatch('news-post-autosave-mode', mode: 'manual');

            return;
        }

        if (! $this->hasAutosaveChanges()) {
            $this->dispatch('news-post-autosaved', savedAt: $this->formatAutosaveTimestamp());

            return;
        }

        $this->save(shouldRedirect: false, shouldSendSavedNotification: false);

        $this->dispatch('news-post-autosaved', savedAt: $this->formatAutosaveTimestamp());
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $admin = Filament::auth()->user();

        unset($data['slug']);
        $data = $this->normalizeDraftPayload($data);

        $this->ensurePublicationRequirements($data);

        $data = $this->normalizePublicationState($data);
        $data['updated_by_user_id'] = $admin instanceof User ? $admin->id : $data['updated_by_user_id'] ?? null;

        return $data;
    }

    protected function statusAction(string $status, string $label, string $icon, string $color): Action
    {
        return Action::make("set_status_{$status}")
            ->label($label)
            ->icon($icon)
            ->color($color)
            ->visible(fn (): bool => $this->getCurrentStatus() !== $status)
            ->requiresConfirmation()
            ->action(function () use ($status): void {
                $record = $this->getRecord();

                if (! $record instanceof NewsPost) {
                    return;
                }

                $this->data['status'] = $status;

                if ($status === 'draft') {
                    $this->data['published_at'] = null;
                    $this->data['scheduled_for'] = null;
                }

                if (($status === 'scheduled') && blank($this->data['scheduled_for'] ?? null)) {
                    $this->data['scheduled_for'] = ($record->scheduled_for ?? now()->addDay())->format('Y-m-d H:i:s');
                }

                if (in_array($status, ['published', 'archived'], true) && blank($this->data['published_at'] ?? null)) {
                    $this->data['published_at'] = ($record->published_at ?? now())->format('Y-m-d H:i:s');
                }

                try {
                    $this->save(shouldRedirect: false, shouldSendSavedNotification: false);
                    $record->refresh();

                    if ($status === 'draft') {
                        $this->dispatch('news-post-autosaved', savedAt: $this->formatAutosaveTimestamp());
                    } else {
                        $this->dispatch('news-post-autosave-mode', mode: 'manual');
                    }
                } catch (ValidationException $exception) {
                    Notification::make()
                        ->danger()
                        ->title('Wpis wymaga uzupelnienia.')
                        ->body('Uzupelnij przynajmniej tytul i tresc, zanim zmienisz status wpisu na inny niz szkic.')
                        ->send();

                    throw $exception;
                }
            })
            ->successNotificationTitle('Status wpisu zostal zaktualizowany.');
    }

    protected function hasAutosaveChanges(): bool
    {
        $record = $this->getRecord();

        return collect([
            $this->normalizeAutosaveValue($this->data['title'] ?? null) !== $this->normalizeAutosaveValue($record->title),
            $this->normalizeAutosaveValue($this->data['content'] ?? null) !== $this->normalizeAutosaveValue($record->content),
            (bool) ($this->data['is_pinned'] ?? false) !== (bool) $record->is_pinned,
            $this->normalizeAutosaveDate($this->data['scheduled_for'] ?? null) !== $this->normalizeAutosaveDate($record->scheduled_for),
            $this->normalizeAutosaveDate($this->data['published_at'] ?? null) !== $this->normalizeAutosaveDate($record->published_at),
        ])->contains(true);
    }

    protected function getCurrentStatus(): ?string
    {
        $record = $this->getRecord();

        return $record instanceof NewsPost ? $record->status : null;
    }

    protected function normalizeAutosaveValue(mixed $value): string
    {
        return NewsPost::normalizeTextColumn($value);
    }

    protected function normalizeAutosaveDate(mixed $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        return \Illuminate\Support\Carbon::parse($value)->seconds(0)->format('Y-m-d H:i:s');
    }

    protected function formatAutosaveTimestamp(): string
    {
        return now()->timezone(config('app.timezone'))->format('H:i');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function normalizeDraftPayload(array $data): array
    {
        $data['title'] = NewsPost::normalizeTextColumn($data['title'] ?? null);
        $data['content'] = NewsPost::normalizeLongTextColumn($data['content'] ?? null);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function ensurePublicationRequirements(array $data): void
    {
        $status = (string) ($data['status'] ?? 'draft');

        if ($status === 'draft') {
            return;
        }

        $errors = [];

        if ($this->normalizeAutosaveValue($data['title'] ?? null) === '') {
            $errors['data.title'] = 'Tytul jest wymagany poza trybem szkicu.';
        }

        if ($this->normalizeAutosaveValue($data['content'] ?? null) === '') {
            $errors['data.content'] = 'Tresc jest wymagana poza trybem szkicu.';
        }

        if ($status === 'scheduled' && blank($data['scheduled_for'] ?? null)) {
            $errors['data.scheduled_for'] = 'Ustaw termin publikacji, jesli wpis ma byc zaplanowany.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function normalizePublicationState(array $data): array
    {
        $status = (string) ($data['status'] ?? 'draft');

        if ($status === 'published') {
            $data['published_at'] = $data['published_at'] ?? now();
            $data['scheduled_for'] = null;
        } elseif ($status === 'scheduled') {
            $data['scheduled_for'] = $data['scheduled_for'] ?? now()->addDay();
            $data['published_at'] = null;
        } elseif ($status === 'archived') {
            $data['published_at'] = $data['published_at'] ?? now();
            $data['scheduled_for'] = null;
        } else {
            $data['published_at'] = null;
            $data['scheduled_for'] = null;
        }

        return $data;
    }

    protected function hasFullWidthFormActions(): bool
    {
        return true;
    }
}
