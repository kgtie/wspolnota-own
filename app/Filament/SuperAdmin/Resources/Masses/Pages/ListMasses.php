<?php

namespace App\Filament\SuperAdmin\Resources\Masses\Pages;

use App\Filament\SuperAdmin\Resources\Masses\MassResource;
use App\Models\Mass;
use App\Models\Parish;
use App\Models\User;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Spatie\LaravelPdf\Enums\Format;
use Spatie\LaravelPdf\Facades\Pdf;

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
                Select::make('parish_id')
                    ->label('Parafia')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->options(fn (): array => Parish::query()
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all()),
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
                $admin = Filament::auth()->user();
                $parish = Parish::query()->find((int) ($data['parish_id'] ?? 0));

                if (! $parish) {
                    Notification::make()
                        ->warning()
                        ->title('Nie znaleziono wskazanej parafii.')
                        ->send();

                    return null;
                }

                $start = Carbon::parse((string) $data['date_from'])->startOfDay();
                $end = Carbon::parse((string) $data['date_to'])->endOfDay();

                $masses = Mass::query()
                    ->where('parish_id', $parish->getKey())
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

                $fileName = sprintf(
                    'intencje-mszalne-%s-%s.pdf',
                    $start->format('Ymd'),
                    $end->format('Ymd'),
                );

                $pdfBase64 = Pdf::view('pdf.masses.intentions-period', [
                    'parishName' => $parish->name,
                    'dateFrom' => $start,
                    'dateTo' => $end,
                    'masses' => $masses,
                    'generatedAt' => now(),
                    'kinds' => Mass::getMassKindOptions(),
                    'types' => Mass::getMassTypeOptions(),
                    'statuses' => Mass::getStatusOptions(),
                ])
                    ->format(Format::A4)
                    ->portrait()
                    ->name($fileName)
                    ->base64();

                if ($admin instanceof User) {
                    activity('admin-mass-management')
                        ->causedBy($admin)
                        ->event('mass_intentions_pdf_exported')
                        ->withProperties([
                            'parish_id' => $parish->getKey(),
                            'date_from' => $start->toIso8601String(),
                            'date_to' => $end->toIso8601String(),
                            'masses_count' => $masses->count(),
                            'file_name' => $fileName,
                        ])
                        ->log('Proboszcz wygenerowal PDF z intencjami mszalnymi.');
                }

                return response()->streamDownload(
                    static function () use ($pdfBase64): void {
                        echo base64_decode($pdfBase64);
                    },
                    $fileName,
                    [
                        'Content-Type' => 'application/pdf',
                    ],
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
