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
            'service_name' => (string) config('app.name', 'Wspolnota'),
            'service_url' => $serviceUrl,
            'service_logo_url' => asset('assets/mail/wspolnota-logo-placeholder.svg'),
            'service_logo_alt' => 'Wspolnota logo placeholder',
            'support_email' => (string) config('mail.from.address', 'wspolnota@wspolnota.app'),
            'category_label' => (string) ($context['category_label'] ?? 'Wiadomosc z Wspolnoty'),
            'accent_color' => $accentColor,
            'accent_soft' => $this->hexToRgba($accentColor, 0.14),
            'parish_name' => $parish?->name,
            'parish_short_name' => $parish?->short_name,
            'parish_url' => $parishUrl,
            'parish_link_label' => $parish ? 'Strona parafii' : 'Publiczne strony parafii',
            'has_parish' => $parish !== null,
            'mobile_note' => $this->resolveMobileNote($parish, (string) ($context['mobile_note_variant'] ?? 'default')),
            'footer_note' => (string) ($context['footer_note'] ?? 'Wiadomosc zostala wyslana automatycznie przez usluge Wspolnota.'),
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
            ? 'Wspolnota pomaga parafii byc blizej ludzi takze na telefonach.'
            : 'Wspolnota jest projektowana jako usluga wygodna takze na telefonach.';

        return match ($variant) {
            'parish' => $subject.' Dziala na iPhone\'ach i telefonach z Androidem, dzieki czemu wierni i administratorzy wracaja do ogloszen, wiadomosci i spraw parafii z dowolnego miejsca.',
            'campaign' => 'Kazda kampania w Wspolnocie wspiera komunikacje z osobami, ktore korzystaja z telefonow z iOS i Androidem, dlatego wartosciowe informacje pozostaja pod reka rowniez po otwarciu maila.',
            default => $subject.' Wspolnota dziala na telefonach z iOS i Androidem, dlatego najwazniejsze sprawy mozna wygodnie kontynuowac takze po przeczytaniu tej wiadomosci.',
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
