<?php

namespace App\Mail;

use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Envelope;

class LandingContactMessage extends WspolnotaMailable
{
    public function __construct(
        public string $name,
        public string $email,
        public ?string $parish,
        public ?string $phone,
        public string $subjectLine,
        public string $messageBody,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Landing kontakt: '.$this->subjectLine,
            replyTo: [
                new Address($this->email, $this->name),
            ],
        );
    }

    protected function htmlBodyView(): string
    {
        return 'mail.html.landing.contact-message';
    }

    protected function textBodyView(): string
    {
        return 'mail.text.landing.contact-message';
    }

    protected function bodyData(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'parish' => $this->parish,
            'phone' => $this->phone,
            'subjectLine' => $this->subjectLine,
            'messageBody' => $this->messageBody,
        ];
    }

    protected function emailContext(): array
    {
        return [
            'category_label' => 'Lead z landing page',
            'preheader' => 'Nowa wiadomosc z formularza kontaktowego landing page.',
        ];
    }
}
