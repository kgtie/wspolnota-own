<?php

namespace App\Notifications\Concerns;

use App\Models\Parish;
use App\Support\Mail\EmailComposer;
use Illuminate\Notifications\Messages\MailMessage;

trait RendersWspolnotaMailMessage
{
    protected function wspolnotaMailMessage(
        string $subject,
        string $htmlBodyView,
        string $textBodyView,
        array $bodyData = [],
        ?Parish $parish = null,
        array $context = [],
    ): MailMessage {
        $payload = app(EmailComposer::class)->composeView(
            htmlBodyView: $htmlBodyView,
            textBodyView: $textBodyView,
            bodyData: $bodyData,
            parish: $parish,
            context: $context,
        );

        $message = (new MailMessage)
            ->subject($subject)
            ->view([
                'html' => 'mail.framework.html',
                'text' => 'mail.framework.text',
            ], $payload);

        $replyToEmail = (string) ($context['reply_to_email'] ?? '');
        $replyToName = $context['reply_to_name'] ?? null;

        if ($replyToEmail !== '') {
            $message->replyTo($replyToEmail, is_string($replyToName) ? $replyToName : null);
        }

        return $message;
    }
}
