<?php

use App\Models\Parish;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

function loginForParishApprovalsApi(User $user): string
{
    return test()->postJson('/api/v1/auth/login', [
        'login' => $user->email,
        'password' => 'Secret#2026',
        'device' => [
            'platform' => 'android',
            'device_id' => 'device-parish-approvals',
            'device_name' => 'Pixel',
            'app_version' => '1.0.0',
        ],
    ])->assertOk()->json('data.tokens.access_token');
}

it('returns parishioner by approval code for parish admin from owned parish', function (): void {
    $parish = Parish::factory()->create([
        'short_name' => 'Sw. Michala',
    ]);

    $admin = User::factory()->admin()->create([
        'email' => 'admin-approval@example.com',
        'password' => Hash::make('Secret#2026'),
        'status' => 'active',
    ]);
    $admin->managedParishes()->attach($parish->getKey(), [
        'is_active' => true,
        'assigned_at' => now(),
        'note' => 'Proboszcz',
    ]);

    $parishioner = User::factory()->unverifiedEmail()->create([
        'email' => 'parafianin@example.com',
        'name' => 'jan.kowalski',
        'full_name' => 'Jan Kowalski',
        'status' => 'active',
        'home_parish_id' => $parish->getKey(),
        'verification_code' => '123456789',
        'is_user_verified' => false,
    ]);

    $access = loginForParishApprovalsApi($admin);

    $this->withHeader('Authorization', 'Bearer '.$access)
        ->getJson('/api/v1/parish-approvals/by-code/123456789')
        ->assertOk()
        ->assertJsonPath('data.user.id', (string) $parishioner->getKey())
        ->assertJsonPath('data.user.login', 'jan.kowalski')
        ->assertJsonPath('data.user.default_parish_id', (string) $parish->getKey())
        ->assertJsonPath('data.user.default_parish_name', 'Sw. Michala')
        ->assertJsonPath('data.user.is_email_verified', false)
        ->assertJsonPath('data.user.is_parish_approved', false)
        ->assertJsonPath('data.user.parish_approval_code', '123456789')
        ->assertJsonPath('data.user.can_operator_approve', true);
});

it('does not reveal parishioner by code to parish admin from another parish', function (): void {
    $ownedParish = Parish::factory()->create();
    $foreignParish = Parish::factory()->create();

    $admin = User::factory()->admin()->create([
        'email' => 'admin-foreign@example.com',
        'password' => Hash::make('Secret#2026'),
        'status' => 'active',
    ]);
    $admin->managedParishes()->attach($ownedParish->getKey(), [
        'is_active' => true,
        'assigned_at' => now(),
        'note' => 'Proboszcz',
    ]);

    User::factory()->create([
        'status' => 'active',
        'home_parish_id' => $foreignParish->getKey(),
        'verification_code' => '987654321',
        'is_user_verified' => false,
    ]);

    $access = loginForParishApprovalsApi($admin);

    $this->withHeader('Authorization', 'Bearer '.$access)
        ->getJson('/api/v1/parish-approvals/by-code/987654321')
        ->assertStatus(404)
        ->assertJsonPath('error.code', 'NOT_FOUND');
});

it('approves parishioner by code for owned parish and records verifier', function (): void {
    $parish = Parish::factory()->create();

    $admin = User::factory()->admin()->create([
        'email' => 'admin-approve@example.com',
        'password' => Hash::make('Secret#2026'),
        'status' => 'active',
    ]);
    $admin->managedParishes()->attach($parish->getKey(), [
        'is_active' => true,
        'assigned_at' => now(),
        'note' => 'Proboszcz',
    ]);

    $parishioner = User::factory()->create([
        'email' => 'approval-target@example.com',
        'status' => 'active',
        'home_parish_id' => $parish->getKey(),
        'verification_code' => '555666777',
        'is_user_verified' => false,
    ]);

    $access = loginForParishApprovalsApi($admin);

    $this->withHeader('Authorization', 'Bearer '.$access)
        ->postJson('/api/v1/parish-approvals/'.$parishioner->getKey().'/approve', [
            'approval_code' => '555666777',
            'parish_id' => $parish->getKey(),
        ])
        ->assertOk()
        ->assertJsonPath('data.status', 'PARISHIONER_APPROVED')
        ->assertJsonPath('data.user.id', (string) $parishioner->getKey())
        ->assertJsonPath('data.user.is_parish_approved', true)
        ->assertJsonPath('data.user.can_operator_approve', false);

    $parishioner->refresh();

    expect($parishioner->is_user_verified)->toBeTrue()
        ->and($parishioner->verified_by_user_id)->toBe($admin->getKey());
});

it('lists pending parishioners for selected parish with search and allows superadmin on any parish', function (): void {
    $parish = Parish::factory()->create([
        'short_name' => 'Matki Bozej',
    ]);

    $superadmin = User::factory()->superAdmin()->create([
        'email' => 'superadmin-approval@example.com',
        'password' => Hash::make('Secret#2026'),
        'status' => 'active',
    ]);

    $jan = User::factory()->create([
        'name' => 'jan.nowak',
        'full_name' => 'Jan Nowak',
        'email' => 'jan@example.com',
        'status' => 'active',
        'home_parish_id' => $parish->getKey(),
        'is_user_verified' => false,
    ]);

    User::factory()->create([
        'name' => 'anna.kowalska',
        'full_name' => 'Anna Kowalska',
        'email' => 'anna@example.com',
        'status' => 'active',
        'home_parish_id' => $parish->getKey(),
        'is_user_verified' => false,
    ]);

    User::factory()->verified()->create([
        'name' => 'marek.verified',
        'full_name' => 'Marek Verified',
        'email' => 'marek@example.com',
        'status' => 'active',
        'home_parish_id' => $parish->getKey(),
    ]);

    $access = loginForParishApprovalsApi($superadmin);

    $this->withHeader('Authorization', 'Bearer '.$access)
        ->getJson('/api/v1/parish-approvals/pending?parish_id='.$parish->getKey().'&search=jan')
        ->assertOk()
        ->assertJsonPath('data.parish_id', (string) $parish->getKey())
        ->assertJsonPath('data.parish_name', 'Matki Bozej')
        ->assertJsonCount(1, 'data.items')
        ->assertJsonPath('data.items.0.id', (string) $jan->getKey())
        ->assertJsonPath('data.items.0.first_name', 'Jan')
        ->assertJsonPath('data.items.0.last_name', 'Nowak')
        ->assertJsonPath('data.items.0.is_parish_approved', false);
});
