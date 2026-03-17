# Kontrakt push FCM dla mobile (Android + iOS)

Ten dokument jest zrodlem prawdy dla zespolu mobile. Backend wysyla push przez Firebase Cloud Messaging HTTP v1. Dla Androida i iOS `provider` jest zawsze `fcm`.

## 1. Rejestracja urzadzenia

Endpoint:

```http
POST /api/v1/me/devices
Authorization: Bearer <token>
Content-Type: application/json
```

Request body:

```json
{
  "provider": "fcm",
  "platform": "android",
  "push_token": "<fcm_token>",
  "device_id": "android-unique-device-id",
  "device_name": "Pixel 9",
  "app_version": "1.2.3",
  "locale": "pl-PL",
  "timezone": "Europe/Warsaw",
  "permission_status": "authorized",
  "parish_id": "12"
}
```

Pola:

- `provider`: zawsze `fcm`
- `platform`: `android` albo `ios`
- `push_token`: aktualny token FCM
- `device_id`: stabilny identyfikator urzadzenia po stronie aplikacji
- `device_name`: model / nazwa marketingowa
- `app_version`: wersja aplikacji
- `locale`: np. `pl-PL`
- `timezone`: np. `Europe/Warsaw`
- `permission_status`: `authorized`, `provisional`, `denied`, `not_determined`
- `parish_id`: opcjonalny aktualny kontekst parafii przypisany do urzadzenia

Preferencje powiadomien zapisywane w backendzie:

```http
PATCH /api/v1/me/notification-preferences
Authorization: Bearer <token>
Content-Type: application/json
```

Przyklad:

```json
{
  "news": { "push": true, "email": false },
  "announcements": { "push": true, "email": true },
  "mass_reminders": { "push": true, "email": true },
  "office_messages": { "push": true, "email": true },
  "parish_approval_status": { "push": true, "email": true },
  "auth_security": { "push": false, "email": true }
}
```

Logout:

```http
DELETE /api/v1/me/devices/{device_id}
Authorization: Bearer <token>
```

## 2. Ogolny ksztalt payloadu FCM

Backend wysyla jeden wspolny kontrakt dla Androida i iOS.

Przyklad FCM HTTP v1 request:

```json
{
  "message": {
    "token": "<fcm_token>",
    "notification": {
      "title": "Nowa aktualnosc w parafii",
      "body": "Dodano nowa aktualnosc: Rekolekcje wielkopostne"
    },
    "data": {
      "notification_id": "0f4b6e21-0f47-47b6-ae9d-cc71f6e67f95",
      "type": "NEWS_CREATED",
      "parish_id": "12",
      "news_id": "73"
    },
    "android": {
      "priority": "NORMAL",
      "ttl": "3600s",
      "collapse_key": "news-12",
      "notification": {
        "channel_id": "default",
        "sound": "default"
      }
    },
    "apns": {
      "headers": {
        "apns-priority": "5",
        "apns-collapse-id": "news-12"
      },
      "payload": {
        "aps": {
          "alert": {
            "title": "Nowa aktualnosc w parafii",
            "body": "Dodano nowa aktualnosc: Rekolekcje wielkopostne"
          },
          "sound": "default"
        }
      }
    }
  }
}
```

Zasady:

- `notification.title` i `notification.body` sa zawsze obecne dla wspieranych typow.
- `data` zawiera tylko stringi. Wartosci bool sa jawnie wysylane jako `true` albo `false`, a liczby jako string.
- Klient ma traktowac `data.notification_id` i `data.type` jako podstawowe pola routingu.
- iOS tez dostaje payload przez FCM. APNs jest tylko transportem pod spodem.

## 3. Pola wspolne dla kazdego push

Kazdy wspierany typ zawiera:

- `notification_id`: id rekordu `notifications` z backendu
- `type`: typ domenowy notyfikacji

Kazdy klient musi:

1. odczytac `data.type`
2. odczytac `data.notification_id`
3. zmapowac payload na ekran / flow
4. po otwarciu mozliwie zsynchronizowac feed notyfikacji z backendem

## 4. Typy payloadow i routing

### 4.1 `NEWS_CREATED`

Przeznaczenie:

- nowa aktualnosc parafialna

`data`:

```json
{
  "notification_id": "<uuid>",
  "type": "NEWS_CREATED",
  "parish_id": "<parish_id>",
  "news_id": "<news_id>"
}
```

Routowanie mobile:

- Android: ekran szczegolow aktualnosci
- iOS: ekran szczegolow aktualnosci

Minimalne wymagania klienta:

- jesli jest `news_id`, otworz widok news detail
- jesli jest tez `parish_id`, ustaw lub zweryfikuj kontekst parafii

Strategia dostarczania:

- priorytet: `normal`
- collapse: `news-{parish_id}` gdy wlaczone w ustawieniach backendu

### 4.2 `ANNOUNCEMENTS_PACKAGE_PUBLISHED`

Przeznaczenie:

- nowy pakiet ogloszen parafialnych

`data`:

```json
{
  "notification_id": "<uuid>",
  "type": "ANNOUNCEMENTS_PACKAGE_PUBLISHED",
  "parish_id": "<parish_id>",
  "announcement_set_id": "<announcement_set_id>"
}
```

Routowanie mobile:

- ekran ogloszen / szczegoly pakietu ogloszen

Strategia dostarczania:

- priorytet: `normal`
- collapse: `announcements-{parish_id}` gdy wlaczone w ustawieniach backendu

### 4.3 `OFFICE_MESSAGE_RECEIVED`

