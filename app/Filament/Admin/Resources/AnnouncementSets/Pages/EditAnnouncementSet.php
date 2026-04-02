<?php

namespace App\Filament\Admin\Resources\AnnouncementSets\Pages;

use App\Filament\Admin\Resources\AnnouncementSets\AnnouncementSetResource;
use App\Models\AnnouncementSet;
use App\Models\User;
use App\Support\Announcements\AnnouncementSetPdfExporter;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditAnnouncementSet extends EditRecord
{
    protected static string $resource = AnnouncementSetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            $this->statusAction('published', 'Opublikuj', 'heroicon-o-check-circle', 'success'),
            $this->statusAction('draft', 'Ustaw jako szkic', 'heroicon-o-document-text', 'warning'),
            $this->statusAction('archived', 'Archiwizuj', 'heroicon-o-archive-box', 'gray'),
            $this->printAction(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $admin = Filament::auth()->user();

        if (($data['status'] ?? null) === 'published' && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        if (($data['status'] ?? null) === 'draft') {
            $data['published_at'] = null;
        }

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

                if (! $record instanceof AnnouncementSet) {
                    return;
                }

                $payload = [
                    'status' => $status,
                    'updated_by_user_id' => $admin instanceof User ? $admin->id : $record->updated_by_user_id,
                ];

                if ($status === 'published') {
                    $payload['published_at'] = $record->published_at ?? now();
                }

                if ($status === 'draft') {
                    $payload['published_at'] = null;
                }

                $record->update($payload);
                $record->refresh();

                if ($admin instanceof User) {
                    activity('admin-announcement-management')
                        ->causedBy($admin)
                        ->performedOn($record)
                        ->event('announcement_set_status_updated')
                        ->withProperties([
                            'parish_id' => Filament::getTenant()?->getKey(),
                            'announcement_set_id' => $record->getKey(),
                            'target_status' => $status,
                            'context' => 'edit_page',
                        ])
                        ->log('Proboszcz zaktualizował status zestawu ogłoszeń z poziomu edycji.');
                }
            })
            ->successNotificationTitle('Status zestawu zostal zaktualizowany.');
    }

    protected function printAction(): Action
    {
        return Action::make('print_pdf')
            ->label('Wydruk PDF')
            ->icon('heroicon-o-printer')
            ->color('gray')
            ->action(function () {
                $record = $this->getRecord();
                $admin = Filament::auth()->user();

                if (! $record instanceof AnnouncementSet) {
                    return null;
                }

                $exporter = app(AnnouncementSetPdfExporter::class);

                if (! $exporter->hasPrintableItems($record)) {
                    Notification::make()
                        ->warning()
                        ->title('Brak aktywnych ogłoszeń do wydruku.')
                        ->send();

                    return null;
                }

                if ($admin instanceof User) {
                    activity('admin-announcement-management')
                        ->causedBy($admin)
                        ->performedOn($record)
                        ->event('announcement_set_pdf_exported')
                        ->withProperties([
                            'parish_id' => Filament::getTenant()?->getKey(),
                            'announcement_set_id' => $record->getKey(),
                            'active_items_count' => $record->items()->where('is_active', true)->count(),
                            'context' => 'edit_page',
                        ])
                        ->log('Proboszcz wygenerował PDF z ogłoszeniami parafialnymi.');
                }

                return $exporter->download($record);
            });
    }

    protected function getCurrentStatus(): ?string
    {
        $record = $this->getRecord();

        return $record instanceof AnnouncementSet ? $record->status : null;
    }
}
