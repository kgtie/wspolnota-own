<?php

use App\Models\Mass;
use App\Models\OfficeConversation;
use App\Models\OfficeMessage;
use App\Models\Parish;
use App\Models\User;
use App\Notifications\MassPendingReminderNotification;
use App\Notifications\OfficeMessageReceivedNotification;
use App\Notifications\ParishApprovalStatusChangedNotification;
use App\Support\Push\PushPayloadFactory;

it('includes parish routing data for office message push payloads', function (): void {
    $parish = Parish::factory()->create();
    $priest = User::factory()->create(['home_parish_id' => $parish->getKey()]);
    $parishioner = User::factory()->create(['home_parish_id' => $parish->getKey()]);

    $conversation = OfficeConversation::query()->create([
        'parish_id' => $parish->getKey(),
        'parishioner_user_id' => $parishioner->getKey(),
        'priest_user_id' => $priest->getKey(),
        'status' => OfficeConversation::STATUS_OPEN,
        'last_message_at' => now(),
    ]);

    $message = OfficeMessage::query()->create([
        'office_conversation_id' => $conversation->getKey(),
        'sender_user_id' => $parishioner->getKey(),
        'body' => 'Test message',
        'has_attachments' => false,
    ]);

    $payload = (new OfficeMessageReceivedNotification($message))->toDatabase($priest);

    expect(data_get($payload, 'type'))->toBe('OFFICE_MESSAGE_RECEIVED')
        ->and(data_get($payload, 'data.chat_id'))->toBe((string) $conversation->getKey())
        ->and(data_get($payload, 'data.message_id'))->toBe((string) $message->getKey())
        ->and(data_get($payload, 'data.parish_id'))->toBe((string) $parish->getKey());
});

it('includes parish routing data for parish approval payloads', function (): void {
    $parish = Parish::factory()->create();
    $user = User::factory()->create([
        'home_parish_id' => $parish->getKey(),
    ]);

    $payload = (new ParishApprovalStatusChangedNotification(true))->toDatabase($user);

    expect(data_get($payload, 'type'))->toBe('PARISH_APPROVAL_STATUS_CHANGED')
        ->and(data_get($payload, 'data.is_parish_approved'))->toBeTrue()
        ->and(data_get($payload, 'data.parish_id'))->toBe((string) $parish->getKey());
});

it('normalizes bool values in push data to explicit strings', function (): void {
    $message = app(PushPayloadFactory::class)->makeTestMessage(
        token: 'test-token',
        platform: 'ios',
        title: 'Test',
        body: 'Body',
        type: 'TEST_MESSAGE',
        routingData: [
            'is_parish_approved' => true,
            'debug' => false,
        ],
    );

    expect($message->data)
        ->toMatchArray([
            'is_parish_approved' => 'true',
            'debug' => 'false',
        ]);
});

it('includes routing data for mass pending payloads', function (): void {
    $parish = Parish::factory()->create();
    $mass = Mass::query()->create([
        'parish_id' => $parish->getKey(),
        'intention_title' => 'Za parafian',
        'celebration_at' => now()->addHours(8),
        'mass_kind' => 'weekday',
        'mass_type' => 'individual',
        'status' => 'scheduled',
    ]);

    $payload = (new MassPendingReminderNotification($mass, '8h'))->toDatabase(new stdClass);

    expect(data_get($payload, 'type'))->toBe('MASS_PENDING')
        ->and(data_get($payload, 'data.mass_id'))->toBe((string) $mass->getKey())
        ->and(data_get($payload, 'data.parish_id'))->toBe((string) $parish->getKey())
        ->and(data_get($payload, 'data.reminder_key'))->toBe('8h');
});
