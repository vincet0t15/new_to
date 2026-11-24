# newTO — Codebase Guide

A source-derived map of this application: what it is, how a request flows through it, the domain logic, and where to look when changing something. Built from a read-only deep analysis of the code as of 2026-11-20; operational additions through 2026-11-24 are in §16. Citations are `path:line`.

> This document is descriptive, not prescriptive. It records how the code **currently** behaves, including known gaps (see §14).

---

## 1. What this project is

An **LGU Travel Order System** (app name `LGU Travel Order System`) for the Municipality of San Vicente, Palawan. Employees file travel orders; a configurable, office-aware approval chain routes them; approved orders get itineraries (Appendix A), per-diem computation, and trip tickets. Four role-based "portals" share one set of models and services.

It started as the Laravel React starter kit and was extended into this workflow application.

### 1.1 Stack

| Concern           | Choice                                                                       | Evidence                                                        |
| ----------------- | ---------------------------------------------------------------------------- | --------------------------------------------------------------- |
| Backend           | Laravel `^13.17`, PHP `^8.3`                                                 | `composer.json:12,16`                                           |
| Inertia           | `inertiajs/inertia-laravel ^3`, `@inertiajs/react ^3`, `@inertiajs/vite ^3`  | `composer.json:13`; `package.json:14-15`                        |
| Frontend          | React 19.2, TypeScript 5.7, Tailwind v4, shadcn/ui "new-york", Radix, lucide | `package.json:30-43`; `components.json:3-20`                    |
| Build             | Vite Plus 1.4.0 (`vp`), React Compiler, Bunny Fonts                          | `package.json:45,52,63-65`; `vite.config.ts:10-30`              |
| Routing to client | Laravel Wayfinder (generated `actions/**`, `routes/**`, `wayfinder/`)        | `composer.json:18`; `package.json:48`; `vite.config.ts:2,27-29` |
| Auth              | Laravel Fortify (username-based) + Spatie `laravel-permission 8.3`           | `composer.json:15,19`                                           |
| Tests / QA        | Pest 5, PHPUnit, Larastan level 7, Pint, Laravel Boost                       | `composer.json:23-32`; `phpstan.neon:13`; `boost.json`          |

---

## 2. Architecture at a glance

```
Browser
  │  Blade document (theme flash-prevention, @vite includes page chunk)
  ▼
resources/js/app.tsx ── Inertia ── layout (by page name) ── page ── components/ui/*
  │  @/routes/* (Wayfinder-generated URLs) / server-supplied urls props
  ▼
routes/web.php → 20 required route files
  │  middleware: auth, verified, portal:<key>, permission:<name>
  ▼
Controller (ownership checks) ── FormRequest (validation)
  ▼
Service (domain rules, DB::transaction + lockForUpdate)
  ▼
Repository contract → Eloquent implementation (master data)
  ▼
MySQL/SQLite ── Inertia response (+ flash toast) ──► React (Sonner, useForm)
```

Layers are **not** enforced by namespace rules; authorization is deliberately split into three places (§11) and there are no Laravel Policy classes.

---

## 3. Request lifecycle / boot sequence

1. **Application bootstrap** — `bootstrap/app.php:15-41` configures routing (`routes/web.php`), console, health `/up`, encrypts cookies except `appearance` and `sidebar_state` (`:22`), appends `HandleAppearance`, `HandleInertiaRequests`, `AddLinkHeadersForPreloadedAssets` to the web group (`:24-28`), and registers aliases `role`, `permission`, `role_or_permission`, `portal` (`:30-35`).
2. **Route registry** — `routes/web.php:7-18` defines welcome/dashboard/inbox, then `:20-39` requires 20 feature route files (portals, settings, offices, workflows, travel-orders, setup tables, roles, accounts).
3. **Root view** — `HandleInertiaRequests.php:25` sets root view `app`. `resources/views/app.blade.php` applies the `dark` class before hydration (`:2`, `:8-20`), emits `@fonts` (`:37`), and `@vite([css, app.tsx, "pages/{$page['component']}.tsx"])` (`:39-40`) so the active page is in the initial document.
4. **Client boot** — `resources/js/app.tsx:11-39`: title suffix (`:12`), layout by page name (`:13-26` — `welcome` and `trip-tickets/print` get none, `auth/*` → AuthLayout, `settings/*` → [AppLayout, SettingsLayout], everything else → AppLayout), global `TooltipProvider` + `Toaster` (`:28-35`), `initializeTheme()` (`:42`).
5. **Shared Inertia props** — `HandleInertiaRequests.php:56-71`: `name`, `auth.user`/`roles`/`permissions`, `portals`, `currentPortal`, `sidebarOpen`, `notifications` (latest 8), `unreadNotificationsCount`. Typed on the client in `types/global.d.ts:11-23`.

**SSR note:** configured but not implemented in-repo — `config/inertia.php:18-23` enables it with a URL but the `bundle` path is commented out and `bootstrap/ssr/` is gitignored. Client code is SSR-safe (`use-appearance.tsx` guards `window`/`document`). Tests call `withoutVite()` (`tests/TestCase.php:14-16`).

---

## 4. Domain data model

### 4.1 Organization & configuration

| Table / model                               | Role                                                                                                                        |
| ------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------- |
| `users` (`User`)                            | Auth identity; nullable unique `employee_id`; role/permission helpers (`User.php:55-131`)                                   |
| `employees` (`Employee`)                    | HR record (office, position, employment type); inverse `user()` (`Employee.php:138-149`)                                    |
| `offices` (`Office`)                        | Filing office; optional `workflow_id` override + `signatory_group_id` + `itinerary_signatory_group_id` (`Office.php:21-36`) |
| `employment_types`, `positions`             | Employee reference data                                                                                                     |
| `workflows` (`Workflow`)                    | Named approval chain; `is_default`, `is_active`; ordered `steps()` by `sort_order,id` (`Workflow.php:46-49`)                |
| `workflow_step_types` (`WorkflowStepType`)  | Reusable step meaning; `is_signatory` decides approve vs acknowledge (`WorkflowStepType.php:72-75`)                         |
| `workflow_steps` (`WorkflowStep`)           | One leg; optional `office_id` (null = filing office), `sort_order`, `auto_skip_creator`, `reviewed_fields`                  |
| `office_designations` (`OfficeDesignation`) | `(office, step type) → employee`, `is_deputy`, effective dates, `is_active` (`OfficeDesignation.php:24-46`)                 |

