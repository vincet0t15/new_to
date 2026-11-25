# LGU Travel Order System

An internal travel-order workflow application for the Municipality of San Vicente, Palawan. Employees file and track travel orders; office designations determine a configurable approval chain; Budget, Mayor, Vice Mayor, and HRMO reviewers act in one shared approval inbox; HRMO records series/control numbers and trip tickets.

The application is a Laravel 13 + Inertia.js 3 + React 19 project built on the Laravel React starter kit.

## What it does

- Draft, submit, return, resubmit, and complete travel orders with an immutable filing context after submission.
- Office-aware routing driven by workflow steps and designations, with a frozen route per submitted order.
- Shared `/approvals` inbox and history for every approving role. There are deliberately **no separate Budget/Mayor/HRMO portals**.
- Itineraries (Appendix A), per-diem computation, printed travel orders, and trip tickets.
- Fuel allocation/transfer ledger with paired debit/credit entries, void-with-reason, and monitoring.
- In-app notifications, audit trail, admin register, reports, and read-only integrity checks.

See [CODEBASE_GUIDE.md](CODEBASE_GUIDE.md) for the source-derived architecture map.

## Requirements

| Tool     | Version                                            |
| -------- | -------------------------------------------------- |
| PHP      | 8.3+ (CI uses 8.5)                                 |
| Composer | 2.x                                                |
| Node.js  | 22 LTS                                             |
| Database | MySQL 8+ recommended; SQLite works for local tests |

PHP extensions required by Laravel: `bcmath`, `ctype`, `curl`, `dom`, `fileinfo`, `filter`, `hash`, `mbstring`, `openssl`, `pcre`, `pdo`, `session`, `tokenizer`, `xml`.

The frontend uses `vite-plus` (`vp`) commands exposed through `package.json`; use `npm` (or `pnpm` if you keep the committed lockfile) consistently.

## Local setup

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Configure `DB_*` in `.env`, then:

```bash
php artisan migrate --seed
php artisan wayfinder:generate --with-form --no-interaction
npm install
npm run dev
```

In a second terminal:

```bash
php artisan serve
```

Wayfinder-generated files under `resources/js/routes`, `resources/js/actions`, and `resources/js/wayfinder` are gitignored. Regenerate them after any route change; type-checking and the Vite build depend on them.

### Seed credentials

Seeded passwords are read from configuration, not hardcoded in seeders:

- `DEMO_SEED_PASSWORD` — demo employee/head/reviewer accounts.
- `KEY_OFFICIAL_SEED_PASSWORD` — named key officials.
- `SUPERADMIN_PASSWORD` — bootstrap super administrator.
- `SEED_FORCE_PASSWORD_CHANGE` — optional `true`/`false` override; defaults off in `local`/`testing` and on elsewhere.

Local and test installs fall back to the historical development password so existing workflows keep working. Production installs must set the required passwords explicitly. Re-seeding never resets an existing account's password. See `config/seed-credentials.php`, `config/superadmin.php`, and the admin runbook in `CODEBASE_GUIDE.md §16.5`.

## Development workflow

```bash
# Backend + frontend in one command
composer dev

# Frontend only
npm run dev

# Regenerate typed route helpers after route changes
php artisan wayfinder:generate --with-form --no-interaction
```

## Verification

Run these before committing:

```bash
vendor/bin/pint --test --dirty
php artisan test
vendor/bin/phpstan analyse --no-progress
npm run types:check
npm run test
npm run check
npm run build
```

`vendor/bin/pint --test` over the whole repository currently reports pre-existing style findings in `app/Providers/AppServiceProvider.php` and `bootstrap/app.php`; use `--dirty` for changed files. See [docs/TESTING.md](docs/TESTING.md) for full details.

## Repository layout

| Path                                                       | Purpose                                                                            |
| ---------------------------------------------------------- | ---------------------------------------------------------------------------------- |
| `app/Http/Controllers`, `app/Services`, `app/Repositories` | Request handling and domain logic.                                                 |
| `app/Support`, `app/Enums`                                 | Shared helpers, URL normalization, and domain enums.                               |
| `routes/`                                                  | Route files required from `web.php`; portal-specific routes live in `portals.php`. |
| `resources/js/pages`, `resources/js/components`            | Inertia pages and shared UI.                                                       |
| `resources/js/test`                                        | Frontend jsdom/Testing Library specs (never place specs under `pages/`).           |
| `database/`                                                | Migrations, factories, and seeders.                                                |
| `tests/`                                                   | Pest backend suites and routing helpers.                                           |
| `docs/`                                                    | Decisions, deployment, testing, and agent guidance.                                |

## Documentation

- [CODEBASE_GUIDE.md](CODEBASE_GUIDE.md) — architecture, request lifecycle, domain model, and admin runbook.
- [docs/DECISIONS.md](docs/DECISIONS.md) — workflow, authorization, and data decisions that must not be changed casually.
- [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md) — production build, environment, and storage requirements.
- [docs/TESTING.md](docs/TESTING.md) — test suites, fixtures, commands, and known caveats.
- [docs/AGENT_GUIDE.md](docs/AGENT_GUIDE.md) — conventions and high-risk areas for coding agents.

## Guiding constraints

- Authorization is **delivery-based**: an action requires a live delivery at the current step. There is no superadmin routing bypass.
- `Approve` advances the workflow; `Forward` passes the same step to another person. These are not interchangeable.
- `Receive` is a read receipt only and never advances a step.
- Employee create/edit screens do not expose fund source; the Budget Officer assigns fund source and earmark number during approval.
- Fuel movements are ledger entries (`allocation|transfer`) written as a paired debit/credit in one database transaction; they are voided with a reason, never hard-deleted.
- Notifications use in-app relative links and absolute email links; legacy absolute rows are normalized when displayed or opened.
- Do not split the shared approval portal or bypass the central workflow engine.
