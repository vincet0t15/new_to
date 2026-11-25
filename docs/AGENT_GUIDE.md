# Agent Guide

Conventions and guardrails for coding agents working in this repository. Read [DECISIONS.md](DECISIONS.md) first: anything marked **Approval required** must not be changed as a side effect of a UI or cleanup task.

## 1. Mental model

- One Laravel application with four workspaces: Employee, Department Head, shared Approvals, and Admin.
- Portal prefixes are navigation/landing only; server-side middleware and services enforce access (`app/Enums/Portal.php:5-78`, `app/Http/Middleware/EnsurePortalAccess.php:12-43`).
- One central workflow engine: `WorkflowRouteResolver` → `TravelOrderSubmissionService` → `TravelOrderApprovalService`. Approvals screens are views over that engine, not owners of workflow logic.
- Authorization is delivery-based: permissions gate verbs, live deliveries gate actions.

## 2. Repository conventions

| Concern       | Convention                                                                                           |
| ------------- | ---------------------------------------------------------------------------------------------------- |
| Controllers   | Thin; delegate to services in `app/Services`.                                                        |
| Data access   | Repository contracts in `app/Repositories`, Eloquent implementations in `app/Repositories/Eloquent`. |
| Domain rules  | Services (`TravelOrder*Service`, `TripTicketService`, `WorkflowRouteResolver`).                      |
| Requests      | Form Requests for validation; never trust client-supplied employee/office identity.                  |
| Routes        | Modular files in `routes/`, required from `web.php`; portal routes in `portals.php`.                 |
| Frontend      | Inertia React pages in `resources/js/pages`, shared UI in `resources/js/components`.                 |
| Types         | Hand-synchronized PHP↔TS types in `resources/js/types`; update both sides when props change.         |
| Route helpers | Wayfinder-generated; never hand-edit generated `routes/`, `actions/`, `wayfinder/` trees.            |
| Tests         | Pest Feature tests + fixtures; frontend specs only under `resources/js/test/`.                       |

Regenerate route helpers after any route change:

```bash
php artisan wayfinder:generate --with-form --no-interaction
```

The Vite plugin does this during `npm run dev` / `npm run build`, but explicit generation avoids stale types during `tsc --noEmit`.

## 3. High-risk files — approval gate

Do not change these for workflow, authorization, or data-semantic reasons without explicit approval:

| Area             | Files                                                                                                                                                                                                                                                                                                                          |
| ---------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| Routing/approval | `app/Services/WorkflowRouteResolver.php`, `TravelOrderSubmissionService.php`, `TravelOrderApprovalService.php`, `TravelOrderResubmissionService.php`, `config/travel-orders.php`, `routes/portals.php`, `app/Enums/Portal.php`, `app/Services/PortalResolver.php`, `EnsurePortalAccess.php`, `TravelOrderActionController.php` |
| Filing context   | `app/Http/Controllers/TravelOrders/TravelOrderController.php`, `UpdateTravelOrderRequest.php`, `ResubmitTravelOrderRequest.php`, `app/Models/TravelOrder.php`                                                                                                                                                                  |
| Fuel/receipts    | `app/Http/Controllers/Admin/FuelAllocationController.php`, `Approvals/FuelMonitoringController.php`, `app/Services/TripTicketService.php`, fuel migrations, trip-ticket Form Requests                                                                                                                                          |
| Notifications    | `app/Support/NotificationUrl.php`, `app/Notifications/TravelOrderNotification.php`, `NotificationController.php`, `RedirectInaccessibleDocument.php`                                                                                                                                                                           |
| Seed credentials | `config/seed-credentials.php`, `config/superadmin.php`, `app/Concerns/ResolvesSeedCredentials.php`, seeders                                                                                                                                                                                                                    |

Never directly edit `routed_steps`, `current_step_index`, or `travel_order_deliveries` rows. Use the designation impact + reassign flow (`CODEBASE_GUIDE.md §16.3-16.4`). The safe diagnostic is:

