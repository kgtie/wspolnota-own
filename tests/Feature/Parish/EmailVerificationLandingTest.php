<?php

use App\Http\Controllers\Parish\EmailVerificationController;
use App\Models\Parish;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;

putenv('APP_URL_EU=https://wspolnotaeu.test');
$_ENV['APP_URL_EU'] = 'https://wspolnotaeu.test';
$_SERVER['APP_URL_EU'] = 'https://wspolnotaeu.test';

it('verifies email on the public parish page and renders success content', function (): void {
    $parish = Parish::factory()->predefined(1)->create([
        'slug' => 'zyrardow-test',
    ]);

    $user = User::factory()->unverifiedEmail()->create([
        'email' => 'parish-page-verify@example.com',
        'home_parish_id' => $parish->getKey(),
    ]);

    Event::fake();

    $verificationUrl = URL::temporarySignedRoute(
        'parish.verification.verify',
        now()->addMinutes(60),
        [
            'subdomain' => $parish->slug,
            'id' => $user->getKey(),
            'hash' => sha1($user->email),
        ],
    );

    $host = parse_url($verificationUrl, PHP_URL_HOST);
    $request = Request::create($verificationUrl, 'GET', [], [], [], [
        'HTTP_HOST' => $host,
        'HTTPS' => 'on',
    ]);

    $response = app(EmailVerificationController::class)(
        $request,
        $parish,
        $user->getKey(),
        sha1($user->email),
    );

    expect($response->getStatusCode())->toBe(200);
    expect($response->getContent())->toContain('Konto zostało aktywowane.');
    expect($response->getContent())->toContain($parish->name);

    Event::assertDispatched(Verified::class);
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});

it('renders a friendly invalid-link page for broken parish verification links', function (): void {
    $parish = Parish::factory()->predefined(2)->create([
        'slug' => 'mszczonow-test',
    ]);

    $user = User::factory()->unverifiedEmail()->create([
        'email' => 'broken-link@example.com',
        'home_parish_id' => $parish->getKey(),
    ]);

    $verificationUrl = URL::temporarySignedRoute(
        'parish.verification.verify',
        now()->addMinutes(60),
        [
            'subdomain' => $parish->slug,
            'id' => $user->getKey(),
            'hash' => sha1('wrong-email@example.com'),
        ],
    );

    $host = parse_url($verificationUrl, PHP_URL_HOST);
    $request = Request::create($verificationUrl, 'GET', [], [], [], [
        'HTTP_HOST' => $host,
        'HTTPS' => 'on',
    ]);

    $response = app(EmailVerificationController::class)(
        $request,
        $parish,
        $user->getKey(),
        sha1('wrong-email@example.com'),
    );

    expect($response->getStatusCode())->toBe(403);
    expect($response->getContent())->toContain('Nie udało się potwierdzić adresu email.');

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});
