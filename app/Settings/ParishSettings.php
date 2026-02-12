<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Ustawienia konkretnej parafii.
 * Zarządzane przez Administratora parafii.
 *
 * UWAGA: spatie/laravel-settings przechowuje ustawienia globalnie
 * (nie per-tenant). Dla ustawień per-parafia korzystamy z kolumny
 * JSON 'settings' w tabeli parishes. Ta klasa służy jako
 * SCHEMAT/KONTRAKT — definiuje, jakie klucze mogą się znaleźć
 * w JSON, i udostępnia helpery.
 *
 * Jeśli w przyszłości potrzebujemy per-tenant settings w Spatie,
 * możemy użyć osobnego repository per parish.
 * Na razie JSON w tabeli parishes jest prostsze i wystarczające.
 */
class ParishSettings
{
    /**
     * Domyślne ustawienia dla nowej parafii
     */
    public static function defaults(): array
    {
        return [
            // Powiadomienia
            'notifications_enabled' => true,
            'mass_reminder_hours_before' => 2,       // Ile godzin przed mszą wysłać push
            'weekly_reminder_enabled' => true,        // Sobotni email do admina
            'weekly_reminder_day' => 'saturday',
            'weekly_reminder_hour' => '17:00',

            // Ogłoszenia
            'announcements_ai_summary' => true,       // Czy AI ma streszczać ogłoszenia
            'announcements_push_on_publish' => true,  // Push do parafian po publikacji

            // Aktualności
            'news_comments_enabled' => true,           // Czy komentarze pod aktualnościami
            'news_comments_require_verification' => true, // Tylko zatwierdzeni mogą komentować

            // Kancelaria online
            'office_enabled' => true,
            'office_file_upload_enabled' => true,

            // Wygląd
            'primary_color' => '#FFC107',              // Amber (domyślny)
            'theme' => 'light',                        // light / dark
        ];
    }

    /**
     * Merguje ustawienia parafii z domyślnymi (uzupełnia brakujące klucze)
     */
    public static function resolve(?array $settings): array
    {
        return array_merge(static::defaults(), $settings ?? []);
    }

    /**
     * Pobiera konkretne ustawienie z mergowanymi defaults
     */
    public static function get(?array $settings, string $key, mixed $default = null): mixed
    {
        $resolved = static::resolve($settings);

        return $resolved[$key] ?? $default;
    }
}
