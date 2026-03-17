<?php

namespace App\Providers\Filament;

use App\Filament\SuperAdmin\Pages\Dashboard;
use App\Http\Middleware\EnsureUserIsSuperAdmin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class SuperAdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('superadmin')
            ->path('superadmin')
            ->authGuard('web')
            ->login(false)
            ->registration(false)
            ->colors([
                'primary' => Color::Red,
            ])
            ->brandName('Wspolnota | SuperAdmin')
            ->favicon(null)
            ->navigationGroups([
                NavigationGroup::make('Podstawowe dane')
                    ->icon('heroicon-o-building-library'),
                NavigationGroup::make('Tresci i liturgia')
                    ->icon('heroicon-o-book-open'),
                NavigationGroup::make('Komunikacja i kampanie')
                    ->icon('heroicon-o-megaphone'),
                NavigationGroup::make('Push i urzadzenia')
                    ->icon('heroicon-o-device-phone-mobile'),
                NavigationGroup::make('Media i pliki')
                    ->icon('heroicon-o-photo')
                    ->collapsed(),
                NavigationGroup::make('System i diagnostyka')
                    ->icon('heroicon-o-wrench-screwdriver')
                    ->collapsed(),
            ])
            ->discoverResources(in: app_path('Filament/SuperAdmin/Resources'), for: 'App\\Filament\\SuperAdmin\\Resources')
            ->discoverPages(in: app_path('Filament/SuperAdmin/Pages'), for: 'App\\Filament\\SuperAdmin\\Pages')
            ->discoverWidgets(in: app_path('Filament/SuperAdmin/Widgets'), for: 'App\\Filament\\SuperAdmin\\Widgets')
            ->pages([
                Dashboard::class,
            ])
            ->widgets([
                AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                EnsureUserIsSuperAdmin::class,
            ]);
    }
}
