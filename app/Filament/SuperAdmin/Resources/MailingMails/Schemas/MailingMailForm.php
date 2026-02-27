<?php

namespace App\Filament\SuperAdmin\Resources\MailingMails\Schemas;

use App\Models\MailingList;
use App\Models\MailingMail;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MailingMailForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Subskrybent')
                    ->columns(2)
                    ->schema([
                        Select::make('mailing_list_id')
                            ->label('Lista')
                            ->required()
                            ->options(fn (): array => MailingList::query()->orderBy('name')->pluck('name', 'id')->all())
                            ->searchable()
                            ->preload()
                            ->native(false),

                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(MailingMail::class, 'email', ignoreRecord: true),

                        DateTimePicker::make('confirmed_at')
                            ->label('Potwierdzony')
                            ->seconds(false)
                            ->native(false),

                        TextInput::make('confirmation_token')
                            ->label('Token potwierdzenia')
                            ->maxLength(255),

                        TextInput::make('unsubscribe_token')
                            ->label('Token wypisu')
                            ->maxLength(255),
                    ]),
            ]);
    }
}
