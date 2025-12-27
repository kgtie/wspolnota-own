<?php

namespace App\Filament\Superadmin\Resources;

use App\Filament\Superadmin\Resources\ParishResource\Pages;
use App\Filament\Superadmin\Resources\ParishResource\RelationManagers;
use App\Models\Parish;
use BackedEnum;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions;
use Filament\Schemas\Components\Section;

/**
 * ParishResource - Zarządzanie parafiami w panelu Superadmina (Filament 4)
 */
class ParishResource extends Resource
{
    protected static ?string $model = Parish::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-library';

    protected static ?string $modelLabel = 'Parafia';

    protected static ?string $pluralModelLabel = 'Parafie';

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return 'Zarządzanie';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Dane podstawowe')
                    ->description('Podstawowe informacje o parafii')
                    ->icon('heroicon-o-information-circle')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Pełna nazwa')
                            ->placeholder('np. Parafia p.w. św. Stanisława Biskupa i Męczennika')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('short_name')
                            ->label('Nazwa skrócona')
                            ->placeholder('np. Parafia Wiskitki')
                            ->required()
                            ->maxLength(100),

                        Forms\Components\TextInput::make('slug')
                            ->label('Slug (URL)')
                            ->placeholder('np. wiskitki')
                            ->required()
                            ->maxLength(100)
                            ->unique(ignoreRecord: true)
                            ->alphaDash()
                            ->helperText('Unikalny identyfikator w adresie URL'),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktywna')
                            ->helperText('Czy parafia jest widoczna w systemie?')
                            ->default(true)
                            ->inline(false),
                    ]),

                Section::make('Lokalizacja')
                    ->description('Adres i przynależność kościelna')
                    ->icon('heroicon-o-map-pin')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('street')
                            ->label('Ulica i numer')
                            ->placeholder('np. ul. Kościelna 10')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('postal_code')
                            ->label('Kod pocztowy')
                            ->placeholder('np. 96-315')
                            ->maxLength(10)
                            ->mask('99-999'),

                        Forms\Components\TextInput::make('city')
                            ->label('Miejscowość')
                            ->placeholder('np. Wiskitki')
                            ->required()
                            ->maxLength(100),

                        Forms\Components\TextInput::make('diocese')
                            ->label('Diecezja')
                            ->placeholder('np. Diecezja Łowicka')
                            ->maxLength(100),

                        Forms\Components\TextInput::make('decanate')
                            ->label('Dekanat')
                            ->placeholder('np. Dekanat Żyrardów')
                            ->maxLength(100),
                    ]),

                Section::make('Dane kontaktowe')
                    ->description('Informacje kontaktowe parafii')
                    ->icon('heroicon-o-phone')
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->placeholder('np. kontakt@parafia.pl')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('phone')
                            ->label('Telefon')
                            ->tel()
                            ->placeholder('np. +48 123 456 789')
                            ->maxLength(20),

                        Forms\Components\TextInput::make('website')
                            ->label('Strona WWW')
                            ->url()
                            ->placeholder('np. https://parafia-wiskitki.pl')
                            ->maxLength(255),
                    ]),

                Section::make('Media')
                    ->description('Logo i zdjęcie w tle')
                    ->icon('heroicon-o-photo')
                    ->columns(2)
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Forms\Components\FileUpload::make('avatar')
                            ->label('Logo parafii')
                            ->image()
                            ->directory('parishes/avatars')
                            ->imageResizeMode('cover')
                            ->imageCropAspectRatio('1:1')
                            ->imageResizeTargetWidth('300')
                            ->imageResizeTargetHeight('300'),

                        Forms\Components\FileUpload::make('cover_image')
                            ->label('Zdjęcie w tle')
                            ->image()
                            ->directory('parishes/covers')
                            ->imageResizeMode('cover')
                            ->imageResizeTargetWidth('1200')
                            ->imageResizeTargetHeight('400'),
                    ]),

                Section::make('Ustawienia zaawansowane')
                    ->description('Dodatkowa konfiguracja parafii')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Forms\Components\KeyValue::make('settings')
                            ->label('Ustawienia')
                            ->keyLabel('Klucz')
                            ->valueLabel('Wartość')
                            ->addActionLabel('Dodaj ustawienie')
                            ->reorderable(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('avatar')
                    ->label('')
                    ->circular()
                    ->size(40)
                    ->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->short_name) . '&background=3b82f6&color=fff'),

                Tables\Columns\TextColumn::make('short_name')
                    ->label('Nazwa')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Parish $record): string => $record->city ?? ''),

                Tables\Columns\TextColumn::make('diocese')
                    ->label('Diecezja')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('decanate')
                    ->label('Dekanat')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('admins_count')
                    ->label('Administratorzy')
                    ->counts('admins')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('parishioners_count')
                    ->label('Parafianie')
                    ->counts('parishioners')
                    ->badge()
                    ->color('success'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktywna')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Utworzono')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('short_name', 'asc')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->placeholder('Wszystkie')
                    ->trueLabel('Tylko aktywne')
                    ->falseLabel('Tylko nieaktywne'),

                Tables\Filters\SelectFilter::make('diocese')
                    ->label('Diecezja')
                    ->options(fn () => Parish::whereNotNull('diocese')
                        ->distinct()
                        ->pluck('diocese', 'diocese')
                        ->toArray()),
            ])
            ->recordActions([
                Actions\ActionGroup::make([
                    Actions\ViewAction::make()
                        ->label('Podgląd'),
                    Actions\EditAction::make()
                        ->label('Edytuj'),
                    Actions\DeleteAction::make()
                        ->label('Usuń'),
                ]),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make()
                        ->label('Usuń zaznaczone'),

                    Actions\BulkAction::make('activate')
                        ->label('Aktywuj')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->action(fn ($records) => $records->each->update(['is_active' => true]))
                        ->deselectRecordsAfterCompletion(),

                    Actions\BulkAction::make('deactivate')
                        ->label('Deaktywuj')
                        ->icon('heroicon-o-x-mark')
                        ->color('danger')
                        ->action(fn ($records) => $records->each->update(['is_active' => false]))
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->emptyStateHeading('Brak parafii')
            ->emptyStateDescription('Dodaj pierwszą parafię do systemu.')
            ->emptyStateIcon('heroicon-o-building-library');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\AdminsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListParishes::route('/'),
            'create' => Pages\CreateParish::route('/create'),
            'view' => Pages\ViewParish::route('/{record}'),
            'edit' => Pages\EditParish::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('is_active', true)->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'short_name', 'city', 'diocese'];
    }
}
