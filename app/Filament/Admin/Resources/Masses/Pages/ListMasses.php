<?php

namespace App\Filament\Admin\Resources\Masses\Pages;

use App\Filament\Admin\Resources\Masses\MassResource;
use App\Models\Mass;
use Barryvdh\DomPDF\Facade\Pdf as DomPdf;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListMasses extends ListRecords
{
    protected static string $resource = MassResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->printPdfAction(),
            CreateAction::make(),
        ];
    }

    protected function printPdfAction(): Action
    {
        return Action::make('print_pdf')
            ->label('Wydruk PDF')
            ->icon('heroicon-o-printer')
            ->color('gray')
            ->modalHeading('Wydruk intencji mszalnych')
            ->modalDescription('Wybierz zakres dat, dla ktorego chcesz wygenerowac zestawienie do wydruku.')
            ->schema([
                DatePicker::make('date_from')
                    ->label('Data od')
                    ->required()
                    ->native(false)
                    ->default($this->defaultDateFrom()),
                DatePicker::make('date_to')
                    ->label('Data do')
                    ->required()
                    ->native(false)
                    ->default($this->defaultDateTo())
                    ->rule('after_or_equal:date_from'),
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

                $start = Carbon::parse((string) $data['date_from'])->startOfDay();
                $end = Carbon::parse((string) $data['date_to'])->endOfDay();

                $masses = Mass::query()
                    ->where('parish_id', $tenant->getKey())
                    ->whereBetween('celebration_at', [$start, $end])
                    ->orderBy('celebration_at')
                    ->get([
                        'celebration_at',
                        'intention_title',
                        'mass_kind',
                        'mass_type',
                        'celebrant_name',
                        'stipendium_amount',
                        'status',
                    ]);

                if ($masses->isEmpty()) {
                    Notification::make()
                        ->warning()
                        ->title('Brak mszy w wybranym zakresie.')
                        ->body('Zmien zakres dat i sproboj ponownie.')
                        ->send();

                    return null;
                }

                $pdf = DomPdf::loadView('pdf.masses.intentions-period', [
                    'parishName' => $tenant->name,
                    'dateFrom' => $start,
                    'dateTo' => $end,
                    'masses' => $masses,
                    'generatedAt' => now(),
                    'kinds' => Mass::getMassKindOptions(),
                    'types' => Mass::getMassTypeOptions(),
                    'statuses' => Mass::getStatusOptions(),
                ])->setPaper('a4', 'portrait');

                $fileName = sprintf(
                    'intencje-mszalne-%s-%s.pdf',
                    $start->format('Ymd'),
                    $end->format('Ymd'),
                );

                return response()->streamDownload(
                    static fn (): string => print($pdf->output()),
                    $fileName,
                );
            });
    }

    protected function defaultDateFrom(): string
    {
        return now()->startOfWeek(Carbon::SUNDAY)->toDateString();
    }

    protected function defaultDateTo(): string
    {
        return now()->startOfWeek(Carbon::SUNDAY)->addWeek()->toDateString();
    }
}
