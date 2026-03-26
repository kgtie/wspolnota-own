# API: Zatwierdzanie parafian kodem 9-cyfrowym lub QR

Ten dokument opisuje mobilne endpointy API v1 do zatwierdzania parafian przez administratora parafii lub superadministratora.

QR moze zawierac ten sam 9-cyfrowy kod, ktory jest uzywany w klasycznym przeplywie wpisywania kodu recznie.

## Zasady dostepu

Wszystkie endpointy z tego dokumentu:

- wymagaja `Bearer access token`
- wymagaja `email_verified`
- sa dostepne tylko dla:
  - administratora parafii
  - superadministratora

Reguly backendowe:

- administrator parafii moze dzialac tylko w swoich aktywnych parafiach
- superadministrator moze dzialac globalnie
- frontend moze ukrywac UI po `role_key`, ale ostateczna decyzja zawsze nalezy do backendu

## 1. Odczyt parafianina po kodzie

- `GET /api/v1/parish-approvals/by-code/{code}`

Przyklad:

- `GET /api/v1/parish-approvals/by-code/123456789`

Zwraca dane uzytkownika oraz flage `can_operator_approve`.

Przyklad odpowiedzi:

```json
{
  "data": {
    "user": {
      "id": "44",
      "login": "jan.kowalski",
      "first_name": "Jan",
      "last_name": "Kowalski",
      "email": "jan@example.com",
      "avatar_url": "https://example.com/storage/profiles/44/avatar.jpg",
      "default_parish_id": "12",
      "default_parish_name": "Parafia sw. Michala",
      "is_email_verified": true,
      "is_parish_approved": false,
      "parish_approval_code": "123456789",
      "can_operator_approve": true
    }
  }
}
```

### Ważne

- administrator parafii nie pobierze kodem parafianina z obcej parafii
- superadministrator moze pobrac dowolnego uzytkownika z aktywnej parafii

## 2. Zatwierdzenie parafianina

- `POST /api/v1/parish-approvals/{userId}/approve`

Request JSON:

```json
{
  "approval_code": "123456789",
  "parish_id": 12
}
```

Reguly backendowe:

- `approval_code` musi miec dokladnie 9 cyfr
- `parish_id` musi wskazywac aktywna parafie
- `parish_id` musi odpowiadac `home_parish_id` uzytkownika
- administrator parafii moze zatwierdzic tylko uzytkownika z parafii, ktora sam obsluguje
- superadministrator moze zatwierdzic uzytkownika z dowolnej aktywnej parafii
- user juz zatwierdzony zwroci konflikt

Przyklad odpowiedzi:

```json
{
  "data": {
    "status": "PARISHIONER_APPROVED",
    "user": {
      "id": "44",
      "login": "jan.kowalski",
      "first_name": "Jan",
      "last_name": "Kowalski",
      "email": "jan@example.com",
      "avatar_url": "https://example.com/storage/profiles/44/avatar.jpg",
      "default_parish_id": "12",
      "default_parish_name": "Parafia sw. Michala",
      "is_email_verified": true,
      "is_parish_approved": true,
      "parish_approval_code": "123456789",
      "can_operator_approve": false
    }
  }
}
```

## 3. Lista oczekujacych parafian

- `GET /api/v1/parish-approvals/pending?parish_id=12&search=jan`

Endpoint sluzy do listy oczekujacych parafian z prostym live search.

Przyklad odpowiedzi:

```json
{
  "data": {
    "parish_id": "12",
    "parish_name": "Parafia sw. Michala",
    "items": [
      {
        "id": "44",
        "first_name": "Jan",
        "last_name": "Kowalski",
        "login": "jan.kowalski",
        "email": "jan@example.com",
        "avatar_url": "https://example.com/storage/profiles/44/avatar.jpg",
        "default_parish_id": "12",
        "default_parish_name": "Parafia sw. Michala",
        "created_at": "2026-03-26T18:30:00Z",
        "is_email_verified": true,
        "is_parish_approved": false
      }
    ]
  }
}
```

### Search

Parametr `search` filtruje po:

- `full_name`
- `login`
- `email`

## Rekomendacja dla aplikacji mobilnej

Przeplyw pod kod 9-cyfrowy i QR moze byc taki sam:

1. operator skanuje QR albo wpisuje kod recznie
2. aplikacja wywoluje `GET /parish-approvals/by-code/{code}`
3. aplikacja wyswietla dane parafianina i sprawdza `can_operator_approve`
4. po potwierdzeniu operator wysyla `POST /parish-approvals/{userId}/approve`

To pozwala uzywac jednego backendowego modelu dla:

- wpisywania kodu
- skanowania QR
- listy oczekujacych parafian
