# Basic REST API (Laravel)

REST API do obsługi kont użytkowników, ról i uprawnień. Projekt używa Laravel 13, PHP 8.3+, PostgreSQL, Laravel Passport i Spatie Permission. Bazowy adres API: `http://localhost/api`.

## Zakres funkcjonalny

- rejestracja, potwierdzenie e-maila, logowanie tokenem Bearer, wylogowanie i reset hasła;
- profil użytkownika, awatar, ustawienia powiadomień oraz usuwanie konta;
- administracja użytkownikami, rolami i uprawnieniami;
- historia zmian ról i użytkowników, w tym soft delete użytkowników;
- asynchroniczne wiadomości e-mail w kolejce bazodanowej;
- lokalne usługi Sail: PostgreSQL, Redis, Memcached, Mailpit i pgAdmin;
- awatary na dysku lokalnym lub S3.

## Wymagania

- WSL 2 z Docker Desktop oraz Docker Compose v2;
- opcjonalnie PHP 8.3+, Composer i Node.js/npm w WSL (dla `composer setup`).

## Start lokalny w WSL

W terminalu WSL przejdź do projektu. Pierwsza instalacja, bez lokalnego PHP/Composera:

```bash
docker run --rm -u "$(id -u):$(id -g)" \
  -v "$PWD":/var/www/html -w /var/www/html \
  laravelsail/php84-composer:latest composer install --ignore-platform-reqs

cp .env.example .env
./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan storage:link
./vendor/bin/sail composer fresh-db
./vendor/bin/sail npm install
./vendor/bin/sail npm run build
```

Jeśli PHP, Composer i npm są już dostępne w WSL, ten sam proces uruchomi skrót:

```bash
composer setup
```

Po starcie:

| Usługa       | Adres                                                  |
| ------------ | ------------------------------------------------------ |
| API          | `http://localhost/api`                                 |
| Health check | `GET http://localhost/up`                              |
| Mailpit      | `http://localhost:8025`                                |
| pgAdmin      | `http://localhost:5050` — `sail@sail.com` / `password` |

Zatrzymanie: `./vendor/bin/sail down`. Ponowny start: `./vendor/bin/sail up -d`.

### Konfiguracja `.env`

Przed uruchomieniem sprawdź `APP_URL`, `DB_*`, `MAIL_*`, `GATEWAY_URL`, `GATEWAY_USE_HASH`, `ENABLE_REGISTRATION` i `CONFIRM_EMAIL`. `GATEWAY_URL` jest adresem frontendu w linkach aktywacyjnych oraz resetu hasła. Dla awatarów ustaw `USERS_AVATARS_DISK_DRIVER=local` lub `s3` oraz właściwy `USERS_AVATARS_DISK_NAME`; dla S3 uzupełnij poświadczenia AWS i bucket.

## Skrypty i dane startowe

`fresh-db` oraz `full-db` wykonują `migrate:fresh` i **usuwają dane**. Używaj ich tylko lokalnie lub testowo.

| Polecenie                                                              | Działanie                                                                                                      |
| ---------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------- |
| `./vendor/bin/sail composer fresh-db`                                  | Resetuje bazę, seeduje role/uprawnienia i konta startowe, tworzy klucze Passport oraz klienta personal access. |
| `./vendor/bin/sail composer full-db`                                   | Jak `fresh-db`, następnie dodaje 100 użytkowników testowych.                                                   |
| `./vendor/bin/sail composer fake-db`                                   | Dodaje 100 użytkowników testowych (50 `teacher`, 50 `student`).                                                |
| `./vendor/bin/sail composer refresh-permissions`                       | Odświeża role, uprawnienia i ich przypisania bez resetu bazy.                                                  |
| `./vendor/bin/sail composer passport-init`                             | Generuje klucze Passport i tworzy klienta personal access.                                                     |
| `./vendor/bin/sail artisan app:migrate-users-avatars-from-local-to-s3` | Kopiuje brakujące awatary z `usersAvatars` do `s3usersAvatars`.                                                |
| `./vendor/bin/sail artisan app:migrate-users-avatars-from-s3-to-local` | Kopiuje brakujące awatary z S3 na dysk lokalny.                                                                |
| `./vendor/bin/sail composer test`                                      | Czyści cache konfiguracji i uruchamia testy Pest.                                                              |
| `./vendor/bin/sail pint --test`                                        | Sprawdza formatowanie Laravel Pint.                                                                            |

