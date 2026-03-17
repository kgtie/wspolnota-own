<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('fcm.enabled', false);
        $this->migrator->add('fcm.project_id', '');
        $this->migrator->addEncrypted('fcm.service_account_json', '');
        $this->migrator->add('fcm.request_timeout_seconds', 10);
        $this->migrator->add('fcm.android_ttl_seconds', 21600);
        $this->migrator->add('fcm.ios_ttl_seconds', 21600);
        $this->migrator->add('fcm.news_collapsible', true);
        $this->migrator->add('fcm.announcements_collapsible', true);
        $this->migrator->add('fcm.office_messages_collapsible', false);
        $this->migrator->add('fcm.parish_approval_collapsible', false);
    }

    public function down(): void
    {
        $this->migrator->delete('fcm.enabled');
        $this->migrator->delete('fcm.project_id');
        $this->migrator->delete('fcm.service_account_json');
        $this->migrator->delete('fcm.request_timeout_seconds');
        $this->migrator->delete('fcm.android_ttl_seconds');
        $this->migrator->delete('fcm.ios_ttl_seconds');
        $this->migrator->delete('fcm.news_collapsible');
        $this->migrator->delete('fcm.announcements_collapsible');
        $this->migrator->delete('fcm.office_messages_collapsible');
        $this->migrator->delete('fcm.parish_approval_collapsible');
    }
};
