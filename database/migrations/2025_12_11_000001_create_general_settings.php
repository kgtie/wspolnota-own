<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('general.site_name', 'Wspólnota');
        $this->migrator->add('general.site_active', true);
        $this->migrator->add('general.contact_email', 'kontakt@wspolnota.app');
        $this->migrator->add('general.registration_open', true);
        $this->migrator->add('general.parish_registration_open', false);
        $this->migrator->add('general.max_upload_size_mb', 10);
    }

    public function down(): void
    {
        $this->migrator->delete('general.site_name');
        $this->migrator->delete('general.site_active');
        $this->migrator->delete('general.contact_email');
        $this->migrator->delete('general.registration_open');
        $this->migrator->delete('general.parish_registration_open');
        $this->migrator->delete('general.max_upload_size_mb');
    }
};
