<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Resources\Masses\MassResource;
use App\Models\Mass;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class UpcomingMassesTableWidget extends TableWidget
{
    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $tenantId = Filament::getTenant()?->getKey();

        return $table
            ->heading('Najblizsze msze swiete')
            ->description('Podgląd najbliższych terminów i intencji bez wychodzenia z pulpitu.')
            ->headerActions([
                Action::make('all_masses')
                    ->label('Pelna lista')
                    ->icon('heroicon-o-list-bullet')
                    ->url(MassResource::getUrl('index')),
                Action::make('create_mass')
                    ->label('Dodaj msze')
                    ->icon('heroicon-o-plus')
                    ->color('primary')
                    ->url(MassResource::getUrl('create')),
            ])
            ->query(
                fn (): Builder => Mass::query()
                    ->where('parish_id', $tenantId ?? 0)
                    ->where('celebration_at', '>=', now()->startOfDay())
                    ->orderBy('celebration_at')
                    ->limit(20)
            )
            ->columns([
                TextColumn::make('celebration_at')
                    ->label('Termin')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),

                TextColumn::make('intention_title')
                    ->label('Intencja')
                    ->searchable()
                    ->description(fn (Mass $record): ?string => $record->celebrant_name ?: null),

                TextColumn::make('mass_kind')
                    ->label('Rodzaj')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn (string $state): string => Mass::getMassKindOptions()[$state] ?? $state),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'warning',
                    })
                    ->formatStateUsing(fn (string $state): string => Mass::getStatusOptions()[$state] ?? $state),

                TextColumn::make('stipendium_amount')
                    ->label('Stypendium')
                    ->state(fn (Mass $record): string => $record->stipendium_amount !== null
                        ? number_format((float) $record->stipendium_amount, 2, ',', ' ').' PLN'
                        : 'Brak'),
            ])
            ->recordUrl(fn (Mass $record): string => MassResource::getUrl('edit', ['record' => $record]))
            ->paginated(false)
            ->poll('120s')
            ->striped();
    }
}
