<?php

namespace App\Filament\SuperAdmin\Resources\ActivityLogs\Pages;

use App\Filament\SuperAdmin\Resources\ActivityLogs\ActivityLogResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\ViewRecord;
use Spatie\Activitylog\Models\Activity;

/**
 * Szczegoly wpisu audytowego z szybkim przejsciem do sprawcy i obiektu,
 * aby operator mogl od razu wejsc w rekord zrodlowy.
 */
class ViewActivityLog extends ViewRecord
{
    protected static string $resource = ActivityLogResource::class;

    protected function getHeaderActions(): array
    {
        /** @var Activity $record */
        $record = $this->getRecord();

        return [
            Action::make('open_subject')
                ->label('Otworz obiekt')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->visible(filled(ActivityLogResource::relationUrl(
                    $record->subject,
                    $record->subject_type,
                    $record->subject_id,
                )))
                ->url(ActivityLogResource::relationUrl(
                    $record->subject,
                    $record->subject_type,
                    $record->subject_id,
                )),

            Action::make('open_causer')
                ->label('Otworz sprawce')
                ->icon('heroicon-o-user')
                ->visible(filled(ActivityLogResource::relationUrl(
                    $record->causer,
                    $record->causer_type,
                    $record->causer_id,
                )))
                ->url(ActivityLogResource::relationUrl(
                    $record->causer,
                    $record->causer_type,
                    $record->causer_id,
                )),

            DeleteAction::make(),
        ];
    }
}
