<?php

namespace App\Filament\Admin\Resources\NewsPosts\Pages;

use App\Filament\Admin\Resources\NewsPosts\NewsPostResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Str;

class EditNewsPost extends EditRecord
{
    protected static string $resource = NewsPostResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        unset($data['slug']); // nie edytowalny dla admina

        if (($data['status'] ?? null) === 'published' && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        $data['excerpt'] = $this->fillExcerptIfEmpty($data); // Warto, żeby excerpt był zawsze wypełniony

        return $data;
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