### 4.2 Travel order & runtime

| Table / model                   | Role                                                                                                                                                                                                                   |
| ------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `travel_orders` (`TravelOrder`) | The aggregate root. Header/lookup fields, status, **frozen route** (`routed_steps`, `routed_step_review_fields`, `signatories`), `current_step_index`, `recipient_user_id`, revision fields (`TravelOrder.php:18-114`) |
| `travel_order_deliveries`       | One row per holder per step; `step_index`, `step_type_id`, status `pending/received/acted/closed`                                                                                                                      |
| `travel_order_actions`          | Append-only audit trail of `WorkflowEvent`s with actor/remarks/meta                                                                                                                                                    |
| `travel_order_revisions`        | One row per return/resubmit round: change type, changed/previous/new data                                                                                                                                              |
| `notifications`                 | Standard Laravel notifications                                                                                                                                                                                         |
| `travel_order_itineraries`      | Place-to-place legs with fare/per diem/registration and `*_is_auto` flags                                                                                                                                              |
| `trip_tickets`                  | Vehicle/fuel/odometer data, ticket no, status `issued/completed/cancelled`; `fuel_source_office_id`, optional `fuel_type_id`, `fuel_receipt_path`; actual `fuel_consumed`/`fuel_cost` written on completion            |
| `fuel_types` (`FuelType`)       | Admin-managed fuel catalogue (`name`, `code`, `is_active`), seeded with Philippine fuel names; referenced by trip tickets and allocations                                                                              |
| `fuel_allocations`              | Append-only office fuel ledger: `type` `allocation                                                                                                                                                                     | transfer`, signed liters/amount (transfer = paired debit/credit), status `active | voided`, source/related links, void audit fields  |
| `fuel_allocation_events`        | Append-only allocation history: `created                                                                                                                                                                               | updated                                                                          | voided` with actor, remarks and before/after meta |

### 4.3 Travel setup & printing

`transportation_modes` + `transportation_fares`, `expense_types`, `fund_sources`, `locations`, `travel_classifications`, `per_diem_configurations` + meal rules + location pivot (`2026_11_18_000006/000007`), `signatory_groups` (3-line for the TO) and `itinerary_signatory_groups` (2-line for Appendix A). Fuel setup (`fuel_types`) is managed from the admin sidebar and is part of travel operations rather than travel-order routing.

---

## 5. The travel-order lifecycle (the core flow)

```
Draft ──submit()──► Submitted ──approve()/acknowledge() per step──► Approved
  ▲                    │  returnTo()                                  │
  │                    ▼                                              ▼
  └──resubmit()──── Returned                                   operational use
```

Trip tickets are independent of the approval state: they can be prepared for **any** order that uses a government vehicle and move `issued → completed/cancelled` (§7.3).

Statuses: `draft`, `submitted`, `approved`, `returned`, `cancelled`, `completed`, `rejected` (`TravelOrderStatus.php:8-16`). Only `submitted` is "open"; `draft` and `returned` are creator-editable (`:49-63`).

> **Important:** the terminal state actually written is **`Approved`**, not `Completed` — `TravelOrderApprovalService::complete()` (`:534-545`). `Completed`, `Cancelled`, `Rejected`, `PulledBack`, and `WorkflowEvent::Cancelled` are declared/presented but have no producer (§14).

### 5.1 Draft creation

`TravelOrderController::store()` validates via `StoreTravelOrderRequest`, creates a Draft, syncs pivots, and optionally submits in the same transaction (`TravelOrderController.php:110-155`). Header validation requires classification, destination, purpose, and valid dates; bounded mode/expense arrays; `Others` expense requires `other_expenses` (`StoreTravelOrderRequest.php:28-95`). Edits are owner + draft/returned only (`:157-229`); deletion is owner + draft only (`:404-422`). Header writes are centralized in `contentPayload()` which also maintains legacy text mirrors (`:502-540`); pivots are synced only when the request includes them (`:543-560`).

### 5.2 Submission freeze

`TravelOrderSubmissionService::submit()` (`:36-109`) is the **single freeze point**, inside `DB::transaction` + `lockForUpdate()`:

1. Owner + Draft + office + workflow + non-empty steps checks (`:41-65`).
2. Preflight: **every** step must have a current designation with a login; at least one step must remain deliverable after excluding the filer (`:120-154`).
3. Freeze `workflow_id`, `routed_steps` (step IDs), `routed_step_review_fields` (parallel array), `signatories`, `status=submitted`, `submitted_at`; clear step/recipient pointers (`:69-94`).
4. Audit `Submitted` (`:96`) and call `approvals->advance($order, 0, $actor)` (`:98-103`).

**Core principle:** the route _shape_ is frozen; holder _identity_ is resolved live at each step. Later admin edits to workflow config cannot move a filed order.

### 5.3 The delivery walk

`TravelOrderApprovalService::advance()` (`:307-387`) walks the frozen step IDs from a start index:

- Loads each step; missing steps → `StepSkipped`.
- Resolves current designations/recipients via `WorkflowRouteResolver`.
- No recipients → audit `FilerWithheld` (if the filer was the only holder and `auto_skip_creator`) or `StepSkipped` (`:498-532`).
- Otherwise: close open deliveries, create one `Pending` delivery per recipient (primary before deputy), set `current_step_index`/`recipient_user_id`, audit `Routed`, notify each recipient, return `true`.
- Loop exhausted → `complete()`: close deliveries, set `Approved`, audit `Completed`.

