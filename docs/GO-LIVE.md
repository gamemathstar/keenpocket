# KeenPocket — Go-Live Runbook

A checklist for taking KeenPocket to production. Work top-to-bottom. Items marked
**(once)** are one-time; the rest are part of each deploy.

---

## 0. Before anything — back up
```bash
# Snapshot the live DB first.
mysqldump -u <user> -p keenpockets > backup-$(date +%F).sql
```

## 1. Apply pending migrations  **(required — features won't work without this)**
All recent feature tables/columns are additive and guarded (they no-op on tables
that already exist), so this is safe to run against the live DB.
```bash
php artisan migrate            # review the list, then confirm in production
```
This adds: charity, plans, account details, guarantor, visibility flags, bank
accounts, messages, disputes, group rules, the **school module**, and the
**Keens economy** (`users.keens`, `keen_transactions`, `settings`).

Verify:
```bash
php artisan migrate:status     # everything shows "Ran"
```

## 2. Rotate the leaked secrets  **(once — security critical)**
Credentials were committed to git history early on. Removing the files does NOT
remove them from history, so **rotate every one** before exposing the app:
- `APP_KEY` → `php artisan key:generate` (note: invalidates existing encrypted data/sessions)
- **Database** password (and create a fresh DB user)
- **Mail / SMTP** credentials
- **Pusher / broadcasting** keys (if used)
- **Firebase / FCM** server key
- **AWS** keys (if used)
- Any **OAuth** client secrets
Then confirm none of these live in the repo and that `.env` is git-ignored.
(Optional but ideal: purge the secrets from git history with `git filter-repo`.)

## 3. Storage symlink  **(once per server)**
For uploaded school logos/backgrounds and profile photos to display:
```bash
php artisan storage:link
```

## 4. Production config
- `.env`: `APP_ENV=production`, `APP_DEBUG=false`, correct `APP_URL`, mail/SMS drivers.
- **Mail must work** — "Forgot password" emails the reset link, so set real SMTP
  (`MAIL_MAILER`, host, port, credentials, `MAIL_FROM_ADDRESS`). With the `log`
  driver the link only lands in `storage/logs`. Reset works only for accounts with
  a real email (registered users, not unclaimed phone placeholders).
- Money/provider features stay **off** unless you've wired real providers
  (`PAYMENTS_ENABLED`, `WALLET_ENABLED`, `OTP_ENABLED`, `PAYOUTS_ENABLED`, etc. = false).
- Keens: charging is **off by default**. Turn it on at **Super Admin → Keens**
  (defaults: Pocket 50 · Adashi 50 · School 100). Super admins create free.
- School super admins: set `SCHOOL_SUPER_ADMINS="you@example.com,..."` (or flip
  `users.is_super_admin`).

## 5. Caches & assets
```bash
composer install --no-dev --optimize-autoloader
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart   # if running queues
```

## 6. Smoke test
- Log in (phone / email / username).
- Create a pocket and an adashi; record + approve a contribution.
- Open a group as a non-member → confirm the limited public view (no member list).
- Super Admin → enable Keens, grant Keens to a test user, create as that user → balance drops.
- `/terms` and `/privacy` load.
- "Forgot password?" on login → request a link → reset → sign in with the new password.

---

## Still-operational / external (not code)
- **Legal**: have a Nigerian lawyer review the Terms & Privacy text and register the
  **trademark** (name, logo, mascot). The mascot image rights are fine (Gemini-generated, used per Google's terms).
- **Reconcile** the reconstructed core-table migrations against a real `schema:dump`.

## Known follow-ups (optional, not blocking)
- **Mobile API parity**: the web gained privacy gating, charity, chat, disputes,
  school and Keens — the mobile API still returns the older shapes. Mirroring these
  is a coordinated app release (changing existing responses would break older app versions).
- **Real-time chat**: currently 5-second polling; WebSockets (Reverb/Pusher) for instant delivery.
- **USSD** and **multi-language (Hausa/Yoruba/Igbo/Pidgin)**: each its own effort (telco integration / professional translation).

## Test suite
```bash
php vendor/bin/phpunit      # currently green
```
