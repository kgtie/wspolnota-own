<?php

namespace App\Mail;

use App\Models\Mass;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class MassMessageMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Mass $mass,
        public string $subjectLine,
        public string $bodyText,
    ) {}
        public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Wiadomość dotycząca mszy: ' . $this->mass->intention,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.mass-message',
            with: [
                'mass' => $this->mass,
                'bodyText' => $this->bodyText,
                'subjectLine' => $this->subjectLine,
            ],
        );
    }
}
