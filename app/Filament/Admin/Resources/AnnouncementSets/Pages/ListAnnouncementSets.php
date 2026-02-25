<?php

namespace App\Filament\Admin\Resources\AnnouncementSets\Pages;

use App\Filament\Admin\Resources\AnnouncementSets\AnnouncementSetResource;
use App\Models\AnnouncementSet;
use App\Support\Announcements\AnnouncementSetPdfExporter;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
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
            ->modalHeading('Wydruk zestawu ogloszen')
            ->schema([
                Select::make('announcement_set_id')
                    ->label('Zestaw ogloszen')
                    ->required()
                    ->preload()
                    ->searchable()
                    ->options(function (): array {
                        $tenant = Filament::getTenant();

                        if (! $tenant) {
                            return [];
                        }

                        return AnnouncementSet::query()
                            ->where('parish_id', $tenant->getKey())
                            ->orderByDesc('effective_from')
                            ->limit(300)
                            ->get(['id', 'title', 'effective_from'])
                            ->mapWithKeys(function (AnnouncementSet $set): array {
                                $date = $set->effective_from?->format('d.m.Y') ?? 'brak daty';

                                return [$set->id => "{$date} - {$set->title}"];
                            })
                            ->all();
                    }),
            ])
            ->action(function (array $data) {
                $tenant = Filament::getTenant();

                if (! $tenant) {
                    Notification::make()
                        ->warning()
                        ->title('Brak aktywnej parafii.')
                        ->send();

                    return null;
                }

                $set = AnnouncementSet::query()
                    ->where('parish_id', $tenant->getKey())
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
                        ->title('Brak aktywnych ogloszen do wydruku.')
                        ->body('Dodaj aktywne pozycje lub zmien ich status.')
                        ->send();

                    return null;
                }

                return $exporter->download($set);
            });
    }
}
