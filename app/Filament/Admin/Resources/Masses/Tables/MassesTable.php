<?php

namespace App\Filament\Admin\Resources\Masses\Tables;

use App\Models\Mass;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Services\MassPdfService;
use Filament\Facades\Filament;
use Illuminate\Support\Carbon;

class MassesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('start_time')
                    ->label('Data')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),

                TextColumn::make('location')->label('Miejsce')->searchable()->toggleable(),

                TextColumn::make('type')
                    ->label('Rodzaj')
                    ->formatStateUsing(fn (string $state, Mass $record) => $record->getTypeLabel())
                    ->toggleable(),

                TextColumn::make('rite')
                    ->label('Ryt')
                    ->formatStateUsing(fn (string $state, Mass $record) => $record->getRiteLabel())
                    ->toggleable(),

                TextColumn::make('attendees_count')
                    ->label('Zapisani')
                    ->sortable(),

                TextColumn::make('stipend')
                    ->label('Stypendium')
                    ->money('PLN')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Filter::make('upcoming')
                    ->label('Nadchodzące')
                    ->query(fn (Builder $query) => $query->upcoming()),

                Filter::make('past')
                    ->label('Przeszłe')
                    ->query(fn (Builder $query) => $query->past()),

                Filter::make('range')
                    ->label('Zakres dat')
                    ->form([
                        DatePicker::make('from')->label('Od'),
                        DatePicker::make('to')->label('Do'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when($data['from'] ?? null, fn (Builder $q, $from) => $q->whereDate('start_time', '>=', $from))
                            ->when($data['to'] ?? null, fn (Builder $q, $to) => $q->whereDate('start_time', '<=', $to));
                    }),
            ])
            ->headerActions([
                CreateAction::make()->label('Dodaj mszę'),

                Action::make('exportPdf')
                    ->label('PDF (zakres)')
                    ->schema([
                        DatePicker::make('from')->label('Od')->required()->default(now()->startOfWeek(0)), // Tydzień zaczyna się w niedzielę
                        DatePicker::make('to')->label('Do')->required()->default(now()->endOfWeek()),
                    ])
                    ->action(function (array $data, MassPdfService $pdf) {
                        $tenant = Filament::getTenant();

                        $from = Carbon::parse($data['from']);
                        $to = Carbon::parse($data['to']);

                        $fileName = 'msze-'.$tenant->slug.'-'.$from->format('Ymd').'-'.$to->format('Ymd').'.pdf';

                        return response()->streamDownload(
                            fn () => print($pdf->weekly($tenant, $from, $to)->output()),
                            $fileName,
                            ['Content-Type' => 'application/pdf']
                        );
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('start_time', 'desc');
    }
}
