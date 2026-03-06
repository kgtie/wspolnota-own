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
