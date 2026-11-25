# Deployment Guide

This guide covers a production release of the LGU Travel Order System. It assumes a standard Laravel web-server setup (PHP-FPM + Nginx/Apache) with a database and a queue worker.

## 1. Requirements

| Concern        | Requirement                                                                             | Evidence                                                                                                        |
| -------------- | --------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------- |
| PHP            | `^8.3` in Composer; CI pins 8.5                                                         | `composer.json:12`, `.github/workflows/tests.yml:22-27`                                                         |
| Composer       | 2.x                                                                                     | CI workflow                                                                                                     |
| Node.js        | 22 LTS (no `engines` field in `package.json`)                                           | `.github/workflows/tests.yml:29-32`                                                                             |
| Laravel        | `^13.17`                                                                                | `composer.json:16`                                                                                              |
| Database       | MySQL 8+ recommended; SQLite/PostgreSQL/SQL Server configured but SQLite is the default | `config/database.php:35-115`                                                                                    |
| Writable paths | `storage/**` and `bootstrap/cache` for the web user and CLI user                        | standard Laravel requirement; `config/session.php:63`, `config/cache.php:50-54`, `config/filesystems.php:41-48` |

A queue worker is required because notifications implement `ShouldQueue` and the default queue connection is `database` (`app/Notifications/TravelOrderNotification.php:8-22`, `config/queue.php:16`).

There is no application-specific scheduler entry in `routes/console.php`.

## 2. Environment

Start from `.env.example`. At minimum, set:

```dotenv
APP_NAME="LGU Travel Order System"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://hrmis.site
APP_KEY=base64:...            # php artisan key:generate on first install

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=toms
DB_USERNAME=...
DB_PASSWORD=...

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
CACHE_STORE=database
QUEUE_CONNECTION=database
MAIL_MAILER=smtp
MAIL_HOST=...
MAIL_PORT=...
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_FROM_ADDRESS=...
```

Notes:

- `APP_ENV` defaults to `production` and `APP_DEBUG` defaults to `false` in `config/app.php:29,42`; never rely on `.env.example`'s local values in production.
- `APP_URL` is used for absolute email links and public file URLs (`config/filesystems.php:41-48`).
- Timezone is fixed to `UTC` (`config/app.php:68`).
- Session/cache/queue tables (`sessions`, `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`) are created by the base migrations.
- Maintenance mode defaults to the `file` driver with a `database` store; switch to `cache` if you need multi-machine control (`config/app.php:117-124`).

### Seeded credentials

Production seeding refuses to run unless the required passwords are set. Existing account passwords are never reset by re-seeding.

| Variable                     | Used by             | Notes                                                                         |
| ---------------------------- | ------------------- | ----------------------------------------------------------------------------- |
| `SUPERADMIN_PASSWORD`        | `SuperAdminSeeder`  | Mandatory in production (`database/seeders/SuperAdminSeeder.php:38-79`).      |
| `DEMO_SEED_PASSWORD`         | `DemoDataSeeder`    | Required if you explicitly seed demo data (`config/seed-credentials.php:18`). |
| `KEY_OFFICIAL_SEED_PASSWORD` | `KeyOfficialSeeder` | Required if you seed named officials (`:20`).                                 |
| `SEED_FORCE_PASSWORD_CHANGE` | All seeders         | Optional `true`/`false`; defaults off in `local`/`testing`, on elsewhere.     |

### Registration throttling

Public `POST /register` is limited per IP address to 5 attempts per 60 seconds by default. Override the limit with `REGISTRATION_RATE_LIMIT_ATTEMPTS` and `REGISTRATION_RATE_LIMIT_DECAY_SECONDS` (`config/fortify.php`). Requests over the limit receive HTTP 429 with a `Retry-After` header.

`DatabaseSeeder` seeds roles, the super administrator, offices, and reference data. It does **not** seed demo or key-official accounts; run those seeders explicitly if needed:

```bash
php artisan db:seed --force
php artisan db:seed --class=KeyOfficialSeeder --force
# Demo data is local/staging only:
php artisan db:seed --class=DemoDataSeeder --force
```

### Travel-order and fuel settings

Add these to `.env` when they differ from the defaults in `config/travel-orders.php`:

