<?php

namespace App\Filament\Admin\Resources\Masses\RelationManagers;

use App\Models\User;
use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ParticipantsRelationManager extends RelationManager
{
    protected static string $relationship = 'participants';

    protected static ?string $title = 'Uczestnicy mszy';

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        return (string) $ownerRecord->participants()->count();
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('full_name')
            ->defaultSort('full_name')
            ->columns([
                TextColumn::make('full_name')
                    ->label('Uczestnik')
                    ->searchable(['full_name', 'name', 'email'])
                    ->sortable()
                    ->description(fn (User $record): ?string => $record->name ? "@{$record->name}" : null),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Skopiowano email'),

                TextColumn::make('homeParish.short_name')
                    ->label('Parafia domowa')
                    ->placeholder('Brak')
                    ->toggleable(),

                TextColumn::make('pivot.registered_at')
                    ->label('Data zapisu')
                    ->dateTime('d.m.Y H:i')
                    ->placeholder('Brak danych')
                    ->sortable(),
            ])
            ->headerActions([
                AttachAction::make()
                    ->label('Dodaj uczestnika')
                    ->preloadRecordSelect()
                    ->recordSelectSearchColumns(['full_name', 'name', 'email'])
                    ->recordSelectOptionsQuery(fn (Builder $query): Builder => $query
                        ->where('role', 0)
                        ->where('status', 'active'))
                    ->form(fn (AttachAction $action): array => [
                        $action->getRecordSelect(),
                        DateTimePicker::make('registered_at')
                            ->label('Data zapisu')
                            ->seconds(false)
                            ->default(now())
                            ->native(false),
                    ]),
            ])
            ->recordActions([
                DetachAction::make()
                    ->label('Wypisz'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DetachBulkAction::make()
                        ->label('Wypisz zaznaczonych'),
                ]),
            ]);
    }
}
