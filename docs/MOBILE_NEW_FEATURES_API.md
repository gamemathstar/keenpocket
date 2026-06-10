# Keen Pocket — New Features API (for Mobile)

> **Audience:** Mobile (Android) developer
> **Companion to:** `API_REFERENCE.md` (existing endpoints)
> **Base URL:** `https://keenpocket.quize-e.online/api/`
> **Last updated:** 2026-06-11

This document covers **everything added in the latest backend round**: new auth endpoints, trust
(OTP/KYC/ratings/reputation), discovery (directory), growth (referrals), money (payments/payouts/wallet),
gamification, and multi-channel reminders.

---

## 0. Read this first — feature flags

Several features are **gated by server config and ship OFF**. When a feature is off, its endpoints still
respond `200` with an `enabled: false` body instead of doing anything. **Build the UI to check
`enabled` (or the `*/status` endpoint) and hide/disable the feature when false.** Nothing breaks while a
feature is off.

| Feature | Default | "Is it on?" |
|---------|---------|-------------|
| Referrals, Reputation, Directory, Ratings, Gamification | **ON** | always available |
| OTP phone verification | OFF | `GET /otp/status` → `enabled` |
| Online payments | OFF | `GET /payments/status` → `enabled` |
| Payouts | OFF | `GET /payouts/status` → `enabled` |
| Wallet | OFF | `GET /wallet` → `enabled` |
| KYC | OFF | `GET /kyc/status` → `enabled` |

Conventions are the same as `API_REFERENCE.md`: `Authorization: Bearer <token>` on protected routes,
snake_case fields, money often as strings.

---

