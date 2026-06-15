# KeenPocket — Mobile App Design Brief & Google Stitch Prompt Library

> **Purpose of this document.** A complete, self-contained specification for designing the **KeenPocket native mobile app** (Android phones, iPhones, Android tablets, iPads) using **Google Stitch**. It was reverse-engineered end-to-end from the production Laravel web application so that a designer or AI design tool can recreate the *entire* product experience — including hidden, admin-only, conditional, and role-gated screens — **without access to the original web app**.
>
> **How to use it.** Phase A is the **master/global prompt** — paste it first into Stitch so every subsequent screen inherits the brand, navigation, and state rules. Phases B onward are **per-feature** prompt sets. Each feature gives you business context, the user journey, a per-screen breakdown, and a ready-to-paste **Google Stitch prompt**. Phase Z (end) holds the navigation map, dependency diagram, component inventory, tablet guidance, and open questions.
>
> **Product in one line.** KeenPocket is a **gamified Nigerian group-savings platform** — think "Duolingo meets Esusu" — where people save together through **Pockets** (admin-pooled monthly savings) and **Adashi** (rotating savings circles / *esusu*), with layers for trust (guarantors, KYC, ratings, reputation), money movement (wallet, payouts), social (chat, leaderboard, referrals), and adjacent collection tools (school fees, group shopping, grocery plans).

---

## Table of Contents

- **Phase A — Master / Global Design Prompt** (paste first)
- **Phase B — Authentication & Onboarding**
- **Phase C — Dashboard / Home**
- **Phase D — Savings Pockets**
- **Phase E — Adashi (Rotating Savings Circles)**
- **Phase F — Contributions & Invoices**
- **Phase G — Charity Drives**
- **Phase H — Group Shopping & Grocery Plans**
- **Phase I — Trust Layer (Guarantors, KYC, Ratings, Reputation)**
- **Phase J — Disputes & Group Chat**
- **Phase K — Wallet, Payouts & Bank Accounts**
- **Phase L — Notifications**
- **Phase M — Discover, Search & Insights**
- **Phase N — Gamification, Leaderboard & Referrals**
- **Phase O — Profile & Settings**
- **Phase P — School Fee Management**
- **Phase Q — Administration (Super Admin, Admin Health)**
- **Phase Z — Final Deliverables** (nav map, dependencies, IA, component inventory, tablet & UX recommendations, clarifications)

---

# PHASE A — MASTER / GLOBAL DESIGN PROMPT

> Paste this whole block into Google Stitch as the **project-level system prompt / style foundation** before generating any screen. Every feature prompt below assumes these rules are already in force.

## A1. Product Overview

**KeenPocket** is a mobile-first **group-savings and community-finance app** for the Nigerian market (currency **₦ / NGN**). It does **not custody money by default** — it is a record-keeping, coordination, and trust platform that organises how groups of people save together. Its two flagship products:

- **Pockets** — a savings box run by an *organiser/admin*. Members commit to pay a fixed amount per "hand" each month for a set number of months. All contributions pool centrally; the admin distributes the pooled funds per the group's rules (emergency fund, group purchase, cashback, scholarship, charity).
- **Adashi** — a **rotating savings circle (esusu/ajo)**. Members all pay the same amount each cycle; one member receives the **entire pot** each cycle in a fixed rotation order, until everyone has received once.

Around these sit: an in-app **wallet**, automated **payouts** to bank accounts, **KYC** identity verification, **guarantor/vouching** trust gates, peer **ratings + reputation**, **gamification** (streaks, badges, a "Keens" coin economy), **referrals**, in-group **chat** and **disputes**, **charity** drives, **group shopping/grocery plans**, and a **school-fee management** module.

**Target audience & personas:**
- **The Organiser ("Admin Amaka")** — runs one or more Pockets/Adashi, collects contributions, verifies payments, manages members, resolves disputes, needs collection-health visibility and trust signals to attract members.
- **The Saver ("Member Musa")** — joins groups to build savings discipline; wants clarity on what to pay, when, his payout date, and proof his money is tracked. Motivated by streaks, badges, leaderboard.
- **The Newcomer ("Discoverer Ngozi")** — browsing public groups to join; needs trust signals (organiser reputation, KYC badge, ratings) before committing.
- **The School Admin & Parent** — a proprietor collecting termly fees; parents tracking what they owe per child.
- **The Super Admin** — platform operator granting permissions and tuning the Keens economy.

## A2. Brand & Design Language

**Personality:** Playful, warm, trustworthy, gamified — **"Duolingo energy for serious money."** Chunky, rounded, friendly, with celebratory moments — but always legible and confidence-inspiring around money.

**Color palette (use exactly):**
- **Brand primary:** `#1cb0f6` (bright sky-blue) — primary buttons, active states, progress fills, links.
- **Brand dark:** `#1899d6` — button 3D shadow edge, pressed states, emphasised values.
- **Brand light:** `#ddf4ff` — active nav background, soft chips, avatar fallback bg, icon tiles.
- **Surface:** white `#ffffff` on page background `#f8fafc` (slate-50). Cards use a near-white top-down gradient `linear-gradient(177deg,#ffffff,#f5fbff)`.
- **Ink/text:** slate scale — primary `#1e293b`/`#334155`, muted labels `#64748b`/`#94a3b8`.
- **Semantic:** success emerald (`#d1fae5` bg / `#047857` text), warning amber (`#fef3c7` bg / `#b45309` text), error red (`#fef2f2` bg / `#991b1b` text), info violet (`#ede9fe` bg / `#6d28d9` text).
- **Gradient cover palettes** (used on group cards/share cards, pick by seed): sky→blue, emerald→teal, violet→indigo, amber→orange, rose→pink, cyan→sky.
- **Gold accent** `#ffd900` — Keens coin, confetti, celebration only.
- **Dark mode** (full support, class-based, persisted): bg `#0f172a`, surface `#1e293b`, text `#e2e8f0`, muted `#94a3b8`, borders `#334155`, brand-light becomes `#0c4a6e`, brand-dark text becomes `#7dd3fc`.

**Typography:** **Nunito** throughout (weights 400/600/700/800/900). Body default weight **600**. Headings & numbers **800** (extrabold). Buttons/nav/labels **800 uppercase** with slight letter-spacing. Scale: tiny 10–11px labels, 12px (xs) labels, 14px (sm) body, 18px headings, 24–30px hero stats, 36px star ratings.

**Shape & depth:**
- Radii are generous: small controls `0.8rem`, cards/inputs `1.1rem`, big cards/modals `1.4rem`, pills fully round.
- **3D "candy" buttons:** flat bottom shadow `0 4px 0 brand-dark`; on press the button translates down 3px and the shadow shrinks (tactile Duolingo press). Secondary "soft" buttons are white with a 2px slate border and the same 3D bottom edge in slate.
- **Cards:** 2px light border, a chunky **7px bottom border** (polaroid/3D feel), soft brand-tinted shadow `0 10px 24px -16px rgba(28,176,246,.45)`; lift `-3px` on tap/hover.
- Shadows are soft and brand-tinted, never harsh black.

**Iconography:** **Emoji-first** supplemental icons paired with text labels (never emoji-only for critical actions). Core emoji vocabulary: 🏠 home, 👛 pocket, 🔄 adashi, 🛒 shopping, 🧭 discover, 🏆 leaderboard, ⭐ profile/rating, 🔔 notifications, 🪙 Keens coin, 💳 wallet, 🏦 payouts, 🎁 referrals, 🤝 vouches/trust, ⚖️ disputes, 💬 chat, 📊 insights, 🏫 school, 🎒 children, 🛡️ super-admin, 🩺 admin health, 🔥 streak, 🧊 streak freeze, 🎯 goal, ✅ done, 🎉/✨ celebration, 📣 share. For mobile, you MAY upgrade emoji to a clean rounded line-icon set (e.g. Phosphor/Lucide) **as long as** the playful tone and the emoji used in celebratory/gamified contexts (coin, streak, badges, confetti) are preserved.

**Mascot:** **"Mr K"** — a friendly character with **wave** and **cheer** poses. Appears in: onboarding, dashboard hero, all empty states (84px), and celebration overlays. Confetti (`#1cb0f6,#1899d6,#ddf4ff,#ffd900`) + mascot slide-up on key wins (joined a group, completed a cycle, hit a streak).

**Logo:** wordmark lockup `KeenPocket` on auth/splash; square app icon in headers and modals.

## A3. Navigation Architecture

**Phones — bottom tab bar (5 tabs), persistent:**
1. 🏠 **Home** (Dashboard)
2. 👛 **Pocket** (a hub that leads to Pockets + Adashi)
3. 🛒 **Shopping** (Plans / group shopping)
4. 🧭 **Discover**
5. ⭐ **Profile**

A top app bar carries: screen title (left), and a cluster on the right — 🔔 notifications (with red unread count bubble, "9+" cap), 🪙 **Keens balance pill** (amber), 🌓 dark-mode toggle, ⚙️ settings, avatar. A search affordance ("Search pockets & adashi…") is reachable from Home/Discover.

Everything **not** on the 5 tabs (Wallet, Payouts, Referrals, Vouches, Insights, Leaderboard, Notifications, Settings, School, Admin, Super Admin) lives behind the **Profile tab** as a grouped menu, and behind contextual entry points. Replicate the web's two collapsible groups conceptually: a **"Pocket" group** (My Pockets, Adashi) and a **"Profile" group** (Profile, Wallet, Payouts & Bank, Referrals, Vouches, Insights, Admin health). School (🏫 My School / 🎒 My Children) and 🛡️ Super Admin appear **only** when the user is entitled (feature flag + permission).

**Tablets / iPad — adaptive:** replace the bottom bar with a **persistent left navigation rail/sidebar** (collapsible) showing the full menu tree, and use a **two-pane (list–detail / master-detail) layout** for list-heavy areas (e.g. Pockets list on the left, selected pocket detail on the right; Notifications list + reading pane; Settings sections + panel). Float action buttons (chat 💬) bottom-right.

**Deep linking:** support deep links to a pocket, an adashi, a notification target, an invite (`/invite?ref=CODE`), and a public user profile. Notifications open straight to their related group/cycle.

## A4. Mobile UX Principles (apply to every screen)

- **Thumb-first:** primary actions reachable in the bottom third; destructive/secondary actions higher or in overflow. Sticky bottom CTA bars for forms.
- **Money confidence:** every monetary figure shows ₦ and is right-aligned in tables; show "verified" vs "pending" states explicitly; show *who* verified and *when*; never let an unverified amount masquerade as confirmed. Include a recurring reassurance line where relevant: *"KeenPocket keeps the records — it never holds your money."*
- **Accessibility:** WCAG AA contrast, min 44×44pt touch targets, dynamic type support, visible focus rings (brand border + ring), labels never conveyed by emoji/color alone, screen-reader labels on icon-only controls, reduced-motion variant for confetti/mascot animations.
- **Performance & offline:** skeleton loaders (not spinners) for cards/lists; optimistic UI for chat and item toggles; cache last-known data and show an **offline banner**; queue actions (contributions, item edits) when offline and sync on reconnect.
- **Forgiveness:** confirm destructive actions (delete account/item, decline member, dismiss dispute); inline validation with specific messages; success via lightweight toast (app icon + message, auto-dismiss ~3.5s).

