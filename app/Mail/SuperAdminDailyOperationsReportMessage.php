<?php

namespace App\Mail;

use Illuminate\Mail\Mailables\Envelope;

class SuperAdminDailyOperationsReportMessage extends WspolnotaMailable
{
    public function __construct(
        public array $report,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Dobowy raport operacyjny Wspolnota - '.$this->report['date_label'],
        );
    }

    protected function htmlBodyView(): string
    {
        return 'mail.html.superadmin.daily-operations-report';
    }

    protected function textBodyView(): string
    {
        return 'mail.text.superadmin.daily-operations-report';
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
            'category_label' => 'Raport operacyjny',
            'preheader' => 'Dobowy raport operacyjny dla superadmina Wspolnoty.',
        ];
    }
}
