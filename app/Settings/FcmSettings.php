<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class FcmSettings extends Settings
{
    public bool $enabled;

    public string $project_id;

    public string $service_account_json;

    public int $request_timeout_seconds;

    public int $android_ttl_seconds;

    public int $ios_ttl_seconds;

    public bool $news_collapsible;

    public bool $announcements_collapsible;

    public bool $office_messages_collapsible;

    public bool $parish_approval_collapsible;

    public static function group(): string
    {
        return 'fcm';
    }

    public static function encrypted(): array
    {
        return ['service_account_json'];
    }

    public function resolvedProjectId(): string
    {
        if ($this->project_id !== '') {
            return $this->project_id;
        }

        $decoded = $this->decodedServiceAccount();

        return (string) ($decoded['project_id'] ?? '');
    }

    public function decodedServiceAccount(): array
    {
        $payload = trim($this->service_account_json);

        if ($payload === '') {
            return [];
        }

        $decoded = json_decode($payload, true);

        return is_array($decoded) ? $decoded : [];
    }
}
