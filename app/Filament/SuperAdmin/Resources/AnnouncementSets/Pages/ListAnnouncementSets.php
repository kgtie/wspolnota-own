<?php

namespace App\Filament\SuperAdmin\Resources\AnnouncementSets\Pages;

use App\Filament\SuperAdmin\Resources\AnnouncementSets\AnnouncementSetResource;
use App\Models\AnnouncementSet;
use App\Support\Announcements\AnnouncementSetPdfExporter;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListAnnouncementSets extends ListRecords
{
    protected static string $resource = AnnouncementSetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->printSelectedAction(),
            CreateAction::make(),
        ];
    }

    protected function printSelectedAction(): Action
    {
        return Action::make('print_selected')
            ->label('Wydruk wybranego zestawu')
            ->icon('heroicon-o-printer')
            ->color('gray')
            ->modalHeading('Wydruk zestawu ogłoszeń')
            ->schema([
                Select::make('announcement_set_id')
                    ->label('Zestaw ogłoszeń')
                    ->required()
                    ->preload()
                    ->searchable()
                    ->options(function (): array {
                        return AnnouncementSet::query()
                            ->with('parish:id,name')
                            ->orderByDesc('effective_from')
                            ->limit(300)
                            ->get(['id', 'parish_id', 'title', 'effective_from'])
                            ->mapWithKeys(function (AnnouncementSet $set): array {
                                $date = $set->effective_from?->format('d.m.Y') ?? 'brak daty';
                                $parishName = $set->parish?->name ?? 'Parafia usunieta';

                                return [$set->id => "{$date} - {$set->title} ({$parishName})"];
                            })
                            ->all();
                    }),
            ])
            ->action(function (array $data) {
                $set = AnnouncementSet::query()
                    ->find($data['announcement_set_id']);

                if (! $set) {
                    Notification::make()
                        ->warning()
                        ->title('Nie znaleziono wybranego zestawu.')
                        ->send();

                    return null;
                }

                $exporter = app(AnnouncementSetPdfExporter::class);

                if (! $exporter->hasPrintableItems($set)) {
                    Notification::make()
                        ->warning()
                        ->title('Brak aktywnych ogłoszeń do wydruku.')
                        ->body('Dodaj aktywne pozycje lub zmien ich status.')
                        ->send();

                    return null;
                }

                return $exporter->download($set);
            });
    }
}
