<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ParishPriestWeeklyDigestMessage extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public array $report,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Cotygodniowa checklista parafii - '.$this->report['parish']['name'],
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.parishes.priest-weekly-digest',
        );
    }
}
