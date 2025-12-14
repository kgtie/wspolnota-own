<?php
namespace App\Mail;

/**
 * Mail klasy potwierdzającej zapis do listy mailingowej
 * Oczekujących na uruchomienie usługi Wspólnota
 */

use App\Models\MailingMail;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ConfirmSubscription extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public MailingMail $subscriber)
    {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Potwierdź zapis do Wspólnoty',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.subscription.confirm',
        );
    }
}