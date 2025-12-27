<?php

namespace App\Providers\Filament;

use App\Filament\Superadmin\Widgets\LatestUsersWidget;
use App\Filament\Superadmin\Widgets\SystemStatsWidget;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
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

/**
 * SuperadminPanelProvider - Panel dla Superadministratora (Filament 4)
 *
 * Dostępny pod adresem /superadmin
 * Tylko dla użytkowników z rolą = 2
 */
class SuperadminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('superadmin')
            ->path('superadmin')
            ->brandName('Wspólnota - Superadmin')
            ->colors([
                'primary' => Color::Amber,
                'danger' => Color::Rose,
                'info' => Color::Sky,
                'success' => Color::Emerald,
                'warning' => Color::Orange,
            ])
            ->discoverResources(
                in: app_path('Filament/Superadmin/Resources'),
                for: 'App\\Filament\\Superadmin\\Resources'
            )
            ->discoverPages(
                in: app_path('Filament/Superadmin/Pages'),
                for: 'App\\Filament\\Superadmin\\Pages'
            )
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(
                in: app_path('Filament/Superadmin/Widgets'),
                for: 'App\\Filament\\Superadmin\\Widgets'
            )
            ->widgets([
                AccountWidget::class,
                SystemStatsWidget::class,
                LatestUsersWidget::class,
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
            ->authGuard('web')
            ->authMiddleware([
                Authenticate::class,
            ])
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->sidebarCollapsibleOnDesktop()
            ->spa();
    }
}
