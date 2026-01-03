<?php

namespace App\Filament\Superadmin\Resources\NewsPosts\Pages;

use App\Filament\Superadmin\Resources\NewsPosts\NewsPostResource;
use Filament\Resources\Pages\CreateRecord;

class CreateNewsPost extends CreateRecord
{
    protected static string $resource = NewsPostResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (($data['status'] ?? null) === 'published' && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        return $data;
    }
}
