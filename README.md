# Wspolnota

Wspolnota to usluga dla parafii laczaca publiczne strony parafialne, panel administracyjny dla proboszcza, panel operatorski SuperAdmin oraz API dla aplikacji mobilnej i PWA. Repozytorium obejmuje warstwe tresci, komunikacji, kancelarii online, powiadomien push, mailingow i raportowania operacyjnego.

## Zakres systemu

- landing marketingowy i strony prawne uslugi,
- publiczne strony parafii pod subdomenami,
- panel `admin` dla parafii oparty o Filament,
- panel `superadmin` do zarzadzania cala usluga,
- API `v1` dla aplikacji mobilnej,
- obsluga mszy, ogloszen, aktualnosci i komentarzy,
- kancelaria online, kampanie komunikacyjne i maile,
- push przez FCM, ustawienia systemowe i raporty schedulerowe.

## Stack

- PHP 8.2+
- Laravel 12
- Filament 4
- Livewire 3
- Vite 7
- Tailwind CSS 4
- Pest 4
- Spatie Media Library
- Spatie Laravel Settings
- Spatie Activitylog

## Architektura

Najwazniejsze obszary aplikacji:

- `app/Filament/Admin` - panel parafialny, zasoby i widgety dla administratora parafii,
- `app/Filament/SuperAdmin` - panel operatorski dla calej uslugi,
- `app/Http/Controllers/Api/V1` - publiczne i chronione endpointy mobilne,
- `app/Http/Controllers/Landing` oraz `resources/views/landing` - landing i strony informacyjne,
- `app/Http/Controllers/Parish` oraz `resources/views/parish` - publiczne strony parafii,
- `app/Support`, `app/Mail`, `app/Notifications`, `app/Jobs` - logika komunikacji, raportow i kolejek,
- `database/migrations`, `database/factories`, `database/seeders` - model danych i dane developerskie,
- `openapi/v1.yaml` - kontrakt API udostepniany pod `/api/v1/openapi.yaml`.

Multi-tenancy panelu parafialnego jest oparte o model `Parish`. Panel `admin` pracuje na kontekscie konkretnej parafii, a `superadmin` zarzadza zasobami globalnie.

## Glowne moduly domenowe

- `Masses` - harmonogram mszy, intencje, uczestnicy i przypomnienia,
- `AnnouncementSets` - zestawy ogloszen wraz z PDF i publikacja,
- `NewsPosts` i `NewsComments` - aktualnosci parafialne z moderacja komentarzy,
- `OfficeConversations` - kancelaria online i zalaczniki,
- `CommunicationCampaigns` - komunikacja e-mail i push,
- `UserDevices`, `PushDeliveries`, `FcmSettings` - obsluga push i diagnostyka,
- `GeneralSettings` - ustawienia globalne uslugi.

## Start lokalny

### Wymagania

- PHP 8.2+
- Composer
- Node.js + npm
- dzialajaca baza danych zgodna z konfiguracja `.env`

### Instalacja

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm install
```

### Development

```bash
composer run dev
```

Polecenie uruchamia jednoczesnie:

- serwer Laravel,
- kolejke,
- podglad logow,
- Vite dev server.

### Build assetow

```bash
npm run build
```

## Testy

Pelny zestaw:

```bash
composer test
```

Przyklad uruchomienia wybranego zestawu:

```bash
php artisan test --compact tests/Feature/Landing/LandingPagesTest.php
```

## Dane developerskie

Seederzy przygotowuja realistyczny zestaw danych dla parafii, administratorow, aktualnosci, ogloszen, kancelarii online i komunikacji:

```bash
php artisan db:seed
```

## Release i deploy

Repo zawiera skrypt przygotowujacy paczke release dla SeoHost:

```bash
npm run build:seohost
```

Wariant z danymi ze `storage`:

```bash
npm run build:seohost:data
```

Skrypt korzysta z `scripts/prepare-seohost-release.sh` i buduje gotowe archiwum ZIP w katalogu `.build/`.

## Wazne adresy aplikacji

- `/` - landing uslugi,
- `/admin` - panel parafialny,
- `/superadmin` - panel operatorski,
- `/api/v1/*` - API mobilne,
- `/api/v1/openapi.yaml` - kontrakt OpenAPI.

## Dokumentacja pomocnicza

Katalog `docs/` zawiera robocze materialy produktowe i techniczne, m.in. kontrakty push, opis wybranych endpointow API i notatki produkcyjne.
