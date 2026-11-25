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

## Change checklist

Before changing a decision above:

1. Identify whether it touches routing, authorization, financial evidence, or notification URL policy.
2. If yes, stop and get explicit approval. Do not "just fix" it in a UI commit.
3. Update this file in the same commit as the code change.
4. Run the verification gate in [TESTING.md](TESTING.md) and the integrity check in [DEPLOYMENT.md](DEPLOYMENT.md).
5. Re-seed only in local/test unless production credentials were explicitly configured.
