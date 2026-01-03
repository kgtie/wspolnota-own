<?php

namespace App\Filament\Admin\Resources\NewsPosts\Pages;

use App\Filament\Admin\Resources\NewsPosts\NewsPostResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;
use App\Models\NewsPost;

class CreateNewsPost extends CreateRecord
{
    protected static string $resource = NewsPostResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['parish_id'] = Filament::getTenant()->id;
        $data['author_user_id'] = auth()->id();

        $data['slug'] = $this->generateUniqueSlug($data['parish_id'], Str::slug($data['title']));

        // published_at jeśli published
        if (($data['status'] ?? null) === 'published' && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        // excerpt auto
        $data['excerpt'] = $this->fillExcerptIfEmpty($data);

        return $data;
    }

    private function generateUniqueSlug(int $parishId, string $base, ?int $ignoreId = null): string
    {
        $slug = $base ?: 'wpis';

        $query = NewsPost::query()
            ->where('parish_id', $parishId)
            ->where('slug', $slug);

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        if (! $query->exists()) {
            return $slug;
        }

        for ($i = 2; $i < 200; $i++) {
            $candidate = "{$slug}-{$i}";
            $q = NewsPost::query()
                ->where('parish_id', $parishId)
                ->where('slug', $candidate);

            if ($ignoreId) {
                $q->where('id', '!=', $ignoreId);
            }

            if (! $q->exists()) {
                return $candidate;
            }
        }

        return "{$slug}-" . Str::random(6);
    }

    private function fillExcerptIfEmpty(array $data): string
    {
        if (filled($data['excerpt'] ?? null)) {
            return $data['excerpt'];
        }

        $plain = trim(strip_tags((string) ($data['content'] ?? '')));
        return Str::words($plain, 30, '…');
    }
}
