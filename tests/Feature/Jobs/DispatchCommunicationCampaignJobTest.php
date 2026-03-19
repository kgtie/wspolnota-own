<?php

use App\Jobs\DispatchCommunicationCampaignJob;
use App\Mail\CommunicationBroadcastMessage;
use App\Models\CommunicationCampaign;
use App\Models\Parish;
use App\Models\User;
use App\Support\SuperAdmin\CommunicationAudienceResolver;
use Illuminate\Support\Facades\Mail;

it('queues campaign emails for resolved recipients and stores dispatch counters', function (): void {
    Mail::fake();

    $parish = Parish::factory()->create();

    $actor = User::factory()->create([
        'email' => 'superadmin@example.com',
        'role' => 2,
        'status' => 'active',
    ]);

    $recipientA = User::factory()->create([
        'email' => 'a@example.com',
        'status' => 'active',
        'home_parish_id' => $parish->getKey(),
    ]);

    $recipientB = User::factory()->create([
        'email' => 'b@example.com',
        'status' => 'active',
        'home_parish_id' => $parish->getKey(),
    ]);

    User::factory()->create([
        'email' => 'outside@example.com',
        'status' => 'active',
    ]);

    $campaign = CommunicationCampaign::query()->create([
        'name' => 'Kampania parafialna',
        'channel' => 'email',
        'status' => CommunicationCampaign::STATUS_DISPATCHING,
        'parish_id' => $parish->getKey(),
        'created_by_user_id' => $actor->getKey(),
        'subject_line' => 'Nowa kampania',
        'preheader' => 'Preheader kampanii',
        'builder_payload' => [
            'campaign_name' => 'Kampania parafialna',
            'recipient_scope' => 'users_by_parish',
            'target_parish_id' => $parish->getKey(),
            'include_inactive_users' => false,
            'only_verified_users' => false,
            'only_email_verified_users' => false,
            'only_users_with_push_devices' => false,
            'respect_email_preferences' => false,
            'email_preference_topic' => 'announcements',
            'subject_line' => 'Nowa kampania',
            'preheader' => 'Preheader kampanii',
            'message_body' => 'Fallback kampanii',
            'campaign_content_html' => '<h2>Komunikat</h2><p>Parafialna tresc kampanii.</p>',
            'cta_label' => 'Otworz',
            'cta_url' => 'https://example.com/campaign',
            'reply_to_email' => 'reply@example.com',
            'reply_to_name' => 'Reply Name',
            'send_copy_to_me' => true,
        ],
    ]);

    (new DispatchCommunicationCampaignJob((int) $campaign->getKey()))
        ->handle(app(CommunicationAudienceResolver::class));

    Mail::assertQueued(CommunicationBroadcastMessage::class, 3);

    Mail::assertQueued(CommunicationBroadcastMessage::class, fn (CommunicationBroadcastMessage $mail): bool => $mail->hasTo($recipientA->email));
    Mail::assertQueued(CommunicationBroadcastMessage::class, fn (CommunicationBroadcastMessage $mail): bool => $mail->hasTo($recipientB->email));
    Mail::assertQueued(CommunicationBroadcastMessage::class, fn (CommunicationBroadcastMessage $mail): bool => $mail->hasTo($actor->email) && str_starts_with($mail->subjectLine, '[Kopia] '));

    $campaign->refresh();

    expect($campaign->status)->toBe(CommunicationCampaign::STATUS_QUEUED)
        ->and($campaign->recipients_total)->toBe(2)
        ->and($campaign->queued_count)->toBe(2)
        ->and($campaign->failed_count)->toBe(0)
        ->and($campaign->queued_at)->not->toBeNull();
});
