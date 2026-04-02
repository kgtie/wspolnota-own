<?php
namespace App\Mail;

/**
 * Mail klasy potwierdzającej zapis do listy mailingowej
 * Oczekujących na uruchomienie usługi Wspólnota
 */

use App\Models\MailingMail;
use Illuminate\Mail\Mailables\Envelope;

class ConfirmSubscription extends WspolnotaMailable
{
    public function __construct(public MailingMail $subscriber)
    {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Potwierdź zapis do Wspólnoty',
        );
    }

    protected function htmlBodyView(): string
    {
        return 'mail.html.subscription.confirm';
    }

    protected function textBodyView(): string
    {
        return 'mail.text.subscription.confirm';
    }

    protected function bodyData(): array
    {
        return [
            'subscriber' => $this->subscriber,
        ];
    }

    protected function emailContext(): array
    {
        return [
            'category_label' => 'Lista mailingowa',
            'preheader' => 'Potwierdz zapis do newslettera Wspolnoty.',
            'footer_note' => 'Ten e-mail pomaga bezpiecznie potwierdzić zapis do listy mailingowej Wspólnoty.',
        ];
    }
}