### 5.4 Approval actions

All mutating actions run in `DB::transaction` with `lockForUpdate()` (`:565-571`), call `ensureSubmitted()` (`:547-552`), and require an active delivery at the current step.

| Action          | Rule                                           | Result                                                                 |
| --------------- | ---------------------------------------------- | ---------------------------------------------------------------------- |
| `receive()`     | Has active delivery                            | Claims pending copy, closes siblings, audits `Received`                |
| `approve()`     | Step type `is_signatory = true`                | Acts, audits `Approved`, `advance(index+1)`                            |
| `acknowledge()` | Step type `is_signatory = false`               | Acts, audits `Acknowledged`, `advance(index+1)`                        |
| `forward()`     | Target must be a co-holder of the current step | Acts as forward, resets target delivery at **same** index, notifies    |
| `returnTo()`    | Requires remarks                               | Snapshots content, opens revision, status `Returned`, notifies creator |

Delivery states are `pending|received|acted|closed`; "open" = pending or received. Multiple holders can hold copies; **first to act claims the step** and siblings are closed.

### 5.5 Return → resubmit → re-route

- `returnTo()` snapshots tracked fields **before** the creator can edit (`TravelOrderApprovalService.php:256-265`).
- `TravelOrderChangeDetector` normalizes the snapshot (sorted pivot IDs, `Y-m-d` dates, integer FKs), diffs it, and classifies **Material** if any changed field intersects `material_fields` (`config/travel-orders.php:23-36`); `submission_notes` is tracked but not material.
- `resumeIndex()` (`:114-137`): minor → the returning step. Material with default policy `restart_from_first_affected` → `min(returning_index, earliest frozen step whose reviewed_fields contain a changed field)`. Can only move **earlier**, never later. Malformed/frozen-map mismatch/no reviewer → 0.
- `TravelOrderResubmissionService::resubmit()` (`:32-106`) completes the revision, increments `revision_number`, sets `Submitted`, audits `Resubmitted`, and re-runs `advance($resumeIndex)` so current designations are honored. **`routed_steps`, review fields, workflow, and document numbers never change.**

---

## 6. Workflow configuration engine (how a route is chosen)

### 6.1 Selection

`WorkflowRouteResolver::workflowFor()` (`:34-49`): office's `workflow_id` if active, else `Workflow::active()->default()->orderBy('id')->first()`. Only one default is guaranteed by repository logic, not a DB constraint.

### 6.2 Target office and recipients

- `targetOfficeFor()` = `$step->office ?? $filingOffice` (`:79-82`) — one "Department Head" step serves every department.
- `designationsFor()` filters `current()` effectivity (inclusive end date), eager-loads user, orders primaries before deputies (`:93-105`).
- `recipientsFor()` maps to users, dedupes, and **withholds the filer only when `auto_skip_creator` is true** (`:116-131`).

### 6.3 Seeded topology

Default: `Department Head (filing office) → Budget (MBO) → Mayor (MO) → HRMO (HRMO)`. Legislative override: `→ Vice Mayor →` instead of Mayor (`WorkflowSeeder.php:35-73`). HRMO is seeded as a **signature** step; receipt steps (`acknowledge`) are supported and tested but not used in seeded config.

### 6.4 Service call graph

```
Controller store/submit
  └─ TravelOrderSubmissionService::submit [tx + lock]
       ├─ WorkflowRouteResolver (workflow/steps/designations/recipients)
       ├─ SignatoryResolver::for(forceLive:true)   ← freezes signature lines
       ├─ TravelOrderAudit::record(Submitted)
       └─ TravelOrderApprovalService::advance(0)
            ├─ resolver again (live designees)
            ├─ audit Routed|FilerWithheld|StepSkipped|Completed
            └─ TravelOrderNotifier::routed() per recipient

ApprovalInboxController / TravelOrderActionController
  └─ TravelOrderApprovalService::{receive,approve,acknowledge,forward,returnTo}
       ├─ lock + ensureSubmitted + currentStep (frozen IDs)
       ├─ claim / markActed / closeOpenDeliveries
       ├─ audit event
       ├─ (approve|acknowledge) → advance(index+1)
       └─ (returnTo) → ChangeDetector::snapshot + Revision::updateOrCreate + Notifier

Controller resubmit [outer tx]
  └─ TravelOrderResubmissionService::resubmit [tx + lock]
       ├─ ChangeDetector::{changedFields,changeType,resumeIndex}
       ├─ revision completion + audit Resubmitted
       └─ advance(resumeIndex)

Screens: WorkflowController::preview → resolver::preview
         show pages → TravelOrderRoutePresenter::{route,timeline,revisions}
         print → SignatoryResolver::for (frozen for filed, live for drafts)
```

There is **no superadmin bypass** in routing/approval — every action needs a live delivery at the current step (`Portal.php:62-74`; `RolesAndPermissionsSeeder.php:113-123`).

---

## 7. Itineraries, per diem, trip tickets, print

### 7.1 Itineraries

Nested under the order with scoped bindings (`routes/travel-orders.php:23-33`); owner + draft/returned only (`TravelOrderItineraryController.php:178-190`). Validation requires `date_to >= date_from`, `H:i` times, valid locations, activity, bounded amounts (`SaveTravelOrderItineraryRequest.php:11-37`).

At save time, server-side computation occurs **only when the auto flags are true**:

- Per diem: return leg detected as `to_location_id === origin`; the rate location for a return leg is where it _leaves_ (`:103-117`).
- Fare: government transport → `0.00`; otherwise configured mode/location fare or the mode's fixed cost (`:158-176`).
- Any mode used is added to the order's mode pivot via `syncWithoutDetaching()` (`:148-153`).
- `lineTotal()` = fare + per diem + registration fee (`TravelOrderItinerary.php:107-115`). There is no global recalculation job when setup data changes.

### 7.2 Per diem computation

