<?php

namespace App\Http\Controllers\Parish;

use App\Http\Controllers\Controller;
use App\Models\Parish;
use App\Support\Announcements\AnnouncementSetPdfExporter;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AnnouncementPdfController extends Controller
{
    public function __construct(
        private readonly AnnouncementSetPdfExporter $pdfExporter,
    ) {}

    public function __invoke(Parish $subdomain): StreamedResponse
    {
        $parish = $subdomain;

        $set = $parish->announcementSets()
            ->published()
            ->current()
            ->latest('effective_from')
            ->firstOrFail();

        abort_unless($this->pdfExporter->hasPrintableItems($set), 404);

        return $this->pdfExporter->download($set);
    }
}
