<?php

namespace App\Filament\Admin\Resources\AnnouncementSets\Schemas;

use App\Models\AnnouncementSet;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Operation;

class AnnouncementSetForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')
                ->label('Tytuł zbioru ogłoszeń')
                ->required()
                ->maxLength(255),

            DatePicker::make('valid_from')
                ->label('Obowiązuje od')
                ->required(),

            DatePicker::make('valid_until')
                ->label('Obowiązuje do')
                ->required()
                ->afterOrEqual('valid_from'),

            Select::make('status')
                ->label('Status')
                ->options(AnnouncementSet::getStatusOptions())
                ->required()
                ->default('draft')
                ->hiddenOn(Operation::Create), // na create zawsze szkic

            Textarea::make('ai_summary')
                ->label('Streszczenie AI')
                ->rows(4)
                ->disabled()
                ->helperText('W przyszłości wygenerujemy automatycznie.'),
        ])->columns(2);
    }
}
