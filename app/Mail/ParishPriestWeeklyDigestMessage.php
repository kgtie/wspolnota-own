<?php

namespace App\Mail;

use Illuminate\Mail\Mailables\Envelope;

class ParishPriestWeeklyDigestMessage extends WspolnotaMailable
{
    public function __construct(
        public array $report,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Cotygodniowa checklista parafii - '.$this->report['parish']['name'],
        );
    }

    protected function htmlBodyView(): string
    {
        return 'mail.html.parishes.priest-weekly-digest';
    }

    protected function textBodyView(): string
    {
        return 'mail.text.parishes.priest-weekly-digest';
    }

    protected function bodyData(): array
    {
        return [
            'report' => $this->report,
        ];
    }

    protected function parishContext(): ?\App\Models\Parish
    {
        $parishId = $this->report['parish']['id'] ?? null;

        return is_numeric($parishId) ? \App\Models\Parish::query()->find((int) $parishId) : null;
    }

    protected function emailContext(): array
    {
        return [
            'category_label' => 'Checklista parafii',
            'preheader' => 'Cotygodniowa checklista dla administratora parafii.',
            'mobile_note_variant' => 'parish',
        ];
    }
}
