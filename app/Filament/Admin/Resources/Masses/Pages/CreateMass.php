<?php

namespace App\Filament\Admin\Resources\Masses\Pages;

use App\Filament\Admin\Resources\Masses\MassResource;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateMass extends CreateRecord
{
    protected static string $resource = MassResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $tenantId = Filament::getTenant()?->getKey();
        $admin = Filament::auth()->user();

        if ($tenantId) {
            $data['parish_id'] = $tenantId;
        }

        $data['created_by_user_id'] = $admin instanceof User ? $admin->id : null;
        $data['updated_by_user_id'] = $admin instanceof User ? $admin->id : null;

        return $data;
    }
}
