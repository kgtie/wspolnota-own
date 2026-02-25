<?php

namespace App\Filament\Admin\Resources\AnnouncementSets\Pages;

use App\Filament\Admin\Resources\AnnouncementSets\AnnouncementSetResource;
use App\Models\AnnouncementSet;
use App\Models\User;
use App\Support\Announcements\AnnouncementSetPdfExporter;
use App\Support\Announcements\AnnouncementSetSummarizer;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Throwable;

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
            $this->generateSummaryAction(),
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

                if (! $record instanceof AnnouncementSet) {
                    return null;
                }

                $exporter = app(AnnouncementSetPdfExporter::class);

                if (! $exporter->hasPrintableItems($record)) {
                    Notification::make()
                        ->warning()
                        ->title('Brak aktywnych ogloszen do wydruku.')
                        ->send();

                    return null;
                }

                return $exporter->download($record);
            });
    }

    protected function generateSummaryAction(): Action
    {
        return Action::make('generate_ai_summary')
            ->label(fn (): string => filled($this->getRecord()?->summary_ai) ? 'Generuj ponownie AI' : 'Generuj streszczenie AI')
            ->icon('heroicon-o-sparkles')
            ->color('info')
            ->visible(fn (): bool => $this->getRecord() instanceof AnnouncementSet && $this->getRecord()->status === 'published')
            ->requiresConfirmation()
            ->action(function (): void {
                $record = $this->getRecord();

                if (! $record instanceof AnnouncementSet) {
                    return;
                }

                $summarizer = app(AnnouncementSetSummarizer::class);

                if (! $summarizer->canGenerateForSet($record)) {
                    Notification::make()
                        ->warning()
                        ->title('Nie mozna wygenerowac streszczenia.')
                        ->body('Zestaw musi byc opublikowany i zawierac co najmniej jedno aktywne ogloszenie.')
                        ->send();

                    return;
                }

                try {
                    $summary = $summarizer->summarize($record);

                    $record->update([
                        'summary_ai' => $summary,
                        'summary_generated_at' => now(),
                        'summary_model' => (string) config('gemini.model'),
                    ]);

                    $record->refresh();
                    $this->refreshFormData(['summary_ai', 'summary_generated_at', 'summary_model']);

                    Notification::make()
                        ->success()
                        ->title('Wygenerowano streszczenie AI.')
                        ->send();
                } catch (Throwable $exception) {
                    report($exception);

                    Notification::make()
                        ->danger()
                        ->title('Nie udalo sie wygenerowac streszczenia AI.')
                        ->body($exception->getMessage())
                        ->send();
                }
            });
    }

    protected function getCurrentStatus(): ?string
    {
        $record = $this->getRecord();

        return $record instanceof AnnouncementSet ? $record->status : null;
    }
}
