<?php

namespace App\Filament\SuperAdmin\Resources\MailingLists\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MailingListInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Lista')
                    ->schema([
                        TextEntry::make('name')->label('Nazwa'),
                        TextEntry::make('mails_count')->label('Subskrybenci')->badge(),
                        TextEntry::make('created_at')->label('Utworzono')->dateTime('d.m.Y H:i'),
                        TextEntry::make('updated_at')->label('Aktualizacja')->since(),
                    ]),
            ]);
    }
}
