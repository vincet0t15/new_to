# Decision Record

This is the contract for behavior that was deliberately chosen during hardening and UI audits. It is descriptive: each entry records what the code does now and why. The six early hardening decisions are recorded here **by topic**; there are no literal `D1`–`D6` labels in the repository, so do not look for them in source.

Changing any entry marked **Approval required** means changing workflow, authorization, or financial evidence semantics. Obtain explicit approval before doing so.

---

## 1. One shared approval portal

**Decision:** Budget Officer, Mayor, Vice Mayor, and HRMO reviewers use the same `/approvals` inbox. Department heads use `/department-head`; employees use the employee portal. There are no separate Budget/Mayor/HRMO portals.

**Why:** The current step holder, not the role label, decides what action is available. Splitting portals would duplicate the inbox and invite divergent workflow logic.

**Evidence:** `routes/portals.php:20,27,35,78`; `app/Enums/Portal.php:78`; both department-head and approval routes use `ApprovalInboxController` and `TravelOrderActionController` (`routes/portals.php:42,82,108`). Portal is UX navigation, not a separate guard (`app/Enums/Portal.php:5-78`).

**Change control:** **Approval required.** Do not add a role-specific approval portal or bypass the shared screen.

---

## 2. Central workflow engine with a frozen route

**Decision:** One engine selects, freezes, and advances the route. Submission captures `workflow_id`, `routed_steps`, review-field map, and signatories; later steps resolve the _person_ live but never rewrite the frozen step list.

**Why:** A submitted order must remain auditable even if designations or workflows change afterward.

**Evidence:** Workflow selection uses the office's active workflow or the active default (`app/Services/WorkflowRouteResolver.php:34`). Submission freezes route shape (`app/Services/TravelOrderSubmissionService.php:69,80,92`). `advance()` walks frozen step IDs, creates one pending delivery per current recipient, and ends as `Approved` when the route is exhausted (`app/Services/TravelOrderApprovalService.php:656,689,702,726,910`). Resubmission preserves route and numbers (`app/Services/TravelOrderResubmissionService.php:15,77`).

**Change control:** **Approval required.** Do not mutate `routed_steps`, `current_step_index`, or delivery rows directly.

---

## 3. Delivery-based authorization and separation of duties

**Decision:** Permissions gate the verb; a live open delivery at the current step gates the actual action. Admin/SuperAdmin have no routing bypass and are excluded from `/approvals`.

**Why:** A role alone must not be enough to approve an order. The person holding the current step is the only actor who may act on it.

**Evidence:** Inbox query = submitted + current step + open delivery for that user (`app/Repositories/Eloquent/EloquentTravelOrderRepository.php:151,158`); `activeDeliveryFor()` checks step, user, and open status (`app/Models/TravelOrder.php:349,369`); open means `pending` or `received` (`app/Enums/TravelOrderDeliveryStatus.php:5,12`). No `app/Policies` layer exists; route middleware, controller ownership, and service state checks are the guards (`CODEBASE_GUIDE.md:324,330`). Tests deny approval without a delivery (`tests/Feature/TravelOrders/TravelOrderRoutingTest.php:360,374`).

**Change control:** **Approval required.**

---

## 4. Frozen filing context and self-filing

**Decision:** A user files only for themselves. `employee_id`, `office_id`, and `created_by` are set server-side from the signed-in account and are immutable after Draft. Update/resubmit payloads exclude those fields.

**Why:** Allowing a filer to change traveler or office after submission would break routing, signatories, and the audit trail.

**Evidence:** Store derives employee/office from the authenticated user (`app/Http/Controllers/TravelOrders/TravelOrderController.php:100,147,149,155`); `contentPayload()` excludes identity fields (`:239,295,548`); ownership is `created_by === user.id` (`app/Models/TravelOrder.php:390`). Resubmission preserves the captured chain (`app/Services/TravelOrderResubmissionService.php:15,77`).

**Change control:** **Approval required.**

---

## 5. `Approve`, `Forward`, and `Receive` are distinct

**Decision:**

- `Approve` advances the workflow to the next step.
- `Forward` passes the same step to another eligible co-holder.
- `Receive` is a read receipt only; it claims nothing and advances nothing.