`PerDiemComputationService::entryAmount()` (`:28-70`):

```
amount = max(0, daily_rate * inclusive_days - meal_deduction)
result = number_format(amount, 2, '.', '')
```

- Config selection: destination-specific beats fallback; higher daily rate wins (`:76-96,154-192`).
- Daily rate = sum of positive meal-rule amounts, else flat `daily_rate` (`:98-112`).
- Inclusive days = `diffInDays + 1`, clamped to 1 if malformed (`:194-204`).
- Deductions apply **only at trip edges**: departure cutoff when leaving the origin, arrival cutoff when returning; intra-trip legs keep all meals (`:114-152`).
- Worked example: daily rate 400 (80+120+150+50), 3 days, depart 08:30 (breakfast 80 @07:00 deducted) → `400*3 - 80 = 1,120.00`. Return leg arriving 16:00 (dinner 150 @18:00 deducted) → `400*1 - 150 = 250.00`. Intra-trip leg → `400.00`.

### 7.3 Trip tickets

Allowed for **any travel order that will use a government vehicle** (`TripTicketService::ensureOrderAllowsTicket()`); there is no `Approved`-only gate and no approval workflow for the ticket itself. Statuses `issued|completed|cancelled`; only `issued` is editable.

- Create: trims plate, rejects duplicate non-cancelled plate on the same order, sets `ticket_no = TT-{YEAR}-{5-digit id}` (`:28-67`). The create dialog is available on the travel order page when a government vehicle is part of the trip.
- Fuel source defaults to the travel order's office. Selecting a different `fuel_source_office_id` marks the ticket as **borrowed fuel**; completion deducts actual liters/cost from that source office's balance (§7.4).
- Fuel estimate: `liters = round(distance * (round_trip ? 2 : 1) / fuel_efficiency, 2)`, else `0.00` (`:157-183`).
- Complete: requires `issued`, validates odometer ordering, can derive distance from readings, and writes actual `fuel_consumed`, `fuel_cost`, receipt number and optional receipt file (`:108-155`). Cancel: requires `issued` + reason (`:158-170`). Delete: only after cancellation.
- Receipt evidence is stored on the `public` disk under `trip-ticket-receipts` (JPG/PNG/PDF, max 5 MB); `fuel_receipt_url` is appended to the model. Keep `php artisan storage:link` in place.
- **Mutations are owner-only** (`authorizeOwner()`). `show`/`print` live outside the employee portal and are readable by the owner, `travel-orders.view-all` holders, delivery recipients, and users who acted on the order (`authorizeViewer()`); routes require `travel-orders.view` / `travel-orders.print`.

### 7.4 Fuel management

Fuel is tracked per **office + fuel type** in an append-only ledger. Balances are derived, never stored:

```
Remaining = SUM(active allocations for office+fuel type)
          - SUM(actual fuel from COMPLETED trip tickets whose source office matches)
```

- **Allocations** (`Admin\FuelAllocationController`): create/edit/void active records; edits and voids are logged to `fuel_allocation_events` (before/after snapshots, actor, reason). Voiding an active transfer record also voids its paired record. No hard delete and no approval workflow.
- **Transfers** (`type=transfer`): office-to-office fuel borrowing creates a paired negative (source) and positive (target) entry in one DB transaction. Transfers are immutable; void and recreate. Insufficient source balance is **warning-only** — the transfer is still recorded and the warning is flashed.
- **Fuel types** (admin `fuel-types` CRUD): full catalogue with active flag, permissions `fuel-types.view/create/update/delete`, and a Philippine fuel-name seeder. Trip tickets and allocations store `fuel_type_id` plus a denormalized `fuel_type` name.
- **Monitoring** (`Approvals\FuelMonitoringController`, shared by Admin and Approvals): filter by office, fuel type, status, borrowed-only, dates and free text; summary includes estimated liters (non-cancelled tickets), actual liters/cost (completed only), allocations and remaining balances. Usage is grouped by office and fuel type, and borrowed fuel is broken down by borrower → source office. CSV export includes receipt number/URL. Both portals use the same page component and route prefix from `$prefix`.
- **Admin dashboard alerts**: configured thresholds (`config/travel-orders.php` → `fuel.low_balance_liters`, `fuel.low_balance_amount`) drive low/negative balance alerts; negative balances are labeled “Over allocation”.

### 7.5 Printing

- `printOrder()` / `printItinerary()` load relations and pass letterhead (`config/travel-orders.php:82-89`) + signatories: **frozen JSON for filed orders, live for drafts** (`TravelOrderController.php:367-402`; `SignatoryResolver.php:27-39`).
- Viewers: owner or anyone ever delivered to (`:666-678`), plus `travel-orders.print` permission on the route.
- Shared print primitives in `components/travel-orders/print/document.tsx` (screen toolbar hidden with `print:hidden`, letterhead images `/LGU.png` and `/Bagong Pilipinas logo.png`); `print/totals.ts` coerces decimal strings to finite numbers.
- `travel-orders/print-order`, `print-itinerary`, and `trip-tickets/print` are **layout-free** (`app.tsx`): no app sidebar/header is rendered, so the printed sheets are clean at any paper size.

### 7.6 Notifications & audit

`TravelOrderNotification` always writes a DB row; mail only for a `User` with an email (`TravelOrderNotification.php:12-40`). Only two events notify: `returned()` to the creator and `routed()` to each recipient (`TravelOrderNotifier.php:20-69`). No notification for approval/acknowledge/completion/trip-ticket events.

`TravelOrderAudit::record()` appends every workflow event (`:19-43`). `TravelOrderRoutePresenter` turns state into UI arrays: `route()`, `timeline()`, `revisions()` (`:29-166`). Admin surfaces: searchable register (`TravelOrderRegisterController.php:11-41`), reports + CSV (`ReportsController.php:17-131`), audit log (`AuditLogController.php:11-49`). Fuel allocation changes additionally append to `fuel_allocation_events` (§7.4); fuel receipts and visible ticket history are exposed through fuel monitoring.

