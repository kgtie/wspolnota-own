<?php

namespace App\Mail;

use Illuminate\Mail\Mailables\Envelope;

class SchedulerDailyReportMessage extends WspolnotaMailable
{
    public function __construct(
        public array $report,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Raport schedulera Wspolnota - '.$this->report['date_label'],
        );
    }

    protected function htmlBodyView(): string
    {
        return 'mail.html.scheduler.daily-report';
    }

    protected function textBodyView(): string
    {
        return 'mail.text.scheduler.daily-report';
    }

    protected function bodyData(): array
    {
        return [
            'report' => $this->report,
        ];
    }

    protected function emailContext(): array
    {
        return [
            'category_label' => 'Raport schedulera',
            'preheader' => 'Dzienny raport wykonania zadan schedulera Wspolnota.',
        ];
    }
}