There is no "approve and choose the next recipient" action.

**Evidence:** `app/Services/TravelOrderApprovalService.php:234,318,343` (approve/forward), `:46-65` (receive). `claim()` on the first real action closes sibling open copies (`:772,805`). Tests show a deputy's `receive()` leaves the primary pending until a real action occurs (`tests/Feature/TravelOrders/TravelOrderRoutingTest.php:142,163,177`).

**Change control:** **Approval required.**

---

## 6. Fail closed on missing or undeliverable steps

**Decision:** If a step has no active designation, no linked login, or no recipient after self-exclusion, submission refuses to proceed. A missing frozen step during approval is an error for admin review, not a silent skip.

**Why:** Silent skips would produce an approval trail that does not match the configured policy.

**Evidence:** Preflight refuses undeliverable chains (`app/Services/TravelOrderSubmissionService.php:111,120,146`); `assertDeliverableOrSkippable()` fails closed (`app/Services/TravelOrderApprovalService.php:835`); missing frozen step throws (`app/Services/TravelOrderApprovalService.php:670`, `app/Exceptions/TravelOrderRoutingException.php:46`). Portal middleware gives 401/403 rather than hiding authorization failures (`app/Http/Middleware/EnsurePortalAccess.php:12,29,43`).

**Change control:** **Approval required.**

---

## 7. Print-number fallback

**Decision:** Displayed/printed numbers use `control_no ?? to_number ?? "#{id}"` consistently across print documents, approval view, trip-ticket view, and notifications.

**Why:** An order without HRMO-assigned numbers must still be identifiable everywhere.

**Evidence:** `resources/js/components/travel-orders/print/travel-order-document.tsx:67`; `resources/js/pages/approvals/show.tsx:196`; `resources/js/pages/trip-tickets/show.tsx:124`; `app/Services/TravelOrderNotifier.php:71`.

**Change control:** Low risk, but keep all call sites synchronized.

---

## 8. Stalled approvals are monitoring-only

**Decision:** The stalled threshold is `TRAVEL_ORDER_STALLED_AFTER_DAYS` (default `3`, clamped to `>= 1`), exposed as `travel-orders.approval.stalled_after_days`. A stalled row is a hint for admins; it never auto-approves, notifies, or reassigns.

**Evidence:** `config/travel-orders.php:96-108`; admin dashboard query (`app/Http/Controllers/Admin/DashboardController.php:155,175,193`); repository condition (`app/Repositories/Eloquent/EloquentTravelOrderRepository.php:181`); inbox exposes the same threshold (`app/Http/Controllers/Approvals/ApprovalInboxController.php:90`). Tests verify config override (`tests/Feature/Administration/AdminOperationsTest.php:32,51`).

**Change control:** Configuration change only; no workflow semantics.

---

## 9. Seed credentials and password hardening

**Decision:** Passwords are env-driven (`SUPERADMIN_PASSWORD`, `DEMO_SEED_PASSWORD`, `KEY_OFFICIAL_SEED_PASSWORD`). Production seeding refuses to run without them; local/test fall back to the historical development password. `SEED_FORCE_PASSWORD_CHANGE` defaults off locally/in tests and on elsewhere. Re-seeding never resets an existing password; changing a password clears the flag.

**Why:** Existing accounts must not be locked out, while production defaults must not be weak.

**Evidence:** `config/seed-credentials.php:18-27`; `config/superadmin.php:16-22`; `app/Concerns/ResolvesSeedCredentials.php:19-51`; `app/Http/Middleware/EnsurePasswordChange.php:10,23`; `tests/Feature/Auth/ForcedPasswordChangeTest.php:19`.

**Change control:** **Approval required** for production credential policy changes.

---

## 10. Fund source and earmark ownership

**Decision:** Employee create/edit screens never expose **Fund source**. The Budget Officer assigns fund source and `earmark_number` during approval. HRMO records series/control/recording/distribution. Fund-availability certification is explicitly out of scope.

**Why:** Separation of duties: the requester must not bind funds; the budget reviewer must; records are a distinct step.

