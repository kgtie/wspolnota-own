# API: Skróty dla mszy swietych

Ten dokument opisuje dodatkowe publiczne endpointy API v1 dla aplikacji mobilnej, obok istniejacego endpointu zakresowego `GET /api/v1/parishes/{parishId}/masses`.

## Istniejacy endpoint zakresowy

- `GET /api/v1/parishes/{parishId}/masses?from=...&to=...&cursor=...`
- sluzy do list widokowych i kalendarzowych
- wymaga jawnego zakresu `from` i `to`

## Nowe endpointy skrótowe

### 1. Ostatnie 5 mszy z przeszlosci

- `GET /api/v1/parishes/{parishId}/masses/recent-past`
- endpoint publiczny
- zwraca maksymalnie 5 ostatnich mszy z przeszlosci
- sortowanie:
  - malejaco po `celebration_at`
  - najblizsza przeszla msza jako pierwsza
- pomija msze anulowane (`status = cancelled`)

Przykladowa odpowiedz:

```json
{
  "data": {
    "masses": [
      {
        "id": "71",
        "parish_id": "4",
        "intention_title": "Za parafian",
        "intention_details": null,
        "celebration_at": "2026-03-19T17:00:00Z",
        "mass_kind": "weekday",
        "mass_type": "individual",
        "status": "completed",
        "celebrant_name": "ks. Adam Nowak",
        "location": "Kosciol parafialny",
        "created_at": "2026-03-01T09:00:00Z",
        "updated_at": "2026-03-19T18:15:00Z"
      }
    ]
  }
}
```

### 2. Najblizsze 10 nadchodzacych mszy

- `GET /api/v1/parishes/{parishId}/masses/upcoming`
- endpoint publiczny
- zwraca maksymalnie 10 najblizszych mszy
- sortowanie:
  - rosnaco po `celebration_at`
  - najblizsza nadchodzaca msza jako pierwsza
- zwraca tylko msze zaplanowane (`status = scheduled`)

Przykladowa odpowiedz:

```json
{
  "data": {
    "masses": [
      {
        "id": "92",
        "parish_id": "4",
        "intention_title": "W intencji rodzin",
        "intention_details": null,
        "celebration_at": "2026-03-21T08:00:00Z",
        "mass_kind": "weekday",
        "mass_type": "individual",
        "status": "scheduled",
        "celebrant_name": "ks. Adam Nowak",
        "location": "Kosciol parafialny",
        "created_at": "2026-03-05T09:00:00Z",
        "updated_at": "2026-03-05T09:00:00Z"
      }
    ]
  }
}
```

## Rekomendacja dla klienta mobilnego

- `recent-past` wykorzystaj na ekranie startowym lub ekranie szczegolow parafii jako blok "Ostatnie msze"
- `upcoming` wykorzystaj na ekranie startowym jako blok "Nadchodzace msze"
- dla pelnych list i filtrow nadal uzywaj endpointu zakresowego z `from` i `to`
