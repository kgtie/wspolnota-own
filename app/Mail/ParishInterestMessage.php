<?php

namespace App\Mail;

use App\Models\Parish;
use Carbon\CarbonInterface;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Envelope;

class ParishInterestMessage extends WspolnotaMailable
{
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

    protected function htmlBodyView(): string
    {
        return 'mail.html.parishes.interest-message';
    }

    protected function textBodyView(): string
    {
        return 'mail.text.parishes.interest-message';
    }

    protected function bodyData(): array
    {
        return [
            'parish' => $this->parish,
            'publicUrl' => $this->publicUrl,
            'requestedAt' => $this->requestedAt,
            'requesterIp' => $this->requesterIp,
            'userAgent' => $this->userAgent,
        ];
    }

    protected function parishContext(): ?Parish
    {
        return $this->parish;
    }

    protected function emailContext(): array
    {
        return [
            'category_label' => 'Lead parafialny',
            'preheader' => 'Nowe zainteresowanie uruchomieniem uslugi dla parafii.',
            'mobile_note_variant' => 'parish',
        ];
    }
}
