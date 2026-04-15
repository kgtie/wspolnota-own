<?php

use App\Models\OfficeConversation;
use App\Models\OfficeMessage;
use App\Models\Parish;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

function loginForOfficeApi(User $user): string
{
    return test()->postJson('/api/v1/auth/login', [
        'login' => $user->email,
        'password' => 'Secret#2026',
        'device' => [
            'platform' => 'android',
            'device_id' => 'device-office-tests',
            'device_name' => 'Pixel',
            'app_version' => '1.0.0',
        ],
    ])->assertOk()->json('data.tokens.access_token');
}

it('allows only the conversation participant to download office attachments through api', function (): void {
    Storage::fake('office');

    $parish = Parish::factory()->create();

    $owner = User::factory()->verified()->create([
        'email' => 'owner-office@example.com',
        'password' => Hash::make('Secret#2026'),
        'status' => 'active',
        'home_parish_id' => $parish->getKey(),
        'email_verified_at' => now(),
    ]);

    $other = User::factory()->verified()->create([
        'email' => 'other-office@example.com',
        'password' => Hash::make('Secret#2026'),
        'status' => 'active',
        'home_parish_id' => $parish->getKey(),
        'email_verified_at' => now(),
    ]);

    $priest = User::factory()->admin()->create([
        'status' => 'active',
    ]);

    $conversation = OfficeConversation::query()->create([
        'parish_id' => $parish->getKey(),
        'parishioner_user_id' => $owner->getKey(),
        'priest_user_id' => $priest->getKey(),
        'status' => OfficeConversation::STATUS_OPEN,
        'last_message_at' => now(),
    ]);

    $message = OfficeMessage::query()->create([
        'office_conversation_id' => $conversation->getKey(),
        'sender_user_id' => $owner->getKey(),
        'body' => 'Załącznik',
        'has_attachments' => true,
    ]);

    $message->addMedia(UploadedFile::fake()->create('test.pdf', 32, 'application/pdf'))
        ->toMediaCollection('attachments', 'office');

    $attachment = $message->getFirstMedia('attachments');

    expect($attachment)->not->toBeNull();

    $ownerAccess = loginForOfficeApi($owner);
    $otherAccess = loginForOfficeApi($other);

    $this->withHeader('Authorization', 'Bearer '.$ownerAccess)
        ->get('/api/v1/office/chats/'.$conversation->getKey().'/attachments/'.$attachment->getKey())
        ->assertOk()
        ->assertHeader('content-disposition', 'attachment; filename=test.pdf');

    $this->withHeader('Authorization', 'Bearer '.$otherAccess)
        ->getJson('/api/v1/office/chats/'.$conversation->getKey().'/attachments/'.$attachment->getKey())
        ->assertStatus(404)
        ->assertJsonPath('error.code', 'NOT_FOUND');
});

it('lists office staff for the requester parish with priority and pastor first', function (): void {
    $parish = Parish::factory()->create();

    $parishioner = User::factory()->verified()->create([
        'email' => 'parishioner-office@example.com',
        'password' => Hash::make('Secret#2026'),
        'status' => 'active',
        'home_parish_id' => $parish->getKey(),
        'email_verified_at' => now(),
    ]);

    $pastor = User::factory()->admin()->create([
        'status' => 'active',
        'full_name' => 'ks. Proboszcz',
    ]);
    $assistant = User::factory()->admin()->create([
        'status' => 'active',
        'full_name' => 'Administrator Pomocniczy',
    ]);

    $pastor->managedParishes()->attach($parish->getKey(), [
        'is_active' => true,
        'assigned_at' => now(),
        'note' => 'Proboszcz',
    ]);
    $assistant->managedParishes()->attach($parish->getKey(), [
        'is_active' => true,
        'assigned_at' => now(),
        'note' => 'Administrator pomocniczy',
    ]);

    $access = loginForOfficeApi($parishioner);

    $this->withHeader('Authorization', 'Bearer '.$access)
        ->getJson('/api/v1/office/parishes/'.$parish->getKey().'/staff')
        ->assertOk()
        ->assertJsonPath('data.items.0.id', (string) $pastor->getKey())
        ->assertJsonPath('data.items.0.role_key', 'pastor')
        ->assertJsonPath('data.items.0.is_default_recipient', true)
        ->assertJsonPath('data.items.1.id', (string) $assistant->getKey())
        ->assertJsonPath('data.items.1.role_key', 'assistant_admin');
});

