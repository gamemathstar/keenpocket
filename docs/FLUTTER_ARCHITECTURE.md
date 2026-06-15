# KeenPocket Mobile — Flutter Architecture Blueprint

> **Status:** Official architecture reference for the KeenPocket native mobile app (iOS + Android, phone + tablet).
> **Audience:** Every engineer on the mobile team. A new joiner should be able to read this and understand the structure, the boundaries, the standards, and *why* each decision was made.
> **Scope:** This is a production, multi-year, multi-developer codebase — not a prototype. Architecture is optimised for scalability, testability, low inter-feature coupling, and high reuse.
>
> **Grounding.** Every recommendation below is derived from the real KeenPocket system: the Laravel backend (`routes/api.php`, 61 REST endpoints under `{{base_url}}/api`, token auth, FCM push, and a large set of env feature flags), the domain models (Pocket, Adashi, Invoice, Wallet, Payout, …), and the reorganised `ui-designs/` design library (phase folders A–Q). Where the backend API does **not yet** expose a capability that the designs show, it is flagged explicitly as a backend dependency rather than silently assumed.

---

## Table of Contents

1. [Phase 1 — Product Analysis](#phase-1--product-analysis)
2. [Phase 2 — Feature Identification](#phase-2--feature-identification)
3. [Phase 3 — Project Structure](#phase-3--project-structure)
4. [Phase 4 — Feature Module Structure](#phase-4--feature-module-structure)
5. [Phase 5 — Shared / Core Architecture](#phase-5--shared--core-architecture)
6. [Phase 6 — Technology Decisions](#phase-6--technology-decisions)
7. [Phase 7 — Coding Standards](#phase-7--coding-standards)
8. [Phase 8 — Development Guidelines](#phase-8--development-guidelines)
9. [Appendix — API ↔ Feature Map & Backend Gaps](#appendix--api--feature-map--backend-gaps)

---

# PHASE 1 — PRODUCT ANALYSIS

## 1.1 What KeenPocket is

A **gamified Nigerian group-savings platform** (₦/NGN). It is primarily a **record-keeping, coordination, and trust layer** — by default it does **not custody money** (the wallet/payment/payout rails ship OFF behind flags). Two flagship savings products anchor the domain:

- **Pocket** — an organiser-run monthly savings box. Members buy "hands" and contribute monthly; the organiser verifies payments and distributes the pool per group rules.
- **Adashi** — a rotating savings circle (*esusu/ajo*). Everyone pays a fixed amount per cycle; one member receives the full pot each cycle in a fixed rotation order until all have received.

Surrounding these: contributions/invoices, charity drives, group shopping, standalone grocery plans, a trust layer (guarantors, KYC, ratings, reputation), in-group chat & disputes, wallet/payouts/bank accounts, notifications, discovery/search/insights, gamification/leaderboard/referrals, profile/settings, a school-fee module, and an administration layer.

## 1.2 Core business domains (data-ownership boundaries)

| Domain | Owns (entities) | Backend source of truth |
|---|---|---|
| **Identity & Session** | User, auth tokens, OTP, KYC status, notification prefs | `auth*`, `otp/*`, `kyc/*` |
| **Savings — Pocket** | Pocket, PocketSlot, PocketGuarantor, ShoppingItem, rules, bank/collection | `pocket*`, `my-pockets`, `create/pocket` |
| **Savings — Adashi** | Adashi, AdashiMember, AdashiRecord (cycle), rotation, overrides | `adashi/*` |
| **Contribution Ledger** | Invoice, InvoiceItem (Paid/Donation), verification state | `invoice*`, `adashi/{id}/contribute` |
| **Charity** | CharityProject, CharityGoalItem, donations | `pocket/charity*` |
| **Money** | Wallet, WalletTransaction, BankAccount, Payout, Payment | `wallet/*`, `payouts/*`, `payments/*` |
| **Trust & Reputation** | Rating, reputation score/band, guarantor vouches | `ratings`, `*/reputation`, `directory/*` |
| **Engagement** | Streaks, badges, Keens coin, leaderboard, referrals | `gamification/*`, `referrals*` |
| **Communication** | Notification, Message (chat), Dispute | `notifications`, `feed/*` (chat/disputes are web-only today) |
| **Planning** | Plan, PlanItem, collaborators | web-only today |
| **School** | School, SchoolClass, FeeItem, Student, PaymentPlan, SchoolPayment | web-only today |
| **Administration** | super-admin grants, Keens economy, collection-health metrics | web-only today |

## 1.3 User roles & permission model (cross-cutting)

Authorization is **per-resource, role-derived** — not a global RBAC table:

- **Global capabilities** on the `User`: `is_super_admin`, `can_create_school`, Keens balance, KYC status.
- **Per-Pocket role** derived at runtime: *organiser* (`pocket.user_id == me`) vs *active member* (slot status 1) vs *pending member* (slot 0) vs *guest*.
- **Per-Adashi role**: *admin* (`adashi.admin_id == me`) vs *active member* (`is_active`) vs *inactive* vs *guest*.

**Architectural consequence:** authorization is a **domain concern** (pure functions on entities like `pocket.roleOf(userId)`), surfaced to the UI as an `AccessPolicy`. It must never be re-implemented ad-hoc inside widgets. Every screen has a **permission-restricted state**.

## 1.4 Cross-cutting concerns (observed in the system)

- **Feature flags everywhere** — `OTP_ENABLED`, `PAYMENTS_ENABLED`, `PAYOUTS_ENABLED`, `KYC_ENABLED`, `WALLET_ENABLED`, `SMS_ENABLED`, `WHATSAPP_ENABLED`, `REFERRALS_ENABLED`, `REFERRAL_REWARD_ENABLED`, `KYC_GATE_DIRECTORY`, plus server-toggled `gamification`, `charity`, `chat`, `school`. Many ship OFF. The app must degrade to a **"coming soon"** state, not break. Status is discoverable via `payments/status`, `payouts/status`, `kyc/status`, `otp/status`, and `enabled` fields in `gamification/me`, `wallet`.
- **Money confidence** — verified vs pending distinction on every amount; ₦ formatting; "records, not custody" messaging.
- **Offline tolerance** — flaky mobile networks; contributions, chat, and plan toggles benefit from optimistic UI + queued sync.
- **Trust signalling** — reputation band, KYC ✓, ratings appear on every join surface.
- **Gamification** — streaks/badges/Keens/leaderboard touch many features (engagement loop).
- **Push (FCM)** — backend already stores `fcm_token`; notifications deep-link to a pocket/adashi/cycle.

## 1.5 Navigation structure (from the design library)

- **Phone:** 5-tab bottom nav — 🏠 Home · 👛 Pocket (hub → Pockets + Adashi) · 🛒 Shopping (Plans) · 🧭 Discover · ⭐ Profile. Everything else nests under Profile or contextual entry points.
- **Tablet/iPad:** persistent left **NavigationRail** + **master-detail** two-pane layouts.
- **Deep links:** pocket, adashi, cycle, invite (`/invite?ref=CODE`), user profile, notification target.

## 1.6 Reusable components (from the design system docs)

A single design language: brand `#1cb0f6`, **Nunito**, "candy" 3D buttons, chunky cards (7px bottom border), soft brand-tinted shadows, emoji-accented icons, **Mr K** mascot, full dark mode. Reusable atoms recur across nearly every screen: stat tile, progress ring/bar, photo-card, avatar, badge/pill, empty-state (Mr K), toast, chat bubble, skeletons, rotation stepper, share card. → These belong in a **shared design system package**, never duplicated per feature.

## 1.7 Complex business processes (the architecture must isolate & test these)

1. **Adashi rotation engine** — contribute → verify → all-paid detection → payout to receiver → advance cycle → COMPLETED; AUTO vs MANUAL modes; admin overrides (set receiver, pause, deactivate, set position). High-value, high-risk → dedicated use cases + exhaustive unit tests.
2. **Contribution + verification ledger** — partial invoices, month allocation, wallet payment, organiser verification; shared by Pocket and Adashi.
3. **Guarantor gate** — join blocked until a named guarantor recommends.
4. **Keens economy** — creation costs deduct coins (when enabled).
5. **Reputation computation** — derived from reliability + cycles + ratings (mostly server-side; client renders).

> **Summary.** KeenPocket is a multi-domain financial-coordination product with two core savings engines, a shared contribution ledger, a strong trust layer, and pervasive feature-flagging. The architecture must (a) isolate the two savings engines yet share the contribution ledger and group-collaboration concerns, (b) make per-resource authorization a first-class, testable domain concern, (c) treat feature flags and offline as cross-cutting infrastructure, and (d) centralise the rich design system.

---

# PHASE 2 — FEATURE IDENTIFICATION

Features map to **business capabilities and domain boundaries**, not screens. The reorganised `ui-designs/` phase folders (A–Q) are a strong starting signal but are deliberately refined: some are merged (a shared contribution capability), some split (trust into KYC vs reputation vs vouching), and shared cross-cutting concerns (group collaboration) are extracted so both savings engines reuse them.

**Module legend:** `app` = composition root · `core`/`design_system`/`data_layer` = shared packages · everything else = a feature under `lib/features/<feature>`.

### Feature: `auth`
- **Business purpose:** Get a trusted, authenticated session and onboard new users.
- **Responsibilities:** Splash routing, onboarding carousel, login (email/phone/username), registration, OTP/MFA, password reset, token lifecycle (issue/refresh/revoke), logout.
- **Screens:** Splash, Onboarding, Login, Register, OTP verification, Forgot/Reset (ui-designs **B**).
- **Workflows:** Sign-up → (OTP) → session; login; token refresh on 401; deep-linked invite captures referral code.
- **Dependencies:** `core` (secure storage, network), `session` (writes the authenticated user).
- **Shared resources:** `SessionManager`, `SecureTokenStore`, `FeatureFlagService` (OTP flag).
- **Justification:** Authentication is its own bounded capability with a distinct lifecycle (tokens) and security surface. It must not be coupled to profile/settings even though both touch `User` — auth owns *credentials & session*, profile owns *identity presentation*.

### Feature: `session` (foundational, near-core)
- **Business purpose:** Single source of truth for "who is logged in" + global capabilities + feature flags hydrated from the backend.
- **Responsibilities:** Current `SessionUser` (id, name, avatar, Keens, KYC status, `is_super_admin`, `can_create_school`, notification prefs), capability checks, feature-flag snapshot, auth-state stream that the router listens to.
- **Screens:** none (cross-cutting).
- **Dependencies:** `core` only.
- **Justification:** Many features read the current user and flags; routing guards depend on auth state. Centralising prevents every feature importing `auth`. Sits between `core` and features. (Implementation may live in `core/session` — see Phase 3.)

### Feature: `dashboard`
- **Business purpose:** At-a-glance savings cockpit aggregating across domains.
- **Responsibilities:** Greeting + total saved, stat tiles, weekly goal/streak, contribution trend chart, badges, quick Pocket/Adashi lists.
- **Screens:** Home dashboard (ui-designs **C**).
- **Dependencies (read-only):** `pockets`, `adashi`, `reputation`, `wallet`, `gamification`, `contributions` — via their **domain repositories/use cases only**, never their BLoCs or widgets.
- **Justification:** It is an *aggregation* capability with its own caching/refresh policy. It depends on others but is depended on by none → keep it a leaf that composes domain use cases. Do **not** let other features import `dashboard`.

### Feature: `pockets`
- **Business purpose:** Create, discover-join, run, and participate in monthly savings pockets.
- **Responsibilities:** Pocket CRUD, slots/members, hands, guarantor toggle, visibility, rules, bank/collection details, cloning, CSV export, embedded pocket shopping suggestions.
- **Screens:** Pockets list, Create, Pocket detail hub, Public pocket view, Manage members (ui-designs **D**) + pocket shopping subset of **H**.
- **Workflows:** Create pocket; request-to-join (+ guarantor); accept/decline; manage; clone.
- **Dependencies:** `contributions` (shared ledger), `group_collaboration` (chat/disputes), `trust` (guarantor/ratings/KYC badge), `money` (payout account selector). `core`, `design_system`.
- **Justification:** A self-contained savings engine with deep admin tooling. Separate from Adashi because the contribution/payout *mechanics differ fundamentally* (pool-and-distribute vs rotate). Pocket shopping lives **inside** pockets (it is a pocket sub-aggregate), not in `plans`.

### Feature: `adashi`
- **Business purpose:** Run and participate in rotating savings circles.
- **Responsibilities:** Adashi CRUD, members & positions, the **rotation engine** (AUTO/MANUAL), cycle records, admin overrides, visibility, bank, clone, export.
- **Screens:** Adashi list, Create, Adashi detail hub (rotation timeline, current cycle), Members & admin controls, Public adashi view (ui-designs **E**), plus the discover-adashi screen (`explore_adashi`).
- **Workflows:** Create/join → position → contribute → verify → rotate/pay-out → complete.
- **Dependencies:** `contributions`, `group_collaboration`, `trust`, `money` (payouts), `core`, `design_system`.
- **Justification:** The rotation engine is the most complex, highest-risk business process in the product and deserves an isolated, heavily tested module. Merging with `pockets` would create a god-feature with two incompatible state machines.

### Feature: `contributions` (shared capability)
- **Business purpose:** The single contribution & invoice ledger used by **both** Pocket and Adashi.
- **Responsibilities:** Contribute (amount), month allocation/preview, submit, pay-from-wallet, invoice history, organiser verify/decline & mark-paid, paid/pending states.
- **Screens:** Contribute, Allocate months, Pay from wallet, Invoice history (ui-designs **F**).
- **Dependencies:** `money` (wallet pay), `core`, `design_system`. Consumes a `ContributionContext` (pocket-slot or adashi-cycle) abstraction so it is host-agnostic.
- **Justification:** Invoices/InvoiceItems are identical in shape across both engines; duplicating this in `pockets` and `adashi` would violate DRY and split a critical ledger. Extracting it keeps both engines thin and the verification rules in one place. **Pockets and Adashi depend on `contributions`, not the reverse.**

### Feature: `charity`
- **Business purpose:** Pocket-scoped Sadaqah/charity drives.
- **Responsibilities:** Drive setup (amount/items goal), donations, donor visibility, progress.
- **Screens:** Charity setup, Donate card (ui-designs **G**).
- **Dependencies:** `pockets` (host context), `contributions` (donations are Donation-type invoice items), `core`, `design_system`.
- **Justification:** Optional, flag-gated, with its own goal model (CharityProject/GoalItem). Kept separate so the Pocket core stays lean and charity can evolve (or be disabled) independently.

### Feature: `plans` (standalone shopping/grocery)
- **Business purpose:** Collaborative monthly/yearly grocery & budget planning (the 🛒 Shopping tab) — distinct from pocket shopping.
- **Responsibilities:** Plan CRUD, items (status/claim/priority), budget tracking, collaborators, archive, carry-over.
- **Screens:** Plans list, Create plan, Plan detail (ui-designs **H**, standalone subset).
- **Dependencies:** `core`, `design_system`, `contacts/sharing` util.
- **Justification:** A different domain (personal/household budgeting), different persona, no money rails, top-level tab. Must not be conflated with pocket shopping (group-buying inside a savings pocket). **Backend gap: no API yet.**

### Feature: `trust` (composite: reputation + ratings + kyc + vouching)
- **Business purpose:** Establish whether strangers can safely pool money.
- **Sub-modules (folders, one feature package):** `reputation` (score/band/stats), `ratings` (rate organiser, ratings received), `kyc` (BVN/NIN verification), `vouching` (guarantor inbox & gate).
- **Screens:** Vouches inbox, KYC verification, Rate organiser, Reputation/trust score (ui-designs **I**).
- **Dependencies:** `session`, `core`, `design_system`. `pockets`/`adashi` depend on `trust` for badges & the guarantor gate.
- **Justification:** These four are one coherent *trust* domain that is consumed the same way by many surfaces (a reputation chip, a KYC badge, a rate button). Grouping them as one feature with internal sub-modules avoids four micro-features while keeping them cohesive. They are split *internally* because each has its own datasource and lifecycle. Kept out of `profile` because trust is consumed app-wide (discover, pocket/adashi headers), not only on the profile screen.

### Feature: `group_collaboration` (shared: chat + disputes)
- **Business purpose:** In-group communication and conflict resolution, attached to **both** Pocket and Adashi.
- **Responsibilities:** Group chat (poll/stream), disputes (raise/resolve/dismiss), member-only access policy.
- **Screens:** Group chat panel, Disputes hub (ui-designs **J**).
- **Dependencies:** `core`, `design_system`, `session`. Consumes a `GroupContext` (pocket|adashi).
- **Justification:** Chat and disputes are cross-cutting features bound to a group context, identical for both engines. Extracting them prevents duplication and lets both savings features compose the same collaboration surface. **Backend gap: chat/disputes are web-only today.**

### Feature: `money` (wallet + payouts + bank accounts + payments)
- **Business purpose:** The (flag-gated) money-movement domain.
- **Responsibilities:** Wallet balance & transactions & top-up, payout account & received payouts, bank-account management, payment-gateway initialisation/verification.
- **Screens:** Wallet home, Payouts & bank hub (ui-designs **K**); pay-from-wallet UI is owned by `contributions` but uses `money` use cases.
- **Dependencies:** `core`, `design_system`, `session`, `FeatureFlagService`.
- **Justification:** One bounded "money" domain (BankAccount is reused by payouts and pocket collection). Most of it ships OFF → must encapsulate the coming-soon/flag logic in one place. Kept separate so the savings engines depend only on small money *use cases* (e.g. `PayInvoiceFromWallet`).

### Feature: `notifications`
- **Business purpose:** Central event inbox + push handling.
- **Responsibilities:** Inbox list (all/unread), mark read/all, deep-link open, FCM token registration & foreground/background handling, local notification display.
- **Screens:** Notifications inbox (ui-designs **L**).
- **Dependencies:** `core` (push service), `session`, router (deep links).
- **Justification:** Notification delivery + inbox is a distinct capability touching every domain via deep links; centralising FCM wiring avoids scattering push logic.

### Feature: `discovery` (discover + search + insights)
- **Business purpose:** Find joinable groups and review personal savings reports.
- **Responsibilities:** Discover open pockets/public adashi, search (own + discoverable), KYC-gated directory, Insights "year in review".
- **Screens:** Discover, Search, Insights (ui-designs **M**).
- **Dependencies:** `pockets`/`adashi` domain use cases (read), `trust` (badges), `core`, `design_system`.
- **Justification:** Discovery is a read-mostly capability with its own ranking/filtering and a directory KYC gate; Insights is reporting over the contribution ledger. Grouped because they share the "aggregate-and-browse" nature and none is large alone. (If Insights grows charts/exports, promote it to its own feature — see Phase 8 triggers.)

### Feature: `gamification` (gamification + leaderboard + referrals)
- **Business purpose:** Drive retention and growth.
- **Responsibilities:** Streaks/freezes, badges, Keens balance surfacing, leaderboard, referral link/code/share & tracking.
- **Screens:** Leaderboard, Badges/achievements, Refer & earn (ui-designs **N**).
- **Dependencies:** `session`, `core`, `design_system`, share/deep-link util.
- **Justification:** The engagement/growth loop is a cohesive capability (all read-mostly, flag-gated, motivational). Referrals could later split out if rewards/economics expand; for now they share the growth concern.

### Feature: `profile` (profile + settings)
- **Business purpose:** Identity presentation and account management.
- **Responsibilities:** Own profile (identity + reputation + badges + ratings), public profile, settings (avatar, notification prefs, password, bank accounts entry, dark mode, logout).
- **Screens:** My profile, Public profile, Settings (ui-designs **O**).
- **Dependencies:** `trust` (reputation/ratings/KYC), `money` (bank accounts), `gamification` (badges), `session`, `core`, `design_system`.
- **Justification:** Profile *renders* trust/engagement data it does not own; settings mutates account-level fields. Distinct from `auth` (credentials) and `trust` (which owns the data).

### Feature: `school`
- **Business purpose:** Termly school-fee management (proprietors) + fee visibility (parents).
- **Responsibilities:** School/classes/fee-items/students/payment-plans/payments; parent "My Children".
- **Screens:** Create school, School dashboard, My Children (ui-designs **P**).
- **Dependencies:** `session` (`can_create_school`), `core`, `design_system`.
- **Justification:** A near-separate B2B sub-product with a different persona and data model; cleanly isolated and flag-gated so it can be shipped, hidden, or even extracted to a sibling app. **Backend gap: web-only today.**

### Feature: `administration` (super admin + admin health)
- **Business purpose:** Platform operation and organiser collection-health reporting.
- **Responsibilities:** Super-admin grants/revokes, Keens-economy config; organiser KPI/health dashboards.
- **Screens:** Super admin console, Admin health (ui-designs **Q**).
- **Dependencies:** `session` (`is_super_admin` / organiser), `pockets`/`adashi` (read metrics), `core`, `design_system`.
- **Justification:** Privileged, role-gated tooling that must be isolated for security and because it is irrelevant to most users; bundling it into normal features would leak admin concerns everywhere.

**Features intentionally NOT created:** "invoices" as a separate thing from contributions (same aggregate); "members" as a feature (a sub-aggregate of pockets/adashi); one feature per screen (rejected — violates the capability principle). Pocket-shopping and standalone-plans kept distinct despite similar UI because they are different domains.

---

# PHASE 3 — PROJECT STRUCTURE

## 3.1 Top-level layout

```
keenpocket_mobile/
├── android/ · ios/ · web/ · macos/        # platform runners (web/desktop optional)
├── lib/                                    # all Dart source (see 3.2)
├── packages/                               # in-repo shared packages (enforced boundaries)
│   ├── core/                               #   pure infra: result, errors, network, utils
│   ├── design_system/                      #   brand tokens, themes, atoms/molecules
│   └── data_layer/ (optional)              #   shared dtos/clients if extracted later
├── assets/                                 # images, fonts, lottie, icons, env json
├── test/                                   # unit + widget tests (mirrors lib/)
├── integration_test/                       # end-to-end / flow tests
├── tool/  (a.k.a. tooling/)                # dev scripts: codegen, l10n, flavors, ci helpers
├── docs/                                   # architecture, ADRs, runbooks, onboarding
├── config/                                 # flavor & environment definitions (json/dart)
├── .github/ (or .gitlab/)                  # CI/CD workflows, PR/issue templates, CODEOWNERS
├── analysis_options.yaml                   # lints + custom import-boundary rules
├── melos.yaml                              # monorepo task runner (if multi-package)
├── pubspec.yaml
└── README.md
```

**Why `packages/` exists.** Truly shared infrastructure (`core`, `design_system`) lives in **separate Dart packages**, not `lib/`. A package cannot import the app or any feature, so the dependency direction (`features → packages`, never reverse) is enforced *by the compiler*, not by convention. This is the single most effective lever for low coupling at scale and the cleanest path to later extracting features into their own packages.

> **Adoption stance (decisive):** start with **two packages** (`core`, `design_system`) + a single app `lib/` that holds all features. Graduate a feature to its own package only when a Phase-8 trigger fires (it ships in another app, or build/test time demands isolation). This gives compiler-enforced boundaries for the highest-churn shared code without paying full multi-package tax on day one. Use **melos** to manage the workspace from the start so promotion is friction-free.

## 3.2 `lib/` layout

```
lib/
├── main_dev.dart · main_staging.dart · main_prod.dart   # flavor entrypoints (thin)
├── bootstrap.dart                          # shared startup: DI, error zone, observers
├── app/
│   ├── app.dart                            # root MaterialApp.router + theme + l10n
│   ├── di/                                 # get_it composition root, module registration
│   ├── router/                             # go_router config, routes, guards, deep links
│   ├── observers/                          # BlocObserver, RouteObserver, lifecycle
│   └── flavors/                            # Flavor enum + per-flavor AppConfig wiring
├── core/                                   # app-internal cross-cutting (see note)
│   ├── session/                            # SessionManager, SessionUser, AccessPolicy
│   ├── feature_flags/                      # FeatureFlagService + flag keys
│   ├── permissions/                        # role resolution helpers (pure)
│   ├── analytics/  logging/  connectivity/ # façades over packages/core or plugins
│   └── widgets/                            # app-level shells (TabScaffold, AdaptiveNav)
└── features/
    ├── auth/  dashboard/  pockets/  adashi/  contributions/  charity/
    ├── plans/  trust/  group_collaboration/  money/  notifications/
    ├── discovery/  gamification/  profile/  school/  administration/
```

> **`packages/core` vs `lib/core`.** `packages/core` is *pure, app-agnostic* infrastructure (Result/Either, Failure types, Dio client, base UseCase, extensions) — reusable by any KeenPocket app. `lib/core` is *app-specific* cross-cutting wiring (session, flags, the adaptive nav shell) that legitimately knows about this app. Keep the pure stuff in the package; keep app-aware glue in `lib/core`.

## 3.3 Directory purposes — what goes in / what must not

| Directory | Purpose | Put here | Never put here |
|---|---|---|---|
| `lib/app/` | Composition root | Router, DI wiring, theme injection, observers, flavors | Business logic, widgets with domain logic |
| `lib/core/` | App-aware cross-cutting | Session, flags, permissions, adaptive shell, analytics façades | Feature UI, entities, repositories |
| `lib/features/<f>/` | One business capability | data/domain/presentation (Phase 4) | Imports of *another feature's* `data`/`presentation` |
| `packages/core/` | Pure infra | Result, errors, Dio, interceptors, base classes, utils | Anything importing Flutter widgets or features |
| `packages/design_system/` | Brand & UI kit | Tokens, themes, atoms/molecules, Mr K assets refs | Business data, API calls, feature logic |
| `assets/` | Static assets | images, `fonts/Nunito*`, lottie, app icons, `env/*.json` | Secrets, generated Dart |
| `test/` | Unit + widget | Mirrors `lib/`; `*_test.dart`, fixtures, mocks | Integration/e2e flows |
| `integration_test/` | E2E flows | Critical-journey driver tests | Pure unit logic |
| `tool/` | Dev automation | codegen, l10n, flavor, screenshot, release scripts | App runtime code |
| `docs/` | Knowledge base | This blueprint, ADRs, runbooks, onboarding | Generated API noise |
| `config/` | Environment defs | `AppConfig` per flavor, base URLs, flag defaults | Real secrets (use CI secrets / `--dart-define`) |

## 3.4 Assets sub-structure

```
assets/
├── images/ (mascot/mr_k_wave.png, mr_k_cheer.png, kandfriends*.png, coin, covers)
├── icons/        # app & in-app icon set (if not pure emoji)
├── lottie/       # confetti / celebration / loading animations
├── fonts/        # Nunito 400/600/700/800/900
└── env/          # non-secret runtime config: feature-flag defaults, seed data
```

> The reorganised `ui-designs/A_foundation_design_system` (Mr K, coin, design-system docs) is the **source of truth** for these assets and for `packages/design_system` tokens. Export production assets from there.

---
# PHASE 4 — FEATURE MODULE STRUCTURE

## 4.1 Chosen layering (and why not 4 layers)

We use **3 layers + use cases**: `data`, `domain`, `presentation`. We deliberately **do not** add a separate top-level `application` layer.

**Reasoning.** The "application" responsibility (orchestration, transactions, app-specific workflow) is real in KeenPocket — e.g. *pay-an-invoice-from-wallet* spans the wallet and ledger, and *reconcile-and-rotate* spans contributions and the cycle. But for a Flutter app, that orchestration is best expressed as **use cases inside `domain`** (which may depend on multiple repositories) plus **BLoC/Cubit in `presentation`** as the UI-facing application-state coordinator. A fourth folder adds ceremony and an extra hop without buying isolation we don't already get. Where orchestration is genuinely heavy (the Adashi engine), we add a `domain/services/` for pure domain services — still inside `domain`, no new layer.

> If a future module proves to need standalone application services with their own lifecycle (rare), it may add `application/` locally — the standard does not forbid it, it just isn't the default.

## 4.2 Standard feature skeleton

```
features/<feature>/
├── <feature>.dart                      # barrel: public API of the feature (exports pages + DI)
├── di/
│   └── <feature>_module.dart           # registers datasources/repos/usecases/blocs in get_it
├── data/
│   ├── datasources/
│   │   ├── <x>_remote_data_source.dart # Dio/retrofit calls → DTOs (throws on error)
│   │   └── <x>_local_data_source.dart  # drift/cache (offline)
│   ├── dtos/                           # *_dto.dart (json_serializable / freezed)
│   ├── mappers/                        # <x>_mapper.dart  DTO ⇆ Entity
│   └── repositories/
│       └── <x>_repository_impl.dart    # implements domain interface; returns Either<Failure,T>
├── domain/
│   ├── entities/                       # pure immutable business objects (freezed)
│   ├── value_objects/                  # Money, Hands, Nuban, KycStatus (+ validation)
│   ├── repositories/                   # abstract <x>_repository.dart (interfaces only)
│   ├── services/                       # pure domain services (e.g. AdashiRotationService)
│   └── usecases/                       # one class per action; call() → Future<Either<Failure,T>>
└── presentation/
    ├── bloc/ (or cubit/)               # <screen>_bloc.dart + state + event (freezed)
    ├── pages/                          # full screens (route targets)
    ├── widgets/                        # feature-private widgets (composed from design_system)
    └── view_models/ (optional)         # UI-shaped projections / form models
```

## 4.3 Layer contracts

### `domain` (the core; framework-agnostic)
- **Responsibilities:** Entities, value objects, repository **interfaces**, pure domain services, use cases. Encodes business rules and **authorization** (`pocket.roleOf(userId)`, `cycle.canContribute(member)`).
- **Allowed deps:** `packages/core` (Result/Failure, base UseCase), Dart only. Nothing else.
- **Forbidden:** Flutter, Dio, drift, json, any `data`/`presentation` import, any other feature's internals.
- **Naming:** `Pocket` (entity), `PocketRepository` (interface), `ContributeToPocket` (use case), `Money`/`Hands` (value objects), `AdashiRotationService`.
- **Example contents:** `domain/entities/adashi.dart`, `domain/usecases/reconcile_and_rotate.dart`, `domain/value_objects/money.dart`.

### `data` (the detail; implements domain)
- **Responsibilities:** DTOs (wire format), remote/local datasources, mappers, repository **implementations** that translate exceptions → `Failure` and orchestrate cache/network (offline-first).
- **Allowed deps:** own `domain`, `packages/core` (Dio, drift wiring), serialization libs.
- **Forbidden:** `presentation`; other features' `data`; leaking DTOs above the repository (only entities cross the boundary).
- **Naming:** `PocketDto`, `PocketRemoteDataSource`, `PocketLocalDataSource`, `PocketMapper`, `PocketRepositoryImpl`.
- **Example:** `data/repositories/pocket_repository_impl.dart` returns `Right(entity)` from cache then refreshes; `data/mappers/invoice_mapper.dart`.

### `presentation` (the UI; depends on domain only)
- **Responsibilities:** BLoC/Cubit (application state), pages, feature widgets. Maps domain `Failure`/entities → UI state (the 8 universal states from the design brief).
- **Allowed deps:** own `domain` (use cases + entities), `packages/design_system`, `packages/core` (Result), `lib/core` (session/flags/permissions, router).
- **Forbidden:** own `data` layer (never call repositories/datasources directly — go through use cases), other features' `presentation`/`data`.
- **Naming:** `PocketDetailBloc` + `PocketDetailState` + `PocketDetailEvent`; `PocketDetailPage`; `MemberTile`.
- **Example:** `presentation/bloc/adashi_detail_bloc.dart`, `presentation/pages/adashi_detail_page.dart`.

## 4.4 The dependency rule (enforced)

```
presentation ─▶ domain ◀─ data
        │          ▲          │
        └────▶ packages/core ◀┘     (+ design_system for presentation)

features ─▶ packages   (never packages ─▶ features)
feature A ─▶ feature B  ONLY via B's domain (entities + repository interfaces + use cases)
```

**Inter-feature dependency policy.** A feature may depend on another feature **only through its `domain` public surface** (exported via the feature barrel) — e.g. `pockets` consumes `contributions`' `ContributeToPocket` use case and `Invoice` entity, never its BLoC, page, repository impl, or DTO. Enforce with `analysis_options.yaml` import rules (custom_lint / a banned-import lint) so `import '.../features/x/data/...'` from another feature fails CI.

## 4.5 Cross-feature sharing patterns used here

- **Shared capability as a feature** (`contributions`, `group_collaboration`, `trust`, `money`): exposes domain use cases + entities; host features (pockets/adashi) inject and compose them. Context passed via a small abstraction (`ContributionContext`, `GroupContext`).
- **Aggregation feature** (`dashboard`, `discovery`): depends *downward* on multiple features' domain use cases; nothing depends on it.
- **Foundational singletons** (`session`, `feature_flags`, `permissions`): in `lib/core`, importable by any layer's appropriate part (presentation/data), never the reverse.

---

# PHASE 5 — SHARED / CORE ARCHITECTURE

Placement key: **[pkg/core]** = `packages/core`, **[pkg/ds]** = `packages/design_system`, **[core]** = `lib/core`, **[app]** = `lib/app`.

### Core Layer — **[pkg/core]**
`Result`/`Either` (via **fpdart**), `Failure` hierarchy (`NetworkFailure`, `ServerFailure`, `CacheFailure`, `AuthFailure`, `ValidationFailure`, `PermissionFailure`, `FeatureDisabledFailure`), base `UseCase<Out, In>`, `NoParams`, `Json` typedefs, common extensions (`BuildContext`, `num→Money`, `DateTime`), `AppLogger` interface. Pure Dart, no Flutter.

### Shared Widgets / Design System — **[pkg/ds]**
The brand system, lifted directly from `ui-designs` tokens. Tokens: `KpColors` (brand `#1cb0f6`, dark `#1899d6`, light `#ddf4ff`, surfaces, semantic, gradient covers, gold `#ffd900`, full dark set), `KpTypography` (Nunito 400/600/700/800/900 scale), `KpSpacing` (4–32), `KpRadii` (12.8/17.6/22.4), `KpShadows` (soft brand-tinted), `KpDurations`/`KpCurves` (spring). Components: `KpButton` (3D candy + soft/secondary), `KpCard` (chunky 7px bottom), `KpPhotoCard`, `KpStatTile`, `KpProgressRing`, `KpProgressBar`, `KpAvatar`, `KpBadge`, `KpEmptyState` (Mr K), `KpToast`, `KpChatBubble`, `KpRotationStepper`, `KpShareCard`, `KpSkeleton*`, `KpOfflineBanner`, `KpComingSoonCard`, `KpAsyncView` (renders the 8 universal states). No business logic, no networking.

### Networking — **[pkg/core]/network**
**Dio** client with interceptors: `AuthInterceptor` (attach bearer; on 401 → refresh via `refresh-token` → retry once → else force logout), `RetryInterceptor` (idempotent GETs, exponential backoff), `ConnectivityInterceptor` (fail fast offline), `LoggingInterceptor` (redacts tokens), `ErrorInterceptor` (HTTP → typed `Failure`). Base URL from `AppConfig` (`{{base_url}} = …/api`). Per-feature **retrofit** API interfaces optional.

### API Layer — **[feature]/data/datasources**
Remote datasources own endpoint calls and return DTOs; they throw typed exceptions. The 61 documented endpoints map 1:1 to datasource methods (see Appendix). Webhook endpoints are server-only and ignored by the client.

### Authentication — `auth` feature + **[core]/session**
Token-based (Laravel Passport/Sanctum). `SecureTokenStore` (flutter_secure_storage) holds access/refresh tokens; `SessionManager` exposes `Stream<AuthState>` consumed by the router guard. Login accepts email/phone/username; OTP flow gated by `OTP_ENABLED`.

### Authorization — **[core]/permissions** + `domain`
Pure role resolution on entities (`Pocket.roleOf`, `Adashi.roleOf`) + global capabilities on `SessionUser` (`isSuperAdmin`, `canCreateSchool`). An `AccessPolicy` API answers `canManage(pocket)`, `canVerify(invoice)`, `canPostInGroup(ctx)`. UI renders **permission-restricted** state from policy results; never hard-codes role checks in widgets.

### Dependency Injection — **[app]/di** + per-feature `di/`
**get_it** + **injectable** (codegen). Composition root in `lib/app/di`; each feature ships a `<feature>_module.dart` registering datasources (lazySingleton), repositories (lazySingleton), use cases (factory), BLoCs (factory). Scopes for session-bound singletons reset on logout.

### Routing / Navigation — **[app]/router**
**go_router** with typed routes, an auth-guard `redirect`, and deep links (`/pocket/:id`, `/adashi/:id/cycle/:n`, `/invite`, `/u/:id`, notification targets). `StatefulShellRoute` for the 5 bottom tabs (preserves per-tab stacks); an **adaptive shell** swaps bottom nav (phone) for `NavigationRail` + master-detail (tablet) using `LayoutBuilder`/breakpoints.

### State Management — `presentation/bloc`
**flutter_bloc**. **Cubit** for simple/derived UI (lists, toggles, settings); **Bloc** (event-driven) for complex flows with discrete events and audit value — auth, the contribution flow, and the Adashi cycle/rotation. States are **freezed** unions modelling the universal states.

### Error Handling — **[pkg/core]**
Repositories convert exceptions → `Failure`; use cases return `Either<Failure, T>`; BLoCs map `Failure` → error state with a user message + retry. A global `runZonedGuarded` + `FlutterError.onError` route uncaught errors to Crashlytics. No raw exceptions surface to widgets.

### Logging — **[pkg/core]/logging** + **[core]**
`AppLogger` interface (pkg) with a `logger`-package impl; structured levels; production builds strip verbose/PII. `BlocObserver` and `RouteObserver` feed logs/analytics.

### Analytics — **[core]/analytics**
`AnalyticsService` façade (interface in core) over **Firebase Analytics**; typed events (`pocket_created`, `contribution_submitted`, `adashi_rotated`, `referral_shared`). Façade keeps features vendor-agnostic and testable.

### Push Notifications — `notifications` feature + **[core]**
**Firebase Cloud Messaging** (backend already stores `fcm_token` and posts to `push/notification/update`). Handles token refresh registration, foreground (`flutter_local_notifications`), background/terminated, and deep-link routing to the target group/cycle.

### Local Storage — **[pkg/core]/storage**
**drift** (SQLite) for relational, offline-first cache (pockets, members, invoices, adashi cycles, chat). **shared_preferences** for trivial flags/prefs (theme, onboarding-seen). Cache policy per repository (stale-while-revalidate).

### Secure Storage — **[pkg/core]/storage**
**flutter_secure_storage** for tokens and any sensitive cache (never log; never in drift). KYC raw IDs are *never* stored client-side (backend keeps only last-4).

### Offline Support — `data` + **[core]/connectivity**
Offline-first repositories return cached entities then refresh. Mutations that must survive offline (contributions, chat sends, plan-item toggles) use an **outbox** table in drift + a `SyncService` that flushes on reconnect, with idempotency keys. UI shows queued/"will sync" affordances.

### File Management — **[pkg/core]/files**
Avatar/logo uploads (multipart via Dio), image pick/crop/compress, CSV export handling (pocket invoices / adashi records), cache dir hygiene.

### Theming — **[pkg/ds]**
`KpTheme.light` / `KpTheme.dark` built from tokens; `ThemeMode` persisted (matches web's localStorage dark-mode). All colors/typography via tokens — no hard-coded hex in features.

### Internationalization — **[app]** + `l10n/`
`flutter gen-l10n` + ARB files; launch **en_NG** with ₦ currency formatting via `intl`. Structure ready for Hausa/Yoruba/Igbo (the domain uses esusu/adashi/Sadaqah terms). No user-facing string literals in code.

### Environment Configuration — **[app]/flavors** + `config/`
Three flavors **dev / staging / prod** with separate entrypoints (`main_<flavor>.dart`), `AppConfig` (base URL, flag defaults, keys) injected via DI; secrets via `--dart-define`/CI, never committed.

### Feature Flags — **[core]/feature_flags**
`FeatureFlagService` hydrates from backend status endpoints (`payments/status`, `payouts/status`, `kyc/status`, `otp/status`, `enabled` fields) + **Firebase Remote Config** for client-side rollout. Flag keys mirror env names (`walletEnabled`, `paymentsEnabled`, `kycEnabled`, …). Gating helper `flags.guard(Feature.wallet, child, comingSoon: KpComingSoonCard())`.

### App Lifecycle Management — **[core]** + **[app]/observers**
`AppLifecycleListener` for resume-refresh (re-hydrate session/flags), background token-expiry checks, pause analytics, and chat-poll suspension to save battery.

### Connectivity Monitoring — **[core]/connectivity**
**connectivity_plus** + reachability check exposed as `Stream<NetworkStatus>`; drives the offline banner, the connectivity interceptor, and the sync service.

---
# PHASE 6 — TECHNOLOGY DECISIONS

Each decision lists the **choice**, **why**, **alternatives**, **trade-offs**. Decisions are recorded as ADRs in `docs/adr/`.

### State Management — **flutter_bloc (Bloc + Cubit)**
- **Why:** Explicit, inspectable, event-sourced where it matters (auth, contributions, Adashi rotation — flows with real audit/complexity), trivially testable with `bloc_test`, and the most common large-team Flutter standard → easy hiring/onboarding. Clear separation of UI-state from business logic suits Clean Architecture.
- **Alternatives:** **Riverpod** (excellent DI+state, less boilerplate, but compile-time-safe provider graph is a different mental model and weaker for explicit event histories), **GetX** (rejected — encourages tight coupling, poor testability), plain `ChangeNotifier`/Provider (too loose for this scale).
- **Trade-offs:** More boilerplate than Riverpod (mitigated by freezed + templates/snippets). We accept verbosity for explicitness and team familiarity.

### Dependency Injection — **get_it + injectable**
- **Why:** Service-locator with codegen registration; fast, scoped (session scope), and decoupled from Flutter; pairs naturally with bloc + clean layering.
- **Alternatives:** Riverpod-as-DI (would couple DI to state choice), manual constructor injection (verbose at this scale).
- **Trade-offs:** Service locator can hide deps; mitigated by registering at composition root only and injecting into constructors (no `getIt()` calls inside domain).

### Routing — **go_router**
- **Why:** First-party, declarative, robust deep linking (required: pocket/adashi/invite/notification), `StatefulShellRoute` for tab state, redirect guards for auth.
- **Alternatives:** **auto_route** (powerful codegen, typed args — strong contender; heavier, codegen churn), Navigator 2.0 raw (too low-level).
- **Trade-offs:** go_router's typed-routes are newer; we wrap routes in typed helpers to compensate.

### Networking — **dio (+ optional retrofit)**
- **Why:** Interceptors (auth/retry/connectivity/logging), cancellation, multipart, timeouts — everything the token+refresh+offline story needs.
- **Alternatives:** `http` (too bare), `chopper` (fine, smaller ecosystem).
- **Trade-offs:** Slightly heavier; justified by interceptor needs.

### Serialization — **freezed + json_serializable**
- **Why:** Immutable entities/DTOs, exhaustive union states for BLoC, value equality, copyWith, generated `fromJson`/`toJson`. DTOs (`json_serializable`) kept separate from entities (`freezed`), bridged by mappers.
- **Alternatives:** Hand-written models (error-prone), `dart_mappable` (nice, smaller adoption).
- **Trade-offs:** Codegen build step (managed via melos/`tool/codegen.sh`).

### Local Database — **drift** (+ shared_preferences)
- **Why:** Relational, type-safe SQL, reactive queries, migrations — fits the relational, offline-first cache (pockets↔slots↔invoices, adashi↔members↔records) and the outbox/sync table.
- **Alternatives:** **Isar** (fast NoSQL, great DX, but relational modelling & migrations weaker for this data), **Hive** (kv only — used implicitly via prefs).
- **Trade-offs:** More setup than Isar; the relational fit and migration story win for a ledger-style app.

### Secure Storage — **flutter_secure_storage**
- **Why:** Keychain/Keystore-backed token storage. **Alternatives:** none serious. **Trade-offs:** platform quirks on some Androids — wrapped behind `SecureTokenStore`.

### Analytics — **Firebase Analytics** (façade-wrapped)
- **Why:** Free, mature, integrates with Crashlytics & Remote Config (already a Firebase/FCM shop). **Alternatives:** Amplitude/Mixpanel (cost), Segment (later, if multi-sink). **Trade-offs:** vendor lock mitigated by the `AnalyticsService` façade.

### Crash Reporting — **Firebase Crashlytics**
- **Why:** Best-in-class free crash + non-fatal reporting; wired to the global error zone. **Alternatives:** **Sentry** (richer tracing/breadcrumbs; viable if APM needed). **Trade-offs:** less performance tracing than Sentry.

### Push Notifications — **Firebase Cloud Messaging**
- **Why:** Backend already issues/stores FCM tokens and targets reminders; no reason to diverge. `flutter_local_notifications` for foreground display. **Alternatives:** OneSignal (extra vendor). **Trade-offs:** iOS APNs setup overhead (one-time).

### Testing Stack — **flutter_test, bloc_test, mocktail, integration_test, alchemist (golden)**
- **Why:** `mocktail` (no codegen mocks), `bloc_test` (state-sequence assertions), `alchemist`/golden_toolkit (deterministic golden tests for the design system), `integration_test` for journeys. **Alternatives:** `mockito` (codegen). **Trade-offs:** golden tests need CI font/render determinism — pinned.

### CI/CD — **GitHub Actions + Melos + Fastlane (+ optional Codemagic)**
- **Why:** PR pipeline (format → analyze → import-boundary lint → unit/widget → golden → build per flavor); Fastlane for signing & store deploys; Melos to scope tasks to changed packages. **Alternatives:** Codemagic (Flutter-native, simplest signing — good if team prefers managed), Bitrise. **Trade-offs:** GH Actions needs more Flutter setup vs Codemagic; chosen for control + existing GitHub usage.

**Indicative package set:** `flutter_bloc`, `get_it`, `injectable`, `go_router`, `dio`, `retrofit`, `freezed`, `json_serializable`, `fpdart`, `drift`, `shared_preferences`, `flutter_secure_storage`, `connectivity_plus`, `firebase_core/messaging/analytics/crashlytics/remote_config`, `flutter_local_notifications`, `intl`, `cached_network_image`, `image_picker`, `mocktail`, `bloc_test`, `alchemist`, `melos`, `custom_lint`.

---

# PHASE 7 — CODING STANDARDS

### Naming conventions
- Types `UpperCamelCase`; members/vars/functions `lowerCamelCase`; constants `lowerCamelCase` (prefer `static const`); enums `UpperCamelCase` with `lowerCamelCase` values.
- Domain ubiquitous language only: `Pocket`, `Adashi`, `Invoice`, `AdashiCycle`, `Hands`, `Keens` — no synonyms ("group", "circle" only in UI copy/l10n).
- Booleans read as predicates: `isOrganiser`, `hasReceived`, `canContribute`.

### Folder naming — `snake_case`, singular layer names (`domain`, `data`, `presentation`), plural collections (`entities`, `usecases`, `widgets`). Feature folders are the capability name (`adashi`, `contributions`).

### File naming — `snake_case.dart`, suffix by role: `*_entity` (avoid; entities just named `pocket.dart`), `*_dto.dart`, `*_mapper.dart`, `*_repository.dart` (interface) / `*_repository_impl.dart`, `*_remote_data_source.dart`, `*_bloc.dart`/`*_cubit.dart`/`*_state.dart`/`*_event.dart`, `*_page.dart`, `*_view.dart` (one screen per `*_page.dart`).

### Widget structure — Prefer **small composable widget classes** over private helper methods returning widgets (better rebuild scoping & testability). `const` everywhere possible. Pages are thin (wire bloc + render `KpAsyncView`); layout/atoms come from `design_system`. No business logic, no direct repository/datasource calls in widgets.

### State classes — `freezed` sealed unions; model the universal states: e.g. `PocketDetailState = Initial | Loading | Loaded(data) | Empty | Failure(failure) | PermissionDenied | FeatureDisabled`. Keep states minimal & serializable-friendly; no controllers/streams inside state.

### Events — Past/imperative intent names: `PocketDetailRequested`, `ContributionSubmitted`, `CycleRotated`. One event = one user/system intent. No UI objects in events.

### Use cases — One class per action, `UpperCamelCase` verb phrase, single public `call(Params)` returning `Future<Either<Failure, T>>`. Params as a nested `freezed`/record. Examples: `CreatePocket`, `RequestToJoinPocket`, `ContributeToPocket`, `VerifyContribution`, `ReconcileAndRotate`, `PayInvoiceFromWallet`. Use cases hold orchestration; repositories stay CRUD-ish.

### Repositories — Interface in `domain/repositories`, impl in `data/repositories`. Methods return `Either<Failure, Entity>` (never throw across the boundary, never return DTOs). Implement caching/offline here.

### DTOs — `data/dtos`, `*_dto.dart`, mirror the JSON wire format exactly (nullable where the API is). `fromJson`/`toJson` only — no business logic. Never leave the `data` layer.

### Models / Entities — "Model" is avoided as ambiguous; we say **Entity** (domain, pure, `freezed`, business rules + invariants) and **DTO** (data). Entities never reference JSON, Dio, or Flutter.

### Mappers — `data/mappers`, pure functions/extension methods `PocketDto.toEntity()` / `Pocket.toDto()`. The only place DTO↔Entity conversion happens; unit-tested.

### Services — Domain services (`domain/services`, pure, e.g. `AdashiRotationService`) vs infrastructure services (`core`/packages, e.g. `AnalyticsService`, `SyncService`). Name by role + `Service`.

### Extensions — `packages/core/extensions` or feature-local `presentation/extensions`; file `*_extensions.dart`; keep small, pure, discoverable; no extensions on `dynamic`.

### Constants — No magic numbers/strings. Design constants → `design_system` tokens; route names → `app/router`; API paths → datasource-local `const`; flag keys → `feature_flags`. l10n strings → ARB, never inline.

### Theme management — Tokens only; access via `Theme.of(context)`/`KpColors`. No raw hex/`TextStyle()` in features. Light+dark mandatory for every screen.

### Error handling — `Either<Failure, T>` end-to-end; exhaustive `when`/`map` on failures in BLoC; user-facing messages via l10n; always provide retry; report non-fatals to Crashlytics. Never `catch (e) {}` silently; never `print`.

### Documentation — `///` dartdoc on every public class/method in `domain` and `packages/*` (the contracts). Explain *why*, not *what*. Each feature has a `README.md` (purpose, screens, deps, backend endpoints, open gaps). ADRs in `docs/adr` for every significant decision.

---

# PHASE 8 — DEVELOPMENT GUIDELINES

### Feature development workflow
1. Read this blueprint + the feature's `README` + relevant `ui-designs/<phase>` screens.
2. Branch `feat/<feature>/<short-desc>` off `develop`.
3. Build **inside-out**: `domain` (entities, repo interface, use cases) → tests → `data` (dtos/mappers/datasource/repo impl) → tests → `presentation` (bloc → page → widgets) → widget/golden tests.
4. Register in `<feature>_module.dart`; expose only the barrel's public surface.
5. Respect boundaries (lint enforces no cross-feature `data`/`presentation` imports).
6. Wire feature flags + the 8 universal states for every screen.

### Pull request standards
- Small, single-purpose PRs (< ~400 lines diff target). Template: what/why, screenshots or goldens for UI, test evidence, flags touched, backend endpoints used, risk/rollout.
- Green CI required: format, analyze, import-boundary lint, unit+widget+golden, build dev. `CODEOWNERS` routes feature dirs to owners.

### Code review standards
- At least one owner approval. Reviewers check: correct layer placement, dependency-rule compliance, domain purity (no Flutter in domain), test coverage for new use cases/blocs, states completeness (loading/empty/error/offline/permission/disabled), no hard-coded strings/colors, naming.

### Testing strategy (the test pyramid)
- **Unit (most):** use cases, domain services (esp. `AdashiRotationService`), mappers, value objects, BLoCs (`bloc_test`). Target ≥ 85% on `domain`, ≥ 70% overall.
- **Widget:** pages render each universal state; permission variants; tablet vs phone layout.
- **Golden:** every `design_system` component (light/dark) + key screens.
- **Integration (`integration_test`):** critical journeys — onboard→login, create pocket→join→contribute→verify, adashi contribute→rotate, offline-contribute→sync.
- Tests mirror `lib/` paths; shared fixtures/builders in `test/support`.

### Release management
- Trunk-ish: `develop` (integration) → `release/x.y.0` (stabilise) → `main` (tagged prod). Hotfix branches off `main`.
- CI builds per flavor; staged store rollout (internal → beta → phased prod). Crashlytics gate on adoption.

### Versioning strategy
- **SemVer** `MAJOR.MINOR.PATCH+BUILD`; build = monotonic CI number. Feature work bumps MINOR; fixes PATCH; breaking auth/storage migrations MAJOR. Tag releases; auto-generate changelog from conventional commits.

### Environment management
- Three flavors (dev/staging/prod) → distinct app ids/icons/base URLs/Firebase projects. Config via `AppConfig` + `--dart-define`; secrets only in CI. Never point a debug build at prod money rails.

### Migration strategy
- **DB:** drift schema versions with explicit migrations + migration tests; never destructive without a migration.
- **API:** datasources tolerate additive backend changes (nullable DTO fields); version pinning in `AppConfig`; mappers absorb shape drift.
- **Storage/security:** token-store format changes guarded by a migration on launch; bump MAJOR if a re-login is forced.

### Scalability considerations
- **Boundary enforcement** (lint + packages) keeps coupling low as features grow.
- **Promote to package** when a trigger fires: a feature is reused by another app; its build/test time dominates; or it needs independent release. `design_system`/`core` are packages from day one; `contributions`, `trust`, `money`, `group_collaboration` are the most likely next promotions.
- **Backend-gap features** (`plans`, `school`, chat/disputes in `group_collaboration`, `administration`) are built behind flags against agreed API contracts; ship dark until endpoints exist.
- **Performance budgets:** const-correctness, `select`/`buildWhen` to scope rebuilds, paginated lists, image caching, isolate heavy work (CSV, parsing).
- **Team scaling:** `CODEOWNERS` per feature; features developed in parallel with minimal merge conflict because they only meet at the composition root + shared domain surfaces.

---

# APPENDIX — API ↔ FEATURE MAP & BACKEND GAPS

**Base:** `{{base_url}} = http://<host>/api` · bearer token · 61 documented endpoints.

| Feature | Representative endpoints | Status |
|---|---|---|
| `auth` / `session` | `register`, `login`, `logout`, `refresh-token`, `request-token`, `verify-token`, `otp/*`, `change-password` | ✅ live |
| `dashboard` | `dashboard` | ✅ live |
| `pockets` | `my-pockets`, `pocket`, `search-pocket`, `create/pocket`, `pocket/join`, `request/accept`, `invite/user`, `pocket/add/bank/detail`, `pocket/switch`, `pocket/selection/switch`, `add/shopping/item`, `remove/shopping/item`, `subscribe/shopping/item`, `pocket/members/add-keen` | ✅ live |
| `adashi` | `adashi` (create), `adashi/dashboard`, `adashi/search`, `adashi/{id}`, `{id}/join`, `{id}/contribute`, `{id}/reconcile`, `{id}/records`, `{id}/next-cycle`, `{id}/auto-rotate`, `{id}/visibility`, `{id}/admin/override`, `{id}/members/{m}/contributors*` | ✅ live |
| `contributions` | `invoice`, `invoice/create`, `pocket/invoices`, `pocket/month/invoices`, `payment/status/update`, `add/payment/item`, `remove/payment/item`, `adashi/{id}/contribute` | ✅ live |
| `charity` | `pocket/charity`, `pocket/charity/donate`, `pocket/charity/setup` | ✅ live |
| `money` | `wallet`, `wallet/history`, `wallet/topup`, `wallet/pay-invoice`, `payments/status|initialize|verify`, `payouts/status`, `payouts/bank-account`, `adashi/{id}/payout` | ⚠️ live but flag-OFF → design coming-soon |
| `trust` | `ratings`, `users/{id}/ratings`, `reputation/me`, `users/{id}/reputation`, `kyc/status`, `kyc/submit`, `directory/pockets`, `directory/adashi` | ✅ live (KYC flag-OFF); **guarantor/vouching API: gap** |
| `notifications` | `notifications`, `push/notification/update`, `posts`, `post` | ✅ live |
| `discovery` | `directory/*`, `search-pocket`, `search-user` | ✅ live; **insights aggregation API: partial/gap** |
| `gamification` | `gamification/me`, `users/{id}/badges`, `referrals`, `referrals/me` | ✅ live (rewards flag-OFF) |
| `group_collaboration` (chat/disputes) | — | ❌ **gap — web-only; needs mobile API** |
| `plans` | — | ❌ **gap — web-only; needs mobile API** |
| `school` | — | ❌ **gap — web-only; needs mobile API** |
| `administration` | — | ❌ **gap — web-only; needs mobile API** |

**Action:** features marked ❌/⚠️ are designed and scaffolded behind feature flags against an agreed API contract; coordinate with backend to expose mobile endpoints (REST parity or a BFF) before enabling. Money features render the coming-soon state until their flags flip.

---

*End of blueprint. Maintain this document: every significant architectural change ships with an ADR in `docs/adr/` and an update here. The structure, boundaries, standards, and the reasoning above are the contract every KeenPocket mobile contribution is reviewed against.*