## Table of contents
1. [Auth additions](#1-auth-additions)
2. [OTP phone verification](#2-otp-phone-verification)
3. [Referrals & WhatsApp invites](#3-referrals--whatsapp-invites)
4. [Reputation](#4-reputation)
5. [Ratings](#5-ratings)
6. [Gamification (streaks & badges)](#6-gamification-streaks--badges)
7. [Public directory](#7-public-directory)
8. [KYC identity verification](#8-kyc-identity-verification)
9. [Online payments](#9-online-payments)
10. [Wallet](#10-wallet)
11. [Payouts](#11-payouts)
12. [Adashi additions](#12-adashi-additions)
13. [Multi-channel reminders](#13-multi-channel-reminders)
14. [New endpoint index](#14-new-endpoint-index)

---

## 1. Auth additions

### 1.1 `POST /api/refresh-token` (public)
Login now **also returns `refresh_token`**; exchange it here for a fresh pair.

**Body** (`application/x-www-form-urlencoded`): `refresh_token` (string, required)

**Success**
```json
{ "status": 1, "token": "eyJ...new", "refresh_token": "rt_new" }
```
**Failure**
```json
{ "status": 0, "token": "", "message": "Invalid refresh token" }
```

### 1.2 `POST /api/change-password` (auth)
**Body** (form): `old_password`, `new_password`, `password_confirmation`
**Response:** boolean — `true` on success, `false` if the old password is wrong or confirmation mismatches.
```json
true
```

### 1.3 `GET /api/request-token` (public)
Request an email reset/verification code. Always returns `true` (no account enumeration). The code is
delivered out-of-band (email/SMS in prod).

**Query:** `email` (required) → **Response:** `true`

### 1.4 `POST /api/verify-token` (public)
**Body** (form): `token` (the 6-digit code) → **Response:** boolean (valid within 30 minutes).
```json
true
```

> **Note:** `POST /api/login` and `POST /api/register` are unchanged except login now includes
> `refresh_token`, and register echoes `message: null` + `keens: []` on success.

---

## 2. OTP phone verification

Gated by `OTP`. When off, `request`/`verify` are no-ops and registration is **not** gated.

### 2.1 `GET /api/otp/status` (public)
```json
{ "enabled": false }
```

### 2.2 `POST /api/otp/request` (public)
**Body** (form): `phone_number` (required), `purpose` (optional: `verify|login|reset`)

**Enabled** → `200`
```json
{ "message": "Verification code sent.", "sent": true, "expires_in": 600 }
```
Cooldown → `429` `{ "message": "Please wait before requesting another code.", "retry_after": 47 }`
Disabled → `200` `{ "enabled": false, "message": "OTP verification is currently disabled." }`

### 2.3 `POST /api/otp/verify` (public)
**Body** (form): `phone_number`, `code`, `purpose?`
```json
{ "verified": true }
```
`422` with `{ "verified": false }` on a bad/expired code. When OTP is enabled, the user must verify
**before** `POST /api/register` (otherwise register returns `422 "Please verify your phone number first."`).

---

## 3. Referrals & WhatsApp invites

**ON by default.** `POST /api/register` now accepts an optional `referral_code` field.

### 3.1 `GET /api/referrals/me` (auth)
```json
{
  "enabled": true,
  "code": "K7P2QSV",
  "invite_link": "https://keenpocket.quize-e.online/invite?ref=K7P2QSV",
  "whatsapp_url": "https://wa.me/?text=Join%20me%20on%20KeenPocket...%20https%3A%2F%2F...%3Fref%3DK7P2QSV",
  "stats": { "invited": 4, "qualified": 2, "rewarded": 0 }
}
```
Open `whatsapp_url` directly to share. A referral becomes **qualified** when the invitee joins their
first pocket/adashi.

### 3.2 `GET /api/referrals` (auth)
```json
{
  "referrals": [
    { "id": 9, "status": "qualified", "created_at": "2026-06-10T09:00:00Z", "name": "Aisha", "phone_number": "0803*****21" }
  ]
}
```

---

## 4. Reputation

**ON.** Computed from activity + ratings; surface it before joining a group.

### 4.1 `GET /api/reputation/me` (auth) · 4.2 `GET /api/users/{id}/reputation` (auth)
```json
{
  "user": { "id": 3, "name": "Sylux Endyusa Dimitri" },
  "reputation": {
    "score": 72,
    "band": "Silver",
    "payment_reliability": 90,
    "invoices_total": 10,
    "invoices_paid": 9,
    "pockets_joined": 2,
    "adashis_joined": 1,
    "cycles_completed": 1,
    "rating_average": 4.6,
    "rating_count": 5
  }
}
```
`band` ∈ `New | Building | Bronze | Silver | Gold`. `payment_reliability` / `rating_average` are `null`
when there's no data yet.

---

## 5. Ratings

**ON.** A member rates the **organizer** of a pocket/adashi (1–5★). One rating per member per group
(re-submitting updates it).

### 5.1 `POST /api/ratings` (auth)
**Body** (`application/json`)
```json
{ "context_type": "pocket", "context_id": 1, "stars": 5, "comment": "Great organizer" }
```
`context_type` ∈ `pocket | adashi`. **Success** `200`:
```json
{ "message": "Rating saved.", "rating": { "id": 12, "stars": 5, "context_type": "pocket", "context_id": 1 } }
```
Errors: `403` (not a member), `422` (rating yourself / invalid context / stars out of 1–5).

### 5.2 `GET /api/users/{id}/ratings` (auth)
```json
{
  "summary": { "average": 4.6, "count": 5 },
  "ratings": [ { "id": 12, "stars": 5, "comment": "Great organizer", "context_type": "pocket", "created_at": "2026-06-10T09:00:00Z", "rater": "Aisha" } ]
}
```

---

## 6. Gamification (streaks & badges)

**ON.** Computed from existing activity.

### 6.1 `GET /api/gamification/me` (auth)
```json
{
  "streak": 3,
  "total_contributed": 45000,
  "badges": [
    { "slug": "first_pocket", "label": "First Pocket", "description": "Joined your first pocket", "earned": true },
    { "slug": "reliable_payer", "label": "Reliable Payer", "description": "Consistently pays on time", "earned": true },
    { "slug": "big_saver", "label": "Big Saver", "description": "Contributed a significant amount", "earned": false }
  ],
  "metrics": { "pockets_joined": 2, "adashis_joined": 1, "cycles_completed": 1, "payment_reliability": 90, "rating_average": 4.6, "referrals_qualified": 2, "kyc_verified": false, "total_contributed": 45000, "payment_streak": 3 }
}
```
`streak` = consecutive paid invoices (most recent backwards). Every badge is returned with an `earned`
flag so you can render locked + unlocked. Badge slugs: `first_pocket`, `adashi_member`,
`reliable_payer`, `cycle_champion`, `top_organizer`, `recruiter`, `verified`, `big_saver`.

### 6.2 `GET /api/users/{id}/badges` (auth)
Earned badges only (for public profiles).
```json
{ "badges": [ { "slug": "first_pocket", "label": "First Pocket", "description": "Joined your first pocket", "earned": true } ] }
```

---

## 7. Public directory

**ON.** Browse joinable groups. Both endpoints are **Laravel-paginated** (`data`, `links`, `meta`).
When KYC is enabled, only verified organizers appear.

### 7.1 `GET /api/directory/pockets?q=&page=` (auth)
Open (not invitation-only) pockets with slots available.
```json
{
  "data": [
    {
      "id": 5, "title": "Office Savings 2026", "pocket_type": "Monthly", "description": "...",
      "amount_per_hand": 10000, "max_keens": 20, "year": 2026, "start_month": 1, "month_count": 12,
      "organizer": "Pocket Admin", "slots_used": 8, "slots_available": 12,
      "organizer_id": 3, "organizer_phone": "080*****567"
    }
  ],
  "current_page": 1, "last_page": 1, "per_page": 20, "total": 1
}
```
`slots_available` is `null` when the pocket is uncapped (`max_keens = 0`). Fetch the organizer's
reputation via [§4.2](#4-reputation).

### 7.2 `GET /api/directory/adashi?q=&page=` (auth)
Public, active adashis.
```json
{
  "data": [
    { "id": 1, "name": "Family Adashi", "amount_per_cycle": 50000, "cycle_duration_days": 30,
      "total_members": 3, "current_cycle_number": 2, "rotation_mode": "MANUAL",
      "admin": "Sylux Endyusa Dimitri", "admin_id_ref": 3, "admin_phone": "090*****321" }
  ],
  "current_page": 1, "last_page": 1, "per_page": 20, "total": 1
}
```

---

## 8. KYC identity verification

Gated by `KYC` (OFF). Raw BVN/NIN is never stored — only last 4 digits.

### 8.1 `GET /api/kyc/status` (auth)
```json
{ "enabled": false, "status": "none", "type": null, "id_last4": null, "verified_at": null }
```
`status` ∈ `none | pending | verified | failed`.

### 8.2 `POST /api/kyc/submit` (auth)
**Body** (form): `type` (`BVN|NIN`), `id_number`
```json
{ "status": "verified", "verified": true }
```
`422` `{ "verified": false }` on failure. Disabled → `{ "enabled": false }`.

---

## 9. Online payments

Gated by `PAYMENTS` (OFF). Card/transfer collection for invoices. The `webhook` route is
server-to-server (gateway → backend) — **not called by the app**.

### 9.1 `GET /api/payments/status` (auth)
```json
{ "enabled": false, "provider": "log", "currency": "NGN" }
```

### 9.2 `POST /api/payments/initialize` (auth)
**Body** (form): `invoice_id`. Returns a checkout URL to open in a browser/webview.
```json
{ "message": "Payment initialized.", "reference": "KP_10_3f9a1b", "authorization_url": "https://checkout.paystack.com/xyz" }
```
Disabled → `{ "enabled": false, "message": "Online payments are currently disabled." }`
Errors: `404` invalid invoice, `422` already paid, `403` not your invoice.

### 9.3 `GET /api/payments/verify?reference=` (auth)
Call after returning from checkout (the webhook is the authoritative backstop).
```json
{ "paid": true }
```
`422` `{ "paid": false }` if not yet confirmed.

---

## 10. Wallet

Gated by `WALLET` (OFF). Members fund a balance and pay contributions from it.

### 10.1 `GET /api/wallet` (auth)
```json
{ "enabled": true, "balance": 7500, "currency": "NGN" }
```
Disabled → `{ "enabled": false }`.

### 10.2 `GET /api/wallet/history` (auth)
Laravel-paginated ledger:
```json
{ "data": [ { "id": 2, "type": "debit", "amount": 2500, "balance_after": 7500, "reason": "contribution", "created_at": "2026-06-11T09:00:00Z" } ], "total": 2 }
```

### 10.3 `POST /api/wallet/topup` (auth)
**Body** (form): `amount` (integer). In production this returns a gateway step; in the current build
(dev provider) it credits immediately.
```json
{ "message": "Wallet funded.", "balance": 10000 }
```

### 10.4 `POST /api/wallet/pay-invoice` (auth)
**Body** (form): `invoice_id`. Debits the wallet and marks the invoice paid atomically.
```json
{ "message": "Invoice paid from wallet.", "balance": 7500 }
```
Errors: `422` insufficient balance (invoice stays unpaid) / already paid, `403` not your invoice, `404`.

---

## 11. Payouts

Gated by `PAYOUTS` (OFF). Disburses a collected Adashi pot to the receiver's bank. Mostly automatic; the
app's role is collecting the member's **bank details** and (for the admin) triggering a manual payout.

### 11.1 `GET /api/payouts/status` (auth)
```json
{ "enabled": false, "provider": "log", "currency": "NGN" }
```

### 11.2 `POST /api/payouts/bank-account` (auth)
Save the member's payout destination.
**Body** (form): `bank_name`, `bank_code`, `account_number`
```json
{ "message": "Payout account saved." }
```

### 11.3 `POST /api/adashi/{id}/payout` (auth, admin only)
Trigger a payout for the latest closed cycle (MANUAL adashis).
```json
{ "message": "Payout success", "payout": { "id": 1, "amount": 150000, "status": "success" } }
```

---

## 12. Adashi additions

### 12.1 `POST /api/adashi/{id}/auto-rotate` (auth)
Reconcile the current cycle and, if fully collected, close + rotate.
```json
{ "success": true, "message": "Payments reconciled", "record": { "id": 11, "status": "COLLECTING" }, "remaining": 1 }
```

### 12.2 `POST /api/adashi/{id}/visibility` (auth, admin only)
Toggle whether the adashi appears in the public directory.
**Body** (`application/json`): `{ "is_public": true }`
```json
{ "success": true, "is_public": true }
```

### 12.3 `POST /api/adashi` — new optional field
`POST /api/adashi` now accepts an optional **`is_public`** (boolean, default `false`). Also,
`rotation_mode` now accepts lowercase `manual`/`auto` (in addition to `AUTO`/`MANUAL`).

> **Contract note:** member flags `has_received` / `is_active` are returned as integers `0/1` (as your
> deserializer expects). `status` / `rotation_mode` are returned uppercase (`ACTIVE`, `MANUAL`) — compare
> case-insensitively client-side, or let us know and we'll lowercase them in responses.

---

## 13. Multi-channel reminders

No new endpoint — behavioral. Payment reminders (Adashi due/overdue + pocket unpaid-invoice nudges) can
now be delivered over **push + SMS + WhatsApp** (SMS/WhatsApp are server-gated and OFF by default). The
app keeps registering its FCM token via `POST /api/push/notification/update` as before; nothing to change.

---

## 14. New endpoint index

| Method | Path | Auth | Gated |
|--------|------|------|-------|
| POST | `/api/refresh-token` | no | — |
| POST | `/api/change-password` | yes | — |
| GET | `/api/request-token` | no | — |
| POST | `/api/verify-token` | no | — |
| GET | `/api/otp/status` | no | OTP |
| POST | `/api/otp/request` | no | OTP |
| POST | `/api/otp/verify` | no | OTP |
| GET | `/api/referrals/me` | yes | — |
| GET | `/api/referrals` | yes | — |
| GET | `/api/reputation/me` | yes | — |
| GET | `/api/users/{id}/reputation` | yes | — |
| POST | `/api/ratings` | yes | — |
| GET | `/api/users/{id}/ratings` | yes | — |
| GET | `/api/gamification/me` | yes | — |
| GET | `/api/users/{id}/badges` | yes | — |
| GET | `/api/directory/pockets` | yes | — |
| GET | `/api/directory/adashi` | yes | — |
| GET | `/api/kyc/status` | yes | KYC |
| POST | `/api/kyc/submit` | yes | KYC |
| GET | `/api/payments/status` | yes | Payments |
| POST | `/api/payments/initialize` | yes | Payments |
| GET | `/api/payments/verify` | yes | Payments |
| GET | `/api/wallet` | yes | Wallet |
| GET | `/api/wallet/history` | yes | Wallet |
| POST | `/api/wallet/topup` | yes | Wallet |
| POST | `/api/wallet/pay-invoice` | yes | Wallet |
| GET | `/api/payouts/status` | yes | Payouts |
| POST | `/api/payouts/bank-account` | yes | Payouts |
| POST | `/api/adashi/{id}/payout` | yes | Payouts |
| POST | `/api/adashi/{id}/auto-rotate` | yes | — |
| POST | `/api/adashi/{id}/visibility` | yes | — |

*Webhook routes (`/api/payments/webhook/{provider}`, `/api/payouts/webhook/{provider}`) are
server-to-server only and not called by the app.*