Po `fresh-db`: `superadmin@app.local`, `admin@app.local`, `teacher@app.local`, `student@app.local`; hasło pierwszych trzech to `root12`. Konto studenta jest niepotwierdzone. To dane wyłącznie dla środowiska lokalnego.

## Kolejki i e-maile

Domyślny `QUEUE_CONNECTION=database` używa tabeli `jobs`; worker musi działać niezależnie od serwera API.

| Kolejka                 | Zadania                                               |
| ----------------------- | ----------------------------------------------------- |
| `register-emails`       | wiadomość po rejestracji i ponowne wysłanie aktywacji |
| `reset-password-emails` | wiadomość z linkiem do resetu hasła                   |

```bash
./vendor/bin/sail artisan queue:work --queue=register-emails,reset-password-emails --tries=3
```

Diagnostyka: `./vendor/bin/sail artisan queue:failed`; ponowienie błędnych zadań: `./vendor/bin/sail artisan queue:retry all`. W lokalnym środowisku wiadomości przechwytuje Mailpit.

## Uwierzytelnianie i odpowiedzi

`POST /api/auth/login` zwraca token Passport. Przekazuj go przy endpointach chronionych:

```http
Authorization: Bearer <token>
Accept: application/json
Locale: pl
```

Nagłówek `Locale` ustawia język komunikatów walidacji. Token dostępu wygasa po 15 dniach, refresh token po 30 dniach. Hasło wymaga co najmniej 8 znaków, małej i wielkiej litery oraz cyfry. Sukces zwraca bezpośrednio zasób JSON; błąd biznesowy ma format `{"message":"KOD_BLEDU"}`.

Endpointy `/api/v1/*` wymagają tokenu i odpowiedniego uprawnienia. Po zmianie ról lub uprawnień użytkownik zostaje oznaczony do wylogowania; kolejne żądanie chronione zwróci `401` z `SHOULD_LOGOUT_ON_NEXT`, po czym należy zalogować się ponownie.

Przykład:

```bash
curl -X POST http://localhost/api/auth/login \
  -H 'Accept: application/json' -H 'Content-Type: application/json' \
  -d '{"email":"admin@app.local","password":"root12"}'
```

## Endpointy

Wszystkie ścieżki są poprzedzone przez `/api`. `:userId` to UUID, a `:roleId` i `:permissionId` są liczbami.

### Autoryzacja

| Metoda | Ścieżka                                   | Dostęp / działanie                                               |
| ------ | ----------------------------------------- | ---------------------------------------------------------------- |
| `POST` | `/auth/register`                          | Publiczna rejestracja.                                           |
| `GET`  | `/auth/confirm-account/:confirmationCode` | Publiczne potwierdzenie konta.                                   |
| `POST` | `/auth/send-reset-password-email`         | Publiczne wysłanie linku resetu.                                 |
| `POST` | `/auth/finish-password-reset`             | Publiczne ustawienie hasła według tokenu.                        |
| `GET`  | `/auth/admins-emails`                     | Publiczna lista e-maili kont z rolą `admin`.                     |
| `POST` | `/auth/login`                             | Publiczne logowanie i wydanie tokenu.                            |
| `POST` | `/auth/logout`                            | Token wymagany; wylogowanie bieżącego urządzenia lub wszystkich. |
| `GET`  | `/auth/current-user`                      | Token wymagany; aktualny użytkownik.                             |

| Operacja            | Pola żądania                                                                                                                                                         |
| ------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Rejestracja         | `firstName`, `lastName`, `email`, `password`, `passwordConfirmation`, `acceptanceRegulations: true`, `roles` (jedna: `student`/`teacher`), `locale` (`pl`/`en`/`es`) |
| Logowanie           | `email`, `password`                                                                                                                                                  |
| Reset — wysłanie    | `email`; opcjonalnie `gatewayUrl`                                                                                                                                    |
| Reset — zakończenie | `email`, `token`, `password`, `password_confirmation`                                                                                                                |
| Wylogowanie         | opcjonalne `revokeAll` (`boolean`)                                                                                                                                   |

### Moje konto — token wymagany

| Metoda   | Ścieżka                                 | Działanie                                             |
| -------- | --------------------------------------- | ----------------------------------------------------- |
| `PATCH`  | `/v1/account/change-password`           | Zmienia hasło i unieważnia wszystkie tokeny.          |
| `POST`   | `/v1/account/resend-confirmation-email` | Ponowna aktywacja; limit raz na godzinę.              |
| `PUT`    | `/v1/account/update-details-user`       | Aktualizuje imię, nazwisko i opcjonalnie awatar.      |
| `GET`    | `/v1/account/get-user-settings`         | Odczytuje ustawienia powiadomień.                     |
| `PATCH`  | `/v1/account/update-user-settings`      | Zmienia `webNotifications` i/lub `mailNotifications`. |
| `DELETE` | `/v1/account/delete-account`            | Miękko usuwa konto; superadmin nie może go usunąć.    |

