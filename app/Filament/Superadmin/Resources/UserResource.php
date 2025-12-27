<?php

namespace App\Filament\Superadmin\Resources;

use App\Filament\Superadmin\Resources\UserResource\Pages;
use App\Filament\Superadmin\Resources\UserResource\RelationManagers;
use App\Models\User;
use BackedEnum;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;

/**
 * UserResource - Zarządzanie użytkownikami w panelu Superadmina (Filament 4)
 */
class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static ?string $modelLabel = 'Użytkownik';

    protected static ?string $pluralModelLabel = 'Użytkownicy';

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return 'Zarządzanie';
    }

    public static function getRoleOptions(): array
    {
        return [
            0 => 'Użytkownik',
            1 => 'Administrator',
            2 => 'Superadministrator',
        ];
    }

    public static function getRoleColor(int $role): string
    {
        return match ($role) {
            0 => 'gray',
            1 => 'info',
            2 => 'warning',
            default => 'gray',
        };
    }

    /**
     * Generuje 9-cyfrowy kod weryfikacyjny
     */
    public static function generateVerificationCode(): string
    {
        return str_pad((string) random_int(0, 999999999), 9, '0', STR_PAD_LEFT);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Dane konta')
                    ->description('Podstawowe informacje o użytkowniku')
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

                Section::make('Rola i uprawnienia')
                    ->description('Określ poziom dostępu użytkownika')
                    ->icon('heroicon-o-shield-check')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('role')
                            ->label('Rola')
                            ->options(self::getRoleOptions())
                            ->default(0)
                            ->required()
                            ->native(false)
                            ->live()
                            ->helperText(function ($state) {
                                return match ((int) $state) {
                                    0 => 'Zwykły użytkownik - może przeglądać parafię i zapisywać się na msze',
                                    1 => 'Administrator - zarządza przypisanymi parafiami',
                                    2 => 'Superadministrator - pełny dostęp do systemu',
                                    default => '',
                                };
                            }),

                        Forms\Components\Toggle::make('email_verified_at')
                            ->label('Email zweryfikowany')
                            ->helperText('Czy użytkownik potwierdził swój email?')
                            ->dehydrateStateUsing(fn ($state) => $state ? now() : null)
                            ->formatStateUsing(fn ($state) => (bool) $state)
                            ->inline(false),
                    ]),

                Section::make('Parafia')
                    ->description('Powiązanie użytkownika z parafią')
                    ->icon('heroicon-o-building-library')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('home_parish_id')
                            ->label('Parafia domowa')
                            ->relationship('homeParish', 'short_name')
                            ->searchable()
                            ->preload()
                            ->placeholder('Wybierz parafię')
                            ->helperText('Parafia, do której użytkownik należy jako parafianin'),

                        Forms\Components\Select::make('current_parish_id')
                            ->label('Aktualny kontekst')
                            ->relationship('currentParish', 'short_name')
                            ->searchable()
                            ->preload()
                            ->placeholder('Brak')
                            ->helperText('Parafia aktualnie przeglądana przez użytkownika'),
                    ]),

                Section::make('Weryfikacja parafianina')
                    ->description('Status weryfikacji przez proboszcza')
                    ->icon('heroicon-o-check-badge')
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('verification_code')
                            ->label('Kod weryfikacyjny (9 cyfr)')
                            ->disabled()
                            ->helperText('Kod do okazania proboszczowi'),

                        Forms\Components\Toggle::make('is_user_verified')
                            ->label('Zweryfikowany')
                            ->helperText('Czy proboszcz zatwierdził użytkownika?')
                            ->live()
                            ->afterStateUpdated(function (Set $set, $state) {
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

                Section::make('Zarządzane parafie')
                    ->description('Parafie przypisane do administratora')
                    ->icon('heroicon-o-building-office-2')
                    ->visible(function (Get $get): bool {
                        return in_array((int) $get('role'), [1, 2]);
                    })
                    ->schema([
                        Forms\Components\Select::make('parishes')
                            ->label('Parafie')
                            ->relationship('parishes', 'short_name')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->helperText('Wybierz parafie, którymi ten użytkownik będzie zarządzał'),
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
            ->emptyStateHeading('Brak użytkowników')
            ->emptyStateDescription('Dodaj pierwszego użytkownika do systemu.')
            ->emptyStateIcon('heroicon-o-users');
    }

    protected static function getTableColumns(): array
    {
        return [
            Tables\Columns\ImageColumn::make('avatar')
                ->label('')
                ->circular()
                ->size(40)
                ->defaultImageUrl(fn (User $record): string => 'https://ui-avatars.com/api/?name=' . urlencode($record->name) . '&background=6366f1&color=fff'),

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

            Tables\Columns\TextColumn::make('role')
                ->label('Rola')
                ->badge()
                ->formatStateUsing(fn (int $state): string => self::getRoleOptions()[$state] ?? 'Nieznana')
                ->color(fn (int $state): string => self::getRoleColor($state)),

            Tables\Columns\TextColumn::make('homeParish.short_name')
                ->label('Parafia domowa')
                ->placeholder('Brak')
                ->toggleable(),

            Tables\Columns\IconColumn::make('is_user_verified')
                ->label('Zweryfikowany')
                ->boolean()
                ->trueIcon('heroicon-o-check-badge')
                ->falseIcon('heroicon-o-clock')
                ->trueColor('success')
                ->falseColor('gray'),

            Tables\Columns\IconColumn::make('email_verified_at')
                ->label('Email')
                ->boolean()
                ->getStateUsing(fn (User $record): bool => (bool) $record->email_verified_at)
                ->trueIcon('heroicon-o-check-circle')
                ->falseIcon('heroicon-o-x-circle')
                ->trueColor('success')
                ->falseColor('danger')
                ->toggleable(isToggledHiddenByDefault: true),

            Tables\Columns\TextColumn::make('parishes_count')
                ->label('Parafie')
                ->counts('parishes')
                ->badge()
                ->color('info')
                ->toggleable(isToggledHiddenByDefault: true),

            Tables\Columns\TextColumn::make('created_at')
                ->label('Utworzono')
                ->dateTime('d.m.Y')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ];
    }

    protected static function getTableFilters(): array
    {
        return [
            Tables\Filters\SelectFilter::make('role')
                ->label('Rola')
                ->options(self::getRoleOptions()),

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

            Tables\Filters\SelectFilter::make('home_parish_id')
                ->label('Parafia domowa')
                ->relationship('homeParish', 'short_name')
                ->searchable()
                ->preload(),
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
                    ->visible(fn (User $record): bool => !$record->is_user_verified && $record->home_parish_id !== null)
                    ->form(fn (User $record): array => [
                        Text::make('Poproś użytkownika o okazanie 9-cyfrowego kodu weryfikacyjnego.')
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
                            ->label('Wprowadź kod od użytkownika')
                            ->mask('999999999')
                            ->required()
                            ->length(9),
                    ])
                    ->modalHeading('Weryfikacja użytkownika')
                    ->modalSubmitActionLabel('Zweryfikuj')
                    ->action(function (User $record, array $data): void {
                        if ($data['entered_code'] !== $record->verification_code) {
                            Notification::make()
                                ->title('Nieprawidłowy kod!')
                                ->body('Wprowadzony kod nie zgadza się z kodem użytkownika.')
                                ->danger()
                                ->send();
                            return;
                        }

                        $record->update([
                            'is_user_verified' => true,
                            'user_verified_at' => now(),
                        ]);

                        Notification::make()
                            ->title('Użytkownik zweryfikowany!')
                            ->success()
                            ->send();
                    }),

                Actions\Action::make('regenerate_code')
                    ->label('Nowy kod')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(fn (User $record): bool => !$record->is_user_verified && $record->home_parish_id !== null)
                    ->requiresConfirmation()
                    ->modalHeading('Wygenerować nowy kod?')
                    ->modalDescription('Poprzedni kod weryfikacyjny przestanie działać. Użytkownik będzie musiał okazać nowy kod.')
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

    public static function getRelations(): array
    {
        return [
            RelationManagers\ParishesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count();
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'full_name', 'email'];
    }
}
