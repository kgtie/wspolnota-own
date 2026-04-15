<?php

namespace App\Support\Mail;

use App\Models\Parish;
use App\Models\User;
use Illuminate\Support\Str;

class EmailThemeFactory
{
    public function make(?Parish $parish = null, array $context = []): array
    {
        $serviceUrl = $this->resolveServiceUrl();
        $parishUrl = $parish ? $this->resolveParishUrl($parish) : $serviceUrl;
        $accentColor = $this->normalizeAccentColor(
            $parish?->getSetting('primary_color', '#b87333') ?? '#b87333',
        );

        return [
            'service_name' => (string) config('app.name', 'Wspólnota'),
            'service_url' => $serviceUrl,
            'service_logo_url' => asset('assets/mail/wspolnota-logo-placeholder.svg'),
            'service_logo_alt' => 'Logo usługi Wspólnota',
            'support_email' => (string) config('mail.from.address', 'wspolnota@wspolnota.app'),
            'category_label' => (string) ($context['category_label'] ?? 'Wiadomość z Wspólnoty'),
            'accent_color' => $accentColor,
            'accent_soft' => $this->hexToRgba($accentColor, 0.14),
            'parish_name' => $parish?->name,
            'parish_short_name' => $parish?->short_name,
            'parish_url' => $parishUrl,
            'parish_link_label' => $parish ? 'Strona parafii' : 'Publiczne strony parafii',
            'has_parish' => $parish !== null,
            'mobile_note' => $this->resolveMobileNote($parish, (string) ($context['mobile_note_variant'] ?? 'default')),
            'footer_note' => (string) ($context['footer_note'] ?? 'Wiadomość została wysłana automatycznie przez usługę Wspólnota.'),
        ];
    }

    public function resolveParishFromUser(?User $user): ?Parish
    {
        if (! $user) {
            return null;
        }

        $parishId = $user->home_parish_id ?: $user->current_parish_id;

        if (! is_numeric($parishId)) {
            return null;
        }

        return Parish::query()
            ->select(['id', 'name', 'short_name', 'slug', 'settings'])
            ->find((int) $parishId);
    }

    public function resolveParishUrl(Parish $parish): string
    {
        try {
            return route('parish.home', ['subdomain' => $parish]);
        } catch (\Throwable) {
            return $this->resolveServiceUrl();
        }
    }

    public function resolveServiceUrl(): string
    {
        try {
            return route('landing.home');
        } catch (\Throwable) {
            return (string) config('app.url', 'https://wspolnota.app');
        }
    }

    private function resolveMobileNote(?Parish $parish, string $variant): string
    {
        $subject = $parish
            ? 'Wspólnota pomaga parafii być bliżej ludzi także na telefonach.'
            : 'Wspólnota jest projektowana jako usługa wygodna także na telefonach.';

        return match ($variant) {
            'parish' => $subject.' Działa na iPhone’ach i telefonach z Androidem, dzięki czemu wierni i administratorzy mogą wracać do ogłoszeń, wiadomości i spraw parafii z dowolnego miejsca.',
            'campaign' => 'Każda kampania we Wspólnocie wspiera komunikację z osobami, które korzystają z telefonów z iOS i Androidem, dlatego ważne informacje pozostają pod ręką także po otwarciu maila.',
            default => $subject.' Wspólnota działa na telefonach z iOS i Androidem, dlatego najważniejsze sprawy można wygodnie kontynuować także po przeczytaniu tej wiadomości.',
        };
    }

    private function normalizeAccentColor(string $color): string
    {
        return preg_match('/^#(?:[0-9a-fA-F]{3}){1,2}$/', $color)
            ? Str::lower($color)
            : '#b87333';
    }

    private function hexToRgba(string $hex, float $alpha): string
    {
        $hex = ltrim($hex, '#');

        if (strlen($hex) === 3) {
            $hex = preg_replace('/(.)/', '$1$1', $hex) ?: 'b87333';
        }

        $rgb = [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];

        return sprintf('rgba(%d, %d, %d, %.2f)', $rgb[0], $rgb[1], $rgb[2], $alpha);
    }
}
