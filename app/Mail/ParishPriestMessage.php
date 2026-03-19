<?php

namespace App\Mail;

use App\Models\Parish;
use App\Models\User;
use Illuminate\Mail\Mailables\Envelope;

class ParishPriestMessage extends WspolnotaMailable
{
    public function __construct(
        public User $recipient,
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

    protected function htmlBodyView(): string
    {
        return 'mail.html.users.priest-message';
    }

    protected function textBodyView(): string
    {
        return 'mail.text.users.priest-message';
    }

    protected function bodyData(): array
    {
        return [
            'recipient' => $this->recipient,
            'sender' => $this->sender,
            'subjectLine' => $this->subjectLine,
            'messageBody' => $this->messageBody,
            'parishName' => $this->parishName,
        ];
    }

    protected function parishContext(): ?Parish
    {
        return $this->sender->homeParish()->first()
            ?? $this->sender->currentParish()->first()
            ?? $this->recipient->homeParish()->first();
    }

    protected function emailContext(): array
    {
        return [
            'category_label' => 'Wiadomosc od parafii',
            'preheader' => $this->subjectLine,
            'mobile_note_variant' => 'parish',
        ];
    }
}
