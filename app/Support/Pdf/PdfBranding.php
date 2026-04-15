<?php

namespace App\Support\Pdf;

use App\Models\Parish;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class PdfBranding
{
    /**
     * @return array{parish_logo_data_uri: ?string, service_logo_data_uri: string}
     */
    public function forParish(?Parish $parish): array
    {
        return [
            'parish_logo_data_uri' => $this->parishLogoDataUri($parish),
            'service_logo_data_uri' => $this->servicePlaceholderLogoDataUri(),
        ];
    }

    protected function parishLogoDataUri(?Parish $parish): ?string
    {
        if (! $parish) {
            return null;
        }

        /** @var Media|null $media */
        $media = $parish->getFirstMedia('avatar');

        if (! $media) {
            return null;
        }

        $path = $media->getPath();

        if (! is_string($path) || $path === '' || ! is_file($path)) {
            return null;
        }

        $contents = @file_get_contents($path);

        if ($contents === false) {
            return null;
        }

        $mimeType = $media->mime_type ?: 'image/png';

        return 'data:'.$mimeType.';base64,'.base64_encode($contents);
    }

    protected function servicePlaceholderLogoDataUri(): string
    {
        $svg = <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" width="240" height="240" viewBox="0 0 240 240" fill="none">
  <rect x="12" y="12" width="216" height="216" rx="48" fill="#F59E0B"/>
  <rect x="28" y="28" width="184" height="184" rx="36" fill="#FFF8E7"/>
  <path d="M61 76L90 171L120 109L150 171L179 76" stroke="#B45309" stroke-width="18" stroke-linecap="round" stroke-linejoin="round"/>
  <text x="120" y="206" text-anchor="middle" font-family="DejaVu Sans, sans-serif" font-size="26" font-weight="700" fill="#92400E">Wspolnota</text>
</svg>
SVG;

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }
}
