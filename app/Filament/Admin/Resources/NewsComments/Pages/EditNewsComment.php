<?php

namespace App\Filament\Admin\Resources\NewsComments\Pages;

use App\Filament\Admin\Resources\NewsComments\NewsCommentResource;
use App\Filament\Admin\Resources\NewsPosts\NewsPostResource;
use App\Models\NewsComment;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;
use Illuminate\Validation\ValidationException;

class EditNewsComment extends EditRecord
{
    protected static string $resource = NewsCommentResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('edit_post')
                ->label('Przejdz do wpisu')
                ->icon('heroicon-o-newspaper')
                ->url(fn (): ?string => $this->getRecord()->newsPost
                    ? NewsPostResource::getUrl('edit', ['record' => $this->getRecord()->newsPost])
                    : null),
            Action::make('comments_for_post')
                ->label('Wszystkie komentarze wpisu')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->url(fn (): string => NewsCommentResource::getUrl('index', [
                    'filters' => [
                        'news_post_id' => ['value' => $this->getRecord()->news_post_id],
                    ],
                ])),
            $this->replyAction(),
            $this->hideAction(),
        ];
    }

    protected function getFormActions(): array
    {
        return [
            $this->getCancelFormAction(),
        ];
    }

    protected function replyAction(): Action
    {
        return Action::make('reply')
            ->label('Odpowiedz')
            ->icon('heroicon-o-arrow-uturn-left')
            ->color('info')
            ->visible(fn (): bool => ! $this->getRecord()->is_hidden && $this->getRecord()->canReceiveReplies())
            ->schema([
                Textarea::make('body')
                    ->label('Tresci odpowiedzi')
                    ->required()
                    ->rows(6)
                    ->maxLength(2000),
            ])
            ->action(function (array $data): void {
                $record = $this->getRecord();

                if ($record->is_hidden) {
                    throw ValidationException::withMessages([
                        'body' => 'Nie mozna odpowiadac na ukryty komentarz.',
                    ]);
                }

                if (! $record->canReceiveReplies()) {
                    throw ValidationException::withMessages([
                        'body' => 'Ten komentarz osiagnal juz maksymalna glebokosc watku.',
                    ]);
                }

                $author = Filament::auth()->user();

                NewsComment::query()->create([
                    'news_post_id' => $record->news_post_id,
                    'user_id' => $author instanceof User ? $author->getKey() : null,
                    'parent_id' => $record->getKey(),
                    'depth' => NewsComment::resolveDepth($record),
                    'body' => (string) ($data['body'] ?? ''),
                ]);

                Notification::make()
                    ->success()
                    ->title('Dodano odpowiedz na komentarz.')
                    ->send();
            });
    }

    protected function hideAction(): Action
    {
        return Action::make('hide_comment')
            ->label('Ukryj komentarz')
            ->icon('heroicon-o-eye-slash')
            ->color('warning')
            ->visible(fn (): bool => ! $this->getRecord()->is_hidden)
            ->requiresConfirmation()
            ->action(function (): void {
                $actor = Filament::auth()->user();

                $this->getRecord()->markHidden($actor instanceof User ? $actor : null);

                Notification::make()
                    ->success()
                    ->title('Komentarz zostal ukryty.')
                    ->send();

                $this->refreshFormData(['is_hidden']);
            });
    }
}
