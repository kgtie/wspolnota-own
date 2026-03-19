<?php

namespace App\Jobs;

use App\Mail\CommunicationBroadcastMessage;
use App\Models\CommunicationCampaign;
use App\Models\Parish;
use App\Models\User;
use App\Support\SuperAdmin\CommunicationAudienceResolver;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Throwable;

class DispatchCommunicationCampaignJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly int $campaignId,
    ) {}

    public function handle(CommunicationAudienceResolver $resolver): void
    {
        $campaign = CommunicationCampaign::query()->find($this->campaignId);

        if (! $campaign instanceof CommunicationCampaign) {
            return;
        }

        $payload = is_array($campaign->builder_payload) ? $campaign->builder_payload : [];
        $recipients = $resolver->resolveEmailRecipients($payload);
        $actor = $campaign->created_by_user_id
            ? User::query()->find($campaign->created_by_user_id)
            : null;
        $brandingParish = $campaign->parish_id
            ? Parish::query()->find($campaign->parish_id)
            : null;

        if ($recipients->isEmpty()) {
            $campaign->update([
                'status' => CommunicationCampaign::STATUS_FAILED,
                'recipients_total' => 0,
                'queued_count' => 0,
                'failed_count' => 0,
                'last_error' => 'Brak odbiorcow dla zapisanej konfiguracji kampanii.',
            ]);

            return;
        }

        $queued = 0;
        $failed = 0;

        foreach ($recipients as $recipient) {
            try {
                Mail::to((string) $recipient['email'])->queue(new CommunicationBroadcastMessage(
                    subjectLine: (string) ($payload['subject_line'] ?? $campaign->subject_line ?? 'Kampania Wspolnoty'),
                    messageBody: (string) ($payload['message_body'] ?? ''),
                    senderName: $actor instanceof User ? ($actor->full_name ?: $actor->name) : null,
                    senderEmail: $actor instanceof User ? $actor->email : null,
                    preheader: (string) ($payload['preheader'] ?? $campaign->preheader ?? ''),
                    contentHtml: (string) ($payload['campaign_content_html'] ?? ''),
                    ctaLabel: (string) ($payload['cta_label'] ?? ''),
                    ctaUrl: (string) ($payload['cta_url'] ?? ''),
                    parish: $brandingParish,
                    heroImageUrl: (string) ($payload['hero_image_url'] ?? ''),
                    campaignName: (string) ($payload['campaign_name'] ?? $campaign->name ?? ''),
                    replyToEmail: (string) ($payload['reply_to_email'] ?? ''),
                    replyToName: (string) ($payload['reply_to_name'] ?? ''),
                ));

                $queued++;
            } catch (Throwable $exception) {
                $failed++;

                report($exception);
            }
        }

        if (($payload['send_copy_to_me'] ?? false) && $actor instanceof User && filled($actor->email)) {
            Mail::to($actor->email)->queue(new CommunicationBroadcastMessage(
                subjectLine: '[Kopia] '.((string) ($payload['subject_line'] ?? $campaign->subject_line ?? 'Kampania Wspolnoty')),
                messageBody: (string) ($payload['message_body'] ?? ''),
                senderName: $actor->full_name ?: $actor->name,
                senderEmail: $actor->email,
                preheader: (string) ($payload['preheader'] ?? $campaign->preheader ?? ''),
                contentHtml: (string) ($payload['campaign_content_html'] ?? ''),
                ctaLabel: (string) ($payload['cta_label'] ?? ''),
                ctaUrl: (string) ($payload['cta_url'] ?? ''),
                parish: $brandingParish,
                heroImageUrl: (string) ($payload['hero_image_url'] ?? ''),
                campaignName: (string) ($payload['campaign_name'] ?? $campaign->name ?? ''),
                replyToEmail: (string) ($payload['reply_to_email'] ?? ''),
                replyToName: (string) ($payload['reply_to_name'] ?? ''),
            ));
        }

        $campaign->update([
            'status' => CommunicationCampaign::STATUS_QUEUED,
            'recipients_total' => $recipients->count(),
            'queued_count' => $queued,
            'failed_count' => $failed,
            'queued_at' => now(),
            'last_error' => $failed > 0 ? 'Co najmniej jedna wiadomosc nie zostala zakolejkowana.' : null,
        ]);
    }
}
