<?php

use App\Models\Parish;

it('exposes normalized staff members in parish api payloads', function (): void {
    $parish = Parish::factory()->create([
        'settings' => [
            'staff_members' => [
                ['name' => ' ks. Jan Kowalski ', 'title' => ' proboszcz '],
                ['name' => 'ks. Piotr Nowak', 'title' => 'wikariusz'],
                ['name' => 'Brak roli', 'title' => ''],
            ],
        ],
    ]);

    $this->getJson('/api/v1/parishes/'.$parish->getKey())
        ->assertOk()
        ->assertJsonCount(2, 'data.parish.staff_members')
        ->assertJsonPath('data.parish.staff_members.0.name', 'ks. Jan Kowalski')
        ->assertJsonPath('data.parish.staff_members.0.title', 'proboszcz')
        ->assertJsonPath('data.parish.staff_members.1.name', 'ks. Piotr Nowak')
        ->assertJsonPath('data.parish.staff_members.1.title', 'wikariusz')
        ->assertJsonPath('data.parish.contact_visibility.email', true)
        ->assertJsonPath('data.parish.contact_visibility.phone', true)
        ->assertJsonPath('data.parish.contact_visibility.website', true)
        ->assertJsonPath('data.parish.contact_visibility.address', true);
});

it('includes staff members in the parish home feed payload', function (): void {
    $parish = Parish::factory()->create([
        'settings' => [
            'staff_members' => [
                ['name' => 'ks. Adam Zielinski', 'title' => 'rezydent'],
            ],
        ],
    ]);

    $this->getJson('/api/v1/parishes/'.$parish->getKey().'/home-feed')
        ->assertOk()
        ->assertJsonPath('data.parish.staff_members.0.name', 'ks. Adam Zielinski')
        ->assertJsonPath('data.parish.staff_members.0.title', 'rezydent');
});

it('hides non-public contact fields in parish api payloads and exposes visibility metadata', function (): void {
    $parish = Parish::factory()->create([
        'email' => 'kontakt@ukryta-parafia.test',
        'phone' => '+48 111 222 333',
        'website' => 'ukryta-parafia.test',
        'street' => 'Cicha 7',
        'postal_code' => '00-123',
        'city' => 'Lodz',
        'settings' => [
            'public_email' => false,
            'public_phone' => false,
            'public_website' => false,
            'public_address' => false,
        ],
    ]);

    $this->getJson('/api/v1/parishes/'.$parish->getKey())
        ->assertOk()
        ->assertJsonPath('data.parish.email', null)
        ->assertJsonPath('data.parish.phone', null)
        ->assertJsonPath('data.parish.website', null)
        ->assertJsonPath('data.parish.street', null)
        ->assertJsonPath('data.parish.postal_code', null)
        ->assertJsonPath('data.parish.city', 'Lodz')
        ->assertJsonPath('data.parish.contact_visibility.email', false)
        ->assertJsonPath('data.parish.contact_visibility.phone', false)
        ->assertJsonPath('data.parish.contact_visibility.website', false)
        ->assertJsonPath('data.parish.contact_visibility.address', false)
        ->assertJsonPath('data.parish.public_contact.email', null)
        ->assertJsonPath('data.parish.public_contact.phone', null)
        ->assertJsonPath('data.parish.public_contact.website', null)
        ->assertJsonPath('data.parish.public_contact.address', null);
});

it('returns public contact and staff data in parish api payloads', function (): void {
    $parish = Parish::factory()->create([
        'email' => 'kontakt@jawna-parafia.test',
        'phone' => '+48 444 555 666',
        'website' => 'jawna-parafia.test',
        'street' => 'Klasztorna 3',
        'postal_code' => '31-001',
        'city' => 'Krakow',
        'settings' => [
            'staff_members' => [
                ['name' => 'ks. Tomasz Lis', 'title' => 'proboszcz'],
            ],
        ],
    ]);

    $this->getJson('/api/v1/parishes/'.$parish->getKey())
        ->assertOk()
        ->assertJsonPath('data.parish.email', 'kontakt@jawna-parafia.test')
        ->assertJsonPath('data.parish.phone', '+48 444 555 666')
        ->assertJsonPath('data.parish.website', 'https://jawna-parafia.test')
        ->assertJsonPath('data.parish.public_contact.email', 'kontakt@jawna-parafia.test')
        ->assertJsonPath('data.parish.public_contact.phone', '+48 444 555 666')
        ->assertJsonPath('data.parish.public_contact.website', 'https://jawna-parafia.test')
        ->assertJsonPath('data.parish.public_contact.address.street', 'Klasztorna 3')
        ->assertJsonPath('data.parish.public_contact.address.postal_code', '31-001')
        ->assertJsonPath('data.parish.public_contact.address.city', 'Krakow')
        ->assertJsonPath('data.parish.staff_members.0.name', 'ks. Tomasz Lis')
        ->assertJsonPath('data.parish.staff_members.0.title', 'proboszcz');
});
