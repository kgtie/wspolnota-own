<?php

namespace App\Mail;

use App\Models\Parish;
use App\Support\Mail\EmailComposer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Queue\SerializesModels;
use Illuminate\Bus\Queueable;

abstract class WspolnotaMailable extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    abstract protected function htmlBodyView(): string;

    abstract protected function textBodyView(): string;

    protected function bodyData(): array
    {
        return [];
    }

    protected function parishContext(): ?Parish
    {
        return null;
    }

    protected function emailContext(): array
    {
        return [];
    }

    public function content(): Content
    {
        $payload = app(EmailComposer::class)->composeView(
            htmlBodyView: $this->htmlBodyView(),
            textBodyView: $this->textBodyView(),
            bodyData: $this->bodyData(),
            parish: $this->parishContext(),
            context: $this->emailContext(),
        );

        return new Content(
            view: 'mail.framework.html',
            text: 'mail.framework.text',
            with: $payload,
        );
    }
}
