<?php

namespace App\Support\Announcements;

use App\Models\AnnouncementSet;
use App\Support\Pdf\PdfBranding;
use Illuminate\Support\Str;
use Spatie\LaravelPdf\Enums\Format;
use Spatie\LaravelPdf\Facades\Pdf;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AnnouncementSetPdfExporter
{
    public function hasPrintableItems(AnnouncementSet $set): bool
    {
        return $set->items()
            ->where('is_active', true)
            ->exists();
    }

    public function download(AnnouncementSet $set): StreamedResponse
    {
        $set->loadMissing('parish');
        $branding = app(PdfBranding::class)->forParish($set->parish);

        $items = $set->items()
            ->where('is_active', true)
            ->orderBy('position')
            ->get([
                'position',
                'title',
                'content',
                'is_important',
            ]);

        $fileName = $this->fileNameFor($set);

        $pdfBase64 = Pdf::view('pdf.announcements.set', [
            'set' => $set,
            'items' => $items,
            'generatedAt' => now(),
            ...$branding,
        ])
            ->format(Format::A4)
            ->portrait()
            ->name($fileName)
            ->base64();

        return response()->streamDownload(
            static function () use ($pdfBase64): void {
                echo base64_decode($pdfBase64);
            },
            $fileName,
            [
                'Content-Type' => 'application/pdf',
            ],
        );
    }

    protected function fileNameFor(AnnouncementSet $set): string
    {
        $date = $set->effective_from?->format('Ymd') ?? now()->format('Ymd');
        $parishPart = $set->parish?->short_name ?: $set->parish?->name ?: 'parafia';
        $titlePart = Str::limit(Str::slug($set->title), 40, '');

        return sprintf(
            'ogloszenia-%s-%s-%s.pdf',
            Str::slug((string) $parishPart),
            $date,
            $titlePart ?: 'zestaw',
        );
    }
}
