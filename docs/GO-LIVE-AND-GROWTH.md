# KeenPocket — Go-Live Hardening & Growth Roadmap

KeenPocket is a community savings platform with two products:

- **Pockets** — group savings / cooperative-purchasing clubs ("hands", monthly invoices).
- **Adashi** — rotating savings (ROSCA / esusu): members contribute each cycle and take turns receiving the pot.

This document captures (1) what was cleaned up, (2) what still blocks a public launch, and
(3) concrete features to grow the user base.

---

## 1. What changed in this pass (cleanup & hardening)

| Area | Change |
| --- | --- |
| **Secrets** | Untracked `keenpockets_.sql` & `.env.sqlite.bak`; broadened `.gitignore` (`*.sql`, `*.bak`, `.env.*`, `.DS_Store`, firebase json). Added a safe `.env.example`. |
| **Auth bug (critical)** | Registration account-claim path stored the password **in plaintext** — now hashed. |
| **Account enumeration** | `login` no longer uses `exists:` validation that revealed which phone numbers are registered. Unknown phone and wrong password now return the same generic response. |
| **Dangerous public routes** | `/firebase/upload` (service-account upload) and the legacy polling-agent UI are now **local-only**; `/api/test` (unauthenticated bulk-write) is local-only; production root is a clean health check. |
| **Rate limiting** | New `auth` throttle (5/min per phone+IP, 100/day per IP) on `register` / `login` / `otp`. |
| **Money integrity** | `createInvoice` and `createPocket` now run inside DB **transactions** (no half-written invoices). Push notifications are **non-fatal** (a Firebase hiccup can no longer roll back a contribution). |
| **Mass assignment** | Replaced `$guarded = []` on the Adashi models + `InvoiceItem` with explicit `$fillable` + `$casts`. |
| **De-duplication** | The giant country-code regex (copied 4×) is now `App\Support\PhoneNumber::normalize()`. |
| **OTP scaffold** | Config-driven phone verification (`config/otp.php`), **shipped OFF** (`OTP_ENABLED=false`). Service + drivers (log / Termii / Africa's Talking / Twilio) + `OtpController` + migration + registration gate that is inert while disabled. |
| **Tests** | `PhoneNumberTest` (unit, passing), `AuthTest`, `OtpTest`. Test DB switched to in-memory sqlite; the two Adashi `ALTER invoices` migrations were made idempotent / driver-aware so the suite can migrate. |

---

## 2. Go-live blockers / must-do next (in priority order)

### 2.1 Rotate all credentials — **do this first**
The secrets were committed to git history (and pushed to GitHub), so they are compromised regardless of file removal. Rotate:
- `APP_KEY` (note: rotating invalidates existing encrypted/session data)
- DB password, OAuth client secret, AWS keys, mail password, Pusher secret
- Firebase service account (regenerate, then re-upload locally)

Optional but recommended: purge the files from history (`git filter-repo --path keenpockets_.sql --path .env.sqlite.bak --invert-paths`) and force-push.

### 2.2 Core-table migrations — **reconstructed (guarded), needs reconciliation**
The core tables had **no migrations** — they lived only in the production MySQL DB, so a fresh deploy
couldn't build the schema and money-path tests couldn't run.

**Done in this pass** — reconstructed, *guarded* migrations:
- `2025_01_01_000000_add_missing_user_columns` — backfills `users.phone_number / username / fcm_token`
  (only if absent).
- `2025_01_01_000001_create_legacy_core_tables` — creates `pockets`, `pocket_slots`, `invoices`,
  `invoice_item`, `items`, `pocket_items`, `notifications`, `invitations`, `banned_users`, `posts`
  (each only **if it does not already exist**).

Because every step is guarded, these are a **complete no-op against the existing production DB** and
only build the schema in fresh / test environments. Verified: `migrate:fresh` runs the full set clean
on sqlite, so the test suite can now build the whole schema (and the Adashi `invoices` alter runs
instead of being skipped).

**Still required before trusting this for a production rebuild:**
1. Reconcile column types/lengths/indexes against the real schema — run on the live DB and diff:
   ```bash
   php artisan schema:dump          # writes database/schema/mysql-schema.sql
   # or: mysqldump --no-data -u <user> -p <db> > database/legacy-schema.sql
   ```
   The reconstruction is best-effort from a 2022 dump + current code usage; enums are modelled as
   strings for sqlite compatibility, and constraints/foreign keys were intentionally omitted.
2. **Not yet reconstructed** (couldn't verify their columns): `purchase_items`, `purchase_preferences`,
   `purchasing_items`, and the legacy election tables (`agents`, `states`, `lgas`, `wards`,
   `polling_units`). Add these from the `schema:dump` output.

Going forward, make every schema change a migration.

### 2.3 PHP version compatibility
The locked dependencies (Laravel 9.9, Carbon 2.57) **fail on PHP 8.5** (`Carbon::setLastErrors`).
Deploy on **PHP 8.1–8.3**, or schedule an upgrade to Laravel 10/11 + Carbon 2.7x to support newer PHP.
`composer.json` says `^8.0.2` but the lockfile effectively caps the usable PHP at ~8.3.

### 2.4 Finish the controller split (deferred on purpose)
`APIController` is ~1,000 lines. The plan (do it once §2.2 gives a test DB so each endpoint can be
regression-tested):
- `PocketController` — create/join/switch/search/show/myPockets/bank details
- `InvoiceController` — createInvoice, invoice, pocket(Month)Invoices, changePaymentStatus
- `ItemController` — add/remove/subscribe payment & shopping items
- `SocialController` — posts, post, notifications, inviteUser, accept/cancel requests
- Move the MySQL `FORMAT()`/`DATE_FORMAT()` reporting queries into a `PocketReportService`.
This is pure reorganization (no behavior change) and should be its own PR.

### 2.5 Known correctness items to address
- **Slot over-subscription race** in `joinPocket`: availability is checked then a slot is inserted
  without locking — two concurrent joins can oversell. Fix with `lockForUpdate()` on the pocket inside
  a transaction.
- **Error handling**: several `catch` blocks return **HTTP 200** with an error message and append
  `$e->getMessage()` (info leak). Standardize on proper status codes + a generic message (log the
  detail). Best paired with the controller split.
- **Passport + Sanctum** are both installed but only Sanctum tokens are used — drop Passport to reduce
  surface, or standardize on one.

---

## 3. Growth — getting more people to use it

The product today assumes a trusted circle (family/friends) and **manual, offline payments** (the owner
marks an invoice "Paid"). To grow beyond that circle, the two unlocks are **(a) trust between strangers**
and **(b) frictionless money movement**, then **(c) viral invite loops**.

### 3.1 Built-in growth loops (the "make more people use it" ask)
1. **Referral program** — *implemented, see §6.* Every user gets a code/link; an invitee who registers
   with it and joins their first pocket/adashi "qualifies" the referral. The single highest-leverage
   viral loop for a savings app.
2. **WhatsApp-first invites** — *implemented, see §6.* `referrals/me` returns a ready `wa.me` share URL
   with the invite link baked in, so every member becomes a recruiter on the channel Nigerians actually
   use. (Pair with app universal links so the link opens straight to the join screen.)
3. **Shareable pocket/adashi cards** — a generated image (group name, target, members, payout schedule)
   users post to status/groups. Social proof + curiosity → installs.
4. **Public directory** — *implemented, see §7.* People can browse and join open **pockets and adashis**
   (KYC-gated when KYC is on). Turns a closed tool into a marketplace.

### 3.2 Trust & safety (required to scale past friends)
- **Phone OTP** — scaffolded here; turn on once an SMS provider is funded.
- **KYC** — *scaffolded (dormant), see §10*: BVN / NIN verification; when on, the public directory only
  lists verified organizers.
- **Escrow + automated payout schedule** so members trust the organizer can't run off with the pot.
- **Member reputation** — *implemented, see §7*: on-time-payment score + activity, surfaced before
  joining. (User-to-user ratings still to come.)
- **Dispute/refund flow** and a clear T&C / default policy (there are already banned-user hooks).

### 3.3 Frictionless money (the biggest product gap)
- **Payment gateway** — *scaffolded (dormant), see §5*: collect contributions via **Paystack** /
  **Flutterwave** instead of "mark as Paid". Converts a tracking app into a savings *platform*.
- **Automated payouts** — *scaffolded (dormant), see §9*: disburse a collected Adashi pot to the
  receiver's bank automatically when a cycle closes (the disbursement half of the money platform).
- **In-app wallet** — *implemented (dormant), see §13*: members fund once and pay contributions straight
  from balance. (Auto-debit / standing orders still to add.)
- **Automated, multi-channel reminders** — *implemented, see §8 (push + SMS) and §14 (WhatsApp)*.

### 3.4 Engagement & retention
- **Savings streaks + badges** — *implemented, see §12.* (Leaderboards still to expand.)
- **Goal visualization** (progress bars toward target_amount; "you're 60% to Sallah").
- **Organizer analytics**: collection rate, defaulters, projected payout dates.

### 3.5 Suggested sequence
1. Turn on OTP + add Paystack/Flutterwave collection (trust + automated money).
2. Ship the **referral program + WhatsApp invites** (viral loop).
3. Add member reputation + public directory (discovery).
4. Layer streaks/badges + multi-channel reminders (retention).

---

## 4. Local setup notes
- Run on **PHP 8.1–8.3**. `cp .env.example .env && php artisan key:generate`.
- Configure the live MySQL DB. Fresh/test DBs now build from migrations (see §2.2); reconcile types against `schema:dump` before a production rebuild.
- OTP is off by default; to try it locally set `OTP_ENABLED=true OTP_PROVIDER=log` and read the code from the log.
- Tests: `php artisan test` (auth/OTP/payment/referral **feature** tests require PHP 8.1–8.3; the unit tests run anywhere).

---

## 6. Referral / WhatsApp invite loop (implemented)

The viral loop is live. Unlike payments/OTP it **ships ON** (`REFERRALS_ENABLED=true`) because it moves
no money; only the optional **reward** (`REFERRAL_REWARD_ENABLED`) ships off. See
[config/referrals.php](../config/referrals.php).

**How it works:**
1. Every user has a unique `users.referral_code` (generated on demand; unambiguous alphabet, no 0/O/1/I).
2. `GET /api/referrals/me` (auth) returns `{ code, invite_link, whatsapp_url, stats }` — `whatsapp_url`
   is a ready-to-share `https://wa.me/?text=…` link with the invite URL embedded. `GET /api/referrals`
   lists who the user has invited.
3. `POST /api/register` accepts an optional `referral_code`; the signup is attributed to the referrer as
   a **pending** `referrals` row (self-referral and duplicates are ignored; failures never block signup).
4. When that invitee **joins their first pocket or adashi**, `ReferralService::qualifyQuietly()` (a
   non-fatal hook in `joinPocket` and Adashi `join`) marks the referral **qualified**.
5. If `REFERRAL_REWARD_ENABLED=true` and an amount is set, qualifying records a reward entitlement on the
   referral row (status `rewarded`). **No money is disbursed** — wire that to a wallet/credit/fee-waiver
   when the payments/wallet work lands.

**Pieces:** `App\Services\Referral\ReferralService`, `App\Http\Controllers\ReferralController`,
`App\Models\Referral`, migrations `2026_06_10_000002` (guarded `referral_code` on users) and
`2026_06_10_000003` (`referrals` table). Tests: `tests/Unit/ReferralLinkTest.php` (runs anywhere) and
`tests/Feature/ReferralTest.php` (attribution / qualify / self-referral, PHP 8.1–8.3).

**Client TODO:** add an `/invite?ref=CODE` deep link / universal link so a tapped WhatsApp invite opens
the app (or store listing) and pre-fills the code on the registration screen.

---

## 5. Online payments (scaffolded — currently dormant)

A config-gated payment integration is in place but **ships OFF** (`PAYMENTS_ENABLED=false`), so the
existing manual "mark as Paid" flow is completely unchanged until you switch it on.

**Pieces** ([config/payments.php](../config/payments.php)):
- `App\Services\Payments\PaymentService` — drivers: `log` (simulates success, no network), `paystack`,
  `flutterwave`. Methods: `initialize`, `verify`, `handleWebhook` (signature-verified, then re-verifies
  against the gateway API — webhook payloads are never trusted as proof of payment).
- `App\Actions\MarkInvoicePaid` — single idempotent place that flips an invoice to `Paid`
  (`paid_through = Online`) and reconciles Adashi-linked records via the existing `reconcilePayments`.
- `App\Http\Controllers\PaymentController` + routes:
  - `GET  /api/payments/status` (auth) — `{ enabled, provider, currency }`
  - `POST /api/payments/initialize` (auth) — `{ invoice_id }` → `{ authorization_url, reference }`
  - `GET  /api/payments/verify?reference=…` (auth) — confirm + settle after redirect
  - `POST /api/payments/webhook/{provider}` (public, signature-verified)
- `payment_transactions` table (migration included) — audit trail of every attempt.

**To turn on (per provider):**
1. Set `PAYMENTS_ENABLED=true`, `PAYMENTS_PROVIDER=paystack` (or `flutterwave`), and the provider keys
   in `.env`. Set `PAYMENTS_CALLBACK_URL` to your app/site return URL.
2. In the provider dashboard, point the webhook to `https://<api-host>/api/payments/webhook/paystack`
   (or `/flutterwave`). For Flutterwave also set `FLUTTERWAVE_SECRET_HASH` to match the dashboard.
3. Client flow: call `initialize` → open `authorization_url` → on return call `verify` (the webhook is
   the authoritative backstop).

**Tests:** webhook signature logic is unit-tested (`tests/Unit/PaymentSignatureTest.php`, runs on any
PHP); settlement via `MarkInvoicePaid` is covered in `tests/Feature/PaymentSettlementTest.php` (now
possible thanks to the reconstructed `invoices` migration — runs on PHP 8.1–8.3).

**Not yet done (intentionally):** automated *payouts/disbursement* to the receiving member (collection
only for now).

---

## 7. Discovery: reputation + directory (implemented)

Turns the closed tool into something strangers can browse and trust. Ships ON
([config/discovery.php](../config/discovery.php)); no money involved.

**Member reputation** (`App\Services\Reputation\ReputationService`) — computed on the fly from existing
activity, **no new tables**:
- Score 0–100 = payment reliability (paid ÷ total invoices, ≤70) + activity (pockets/adashis joined,
  cycles received, ≤30). Bands: `New` (no history) → `Building` → `Bronze` → `Silver` → `Gold`.
- `GET /api/reputation/me` and `GET /api/users/{id}/reputation` (auth).

**Public directory** (`App\Http\Controllers\DirectoryController`):
- `GET /api/directory/pockets?q=` (auth, paginated) — lists pockets that are **open** (`status = 1`,
  not invitation-only) **and not full** (active `hand_count` sum < `max_keens`; `max_keens = 0` = uncapped).
- Each row: pocket summary + `slots_used` / `slots_available` + `organizer` name + `organizer_id` +
  **masked** organizer phone. Reputation is fetched per-organizer via the endpoint above (kept out of the
  list to avoid N+1).

**Product decisions made (adjust if you disagree):**
- "Open to join" = the existing pocket `status` flag (the join flow already treats `!status` as
  invitation-only). Full pockets are excluded via a portable correlated subquery (verified on sqlite).
- Organizer phone is masked in the directory (it was previously exposed raw by `search`).

**Adashi directory** (added): `GET /api/directory/adashi` lists adashis that are `is_public` + `ACTIVE`,
KYC-gated on the admin, admin phone masked. Adashis are **private by default** (`adashis.is_public`,
guarded migration `2026_06_11_000004`); the admin toggles listing via `POST /api/adashi/{id}/visibility`,
and can set it at creation (`is_public` on `POST /api/adashi`). Joining is now privacy-aware: **private
adashis are admin-only**, public ones allow **self-join only** (and the long-standing
`total_members` double-count on re-join was fixed). Tests: `tests/Feature/AdashiDirectoryTest.php`
(PHP 8.1–8.3); listing verified on seeded sqlite.

**Follow-ups:** KYC gating also on pocket/adashi creation, user-to-user ratings, and caching reputation if
directory traffic grows. Discovery tests: `tests/Unit/ReputationScoreTest.php` (runs anywhere) +
`tests/Feature/DiscoveryTest.php` (PHP 8.1–8.3).

---

## 8. Multi-channel reminders (implemented)

Missed payments are the #1 reason savings groups collapse, so reminders now go out over **push + SMS**.

**SMS channel** (`App\Services\Sms\SmsSender`, [config/sms.php](../config/sms.php)) — reusable, with
`log`/Termii/Africa's Talking/Twilio drivers, **shipped OFF** (`SMS_ENABLED=false`; SMS costs money) and
sharing the OTP provider credentials. When off, reminders simply go push-only.

**Wiring:** `Notification::make()` now auto-delivers over SMS for any `PAYMENT_REMINDER` notification (and
any caller passing `$sms=true`), via a best-effort `Notification::sendSms()` that never throws. So the
**existing** Adashi reminders (24h-due, 30%-remaining, overdue alerts — already scheduled in
`adashi:process` every 5 min) became multi-channel with no scheduler changes.

**New — pocket reminders:** pockets previously got *no* reminders (`paymentReminderNotification` was never
called). Added `pockets:remind` (`App\Console\Commands\PocketReminder`), scheduled **weekly (Mon 09:00)**,
which nudges active members who have unpaid invoices — push + SMS. Scoped to existing unpaid invoices
rather than inferred per-installment due dates (pockets have no due-date field yet — a future addition
would enable precise "due in 3 days" reminders).

**Verified:** `SmsSender` unit-tested on any PHP (`tests/Unit/SmsSenderTest.php`); reminder targeting
verified on seeded sqlite (active+unpaid reminded; paid and inactive skipped); full command +
notification creation in `tests/Feature/ReminderTest.php` (PHP 8.1–8.3).

**To turn on SMS:** set `SMS_ENABLED=true` and `SMS_PROVIDER` + the provider keys (same as OTP). Reminders
immediately start sending SMS in addition to push. WhatsApp template reminders are in §14.

---

## 9. Automated payouts (implemented — dormant)

The disbursement half of the money platform: when an Adashi cycle is fully collected, send the pot to the
receiver's bank automatically. **Ships OFF** (`PAYOUTS_ENABLED=false`) — it moves real money OUT — and
reuses the Paystack/Flutterwave keys (their **Transfers** product must be enabled).
See [config/payouts.php](../config/payouts.php).

**Safety invariants (money-out — these matter most):**
- **One payout per cycle**: `payouts.adashi_record_id` is UNIQUE, and `PayoutService` refuses to re-attempt
  a payout that is already `pending`/`success`. A pot can never be disbursed twice. (Verified: idempotency
  short-circuit + unique constraint.)
- **Re-verify, don't trust**: transfer webhooks are signature-verified (Paystack HMAC / Flutterwave hash)
  before a payout flips to `success`/`failed`.
- **No silent money loss**: a receiver with no bank details records a `failed` payout
  (`failure_reason = no_bank_details`) — visible, retryable, never a silent skip.

**Pieces:** `App\Services\Payouts\PayoutService` (drivers `log`/`paystack`/`flutterwave`),
`App\Http\Controllers\PayoutController`, `App\Models\Payout`, migrations `2026_06_11_000001` (guarded
`payout_*` bank columns on users) + `2026_06_11_000002` (`payouts` table).

**Endpoints:**
- `GET  /api/payouts/status` — `{ enabled, provider, currency }`
- `POST /api/payouts/bank-account` — member saves their payout destination (`bank_name`, `bank_code`, `account_number`)
- `POST /api/adashi/{id}/payout` — **admin** triggers payout for the latest closed cycle (for MANUAL rotation)
- `POST /api/payouts/webhook/{provider}` — public, signature-verified transfer-status callback

**Flow:** AUTO-rotation adashis disburse automatically — `AdashiController::autoRotate()` calls
`PayoutService::attemptForRecord()` (non-fatal) when a cycle closes. MANUAL adashis use the admin endpoint.

**Prerequisites / follow-ups:** members must save bank details first (add a client screen → `bank-account`);
bank-code validation / account-name resolution (Paystack `bank`/`resolve` endpoints); a retry path for
`failed` payouts; and pocket payouts (pockets have no rotating-receiver concept — out of scope here).
Tests: `tests/Unit/PayoutSignatureTest.php` (signature + double-pay guard, any PHP) +
`tests/Feature/PayoutTest.php` (disburse / idempotency / no-bank, PHP 8.1–8.3).

**To turn on:** `PAYOUTS_ENABLED=true`, `PAYOUTS_PROVIDER=paystack|flutterwave`, ensure Transfers is enabled
on the provider account, and point the provider's transfer webhook at `/api/payouts/webhook/{provider}`.

---

## 10. KYC identity verification (implemented — dormant)

Identity verification (BVN / NIN) — the trust gate for letting strangers transact. **Ships OFF**
(`KYC_ENABLED=false`); while off nothing is verified and no flow requires it. See
[config/kyc.php](../config/kyc.php).

**Privacy-first:** the raw BVN/NIN is sent to the provider for the check and then **discarded** — only the
**last 4 digits** (for the user's reference) and the provider's verification reference are stored
(`users.kyc_*`, guarded migration `2026_06_11_000003`).

**Pieces:** `App\Services\Kyc\KycService` (drivers `log` (dev, simulates) / `dojah`),
`App\Http\Controllers\KycController`, fields on `users`.

**Endpoints:**
- `GET  /api/kyc/status` — `{ enabled, status, type, id_last4, verified_at }`
- `POST /api/kyc/submit` — `{ type: BVN|NIN, id_number }` (rate-limited; raw number never persisted)

**Trust gate:** when `KYC_ENABLED=true` and `KYC_GATE_DIRECTORY=true`, the public directory (§7) only lists
pockets whose **organizer is KYC-verified**. Verified on seeded sqlite: KYC off → all organizers shown;
KYC on → unverified organizers hidden.

**Follow-ups:** also gate **payout** and **pocket/adashi creation** on verified status; add a name/DOB match
step; webhook for async provider results. Tests: `tests/Unit/KycServiceTest.php` (any PHP) +
`tests/Feature/KycTest.php` (submit + directory gating, PHP 8.1–8.3).

**To turn on:** `KYC_ENABLED=true`, `KYC_PROVIDER=dojah` + `DOJAH_APP_ID`/`DOJAH_SECRET_KEY`.

---

## 11. Peer trust ratings (implemented)

Members rate the organizer (1–5★) of a pocket/adashi they belong to — capturing trustworthiness that
payment data alone can't (did the organizer disburse, communicate, run it well?). Ships ON; no money.

**Pieces:** `App\Services\Rating\RatingService`, `App\Http\Controllers\RatingController`,
`App\Models\Rating`, migration `2026_06_11_000005` (`ratings` table, **one rating per rater per group**,
updatable).

**Endpoints:**
- `POST /api/ratings` — `{ context_type: pocket|adashi, context_id, stars: 1–5, comment? }`
- `GET  /api/users/{id}/ratings` — `{ summary: { average, count }, ratings: [...] }`

**Authorization:** the rater must be an **active member** of the pocket/adashi; the ratee is its
**organizer** (pocket owner / adashi admin); you cannot rate yourself; re-rating updates your existing
rating. Verified on seeded sqlite (non-member→403, self→422, invalid context→422, average math).

**Reputation integration:** `ReputationService::forUser()` now also returns `rating_average` /
`rating_count`, so a member's trust profile combines **payment reliability + activity + peer ratings**.
(The 0–100 score formula is unchanged; rating is surfaced alongside it for now.) Tests:
`tests/Feature/RatingTest.php` (PHP 8.1–8.3).

---

## 12. Gamification: streaks + badges (implemented)

Engagement layer, computed on the fly from existing activity — **no new mutable state**, so it can never
drift from reality. Ships ON; no money. Thresholds are tunable in [config/gamification.php](../config/gamification.php).

**Pieces:** `App\Services\Gamification\GamificationService`, `App\Http\Controllers\GamificationController`.

**Endpoints:**
- `GET /api/gamification/me` — `{ streak, total_contributed, badges: [...], metrics }`
- `GET /api/users/{id}/badges` — earned badges only (public profile)

**Streak** = current run of consecutive **Paid** invoices (newest backwards), across the member's pocket
slots and adashi memberships. **Total contributed** = sum of their Paid invoices.

**Badges** (each returned with an `earned` flag so clients show locked + unlocked):
`first_pocket`, `adashi_member`, `reliable_payer` (≥3 invoices & ≥90% reliability), `cycle_champion`
(received an adashi payout), `top_organizer` (≥3 ratings & ≥4.5★), `recruiter` (≥3 qualified referrals),
`verified` (KYC), `big_saver` (≥₦100k contributed). Reuses reputation/rating/referral/KYC data.

**Verified:** `evaluateBadges()` is pure and unit-tested (`tests/Unit/GamificationBadgeTest.php`, any PHP);
full profile (streak=2, total=₦5,000, `first_pocket`) verified on seeded sqlite; endpoint coverage in
`tests/Feature/GamificationTest.php` (PHP 8.1–8.3).

---

## 13. In-app wallet (implemented — dormant)

Members fund a balance once and pay contributions straight from it — no card re-entry each cycle.
**Ships OFF** (`WALLET_ENABLED=false`); it's money handling. See [config/wallet.php](../config/wallet.php).

**Ledger safety:** `App\Services\Wallet\WalletService` mutations are **atomic + row-locked**
(`SELECT … FOR UPDATE`), write a `wallet_transactions` row with the **running balance**, and credits are
**idempotent on `reference`** (a repeated gateway callback funds once). Debits throw
`InsufficientFundsException` — never overdraw.

**Pieces:** `Wallet` + `WalletTransaction` models, migration `2026_06_11_000006`,
`App\Http\Controllers\WalletController`.

**Endpoints:**
- `GET  /api/wallet` — `{ balance, currency }`
- `GET  /api/wallet/history` — paginated ledger
- `POST /api/wallet/topup` — `{ amount }` (credits immediately under the dev `log` payment provider;
  production funding goes through the gateway + a credit on webhook — a documented follow-up)
- `POST /api/wallet/pay-invoice` — `{ invoice_id }`: **debit + settle in one transaction**
  (`MarkInvoicePaid`, `paid_through = Wallet`); insufficient balance → 422 and the invoice stays unpaid.

**Verified:** idempotent credit + overdraft block verified on seeded sqlite; full top-up → pay-invoice →
insufficient → authorization flow in `tests/Feature/WalletTest.php` (PHP 8.1–8.3).

**Follow-ups:** gateway-funded top-ups (purpose-tagged payment → webhook credit), auto-debit / standing
orders for recurring contributions, and routing Adashi payouts to the wallet as an option.

---

## 14. WhatsApp template reminders (implemented — dormant)

WhatsApp is the dominant channel in Nigeria, so reminders can also go out as WhatsApp **template**
messages via the Meta Cloud API. **Ships OFF** (`WHATSAPP_ENABLED=false`); reminders still send push/SMS.
See [config/whatsapp.php](../config/whatsapp.php).

**Pieces:** `App\Services\WhatsApp\WhatsAppSender` (`log`/`meta` drivers). Wired into the same reminder
path as SMS — `Notification::make()` calls `sendWhatsApp()` (best-effort, never throws) for any
`PAYMENT_REMINDER`, so the existing Adashi + pocket reminders gain a WhatsApp channel with no scheduler
change. Body text is passed as template parameter `{{1}}`.

**Verified:** `WhatsAppSender` unit-tested (`tests/Unit/WhatsAppSenderTest.php`, any PHP).

**To turn on:** create + get approval for a `payment_reminder` template in WhatsApp Business, then set
`WHATSAPP_ENABLED=true`, `WHATSAPP_PROVIDER=meta`, `WHATSAPP_TOKEN`, `WHATSAPP_PHONE_NUMBER_ID`, and
`WHATSAPP_TEMPLATE_PAYMENT_REMINDER` to the approved template name.
