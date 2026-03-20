<?php

namespace App\Filament\Admin\Resources\AnnouncementSets\Schemas;

use App\Filament\Support\AnnouncementSets\AnnouncementSetFormLayout;
use Filament\Forms\Components\Hidden;
use Filament\Schemas\Schema;

class AnnouncementSetForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components(AnnouncementSetFormLayout::components([
                Hidden::make('parish_id'),
                Hidden::make('created_by_user_id'),
                Hidden::make('updated_by_user_id'),
            ]));
    }
}
