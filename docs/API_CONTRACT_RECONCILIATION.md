# API Contract Reconciliation

Backend reconciliation against the mobile client contract (`API_REFERENCE.md`, client source of
truth: `Api.kt` / `AdashiApiService.kt`). Generated 2026-06-11.

## ✅ Fixed in the backend

| # | Issue | Severity | Fix |
|---|-------|----------|-----|
| 1 | `AdashiMember.has_received` / `is_active` serialized as `true/false` | 🔴 contract break | Cast changed `boolean → integer` → now `0/1` (checklist §Adashi). Also `AdashiContributor.is_active`. |
| 2 | `rotation_mode` only accepted `AUTO`/`MANUAL`; client sends `manual`/`auto` → adashi create **422'd** | 🔴 breaks app | Validation accepts any case; normalized to uppercase on store (scheduler still compares `AUTO`). |
| 3 | `POST /api/change-password` missing (404) | 🔴 | Added (`AuthController@changePassword`), returns boolean. |
| 4 | `POST /api/refresh-token` missing | 🔴 | Added; **login now also returns `refresh_token`**. Rotates the access/refresh pair; rejects an access token used as refresh. |
| 5 | `GET /api/request-token` + `POST /api/verify-token` missing | 🔴 | Added (email token via `password_resets`, 30-min validity, no account enumeration). Token delivered via log in dev — wire to mail/SMS for prod. |
| 6 | `POST /api/adashi/{id}/auto-rotate` missing | 🟠 | Added (`AdashiController@rotate` → reconciles + rotates the current cycle). |
| 7 | Register response lacked `message`/`keens`; login error lacked `status`/`token` | 🟡 | Aligned to the doc's exact shapes (§2.1, §2.2). |

Tests: `tests/Feature/ContractTest.php` (login+refresh, change-password, request/verify-token,
lowercase `rotation_mode`, integer member flags) — runs on PHP 8.1–8.3. Unit suite stays green on any PHP.

## ⚠️ Format differences the mobile team should confirm (NOT changed unilaterally)

These are bidirectional decisions — the backend's internal logic / scheduler depends on the current
values, so flag whether **backend adapts** or **client tolerates**:

1. **Casing of `status` / `rotation_mode` in responses.** Backend stores/returns `ACTIVE` and (normalized)
   `AUTO`/`MANUAL`; the doc examples show lowercase (`active`, `manual`). The scheduler and reconcile
   logic compare uppercase. **Recommendation:** client compares case-insensitively, or we add an output
   accessor to lowercase these. (Low effort either way — pick one.)
2. **`amount_per_cycle` type.** Stored/returned as integer; doc example shows string `"50000"`. Client
   sends it as a string and Laravel accepts it. Confirm the client deserializer tolerates a JSON number.
3. **`due_date` vs `due_at`.** Adashi records expose `due_at` (DB column); doc §5.3/§5.7 reference
   `due_date` (and §5.7 shows both). Confirm the client field name, or we add a `due_date` alias.
4. **Refresh token semantics.** Sanctum has no native refresh tokens; the implemented flow issues a
   second named token (`refresh`) and rotates. It is a valid bearer if misused — acceptable for now,
   but consider scoping it with an ability if refresh tokens must be access-incapable.
5. **`request-token` / `verify-token` purpose.** Implemented as a generic email token (password-reset
   style). If these are meant for *email verification* rather than reset, the downstream "consume token"
   step (e.g. a reset-password endpoint) still needs defining with the mobile team.

## Notes
- Money/provider features added earlier (payments, payouts, OTP, KYC, wallet, WhatsApp) remain **off by
  default** and are not part of the current client contract — see `GO-LIVE-AND-GROWTH.md`.
- The core read-model response shapes (dashboard, pocket detail `list_load`/`pocketSlots`/`invitations`,
  invoices) were **not** modified here; verify them against §3 with a real device against the live DB,
  since those queries are MySQL-specific and untestable on the sqlite test DB.
