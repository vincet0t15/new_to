# Testing Guide

This project uses Pest 5 for the backend and Vite Plus / Vitest with Testing Library for the frontend. PHP Feature tests are the integration layer; there is no separate Dusk/Playwright/Cypress harness. Browser QA is manual.

## 1. Commands

### Backend

```bash
php artisan test                              # Pest (Unit + Feature)
composer test                                 # config:clear -> lint:check -> types:check -> artisan test
composer lint                                 # Pint --parallel (fixes)
composer lint:check                           # Pint --parallel --test (check only)
composer types:check                          # PHPStan via Larastan
vendor/bin/pint --test --dirty                # preferred local style check
vendor/bin/phpstan analyse --no-progress      # preferred local static analysis
```

### Frontend

```bash
npm test                 # vp test (jsdom + Testing Library)
npm run test             # alias of npm test
npm run types:check      # tsc --noEmit
npm run check            # vp check (format + lint, type-aware)
npm run check:fix        # vp check --fix
npm run build            # vp build
npm run build:ssr        # vp build && vp build --ssr
```

### Combined

```bash
composer ci:check        # npm run check + npm run types:check + composer test
```

CI does **not** run `npm test`; frontend specs must be run locally.

### Full pre-commit gate

```bash
vendor/bin/pint --test --dirty
php artisan test
vendor/bin/phpstan analyse --no-progress
npm run types:check
npm run test
npm run check
npm run build
```

Last full run: **370 backend tests (368 passed, 2 skipped), 2380 assertions**; PHPStan 0 errors; frontend **10 files / 26 tests** passing.

## 2. Backend configuration

| Concern           | Value                                                                                                    | Evidence                                         |
| ----------------- | -------------------------------------------------------------------------------------------------------- | ------------------------------------------------ |
| Test framework    | Pest 5 + pest-plugin-laravel 5, PHPUnit 13                                                               | `composer.json:21-32`, `composer.lock`           |
| Suites            | `Unit` → `tests/Unit`, `Feature` → `tests/Feature`                                                       | `phpunit.xml:7-14`                               |
| Database          | SQLite in memory (`:memory:`), foreign keys on                                                           | `phpunit.xml:26-28`, `config/database.php:35-45` |
| Feature bootstrap | All Feature tests use `Tests\TestCase` + `RefreshDatabase`                                               | `tests/Pest.php:20-22`                           |
| Vite              | `withoutVite()` — PHP tests do not need built assets                                                     | `tests/TestCase.php:14-16`                       |
| Env isolation     | array cache/session/mail, sync queue, null broadcast, bcrypt 4 rounds                                    | `phpunit.xml:21-34`                              |
| PHPStan           | Larastan level 7 over `app/`, `bootstrap/app.php`, `config/`, `database/`, `routes/`; tests not analyzed | `phpstan.neon:1-13`                              |
| Pint              | `laravel` preset, no excludes                                                                            | `pint.json:1-3`                                  |
| CI                | push to `main` / PRs; PHP 8.5, Composer 2, Node 22; `composer setup` then `composer ci:check`            | `.github/workflows/tests.yml:1-38`               |

There is no coverage block in `phpunit.xml`; CI runs with `coverage: none`.

## 3. Backend test areas

Feature suites include:

- Auth/settings: `tests/Feature/Auth`, `tests/Feature/Settings` (including forced password change).
- Portals/dashboards: `tests/Feature/Portals`, `tests/Feature/DashboardTest.php`.
- Administration: roles, accounts, admin operations, seeders.
- Master data and workflow setup: offices, employees, positions, designations, workflows, travel setup.
- Travel-order domain: access, header, routing, itinerary, revisions, print, earmark/record, register filters, trip tickets.
- Approvals: history, document preview.
- Notifications: header, inbox, link normalization, inaccessible-document redirect.
- Console: `travel-orders:check-integrity`.
- Fuel monitoring/allocations.

Most HTTP tests assert Inertia payloads with `AssertableInertia`, which keeps page props and component names under test.

## 4. Shared PHP fixtures and helpers

`tests/Feature/TravelOrders/routing_helpers.php` is globally required from `tests/Pest.php:50`:

| Helper                              | Purpose                                                          |
| ----------------------------------- | ---------------------------------------------------------------- |
| `routingEmployee(?Office)`          | Employee with an office.                                         |
| `routingUser(SystemRole, Employee)` | User linked to that employee and assigned a seeded role by slug. |
| `routingChain()`                    | Full Department Head → Budget → HRMO chain with designations.    |
| `routingDraft()`                    | Draft owned by the fixture filer.                                |
| `routingSubmit()`                   | Submits that draft through `TravelOrderSubmissionService`.       |

