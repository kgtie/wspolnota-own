<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;

class MassCalendar extends Page
{
    public static function getNavigationLabel(): string
    {
        return 'Kalendarz mszy';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Parafia';
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-calendar';
    }

    public function getView(): string
    {
        return 'filament.admin.pages.mass-calendar';
    }
}
