<?php

namespace App\Filament\Admin\Resources\AnnouncementSets\Pages;

use App\Filament\Admin\Resources\AnnouncementSets\AnnouncementSetResource;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateAnnouncementSet extends CreateRecord
{
    protected static string $resource = AnnouncementSetResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $tenantId = Filament::getTenant()?->getKey();
        $admin = Filament::auth()->user();

        if ($tenantId) {
            $data['parish_id'] = $tenantId;
        }

        if (($data['status'] ?? null) === 'published' && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        $data['created_by_user_id'] = $admin instanceof User ? $admin->id : null;
        $data['updated_by_user_id'] = $admin instanceof User ? $admin->id : null;

        return $data;
    }
}