---

## 8. Authentication

- Guard `web`, provider Eloquent `App\Models\User` (`config/auth.php:18-21,40-44,64-68`). Fortify authenticates on **username**, lowercased (`config/fortify.php:48,63`).
- **Only the `registration` Fortify feature is enabled** (`config/fortify.php:145-147`). Login/logout are core Fortify routes; 2FA, password reset, and email verification are not enabled. The `verified` middleware is effectively inert because `User` does not implement `MustVerifyEmail` (`User.php:37`); 2FA code/tests are dormant.
- `CreateNewUser` creates a **plain user** — no Employee link, no role (`CreateNewUser.php:21-33`). Roles/employee links are added later by the admin Accounts module or seeders.
- `LoginResponse` honors `url.intended` only when its path maps to a portal the user may enter, else the primary portal dashboard (`LoginResponse.php:33-43`; `PortalResolver.php:106-118`). `RegisterResponse` always goes to the default portal dashboard.
- Password policy is centralized in `AppServiceProvider.php:40-48` (strict in production, `null` otherwise), consumed by registration, password updates, and account creation.
- Profile/security settings live under `routes/settings.php`; profile update/delete and password update are implemented; 2FA page is not active.

---

## 9. Roles, permissions, portals

### 9.1 Roles & permissions (Spatie)

Seven stable slugs (`SystemRole.php:12-20`): `employee`, `department_head`, `budget_officer`, `mayor`, `hrmo`, `admin`, `super_admin`. 84 permissions are seeded (`RolesAndPermissionsSeeder.php:23-111`); `super_admin` gets all, `admin` gets all except `accounts.manage-admins` (`:113-123`). Behavior keys off **slug** because administrators can rename display names (`User.php:84-102`).

Additional safety rules: Super Admin permissions can't be edited; built-in roles can't be renamed/deleted; users can't change their own roles; granting/revoking admin roles requires `accounts.manage-admins`; the last admin can't be removed (`RoleController.php:95-155`; `UserAccountController.php:131-274`).

### 9.2 Portals

A portal is a **route-prefix UX workspace**, not a separate app or guard (`Portal.php:5-11`):

| Portal          | Prefix            | Landing                     | Grants                            |
| --------------- | ----------------- | --------------------------- | --------------------------------- |
| Employee        | `employee`        | `employee.dashboard`        | everyone signed in (baseline)     |
| Department Head | `department-head` | `department-head.dashboard` | `department_head`                 |
| Approvals       | `approvals`       | `approvals.inbox`           | `budget_officer`, `mayor`, `hrmo` |
| Admin           | `admin`           | `admin.dashboard`           | `admin`, `super_admin`            |

- The active portal is **derived from the URL**, never persisted (`PortalResolver.php:75-97`; `HandleInertiaRequests.php:46-54`). The personal `/inbox` route is explicitly resolved to the baseline Employee portal so privileged accounts get the employee shell instead of their default workspace (`PortalResolver.php:87-95`).
- `EnsurePortalAccess` fails closed: 401 for guests, accepts if any `portal:` argument is enterable, else 403 (`:29-44`).
- `PortalSwitcher` is now mounted in the **header** (`app-sidebar-header.tsx`) rather than inside the user dropdown, so a multi-role account can see at a glance how many approvals are waiting in another workspace without leaving the current one. It only renders when ≥2 portals are available (`portal-switcher.tsx`). Each processing portal carries a `pendingCount` badge (`PortalOption.pendingCount`), computed once in `HandleInertiaRequests` from the delivery-based live queue (`TravelOrderRepository::pendingInboxCount`, which reuses `inboxQuery`) and attached to every portal that owns a processing inbox; employee-only and admin-only accounts skip the extra query (`HandleInertiaRequests::hasProcessingPortal`). The sidebar builds a different nav group set per portal (`app-sidebar.tsx:350-388`).
- Portal dashboards are role-focused operational views (see §12.2): the employee dashboard shows personal order counts plus recent orders; the department-head dashboard shows the approval queue plus pending items; the admin dashboard shows pipeline counts, fuel position, recent organization orders and fuel alerts. The shared `/approvals` inbox remains the Approvals portal landing page.
- The employee portal no longer carries a processing inbox or workspace tabs: `WorkspaceTabs` was removed and the header now exposes only a `My Workspace` shortcut inside processing portals, while the processing queue (`Approval Inbox`) stays in the Approvals/Department Head sidebar. Multi-role visibility is handled by the header `PortalSwitcher` badge instead.

---

## 10. Authorization model (three layers, no policies)

1. **Route middleware** — `auth`, `verified`, `portal:<key>`, `permission:<name>` at group and per-route level (`routes/portals.php:22-113`; `routes/travel-orders.php:8-21`; all admin route files).
2. **Controller ownership checks** — `created_by === user id` for filer actions (`TravelOrderController.php:680-689`); print viewers may also be delivery recipients (`:666-678`); approval show allows owner or any prior recipient (`ApprovalInboxController.php:175-185`).
3. **Service state/holder checks** — status + open delivery + correct step type (`TravelOrderApprovalService.php:463-552`).

React `Can` / `useCan` / `NavMain` checks are **UI-only** (`can.tsx:4-20`; `use-can.ts:5-16`; `nav-main.tsx:23-38`) and never replace server gates. There is no `app/Policies` directory.

Capability summary (still subject to state/ownership/holder checks):

