<?php

namespace App\Filament\Superadmin\Resources\ParishResource\Pages;

use App\Filament\Superadmin\Resources\ParishResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Flex;

class ViewParish extends ViewRecord
{
    protected static string $resource = ParishResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Edytuj'),
            Actions\DeleteAction::make()
                ->label('Usuń'),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        Flex::make([
                            ImageEntry::make('avatar')
                                ->label('')
                                ->circular()
                                ->size(100)
                                ->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->short_name) . '&background=3b82f6&color=fff&size=200')
                                ->grow(false),
                            Section::make()
                                ->schema([
                                    TextEntry::make('name')
                                        ->label('')
                                        ->size('lg')
                                        ->weight('bold'),
                                    TextEntry::make('slug')
                                        ->label('')
                                        ->badge()
                                        ->color('gray')
                                        ->prefix('/')
                                        ->copyable(),
                                ])
                                ->extraAttributes(['class' => 'border-0 shadow-none p-0']),
                        ])->from('md'),
                    ]),

                Section::make('Informacje')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('short_name')
                            ->label('Nazwa skrócona'),
                        TextEntry::make('full_address')
                            ->label('Adres')
                            ->state(fn ($record) => collect([
                                $record->street,
                                $record->postal_code . ' ' . $record->city,
                            ])->filter()->implode(", "))
                            ->placeholder('Nie podano'),
                        TextEntry::make('diocese')
                            ->label('Diecezja')
                            ->placeholder('Nie podano'),
                        TextEntry::make('email')
                            ->label('Email')
                            ->icon('heroicon-o-envelope')
                            ->copyable()
                            ->placeholder('Nie podano'),
                        TextEntry::make('decanate')
                            ->label('Dekanat')
                            ->placeholder('Nie podano'),
                        TextEntry::make('phone')
                            ->label('Telefon')
                            ->icon('heroicon-o-phone')
                            ->placeholder('Nie podano'),
                        IconEntry::make('is_active')
                            ->label('Status')
                            ->boolean()
                            ->trueIcon('heroicon-o-check-circle')
                            ->falseIcon('heroicon-o-x-circle')
                            ->trueColor('success')
                            ->falseColor('danger'),
                        TextEntry::make('website')
                            ->label('Strona WWW')
                            ->icon('heroicon-o-globe-alt')
                            ->url(fn ($record) => $record->website)
                            ->openUrlInNewTab()
                            ->placeholder('Nie podano'),
                    ]),

                Section::make('Statystyki')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('admins_count')
                            ->label('Administratorzy')
                            ->state(fn ($record) => $record->admins()->count())
                            ->badge()
                            ->color('info'),
                        TextEntry::make('parishioners_count')
                            ->label('Parafianie')
                            ->state(fn ($record) => $record->parishioners()->count())
                            ->badge()
                            ->color('success'),
                        TextEntry::make('masses_count')
                            ->label('Msze')
                            ->state(fn ($record) => $record->masses()->count())
                            ->badge()
                            ->color('warning'),
                    ]),

                Section::make('Metadane')
                    ->columns(2)
                    ->collapsed()
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('Utworzono')
                            ->dateTime('d.m.Y H:i'),
                        TextEntry::make('updated_at')
                            ->label('Ostatnia aktualizacja')
                            ->dateTime('d.m.Y H:i'),
                    ]),
            ]);
    }
}
