<?php

use App\Http\Middleware\RedirectWwwToApex;
use App\Mail\LandingContactMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->withoutVite();
});

it('renders the landing homepage', function () {
    $this->get(route('landing.home'))
        ->assertOk()
        ->assertSee('Parafia, która nadąża za codziennością.')
        ->assertSee('Pricing')
        ->assertSee('application/ld+json', false)
        ->assertSee('rel="canonical"', false)
        ->assertSee('property="og:title"', false);
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
        ->assertSee('wspolnota@wspolnota.app')
        ->assertSee('ContactPage', false);
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

it('serves a sitemap for public pages', function () {
    $this->get(route('landing.sitemap'))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
        ->assertSee(route('landing.home'), false)
        ->assertSee(route('landing.contact'), false);
});

it('serves robots with sitemap reference', function () {
    $this->get(route('landing.robots'))
        ->assertOk()
        ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
        ->assertSee('Sitemap:')
        ->assertSee('/sitemap.xml');
});

it('marks guest auth pages as noindex', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('noindex,nofollow,noarchive', false);
});

it('redirects www host to apex host', function () {
    config()->set('app.url', 'https://wspolnota.test');

    $request = Request::create('https://www.wspolnota.test/kontakt?utm_source=test', 'GET');
    $middleware = new RedirectWwwToApex();
    $response = $middleware->handle($request, fn () => response('ok'));

    expect($response->getStatusCode())->toBe(301);
    expect($response->headers->get('Location'))->toBe('https://wspolnota.test/kontakt?utm_source=test');
});
