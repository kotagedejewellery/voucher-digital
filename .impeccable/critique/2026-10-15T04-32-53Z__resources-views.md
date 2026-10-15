---
target: all customer and admin interaction-feedback surfaces
total_score: 28
max_score: 40
na_heuristics: 
p0_count: 0
p1_count: 1
target_identity: "file:E:\\AI Engineer\\PT KotaGede Jewellery\\New System\\digital-voucher\\resources\\views"
timestamp: 2026-10-15T04-32-53Z
slug: resources-views
---
## Design Health Score

| # | Heuristic | Score | Key issue |
|---|---|---:|---|
| 1 | Visibility of system status | 3/4 | Feedback exists, but runtime failure feedback looks successful and can disappear. |
| 2 | Match with the real world | 3/4 | Bahasa Indonesia, rupiah, WIB and manual merchant verification fit the task; duplicate-reference recovery is under-labelled. |
| 3 | User control and freedom | 3/4 | Confirmation cancellation/Escape and filter reset work; camera permission has no in-page cancellation while pending. |
| 4 | Consistency and standards | 3/4 | Shared controls work; server and runtime feedback differ in tone and lifetime. |
| 5 | Error prevention | 2/4 | Server-side financial guards are strong; a frozen client payment-time maximum can reject valid entries. |
| 6 | Recognition rather than recall | 3/4 | Dialogs retain context; copy and duplicate-reference recovery require hidden context. |
| 7 | Flexibility and efficiency | 3/4 | Search, scanner/manual input and CSV are available; duplicate lookup needs a clearer direct path. |
| 8 | Aesthetic and minimalist design | 3/4 | Customer and operator tasks stay focused; source review only. |
| 9 | Error recovery | 2/4 | Field-linked recovery is strong; client-side payment time, clipboard and transient offline feedback need better recovery. |
| 10 | Help and documentation | 3/4 | Task hints/support links exist; a few recovery messages omit the next actionable step. |
| **Total** | | **28/40** | **Good; targeted hardening recommended.** |

## Design Specificity Verdict

The interaction model is authored for this product: it distinguishes QRIS payment from voucher QR, manual merchant verification from customer confirmation, WIB-based tier values, one-time transfer, and a claim that locks the discount. Preserve the current visual world and task sequencing.

The deterministic detector was clean (`[]`, 0 findings) for `resources/views`; no ignore file exists. Browser evidence at 390px showed no overflow or JavaScript errors, labelled controls, keyboard-operable drawer and confirmation cancellation, but the served local artifact has older branding than current source. Browser observations therefore validate interaction patterns only, not final visual fidelity.

## Overall Impression

The main flows have a sound feedback foundation: irreversible actions are deliberately confirmed, field errors are actionable, loading states avoid duplicate work, and customer status remains inspectable. The most valuable next pass is not more popups; it is making failure feedback semantically correct, persistent when recovery matters, and directly actionable.

## What's Working

- Payment verification, QR scan preview, claim and transfer remain distinct decisions, with server guards behind the UI.
- Confirmation dialogs summarize current values, focus Batal first, and support Escape without submitting.
- Search recovery, stale-result inertness, inline validation and customer status links protect users from ambiguous outcomes.

## Priority Issues

### P1 — Valid payment time can be rejected after the page has been open

`admin/order.blade.php` renders `paid_at.max` at page render. A payment received after that moment can be rejected by native browser validation, despite the server correctly validating against the time of submission.

**Fix:** remove or refresh the frozen client maximum; retain the server-side future-time guard and show a specific Indonesian error.

### P2 — Failure feedback is styled and timed like success feedback

Offline-submit and clipboard failure messages flow through a shared `notice success` outlet, with a six-second client timeout. An interrupted customer/operator can lose the recovery instruction, and the green styling contradicts the message.

**Fix:** add semantic info/warning/error variants to the shared feedback component. Keep blocked-payment or offline recovery visible until resolved or dismissed; reserve short toasts for noncritical success.

### P2 — Copy-link failure does not reveal a copyable value

The private status URL is held in a data attribute. When copying fails, the user is told to copy the displayed value manually even though the URL is not rendered.

**Fix:** reveal a selectable private URL on failure, or explicitly tell the user to copy the address-bar URL.

### P2 — Duplicate merchant-reference recovery hides the likely existing record

The recovery instruction sends an operator to the order list, which defaults to pending verification while the matching record is usually paid. Search supports merchant reference, but its label does not say so.

**Fix:** link to the list with the merchant reference and all statuses preselected; at minimum, say “Pilih Semua status” and label the search input accordingly.

### P3 — Financial confirmation shows a raw datetime-local string

The verification dialog presents the browser `datetime-local` syntax rather than a readable Indonesian WIB value.

**Fix:** format the existing wall-clock time for display only, without converting its timezone.

## Persona Red Flags

- **Jordan, first-time customer:** a failed copy action gives no visible link to copy and weakens trust in the “periksa status” path.
- **Casey, interrupted mobile customer:** transient offline feedback can disappear while switching to the payment app; the payment-time maximum can become stale after interruption.
- **Alex, operator:** investigating a duplicate payment requires knowing to switch out of the pending-only default.
- **Sam, keyboard/screen-reader operator:** dialog and drawer controls have good keyboard foundations, but failure announcements share a success-styled polite outlet; screen-reader behavior still needs direct testing.

## Minor Observations

- Camera acquisition has no visible cancellation while permission is pending; cleanup on page hide should be tested on real mobile hardware.
- Server and client feedback use separate mechanisms at the same screen position and can overlap.
- The manual lookup hint should be programmatically associated with its input.

## Questions to Consider

- Which recovery messages must persist because the user may leave the screen to pay or contact support?
- Can every operator recovery message include a direct filtered path instead of asking them to reproduce the search context?
- Does a confirmation dialog add safety, or is inline feedback sufficient for that action?
