<?php

namespace App\Filament\Admin\Resources\NewsPosts\Pages;

use App\Filament\Admin\Resources\NewsPosts\NewsPostResource;
use App\Models\NewsPost;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;
use Filament\Support\Facades\FilamentView;

class CreateNewsPost extends CreateRecord
{
    protected static string $resource = NewsPostResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;

    public function mount(): void
    {
        $this->authorizeAccess();

        $tenantId = Filament::getTenant()?->getKey();
        $admin = Filament::auth()->user();

        abort_if(blank($tenantId), 404);

        $record = NewsPost::query()->create([
            'parish_id' => $tenantId,
            'title' => '',
            'content' => '',
            'status' => 'draft',
            'is_pinned' => false,
            'created_by_user_id' => $admin instanceof User ? $admin->id : null,
            'updated_by_user_id' => $admin instanceof User ? $admin->id : null,
        ]);

        $redirectUrl = static::getResource()::getUrl('edit', [
            'record' => $record,
        ]);

        $this->redirect($redirectUrl, navigate: FilamentView::hasSpaMode($redirectUrl));
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $tenantId = Filament::getTenant()?->getKey();
        $admin = Filament::auth()->user();

        if ($tenantId) {
            $data['parish_id'] = $tenantId;
        }

        unset($data['slug']);
        $data['title'] = NewsPost::normalizeTextColumn($data['title'] ?? null);
        $data['content'] = NewsPost::normalizeLongTextColumn($data['content'] ?? null);

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
