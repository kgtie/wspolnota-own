<?php

namespace App\Mail;

use App\Models\Mass;
use App\Models\Parish;
use App\Models\User;
use Illuminate\Mail\Mailables\Envelope;

class MassParticipantsMessage extends WspolnotaMailable
{
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

    protected function htmlBodyView(): string
    {
        return 'mail.html.masses.participants-message';
    }

    protected function textBodyView(): string
    {
        return 'mail.text.masses.participants-message';
    }

    protected function bodyData(): array
    {
        return [
            'mass' => $this->mass,
            'sender' => $this->sender,
            'subjectLine' => $this->subjectLine,
            'messageBody' => $this->messageBody,
            'parishName' => $this->parishName,
        ];
    }

    protected function parishContext(): ?Parish
    {
        return $this->mass->parish()->first();
    }

    protected function emailContext(): array
    {
        return [
            'category_label' => 'Komunikacja parafialna',
            'preheader' => $this->subjectLine,
            'mobile_note_variant' => 'parish',
        ];
    }
}
