<?php

namespace App\Providers\Filament;

use App\Models\Parish;
use App\Models\User;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * AdminPanelProvider - Panel dla administratorów parafii
 * 
 * Konfiguracja multi-tenancy gdzie Parish jest tenantem.
 * Administratorzy mogą przełączać się między przypisanymi parafiami.
 */
class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('admin')
            ->path('admin')
            ->passwordReset()
            ->emailVerification()
            ->profile()
            ->colors([
                'primary' => Color::Blue,
                'danger' => Color::Rose,
                'gray' => Color::Slate,
                'info' => Color::Sky,
                'success' => Color::Emerald,
                'warning' => Color::Orange,
            ])
            ->font('Inter')
            ->brandName('Wspólnota - Panel Administratora')
            ->favicon(asset('favicon.ico'))
            
            // Multi-tenancy: Parish jako tenant
            ->tenant(Parish::class, ownershipRelationship: 'parishes', slugAttribute: 'slug')
            ->tenantMenu(true)
            ->tenantMenuItems([
                // Możliwość przejścia do profilu parafii
                'profile' => \Filament\Navigation\MenuItem::make()
                    ->label('Ustawienia parafii')
                    ->icon('heroicon-o-cog-6-tooth'),
            ])
            
            // Automatyczne wykrywanie zasobów
            ->discoverResources(in: app_path('Filament/Admin/Resources'), for: 'App\\Filament\\Admin\\Resources')
            ->discoverPages(in: app_path('Filament/Admin/Pages'), for: 'App\\Filament\\Admin\\Pages')
            ->discoverWidgets(in: app_path('Filament/Admin/Widgets'), for: 'App\\Filament\\Admin\\Widgets')
            
            // Strony systemowe
            ->pages([
                \App\Filament\Admin\Pages\Dashboard::class,
            ])
            
            // Widgety na dashboardzie
            ->widgets([
                \App\Filament\Admin\Widgets\ParishStatsWidget::class,
                \App\Filament\Admin\Widgets\UpcomingMassesWidget::class,
                \App\Filament\Admin\Widgets\RecentParishionersWidget::class,
            ])
            
            ->authGuard('web')
            // Middleware
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
            ])
            
            // Nawigacja
            ->sidebarCollapsibleOnDesktop()
            ->navigationGroups([
                'Parafia' => \Filament\Navigation\NavigationGroup::make()
                    ->label('Parafia')
                    ->icon('heroicon-o-building-library'),
                'Liturgia' => \Filament\Navigation\NavigationGroup::make()
                    ->label('Liturgia')
                    ->icon('heroicon-o-calendar-days'),
                'Komunikacja' => \Filament\Navigation\NavigationGroup::make()
                    ->label('Komunikacja')
                    ->icon('heroicon-o-megaphone'),
            ])
            
            // Ustawienia
            ->maxContentWidth('full')
            ->databaseNotifications();
    }
}
