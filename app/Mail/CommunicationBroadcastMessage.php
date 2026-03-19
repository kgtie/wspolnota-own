<?php

namespace App\Mail;

use App\Models\Parish;
use App\Support\Mail\CampaignContentRenderer;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Envelope;

class CommunicationBroadcastMessage extends WspolnotaMailable
{
    public function __construct(
        public string $subjectLine,
        public string $messageBody,
        public ?string $senderName = null,
        public ?string $senderEmail = null,
        public ?string $preheader = null,
        public ?string $contentHtml = null,
        public ?string $ctaLabel = null,
        public ?string $ctaUrl = null,
        public ?Parish $parish = null,
        public ?string $heroImageUrl = null,
        public ?string $campaignName = null,
        public ?string $replyToEmail = null,
        public ?string $replyToName = null,
    ) {}

    public function envelope(): Envelope
    {
        $replyTo = $this->replyToEmail ?: $this->senderEmail;
        $replyToName = $this->replyToEmail ? $this->replyToName : $this->senderName;

        return new Envelope(
            subject: $this->subjectLine,
            replyTo: filled($replyTo) ? [new Address($replyTo, $replyToName)] : [],
        );
    }

    protected function htmlBodyView(): string
    {
        return 'mail.html.communication.broadcast-message';
    }

    protected function textBodyView(): string
    {
        return 'mail.text.communication.broadcast-message';
    }

    protected function bodyData(): array
    {
        $renderer = app(CampaignContentRenderer::class);

        return [
            'subjectLine' => $this->subjectLine,
            'messageBody' => $this->messageBody,
            'senderName' => $this->senderName,
            'senderEmail' => $this->senderEmail,
            'contentHtml' => $renderer->renderForEmail($this->contentHtml),
            'contentText' => $renderer->toPlainText($this->contentHtml),
            'ctaLabel' => $this->ctaLabel,
            'ctaUrl' => $this->ctaUrl,
            'heroImageUrl' => $this->heroImageUrl,
            'campaignName' => $this->campaignName,
        ];
    }

    protected function parishContext(): ?Parish
    {
        return $this->parish;
    }

    protected function emailContext(): array
    {
        return [
            'category_label' => $this->campaignName ?: 'Kampania email',
            'preheader' => (string) $this->preheader,
            'mobile_note_variant' => 'campaign',
            'footer_note' => 'Ta wiadomosc jest czescia komunikacji przygotowanej w centrum kampanii Wspolnoty.',
        ];
    }
}
