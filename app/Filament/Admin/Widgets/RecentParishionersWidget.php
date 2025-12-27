<?php

namespace App\Filament\Admin\Widgets;

use App\Models\User;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Forms;

/**
 * RecentParishionersWidget - Ostatnio zarejestrowani parafianie (Filament 4)
 * 
 * Widget wyświetlający najnowszych parafian z możliwością szybkiej weryfikacji.
 */
class RecentParishionersWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 2;

    protected static ?string $heading = 'Ostatnio zarejestrowani parafianie';

    public function table(Table $table): Table
    {
        $parish = Filament::getTenant();

        return $table
            ->query(
                User::query()
                    ->where('home_parish_id', $parish?->id)
                    ->where('role', 0) // Tylko zwykli użytkownicy
                    ->latest()
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\ImageColumn::make('avatar')
                    ->label('')
                    ->circular()
                    ->size(40)
                    ->defaultImageUrl(fn (User $record): string => 'https://ui-avatars.com/api/?name=' . urlencode($record->name) . '&background=3b82f6&color=fff'),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nazwa')
                    ->description(fn (User $record): string => $record->full_name ?? ''),

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
                    ->since(),
            ])
            ->recordActions([
                Actions\Action::make('verify')
                    ->label('Zweryfikuj')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn (User $record): bool => !$record->is_user_verified)
                    ->form(fn (User $record): array => [
                        Text::make('Poproś parafianina o okazanie 9-cyfrowego kodu weryfikacyjnego.')
                            ->color('neutral'),
                        Section::make()
                            ->schema([
                                Text::make('Oczekiwany kod:')
                                    ->color('neutral'),
                                Text::make($record->verification_code ?? 'Brak kodu')
                                    ->size('lg')
                                    ->weight('bold')
                                    ->fontFamily('mono')
                                    ->copyable(),
                            ]),
                        Forms\Components\TextInput::make('entered_code')
                            ->label('Wprowadź kod')
                            ->mask('999999999')
                            ->required()
                            ->length(9),
                    ])
                    ->modalHeading('Weryfikacja parafianina')
                    ->modalSubmitActionLabel('Zweryfikuj')
                    ->action(function (User $record, array $data): void {
                        if ($data['entered_code'] !== $record->verification_code) {
                            Notification::make()
                                ->title('Nieprawidłowy kod!')
                                ->body('Wprowadzony kod nie zgadza się.')
                                ->danger()
                                ->send();
                            return;
                        }

                        $record->update([
                            'is_user_verified' => true,
                            'user_verified_at' => now(),
                        ]);

                        Notification::make()
                            ->title('Parafianin zweryfikowany!')
                            ->success()
                            ->send();
                    }),
            ])
            ->paginated(false)
            ->emptyStateHeading('Brak parafian')
            ->emptyStateDescription('Nie ma jeszcze żadnych zarejestrowanych parafian w tej parafii.')
            ->emptyStateIcon('heroicon-o-users');
    }
}
