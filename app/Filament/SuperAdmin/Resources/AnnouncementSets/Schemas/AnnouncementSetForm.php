<?php

namespace App\Filament\SuperAdmin\Resources\AnnouncementSets\Schemas;

use App\Filament\Support\AnnouncementSets\AnnouncementSetFormLayout;
use App\Models\Parish;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class AnnouncementSetForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components(AnnouncementSetFormLayout::components([
                Select::make('parish_id')
                    ->label('Parafia')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->options(fn (): array => Parish::query()
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all()),
                Hidden::make('created_by_user_id'),
                Hidden::make('updated_by_user_id'),
            ]));
    }
}
