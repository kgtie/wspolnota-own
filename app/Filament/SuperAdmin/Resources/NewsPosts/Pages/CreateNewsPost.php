<?php

namespace App\Filament\SuperAdmin\Resources\NewsPosts\Pages;

use App\Filament\SuperAdmin\Resources\NewsPosts\NewsPostResource;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;

class CreateNewsPost extends CreateRecord
{
    protected static string $resource = NewsPostResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $admin = Filament::auth()->user();

        $data = $this->normalizePublicationState($data);

        $data['created_by_user_id'] = $admin instanceof User ? $admin->id : null;
        $data['updated_by_user_id'] = $admin instanceof User ? $admin->id : null;

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function normalizePublicationState(array $data): array
    {
        $status = (string) ($data['status'] ?? 'draft');

        if ($status === 'published') {
            $data['published_at'] = $data['published_at'] ?? now();
            $data['scheduled_for'] = null;
        } elseif ($status === 'scheduled') {
            $data['scheduled_for'] = $data['scheduled_for'] ?? now()->addDay();
            $data['published_at'] = null;
        } elseif ($status === 'archived') {
            $data['published_at'] = $data['published_at'] ?? now();
            $data['scheduled_for'] = null;
        } else {
            $data['published_at'] = null;
            $data['scheduled_for'] = null;
        }

        return $data;
    }
}