| Area                                       | Employee/filer                                    | Approver roles                                | Admin                               |
| ------------------------------------------ | ------------------------------------------------- | --------------------------------------------- | ----------------------------------- |
| Create/edit/delete own draft               | yes                                               | no                                            | no special route                    |
| Submit/resubmit own order                  | yes                                               | no                                            | no                                  |
| Manage itinerary                           | own draft/returned                                | no                                            | no                                  |
| Prepare/complete/cancel/print trip ticket  | own order, government vehicle required            | no                                            | no                                  |
| View/print trip ticket                     | owner, `travel-orders.view-all`, delivered, actor | delivered recipient + view/print permission   | view/print permission               |
| Receive/approve/acknowledge/return/forward | no                                                | only with active delivery + correct step type | only if designated                  |
| Budget earmark / fund source               | no                                                | Budget Review holder action, no step advance  | no                                  |
| HRMO series/control/recording/distribution | no                                                | HRMO Review holder action, no step advance    | no                                  |
| Manage fuel allocations/transfers/types    | no                                                | no                                            | yes with fuel permissions           |
| View fuel monitoring                       | no                                                | yes (shared Approvals/Admin page)             | yes                                 |
| Print TO / Appendix A                      | owner or delivered recipient + print permission   | delivered recipient + print permission        | print permission alone insufficient |
| View all orders / reports / audit          | no                                                | no                                            | yes with admin permissions          |

---

## 11. Master-data CRUD & repository pattern

16 contract→Eloquent bindings in `RepositoryServiceProvider.php:39-62` (Office, Employee, Position, EmploymentType, TravelOrder, workflows, travel setup, signatory groups, etc.). Controllers depend on the contract, not Eloquent.

The **Office slice** is the canonical pattern:

- Route group `auth, verified, portal:admin, permission:administration.view`, prefix `admin/offices`, with per-verb permissions (`routes/offices.php:6-22`).
- `OfficeController`: `index()` paginates via repository and renders `offices/index`; writes use `validated()` data, flash a toast, and `back()`; `destroy()` blocks deletion when employees/steps/designations reference the office (`:26-94`).
- `StoreOfficeRequest` / `UpdateOfficeRequest` validate only `office_name`/`office_code` — note that `workflow_id` submitted from the UI is ignored (tests assert this).
- Repository handles search, eager loading, pagination, and thin writes (`EloquentOfficeRepository.php:11-41`).
- Frontend: `pages/offices/{index,create,edit,delete}.tsx`, `types/office.ts`, dialogs closed on success, buttons gated by `<Can>`.

Most resources follow this shape: migration → model → contract + Eloquent repo → binding → FormRequests → controller → route file (+ `require` in `web.php`) → permissions in the seeder → TS types → pages → nav item → Pest tests.

---

## 12. Frontend architecture

### 12.1 Wayfinder codegen

`vite.config.ts:2,27-29` enables `wayfinder({ formVariants: true })`. Generated (and **gitignored**, `:10-12`) trees:

- `resources/js/actions/**` — controller-keyed TS modules mirroring PHP namespaces.
- `resources/js/routes/**` — URL-keyed modules; pages import the **default export** of a URL-keyed module.
- `resources/js/wayfinder/index.ts` — runtime types and query-param helpers (booleans → 1/0, arrays → `key[]`, nested bracket notation, URL defaults).

`SomeRoute().url` is the client-side equivalent of Laravel's `route()` (`pages/travel-orders/index.tsx:19,49`). `formVariants: true` emits `.form()` helpers with hidden `_method` for PUT/DELETE. The approval flow instead receives server-computed `urls` props (`approvals/show.tsx:39-70`). A fresh checkout must run `dev`/`build` (or `composer setup`) before these imports resolve.

### 12.2 UI system

shadcn/ui `new-york` + Radix + Tailwind v4 tokens in `resources/css/app.css:8-143` (semantic `oklch` variables, `.dark` overrides). Theme state is an external store backed by localStorage **and** a cookie read server-side by `HandleAppearance.php:19`, preventing a flash. Forms use Inertia `useForm`, `InputError`, and Sonner toasts driven by `Inertia::flash('toast', …)` → `useFlashToast()` → global `<Toaster />` (`use-flash-toast.ts`; `types/ui.ts`). Flash payloads are shared by `HandleInertiaRequests` as `flash.toast`, `flash.success`, `flash.warning` and `flash.error`; `useFlashToast()` listens to Inertia router events rather than calling `usePage()` outside a page component.

Portal dashboards share a small presentational kit in `components/dashboard/`: `DashboardHeader` (eyebrow, greeting, description, date, actions), `DashboardPanel` (icon header, action slot, flush content, footer), `SectionHeading`, `QuickLinks`, `EmptyState` and `DashboardLink`. `StatCard` (`components/stat-card.tsx`) is the KPI tile used by dashboards and the approvals inbox; it supports neutral/info/warning/success/danger tones, optional icons and hrefs, and quiets itself when the value is zero. The visual direction is civic/institutional: emerald brand accent (matching `welcome.tsx`), neutral `bg-muted/30` page background, a max-width shell, and card surfaces using existing tokens. No dashboard adds fabricated trend data; all figures come from controller props.

### 12.3 Types

No generated data-type bridge. `resources/js/types/**` is hand-maintained, mirroring PHP enums (as string unions), entity shapes (snake_case, decimal money as `string | null`), the generic `PaginatedDataResponse<T>`, and shared props via `global.d.ts`. Guards are `npm run types:check` (`tsc --noEmit`) and `composer types:check` (PHPStan).

---

## 13. Testing, tooling, CI

