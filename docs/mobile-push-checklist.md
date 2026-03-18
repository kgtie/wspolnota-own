# Mobile Push Checklist

Ta checklista jest krotka i operacyjna. Zespol mobile powinien ja traktowac jako minimum konieczne do poprawnego dzialania push po zmianach backendu.

## 1. Po zalogowaniu zarejestruj urzadzenie

Wywolaj:

```http
POST /api/v1/me/devices
```

Wyslij zawsze:

- `provider = fcm`
- `platform = android | ios`
- `push_token`
- `device_id`
- `device_name`
- `app_version`
- `locale`
- `timezone`
- `permission_status`

Wazne:

- Nie pomijaj `permission_status`.
- Jesli system nie udzielil jeszcze zgody, wyslij `not_determined`.
- Jesli user odmowil, wyslij `denied`.
- Push backendowy nie ruszy bez poprawnego statusu urzadzenia.

## 2. Zapisz preferencje powiadomien w backendzie

Wywolaj:

```http
PATCH /api/v1/me/notification-preferences
```

Wyślij komplet sekcji:

- `news`
- `announcements`
- `mass_reminders`
- `office_messages`
- `parish_approval_status`
- `auth_security`

Kazda sekcja musi miec oba pola:

- `push`
- `email`

Wazne:

- Samo posiadanie tokenu FCM nie wystarczy.
- Backend nie wysyla automatycznych pushy, jesli user nie ma zapisanego rekordu zgod.

## 3. Aktualizuj urzadzenie przy kazdej zmianie tokenu lub zgody

Ponow `POST /api/v1/me/devices`, gdy:

- zmieni sie `push_token`
- zmieni sie `permission_status`
- zmieni sie `parish_id` kontekstowe na urzadzeniu
- zmieni sie `app_version`
- user wraca po reinstalacji aplikacji

## 4. Przy logout usun powiazanie urzadzenia

Wywolaj:

```http
DELETE /api/v1/me/devices/{id}
```

Backendowy rekord urzadzenia nie powinien zostawac po logout.

## 5. Obsloz wszystkie typy payloadow

Mobile musi routowac co najmniej:

- `NEWS_CREATED`
- `ANNOUNCEMENTS_PACKAGE_PUBLISHED`
- `MASS_PENDING`
- `OFFICE_MESSAGE_RECEIVED`
- `PARISH_APPROVAL_STATUS_CHANGED`
- `TEST_MESSAGE`

Kluczowe pola routingu:

- `notification_id`
- `type`
- `parish_id`
- `news_id`
- `announcement_set_id`
- `mass_id`
- `chat_id`

## 6. Wymuszone scenariusze testowe

Przed release sprawdz:

1. `permission_status = not_determined` -> brak push
2. `permission_status = denied` -> brak push
3. `permission_status = authorized` + zapisane zgody -> push dochodzi
4. zmiana toggle w ustawieniach mobile -> backend od razu respektuje zmiane
5. refresh tokenu FCM -> backend dostaje nowy token
6. logout -> urzadzenie znika z backendu

## 7. Co jest teraz najwazniejsze

Po ostatnich zmianach backendu dwie rzeczy sa obowiazkowe:

1. Mobile musi zawsze wysylac prawdziwy `permission_status`.
2. Mobile musi zawsze zapisywac preferencje w `/api/v1/me/notification-preferences`.

Bez tego automatyczne push notifications nie beda dzialac zgodnie z oczekiwaniem.