it('creates office conversation with explicitly selected recipient', function (): void {
    $parish = Parish::factory()->create();

    $parishioner = User::factory()->verified()->create([
        'email' => 'chat-owner@example.com',
        'password' => Hash::make('Secret#2026'),
        'status' => 'active',
        'home_parish_id' => $parish->getKey(),
        'email_verified_at' => now(),
    ]);

    $pastor = User::factory()->admin()->create([
        'status' => 'active',
        'full_name' => 'ks. Proboszcz',
    ]);
    $assistant = User::factory()->admin()->create([
        'status' => 'active',
        'full_name' => 'Administrator Pomocniczy',
    ]);

    $pastor->managedParishes()->attach($parish->getKey(), [
        'is_active' => true,
        'assigned_at' => now(),
        'note' => 'Proboszcz',
    ]);
    $assistant->managedParishes()->attach($parish->getKey(), [
        'is_active' => true,
        'assigned_at' => now(),
        'note' => 'Administrator pomocniczy',
    ]);

    $access = loginForOfficeApi($parishioner);

    $response = $this->withHeader('Authorization', 'Bearer '.$access)
        ->postJson('/api/v1/office/chats', [
            'parish_id' => $parish->getKey(),
            'recipient_user_id' => $assistant->getKey(),
            'message' => 'Dzien dobry, pisze do pomocniczego administratora.',
        ])
        ->assertCreated()
        ->assertJsonPath('data.chat.recipient_user_id', (string) $assistant->getKey())
        ->assertJsonPath('data.chat.recipient.role_key', 'assistant_admin');

    $chatId = (int) $response->json('data.chat.id');

    expect(OfficeConversation::query()->findOrFail($chatId)->priest_user_id)->toBe($assistant->getKey());
});

it('returns parish user directory filtered by scope for office consumers', function (): void {
    $parish = Parish::factory()->create();

    $requester = User::factory()->verified()->create([
        'email' => 'directory-owner@example.com',
        'password' => Hash::make('Secret#2026'),
        'status' => 'active',
        'home_parish_id' => $parish->getKey(),
        'email_verified_at' => now(),
    ]);

    $pastor = User::factory()->admin()->create([
        'status' => 'active',
    ]);
    $pastor->managedParishes()->attach($parish->getKey(), [
        'is_active' => true,
        'assigned_at' => now(),
        'note' => 'Proboszcz',
    ]);

    $allUser = User::factory()->create([
        'home_parish_id' => $parish->getKey(),
        'status' => 'active',
        'email_verified_at' => null,
        'is_user_verified' => false,
    ]);
    $emailVerifiedUser = User::factory()->create([
        'home_parish_id' => $parish->getKey(),
        'status' => 'active',
        'email_verified_at' => now(),
        'is_user_verified' => false,
    ]);
    $parishApprovedUser = User::factory()->verified()->create([
        'home_parish_id' => $parish->getKey(),
        'status' => 'active',
        'email_verified_at' => now(),
    ]);

    $access = loginForOfficeApi($requester);

    $this->withHeader('Authorization', 'Bearer '.$access)
        ->getJson('/api/v1/office/parishes/'.$parish->getKey().'/users?scope=all')
        ->assertOk()
        ->assertJsonFragment(['id' => (string) $allUser->getKey()])
        ->assertJsonFragment(['id' => (string) $emailVerifiedUser->getKey()])
        ->assertJsonFragment(['id' => (string) $parishApprovedUser->getKey()]);

    $this->withHeader('Authorization', 'Bearer '.$access)
        ->getJson('/api/v1/office/parishes/'.$parish->getKey().'/users?scope=email_verified')
        ->assertOk()
        ->assertJsonMissing(['id' => (string) $allUser->getKey()])
        ->assertJsonFragment(['id' => (string) $emailVerifiedUser->getKey()])
        ->assertJsonFragment(['id' => (string) $parishApprovedUser->getKey()]);

    $this->withHeader('Authorization', 'Bearer '.$access)
        ->getJson('/api/v1/office/parishes/'.$parish->getKey().'/users?scope=parish_approved')
        ->assertOk()
        ->assertJsonMissing(['id' => (string) $allUser->getKey()])
        ->assertJsonMissing(['id' => (string) $emailVerifiedUser->getKey()])
        ->assertJsonFragment(['id' => (string) $parishApprovedUser->getKey()]);
});
