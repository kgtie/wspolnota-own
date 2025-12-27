<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ParishionerResource\Pages;
use App\Models\User;
use BackedEnum;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;

/**
 * ParishionerResource - Zarządzanie parafianami w panelu admina (Filament 4)
 * 
 * Wyświetla użytkowników, których home_parish_id odpowiada aktualnej parafii (tenant).
 * Nie używamy automatycznego scopingu tenancy, bo Users nie mają bezpośrednio parish_id,
 * tylko home_parish_id - stąd ręczne filtrowanie.
 */
class ParishionerResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static ?string $modelLabel = 'Parafianin';

    protected static ?string $pluralModelLabel = 'Parafianie';

    protected static ?int $navigationSort = 1;

    /**
     * Wyłączamy automatyczny scope tenancy - mamy własny
     */
    protected static bool $isScopedToTenant = false;

    public static function getNavigationGroup(): ?string
    {
        return 'Parafia';
    }

    /**
     * Generuje 9-cyfrowy kod weryfikacyjny
     */
    public static function generateVerificationCode(): string
    {
        return str_pad((string) random_int(0, 999999999), 9, '0', STR_PAD_LEFT);
    }

    /**
     * Nadpisujemy bazowy query - filtrujemy po home_parish_id
     */
    public static function getEloquentQuery(): Builder
    {
        $parish = Filament::getTenant();

        return parent::getEloquentQuery()
            ->where('home_parish_id', $parish?->id)
            ->where('role', 0); // Tylko zwykli użytkownicy (nie admini)
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Dane osobowe')
                    ->description('Podstawowe informacje o parafianinie')
                    ->icon('heroicon-o-user')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nazwa użytkownika')
                            ->placeholder('np. jan_kowalski')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('full_name')
                            ->label('Imię i nazwisko')
                            ->placeholder('np. Jan Kowalski')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        Forms\Components\TextInput::make('password')
                            ->label('Hasło')
                            ->password()
                            ->dehydrateStateUsing(fn ($state) => $state ? Hash::make($state) : null)
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->minLength(8)
                            ->helperText(fn (string $operation) => $operation === 'edit'
                                ? 'Pozostaw puste, aby nie zmieniać hasła'
                                : 'Minimum 8 znaków'),

                        Forms\Components\FileUpload::make('avatar')
                            ->label('Zdjęcie profilowe')
                            ->image()
                            ->directory('users/avatars')
                            ->imageResizeMode('cover')
                            ->imageCropAspectRatio('1:1')
                            ->imageResizeTargetWidth('200')
                            ->imageResizeTargetHeight('200')
                            ->columnSpanFull(),
                    ]),

                Section::make('Weryfikacja')
                    ->description('Status weryfikacji parafianina')
                    ->icon('heroicon-o-check-badge')
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('verification_code')
                            ->label('Kod weryfikacyjny (9 cyfr)')
                            ->disabled()
                            ->helperText('Kod do okazania przez parafianina'),

                        Forms\Components\Toggle::make('is_user_verified')
                            ->label('Zweryfikowany')
                            ->helperText('Czy parafianin został zweryfikowany?')
                            ->live()
                            ->afterStateUpdated(function (Set $set, $state): void {
                                if ($state) {
                                    $set('user_verified_at', now());
                                } else {
                                    $set('user_verified_at', null);
                                }
                            })
                            ->inline(false),

                        Forms\Components\DateTimePicker::make('user_verified_at')
                            ->label('Data weryfikacji')
                            ->disabled()
                            ->displayFormat('d.m.Y H:i'),
                    ]),

                Section::make('Status konta')
                    ->description('Dodatkowe informacje o koncie')
                    ->icon('heroicon-o-shield-check')
                    ->columns(2)
                    ->collapsed()
                    ->schema([
                        Forms\Components\Toggle::make('email_verified_at')
                            ->label('Email zweryfikowany')
                            ->helperText('Czy użytkownik potwierdził swój email?')
                            ->dehydrateStateUsing(fn ($state) => $state ? now() : null)
                            ->formatStateUsing(fn ($state) => (bool) $state)
                            ->inline(false),

                        Forms\Components\DateTimePicker::make('created_at')
                            ->label('Data rejestracji')
                            ->disabled()
                            ->displayFormat('d.m.Y H:i'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns(self::getTableColumns())
            ->defaultSort('created_at', 'desc')
            ->filters(self::getTableFilters())
            ->recordActions(self::getTableRecordActions())
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make()
                        ->label('Usuń zaznaczonych'),
                ]),
            ])
            ->emptyStateHeading('Brak parafian')
            ->emptyStateDescription('Nie ma jeszcze żadnych zarejestrowanych parafian w tej parafii.')
            ->emptyStateIcon('heroicon-o-users');
    }

    protected static function getTableColumns(): array
    {
        return [
            Tables\Columns\ImageColumn::make('avatar')
                ->label('')
                ->circular()
                ->size(40)
                ->defaultImageUrl(fn (User $record): string => 'https://ui-avatars.com/api/?name=' . urlencode($record->name) . '&background=3b82f6&color=fff'),

            Tables\Columns\TextColumn::make('name')
                ->label('Nazwa')
                ->searchable()
                ->sortable()
                ->description(fn (User $record): string => $record->full_name ?? ''),

            Tables\Columns\TextColumn::make('email')
                ->label('Email')
                ->searchable()
                ->sortable()
                ->copyable()
                ->icon('heroicon-o-envelope'),

            Tables\Columns\TextColumn::make('verification_code')
                ->label('Kod')
                ->fontFamily('mono')
                ->toggleable()
                ->placeholder('-'),

            Tables\Columns\IconColumn::make('is_user_verified')
                ->label('Zweryfikowany')
                ->boolean()
                ->trueIcon('heroicon-o-check-badge')
                ->falseIcon('heroicon-o-clock')
                ->trueColor('success')
                ->falseColor('warning'),

            Tables\Columns\IconColumn::make('email_verified_at')
                ->label('Email')
                ->boolean()
                ->getStateUsing(fn (User $record): bool => (bool) $record->email_verified_at)
                ->trueIcon('heroicon-o-check-circle')
                ->falseIcon('heroicon-o-x-circle')
                ->trueColor('success')
                ->falseColor('danger')
                ->toggleable(isToggledHiddenByDefault: true),

            Tables\Columns\TextColumn::make('created_at')
                ->label('Zarejestrowano')
                ->dateTime('d.m.Y')
                ->sortable()
                ->toggleable(),
        ];
    }

    protected static function getTableFilters(): array
    {
        return [
            Tables\Filters\TernaryFilter::make('is_user_verified')
                ->label('Weryfikacja')
                ->placeholder('Wszystkie')
                ->trueLabel('Zweryfikowani')
                ->falseLabel('Niezweryfikowani'),

            Tables\Filters\TernaryFilter::make('email_verified_at')
                ->label('Email')
                ->placeholder('Wszystkie')
                ->trueLabel('Potwierdzony')
                ->falseLabel('Niepotwierdzony')
                ->queries(
                    true: fn (Builder $query) => $query->whereNotNull('email_verified_at'),
                    false: fn (Builder $query) => $query->whereNull('email_verified_at'),
                ),
        ];
    }

    protected static function getTableRecordActions(): array
    {
        return [
            Actions\ActionGroup::make([
                Actions\ViewAction::make()
                    ->label('Podgląd'),
                Actions\EditAction::make()
                    ->label('Edytuj'),

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
                            ->label('Wprowadź kod od parafianina')
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
                                ->body('Wprowadzony kod nie zgadza się z kodem parafianina.')
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

                Actions\Action::make('regenerate_code')
                    ->label('Nowy kod')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(fn (User $record): bool => !$record->is_user_verified)
                    ->requiresConfirmation()
                    ->modalHeading('Wygenerować nowy kod?')
                    ->modalDescription('Poprzedni kod weryfikacyjny przestanie działać.')
                    ->modalSubmitActionLabel('Generuj nowy kod')
                    ->action(function (User $record): void {
                        $newCode = self::generateVerificationCode();
                        $record->update(['verification_code' => $newCode]);

                        Notification::make()
                            ->title('Nowy kod wygenerowany!')
                            ->body('Nowy kod: ' . $newCode)
                            ->success()
                            ->send();
                    }),

                Actions\DeleteAction::make()
                    ->label('Usuń'),
            ]),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListParishioners::route('/'),
            'create' => Pages\CreateParishioner::route('/create'),
            'view' => Pages\ViewParishioner::route('/{record}'),
            'edit' => Pages\EditParishioner::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $parish = Filament::getTenant();
        if (!$parish) {
            return null;
        }

        $count = User::where('home_parish_id', $parish->id)
            ->where('role', 0)
            ->where('is_user_verified', false)
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Oczekujących na weryfikację';
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'full_name', 'email'];
    }
}
