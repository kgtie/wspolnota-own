# API parafii: profil publiczny i kontakt

Ten dokument opisuje, jak aplikacja mobilna Flutter powinna korzystac z endpointow:

- `GET /api/v1/parishes`
- `GET /api/v1/parishes/{parishId}`
- `GET /api/v1/parishes/{parishId}/home-feed`

Dotyczy to szczegolnie:

- widocznosci publicznych danych kontaktowych,
- informacji o osobach w parafii,
- bezpiecznego renderowania profilu parafii w aplikacji.

## 1. Zasada ogolna

Backend rozroznia:

- dane zapisane w panelu administracyjnym,
- dane dopuszczone do publicznej publikacji.

API parafii zwraca tylko publicznie dozwolona warstwe kontaktu.

To oznacza:

- `email`, `phone`, `website`, `street`, `postal_code` w payloadzie sa juz przefiltrowane,
- jesli parafia ukryla dana informacje, API zwroci `null`,
- `city` pozostaje dostepne jako element identyfikacji parafii,
- dodatkowo klient dostaje jawne metadane `contact_visibility` i blok `public_contact`.

## 2. Pola parafii istotne dla klienta

Przyklad payloadu:

```json
{
  "id": "12",
  "name": "Parafia p.w. sw. Stanislawa",
  "short_name": "Parafia Wiskitki",
  "slug": "wiskitki",
  "email": "kontakt@parafia.pl",
  "phone": "+48 123 456 789",
  "website": "https://parafia.pl",
  "street": "ul. Koscielna 1",
  "postal_code": "96-315",
  "city": "Wiskitki",
  "diocese": "Diecezja Lowicka",
  "decanate": "Dekanat Wiskitki",
  "contact_visibility": {
    "email": true,
    "phone": true,
    "website": true,
    "address": true
  },
  "public_contact": {
    "email": "kontakt@parafia.pl",
    "phone": "+48 123 456 789",
    "website": "https://parafia.pl",
    "address": {
      "street": "ul. Koscielna 1",
      "postal_code": "96-315",
      "city": "Wiskitki"
    }
  },
  "staff_members": [
    {
      "name": "ks. Jan Kowalski",
      "title": "proboszcz"
    },
    {
      "name": "ks. Piotr Nowak",
      "title": "wikariusz"
    }
  ],
  "is_active": true,
  "avatar_url": "https://...",
  "cover_url": "https://..."
}
```

## 3. Znaczenie pol `contact_visibility`

Pole:

```json
"contact_visibility": {
  "email": true,
  "phone": false,
  "website": true,
  "address": false
}
```

oznacza, czy dana kategoria kontaktu jest publicznie dopuszczona przez parafie.

Flutter powinien traktowac te flagi jako:

- metadane do sterowania UI,
- dodatkowe zrodlo prawdy dla logiki renderowania,
- informacje diagnostyczna przy debugowaniu payloadu.

## 4. Znaczenie bloku `public_contact`

To jest preferowany blok do renderowania publicznego kontaktu.

Struktura:

```json
"public_contact": {
  "email": "kontakt@parafia.pl",
  "phone": "+48 123 456 789",
  "website": "https://parafia.pl",
  "address": {
    "street": "ul. Koscielna 1",
    "postal_code": "96-315",
    "city": "Wiskitki"
  }
}
```

Jesli dana nie jest publiczna:

- pole przyjmuje `null`,
- dla adresu cale `address` przyjmuje `null`.

## 5. Rekomendacja dla Fluttera

Do renderowania ekranu parafii:

- preferuj `public_contact`,
- `contact_visibility` uzywaj do decyzji o pokazywaniu sekcji,
- top-level `email`, `phone`, `website`, `street`, `postal_code` traktuj jako pola kompatybilnosciowe, ale juz publicznie przefiltrowane.

Minimalna logika:

```dart
final contact = parish.publicContact;

final showEmail = contact.email != null;
final showPhone = contact.phone != null;
final showWebsite = contact.website != null;
final showAddress = contact.address != null;
final showAnyContact = showEmail || showPhone || showWebsite || showAddress;
```

## 6. Model danych Flutter

Przykladowe DTO:

```dart
class ParishDto {
  final String id;
  final String name;
  final String shortName;
  final String slug;
  final String? email;
  final String? phone;
  final String? website;
  final String? street;
  final String? postalCode;
  final String city;
  final String? diocese;
  final String? decanate;
  final ParishContactVisibilityDto contactVisibility;
  final ParishPublicContactDto publicContact;
  final List<ParishStaffMemberDto> staffMembers;
  final bool isActive;
  final String? avatarUrl;
  final String? coverUrl;
}

class ParishContactVisibilityDto {
  final bool email;
  final bool phone;
  final bool website;
  final bool address;
}

class ParishPublicContactDto {
  final String? email;
  final String? phone;
  final String? website;
  final ParishPublicAddressDto? address;
}

class ParishPublicAddressDto {
  final String? street;
  final String? postalCode;
  final String city;
}

class ParishStaffMemberDto {
  final String name;
  final String title;
}
```

## 7. Renderowanie osob parafii

Pole:

```json
"staff_members": [
  { "name": "ks. Jan Kowalski", "title": "proboszcz" }
]
```

jest przeznaczone do prostego renderowania publicznego skladu parafii.

Reguly:

- lista moze byc pusta,
- backend zwraca tylko wpisy poprawne po normalizacji,
- kazdy element ma zawsze:
  - `name`
  - `title`

Rekomendacja UI:

- pokazuj sekcje tylko gdy `staff_members.isNotEmpty`,
- renderuj jako lista kart lub wierszy:
  - `name` jako glowna etykieta,
  - `title` jako podtytul/rola.

## 8. Kompatybilnosc wsteczna

Top-level pola:

- `email`
- `phone`
- `website`
- `street`
- `postal_code`

nadal istnieja, ale sa teraz polami publicznymi.

To znaczy:

- nie wolno zakladac, ze zawieraja komplet danych zapisanych w panelu,
- jezeli klient potrzebuje tylko warstwy publicznej, powinien przejsc na `public_contact`,
- jezeli klient ma stary kod, nie powinien sie wysypac, ale moze zobaczyc `null`.

## 9. Zachowanie dla ekranow

Lista parafii:

- pokazuj `short_name`, `city`, `avatar_url`
- kontakt pokazuj tylko warunkowo

Szczegoly parafii:

- uzywaj `public_contact`
- uzywaj `staff_members`
- `city`, `diocese`, `decanate` mozna pokazywac niezaleznie od kontaktu

Home feed parafii:

- blok `parish` ma ten sam kontrakt i te same zasady
- nie trzeba budowac osobnej logiki dla `/home-feed`

## 10. Edge cases

1. `contact_visibility.email = false` i `public_contact.email = null`
- nie pokazuj przycisku email ani linku `mailto:`

2. `contact_visibility.address = false`
- nie pokazuj ulicy i kodu pocztowego
- `city` nadal moze byc pokazane jako element tozsamosci parafii

3. `staff_members = []`
- ukryj cala sekcje osob parafii

4. `website` w payloadzie ma juz postac znormalizowana
- backend dodaje `https://`, jesli bylo potrzebne

## 11. Rekomendacja implementacyjna

Po stronie Fluttera najlepiej:

1. parsowac `public_contact` i `staff_members` do osobnych DTO,
2. nie budowac UI na podstawie surowych pol top-level,
3. traktowac `contact_visibility` jako wsparcie dla debugowania i warunkowego layoutu,
4. nie zakladac, ze brak pola oznacza blad backendu; `null` moze byc prawidlowym stanem biznesowym.
