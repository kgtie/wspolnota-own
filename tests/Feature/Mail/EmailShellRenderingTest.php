<?php

use App\Mail\CommunicationBroadcastMessage;
use App\Models\Parish;

it('renders the shared email shell with service logo, service link, parish link and mobile note', function (): void {
    $parish = Parish::factory()->create([
        'slug' => 'mail-shell-test',
        'name' => 'Parafia Mail Shell',
    ]);

    $mail = new CommunicationBroadcastMessage(
        subjectLine: 'Nowa kampania',
        messageBody: 'Fallback tresci',
        senderName: 'Zespol Wspolnoty',
        senderEmail: 'hello@example.com',
        preheader: 'Preheader kampanii',
        contentHtml: '<h2>Nowa tresc</h2><p>Mail testowy dla nowego shella.</p>',
        ctaLabel: 'Otworz Wspolnote',
        ctaUrl: 'https://example.com/kampania',
        parish: $parish,
        campaignName: 'Kampania testowa',
    );

    $html = $mail->render();

    expect($html)
        ->toContain('wspolnota-logo-placeholder.svg')
        ->toContain(route('landing.home'))
        ->toContain(route('parish.home', ['subdomain' => $parish]))
        ->toContain('Przejdz do uslugi')
        ->toContain('Strona parafii')
        ->toContain('Nowa tresc')
        ->toContain('Mail testowy dla nowego shella.')
        ->toContain('iOS i Androidem');
});

it('falls back to the public parish pages link when campaign has no parish branding', function (): void {
    $mail = new CommunicationBroadcastMessage(
        subjectLine: 'Globalna kampania',
        messageBody: 'Fallback tresci',
        contentHtml: '<p>Globalny mailing.</p>',
        campaignName: 'Globalna kampania',
    );

    $html = $mail->render();

    expect($html)
        ->toContain('Publiczne strony parafii')
        ->toContain(route('landing.home'));
});