- **Tests:** Pest 5 + Laravel plugin; suites Unit/Feature (`phpunit.xml:7-14`); in-memory SQLite (`:26-27`); `RefreshDatabase` bound to all Feature tests (`tests/Pest.php:20-22`); `withoutVite()` in `TestCase` (`:14-16`). Global helpers in `tests/Feature/TravelOrders/routing_helpers.php` (`routingEmployee()`, `routingUser()`, `routingChain()`) and `grantAdminPortal()` in `Pest.php:59-73`. Inertia assertions via `AssertableInertia`.
- **Frontend tests:** `vp test` (`npm test`) with jsdom + Testing Library; configured in `vite.config.ts:42-46`, setup in `resources/js/test/setup.ts`. Specs cover the portal switcher, admin reassign dialog, and approval actions. Keep specs under `resources/js/test/`, not `pages/`: Inertia's resolver globs every `.tsx` under `pages/` and would ship tests as page chunks. Assurance is `vp check` + `tsc --noEmit` + `vp test` + PHP-side Inertia assertions.
- **Quality:** Pint `laravel` preset (`pint.json`); Larastan level 7 over `app/`, `bootstrap/app.php`, `config/`, `database/`, `routes/` (`phpstan.neon:6-13`); no Rector.
- **Scripts:** `composer setup` (install, `.env`, key, migrate, npm install + build), `composer dev` (`artisan dev`), `composer ci:check` (`npm run check` + `types:check` + `@test`), `composer test` (config clear → Pint check → PHPStan → `artisan test`).
- **CI:** `.github/workflows/tests.yml` — push to `main` / PRs, PHP 8.5 + Composer 2, Node 22, `composer setup`, `composer ci:check`. Dependabot updates Actions weekly.
- **Agent tooling:** Laravel Boost with `boost.json` targeting `opencode`; locally gitignored `.agents/skills/*`.

---

## 14. Known gaps, latent issues, and gotchas

These are observations from the source analysis, not changes:

1. **Unwired travel-order states/events.** The travel-order statuses `Completed`, `Cancelled`, `Rejected` and the `PulledBack`, `Cancelled` workflow events are declared/presented but still have no producer; the admin dashboard shows those buckets (usually `0`) until one exists. `travel-orders.earmark` is now used by the Budget Review holder action, but `travel-orders.cancel` remains seeded and unused. Presentation maps `Rejected`/`Cancelled` as "cancelled". Trip-ticket `completed`/`cancelled` **are** actively written.
2. **No superadmin routing bypass.** All approval actions still require a live delivery; `/approvals` explicitly excludes admin roles.
3. ~~**`SignatoryResolver::routedTypes()` key mismatch** — `array_flip(routed_steps)` builds step-ID→index, but step _types_ are sorted by `$positions[$type->id]` (step-type ID), so route ordering of signature lines isn't actually guaranteed.~~ **Fixed 2026-11-21:** steps are now sorted by their own position in `routed_steps` before their types are plucked; a regression test in `TravelOrderPrintTest.php` pins the order.
4. **`forward()` closes open deliveries globally**, not scoped to the current step — safe under the current assumption that only the current step has open copies (`TravelOrderApprovalService.php:178,554-560`).
5. **Readiness check is global** — submission requires designations on _every_ step but only _some_ deliverable step after excluding the filer; displayed routes may have fewer actual stops.
6. **`returnTo()` stores `change_type='minor'` initially**; the real classification is computed only on resubmit.
7. **Resume-policy fallback mismatch** — config default is `restart_from_first_affected` (`config/travel-orders.php:16-17`) while the detector's string fallback is `resume` (`:127-136`); config wins in normal use.
8. **`verified` middleware is inert** (User is not `MustVerifyEmail`); 2FA is dormant.
9. **SSR is half-configured** (no bundle path, no committed `bootstrap/ssr/`).
10. **Generated Wayfinder code is untracked** — must generate before type-checking/building.
11. ~~**No JS tests**~~ — **Fixed 2026-11-24:** `npm test` runs jsdom/Testing Library specs for the portal switcher, reassign dialog, and approval actions. PHP↔TS domain types are still manually synchronized.
12. **Per-diem config cache is instance-local** (fresh service per request, so fine today).
13. **Nested transaction** in `TravelOrderController::resubmit()` wrapping the service transaction (savepoints; correct but note for rollback debugging).
14. **Workflow deletion guards use `travel_orders.workflow_id`**, set only at submission; drafts are unaffected.
15. **Admin has no travel-order detail route** — the admin register is index-only, so the admin dashboard's recent-orders rows are informational and deliberately not linked; employees/department heads reach detail pages through their own portals.

---

## 15. "Where do I go to…" quick reference

| Task                          | Start here                                                                                                                                                                                                                |
| ----------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Add a master-data resource    | Copy the Office slice (§11): migration, model, repo contract+impl, provider binding, FormRequests, controller, route file + `require`, seeder permissions, TS types, pages, nav, tests                                    |
| Change approval routing rules | `WorkflowRouteResolver`, `TravelOrderSubmissionService`, `TravelOrderApprovalService`, `config/travel-orders.php`                                                                                                         |
| Add an approval action        | `routes/portals.php`, permission in `RolesAndPermissionsSeeder`, `TravelOrderActionController`, a service method, ability flags in `ApprovalInboxController::show()`, button in `pages/approvals/show.tsx`                |
| Change per-diem math          | `PerDiemComputationService`, `TravelOrderItineraryController::persist()`                                                                                                                                                  |
| Change print output           | `components/travel-orders/print/*`, `pages/travel-orders/print-*.tsx`, `SignatoryResolver`, `config/travel-orders.php:82-89`                                                                                              |
| Change trip-ticket rules      | `TripTicketService`, `TravelOrderTripTicketController`, `SaveTripTicketRequest`/`CompleteTripTicketRequest`, `pages/trip-tickets/*`, `routes/travel-orders.php`                                                           |
| Change fuel behavior          | `Admin\FuelAllocationController`, `Approvals\FuelMonitoringController`, `FuelAllocation*` models, `fuel_allocations`/`fuel_allocation_events` migrations, `pages/fuel-allocations/*`, `pages/approvals/fuel-monitoring/*` |
| Change a portal dashboard     | `PortalDashboardController` / `Admin\DashboardController`, `types/dashboard.ts`, `pages/portals/{employee,department-head,admin}/dashboard.tsx`, `components/dashboard/*`, `components/stat-card.tsx`                     |
| Change what a portal can see  | `Portal`, `PortalResolver`, `EnsurePortalAccess`, `components/app-sidebar.tsx`                                                                                                                                            |
| Change shared client props    | `HandleInertiaRequests::share()`, `types/global.d.ts`                                                                                                                                                                     |
| Debug a stuck approval        | Admin runbook §16; `php artisan travel-orders:check-integrity`, delivery rows + `TravelOrderAudit` actions + `TravelOrderRoutePresenter::route()/timeline()`                                                              |
| Run all checks                | `vendor/bin/pint --dirty`, `php artisan test`, `composer types:check`, `npm run check`, `npm run types:check`, `npm test`, `npm run build`                                                                                |

