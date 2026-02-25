<?php

namespace App\Mail;

use App\Models\Mass;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MassParticipantsMessage extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Mass $mass,
        public User $sender,
        public string $subjectLine,
        public string $messageBody,
        public ?string $parishName = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectLine,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.masses.participants-message',
        );
    }
}
