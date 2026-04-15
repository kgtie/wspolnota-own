<?php

namespace App\Providers\Filament;

use App\Filament\Admin\Pages\EditParishProfile;
use App\Filament\Admin\Pages\Dashboard;
use App\Models\Parish;
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
 * Konfiguruje główny panel parafialny Filament pod adresem /admin.
 *
 * Ten provider scala trzy obszary odpowiedzialności:
 * - dostęp wyłącznie dla kont webowych z guardem `web`,
 * - multi-tenancy oparte o model `Parish`,
 * - auto-discovery zasobów, stron i widgetów panelu proboszcza.
 */
class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')

            // Auth: korzystamy z Breeze, wyłączamy login/register Filament
            ->authGuard('web')
            ->login(false)
            ->registration(false)

            // Multi-tenancy: Parish jako tenant
            ->tenant(Parish::class, ownershipRelationship: 'users', slugAttribute: 'slug')
            ->tenantProfile(EditParishProfile::class)
            ->tenantMenu()

            // Wygląd
            ->colors([
                'primary' => Color::Amber,
            ])
            ->brandName('Wspólnota')
            ->favicon(asset('favicon/favicon-32x32.png'))

            // Auto-discovery zasobów, stron i widgetów
            ->discoverResources(in: app_path('Filament/Admin/Resources'), for: 'App\\Filament\\Admin\\Resources')
            ->discoverPages(in: app_path('Filament/Admin/Pages'), for: 'App\\Filament\\Admin\\Pages')
            ->discoverWidgets(in: app_path('Filament/Admin/Widgets'), for: 'App\\Filament\\Admin\\Widgets')

            // Domyślne strony i widgety
            ->pages([
                Dashboard::class,
            ])

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
            ]);
    }
}