## A5. Universal States — REQUIRED on every screen prompt

Every screen you generate MUST define all of these (omit only when truly impossible, and say so):

1. **Default / populated** — the normal, data-present state.
2. **Loading** — skeleton placeholders matching the final layout (cards, list rows, stat tiles); never a bare spinner for content areas.
3. **Empty / no-data** — Mr K mascot (84px) + bold title + one-line explanation + primary CTA (e.g. "Create your first pocket", "Discover groups"). Use the empty-state component pattern.
4. **Error** — friendly retry card (mascot optional), plain-language cause, **Retry** button; inline field errors in a red `bg-red-50 border-red-200 text-red-800` block listing issues.
5. **Success** — toast (app icon + message) and, for milestone actions, the confetti + mascot celebration overlay.
6. **Offline** — top banner "You're offline — showing saved data"; disable/queue write actions; show last-synced timestamp.
7. **Permission-restricted** — when the user lacks the role (e.g. non-admin viewing an admin tool, non-member viewing members-only data): show a calm locked state explaining who can access it and the path to gain access (request to join, get verified, contact organiser) — never a dead end.
8. **Feature-disabled (flagged-off)** — many features are env-flag gated (wallet, payments, payouts, KYC, OTP, gamification, charity, chat, school, referrals, referral rewards). When off, show a **"coming soon"** placeholder card (emoji + heading + explanation) instead of broken UI.

---
# PHASE B — AUTHENTICATION & ONBOARDING

**Feature overview.** Session/token auth on a shared User model. Single-identifier login (email **or** phone **or** username), classic registration, optional phone **OTP** (MFA / passwordless / reset — gated by `OTP_ENABLED`, no-op when off). Email & phone are immutable after signup ("contact support"). New users start with **50 Keens** and 2 streak-freezes.

**User journey.** Splash → (first run) Onboarding carousel → Login or Register → (if OTP on) OTP verification → Dashboard. Exit points: forgot-password (OTP reset), or deep-linked invite that pre-fills a referral code.

### Screens

**B1. Splash** — logo lockup + mascot on brand-tinted background; brief load; routes to onboarding (first run), dashboard (authed), or login.

**B2. Onboarding carousel** (first run only) — 3–4 swipeable slides introducing: *Save together (Pockets)*, *Take turns (Adashi)*, *Build trust & streaks*, with Mr K illustrations, dots, Skip, and Get Started → Register.

**B3. Login** — one **identifier** field (label "Email, phone, or username"), password (with show/hide), "Remember me", primary **Log in** button, link to Register, link to "Forgot password?" (OTP reset if enabled). Generic error only: *"Invalid credentials. Check your phone/email/username and password."*
- Validation: identifier required, password required.

**B4. Register** — fields: **Full name** (≤255), **Email** (unique), **Phone number** (unique, ≤20), **Password** (≥6) + **Confirm**. Terms acceptance. On success: account created (username defaults to phone), 50 Keens granted, celebration, → Dashboard. Referral code captured if arriving via invite link.
- Inline per-field validation; "email already in use" / "phone already in use".

**B5. OTP verification** (only when `OTP_ENABLED`) — phone display, **6-digit** code input (auto-advance boxes), resend with **60s cooldown** timer, purpose-aware copy (verify signup / passwordless login / reset). Lockout after 5 failed attempts; expiry 10 min. If flag off, this screen is skipped entirely.

**B6. Forgot / reset password** — request code to phone → verify → set new password. Reuses OTP component.

**Tablet layout.** Centered auth card (max ~480px) on a brand-gradient canvas with Mr K + friends illustration occupying the other half (split-screen). Onboarding uses larger illustrations with side-by-side text.

**Accessibility.** Labels on all fields; password show/hide announced; OTP boxes as a single labelled group; error text tied to fields; large 44pt targets.