**Evidence:** Review fields and actions are configured by step type (`config/travel-orders.php:23-54`); earmark/record actions are permission-gated in `routes/portals.php:66-74`; service audits assignments (`app/Services/TravelOrderApprovalService.php:188,204`).

**Change control:** **Approval required.**

---

## 11. Fuel ledger invariants

**Decision:**

- Fuel movements are `allocation|transfer` ledger entries, never a direct `fuel_allocation_id` on trip tickets.
- A transfer writes a negative source row and positive target row in one DB transaction, linked by `related_allocation_id`, with paired debit/credit events.
- Balances are derived from active allocations minus completed-trip actuals; cancelled tickets are excluded.
- Insufficient source balance is a **warning only**; the transfer is still recorded.
- No hard deletes: void with a reason, and voiding one transfer side voids the paired side.
- Transfers cannot be edited; void and recreate.

**Evidence:** `app/Http/Controllers/Admin/FuelAllocationController.php:111,142,160,181,237,265,293,309,325`; `app/Http/Controllers/Approvals/FuelMonitoringController.php:31,102`; `tests/Feature/FuelMonitoringTest.php:178`; migrations `2026_11_23_072036_create_fuel_allocation_events_table.php` and related allocation tables.

**Change control:** **Approval required.**

---

## 12. Receipt storage

**Decision:** Trip-ticket fuel receipts are JPG/JPEG/PNG/PDF up to 5 MB, stored on the `public` disk under `trip-ticket-receipts`. The DB stores `fuel_receipt_path`; the model appends `fuel_receipt_url`. `php artisan storage:link` is required.

**Evidence:** `app/Http/Requests/TripTickets/SaveTripTicketRequest.php:30`; `app/Http/Requests/TripTickets/CompleteTripTicketRequest.php:21`; `app/Services/TripTicketService.php:264-283`; `app/Models/TripTicket.php:92,175`; `config/filesystems.php:41-48`.

**Change control:** Low risk for UI changes, but storage path/disk changes require migration and deployment coordination.

---

## 13. Notifications and document fallback

**Decision:**

- In-app notification URLs are relative; email action URLs are absolute.
- Legacy absolute rows are normalized when displayed or opened; foreign schemes and protocol-relative URLs are rejected.
- Only notification-originated 403/404 document visits get a friendly redirect to `/inbox` with a toast. Direct visits keep normal errors.
- Historical delivery recipients may view documents; only current active holders get action buttons.

**Evidence:** `app/Notifications/TravelOrderNotification.php:13,36,46,58,75`; `app/Support/NotificationUrl.php:5,19,58,80`; `app/Http/Middleware/RedirectInaccessibleDocument.php:12,27,31`; `tests/Feature/Notifications/NotificationLinkTest.php:9,34`.

**Change control:** **Approval required** for URL policy changes.

---

## 14. Admin rescue is not workflow editing

**Decision:** Reassignment is rescue-only: it closes open deliveries at the current step, creates one fresh pending delivery for the new holder, updates `recipient_user_id`, audits `Reassigned`, and notifies. It never approves, returns, or advances. Prefer designation impact + reassign over direct database edits.

**Evidence:** `app/Services/TravelOrderApprovalService.php:371,379`; `CODEBASE_GUIDE.md:470,481-485`.

**Change control:** **Approval required.**

---

## 15. UI audit decisions

**Decision:**

- The canonical label is **Approval Inbox**; personal `/inbox` stays the Employee-shell notification inbox.
- Personal notification inbox has no All/Unread/Read filter; unread items are bold, read items normal; pagination is retained. A "Mark all read" action posts to `inbox.read-all` and is disabled when nothing is unread.
- Destructive or workflow-affecting actions use the shared `ConfirmDialog`; no native `confirm()` calls.
- Raw `JSON.stringify(event.meta)` is never shown; `eventDetailLines()` renders human-readable before/after values.
- Shared `Pagination` supplies a labelled `nav`, `aria-current`, disabled links without `href`, and no `dangerouslySetInnerHTML`.
- Admin dashboard rows link to the filtered **admin register**, not to `/travel-orders/{id}` (employee-owned; 403 for admins).