```dotenv
TRAVEL_ORDER_RESUBMISSION_POLICY=restart_from_first_affected
TRAVEL_ORDER_STALLED_AFTER_DAYS=3
FUEL_LOW_BALANCE_LITERS=20
FUEL_LOW_BALANCE_AMOUNT=1000

TRAVEL_ORDER_REPUBLIC="Republic of the Philippines"
TRAVEL_ORDER_PROVINCE="Palawan"
TRAVEL_ORDER_MUNICIPALITY="San Vicente"
TRAVEL_ORDER_OFFICE="Municipality of San Vicente"
TRAVEL_ORDER_STATION="..."
TRAVEL_ORDER_STATION_LINE="..."
```

`TRAVEL_ORDER_STALLED_AFTER_DAYS` is clamped to at least `1` and drives the admin stalled-approval panel only; it never auto-approves or reassigns (`config/travel-orders.php:96-108`).

## 3. Release steps

```bash
# 1. Install PHP dependencies
composer install --no-dev --prefer-dist --optimize-autoloader

# 2. Install frontend dependencies and build assets
npm ci
npm run build
# Wayfinder output is generated by the Vite plugin during dev/build; to generate explicitly:
php artisan wayfinder:generate --with-form --no-interaction

# 3. Configure the environment and framework caches
php artisan migrate --force
php artisan storage:link
php artisan optimize
```

For an existing release, the safe order is:

```bash
git pull --ff-only
composer install --no-dev --prefer-dist --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan optimize:clear
php artisan optimize
php artisan queue:restart
```

If `/admin/dashboard` returns a 500 after deployment, run `php artisan optimize:clear` and capture the fresh exception from `storage/logs/laravel.log` before making code changes.

### Storage

Trip-ticket receipts are stored on the `public` disk under `trip-ticket-receipts` (`app/Services/TripTicketService.php:264-281`). Validation accepts JPG/JPEG/PNG/PDF up to 5 MB (`app\Http\Requests\TripTickets\CompleteTripTicketRequest.php:22`). Keep `public/storage` pointing at `storage/app/public`:

```bash
php artisan storage:link
```

Generated/ignored artifacts that must exist at runtime: `public/build`, `public/storage`, `storage/framework/{cache,sessions,views}`, `storage/logs`, `storage/app/public`, and `bootstrap/cache` (`.gitignore:2-13`).

### Queue worker

Run a supervisor-managed worker so queued notification emails are delivered:

```bash
php artisan queue:work --tries=3
```

Use `php artisan queue:restart` after each deploy so workers pick up new code. Failed jobs land in `failed_jobs` and can be retried with `php artisan queue:retry all`.

## 4. Post-deployment checks

```bash
php artisan travel-orders:check-integrity
php artisan travel-orders:check-integrity --json --fail-on-warning
```

The integrity command is read-only: it never repairs data. Exit `1` means errors exist (or warnings with `--fail-on-warning`) (`CODEBASE_GUIDE.md:458-465`).

Also verify:

1. Sign-in with the configured super administrator and one approving role.
2. `/approvals/inbox` loads and the `Approval Inbox` label is visible.
3. `/admin/dashboard` loads; stalled and fuel panels render without 500s.
4. A trip ticket receipt can be uploaded and its URL opens under `/storage/trip-ticket-receipts/...`.
5. A notification opens and its in-app link stays on the current host.
6. `php artisan route:list` succeeds after `optimize`.

## 5. Rollback

1. Put the app in maintenance mode if the release is user-visible.
2. Redeploy the previous commit and rerun `composer install`, `npm ci`, `npm run build`.
3. Roll back only forward-compatible migrations; do not hand-edit delivery or workflow rows.
4. Run `php artisan optimize:clear && php artisan optimize`, then `php artisan queue:restart`.
5. Clear maintenance mode and re-run the integrity and smoke checks above.

Workflow data changes are rescue operations, not rollback operations: use the documented designation impact and reassign flows (`CODEBASE_GUIDE.md §16.3-16.4`) rather than editing `routed_steps`, `current_step_index`, or delivery rows directly.

## 6. Related documents

- [README.md](../README.md) — local setup and verification commands.
- [TESTING.md](TESTING.md) — test suites and quality gates.
- [DECISIONS.md](DECISIONS.md) — invariants that must survive deployment and maintenance.
- [CODEBASE_GUIDE.md](../CODEBASE_GUIDE.md) — admin runbook and source map.
