<?php

namespace App\Settings;

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
            // Osoby widoczne publicznie przy parafii
            'staff_members' => [],

            // Widocznosc publiczna danych parafii
            'public_email' => true,
            'public_phone' => true,
            'public_website' => true,
            'public_address' => true,

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
        $resolved = array_merge(static::defaults(), $settings ?? []);
        $resolved['staff_members'] = static::normalizeStaffMembers($resolved['staff_members'] ?? []);

        return $resolved;
    }

    /**
     * Pobiera konkretne ustawienie z mergowanymi defaults
     */
    public static function get(?array $settings, string $key, mixed $default = null): mixed
    {
        $resolved = static::resolve($settings);

        return $resolved[$key] ?? $default;
    }

    /**
     * Normalizuje listę osób związanych z parafią do bezpiecznej struktury API/UI.
     *
     * @return array<int, array{name: string, title: string}>
     */
    public static function normalizeStaffMembers(mixed $members): array
    {
        if (! is_array($members)) {
            return [];
        }

        return collect($members)
            ->filter(fn ($member): bool => is_array($member))
            ->map(function (array $member): array {
                return [
                    'name' => trim((string) ($member['name'] ?? '')),
                    'title' => trim((string) ($member['title'] ?? '')),
                ];
            })
            ->filter(fn (array $member): bool => $member['name'] !== '' && $member['title'] !== '')
            ->values()
            ->all();
    }
}
