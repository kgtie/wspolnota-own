# Wdrozenie produkcyjne na SEOHOST.pl

## Zalecany model wdrozenia

Najprostszy wariant dla tego projektu to pozostawienie aplikacji Laravel w standardowym ukladzie katalogow i ustawienie webroota domeny na katalog `public/`.

To jest szczegolnie wazne dla tej aplikacji, bo korzysta z:

- Laravel 12 i standardowego front controllera w `public/index.php`
- assetow budowanych przez Vite do `public/build`
- linku `public/storage`
- schedulera Laravela zdefiniowanego w `routes/console.php`

## Co jest przygotowane w repozytorium

- [`.env.production.example`](/Users/konradgruza/Herd/wspolnota/.env.production.example) zawiera bezpieczny punkt startowy dla produkcji.
- [`scripts/prepare-seohost-release.sh`](/Users/konradgruza/Herd/wspolnota/scripts/prepare-seohost-release.sh) buduje assety i tworzy gotowa paczke `ZIP`.

Domyslnie skrypt tworzy paczke tylko z kodem aplikacji, bez danych uzytkownikow z `storage/app/public` i `storage/app/office`.

## Dlaczego `QUEUE_CONNECTION=sync`

W tym projekcie nie ma stale uruchamianego workera kolejek, a hosting wspoldzielony zwykle nie nadaje sie do utrzymywania procesu `queue:work` 24/7.

Dodatkowo biblioteka Media Library ma domyslnie wlaczone kolejki dla konwersji obrazow. Dlatego w produkcyjnym `.env` ustawiamy:

- `QUEUE_CONNECTION=sync`
- `QUEUE_CONVERSIONS_BY_DEFAULT=false`

To upraszcza wdrozenie i usuwa zaleznosc od osobnego workera.

## Przygotowanie paczki lokalnie

W katalogu projektu uruchom:

```bash
bash scripts/prepare-seohost-release.sh
```

Skrot `npm` dla tego wariantu:

```bash
npm run build:seohost
```

Jesli chcesz przeniesc rowniez aktualne pliki uzytkownikow, uruchom:

```bash
bash scripts/prepare-seohost-release.sh --with-storage-data
```

Skrot `npm` dla paczki z danymi:

```bash
npm run build:seohost:data
```

Po wykonaniu dostaniesz:

- katalog `.build/seohost/release`
- archiwum `.build/seohost/wspolnota-seohost-release.zip`

Ta paczka zawiera:

- gotowe assety z `npm run build`
- katalog `vendor`
- kod aplikacji bez lokalnego `.env`
- oczyszczone katalogi runtime (`storage/framework`, `storage/logs`, `bootstrap/cache`)

W trybie domyslnym paczka nie zawiera:

- dumpa bazy MySQL
- publicznych uploadow z `storage/app/public`
- prywatnych plikow z `storage/app/office`

## Struktura na serwerze

Przykladowa lokalizacja po stronie SEOHOST:

```text
/domains/twoja-domena.pl/wspolnota
```

Po rozpakowaniu aplikacja powinna miec standardowa strukture:

```text
/domains/twoja-domena.pl/wspolnota/app
/domains/twoja-domena.pl/wspolnota/bootstrap
/domains/twoja-domena.pl/wspolnota/public
/domains/twoja-domena.pl/wspolnota/storage
/domains/twoja-domena.pl/wspolnota/vendor
```

Nastepnie w panelu domeny ustaw webroot na:

```text
/domains/twoja-domena.pl/wspolnota/public
```

## Wdrozenie krok po kroku

1. Wygeneruj paczke lokalnie:

```bash
bash scripts/prepare-seohost-release.sh
```

Jesli chcesz zbudowac paczke razem z plikami uzytkownikow:

```bash
bash scripts/prepare-seohost-release.sh --with-storage-data
```

2. Wrzuc `wspolnota-seohost-release.zip` na serwer do katalogu domeny, np.:

```text
/domains/twoja-domena.pl/
```

3. Zaloguj sie przez SSH lub Terminal w panelu i rozpakuj paczke:

```bash
cd /domains/twoja-domena.pl
rm -rf wspolnota
unzip wspolnota-seohost-release.zip -d .
mv release wspolnota
```

4. Skopiuj plik `.env`:

```bash
cd /domains/twoja-domena.pl/wspolnota
cp .env.production.example .env
```

5. Uzupelnij `.env`:

- `APP_URL=https://twoja-domena.pl`
- dane bazy MySQL z SEOHOST
- dane SMTP
- `WSPOLNOTA_CONTACT_RECIPIENT`
- `WSPOLNOTA_SCHEDULER_REPORT_RECIPIENT`
- ewentualnie `GEMINI_API_KEY`, jesli AI ma dzialac na produkcji

6. Wygeneruj klucz aplikacji:

```bash
php artisan key:generate --force
```

7. Ustaw prawa zapisu:

```bash
chmod -R 775 storage bootstrap/cache
```

8. Utworz link do storage:

```bash
php artisan storage:link
```

9. Uruchom migracje:

```bash
php artisan migrate --force
```

10. Zbuduj cache produkcyjny:

```bash
php artisan optimize:clear
php artisan optimize
```