> **Google Stitch prompt — Authentication & Onboarding**
>
> "Design a mobile authentication flow for **KeenPocket**, a playful Nigerian group-savings fintech. Brand color #1cb0f6 (sky-blue), Nunito font (extrabold headings), generous rounded corners (1.1–1.4rem), candy-style 3D buttons (flat #1899d6 bottom shadow that compresses on press), soft brand-tinted shadows, friendly mascot 'Mr K'.
>
> Generate these screens: (1) **Splash** — centered KeenPocket wordmark + waving Mr K mascot on a #f8fafc background with a subtle brand glow. (2) **Onboarding** — a 3-slide swipeable carousel with large Mr K illustrations, an extrabold headline + one-line subtext per slide ('Save together', 'Take turns with Adashi', 'Build streaks & trust'), page dots, a text 'Skip', and a primary 'Get started' button. (3) **Login** — a centered white card (rounded-2xl, 7px chunky bottom border, brand shadow) with one input 'Email, phone, or username', a password field with show/hide eye, a 'Remember me' checkbox, a full-width brand 'Log in' button, and links 'Create account' and 'Forgot password?'. (4) **Register** — fields Full name, Email, Phone number, Password, Confirm password, a terms checkbox, and a 'Create account' button; show inline validation states. (5) **OTP verification** — phone number shown, six separate digit boxes, a 60-second 'Resend code' countdown, and a 'Verify' button.
>
> Include all states: default, loading (skeleton on the card), inline field-error (red #fef2f2 block listing issues), success (toast with app icon + confetti burst and cheering Mr K on first signup), offline banner, and a 'feature unavailable' variant of the OTP screen (since OTP can be switched off). On tablets/iPad, use a split-screen: auth card on one side, full-bleed Mr K-and-friends illustration on a brand gradient on the other. Ensure 44pt touch targets, AA contrast, and dark-mode variants (bg #0f172a, surface #1e293b)."

---

# PHASE C — DASHBOARD / HOME

**Feature overview.** The 🏠 Home tab — an at-a-glance savings cockpit pulling from every module: greeting, total saved, group counts, reputation, wallet, weekly goal + streak, a 6-month contribution trend chart, earned badges, and quick lists of active Pockets and Adashi.

**User journey.** Default landing after login. Entry to everything: tap a stat tile → its module; tap a group card → group detail; tap weekly goal → leaderboard/insights; empty states route to Discover/Create.

### Screen — Dashboard

- **Hero:** "Hello, {first name}" + Mr K (≈64px), with **Total saved ₦** prominently (sum of all verified Pocket + Adashi contributions).
- **4 stat tiles** (icon tile + big value + label): **Active Pockets** (👛), **Adashi groups** (🔄), **Reputation band** (⭐, e.g. "Tier 1"/"New"), **Wallet balance** (💳, or "—" if wallet disabled).
- **Weekly goal card:** ✅/🎯 status, 🔥 streak count, 🧊 freeze count, motivational line ("resets Monday").
- **Contribution trend:** bar chart, last 6 months, brand bars; empty → "No contributions recorded yet."
- **Gamification card** (if `GAMIFICATION_ENABLED`): reputation score ring (0–100) + savings streak + first 6 earned badges (🏅 pills).
- **My Pockets:** horizontal/stacked photo-cards (gradient cover, status badge, title, ₦/hand, hands, duration "X months · {year}"); empty → CTA to Discover.
- **My Adashi:** photo-cards (cycle #, ₦/cycle, member count); empty → CTA to Create.

**Tablet layout.** Multi-column grid: hero spans top; 4 stat tiles in a row; weekly-goal + reputation ring side by side; trend chart wide; Pockets and Adashi as two parallel card columns.

**Accessibility.** Chart has an accessible data summary/table alternative; stat tiles are labelled buttons; streak/freeze counts have text labels.

> **Google Stitch prompt — Dashboard / Home**
>
> "Design the **Home dashboard** for KeenPocket (playful Nigerian savings fintech; brand #1cb0f6, Nunito, rounded cards with chunky 7px bottom borders and soft brand shadows, emoji icons, Mr K mascot). Layout top-to-bottom on phone: (1) a hero row 'Hello, Amaka 👋' with a small waving Mr K and a large right-aligned '₦248,000 total saved'. (2) A 2×2 grid of stat tiles — each a white rounded card with a colored rounded icon square (brand-light/amber/emerald/violet) + extrabold value + uppercase label: 'Active Pockets 3', 'Adashi 2', 'Reputation Tier 1', 'Wallet ₦5,000'. (3) A 'Weekly goal' card with a 🎯/✅ status, a 🔥 streak counter ('5-week streak'), a 🧊 freeze badge ('2 freezes'), and 'Resets Monday'. (4) A 'Contribution trend' bar chart over the last 6 months in brand blue. (5) A gamification strip: a circular reputation progress ring (0–100, brand fill) beside a row of badge pills (🏅 'Early Saver', 🏅 'Reliable Payer'). (6) 'My Pockets' — horizontally scrollable photo-cards with a colorful gradient cover, a status pill, title, '₦5,000 / hand', and '6 months · 2026'. (7) 'My Adashi' — similar cards showing 'Cycle 2 of 8 · ₦10,000 / cycle · 8 members'.
>
> Provide all states: loading (skeleton tiles, cards, and chart placeholder), empty (Mr K + 'No pockets yet' + 'Discover groups' button for each section, and a chart empty message), error (retry card), offline banner, and disabled variants (wallet tile shows '—' with a subtle 'coming soon', gamification strip hidden when off). On tablet/iPad, expand to a 3–4 column dashboard grid with the chart spanning full width and Pockets/Adashi shown as two parallel columns. Support dark mode and AA contrast."

---

# PHASE D — SAVINGS POCKETS

**Feature overview.** A **Pocket** is an organiser-run monthly savings box. The organiser sets ₦/hand, number of months, capacity (max hands), and rules. Members buy one or more "hands" (multiples of the base amount) and contribute monthly; the organiser verifies payments and distributes the pool per the group's purpose. Rich admin controls: members, guarantor gate, visibility, bank details, rules, charity, shopping, cloning, CSV export.

**Roles.** **Organiser/Owner** (`pocket.user_id`): full control. **Active member** (slot status 1): contribute, rate admin, set payout account, suggest shopping items, chat, raise disputes. **Pending member** (slot status 0): awaiting acceptance (and guarantor recommendation if required). **Non-member/guest**: sees public summary, can request to join if pocket is OPEN.

**Statuses.** Pocket `status`: **1 = OPEN** (request-to-join) / **0 = CLOSED** (invite-only). Slot `status`: **1 = ACTIVE** / **0 = PENDING**. Guarantor: PENDING / RECOMMENDED / DECLINED.

**User journey.** Discover or invite → view public pocket → request to join (provide hands; guarantor contact if required) → accepted → contribute monthly → track progress → (organiser) manage & verify → cycle completes → clone for next year.

### Screens

**D1. Pockets list** (`pockets.index`) — two groups: "Pockets I organise" and "Pockets I'm in", as photo-cards (gradient cover, status pill OPEN/CLOSED, title, ₦/hand, hands, duration). FAB/CTA **Create pocket**. Empty → Mr K + Discover/Create.

**D2. Create pocket** (`pockets.create`) — form: **Title** (req ≤255), **Description**, **Year** (2020–2100), **Start month** (1–12), **Duration** months (1–60), **₦ per hand** (≥1), **Member capacity / max hands** (0 = unlimited), **Your hands** (≥1), **Accept terms**. Show a cost note if Keens economy on (cost per 50 hands). Terms-notice amber callout.

**D3. Pocket detail** (`pockets.show`) — role-adaptive hub:
- Header: cover, title, description, ₦/hand, duration, status; organiser reputation; **Rate admin** button (members); **Share** card.
- **My progress** ring/bar: contributed vs target (hands × months × ₦/hand).
- **Members** list (hidden hand counts if `members_visible` off & viewer not owner); "No members yet" empty.
- **Contributions/Invoices**: my invoices (amount, status Paid/Not-Paid, paid-through, date); **Contribute** CTA.
- **Top contributors** mini-leaderboard (by count, not amount).
- **Rules** card (free text ≤5000).
- **Charity** section (if enabled): progress + donate.
- **Group shopping** list (suggest if `open_purchasing_item`).
- **Payout account** selector (member picks a saved bank account).
- **Group chat** FAB + **Disputes** section.
- Owner-only: **Pending approvals** (member invoices awaiting verification → Mark paid), Manage entry, settings toggles.

**D4. Public pocket view** (`pockets._public`, non-members) — organiser name + reputation + KYC badge, ₦/hand, **hands remaining**, rules preview, **Request to join** form (hands; guarantor contact field if `guarantor_required`); CLOSED → "Invite-only — ask the organiser." Permission-restricted member data hidden.

**D5. Manage pocket / members** (`pockets.manage`, owner only) — members table (name, hands, status), **pending requests** with guarantor status, **add member by phone** form (name if new, phone ≤20, hands, terms), accept/decline, and setting toggles: **OPEN/CLOSED**, **guarantor required**, **members visibility**, **shopping suggestions open**, **bank/collection details** (account name, bank, NUBAN ≤32), **rules**, **clone**, **export invoices (CSV)**.

**Edge cases.** Hands full (capacity reached → no join), guarantor required but none recommended (owner can't accept), pocket closed (no public join), member leaves mid-cycle (slot removable, invoices retained), unverified invoices don't count toward progress.

**Tablet layout.** Master-detail: pockets list left, detail right. Detail uses a 2–3 column dashboard (progress + members + activity). Manage screen uses a wide table with inline actions and a right-side settings panel.

> **Google Stitch prompt — Savings Pockets**
>
> "Design the **Savings Pockets** feature for KeenPocket (playful Nigerian group-savings fintech; brand #1cb0f6, Nunito extrabold, rounded cards with 7px chunky bottoms + soft brand shadows, colorful gradient covers, emoji icons, Mr K mascot, candy 3D buttons). Generate:
>
> (1) **Pockets list** — sections 'Pockets I organise' and 'Pockets I'm in' as photo-cards: a colorful gradient cover (sky→blue, emerald→teal, etc.) with a small status pill ('OPEN'/'CLOSED') and a piggy emoji, then a white body with title, '₦5,000 / hand', '12 hands', '6 months · 2026'. A floating 'Create pocket' button.
>
> (2) **Create pocket** form — Title, Description, Year, Start month (dropdown), Duration in months, ₦ per hand, Member capacity (note '0 = unlimited'), Your hands, an amber terms-notice callout with a checkbox, and a sticky bottom 'Create pocket' button; optionally show a '🪙 Costs 1 Keen' chip.
>
> (3) **Pocket detail** — a hub: header card (gradient cover, title, ₦/hand, duration, status pill, organiser name with a ⭐4.8 reputation chip and a '✓ Verified' badge, a '⭐ Rate admin' button and '📣 Share'); a 'My progress' section with a circular progress ring 'contributed vs target'; a 'Members' list with avatars and hand counts; a 'My contributions' list with status pills ('Paid' emerald, 'Not paid' amber) and a 'Contribute' button; a 'Top contributors' mini-leaderboard with 🥇🥈🥉; a 'Group rules' text card; a charity progress card with a 'Donate' button; a group shopping list; a 'Payout account' selector; a floating 💬 chat button; and a '⚖️ Disputes' section. Show an owner-only 'Pending approvals' card listing member contributions with 'Mark paid' buttons.
>
> (4) **Public pocket view** (for non-members) — organiser reputation + KYC badge, ₦/hand, 'hands remaining', a rules preview, and a 'Request to join' form (number of hands; plus a 'Guarantor email or phone' field shown only when guaranteeing is required). Show a 'CLOSED — invite only' variant.
>
> (5) **Manage members** (owner) — a members table (avatar, name, hands, status), a 'Pending requests' list with guarantor status chips and Accept/Decline buttons, an 'Add member by phone' form, and a settings panel with toggles: Open/Closed, Require guarantor, Members visibility, Open shopping suggestions; plus collection bank details (Account name, Bank, NUBAN), a rules editor, a 'Clone for next year' action, and 'Export invoices (CSV)'.
>
> Include all states: loading skeletons, empty ('No members yet', 'No pockets yet' with Mr K + Create/Discover CTAs), error/retry, offline banner, success toast + confetti when a member is accepted or a pocket is created, permission-restricted (members-only data masked for guests; admin controls hidden for non-owners), and 'hands full' / 'invite-only closed' variants. On tablet/iPad, use a master-detail split (list left, detail right) and a wide manage table with a right-hand settings panel. Dark mode + AA contrast throughout."

---

# PHASE E — ADASHI (ROTATING SAVINGS CIRCLES)

**Feature overview.** An **Adashi** (esusu/ajo) is a rotating savings circle: every active member contributes a fixed **₦ per cycle**; each cycle one member receives the **entire pot**, following a fixed **rotation order (position 1…N)**, until all have received once. Cycle length is in days. Rotation can be **AUTO** (advances when everyone has paid) or **MANUAL** (admin reconciles & rotates). Admin has powerful overrides.

**Roles.** **Admin** (`adashi.admin_id`): create, add members, set collection account, verify/decline contributions, contribute on behalf, reconcile/rotate, overrides (pause/resume, set receiver, mark paid-out, mark dispute, deactivate/reactivate member, set position, adjust contribution), visibility toggles, rules, clone, CSV export. **Active member** (`is_active=1`): contribute, set payout account, rate admin, view timeline, chat, disputes. **Inactive member** (`is_active=0`): skipped, reactivatable. **Guest** (public adashi only): view summary, self-join.

**Statuses.** Adashi `status`: ACTIVE / COMPLETED / PAUSED. Cycle `AdashiRecord.status`: PENDING / COLLECTING / PAID_OUT / DISPUTE. Contribution invoices: Paid / Not-Paid (pending verification). Visibility: `is_public` (discoverable + self-join), `payout_visible` (names+dates vs positions-only).

**User journey.** Create/join → assigned a position → each cycle contribute ₦/cycle → admin verifies → when all paid, pot goes to the cycle's receiver, rotation advances → repeat → when everyone has received, COMPLETED → clone.

### Screens

**E1. Adashi list** (`adashi.index`) — cards for circles I'm an active member of: name, ₦/cycle, member count, current cycle "Cycle 2 of 8", status. CTA **Create adashi**. Empty → Mr K + Create.

**E2. Create adashi** (`adashi.create`) — **Name** (req), **₦ per cycle** (≥1), **Cycle length (days)** (≥1), **Start date**, **Rotation mode** AUTO/MANUAL, **Public?** toggle, **Collection account** (bank, NUBAN, account name), terms. Cost chip if Keens on (per 50 members).

**E3. Adashi detail** (`adashi.show`) — role-adaptive:
- Header: name, ₦/cycle, cycle length, status (ACTIVE/PAUSED/COMPLETED), admin reputation, **Rate admin**, **Share**.
- **Current cycle** card: "Cycle N", paid X/total members, ₦ collected vs expected, status, **receiver** (name if `payout_visible` or you're the receiver, else position only), **Contribute** CTA (amount capped to remaining owed this cycle).
- **Rotation timeline:** ordered list of positions → member (names+projected payout dates if `payout_visible`, else "Position 3 · upcoming"), badges: received ✅, "up next", you-highlight.
- **Payout account** selector (member's bank account for when their turn comes).
- **Pending contributions** (admin): list of Not-Paid invoices → **Verify** / **Decline**; **Reconcile & rotate** button (manual mode).
- **Cycles history** table (cycle #, due date, collected, receiver, status).
- Chat FAB, Disputes, top contributors.

**E4. Members & admin controls** (`adashi.members`, admin only) — members table (position, name, status, has-received), current receiver highlighted, **add member by phone**, and **admin override** actions: set receiver, deactivate/reactivate, adjust (record offline) contribution, set position, pause/resume, mark paid-out, mark dispute. Visibility toggles (`is_public`, `payout_visible`), bank details, rules, clone, export records (CSV).

**E5. Public adashi view** (`adashi._public`) — admin name + reputation, member count, ₦/cycle, cycle length, **Join** (only if `is_public`); private → "Admin-managed — ask to be added."

**Edge cases.** Member joins mid-rotation (appended to end), all received → COMPLETED, unverified contributions stall the cycle (COLLECTING), payout service off (cycle advances but no transfer), private circle (no self-join), payout-visible off (members see positions only).

**Tablet layout.** Master-detail: list left; detail right with the **rotation timeline as a prominent vertical stepper** beside the current-cycle card; admin controls in a right rail. Cycles history as a full table.

> **Google Stitch prompt — Adashi (Rotating Savings Circles)**
>
> "Design the **Adashi** rotating-savings feature for KeenPocket (playful Nigerian fintech; brand #1cb0f6, Nunito extrabold, rounded cards, emoji icons 🔄, Mr K mascot, 3D candy buttons, soft brand shadows). An Adashi is an esusu/ajo: everyone pays a fixed amount each cycle and one member receives the whole pot each cycle in a fixed rotation. Generate:
>
> (1) **Adashi list** — cards with a rotating-arrows motif, name, '₦10,000 / cycle', '8 members', a cycle progress chip 'Cycle 2 of 8', and a status pill (ACTIVE/PAUSED/COMPLETED). A 'Create adashi' button.
>
> (2) **Create adashi** form — Name, ₦ per cycle, Cycle length in days, Start date (date picker), a Rotation mode segmented control (AUTO / MANUAL), a 'Make public' toggle, collection bank details (Bank, NUBAN, Account name), a terms checkbox, and a sticky 'Create' button.
>
> (3) **Adashi detail** — a 'Current cycle' card showing 'Cycle 2', a paid-progress bar 'Paid 6 of 8 members', '₦60,000 collected of ₦80,000', the cycle status, the receiver ('This cycle: Musa receives ₦80,000' — or 'Position 3' when payout is hidden), and a 'Contribute ₦10,000' button. Below it, a prominent **rotation timeline** rendered as a vertical stepper: each step shows position number, member avatar + name, projected payout date, and a state badge (✅ received, '🔵 up next', '· upcoming', and a highlight for 'You'). Add a 'Payout account' selector, a 'Cycles history' table (cycle #, due date, collected, receiver, status), a 💬 chat button, and a '⚖️ Disputes' section. Header has admin name + ⭐ reputation, a 'Rate admin' button, and 'Share'.
>
> (4) **Admin controls / members** (admin only) — a members table (position, avatar, name, status, 'received' badge), the current receiver highlighted, an 'Add member by phone' form, a 'Pending contributions' list with 'Verify'/'Decline' buttons, a 'Reconcile & rotate' button, and an 'Admin overrides' panel with actions: Set receiver, Deactivate/Reactivate member, Adjust contribution (record offline payment), Set position, Pause/Resume circle, Mark paid-out, Mark dispute; plus visibility toggles (Public, Show payout names/dates), bank details, rules, 'Clone', and 'Export records (CSV)'.
>
> (5) **Public adashi view** — admin reputation, member count, ₦/cycle, cycle length, and a 'Join circle' button; show a private variant 'Admin-managed — ask to be added'.
>
> All states: loading skeletons, empty ('No adashi yet' + Mr K + Create), error/retry, offline banner, success toast + confetti when a cycle pays out or you join, permission-restricted (payout names hidden when payout-visibility is off; admin overrides hidden for members; non-members see only the public summary), and a COMPLETED celebration state. On tablet/iPad: master-detail with the rotation stepper beside the current-cycle card and admin overrides in a right rail. Dark mode + AA contrast."

---

# PHASE F — CONTRIBUTIONS & INVOICES

**Feature overview.** The contribution ledger. Each **Invoice** records a payment toward a Pocket month or an Adashi cycle, built from **InvoiceItems** (type **Paid** = savings, or **Donation** = charity; each tagged with a month/cycle). Members submit; organisers verify. Members may pay from **wallet** (if enabled) or be marked paid manually by the organiser. Multiple partial invoices per cycle are allowed and summed.

**Statuses.** `payment_status`: **Paid** (verified, counts) / **Not Paid** (pending). `paid_through`: **Manual** (organiser-marked) / **Wallet** / **Pending**. Item `type`: Paid / Donation. Owner-submitted invoices auto-mark Paid; member-submitted wait for verification.

**User journey (Pocket).** Contribute → choose amount → (preview) allocate across months → optional charity donation → submit (Not Paid) → organiser marks Paid OR member pays from wallet → counts toward progress + leaderboard + streak.

### Screens

**F1. Contribute (amount)** (`invoices.create`) — total **amount** (≥1), optional **donation** to charity (if enabled), context summary (what's owed). Continue → preview.

**F2. Allocate / preview** (`invoices.preview`) — month-by-month breakdown of how the amount maps to outstanding months (editable per-month amounts; allocated total ≤ amount); shows remaining/over. Confirm → submit.

**F3. Pay from wallet** (`invoices.payWallet`) — for a pending invoice, confirm paying from wallet balance (balance shown; insufficient → blocked with top-up CTA). Debits wallet, marks Paid (paid_through Wallet).

**F4. Invoice list / history** — per group and personal: invoice no, amount, status pill, paid-through, date. Organiser sees **Pending approvals** with **Mark paid**. Adashi: contribution modal capped to remaining owed; admin **Verify/Decline**.

**Validation.** amount ≥1; per-month amounts ≥0 summing ≤ total; wallet payment requires sufficient balance; Adashi contribution ≤ (₦/cycle − already submitted this cycle).

**Edge cases.** Over-payment allocates to future months/surplus; unverified invoice never counts; wallet disabled hides wallet-pay; duplicate partials summed.

**Tablet layout.** Two-pane: invoice list left, detail/allocation right. Allocation grid shows all months at once.

> **Google Stitch prompt — Contributions & Invoices**
>
> "Design the **contribution & invoice** flow for KeenPocket (Nigerian savings fintech; brand #1cb0f6, Nunito, rounded cards, status pills, ₦ amounts right-aligned, Mr K mascot). Generate:
>
> (1) **Contribute** — a focused form showing the group name and what's owed, a large '₦ Amount' input, an optional 'Add a charity donation' field (shown only when the group's charity is on), and a 'Continue' button.
>
> (2) **Allocate across months** — a preview list of outstanding months (e.g. 'Jan ₦5,000 / 0 paid', 'Feb …'), each with an editable amount field, a running 'Allocated ₦X of ₦Y' summary with an over/under indicator, and a 'Submit contribution' button. Make clear the contribution will be 'Pending verification' until the organiser confirms.
>
> (3) **Pay from wallet** — a confirmation sheet showing wallet balance, the invoice amount, the resulting balance, and a 'Pay ₦5,000 from wallet' button; plus an 'insufficient balance' variant with a 'Top up' CTA.
>
> (4) **Invoice history** — a list of invoice rows: invoice number, ₦ amount, a status pill ('Paid' emerald / 'Not paid' amber), a 'via Wallet/Manual' tag, and a date. Add an organiser 'Pending approvals' variant where each row has a 'Mark paid' button, and an Adashi 'Verify / Decline' variant.
>
> States: loading (skeleton rows), empty ('No contributions yet' + Mr K), error/retry, offline (queue the contribution, show 'will sync' chip), success (toast + confetti and a streak-increment animation), and a wallet-disabled variant (hide the wallet-pay option). On tablet, use a two-pane list + allocation detail. Dark mode + AA contrast."

---

# PHASE G — CHARITY DRIVES

**Feature overview.** Pocket-scoped charity collection (Sadaqah / fi-sabilillah). The organiser opens a drive with an **amount** target or an **items** target; members donate extra on top of contributions (added as a Donation-type invoice item). Individual donations are private by default; the organiser can reveal a donor breakdown.

**Roles.** Owner: create/edit drive, set goal type, targets, donor-visibility, close. Member: donate amount (or pledge items). Statuses: project ACTIVE/CLOSED; goal_type amount/items.

### Screens

**G1. Charity setup** (`charity.setup`, owner) — Title (≤255), Description, **Goal type** amount/items, **Target amount** (if amount), **Items list** (name, unit, target qty — repeatable, if items), donor-visibility toggle, save. Edit existing.

**G2. Charity on pocket detail** — progress (₦ raised vs target, or per-item bars), **Donate** form (amount or item pledge), donor breakdown (only if `charity_donors_visible`, and still anonymised by default), "no active drive" hides the section.

**Edge cases.** No active project → section hidden; items goal with no items → show total raised; privacy preserved unless explicitly revealed.

**Tablet layout.** Setup form left, live preview of the donor-facing card right.

> **Google Stitch prompt — Charity Drives**
>
> "Design **charity drives** for KeenPocket (Nigerian savings fintech; brand #1cb0f6, Nunito, rounded cards, warm tone, 🤲/❤️ motifs, Mr K). Generate: (1) **Charity setup** (organiser) — Title, Description, a 'Goal type' segmented control (Amount / Items); when 'Amount' show a 'Target ₦' field; when 'Items' show a repeatable item list ('name', 'unit', 'target quantity'); a 'Show donor breakdown' toggle; and a 'Save drive' button. (2) **Donate card** (on a pocket) — a progress bar '₦120,000 raised of ₦200,000' (or per-item progress bars 'Food packs 30/100'), a 'Donate' input (amount or item quantity), a reassurance line that donations are recorded transparently, and an anonymised donor breakdown shown only when revealed. States: default, loading, empty/no-active-drive (section hidden with a subtle 'Start a charity drive' CTA for the organiser), error, success (toast + gentle confetti), offline, permission (only the organiser sees setup), and a charity-disabled variant (feature flag off). Tablet: setup form beside a live preview. Dark mode + AA contrast."

---

# PHASE H — GROUP SHOPPING & GROCERY PLANS

Two distinct but related tools. **Group shopping** is a *suggestion list inside a pocket*. **Plans** is a *standalone collaborative grocery/budget planner* (the 🛒 Shopping tab).

## H-1. Group shopping (inside a Pocket)
Members suggest items to buy together when the organiser opens suggestions (`open_purchasing_item`). Each **ShoppingItem**: name, unit_price, person_count (people sharing), category; total = unit_price × person_count. Owner adds/deletes anytime and toggles whether members may suggest. Empty → "No items yet"; closed → "Suggestions closed" badge.

## H-2. Plans (standalone, the Shopping tab)
**Feature overview.** Collaborative monthly/yearly grocery & budget planner for couples/families. Owner creates a plan (title, period month/year, optional budget, optional carry-over of deferred items as ⭐ priority). Items have name, qty, unit, unit price, note, status **pending/purchased/deferred**, and can be **claimed** by a shopper. Plans can be **shared** with collaborators (by email/phone), who can also edit items. Owner can **archive**. Summary: total/purchased/deferred/pending counts, estimated total, spent, over-budget, % spent.

**Roles.** Owner: full CRUD, share/unshare, archive. Collaborator: view + edit items + claim (cannot share/archive/delete plan). Status: plan ACTIVE/ARCHIVED.

### Screens

**H3. Plans list** (`plans.index`) — owned + shared plans as cards: title, period ("June 2026"), counts (bought/pending/deferred), budget progress bar. CTA **Create plan**. Empty → Mr K + Create.

**H4. Create plan** (`plans.create`) — Title (req), **Period type** month/year, **Month** (YYYY-MM picker), **Budget** (optional ₦), **Carry from** previous plan (imports deferred as ⭐). Save.

**H5. Plan detail** (`plans.show`) — header (title, period, archive); **summary boxes** (total items, bought, deferred, est. total); **budget bar** (over-budget red); **item list** rows (name, qty × unit, unit_price, line total, status badge, ⭐ priority, claimed-by avatar) with actions: mark **purchased**/**deferred**/**pending**, **claim/unclaim** ("I'll buy it"), **edit** (modal), **delete**; **add item** form; **collaborators** section (owner adds/removes by email/phone).

**Edge cases.** Over budget warning; deferred carry-over priority; collaborator can edit but not manage sharing; archived plan read-only.

**Tablet layout.** Plans list left, plan detail right; item list as a wide table; add-item as an inline row.

> **Google Stitch prompt — Group Shopping & Grocery Plans**
>
> "Design the **Shopping** experiences for KeenPocket (Nigerian savings fintech; brand #1cb0f6, Nunito, rounded cards, 🛒 motif, Mr K, friendly checklists). Generate TWO sets:
>
> A) **Group shopping inside a pocket** — a list of suggested items, each row: item name, '₦ unit price', '× people sharing', a computed total, and a category chip; an 'Add item' form (name, unit price, people, category); an owner toggle 'Allow members to suggest'; a 'Suggestions closed' badge variant; and an empty 'No items yet' state.
>
> B) **Standalone grocery Plans (the Shopping tab)** — (1) **Plans list**: cards with a title, a period label 'June 2026', count chips 'Bought 8 · Pending 5 · Deferred 2', and a budget progress bar; a 'Create plan' button. (2) **Create plan**: Title, a Period segmented control (Month / Year), a month picker, an optional Budget ₦, and a 'Carry over deferred items from a previous plan' selector (imported items get a ⭐). (3) **Plan detail**: a header with title + period + 'Archive'; summary tiles (Total items, Bought, Deferred, Estimated ₦); a budget bar that turns red when over budget; a checklist of item rows — checkbox, name, 'qty × unit', '₦ unit price', line total, a status badge (Pending/Purchased/Deferred), a ⭐ for priority, and a small 'claimed by' avatar with an 'I'll buy it / Unclaim' toggle, plus edit and delete; an 'Add item' inline form; and a 'Shared with' collaborators section where the owner adds people by email or phone.
>
> States for both: loading skeletons, empty (Mr K + 'Create your first plan'), error/retry, offline (optimistic item check-off that syncs later), success toast, permission (collaborators can edit items but not share/archive/delete the plan; non-owners never see sharing controls), and an archived read-only variant. Tablet: master-detail with the item checklist as a wide table. Dark mode + AA contrast."

---
# PHASE I — TRUST LAYER (Guarantors, KYC, Ratings, Reputation)

Trust is KeenPocket's moat. Four interlocking systems decide whether strangers will pool money.

## I-1. Guarantors / Vouching
**Overview.** Optional join gate on a Pocket (`guarantor_required`). A would-be member must name an existing KeenPocket user (by email/phone) as guarantor; the guarantor must **Recommend** before the organiser can accept. Decline rejects the request (slot deleted). `PocketGuarantor.status`: PENDING / RECOMMENDED / DECLINED. Guarantor can't be self or the organiser.

**Screens.** **I-a Vouches inbox** (`guarantor.requests`) — list of requests where I'm named: requester avatar+name, pocket, status, optional note, **Recommend** / **Decline** buttons. Empty → "No vouch requests." During join (Phase D4) the requester enters a guarantor contact; "guarantor not found" error if no match.

## I-2. KYC / Identity (BVN/NIN)
**Overview.** Gated by `KYC_ENABLED` (off → "not required"). User submits **type** (BVN/NIN) + **ID number** (10–11 digits); only last 4 digits stored (`kyc_id_last4`) plus reference + verified_at. Status: none/pending/verified/failed. Verified users get a **✓ Verified** badge; if `KYC_GATE_DIRECTORY`, only verified organisers appear in Discover.

**Screens.** **I-b KYC submit** (in Profile) — type segmented control, ID number input, privacy reassurance ("we store only the last 4 digits"), submit; result banners verified ✓ / pending / failed-retry; "not required" when off.

## I-3. Ratings
**Overview.** Members rate an organiser **1–5 stars** (+ optional comment ≤500) after a Pocket/Adashi. One rating per rater per context (overwrites). Shown on public profiles; feeds reputation.

**Screens.** **I-c Rate admin modal** — five tappable stars (hover/scale), optional comment, Submit/Cancel; trigger button shows current average + count. **I-d Ratings received list** (on profile) — rater, stars, comment, context.

## I-4. Reputation
**Overview.** Computed score **0–100** + **band** (New / Tier 1 / Tier 2 / Keen Pioneer) from on-time payment reliability, cycles completed, ratings. Surfaced as a **progress ring** + band badge + stats (payment reliability %, pockets joined, adashis joined, cycles completed, rating avg + count). Thresholds drive badges (reliable_payer, top_organizer, recruiter, big_saver).

**Screens.** Reputation ring + stats block on own Profile and Public Profile (Phase O).

**Tablet layout.** Vouches inbox as two-pane (request list + detail with requester reputation). Profile reputation as a prominent left rail.

> **Google Stitch prompt — Trust Layer (Guarantors, KYC, Ratings, Reputation)**
>
> "Design KeenPocket's **trust & verification** screens (Nigerian savings fintech; brand #1cb0f6, Nunito, rounded cards, 🤝/⭐/✓ motifs, Mr K, trustworthy-but-friendly tone). Generate:
>
> (1) **Vouches inbox** — a list of incoming guarantor requests: requester avatar + name + their reputation chip, the pocket they want to join, a status pill (Pending/Recommended/Declined), an optional note, and 'Recommend' (brand) / 'Decline' (soft) buttons. Empty state 'No vouch requests' with Mr K.
>
> (2) **KYC verification** — a card with a 'BVN / NIN' segmented control, an ID number input (10–11 digits, numeric keypad), a privacy reassurance line 'We only keep the last 4 digits', and a 'Verify identity' button; plus result variants: a green '✓ Identity verified' banner, an amber 'Verification pending', a red 'Verification failed — check the number' with retry, and a neutral 'Not required' when the feature is off.
>
> (3) **Rate admin** modal — a sheet titled 'Rate the organiser' with five large tappable stars that fill amber on selection, an optional comment field, and Submit/Cancel; the launching button shows '⭐ 4.8 (12)'.
>
> (4) **Reputation block** — a circular score ring (0–100, brand fill) with a band badge ('TIER 1' / 'KEEN PIONEER'), and a stats list: payment reliability %, pockets joined, adashis joined, cycles completed, average rating + count; plus a 'Ratings received' list (rater, ★★★★☆, comment, context).
>
> States: loading, empty, error/retry, offline, success (toast; subtle confetti on first verification or first 5-star), permission (you can only rate groups you're a member of; guarantor actions only where you're named). Tablet: vouches as list-detail; reputation as a left rail on the profile. Dark mode + AA contrast."

---

# PHASE J — DISPUTES & GROUP CHAT

## J-1. Disputes
**Overview.** Members/admins flag issues (late payout, missed contribution, fraud) on a Pocket/Adashi. **Dispute**: subject (≤255), body (≤2000), status OPEN/RESOLVED/DISMISSED, resolution note (≤2000), resolver, resolved_at. Members raise + see their own; admin sees all and resolves/dismisses with a note.

**Screens.** **J-a Disputes section** (on group detail) — list (subject, status pill amber/emerald/slate, body, raiser, time, resolution if any); **Raise dispute** modal (subject, body, disclaimer); admin inline **Resolve** (with note) / **Dismiss**. Empty → "No disputes 🤝".

## J-2. Group chat
**Overview.** Real-time-ish member chat per group (poll every ~5s). **Message**: body (≤1000), author, time. Only the organiser + active members can post; non-members see read-only "members only". Gated by `CHAT_ENABLED`.

**Screens.** **J-b Chat panel** — floating 💬 FAB with unread bubble; panel with brand header (app icon, "Group chat", live dot), message bubbles (mine = brand right-aligned, others = white left-aligned with name+time), input pill + send; empty "No messages yet — say hello 👋"; restricted footer for non-members.

**Tablet layout.** Chat as a docked right-hand panel (not floating). Disputes as a dedicated tab within group detail.

> **Google Stitch prompt — Disputes & Group Chat**
>
> "Design KeenPocket's **group communication** features (Nigerian savings fintech; brand #1cb0f6, Nunito, rounded, ⚖️/💬 motifs, Mr K). Generate:
>
> (1) **Disputes** — a section titled '⚖️ Disputes' with a 'Raise a dispute' button; a list of dispute cards (subject, a status pill 'Open' amber / 'Resolved' emerald / 'Dismissed' slate, body text, 'Raised by Musa · 2d ago', and a resolution note box when resolved). A 'Raise a dispute' modal with Subject, Body (textarea), a disclaimer line, and Submit. An admin variant where open disputes show an inline resolution input with 'Resolve' and 'Dismiss' buttons. Empty: 'No disputes 🤝'.
>
> (2) **Group chat** — a floating round brand chat button (💬) with a red unread count bubble; an open chat panel with a brand-blue header (app icon in a white ring, 'Group chat', a green live dot, close ✕), a scrollable message area on a #f8fafc background with bubbles (my messages brand-blue and right-aligned with a tucked corner; others white-bordered and left-aligned with a small name + time above), and a bottom input as a rounded pill with a circular send button. Show an empty 'No messages yet — say hello 👋' and a non-member read-only footer 'Only members can post here.'
>
> States: loading (skeleton bubbles / dispute rows), empty, error/retry, offline (queued message with a clock icon, 'sending…'), success toast, permission (post box hidden for non-members; resolve/dismiss only for admins), and a chat-disabled variant. Tablet/iPad: dock the chat as a persistent right-hand panel and show Disputes as a tab in the group detail. Dark mode + AA contrast."

---

# PHASE K — WALLET, PAYOUTS & BANK ACCOUNTS

## K-1. Wallet (`WALLET_ENABLED`)
**Overview.** Optional in-app balance to fund contributions without a gateway per payment. **Wallet**: balance, currency (NGN). **WalletTransaction**: type credit/debit, amount, balance_after, reason (topup/payment/refund), reference, time. Top-up via gateway (or instant in dev 'log' provider). When off → "coming soon" card.

**Screens.** **K-a Wallet home** — gradient balance card ("Available balance ₦X"), **Top up** (amount, default 5000, Add), **recent activity** list (reason, time, +/− amount in green/slate, running balance). Empty activity → "No transactions yet." Disabled → 💳 coming-soon card.

## K-2. Payouts & bank accounts (`PAYOUTS_ENABLED`)
**Overview.** Where Adashi payouts (and pocket distributions) land. Personal **payout account** (bank_name, bank_code, account_number) + per-pocket **collection** details (bank, NUBAN). Saved **BankAccount** list (label, account_name, bank, NUBAN, is_default) reused as payout targets. **Payout** records: amount, provider, reference, status pending/success/failed, failure_reason, disbursed_at.

**Screens.** **K-b Payouts hub** — **My payout account** form (bank name, code, account number); **Payouts received** list (reference, ₦, status badge); **Pocket collection details** per owned pocket (bank, NUBAN). Disabled → info banner "payouts not switched on yet — you can still save your account."

**Tablet layout.** Wallet: balance + top-up left, activity table right. Payouts: account form left, payouts-received + pocket-collection right.

> **Google Stitch prompt — Wallet, Payouts & Bank Accounts**
>
> "Design KeenPocket's **money** screens (Nigerian savings fintech; brand #1cb0f6, Nunito, rounded cards, ₦, 💳/🏦 motifs, Mr K, confidence-inspiring). Generate:
>
> (1) **Wallet home** — a large gradient (sky→blue) balance card 'Available balance ₦5,000'; a 'Top up' card with an amount input (default 5,000) and an 'Add' button; and a 'Recent activity' list where each row shows a reason ('Top up', 'Pocket payment', 'Refund'), a timestamp, a signed amount (+green credit / −slate debit), and the running balance after. Include a 'wallet disabled' variant: a 💳 card 'Wallet is coming soon'.
>
> (2) **Payouts & bank** — a 'My payout account' form (Bank name, Bank code, Account number) with a 'Save account' button; a 'Payouts received' list (reference, ₦ amount, a status badge: pending amber / success emerald / failed red with reason); and a 'Pocket collection details' section listing each pocket I organise with editable Bank + NUBAN fields. Include a 'payouts not enabled yet' info banner that still lets the user save their account.
>
> (3) **Bank accounts** — a reusable list of saved accounts (label, bank, masked NUBAN, a 'Default' chip), with 'Add account', 'Make default', and 'Remove' (with confirm).
>
> States: loading skeletons, empty ('No transactions yet', 'No payouts yet', 'No accounts saved'), error/retry, offline, success toast, permission (pocket collection details only for organisers), and feature-disabled variants for wallet and payouts. Tablet: two-column (balance/forms left, activity/history right). Dark mode + AA contrast; mask account numbers showing only the last 4 digits."

---

# PHASE L — NOTIFICATIONS

**Overview.** Central inbox for all events. **Notification**: title, body, type, sender, model_id (links to a Pocket/Adashi), status Read/Not-Read, time. Types include PAYMENT_REMINDER, POCKET_INVITATION, USER_JOINED, REQUEST_MADE, REQUEST_APPROVED, PAYMENT_RECEIVED, PAYMENT_MADE, ITEM_SELECTION, PERSONAL_MESSAGE. Multi-channel delivery respects user prefs (push/SMS/WhatsApp); SMS/WhatsApp only for reminders. Opening a notification deep-links to its group and marks it read.

**Screens.** **L-a Notifications list** — filter tabs **All / Unread (n)**, **Mark all read**; rows with unread blue dot, type icon, title (link), body, time, per-row **Mark read** and tap-to-open (deep link). Empty → "You're all caught up!" / "No notifications yet."

**Tablet layout.** List + reading pane (master-detail); selecting a notification shows its full content + a button to its target.

> **Google Stitch prompt — Notifications**
>
> "Design the **Notifications** inbox for KeenPocket (Nigerian savings fintech; brand #1cb0f6, Nunito, rounded, 🔔, Mr K). A screen with filter tabs 'All' and 'Unread (3)', a 'Mark all read' text button, and a list of notification rows: a leading category icon in a colored rounded tile (💰 payment, 👤 joined, 📨 invitation, ⏰ reminder), a title, a one-line body, a relative timestamp, an unread blue dot on the left edge, and a per-row 'Mark read'. Tapping a row opens its related pocket/adashi. States: loading (skeleton rows), empty ('You're all caught up!' with a relaxed Mr K, and 'No notifications yet'), error/retry, offline (cached list + banner), success (subtle), permission n/a. Tablet/iPad: master-detail — the list on the left and a reading pane on the right with the full message and an 'Open group' button. Dark mode + AA contrast; ensure unread state is conveyed by more than color (dot + bold title)."

---

# PHASE M — DISCOVER, SEARCH & INSIGHTS

## M-1. Discover (🧭 tab)
**Overview.** Browse joinable groups. Lists **open Pockets** (status 1, not full) and **public Adashi** (is_public, ACTIVE). If `KYC_GATE_DIRECTORY`, only verified organisers shown. Search `q` (substring, ≤30 each).

**Screens.** **M-a Discover** — hero, search bar, two sections of cards: Pocket (title, ₦/hand, organiser + reputation/KYC), Adashi (name, ₦/cycle, members, admin). Tap → public view → join. Empty → "No open pockets/adashis found."

## M-2. Search
**Overview.** Searches my own + discoverable groups (includes my non-open groups). Split results Pockets/Adashi with counts. Empty query → "Type a name to search."

## M-3. Insights / Reports (📊)
**Overview.** Personal "year in review" across all groups: **Total saved**, **Saved this year**, **Donated (Sadaqah)**, **Contributions count**, **Pockets**, **Adashi** — verified amounts only. Reassurance: "KeenPocket keeps the records — it never holds your money." (This is the closest thing to a member-facing **report**; see also Admin Health for organiser reports, Phase Q.)

**Screens.** **M-c Insights** — 6 stat cards (label, big value, subtext); donated card amber. All-zero still renders.

**Tablet layout.** Discover as a multi-column gallery with a sticky search/filter rail. Insights as a wide stat grid + optional trend charts.

> **Google Stitch prompt — Discover, Search & Insights**
>
> "Design KeenPocket's **discovery and personal reports** (Nigerian savings fintech; brand #1cb0f6, Nunito, rounded cards, 🧭/📊, Mr K). Generate:
>
> (1) **Discover** — a hero strip with Mr K and 'Find your circle', a search bar 'Search pockets & adashi…', then two card galleries: 'Open pockets' (cards with gradient cover, title, '₦5,000 / hand', organiser name + ⭐ reputation chip + optional ✓ verified) and 'Open adashi' (name, '₦10,000 / cycle', '8 members', admin + reputation). Each card has a 'View' that leads to a public join screen. Empty: 'No open groups found' with Mr K.
>
> (2) **Search results** — a search field with split sections 'Pockets (4)' and 'Adashi (2)' as result cards; an initial 'Type a name to search' prompt and a 'No matches' state.
>
> (3) **Insights / Year in review** — a grid of six stat cards: 'Total saved ₦', 'Saved this year ₦', 'Donated (Sadaqah) ₦' (amber accent), 'Contributions' (count), 'Pockets' (count), 'Adashi' (count); each card has a small label, a big extrabold value, and a subtext like 'verified contributions, all groups'; plus a footnote 'KeenPocket keeps the records — it never holds your money.'
>
> States: loading skeletons, empty (per section), error/retry, offline (cached), success n/a, permission (KYC-gated directory hides unverified organisers; show a subtle note), feature variants. Tablet: Discover as a multi-column gallery with a sticky search rail; Insights as a wide grid with optional trend mini-charts. Dark mode + AA contrast."

---

# PHASE N — GAMIFICATION, LEADERBOARD & REFERRALS

## N-1. Gamification (`GAMIFICATION_ENABLED`)
**Overview.** Streaks (weeks with ≥1 verified contribution; reset Monday), **streak freezes** (start 2; bridge a missed week), and **badges** (earned vs locked) with thresholds: **Reliable Payer** (≥90% reliability, ≥3 invoices), **Top Organizer** (≥4.5 avg, ≥3 ratings), **Recruiter** (≥3 referrals), **Big Saver** (≥₦100k contributed). **Keens** coin economy (start 50; spent to create groups when enabled).

## N-2. Leaderboard (🏆)
**Overview.** Ranks top 20 savers by **contribution count** (not amount — privacy), period **This week / All time**; shows my rank if outside top 20. "Resets every Monday."

**Screens.** **N-a Leaderboard** — period toggle, privacy note, ranked rows (🥇🥈🥉/number, avatar, name, count + "pts"), my-row highlight, my-standing footer if outside top 20. Empty → "No rankings yet."

**N-b Badges grid** (on profile) — earned 🏅 vs locked 🔒 with descriptions.

## N-3. Referrals (`REFERRALS_ENABLED`)
**Overview.** Invite link + code; **Referral** tracks referrer→referred, status pending/qualified/rewarded. Qualify on register or first group-join (`REFERRAL_QUALIFY_ON`). Rewards only if `REFERRAL_REWARD_ENABLED`. WhatsApp share with templated message.

**Screens.** **N-c Referrals** — gradient hero "Bring your circle along 🎁", invite link (copy) + code badge + **WhatsApp share**, impact stats (invited / qualified / rewarded), invited-people list with per-person status. Empty → "No invites yet — share your link."

**Tablet layout.** Leaderboard centered with a wider podium top-3. Referrals: link+share left, stats+list right.

> **Google Stitch prompt — Gamification, Leaderboard & Referrals**
>
> "Design KeenPocket's **motivation & growth** screens (Nigerian savings fintech; brand #1cb0f6, Nunito extrabold, very playful, 🔥🪙🏅🏆🎁, Mr K, confetti). Generate:
>
> (1) **Leaderboard** — a segmented control 'This week / All time', a privacy note 'Ranked by contributions — amounts stay private · Resets Monday', a top-3 podium (🥇🥈🥉 with avatars), then ranked rows (rank number, avatar, name, 'X pts' = contribution count); highlight the current user's row in brand-light and pin a 'Your rank #42 · 7 contributions' bar at the bottom when they're outside the top 20. Empty: 'No rankings yet'.
>
> (2) **Badges grid** — a grid of badge tiles: earned ones in full color with 🏅 and a label ('Reliable Payer', 'Top Organizer', 'Recruiter', 'Big Saver'), locked ones greyed with 🔒 and the unlock condition as a tooltip/subtext. Include a streak header card: a big 🔥 '5-week streak' with 🧊 'freezes: 2'.
>
> (3) **Referrals** — a gradient hero 'Bring your circle along 🎁' with Mr K; a copyable invite link field + a monospace referral-code chip; a prominent green 'Share on WhatsApp' button; an impact row of three stats 'Invited 8 / Qualified 3 / Rewarded 1'; and a list of invited people with per-person status pills (Pending/Qualified/Rewarded). Empty: 'No invites yet — share your link above.'
>
> States: loading skeletons, empty, error/retry, offline, success (confetti + cheering Mr K when a badge unlocks or a referral qualifies), permission n/a, and disabled variants (gamification off hides badges/streaks; referral rewards off shows tracking only — hide reward amounts). Tablet: leaderboard centered with a larger podium; referrals as link/share left + stats/list right. Dark mode + AA contrast; never show monetary contribution amounts on the leaderboard."

---

# PHASE O — PROFILE & SETTINGS

## O-1. Profile (own) & Public profile
**Overview.** Own profile: identity (avatar, name, phone), reputation ring + band, stats (payment reliability %, pockets joined, adashis joined, cycles completed, rating avg+count, KYC status), achievements (badges), ratings received, and the KYC submit form (if enabled & unverified). Public profile (others): read-only identity + KYC badge + reputation + stats + **open groups by this person** + ratings; "(you)" if self.

## O-2. Settings
**Overview.** **Avatar** upload (image ≤2MB), **notification preferences** (push/SMS/WhatsApp toggles), **account info** (name editable; email & phone locked → "contact support"), **change password** (current + new ≥6 + confirm), **bank accounts** (add/default/delete; label, account_name, bank, NUBAN; first becomes default), **dark mode** toggle, logout.

**Screens.** **O-a Profile**, **O-b Public profile**, **O-c Settings** (sectioned), **O-d Bank accounts** (shared with Phase K).

**Tablet layout.** Profile as a left identity/reputation rail + right achievements/ratings. Settings as a sections list (left) + panel (right).

> **Google Stitch prompt — Profile & Settings**
>
> "Design KeenPocket's **profile and settings** (Nigerian savings fintech; brand #1cb0f6, Nunito, rounded cards, ⭐/⚙️, Mr K). Generate:
>
> (1) **My profile** — a left identity block: large avatar (image or brand-light initials), name, phone, a reputation score ring (0–100) with a band badge; a stats list (payment reliability %, pockets joined, adashis joined, cycles completed, average rating + count, KYC status chip); an 'Achievements' badges grid (earned 🏅 / locked 🔒); a 'Ratings received' list; and a KYC verification card when unverified.
>
> (2) **Public profile** (viewing someone else) — read-only identity with a '✓ Verified' badge, reputation + stats, an 'Open groups by Amaka' gallery of their joinable pockets/adashi, and ratings received; show a '(you)' indicator when viewing self, and an empty 'No open groups right now'.
>
> (3) **Settings** — sectioned cards: a 'Profile photo' uploader with preview and 'Upload'; 'Notification preferences' with three toggles (Push, SMS, WhatsApp); 'Account info' with an editable Name and locked Email/Phone showing 'Contact support to change'; 'Change password' (current, new, confirm); 'Bank accounts' list with add/make-default/remove; a 'Dark mode' toggle; and a 'Log out' button.
>
> States: loading skeletons, empty (no badges/ratings/bank accounts), error/retry (e.g. avatar too large 'max 2MB'), offline, success toast (saved), permission (public profile is read-only; KYC card hidden when feature off / already verified), confirm dialogs for destructive actions (remove bank account, log out). Tablet: profile as identity rail + achievements/ratings panel; settings as sections list + detail panel. Dark mode + AA contrast; mask bank NUBANs."

---
# PHASE P — SCHOOL FEE MANAGEMENT

**Overview.** A self-contained fee-collection module (`SCHOOL_ENABLED`, permission `can_create_school`, possibly paid). A **school admin** creates a school, classes, fee items, students (linked to parents by phone), payment plans, and records payments; **parents** track what each child owes per term. No payment gateway — manual record-keeping.

**Roles.** **School owner** (`can_create_school`; one school per owner): full setup + record payments. **Parent** (`parent_id` on Student, matched by phone): read-only "My Children". **Super admin**: grants/revokes `can_create_school`, tunes Keens costs (Phase Q).

**Data.** School (name, address, contact, bank, NUBAN, account_name, logo, background_image); SchoolClass (name); FeeItem (class, **term 1/2/3**, name, amount); Student (class, parent, name); PaymentPlan (mode **percent** 100/50/30 **or min_monthly**, note, status ACTIVE); SchoolPayment (student, term, amount, note, recorded_by).

### Screens

**P1. Create school** (`school.create`) — name (req), address, contact, bank/NUBAN/account_name, logo (≤2MB), background (≤4MB); Keens-cost note. One-per-owner.

**P2. School dashboard** (`school.show`, owner) — tabs/sections: **Classes** (add name), **Fee items** (per class, per term, name, amount), **Students** (add: name, class, parent phone [+ parent name if new]), **Payment plans** (per student: percent vs min-monthly + note), **Record payment** (student, term, amount, note). Summaries of collection per class/term.

**P3. My Children** (`school.children`, parent) — per child card: school + class; per **term (1/2/3)** fee, paid, balance, a progress bar; active payment plan terms shown. Empty → "No children linked yet."

**Edge cases.** Disabled flag → routes 404 (hide module entirely). Parent not yet a user → placeholder account created on add. No gateway → payments are admin-recorded only.

**Tablet layout.** School dashboard as a multi-pane admin console (left nav of sections, right working area + tables). My Children as a multi-column card grid.

> **Google Stitch prompt — School Fee Management**
>
> "Design KeenPocket's **school fee management** module (Nigerian fintech; brand #1cb0f6, Nunito, rounded cards, 🏫/🎒, Mr K, clean admin tone). Generate:
>
> (1) **Create school** — a form: School name, Address, Contact, bank details (Bank, NUBAN, Account name), a logo upload and a background image upload, a '🪙 costs Keens' note, and a 'Create school' button.
>
> (2) **School dashboard** (owner) — a console with sections: 'Classes' (a list + 'Add class' name field); 'Fee items' (grouped by class and by Term 1/2/3, each item with a name and ₦ amount, plus an add form with a term selector); 'Students' (a roster table — student name, class, parent name/phone — and an 'Add student' form with a 'parent phone' field that creates the parent if new); 'Payment plans' (per student: a mode toggle 'Percent (100/50/30)' vs 'Minimum monthly ₦', plus a note); and 'Record payment' (student, Term, ₦ amount, note). Show per-term collection summaries with progress bars.
>
> (3) **My Children** (parent, read-only) — a card per child: child name, school, class; then per term (Term 1/2/3) a row 'Fee ₦45,000 · Paid ₦30,000 · Balance ₦15,000' with a progress bar; and the active payment plan summary. Empty: 'No children linked yet'.
>
> States: loading skeletons, empty (no classes/students/children with Mr K + CTA), error/retry, offline, success toast (saved/recorded), permission (only owners see the dashboard; parents see only My Children; the whole module is hidden when the school feature is off or the user lacks 'create school'). Tablet: dashboard as a left-section-nav + right working area with wide tables; My Children as a multi-column grid. Dark mode + AA contrast."

---

# PHASE Q — ADMINISTRATION (Super Admin, Admin Health)

## Q-1. Super Admin (🛡️, `isSuperAdmin()`)
**Overview.** Platform operator console. Search users (name/email/phone); **grant/revoke** `can_create_school` (can't revoke other super admins); list all schools (owner); configure the **Keens coin economy** — toggle `coins_enabled`, set costs (`cost_pocket` per 50 hands, `cost_adashi` per 50 members, `cost_school` per 100 students). Super admins create everything free.

**Screens.** **Q-a Super admin** — user search + results table (name, email, phone, can-create-school flag, Grant/Revoke); schools list (name + owner + open); coin settings panel (toggle + three cost inputs + save).

## Q-2. Admin Health (🩺)
**Overview.** Collection-health dashboard for organisers (the main **organiser report**). Per **Pocket** they run: members, target (members × months × ₦/hand), collected (verified), % , **at-risk** count (members who never paid). Per **Adashi** they run: members, paid this cycle, cycle target (₦/cycle × members), collected, %, **pending** verification count.

**Screens.** **Q-b Admin health** — card grid per group: title, member count, progress bar + %, ₦ amounts, red **At risk / Pending** badges; cards link to the group. Empty → "You don't organise any groups yet."

**Tablet layout.** Super admin as a full admin console (search + tables + settings rail). Admin health as a wide multi-column KPI grid with sortable tables.

> **Google Stitch prompt — Administration (Super Admin & Admin Health)**
>
> "Design KeenPocket's **admin consoles** (Nigerian fintech; brand #1cb0f6, Nunito, rounded cards, 🛡️/🩺, data-dense but still friendly). Generate:
>
> (1) **Super Admin** — a user search bar (by name/email/phone) and a results table (avatar, name, email, phone, a 'Can create school' status, and a 'Grant'/'Revoke' button — revoke disabled for other super admins); a 'Schools' list (school name, owner, 'Open'); and a 'Keens economy' settings card with a 'Coins enabled' toggle and three cost inputs ('Pocket — Keens per 50 hands', 'Adashi — Keens per 50 members', 'School — Keens per 100 students') with a 'Save' button.
>
> (2) **Admin Health** — a KPI card grid, one card per pocket/adashi the user organises: title, member count, a progress bar with % collected, '₦ collected of ₦ target', and a red badge ('3 at risk' for pockets / '2 pending verification' for adashi); each card links to the group. Empty: 'You don't organise any groups yet.'
>
> States: loading (skeleton tables/cards), empty, error/retry, offline, success toast (granted/saved), permission-restricted (these screens are visible only to super admins / organisers respectively — others get a calm 'You don't have access' with a path back). Tablet/iPad: super admin as a full console (search + wide table + settings rail); admin health as a wide multi-column KPI grid. Dark mode + AA contrast."

---

# PHASE Z — FINAL DELIVERABLES

## Z1. Complete Navigation Map

```
KeenPocket (mobile)
├─ Splash → Onboarding (first run)
├─ AUTH: Login · Register · OTP verify · Forgot/Reset
│
├─ [Tab 1] 🏠 Home (Dashboard)
│     ├─ stat tiles → Pockets / Adashi / Reputation(Profile) / Wallet
│     ├─ weekly goal → Leaderboard
│     ├─ trend chart, badges → Gamification/Profile
│     └─ group cards → Pocket detail / Adashi detail
│
├─ [Tab 2] 👛 Pocket (hub)
│     ├─ My Pockets → list → Pocket detail
│     │      ├─ Public pocket view (non-member) → Request to join (+ guarantor)
│     │      ├─ Contribute → Allocate → (Pay from wallet)
│     │      ├─ Manage members (owner) · settings · clone · export
│     │      ├─ Charity setup/donate · Group shopping
│     │      ├─ Group chat · Disputes · Rate admin · Payout account
│     │      └─ Pending approvals (owner)
│     └─ Adashi → list → Adashi detail
│            ├─ Public adashi view → Join
│            ├─ Contribute (cycle) · Rotation timeline · Cycles history
│            ├─ Members & admin overrides (admin) · clone · export
│            └─ Chat · Disputes · Rate admin · Payout account
│
├─ [Tab 3] 🛒 Shopping (Plans)
│     └─ Plans list → Create plan → Plan detail (items, claim, share, archive)
│
├─ [Tab 4] 🧭 Discover
│     ├─ Search (pockets & adashi)
│     └─ Open pockets / Open adashis → Public view → join
│
└─ [Tab 5] ⭐ Profile (+ grouped menu)
      ├─ My profile (reputation, badges, ratings, KYC)
      ├─ Public profile (others)
      ├─ Wallet · Payouts & Bank · Bank accounts
      ├─ Referrals · Vouches (guarantor inbox) · Insights
      ├─ Leaderboard · Notifications 🔔
      ├─ Settings (avatar, prefs, password, accounts, dark mode, logout)
      ├─ 🏫 School (My School / My Children)  [if entitled]
      └─ 🛡️ Super Admin · 🩺 Admin Health     [if entitled]
```

## Z2. Feature Dependency Diagram

```
                         ┌─────────────┐
                         │    USER     │ (keens, kyc, reputation, prefs)
                         └──────┬──────┘
            ┌───────────────────┼───────────────────┐
            ▼                   ▼                   ▼
        ┌────────┐         ┌────────┐         ┌──────────┐
        │ POCKET │         │ ADASHI │         │  SCHOOL  │
        └───┬────┘         └───┬────┘         └────┬─────┘
            │                  │                   │
   ┌────────┼────────┐   ┌─────┼──────┐       (fees/plans/
   ▼        ▼        ▼   ▼     ▼      ▼         payments)
INVOICES  MEMBERS  CHARITY  INVOICES ROTATION PAYOUTS
   │        │       SHOPPING   │     /CYCLES    │
   │     GUARANTORS            │               BANK ACCT
   ▼        │                  ▼                │
 WALLET ◄───┘             WALLET/PAYOUTS ◄──────┘
   │
   ▼
 PAYMENTS (gateway)

Cross-cutting (attach to Pocket AND Adashi):
  CHAT · DISPUTES · RATINGS → REPUTATION · NOTIFICATIONS
Engagement loop:
  CONTRIBUTIONS → STREAKS/BADGES → LEADERBOARD ; REFERRALS → new USERS
Trust gates:
  KYC → Discover visibility ; GUARANTOR → Pocket join ; REPUTATION/RATINGS → join confidence
```

**Key dependencies & feature flags (live vs stub):**
- **Live by default:** Pockets, Adashi, Invoices/Contributions (manual mark-paid), Charity, Chat, Gamification, Referrals (tracking), School, Discover/Search/Insights, Disputes, Ratings/Reputation, Guarantors.
- **Off by default (design the "coming soon" state):** Wallet, Payments (gateway), Payouts (auto-disburse), KYC, OTP, Referral **rewards**.
- Wallet top-up depends on Payments; Adashi auto-disburse depends on Payouts; Discover gating depends on KYC; group creation cost depends on the Keens economy toggle.

## Z3. Mobile Information Architecture (summary)

- **5 primary destinations** (tabs) keep the savings core one tap away; everything administrative/peripheral nests under Profile or contextual entry points.
- **Two domain objects** (Pocket, Adashi) share a near-identical detail "hub" pattern: header → my-status → members/rotation → contributions → social (chat/disputes) → admin tools (gated). Reuse one detail template, swap the middle module (pool progress vs rotation timeline).
- **Role-adaptive screens, not separate apps:** the same detail screen renders member vs organiser vs guest variants. Always show the permission-restricted state rather than hiding navigation entirely.
- **Money is always explicit:** ₦ formatting, verified/pending distinction, "records not custody" reassurance.

## Z4. Recommended Design System (mobile tokens)

- **Color tokens:** `brand #1cb0f6`, `brand-dark #1899d6`, `brand-light #ddf4ff`, `bg #f8fafc`, `surface #ffffff`, `card-grad #ffffff→#f5fbff`, ink `#1e293b/#334155`, muted `#64748b/#94a3b8`, success `#047857/#d1fae5`, warning `#b45309/#fef3c7`, error `#991b1b/#fef2f2`, info `#6d28d9/#ede9fe`, gold `#ffd900`; dark set as in A2.
- **Type scale (Nunito):** 10/11 micro, 12 label, 14 body, 16 lead, 18 h3, 24 h2/stat, 30 hero-stat, 36 stars; weights 600 body / 800 headings & buttons / 900 hero.
- **Radii:** 12.8px (sm), 17.6px (md/inputs), 22.4px (lg/cards/modals), pill.
- **Elevation:** soft brand-tinted shadows; cards lift −3px on press; **3D candy buttons** (4px bottom shadow, compress on press).
- **Spacing:** 4/8/12/16/20/24/32 scale.
- **Motion:** 140ms card lift, 450ms spring (cubic-bezier(.2,.8,.3,1.2)) for celebrations; respect reduced-motion.

## Z5. Component Inventory (build these once, reuse everywhere)

| Component | Variants / notes |
|---|---|
| **App bar** | title + notif bell (count) + Keens pill + dark toggle + settings + avatar |
| **Bottom tab bar** (phone) / **Nav rail** (tablet) | 5 tabs / full tree |
| **Primary button** (3D candy) | default, pressed, disabled, loading, full-width |
| **Soft/secondary button** | white + slate 3D edge |
| **Text-only / icon button** | overflow, links |
| **Input / textarea / select** | focus ring (brand), error, disabled; custom chevron select |
| **Segmented control** | period toggles, goal-type, rotation-mode |
| **Toggle / checkbox** | brand-filled |
| **Card** (chunky 7px bottom) | standard, **photo-card** (gradient cover + status pill), pressable |
| **Stat tile** | icon-square (blue/amber/green/violet) + value + label |
| **Progress bar** | labeled, money current/target |
| **Progress ring** | score/reputation, contribution |
| **Avatar** | image / brand-light initials; sizes 24–96 |
| **Badge / pill** | status (emerald/amber/red/slate), Keens (amber), nav-active |
| **Mini leaderboard** | rank medal + name + count |
| **List row** | divider list; member row, invoice row, notification row, plan item row |
| **Table** | members, students, payouts, super-admin users (tablet-wide) |
| **Modal / bottom sheet** | dispute, rate-admin, share-card, item-edit, confirm |
| **Chat** | FAB + unread bubble; panel; bubbles (mine/other); input pill |
| **Empty state** | Mr K (84) + title + message + CTA |
| **Toast** | app icon + message, auto-dismiss |
| **Error block** | red list of issues |
| **Skeletons** | card, list-row, stat-tile, chart, table |
| **Offline banner** | top, "showing saved data" |
| **Celebration overlay** | confetti + Mr K cheer |
| **Coming-soon card** | emoji + heading (feature-flag off) |
| **Rotation stepper** | Adashi positions (vertical) |
| **Bar chart / sparkline** | dashboard trend, insights |
| **Terms-notice callout** | amber, with checkbox |
| **Share card** | gradient preview + WhatsApp + copy |

## Z6. Tablet / iPad–Specific Recommendations

- Replace bottom tabs with a **persistent collapsible left nav rail**; surface the full menu tree (no nesting-by-necessity).
- **Master-detail everywhere lists exist:** Pockets, Adashi, Notifications, Plans, Settings, Vouches, Super-admin users — list left, detail/editor right.
- **Group detail = multi-column dashboard:** progress/rotation, members, and activity side by side instead of a long scroll; **dock chat** as a right panel; admin overrides in a right rail.
- **Wide data tables** for members, students, payouts, super-admin (with sort/filter); inline row actions.
- Use the extra width for **two-up forms** (create pocket/adashi/plan/school) with a live preview pane (e.g. how the group card or share card will look).
- Keep max content width readable (don't stretch single-column forms full-bleed); center with generous gutters and a side illustration on auth.
- Support **landscape + split-view/multitasking**; keyboard shortcuts for power organisers; hover states for nav/cards.

## Z7. Mobile UX Improvement Opportunities (vs current web)

1. **Unify Pocket vs Adashi mental model up front** — an explainer/chooser ("Pool & share" vs "Take turns") at create-time; the web assumes the user already knows.
2. **Push-first reminders** — lean on native push for payment reminders and "your cycle is next"; reduce reliance on SMS/WhatsApp; add a contribution **due-soon** home banner.
3. **One-tap contribute** — a Home quick-action and per-group sticky "Contribute" with smart default amount (this month's owed); collapse the create→preview→submit flow into a single sheet with optional month-allocation expander.
4. **Biometric unlock + secure session** for a money app; remember device.
5. **Live rotation visualization** — Adashi as an animated wheel/stepper with "you're 3rd · ~Aug 14"; countdown to your payout.
6. **Optimistic, offline-first** chat and item check-offs; queued contributions with clear "will sync" status (mobile networks are flaky).
7. **Trust at a glance** — surface reputation band, KYC ✓, and ratings prominently on every join surface; a "why join this group" trust summary.
8. **Streak rescue nudges** — proactive "use a freeze?" prompt before a streak breaks; celebrate milestones harder on mobile.
9. **Document/receipt capture** — let members attach a transfer screenshot to a contribution to speed organiser verification (today verification is blind).
10. **Deep-linked invites & QR** — organiser shares a QR/short link; joining is one scan.
11. **Accessibility upgrade** — replace emoji-as-icon with labelled icons + emoji accents; ensure status never relies on color alone (web leans on color pills).
12. **Wallet/payout progressive disclosure** — clearly stage "coming soon" features so the app doesn't feel broken while flags are off.

## Z8. Where Mobile UX Should Differ From Web UX

- **Navigation:** web uses a collapsible sidebar + top bar + a thin mobile tab bar; native should commit to **bottom tabs (phone) / nav rail (tablet)** as the primary model, not a hamburger.
- **Group detail:** web is one long scroll with many sections; mobile should **chunk into tabs/segments** (Overview · Members · Contributions · Chat · Admin) to cut scrolling.
- **Forms:** web shows full multi-field forms; mobile should use **sheets, steppers, and smart defaults**, with sticky bottom CTAs and numeric keypads for amounts.
- **Tables → cards:** web tables (members, payouts, super-admin) should become **card lists** on phones, reserving tables for tablets.
- **Chat:** web is a floating widget polling every 5s; mobile should use **push + optimistic send + pull-to-refresh** and feel like a native messenger.
- **Celebrations & gamification:** mobile can afford **richer motion, haptics, and home-screen streak widgets**; web keeps it light.
- **Real-time money states:** mobile should show **pending/verifying** states inline with push updates when an organiser verifies, instead of requiring a manual refresh.
- **Auth:** mobile adds **biometrics, OTP autofill, and persistent sessions**; web relies on password + remember-me.
- **Offline:** mobile must degrade gracefully (cached data, queued writes); web largely assumes connectivity.

## Z9. Missing Requirements / Clarifications Needed

1. **Money custody & rails:** Payments, Payouts, and Wallet are **disabled by default**. Is the mobile launch shipping with real gateways (Paystack/Flutterwave) live, or as a records-only app? This determines whether to fully design wallet/payout flows or just "coming soon" states.
2. **Keens economy:** Is the coin economy on at launch (creation costs Keens)? If so we need a **Keens top-up/earn** surface and pricing — currently users just start with 50 and there's no buy-Keens screen.
3. **KYC at launch:** On or off? If on, which provider and is it gating join/discovery? Affects onboarding friction and trust badges.
4. **OTP/MFA:** Will phone OTP be required at launch (it's off by default)? Native needs SMS-autofill and possibly biometric.
5. **Pocket payout mechanics:** Pockets "pool and distribute per rules," but the distribution/disbursement logic to members isn't fully formalized in the web app (rules are free-text). Mobile may need a structured **payout schedule/rules** UI — confirm desired model.
6. **Notifications real-time:** Is there (or will there be) push infrastructure (FCM token exists)? Chat currently polls. Confirm push scope for parity.
7. **Localization & currency:** Nigeria/₦ only, or multi-country? Any need for Hausa/Yoruba/Igbo localization (the product has esusu/adashi/Sadaqah framing)?
8. **Branding source of truth:** Confirm the final logo/mascot assets and whether we keep emoji-as-icons or move to a custom icon set for native.
9. **School module scope:** Is School part of the consumer app or a separate B2B surface? It implies a very different (admin/roster) persona than the savings core.
10. **Legacy/non-product modules:** The repo contains legacy polling-agent/electoral tooling (States/LGAs/Wards/Polling Units, `/agents`, Firebase upload) that is **local-only and not part of the savings product** — confirmed excluded from mobile. Flagging so it isn't mistakenly designed.
11. **Data viz depth:** Insights is minimal (6 stats). Do we want richer charts/exports (PDF statements) on mobile?
12. **Accessibility & compliance targets:** Confirm WCAG level, and any Nigerian financial/data-protection (NDPR) requirements affecting KYC storage and consent screens.

---

*End of brief. Phases A–Q provide paste-ready Google Stitch prompts per feature module; Phase Z provides the connective architecture, component system, tablet guidance, and the open questions to resolve with product before a full build.*