```bash
php artisan travel-orders:check-integrity
php artisan travel-orders:check-integrity --json --fail-on-warning
```

It is read-only and never repairs data.

## 4. Safe change patterns

### Adding or changing a page

1. Reuse an existing page in the same folder as the template.
2. Keep Inertia component name and prop names stable unless there is a reason to change them; update `resources/js/types` and the corresponding controller in the same commit.
3. Keep page roots `min-w-0` and avoid `overflow-x-auto` at the page level; scope overflow to the table/card that needs it.
4. Use shared components: `Heading`, `Pagination`, `ConfirmDialog`, `TableEmptyState`, `Combobox`/`CustomComboBox`, `InputError`, `PasswordInput`.

### Adding an approval action

1. Add the route + permission in `routes/portals.php` and `RolesAndPermissionsSeeder.php`.
2. Add the service method in `TravelOrderApprovalService` with delivery/state checks before mutation.
3. Expose ability flags from `ApprovalInboxController::show()` and render a button in `resources/js/pages/approvals/show.tsx`.
4. Regenerate Wayfinder, add a Feature test and a frontend interaction test, then run the full gate.

Do not add "approve and choose next recipient"; `Approve` advances and `Forward` passes to another person at the same step.

### Changing filters/pagination

- Prefer the shared `Pagination` component; it renders a labelled `nav`, `aria-current`, disabled spans, and no raw HTML.
- Keep filter state in local component state, send queries with `router.get(..., { preserveState: true, preserveScroll: true })`, and mirror backend `filters` props.
- Backend must call `->withQueryString()` so pagination links retain filters.

### Changing master data

Follow the Office slice: migration, model, repository contract + implementation, provider binding, Form Requests, controller, route file + `require`, seeder permissions, TS types, pages, nav, tests.

## 5. UI audit rules to preserve

- Canonical approval label is **Approval Inbox**. Personal `/inbox` is the Employee-shell notification inbox.
- No All/Unread/Read filter on the personal notification inbox; unread bold, read normal, pagination retained. "Mark all read" posts to `inbox.read-all` and disables at zero unread.
- Owner Edit / Create itinerary / Create trip-ticket actions stay hidden while submitted/processing; show them only in Draft or Returned.
- Employee create/edit never shows **Fund source**; Budget Officer assigns fund source and earmark number; HRMO records series/control/recording/distribution.
- Default locations are `San Vicente, Palawan` and `Puerto Princesa City, Palawan`; fuel flows use `CustomComboBox`.
- No native `confirm()`; use `ConfirmDialog`. No raw `JSON.stringify(event.meta)`; use `eventDetailLines()`.
- Dialog `Content` scrolls at `max-h-[90vh]`; `Combobox` measures viewport/dialog and flips or caps dropdown height.

## 6. Verification before finishing

```bash
vendor/bin/pint --test --dirty
php artisan test
vendor/bin/phpstan analyse --no-progress
npm run types:check
npm run test
npm run check
npm run build
php artisan travel-orders:check-integrity
```

Caveats:

- `vendor/bin/pint --test` over the whole repository fails on pre-existing files; use `--dirty`.
- PowerShell blocks `npx`/`npm.ps1`; use `npm.cmd ...` on Windows.
- CI does not run `npm test`; run it locally.
- Wayfinder output is gitignored and must be generated before type checks on a fresh checkout.
- Frontend and PHP domain types are synchronized manually.

## 7. Operational safety

- Do not push, open a PR, or merge unless explicitly asked.
- Commit locally with a conventional commit message describing the behavior change.
- Never put secrets in source; use `.env` and the seed credential config.
- Production `optimize:clear` / config-cache rebuild is required after changing config values such as `TRAVEL_ORDER_STALLED_AFTER_DAYS`.
- If a production path 500s after deployment, clear caches and read `storage/logs/laravel.log` before changing code.

See [DEPLOYMENT.md](DEPLOYMENT.md) for release steps and [TESTING.md](TESTING.md) for test details.
