# API: Kancelaria online, katalog odbiorcow i wybieranie adresata

Ten dokument opisuje nowe endpointy API v1 potrzebne do tworzenia rozmow w kancelarii online z poziomu aplikacji mobilnej.

## Zasady dostepu

Wszystkie endpointy z tego dokumentu:

- wymagaja `Bearer access token`
- wymagaja `email_verified`
- wymagaja `parish_approved`
- sa ograniczone do wlasnej parafii uzytkownika

To oznacza, ze uzytkownik moze:

- pobrac liste obslugujacych kancelarie tylko dla swojej parafii
- pobrac katalog uzytkownikow tylko dla swojej parafii
- utworzyc rozmowe tylko z obsluga kancelarii swojej parafii

## 1. Lista osob obslugujacych kancelarie

- `GET /api/v1/office/parishes/{parishId}/staff`

Endpoint zwraca uporzadkowana liste osob obslugujacych kancelarie dla danej parafii.

Backend zwraca:

- dane odbiorcy
- role techniczno-biznesowa
- priorytet sortowania
- flage `is_default_recipient`

Najwazniejsze role:

- `pastor`
- `moderator`
- `assistant_admin`
- `superadmin`
- `admin`
- `custom`

Przyklad odpowiedzi:

```json
{
  "data": {
    "parish_id": "12",
    "items": [
      {
        "id": "5",
        "display_name": "ks. Jan Nowak",
        "avatar_url": "https://example.com/storage/profiles/5/avatar.jpg",
        "role_key": "pastor",
        "role_label": "Proboszcz",
        "priority": 300,
        "assignment_note": "Proboszcz",
        "is_default_recipient": true
      },
      {
        "id": "8",
        "display_name": "Anna Kowalska",
        "avatar_url": null,
        "role_key": "assistant_admin",
        "role_label": "Administrator pomocniczy",
        "priority": 180,
        "assignment_note": "Administrator pomocniczy",
        "is_default_recipient": false
      }
    ]
  }
}
```

### Jak tego uzyc w aplikacji

- pokaz liste odbiorcow przy tworzeniu nowej rozmowy
- domyslnie zaznacz pozycje z `is_default_recipient = true`
- jesli chcesz, pokaz proboszcza na samej gorze bez dodatkowego sortowania po stronie klienta

## 2. Katalog uzytkownikow parafii

- `GET /api/v1/office/parishes/{parishId}/users`

Endpoint testowy pod przyszle scenariusze rozmow miedzy uzytkownikami lub grupami uzytkownikow.

Dostepne zakresy:

- `scope=all`
- `scope=email_verified`
- `scope=parish_approved`

Przyklad:

- `GET /api/v1/office/parishes/12/users?scope=parish_approved`

Przyklad odpowiedzi:

```json
{
  "data": {
    "parish_id": "12",
    "scope": "parish_approved",
    "items": [
      {
        "id": "44",
        "display_name": "Jan Kowalski",
        "avatar_url": "https://example.com/storage/profiles/44/avatar.jpg",
        "default_parish_id": "12",
        "is_email_verified": true,
        "is_parish_approved": true,
        "created_at": "2026-03-20T10:15:00Z"
      }
    ]
  }
}
```

### Uwagi

- endpoint nie zwraca emaila ani loginu
- endpoint zwraca tylko aktywnych zwyklych uzytkownikow danej parafii
- obecnie jest to katalog pomocniczy, nie lista kontaktow gotowych do prywatnego czatu

## 3. Tworzenie rozmowy z wybranym odbiorca

- `POST /api/v1/office/chats`

Request JSON:

```json
{
  "parish_id": 12,
  "recipient_user_id": 8,
  "message": "Dzien dobry, chcialbym umowic sprawe w kancelarii."
}
```

Zasady:

- `recipient_user_id` jest opcjonalne
- jesli go nie wyslesz, backend wybierze domyslnego odbiorce o najwyzszym priorytecie
- jesli je wyslesz, musi wskazywac osobe z endpointu `staff` dla tej parafii

Przyklad odpowiedzi:

```json
{
  "data": {
    "chat": {
      "id": "31",
      "uuid": "f3f83388-0f72-47a6-a84c-8e06ac7db35a",
      "parish_id": "12",
      "parish_name": "Parafia sw. Michala",
      "status": "open",
      "recipient_user_id": "8",
      "recipient": {
        "id": "8",
        "display_name": "Anna Kowalska",
        "avatar_url": null,
        "role_key": "assistant_admin",
        "role_label": "Administrator pomocniczy",
        "priority": 180
      },
      "last_message_at": "2026-03-23T10:40:00Z",
      "last_message_preview": null,
      "created_at": "2026-03-23T10:40:00Z",
      "updated_at": "2026-03-23T10:40:00Z"
    }
  }
}
```

## Rekomendacja dla React Native

- przy otwieraniu formularza nowej rozmowy:
  - pobierz `staff`
  - pokaz picker odbiorcy
  - domyslnie zaznacz `is_default_recipient = true`
- jesli planujesz dalszy rozwoj prywatnych chatow:
  - traktuj endpoint `users` jako techniczny katalog parafialny
  - nie zakladaj jeszcze, ze wszystkie zwrocone osoby mozna od razu wykorzystac jako gotowych adresatow prywatnych rozmow
