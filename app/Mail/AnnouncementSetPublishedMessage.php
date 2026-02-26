<?php

namespace App\Mail;

use App\Models\AnnouncementSet;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AnnouncementSetPublishedMessage extends Mailable
{
    use Queueable, SerializesModels;

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

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.announcements.published-message',
        );
    }
}
