# API: Aktualnosci, komentarze, galeria i zalaczniki

Ten dokument opisuje publiczne i zalogowane endpointy API v1 zwiazane z aktualnosciami parafialnymi.

## Endpointy publiczne

### Lista newsow

- `GET /api/v1/parishes/{parishId}/news`
- paginacja cursor w `meta.next_cursor`

### Szczegoly newsa

- `GET /api/v1/parishes/{parishId}/news/{newsId}`

### Lista komentarzy

- `GET /api/v1/parishes/{parishId}/news/{newsId}/comments`
- endpoint publiczny
- zwraca tylko komentarze glowne w `data`
- kazdy komentarz glowny zawiera zagniezdzone `replies`
- obslugiwane poziomy:
  - `depth = 0` komentarz glowny
  - `depth = 1` odpowiedz
  - `depth = 2` odpowiedz na odpowiedz
- `replies_count` informuje, ile odpowiedzi ma dany komentarz na danym poziomie
- komentarz ukryty zwraca:
  - `body = null`
  - `user = null`
  - `is_hidden = true`

Przykladowy fragment odpowiedzi:

```json
{
  "data": [
    {
      "id": "15",
      "parent_id": null,
      "depth": 0,
      "body": "Komentarz glowny",
      "is_hidden": false,
      "can_reply": true,
      "user": {
        "id": "8",
        "name": "Jan Kowalski",
        "avatar_url": "https://example.com/avatar.jpg"
      },
      "replies_count": 1,
      "replies": [
        {
          "id": "16",
          "parent_id": "15",
          "depth": 1,
          "body": "Odpowiedz",
          "is_hidden": false,
          "can_reply": true,
          "user": {
            "id": "8",
            "name": "Jan Kowalski",
            "avatar_url": "https://example.com/avatar.jpg"
          },
          "replies_count": 1,
          "replies": [
            {
              "id": "17",
              "parent_id": "16",
              "depth": 2,
              "body": "Odpowiedz drugiego poziomu",
              "is_hidden": false,
              "can_reply": false,
              "user": {
                "id": "8",
                "name": "Jan Kowalski",
                "avatar_url": "https://example.com/avatar.jpg"
              },
              "replies_count": 0,
              "replies": []
            }
          ]
        }
      ]
    }
  ],
  "meta": {
    "next_cursor": null,
    "has_more": false
  }
}
```

### Galeria newsa

- `GET /api/v1/parishes/{parishId}/news/{newsId}/gallery`
- endpoint publiczny
- zwraca obrazy z kolekcji `gallery`

Przykladowa odpowiedz:

```json
{
  "data": {
    "gallery": [
      {
        "id": "101",
        "file_name": "foto-1.jpg",
        "name": "foto-1",
        "mime_type": "image/jpeg",
        "size": 120345,
        "original_url": "https://example.com/storage/news/1/foto-1.jpg",
        "preview_url": "https://example.com/storage/news/1/conversions/foto-1-preview.jpg",
        "thumb_url": "https://example.com/storage/news/1/conversions/foto-1-thumb.jpg",
        "created_at": "2026-03-20T12:30:00Z"
      }
    ]
  }
}
```

### Zalaczniki newsa

- `GET /api/v1/parishes/{parishId}/news/{newsId}/attachments`
- endpoint publiczny
- zwraca pliki z kolekcji `attachments`

Przykladowa odpowiedz:

```json
{
  "data": {
    "attachments": [
      {
        "id": "202",
        "file_name": "biuletyn.pdf",
        "name": "biuletyn",
        "mime_type": "application/pdf",
        "size": 54321,
        "download_url": "https://example.com/storage/news/1/biuletyn.pdf",
        "created_at": "2026-03-20T12:35:00Z"
      }
    ]
  }
}
```

## Endpointy zalogowane

### Dodanie komentarza

- `POST /api/v1/parishes/{parishId}/news/{newsId}/comments`
- wymagania:
  - `Bearer access token`
  - zalogowany user
  - `email_verified`

Request JSON:

```json
{
  "body": "Tresc komentarza",
  "parent_id": 15
}
```

Zasady:

- `parent_id = null` lub brak pola tworzy komentarz glowny
- `parent_id` wskazuje komentarz rodzica w tym samym newsie
- maksymalna glebokosc odpowiedzi to 2
- nie mozna odpowiadac na komentarz ukryty

### Usuniecie komentarza

- `DELETE /api/v1/parishes/{parishId}/news/{newsId}/comments/{commentId}`
- user moze ukryc tylko swoj komentarz
- API zwraca `204 No Content`

## Uwagi implementacyjne dla klienta mobilnego

- komentarze nalezy renderowac jako drzewo:
  - warstwa glowna z `data`
  - kolejne poziomy z pola `replies`
- klient nie powinien dopuszczac odpowiedzi, gdy `can_reply = false`
- `comments_enabled = false` przy newsie oznacza, ze formularz komentarza ma byc ukryty
