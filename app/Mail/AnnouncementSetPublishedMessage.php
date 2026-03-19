<?php

namespace App\Mail;

use App\Models\AnnouncementSet;
use Illuminate\Mail\Mailables\Envelope;

class AnnouncementSetPublishedMessage extends WspolnotaMailable
{
    public function __construct(
        public AnnouncementSet $announcementSet,
        public string $parishName,
    ) {}

    public function envelope(): Envelope
    {
        $dateFrom = $this->announcementSet->effective_from?->format('d.m.Y') ?? 'brak daty';

        return new Envelope(
            subject: "Nowe ogloszenia parafialne ({$dateFrom})",
        );
    }

    protected function htmlBodyView(): string
    {
        return 'mail.html.announcements.published-message';
    }

    protected function textBodyView(): string
    {
        return 'mail.text.announcements.published-message';
    }

    protected function bodyData(): array
    {
        return [
            'announcementSet' => $this->announcementSet,
            'parishName' => $this->parishName,
        ];
    }

    protected function parishContext(): ?\App\Models\Parish
    {
        return $this->announcementSet->parish()->first();
    }

    protected function emailContext(): array
    {
        return [
            'category_label' => 'Ogloszenia parafialne',
            'preheader' => 'Nowy zestaw ogloszen parafialnych jest juz gotowy.',
            'mobile_note_variant' => 'parish',
        ];
    }
}
