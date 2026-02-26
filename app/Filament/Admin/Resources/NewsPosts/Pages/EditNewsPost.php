<?php

namespace App\Filament\Admin\Resources\NewsPosts\Pages;

use App\Filament\Admin\Resources\NewsPosts\NewsPostResource;
use App\Models\NewsPost;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;

class EditNewsPost extends EditRecord
{
    protected static string $resource = NewsPostResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            $this->statusAction('published', 'Opublikuj', 'heroicon-o-check-circle', 'success'),
            $this->statusAction('scheduled', 'Zaplanuj', 'heroicon-o-clock', 'warning'),
            $this->statusAction('draft', 'Ustaw jako szkic', 'heroicon-o-document-text', 'info'),
            $this->statusAction('archived', 'Archiwizuj', 'heroicon-o-archive-box', 'gray'),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $admin = Filament::auth()->user();

        unset($data['slug']);

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
                $admin = Filament::auth()->user();

                if (! $record instanceof NewsPost) {
                    return;
                }

                $payload = $this->normalizePublicationState([
                    'status' => $status,
                    'published_at' => $record->published_at,
                    'scheduled_for' => $record->scheduled_for,
                ]);
                $payload['updated_by_user_id'] = $admin instanceof User ? $admin->id : $record->updated_by_user_id;

                $record->update($payload);
                $record->refresh();
            })
            ->successNotificationTitle('Status wpisu zostal zaktualizowany.');
    }

    protected function getCurrentStatus(): ?string
    {
        $record = $this->getRecord();

        return $record instanceof NewsPost ? $record->status : null;
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
}
