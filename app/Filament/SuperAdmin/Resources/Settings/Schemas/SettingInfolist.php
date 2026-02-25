<?php

namespace App\Filament\SuperAdmin\Resources\Settings\Schemas;

use App\Filament\SuperAdmin\Resources\Settings\SettingResource;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Spatie\LaravelSettings\Models\SettingsProperty;

class SettingInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Ustawienie')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('group')
                            ->label('Grupa')
                            ->badge()
                            ->color('primary'),

                        TextEntry::make('name')
                            ->label('Nazwa')
                            ->copyable()
                            ->copyMessage('Skopiowano klucz'),

                        IconEntry::make('locked')
                            ->label('Zablokowane')
                            ->boolean(),

                        TextEntry::make('updated_at')
                            ->label('Ostatnia zmiana')
                            ->dateTime('d.m.Y H:i')
                            ->placeholder('Brak'),
                    ]),

                Section::make('Payload')
                    ->schema([
                        TextEntry::make('payload')
                            ->label('JSON')
                            ->state(fn (SettingsProperty $record): string => SettingResource::prettyPayload($record->payload))
                            ->copyable()
                            ->copyMessage('Skopiowano payload')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
