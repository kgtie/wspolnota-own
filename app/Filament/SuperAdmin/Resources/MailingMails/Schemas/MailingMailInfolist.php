<?php

namespace App\Filament\SuperAdmin\Resources\MailingMails\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MailingMailInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Subskrybent')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('email')->label('Email')->copyable(),
                        TextEntry::make('mailingList.name')->label('Lista')->placeholder('Brak'),
                        TextEntry::make('confirmed_at')->label('Potwierdzony')->dateTime('d.m.Y H:i')->placeholder('Nie'),
                        TextEntry::make('deleted_at')->label('Wypisany')->dateTime('d.m.Y H:i')->placeholder('Nie'),
                        TextEntry::make('created_at')->label('Dodany')->dateTime('d.m.Y H:i'),
                        TextEntry::make('updated_at')->label('Aktualizacja')->since(),
                    ]),
            ]);
    }
}