**Evidence:** `resources/js/pages/portals/department-head/dashboard.tsx` (label), `resources/js/pages/notifications/index.tsx`, `resources/js/components/confirm-dialog.tsx`, `resources/js/pages/admin/fuel-allocations/index.tsx` (`eventDetailLines`), `resources/js/components/pagination.tsx`, `resources/js/pages/portals/admin/dashboard.tsx` (`registerHref`).

**Change control:** Low/medium risk; preserve accessibility and authorization behavior.

---

## 16. Scope exclusions

- **Fund-availability certification is out of scope.** Do not add certification UI, permissions, or workflow steps without a new decision.
- No split portals, no new approval screen variants, and no direct database repair of workflow state.
- The stalled/fuel alert panels are informational only.

---

## 17. Account status and registration throttling

**Decision:**

- `users.is_active` defaults to `true` and is the only account-level switch. New registrations and admin-created accounts start active unless explicitly unchecked.
- A deactivated account is refused during the Fortify login pipeline and signed out on its next web request; roles and employee links are unchanged.
- Only administrators with `accounts.edit` can toggle status. Self-deactivation and deactivating the last active administrator are refused.
- Public `POST /register` is throttled per IP address (default 5 attempts per 60 seconds) and returns HTTP 429 with `Retry-After`.

**Why:** Disabling access must not delete or rewrite people, roles, employee links, or workflow history. Per-IP throttling slows bulk fake registrations without blocking legitimate shared networks longer than a minute.

**Evidence:** `database/migrations/2026_11_25_000001_add_is_active_to_users_table.php`; `app/Actions/Fortify/RejectInactiveAccount.php`; `app/Http/Middleware/EnsureAccountIsActive.php`; `app/Http/Middleware/ThrottleRegistration.php`; `config/fortify.php:117`; `routes/accounts.php:23`; `app/Http/Controllers/Accounts/UserAccountController.php`.

**Change control:** **Approval required** for changing who may deactivate accounts or for removing the login enforcement; threshold tuning alone is low risk.

---

## 18. Public registration mirrors employee creation

**Decision:**

- Public `/register` collects the same fields as employee create: first/middle/last name, suffix, position, office, employment type, contact number, monthly salary, and active status. Photo upload is intentionally excluded; it stays on the Employee screen.
- Submitting the form creates an `Employee` record and a linked `User` in one database transaction. The login's `name` comes from the employee's computed `full_name`; no name columns are added to `users`.
- Self-registration still grants **no role or permission**. Administrator-managed account creation keeps its existing Full Name and optional existing-employee link.

**Why:** The user wants the outside registration to match employee creation so a registrant has employee details immediately and there is no duplicate name data on `users`. Creating both records together keeps the login and its employee details consistent and linked.

**Evidence:** `app/Actions/Fortify/CreateNewUser.php`; `app/Providers/FortifyServiceProvider.php::registerView`; `resources/js/pages/auth/register.tsx`; `tests/Feature/Auth/RegistrationTest.php`.

**Change control:** **Approval required** for adding name columns to `users`, granting roles during public registration, or removing the transaction that links both records.

---

## 19. Vehicle registry and trip-ticket selection

**Decision:**

- A `vehicles` registry holds plate number (unique), type, model, seating capacity, one assigned active employee as driver, and an active flag. Only administrators with `vehicles.*` permissions manage it; there is no separate fleet portal.
- `trip_tickets.vehicle_id` is nullable and points at the registry entry chosen while preparing the ticket. The plate, type and driver are still copied onto the ticket as a **snapshot** so a printed or filed ticket survives a vehicle being edited, deactivated or deleted.
- Selecting a registered vehicle on the trip-ticket form fills in its plate, type and assigned driver. The preparer may override the type and driver for that trip, and may enter a plate manually for legacy vehicles. When a registered vehicle is selected, the registry plate wins so the duplicate-plate rule and the printed ticket agree.
- A vehicle already referenced by a trip ticket cannot be deleted; administrators should set it inactive instead.

**Why:** The system previously stored vehicle details only inside each trip ticket, so the same plate had to be retyped and there was no reliable answer to "which driver usually drives this vehicle". A registry removes that repetition while the snapshot preserves audit and print fidelity.