11. Ustaw webroot domeny na katalog:

```text
/domains/twoja-domena.pl/wspolnota/public
```

12. Dodaj cron, ktory co minute uruchamia scheduler Laravela:

```bash
* * * * * /usr/local/bin/php /domains/twoja-domena.pl/wspolnota/artisan schedule:run >> /dev/null 2>&1
```

Jesli na Twoim koncie `php` ma inna sciezke, sprawdz ja poleceniem:

```bash
which php
```

## Co trzeba ustawic w bazie i panelu

### PHP

Projekt wymaga minimum PHP 8.2. Lokalnie dziala na PHP 8.4. Na hostingu ustaw co najmniej PHP 8.2, a najlepiej 8.3 lub 8.4, jesli jest dostepne.

### Rozszerzenia PHP

Upewnij sie, ze wlaczone sa co najmniej:

- `bcmath`
- `ctype`
- `fileinfo`
- `json`
- `mbstring`
- `openssl`
- `pdo_mysql`
- `tokenizer`
- `xml`
- `gd` lub `imagick`
- `zip`

### Baza danych

Projekt jest skonfigurowany pod MySQL:

- `DB_CONNECTION=mysql`
- sesje sa w bazie: `SESSION_DRIVER=database`
- cache jest w bazie: `CACHE_STORE=database`

Po wdrozeniu migracje powinny utworzyc potrzebne tabele.

## Jesli chcesz przeniesc rowniez dane

Kod aplikacji to tylko jedna czesc wdrozenia. Jesli chcesz przeniesc aktualne dane, musisz dodatkowo przeniesc baze MySQL.

### Eksport z obecnego srodowiska

Przykladowo:

```bash
mysqldump -u NAZWA_UZYTKOWNIKA -p NAZWA_BAZY > wspolnota.sql
```

### Import na SEOHOST

Po utworzeniu bazy w panelu:

```bash
mysql -h HOST_BAZY -u UZYTKOWNIK_BAZY -p NAZWA_BAZY < wspolnota.sql
```

Jesli nie masz dostepu do importu przez SSH, mozna uzyc phpMyAdmin z panelu hostingu.

### Pliki uzytkownikow

Ten projekt zapisuje dane uzytkownikow co najmniej w:

- `storage/app/public`
- `storage/app/office`

Domyslny release ich nie pakuje, zeby nie wrzucac danych deweloperskich na produkcje przypadkiem.

Jesli chcesz przeniesc te pliki, masz dwie bezpieczne opcje:

1. Zbudowac paczke poleceniem `bash scripts/prepare-seohost-release.sh --with-storage-data`.
2. Przeniesc te katalogi osobno po SFTP/SSH po zbudowaniu standardowej paczki.

## Konfiguracja maili

Ta aplikacja wysyla powiadomienia email oraz wykonuje powiadomienia z harmonogramu, wiec produkcja wymaga dzialajacego SMTP.

Minimalny zestaw:

```dotenv
MAIL_MAILER=smtp
MAIL_SCHEME=tls
MAIL_HOST=...
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_FROM_ADDRESS=...
MAIL_FROM_NAME="${APP_NAME}"
```

## Scheduler aplikacji

W aplikacji sa zaplanowane zadania:

- generowanie streszczen AI ogloszen
- publikacja zaplanowanych aktualnosci
- wysylka powiadomien o aktualnych ogloszeniach
- dzienny raport email z wykonania schedulera

Raport jest wysylany raz dziennie o `23:59` na adres z:

- `WSPOLNOTA_SCHEDULER_REPORT_RECIPIENT`

Jesli ta zmienna nie jest ustawiona, aplikacja korzysta z:

- `WSPOLNOTA_CONTACT_RECIPIENT`

Bez crona te procesy nie beda dzialac.

## Checklista po wdrozeniu

Sprawdz po wdrozeniu:

1. Czy strona glowna otwiera sie bez bledu 500.
2. Czy logowanie do panelu Filament dziala.
3. Czy upload pliku tworzy wpis w `public/storage`.
4. Czy `php artisan schedule:run` przechodzi bez bledu.
5. Czy wysylka emaila testowego dziala.

## Aktualizacja kolejna wersja

Najprostszy bezpieczny schemat:

1. Lokalnie uruchom ponownie `bash scripts/prepare-seohost-release.sh`.
2. Wgraj nowe `wspolnota-seohost-release.zip`.
3. Na serwerze podmien katalog `wspolnota`.
4. Zachowaj istniejacy plik `.env`.
5. Uruchom:

```bash
cd /domains/twoja-domena.pl/wspolnota
php artisan migrate --force
php artisan optimize:clear
php artisan optimize
```

## Zrodla pomocnicze SEOHOST

Przy wdrozeniu przydadza sie materialy SEOHOST dotyczace:

- konfiguracji `Customize Webroot` w DirectAdmin
- korzystania z Terminala w DirectAdmin
- ustawienia zadan `cron`
- zmiany wersji PHP dla domeny

Najwygodniej szukac ich w bazie wiedzy:

- https://seohost.pl/pomoc/kat-directadmin
- https://seohost.pl/pomoc/kat-hosting
- https://seohost.pl/pomoc/
