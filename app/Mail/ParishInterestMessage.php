<?php

namespace App\Mail;

use App\Models\Parish;
use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ParishInterestMessage extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Parish $parish,
        public string $publicUrl,
        public CarbonInterface $requestedAt,
        public ?string $requesterIp,
        public ?string $userAgent,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Zainteresowanie usługą: '.$this->parish->short_name,
            replyTo: $this->parish->email
                ? [new Address($this->parish->email, $this->parish->short_name)]
                : [],
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.parishes.interest-message',
        );
    }
}
