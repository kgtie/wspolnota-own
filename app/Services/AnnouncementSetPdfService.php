<?php

namespace App\Services;

use App\Models\AnnouncementSet;
use Barryvdh\DomPDF\Facade\Pdf;

class AnnouncementSetPdfService
{
    public function make(AnnouncementSet $set): \Barryvdh\DomPDF\PDF
    {
        $set->load([
            'parish',
            'announcements' => fn ($q) => $q->orderBy('sort_order'),
        ]);

        return Pdf::loadView('pdf.announcement-set', [
            'set' => $set,
            'parishName' => $set->parish?->name ?? '—',
            'announcements' => $set->announcements,
        ])->setPaper('a4');
    }
}