`change-password` wymaga `oldPassword`, `newPassword`, `passwordConfirmation`. Aktualizacja profilu używa `multipart/form-data`: `firstName`, `lastName`, opcjonalnie `avatar` (jpg/jpeg/png/webp/gif, max 5 MB) albo `removeAvatar=true`.

### Użytkownicy — `USERS_*`

| Metoda   | Ścieżka                            | Uprawnienie    | Działanie                           |
| -------- | ---------------------------------- | -------------- | ----------------------------------- |
| `GET`    | `/v1/user/get-all`                 | `USERS_VIEW`   | Pełna lista aktywnych użytkowników. |
| `GET`    | `/v1/user`                         | `USERS_VIEW`   | Lista stronicowana.                 |
| `GET`    | `/v1/user/deleted`                 | `USERS_VIEW`   | Lista usuniętych użytkowników.      |
| `GET`    | `/v1/user/:userId`                 | `USERS_VIEW`   | Szczegóły użytkownika.              |
| `GET`    | `/v1/user/history/:userId`         | `USERS_VIEW`   | Historia zmian.                     |
| `POST`   | `/v1/user`                         | `USERS_CREATE` | Tworzy użytkownika.                 |
| `PUT`    | `/v1/user/:userId`                 | `USERS_UPDATE` | Aktualizuje dane.                   |
| `PATCH`  | `/v1/user/:userId/change-password` | `USERS_UPDATE` | Ustawia hasło.                      |
| `PUT`    | `/v1/user/attach-roles/:userId`    | `USERS_UPDATE` | Synchronizuje role po ID.           |
| `DELETE` | `/v1/user/:userId`                 | `USERS_DELETE` | Miękko usuwa konto.                 |
| `DELETE` | `/v1/user/:userId/restore`         | `USERS_DELETE` | Przywraca konto.                    |

Tworzenie użytkownika wymaga `firstName`, `lastName`, `email`, `confirmed`, `locale`; `roles` jest opcjonalną tablicą z jedną rolą `student` lub `teacher`. Zmiana hasła wymaga `password` i `password_confirmation`; przypisanie ról przyjmuje `selectedRolesIds` (tablica ID).

### Role — `ROLES_*`

| Metoda   | Ścieżka                    | Uprawnienie    | Działanie                       |
| -------- | -------------------------- | -------------- | ------------------------------- |
| `GET`    | `/v1/role/get-all`         | `ROLES_VIEW`   | Wszystkie role.                 |
| `GET`    | `/v1/role`                 | `ROLES_VIEW`   | Role stronicowane.              |
| `GET`    | `/v1/role/:roleId`         | `ROLES_VIEW`   | Szczegóły roli.                 |
| `GET`    | `/v1/role/history/:roleId` | `ROLES_VIEW`   | Historia roli.                  |
| `GET`    | `/v1/role/get-permissions` | `ROLES_VIEW`   | Uprawnienia do formularza roli. |
| `POST`   | `/v1/role`                 | `ROLES_CREATE` | Tworzy rolę.                    |
| `PUT`    | `/v1/role/:roleId`         | `ROLES_UPDATE` | Aktualizuje rolę i uprawnienia. |
| `DELETE` | `/v1/role/:roleId`         | `ROLES_DELETE` | Usuwa rolę.                     |

Tworzenie/aktualizacja roli: `name` oraz niepusta tablica nazw `permissions`, np. `["USERS_VIEW","USERS_CREATE"]`.

### Uprawnienia — `PERMISSIONS_*`

| Metoda   | Ścieżka                        | Uprawnienie          | Działanie                 |
| -------- | ------------------------------ | -------------------- | ------------------------- |
| `GET`    | `/v1/permission/get-all`       | `PERMISSIONS_VIEW`   | Wszystkie uprawnienia.    |
| `GET`    | `/v1/permission`               | `PERMISSIONS_VIEW`   | Uprawnienia stronicowane. |
| `GET`    | `/v1/permission/:permissionId` | `PERMISSIONS_VIEW`   | Szczegóły uprawnienia.    |
| `POST`   | `/v1/permission`               | `PERMISSIONS_CREATE` | Tworzy uprawnienie.       |
| `PUT`    | `/v1/permission/:permissionId` | `PERMISSIONS_UPDATE` | Aktualizuje uprawnienie.  |
| `DELETE` | `/v1/permission/:permissionId` | `PERMISSIONS_DELETE` | Usuwa uprawnienie.        |

