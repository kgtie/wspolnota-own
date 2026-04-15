<?php

use App\Http\Controllers\Parish\HomeController;
use App\Models\Parish;

it('renders staff members on the active public parish page', function (): void {
    $parish = Parish::factory()->create([
        'slug' => 'aktywni-duszpasterze',
        'is_active' => true,
        'settings' => [
            'staff_members' => [
                ['name' => 'ks. Jan Kowalski', 'title' => 'proboszcz'],
                ['name' => 'ks. Piotr Nowak', 'title' => 'wikariusz'],
            ],
        ],
    ]);

    $html = app(HomeController::class)->index($parish)->render();

    expect($html)
        ->toContain('Duszpasterze i posługa')
        ->toContain('ks. Jan Kowalski')
        ->toContain('proboszcz')
        ->toContain('ks. Piotr Nowak')
        ->toContain('wikariusz');
});

it('renders staff members on the inactive public parish page', function (): void {
    $parish = Parish::factory()->inactive()->create([
        'slug' => 'nieaktywni-duszpasterze',
        'settings' => [
            'staff_members' => [
                ['name' => 'ks. Adam Zielinski', 'title' => 'rezydent'],
            ],
        ],
    ]);

    $html = app(HomeController::class)->index($parish)->render();

    expect($html)
        ->toContain('Duszpasterze i posługa')
        ->toContain('ks. Adam Zielinski')
        ->toContain('rezydent')
        ->toContain('Aplikacja mobilna')
        ->toContain('Wspólnota jest dostępna także w sklepach mobilnych');
});

it('hides contact details on the active public parish page when visibility is disabled', function (): void {
    $parish = Parish::factory()->create([
        'slug' => 'ukryte-kontakty',
        'email' => 'sekretny-kontakt@example.test',
        'phone' => '+48 999 777 111',
        'website' => 'https://sekretna-parafia.test',
        'street' => 'Ukryta 99',
        'postal_code' => '00-999',
        'city' => 'Warszawa',
        'settings' => [
            'public_email' => false,
            'public_phone' => false,
            'public_website' => false,
            'public_address' => false,
        ],
    ]);

    $html = app(HomeController::class)->index($parish)->render();

    expect($html)
        ->not->toContain('sekretny-kontakt@example.test')
        ->not->toContain('+48 999 777 111')
        ->not->toContain('sekretna-parafia.test')
        ->not->toContain('Ukryta 99')
        ->not->toContain('Kontakt z parafią');
});