**Evidence:** `database/migrations/2026_11_25_000002_create_vehicles_table.php`; `database/migrations/2026_11_25_000003_add_vehicle_id_to_trip_tickets_table.php`; `app/Services/TripTicketService.php::applyVehicle`; `app/Http/Controllers/Vehicles/VehicleController.php`; `resources/js/components/travel-orders/trip-ticket-form.tsx`.

**Change control:** **Approval required** for making the registry link mandatory, removing the snapshot fields, or letting non-admins manage vehicles.

---

## 20. Employee progress tracker and internal notes

**Decision:**

- The employee show page has a "Status & route" action that opens a dialog rendering the already-frozen approval route and naming the step that currently holds the order. This is presentation only: the route comes from `TravelOrderRoutePresenter`, and no routing, delivery, or authorization rule changes.
- "Completed Travel Orders" in the employee sidebar is a shortcut to the existing index with `status=completed`. It is a filtered link, not a second register.
- An approver may mark remarks on `approve`, `acknowledge`, or `forward` as internal. The audit row keeps the text and `is_internal = true`, but the filer's timeline receives `remarks: null`. Return remarks are always visible because they tell the filer what to revise.

**Why:** The filer needs to see where the document is without learning internal processing notes, and a completed shortcut is faster than opening the index and choosing a status filter.

**Evidence:** `app/Services/TravelOrderRoutePresenter.php::route,timeline`; `app/Http/Controllers/TravelOrders/TravelOrderController.php::show`; `resources/js/components/travel-orders/document-status-dialog.tsx`; `resources/js/components/travel-orders/route-progress.tsx`; `database/migrations/2026_11_25_000004_add_is_internal_to_travel_order_actions_table.php`; `app/Http/Controllers/Approvals/TravelOrderActionController.php::internal`.

**Change control:** **Approval required** for changing who may see internal notes or for hiding any audit event from the filer. The tracker must keep reading the frozen route; it must not re-evaluate the workflow.

---

## 21. Travel order filing and HRMO series numbers

**Decision:**

- A travel order receives its filing number the first time it is submitted: `TO-YYYY-NNNN`, where the suffix counts the orders filed in that year. A returned order keeps the same filing number when it is resubmitted.
- `to_number` holds the filing number. `control_no` holds the HRMO series number.
- The HRMO series number is `HRMO-TO-YYYY-NNNN` and is assigned when the HRMO step is cleared, whether that step is a signature or a receipt. Its suffix counts the orders HRMO approved in that year, independently of the filing sequence, so a document approved out of order still gets the next HRMO number.
- An existing `control_no` is never overwritten. A legacy order approved without a filing number receives one first, then the series number.
- The record/release action no longer accepts caller-supplied numbers. It records only the release status and remarks; both numbers are owned by the numbering service.
- On the printed travel order, `NUMBER` is the filing number and `SERIES` is the HRMO series number.

**Why:** Filing and HRMO approval are two separate events with two separate yearly sequences. Keeping them in separate columns preserves the filing number for audit and lets the HRMO series stay sequential even when HRMO acts on documents out of filing order.

**Evidence:** `app/Services/TravelOrderNumberService.php`; `app/Services/TravelOrderSubmissionService.php::submit`; `app/Services/TravelOrderApprovalService.php::approve,acknowledge,assignHrmoSeries,record`; `resources/js/components/travel-orders/print/travel-order-document.tsx`; `tests/Feature/TravelOrders/TravelOrderNumberingTest.php`.

**Change control:** **Approval required** for changing the prefixes, the per-year scope, the point at which a number is assigned, or for allowing callers to supply numbers again.

---

## Change checklist

Before changing a decision above:

1. Identify whether it touches routing, authorization, financial evidence, or notification URL policy.
2. If yes, stop and get explicit approval. Do not "just fix" it in a UI commit.
3. Update this file in the same commit as the code change.
4. Run the verification gate in [TESTING.md](TESTING.md) and the integrity check in [DEPLOYMENT.md](DEPLOYMENT.md).
5. Re-seed only in local/test unless production credentials were explicitly configured.
