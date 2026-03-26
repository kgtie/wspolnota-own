<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Ustawienia globalne całej Usługi Wspólnota.
 * Zarządzane wyłącznie przez SuperAdmina.
 *
 * Grupa: 'general'
 */
class GeneralSettings extends Settings
{
    /** Nazwa usługi */
    public string $site_name;

    /** Czy usługa jest aktywna (globalny kill switch) */
    public bool $site_active;

    /** Email kontaktowy usługi */
    public string $contact_email;

    /** Czy rejestracja nowych użytkowników jest otwarta */
    public bool $registration_open;

    /** Czy rejestracja nowych parafii jest otwarta */
    public bool $parish_registration_open;

    /** Maksymalny rozmiar uploadu w MB */
    public int $max_upload_size_mb;

    /** Numer wersji calej uslugi Wspolnota */
    public string $service_version;

    public static function group(): string
    {
        return 'general';
    }
}
