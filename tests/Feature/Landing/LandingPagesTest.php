<?php

use App\Mail\LandingContactMessage;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->withoutVite();
});

it('renders the landing homepage', function () {
    $this->get(route('landing.home'))
        ->assertOk()
        ->assertSee('Parafia, która nadąża za codziennością.')
        ->assertSee('Pricing');
});

it('renders the terms page', function () {
    $this->get(route('landing.terms'))
        ->assertOk()
        ->assertSee('Zasady korzystania z usługi Wspólnota');
});

it('renders the privacy page', function () {
    $this->get(route('landing.privacy'))
        ->assertOk()
        ->assertSee('Prywatność, dane i pliki cookies');
});

it('renders the contact page', function () {
    $this->get(route('landing.contact'))
        ->assertOk()
        ->assertSee('wspolnota@wspolnota.app');
});

it('sends a contact form email', function () {
    Mail::fake();

    $this->post(route('landing.contact.send'), [
        'name' => 'Konrad Gruza',
        'email' => 'kontakt@example.com',
        'parish' => 'Parafia Testowa',
        'phone' => '123456789',
        'subject' => 'Chcemy wdrożenie pilotażowe',
        'message' => 'Chcemy porozmawiać o pilotażu oraz o tym, jak pokazać screeny na stronie głównej.',
    ])->assertRedirect(route('landing.contact'));

    Mail::assertSent(LandingContactMessage::class, function (LandingContactMessage $mail) {
        return $mail->hasTo(config('services.wspolnota.contact_recipient', 'konrad@wspolnota.app'));
    });
});

it('validates the contact form payload', function () {
    Mail::fake();

    $this->from(route('landing.contact'))
        ->post(route('landing.contact.send'), [
            'name' => '',
            'email' => 'zly-email',
            'subject' => '',
            'message' => 'za krotka',
        ])
        ->assertRedirect(route('landing.contact'))
        ->assertSessionHasErrors(['name', 'email', 'subject', 'message']);

    Mail::assertNothingSent();
});