---

## 16. Admin runbook

These procedures are for the shared admin portal. They observe or repair delivery state; they do not change routing rules. Workflow configuration changes affect future submissions only unless an explicit rescue action is taken.

### 16.1 Daily monitoring

The admin dashboard (`admin.dashboard`) exposes `stalledOrders`, `stalledTotal`, `stalledThresholdDays`, `fuelAlerts`, and `fuelSummary`.

- The stalled threshold comes from `TRAVEL_ORDER_STALLED_AFTER_DAYS` (default `3`, clamped to at least `1`) in `config/travel-orders.php:96-108`. After changing it, run `php artisan config:clear` locally or rebuild the production config cache.
- A stalled row is a monitoring hint only: no automatic approval, notification, or reassignment. Age is derived from `submitted_at`; holder is `recipient_user_id`; step is resolved from the frozen route.
- Fuel alerts compare active allocations with **completed** trip tickets only; cancelled tickets are excluded. Negative balances are allowed and surfaced as alerts. Thresholds are `FUEL_LOW_BALANCE_LITERS` and `FUEL_LOW_BALANCE_AMOUNT`.

### 16.2 Investigate a stalled approval

1. Open the stalled panel on the admin dashboard and note the order number, holder, step, and age. The admin register is index-only, so there is no admin detail page.
2. Run the read-only integrity check:

    ```bash
    php artisan travel-orders:check-integrity
    php artisan travel-orders:check-integrity --json --fail-on-warning
    ```

    It exits `0` when there are no errors, `1` when any error exists (or any warning with `--fail-on-warning`). `--json` returns `ok`, `errors`, `warnings`, and `findings`. It never repairs data.

    Error codes cover a submitted order without a workflow/route/timestamp, a mismatched frozen review map, an invalid current step, no open delivery, a missing or mismatched recipient, a stale open delivery, a delivery outside the frozen route, a missing delivery user, an acted delivery without a timestamp, and an orphan delivery. Warnings cover stale route pointers on closed orders, received deliveries without a timestamp, and open deliveries left at another step.

3. Inspect `travel_order_deliveries` for open rows at `current_step_index` and compare them with `recipient_user_id`. Read the audit trail (`TravelOrderAudit`, including `Routed`, `Received`, `Forwarded`, and `Reassigned`) before acting.
4. Do not edit `routed_steps`, `current_step_index`, or delivery rows directly. Use the designation impact + reassign flow below.

### 16.3 Change a designation safely

1. In the admin designations list, open **Impact** for the designation before replacing or ending it (`GET admin/designations/{designation}/impact`).
2. The dialog lists only submitted orders whose **current-step open delivery** is held by the outgoing user and whose frozen step type matches the designation. Changing a designation never moves an existing pending delivery by itself.
3. Candidate replacements prefer the holders named by the current step; if none are configured, the service falls back to approver-role accounts. The outgoing holder is excluded. Fix configuration or designations first if the candidate list is empty.
4. After saving the designation, reassign the affected orders from the impact list. Bulk reassignment accepts at most 100 order IDs; each order is handled in its own transaction, so a stale document is reported without undoing successful rescues.

### 16.4 Reassign a stranded order

Requires `travel-orders.reassign` (`admin.travel-orders.reassign` / `admin.travel-orders.reassign-bulk`).

- Reassignment is rescue-only. It closes open deliveries at the current step, creates one fresh `Pending` delivery for the new holder, updates `recipient_user_id`, records `WorkflowEvent::Reassigned` with `from_user_id`/`to_user_id`, and notifies the new holder.
- It does **not** approve, return, or advance the workflow. A normal holder still uses **Approve** (advances) or **Forward** (passes to another person at the same step) in shared `/approvals`.
- Always include remarks explaining why the original holder can no longer act. After a bulk pass, review the partial-failure report and rerun the integrity check; successful orders are not rolled back.

### 16.5 Seeded accounts and password forcing

Seed credentials are centralized in `config/seed-credentials.php` and applied by `ResolvesSeedCredentials`:

- `DEMO_SEED_PASSWORD` and `KEY_OFFICIAL_SEED_PASSWORD` are required before seeding production. Local and test installs fall back to the historical `password` value. Re-seeding never resets an existing account's password.
- `SEED_FORCE_PASSWORD_CHANGE` may be `true` or `false`. When unset, it defaults off in `local`/`testing` and on elsewhere; it only marks **newly seeded** accounts with `must_change_password`.
- Flagged accounts are redirected to `security.edit`. Guests, `security.edit`, `user-password.update`, `logout`, and `verification.*` remain reachable so the user can sign in and change the password. A successful password update clears the flag.
- The migration adds the column with a `false` default, so existing accounts are unaffected. If a production seed refuses to run, set the missing environment variables and rerun the seeder rather than editing the database directly.

### 16.6 Verification gate

Before committing or deploying a workflow/dashboard change:

```bash
vendor/bin/pint --dirty
php artisan test
composer types:check
npm run check
npm run types:check
npm test
npm run build
```

`npm test` runs the jsdom/Testing Library suite. Browser QA covers the admin stalled panel, the designation impact dialog, a reassign toast and audit row, both document-preview tabs, and the security banner for a flagged account; these paths were verified on 2026-11-24.

Detailed, citation-heavy source reports live in `%TEMP%\opencode\newto-analysis\` (`01-workflow-approval.md`, `02-travel-order-domain.md`, `03-auth-portals-crud.md`, `04-frontend-tooling.md`) if you need line-level depth beyond this guide.