Przeznaczenie:

- nowa wiadomosc w kancelarii online

`data`:

```json
{
  "notification_id": "<uuid>",
  "type": "OFFICE_MESSAGE_RECEIVED",
  "chat_id": "<office_conversation_id>",
  "message_id": "<office_message_id>",
  "parish_id": "<parish_id>"
}
```

Routowanie mobile:

- ekran konkretnej konwersacji kancelarii online

Minimalne wymagania klienta:

- otwieraj bezposrednio po `chat_id`
- `message_id` moze sluzyc do scrollowania / focusu na konkretna wiadomosc

Strategia dostarczania:

- priorytet: `high`
- collapse: `office-{chat_id}` tylko gdy wlaczone w ustawieniach backendu
- domyslnie tego typu nie nalezy agresywnie zwijac po stronie UX

### 4.4 `MASS_PENDING`

Przeznaczenie:

- przypomnienie o zblizajacej sie mszy, na ktora uzytkownik zapisal uczestnictwo

`data`:

```json
{
  "notification_id": "<uuid>",
  "type": "MASS_PENDING",
  "mass_id": "<mass_id>",
  "parish_id": "<parish_id>",
  "reminder_key": "24h",
  "celebration_at": "2026-03-17T13:00:00+01:00"
}
```

Dozwolone `reminder_key`:

- `24h`
- `8h`
- `1h`

Routowanie mobile:

- ekran szczegolow mszy lub ekran zapisanych mszy uzytkownika

Strategia dostarczania:

- priorytet: `high`
- collapse: brak, kazde przypomnienie jest osobnym zdarzeniem
- email jest osobna sciezka backendowa i nie wynika bezposrednio z payloadu push

### 4.5 `PARISH_APPROVAL_STATUS_CHANGED`

Przeznaczenie:

- zmiana statusu zatwierdzenia parafialnego konta

`data`:

```json
{
  "notification_id": "<uuid>",
  "type": "PARISH_APPROVAL_STATUS_CHANGED",
  "is_parish_approved": "true",
  "parish_id": "<parish_id>"
}
```

Uwaga:

- `is_parish_approved` przychodzi jako string, bo backend stringifikuje dane FCM
- klient powinien traktowac `"true"` jako `true` i `"false"` jako `false`

Routowanie mobile:

- ekran profilu / statusu konta / onboarding state

Strategia dostarczania:

- priorytet: `high`
- collapse: `parish-approval` gdy wlaczone w ustawieniach backendu

### 4.6 `TEST_MESSAGE`

Typ techniczny z panelu superadmina do testow integracyjnych.

`data`:

```json
{
  "notification_id": "test-<timestamp>",
  "type": "TEST_MESSAGE",
  "...": "dowolne pola przekazane recznie z panelu"
}
```

Routowanie mobile:

- brak routingu biznesowego
- mozna otworzyc ekran debug / inbox albo tylko pokazac baner

## 5. Zachowanie Android

Android powinien:

1. obsluzyc push w foreground, background i po tapnieciu
2. czytac routing z `remoteMessage.data`
3. traktowac `notification` jako warstwe prezentacji, ale nie jako zrodlo logiki
4. wspierac `collapse_key` zgodnie z natywnym zachowaniem FCM

Rekomendowane mapowanie:

- `NEWS_CREATED` -> `NewsDetails(newsId, parishId)`
- `ANNOUNCEMENTS_PACKAGE_PUBLISHED` -> `AnnouncementsDetails(announcementSetId, parishId)`
- `OFFICE_MESSAGE_RECEIVED` -> `OfficeChat(chatId, messageId, parishId)`
- `MASS_PENDING` -> `MassDetails(massId, parishId, reminderKey)`
- `PARISH_APPROVAL_STATUS_CHANGED` -> `AccountApproval(parishId)`

## 6. Zachowanie iOS

iOS powinien:

1. obsluzyc push przez Firebase Messaging i APNs
2. czytac routing z `userInfo`
3. traktowac `aps.alert` jako UI, a `data` jako logike
4. respektowac `apns-collapse-id` dla typow collapsible

Rekomendowane mapowanie:

- `NEWS_CREATED` -> `NewsDetailsView(newsId, parishId)`
- `ANNOUNCEMENTS_PACKAGE_PUBLISHED` -> `AnnouncementsView(announcementSetId, parishId)`
- `OFFICE_MESSAGE_RECEIVED` -> `OfficeConversationView(chatId, messageId, parishId)`
- `MASS_PENDING` -> `MassDetailView(massId, parishId, reminderKey)`
- `PARISH_APPROVAL_STATUS_CHANGED` -> `AccountStatusView(parishId)`

## 7. Zasady kompatybilnosci

- Klient nie moze zakladac, ze backend zawsze wysle wszystkie pola poza `notification_id` i `type`.
- Klient musi byc odporny na dodatkowe klucze w `data`.
- Gdy routing nie moze byc wykonany, klient powinien otworzyc ogolny feed notyfikacji.
- Dla `OFFICE_MESSAGE_RECEIVED` klient nie powinien deduplikowac lokalnie po samym `chat_id`; zrodlem prawdy jest `notification_id`.

## 8. Status na dzis

Wspierane typy produkcyjne:

- `NEWS_CREATED`
- `ANNOUNCEMENTS_PACKAGE_PUBLISHED`
- `MASS_PENDING`
- `OFFICE_MESSAGE_RECEIVED`
- `PARISH_APPROVAL_STATUS_CHANGED`

Typ techniczny:

- `TEST_MESSAGE`