Tworzenie/aktualizacja: `name`, `groupName`.

## Listy, filtrowanie i sortowanie

`/v1/user`, `/v1/role` i `/v1/permission` obsługują `pageSize`, `filter[...]` i `sort`; wynik stronicowany zawiera `data`, `total`, `page`, `lastPage`, `pageSize`. Prefiks `-` w sortowaniu oznacza kolejność malejącą.

```text
GET /api/v1/user?pageSize=20&filter[role]=teacher&filter[email]=example&sort=-createdAt
GET /api/v1/role?filter[name]=admin&sort=name
GET /api/v1/permission?filter[groupName]=USERS&sort=name
```

- użytkownicy — sort: `id`, `fullName`, `email`, `createdAt`, `updatedAt`, `deletedAt`, `emailVerifiedAt`; filtry: te pola, `emailVerifiedAtYes`, `emailVerifiedAtNo`, `role`, `permission`;
- role — sort/filtry: `id`, `name`, `guardName`, `createdAt`, `updatedAt`;
- uprawnienia — sort/filtry: `id`, `name`, `guardName`, `groupName`.

Daty są wyszukiwane tekstowo, np. `filter[createdAt]=2026-10`. Nieznany filtr lub sortowanie zwraca błąd.

## Procesy aplikacji

1. Rejestracja tworzy konto, rolę i wpis historii. Przy `CONFIRM_EMAIL=true` e-mail aktywacyjny trafia do `register-emails`.
2. Logowanie wystawia token Passport oraz zwraca efektywne role i uprawnienia.
3. Reset hasła zapisuje token na 60 minut w `password_reset_tokens`, a e-mail trafia do `reset-password-emails`.
4. Zmiany użytkowników i ról zapisują historię. Zmiana ról/uprawnień wymusza ponowne logowanie użytkowników, których dotyczy.
5. Awatar jest przycinany do 256×256, kodowany do WebP i zapisywany na skonfigurowanym dysku.

## Wdrożenie produkcyjne

Sail jest konfiguracją developerską — repozytorium nie zawiera gotowego manifestu produkcyjnego. W produkcji użyj nginx/Apache + PHP-FPM (lub docelowych kontenerów), trwałej bazy PostgreSQL, działającej kolejki i prawdziwego SMTP. Nie wystawiaj Mailpit, pgAdmin ani portów baz danych publicznie.

Minimalna procedura po wdrożeniu kodu:

```bash
composer install --no-dev --prefer-dist --optimize-autoloader
npm ci
npm run build
php artisan key:generate --force                 # tylko przy pierwszym wdrożeniu
php artisan migrate --force
php artisan storage:link
php artisan passport:keys --force                # tylko przy pierwszym wdrożeniu / po rotacji kluczy
php artisan optimize
```

Ustaw co najmniej `APP_ENV=production`, `APP_DEBUG=false`, unikalne `APP_KEY`, poprawne `APP_URL` i `GATEWAY_URL`, bezpieczne `DB_*`, `MAIL_*`, `QUEUE_CONNECTION`, parametry S3 (jeżeli używane) oraz `ENABLE_REGISTRATION`/`CONFIRM_EMAIL`. Klucze Passport przechowuj jako sekret. Jeśli brakuje klienta personal access, utwórz go przez `php artisan passport:client --personal`.

Worker uruchom pod Supervisorem, systemd lub orkiestratorem kontenerów:

```bash
php artisan queue:work database --queue=register-emails,reset-password-emails --sleep=3 --tries=3 --max-time=3600
```

Po wdrożeniu wykonaj `php artisan queue:restart`, aby workery podjęły nowy kod. Projekt nie definiuje obecnie własnych zadań harmonogramu, więc osobny proces `schedule:work` nie jest potrzebny.

## Weryfikacja

```bash
./vendor/bin/sail composer test
./vendor/bin/sail pint --test
./vendor/bin/sail artisan route:list --path=api
```

## Wgrywanie zmian produkcyjnie

Skrypt sh/build-and-deploy.sh służy do odświeżenia aplikacji w środowisku produkcyjnym po wdrożeniu zmian.

Przy pierwszym uruchomieniu:

```bash
chmod +x sh/build-and-deploy.sh
./sh/build-and-deploy.sh
```

Kolejne uruchomienia:

```bash
./sh/build-and-deploy.sh
```
