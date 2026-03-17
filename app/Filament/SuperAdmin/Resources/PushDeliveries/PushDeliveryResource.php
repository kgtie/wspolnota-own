<?php

namespace App\Filament\SuperAdmin\Resources\PushDeliveries;

use App\Filament\SuperAdmin\Resources\PushDeliveries\Pages\ListPushDeliveries;
use App\Filament\SuperAdmin\Resources\PushDeliveries\Pages\ViewPushDelivery;
use App\Models\PushDelivery;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class PushDeliveryResource extends Resource
{
    protected static ?string $model = PushDelivery::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-paper-airplane';

    protected static ?string $modelLabel = 'dostarczenie push';

    protected static ?string $pluralModelLabel = 'dostarczenia push';

    protected static ?string $navigationLabel = 'Dostarczenia push';

    protected static string|UnitEnum|null $navigationGroup = 'Komunikacja';

    protected static ?int $navigationSort = 4;

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->persistSearchInSession()
            ->persistFiltersInSession()
            ->columns([
                TextColumn::make('id')->label('#')->badge()->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->sortable()
                    ->color(fn (string $state): string => match ($state) {
                        PushDelivery::STATUS_SENT => 'success',
                        PushDelivery::STATUS_FAILED => 'danger',
                        default => 'warning',
                    }),
                TextColumn::make('type')->label('Typ')->badge()->searchable()->sortable(),
                TextColumn::make('user.email')->label('Uzytkownik')->searchable()->toggleable(),
                TextColumn::make('device.platform')->label('Platforma')->badge()->toggleable(),
                TextColumn::make('collapse_key')->label('Collapse')->placeholder('Brak')->toggleable(),
                TextColumn::make('message_id')->label('Message ID')->limit(36)->copyable()->toggleable(),
                TextColumn::make('error_code')->label('Error code')->badge()->toggleable(),
                TextColumn::make('error_message')->label('Blad')->limit(80)->wrap()->toggleable(),
                TextColumn::make('created_at')->label('Utworzono')->dateTime('d.m.Y H:i:s')->sortable(),
                TextColumn::make('sent_at')->label('Wyslano')->dateTime('d.m.Y H:i:s')->placeholder('Brak')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        PushDelivery::STATUS_QUEUED => PushDelivery::STATUS_QUEUED,
                        PushDelivery::STATUS_SENT => PushDelivery::STATUS_SENT,
                        PushDelivery::STATUS_FAILED => PushDelivery::STATUS_FAILED,
                    ]),
                SelectFilter::make('platform')
                    ->label('Platforma')
                    ->options([
                        'android' => 'Android',
                        'ios' => 'iOS',
                    ]),
                SelectFilter::make('type')
                    ->label('Typ')
                    ->options(fn (): array => PushDelivery::query()
                        ->whereNotNull('type')
                        ->select('type')
                        ->distinct()
                        ->orderBy('type')
                        ->pluck('type', 'type')
                        ->all()),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    DeleteAction::make(),
                ])
                    ->label('Akcje')
                    ->icon('heroicon-o-ellipsis-horizontal')
                    ->iconButton(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Podstawowe')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('id')->label('ID')->badge(),
                        TextEntry::make('status')->label('Status')->badge(),
                        TextEntry::make('type')->label('Typ')->badge(),
                        TextEntry::make('provider')->label('Provider')->badge(),
                        TextEntry::make('user.email')->label('Uzytkownik')->placeholder('Brak'),
                        TextEntry::make('device.device_id')->label('Device ID')->placeholder('Brak'),
                        TextEntry::make('platform')->label('Platforma')->badge(),
                        TextEntry::make('collapse_key')->label('Collapse key')->placeholder('Brak'),
                        TextEntry::make('message_id')->label('Message ID')->copyable()->placeholder('Brak'),
                        TextEntry::make('error_code')->label('Error code')->placeholder('Brak'),
                        TextEntry::make('error_message')->label('Error message')->columnSpanFull()->placeholder('Brak'),
                        TextEntry::make('created_at')->label('Utworzono')->dateTime('d.m.Y H:i:s'),
                        TextEntry::make('sent_at')->label('Wyslano')->dateTime('d.m.Y H:i:s')->placeholder('Brak'),
                        TextEntry::make('failed_at')->label('Failed at')->dateTime('d.m.Y H:i:s')->placeholder('Brak'),
                    ]),
                Section::make('Payload / response')
                    ->schema([
                        TextEntry::make('payload')
                            ->label('Payload')
                            ->state(fn (PushDelivery $record): string => json_encode($record->payload ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}')
                            ->copyable(),
                        TextEntry::make('response')
                            ->label('Response')
                            ->state(fn (PushDelivery $record): string => json_encode($record->response ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}')
                            ->copyable(),
                    ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['user', 'device'])
            ->orderByDesc('id');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPushDeliveries::route('/'),
            'view' => ViewPushDelivery::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }
}