`grantAdminPortal(User)` assigns the Admin role without permissions, so tests remain honest about permission gating (`tests/Pest.php:52-73`). `skipUnlessFortifyHas()` skips tests for disabled Fortify features (`tests/TestCase.php:19-24`).

Factories live in `database/factories/`. Notable ones: `UserFactory` (password `password`), `TravelOrderFactory`, `WorkflowStepTypeFactory::receipt()`, office/designation/fuel/trip-ticket factories.

## 5. Frontend test setup

- Config: `vite.config.ts:42-46` — `environment: 'jsdom'`, `include: ['resources/js/**/*.test.{ts,tsx}']`, setup file `resources/js/test/setup.ts`.
- Setup imports `@testing-library/jest-dom/vitest` and calls cleanup after each test (`resources/js/test/setup.ts:1-7`).
- Alias `@/*` → `./resources/js/*` (`tsconfig.json:110-112`).
- Fixtures: `resources/js/test/fixtures.ts` — `makeTravelOrder()`, `makeTripTicket()`, letterhead/signatories, `makeAbilities()`.
- Keep specs under `resources/js/test/`, never `resources/js/pages/`: Inertia's resolver globs every `.tsx` under `pages/` and would ship tests as page chunks.

Current specs cover: approval actions, audit filters, combobox semantics, confirm dialog, input error ARIA, notification "mark all read", shared pagination, admin reassign dialog/register filters, and trip-ticket document.

Inertia modules are mocked with `vi.fn()` spies for `router`, `useForm`, `usePage`, `Link`, and `Head`. UI components are mocked where focus or rendering depth would obscure the behavior under test.

## 6. Fakes and isolation

- PHP global env: array mail/cache/session, sync queue, null broadcast (`phpunit.xml:24-31`).
- `Notification::fake()` / `assertSentTo()` in return and resubmission tests (`tests/Feature/TravelOrders/TravelOrderRevisionTest.php:263-289`).
- `Storage::fake('public')` and `UploadedFile::fake()` in employee photo tests (`tests/Feature/Employees/EmployeeTest.php:155-184,294-325`).
- No `Http::fake()`, `Mail::fake()`, `Bus::fake()`, or Mockery mocks are currently used; there are no external HTTP integrations in application code.
- JS fakes use Vitest `vi`; no @vitest/browser project is configured.

## 7. Known caveats

1. **Two skipped tests** — 2FA cases in `tests/Feature/Settings/SecurityTest.php` are skipped because `config/fortify.php:145-147` enables only registration. This is expected, not a failure.
2. **Whole-repo Pint** — `composer lint`/`lint:check` run `pint --parallel` with no path and `pint.json` has no excludes, so they can visit generated/ignored PHP under `storage/framework/views`. Pre-existing fixer findings in `app/Providers/AppServiceProvider.php` (`unary_operator_spaces`, `not_operator_with_successor_space`, `single_line_after_imports`, `ordered_imports`) and `bootstrap/app.php` (`statement_indentation`) also fail whole-repo checks. Use `vendor/bin/pint --test --dirty` locally.
3. **Generated Wayfinder code** — `resources/js/actions`, `resources/js/routes`, and `resources/js/wayfinder` are gitignored. Generate with `php artisan wayfinder:generate --with-form --no-interaction` (or via `npm run dev`/`npm run build`) before type-checking, linting, or running JS tests on a fresh checkout.
4. **CI does not run frontend specs** — run `npm test` locally.
5. **No portal-switcher spec exists** despite older notes in `CODEBASE_GUIDE.md`; the current specs are listed above.
6. **Manual type sync** — PHP↔TS domain types are hand-maintained; changing a backend prop requires updating `resources/js/types` manually.
7. **Manual browser QA** — no automated e2e suite. Paths worth checking by hand: admin stalled panel, designation impact dialog, reassign toast/audit row, all document-preview tabs, forced-password banner, combobox keyboard/label behavior, and no horizontal overflow at narrow widths.

## 8. Writing new tests

- Backend: place Feature tests under the matching directory in `tests/Feature`, seed `RolesAndPermissionsSeeder` when role slugs or permissions matter, and use the routing helpers rather than hand-building approval chains.
- Frontend: colocate as `resources/js/test/<area>.test.tsx`, mock Inertia at the module boundary, and assert user-visible behavior (labels, roles, disabled state) rather than implementation details.
- Any change to workflow, authorization, fuel, receipts, or notification URLs must include a regression test and an update to [DECISIONS.md](DECISIONS.md).
