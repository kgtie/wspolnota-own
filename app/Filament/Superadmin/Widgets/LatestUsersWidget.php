<?php

namespace App\Filament\Superadmin\Widgets;

use App\Filament\Superadmin\Resources\UserResource;
use App\Models\User;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Actions;

/**
 * LatestUsersWidget - Ostatnio zarejestrowani użytkownicy (Filament 4)
 */
class LatestUsersWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Ostatnio zarejestrowani użytkownicy';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                User::query()
                    ->latest()
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\ImageColumn::make('avatar')
                    ->label('')
                    ->circular()
                    ->size(40)
                    ->defaultImageUrl(fn (User $record): string => 'https://ui-avatars.com/api/?name=' . urlencode($record->name) . '&background=6366f1&color=fff'),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nazwa')
                    ->description(fn (User $record): string => $record->email),

                Tables\Columns\TextColumn::make('role')
                    ->label('Rola')
                    ->badge()
                    ->formatStateUsing(fn (int $state): string => UserResource::getRoleOptions()[$state] ?? 'Nieznana')
                    ->color(fn (int $state): string => UserResource::getRoleColor($state)),

                Tables\Columns\TextColumn::make('homeParish.short_name')
                    ->label('Parafia')
                    ->placeholder('Brak'),

                Tables\Columns\TextColumn::make('verification_code')
                    ->label('Kod')
                    ->fontFamily('mono')
                    ->placeholder('-'),

                Tables\Columns\IconColumn::make('is_user_verified')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-clock')
                    ->trueColor('success')
                    ->falseColor('warning'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Zarejestrowano')
                    ->since()
                    ->sortable(),
            ])
            ->recordActions([
                Actions\Action::make('view')
                    ->label('Zobacz')
                    ->icon('heroicon-o-eye')
                    ->url(fn (User $record): string => UserResource::getUrl('view', ['record' => $record])),
            ])
            ->paginated(false);
    }
}
