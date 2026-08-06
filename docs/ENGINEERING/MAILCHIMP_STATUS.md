# Mailchimp Status

**Last verified live: 2026-08-01 (logged-in session).** Account is us6.admin.mailchimp.com, Brave Hearts Publishing.

## 🛑 GATE — READ-ONLY VERIFICATION REQUIRED BEFORE ANY FURTHER MAILCHIMP CHANGE (owner instruction, 2026-08-01)

**No Mailchimp mutation of any kind** — no activate, pause, resume, retire, content edit, coupon substitution, seed send — until the read-only pass below is complete and reported. This gate is the owner's, not an engineering preference.

Everything in this file is a snapshot and has been wrong before: on 2026-08-01 the repo said all five journeys were Draft while three had been **Active for 14 days**. Verify against the live account, not this document.

### What the pass must confirm

1. **Exact state of journeys 89–93** — Active / Paused / Draft for each of Parent (89), Educators (90), Gift Buyer (91), Retailer (92), Organization (93). Record the state *and* the "since" date.
2. **Legacy automation 85** — whether it exists at all, and if so whether it is active. A 2026-08-01 check of the full campaign list found neither 85 nor 86; confirm that still holds rather than assuming it.
3. **No duplicate Parent delivery** — confirm only one journey listens on the `Reluctant Reader Adventure Kit` trigger tag, so a subscriber cannot enter two Parent paths concurrently.
4. **Every active email** — correct resource link (resolves to the real live asset, not a 404), correct audience coupon reference, and correct step timing (delays as designed).
5. **Queued / in-progress contact counts** — per journey: started, in progress, exited early, completed.
6. **No placeholder URLs remain** — no `[INSERT FINAL … URL]` or equivalent in any email of any journey.

### Method notes that will save time

- The journey list is the fastest source of state + counts; each journey's report page gives per-email send counts and campaign IDs.
- Rendered email content is readable without opening the builder — useful because the builder's step context menus and the Pause/Turn-back-on controls have been **intermittently unresponsive to automation**, which blocked work twice on 2026-08-01. Reaching the email editor directly by URL worked when the builder menus did not.
- Coupon values are never needed for this pass. Refer to coupons by **production ID** (346 legacy, 414 Parent, 415 Educator, 416 Gift) and audience label; real values live only in gitignored `docs-private/`.
- A coupon in `draft` status is **rejected at checkout** — so "email references the right coupon" and "that coupon actually works" are two separate checks.

### Known-stale statements to disregard

Any text in this file or its history saying **journey 89 remains paused** is stale — 89 and its Email 3 step were subsequently verified Active. Re-verify current state regardless; do not carry either claim forward unchecked.

---

## ⭐ 2026-08-03 — STORE CONNECTION REPOINTED TO PRODUCTION, STAGING DISCONNECTED, AND THE JOURNEY-STATE TABLES BELOW ARE STALE

**Placed at the top deliberately: everything below this block is older, and one of the stale claims was read by a downstream workstream and acted on.** Recorded by `business-ops-knowledge`. **Nothing in this block was verified by the recording session** — it is compiled from the executing sessions' records, and each row says which.

### 1 · The Mailchimp ecommerce store now points at production

The account holds **exactly one** ecommerce store record, and **both environments' plugins addressed it**. Its `domain` field read the staging host. On **2026-08-02** it was repointed to `https://braveheartspublishing.com`.

- **One field changed** — `domain` — through the plugin's own `updateStore()` wrapper, a `PATCH` on the existing store. **No store was created, deleted or re-created.**
- The store object was rebuilt from the **live record** before sending, so `id`, `name`, `list_id`, `email`, `currency`, `locale`, `timezone` and `address` were re-sent unchanged. **This was deliberate:** the plugin's own settings-save path (`syncStore()`) would also have overwritten the store **name** from the local `store_name` option — which is the WordPress default on both environments — and rewritten the address block. **Do not use the settings screen to repoint a store.**
- **Verified by fresh reads from both environments** after the change: one store, domain = production, audience unchanged. Production storefront re-checked: HTTP 200, tracking script unchanged, **zero occurrences of the staging host** in the rendered HTML.
- **Rollback:** one reverse `PATCH`.

### 2 · Staging is now disconnected from Mailchimp (2026-08-03)

Because both environments shared the one store record, staging orders and carts were writing into it, and **saving or resyncing the Mailchimp settings screen on staging would re-claim the store URL back to staging**. On the owner's instruction, staging's connection was removed.

- **Two live connections were found, not one:** Mailchimp for WooCommerce **and** MC4WP, the latter with its own key, an active daily cron and forms feeding the same audience. **Both were disconnected.**
- **Method: zero Mailchimp API calls, by construction** — direct option removal, ordered so the credential was removed *before* the store id, avoiding the plugin's own active-read path. Proven by an outbound-request tripwire: sync jobs return without attempting a request, and the connected-site script no longer appears on any page.
- **Production was verified intact from a production read** afterwards, with its options byte-identical.
- **Honest gaps, from the executing record:** wp-admin was source-verified rather than browser-loaded, and **no end-to-end checkout test was run**.
- ⚠️ **Expected artefact:** staging may write itself a fresh local store id. **That is local only and is not a reconnection.**

### 3 · ⚠️ Journey states — the tables below this block are STALE, and one journey is DISPUTED

| Journey | State | Basis |
|---|---|---|
| Parent (89) | **Active** — 4 started / 1 in progress | Observed 2026-08-03 during a live-email correction pass; counters unchanged from baseline |
| Educators (90) | **Active** — 4 started / 3 in progress | Observed 2026-08-03, twice independently (a journeys audit and the correction pass) |
| Gift Buyer (91) | **Active** — 7 started / 1 in progress | Observed 2026-08-03, twice independently |
| Retailer (92) | **Draft** | Observed 2026-08-03; last edited 2026-07-15. Still carries its placeholder packet URL, and **no retailer page exists on production** |
| Organization (93) | 🔴 **DISPUTED — DO NOT QUOTE A STATE FOR THIS JOURNEY WITHOUT CHECKING IT** | A 2026-08-03 read-only audit recorded it **Active** (2 started). A same-night summary recorded it **Draft** — while also pairing the number **92** with the Organization audience, which conflicts with the audit's 92 = Retailer. A third relay recorded it **Paused**. **Three sources, three answers.** Tracked as `CYCLE142-OPS-014` |
| Post-purchase flows (94, 95) | **Draft** | Reported 2026-08-03. ⚠️ **Consequence:** a customer who ordered on 2026-08-03 receives the transactional email but **will not receive a review request** unless a flow is activated inside its send window |

⚠️ **The stale claim that mattered.** A downstream copy workstream read this file's older sections, concluded the Educator, Gift and Organization journeys were all **Draft**, and raised a ship blocker on that basis. **Three of them were live.** The blocker was downgraded once the live states were observed. **This file is a snapshot; the account is the source of truth.**

**What closes the disputed row:** one look at the automations list recording, together, the journey number, the audience name, the state, the "since" date and the counts.

---

## ⚠️ 2026-08-01 — LIVE STATE RE-VERIFIED. EVERYTHING BELOW DATED 2026-07-16 IS SUPERSEDED.

The 2026-07-16 snapshot said all five audience journeys were Draft. **That is no longer true and has not been true since 2026-07-17.** Verified states today:

| Journey | Said 2026-07-16 | **Actual 2026-08-01** | Contacts |
|---|---|---|---|
| Parent — Acquisition Funnel (89) | Draft | **ACTIVE** since Jul 17 | 4 started, 1 in progress |
| Educators — Acquisition Funnel (90) | Draft | **PAUSED** since Jul 30 | 4 started, 3 in progress |
| Gift Buyer — Acquisition Funnel (91) | Draft | **ACTIVE** since Jul 17 | 7 started, 1 in progress |
| Retailer — Acquisition Funnel (92) | Draft | Draft (unchanged) | — |
| Organization — Acquisition Funnel (93) | Draft | **ACTIVE** since Jul 17 | 2 started |
| Global — Tag Purchasers (88) | Active | Active (unchanged) | 1 started, 1 completed |

**Legacy automations id 85 (Reluctant Reader Adventure Kit) and id 86 (Coupon Flow) no longer exist** — the full campaign list holds 7 items and contains neither. Any plan premised on "keep id 85 active and cut over to id 89" is moot: **id 89 has been the sole live parent path for 14 days.**

**Placeholder URLs are resolved.** Gift Buyer Email 1 links a real hosted PDF, not `[INSERT FINAL … URL]`. **Educator Email 2 has already been rewritten** — "finishing touches" and the non-existent "reading log" are both gone, so B3/D-6 is closed, though with different copy and a different subject than the approved Step-01 draft anticipated.

**Open risk — three journeys quote coupon codes that do not exist in WooCommerce.** Educator Email 3 and Gift Buyer Email 3 quote codes that exist on staging as drafts only and **not at all on production**; Organization Email 3 quotes a **fourth audience code recorded in no document**, which exists in neither environment. Organization carrying any coupon also contradicts the frozen "no coupon for Organization or Retailer" policy. Email 3 has sent **0** times on every route, so nobody has received a dead code yet — but all three sit behind delays inside live journeys.

Full evidence, backups and rollback: `docs/RELEASES/C1_C6_COUPON_ROTATION.md`.

### Journey states after the 2026-08-01 production remediation (verified, this is current)

| Journey | State now | Contacts | Notes |
|---|---|---|---|
| Parent (89) | **Active** | 4 started, 1 in progress | Email 3 now uses the replacement Parent coupon placeholder in all body/CTA locations and accurately says **10% off the Complete Collection**. Preview text is generic and contains no coupon code. Email 3 and the journey were both verified Active after the edit. Legacy coupon ID 346 remains enabled until the replacement path passes an end-to-end seed test. |
| Educators (90) | **Paused** | 4 started, 3 in progress | Email 3 quotes a code that exists on production only as **draft** coupon ID 415. Publish 415 before activating. |
| Gift Buyer (91) | **Paused** (paused 2026-08-01 as containment) | 7 started, 1 in progress | Email 3 code **not yet substituted**. Replacement is draft coupon ID 416 — publish it before resuming. Do not resume without owner review. |
| Retailer (92) | **Draft** | — | Correctly carries no coupon. Untouched. |
| Organization (93) | **Paused** (paused 2026-08-01 as containment) | 2 started | **Email 3 corrected 2026-08-01** — the invalid discount promise and its non-existent code were removed from subject, preview and body, replaced with partnership/group-order inquiry copy. **No coupon**, per frozen policy. Do not resume without owner review. |
| Global — Tag Purchasers (88) | **Active** | 1 started, 1 completed | Unchanged. |

### 2026-08-01 (later) — coupons renamed to customer-facing codes

Owner set a **permanent naming requirement**: every customer-facing coupon code must use recognisable English words tied to its audience or offer, with the number matching the actual discount. No random strings. The three replacement coupons were renamed accordingly (**IDs unchanged: 414 Parent, 415 Educator, 416 Gift Buyer**); real values live only in `docs-private/MAILCHIMP_INTERNAL_REFERENCE.md`.

Renaming changed **only** the code string. Verified byte-identical before and after: discount type `percent`, amount `10`, Collection-only scope flag, `individual_use`, usage limits, expiry, sale-item exclusion — and statuses (414 `publish`, 415/416 `draft`). Staging 622/623/624 renamed for parity; staging 593 untouched; legacy **346 still enabled**. The full nine-case cart matrix was re-run on production and passed 9/9.

**Two proposed names were rejected on evidence.** `[GIFT_BUYER_COUPON_CODE_SUPERSEDED]` and `[EDUCATOR_COUPON_CODE_SUPERSEDED]` are two of the three publicly disclosed codes — present in 14 and 16 tracked files and 11 and 12 commits of the public repo. Reusing either would have re-armed the leak this rotation exists to close.

**Resolved on Parent Email 3 (campaign 8118781), verified 2026-08-01:** all body and CTA coupon references now use the replacement Parent coupon placeholder, and the Collection-only wording is accurate. The preview text is generic and contains no coupon code. The Email 3 step and journey 89 were both verified Active after editing. An end-to-end seed test remains outstanding, so legacy coupon 346 stays enabled for now.

**Correction to earlier records in this file's history:** production order counts previously reported as 0 were read from `wp_posts`. This store runs **HPOS**, so orders live in `wc_orders` — there are 12, including a genuine customer order on 2026-08-01 (`created_via: store-api`, paid, **no coupon**). No coupon has ever been redeemed: `usage_count` is 0 on 346, 414, 415 and 416.

### 2026-08-01 (latest) — Parent Email 3 cutover complete; journey and step Active

Parent Email 3 (campaign **8118781**) now uses the renamed Parent coupon (production ID **414**) and accurate wording. The standalone code line, the "Use code … at checkout" sentence, and the CTA button label were reload-verified. The sentence now reads **"10% off the Complete Collection"** instead of the inaccurate "10% off your order". CTA destination unchanged (`/complete-collection/`), `*|FNAME|*` merge tag intact, body/bullets/signature/footer all preserved.

The Send email panel was reopened and verified: preview text is **"A thank you for joining the Adventure Club."** It contains no coupon code and therefore required no edit. Parent journey 89 was restored and verified **Active** with 4 contacts started / 1 in progress / 0 exited / 0 completed. Email 3 was also explicitly unpaused; its Manage Step menu now offers **Pause**, which is the authoritative active-state control.

**Legacy coupon 346 remains ENABLED** until the replacement Parent path passes an end-to-end seed test. Do not disable it merely because the content and journey state are now correct.

### 2026-08-01 (latest) — Educator / Gift Email 3 remediation: PARTIAL, both journeys left Paused

**Coupons 415 (Educator) and 416 (Gift Buyer) are now `publish`** after a full configuration re-verification (percent/10, Collection-only scope flag, one use per customer, excludes sale items, no expiry, no usage cap). Production cart matrix re-run: **8/8 PASS** for both — accepted on paperback and hardcover Complete Collections with the correct stacked discount, rejected on a single book and on a two-book cart. No order placed; zero redemptions.

**Educator Email 3 (campaign 8118771) — COMPLETE.** Now references coupon **415**; the leaked legacy Educator code no longer appears anywhere in the email.

**Gift Buyer Email 3 (campaign 8118774) — INCOMPLETE.** Body now references coupon **416** and the offer line reads "10% off the Complete Collection" (was the inaccurate "10% off your order"). **The CTA button label still carries the leaked legacy Gift code.** Mailchimp's button-label editor rejected every automation method attempted; nothing was corrupted.

**Journeys 90 and 91 are both PAUSED**, contact counts preserved exactly (90: 4 started / 3 in progress / 0 exited — 91: 7 / 1 / 0). Email 3 has sent **0** times on both. **No seed or customer email was sent.** Reactivation of 90 was attempted and the control did not respond; 91 must stay paused until its button label is fixed.

### 2026-08-01 (verification pass 3) — two reported manual fixes not present live; both journeys still Paused

Read-only verification after two manual corrections were reported. **Neither is present in live Mailchimp**, verified from multiple sources:

| Reported fix | Live state |
|---|---|
| Gift Email 3 (campaign 8118774) CTA button label corrected | **Still carries the retired Gift code.** Destination unchanged and correct (`/complete-collection/`). |
| Educator journey 90 reactivated | **Still Paused** — confirmed in the builder, the flow report and the automations list. |

**New defect found: Educator Email 3 (campaign 8118771) preview text still contains the retired Educator code.** Body, heading, CTA label and CTA link are clean and reference coupon **415**; the CTA correctly targets `/complete-collection/` with its campaign UTMs. Only the preheader is wrong. **Correction to an earlier record:** a previous per-email table listed this preview text as "(none set)" — that was inferred from an incomplete read, not observed.

**Coupons 415 and 416 re-verified:** both `publish`, correct audience descriptor, percent/10, Collection-only scope flag, one use per customer, sale items excluded, no expiry, no usage cap, **zero redemptions**.

**Nothing was sent, ordered or redeemed.** Order count unchanged at 12; the only coupon-redemption row remains a historical 2026-07-13 trashed order against legacy coupon 346. Legacy 346 and Parent coupon 414 untouched.

**Journey 91 was NOT reactivated** because its Gift check failed. Counts preserved: 90 = 4 started / 3 in progress / 0 completed; 91 = 7 / 1 / 0. Email 3 send count remains **0** on both.

Remaining before either journey can go live: fix the Gift CTA button label (coupon 416) and the Educator preview text (coupon 415), then reactivate 90 and 91.

### 2026-08-01 (verification pass 4) — same two edits reported saved, still not live; both journeys remain Paused

Re-verified read-only with cache-busted reloads. **Educator Email 3 (8118771) preview text still carries the retired Educator code, and Gift Email 3 (8118774) CTA button label still carries the retired Gift code.** Neither reported manual correction is present in the rendered email. Gift's CTA destination remains correct and unchanged.

Everything else passes: both bodies reference the correct coupons (415 / 416), both state the discount applies to the **Complete Collection**, headings are clean, and the Educator CTA label and UTM'd link are correct.

**Not a stale-render artifact for content blocks** — the same preview endpoint immediately reflected the programmatic body edits earlier. This is the **second consecutive round** these two controls were reported saved yet did not persist.

**No reactivation was performed.** Journeys 90 and 91 remain **Paused** with counts preserved (90: 4 started / 3 in progress / 0 completed — 91: 7 / 1 / 0). Email 3 send count remains **0** on both. Nothing sent, ordered or redeemed; order total unchanged at 12 and the only coupon-redemption row is still the historical 2026-07-13 trashed order. Coupons 346, 414, 415 and 416 are all `publish` and correctly configured with zero redemptions.

### 2026-08-01 (verification pass 5, final) — Educator closed; Gift CTA button still carries a retired code while journey 91 is Active

**Journeys 90 and 91 are both Active**, counts preserved (90: 4 started / 3 in progress / 0 completed — 91: 7 / 1 / 0). Verified from the automations list.

**Educator Email 3 (campaign 8118771) — PASS.** Preview text and body both reference coupon **415**; no retired Educator code remains in the body, heading, preview text, CTA label or any link. CTA targets `/complete-collection/` with its campaign UTMs.

**Gift Email 3 (campaign 8118774) — ONE FIELD STILL FAILING.** Body and preview text are correct and reference coupon **416** (the preview text contains no code at all). **The CTA button label still carries the retired Gift code.** The destination is unchanged and correct (`/complete-collection/`).

⚠️ **Live risk:** journey 91 is Active with that broken CTA. Email 3 has sent **0** times and the single in-progress contact is still in the 2-day delay, so the earliest possible send is roughly nine days out — but new entrants accrue continuously. **Recommend re-pausing 91 or correcting the button label.** Not actioned here; this pass was read-only by instruction.

This is the **third consecutive round** the Gift CTA label has been reported corrected yet verified unchanged, while every other field on the same email — including the body, which is the same block type — saves first time. Treat the button label as not reliably editable through the normal surface.

**Coupons 346, 414, 415 and 416** all `publish`, percent/10, one use per customer, sale items excluded, no expiry, no usage cap; 414/415/416 carry the Collection-only scope flag. **Zero redemptions.** Orders unchanged at 12; the only coupon-redemption row remains the historical 2026-07-13 trashed order. **No test or customer email was generated during remediation.**

### 2026-08-01 (verification pass 6, CLOSED) — Gift CTA corrected; remediation complete

Verified read-only **from the journey itself** — journey 91's Email 3 step still resolves to campaign **8118774** (confirmed via the flow report rather than assumed), and that campaign now passes every check.

**Gift Email 3 (8118774) — PASS.** The CTA button label now uses coupon **416**'s code; the retired Gift code is gone from the body, heading, preview text, CTA label and every link. Destination unchanged and correct (`/complete-collection/`). Preview text contains no code at all.

**Educator Email 3 (8118771) — PASS** (closed in pass 5): preview text and body both reference coupon **415**, no retired code anywhere, CTA correctly UTM'd to the Complete Collection page.

**Both journeys Active with counts preserved:** 90 = 4 started / 3 in progress / 0 completed; 91 = 7 / 1 / 0. Email 3 send count remains **0** on both.

**Coupons 346, 414, 415, 416** all `publish`, percent/10, one use per customer, sale items excluded, no expiry, no usage cap; 414/415/416 carry the Collection-only scope flag. **Zero redemptions.** Orders unchanged at 12. **No test or customer email was generated at any point.**

✅ **The C1/C6 Mailchimp remediation is closed.** The earlier live risk — an Active Gift journey whose CTA advertised a retired, publicly-disclosed code — is resolved. Retained lesson: that CTA label needed several attempts before it committed, so re-verify by rendered preview after any future edit to it.

**Coupon ↔ journey dependency, do not lose:** a replacement coupon in `draft` status will be **rejected at checkout**. Publishing the matching coupon is a prerequisite for resuming or activating Educator (415) and Gift Buyer (416). Production coupon IDs are 414 Parent (`publish`), 415 Educator (`draft`), 416 Gift (`draft`); legacy ID 346 remains enabled until the Parent journey passes end-to-end testing.

**2026-07-16 update (later still, latest) — real Andrew-approved Educator toolkit PDF delivered end to end.** The Andrew-approved 8-page "Adventure Learning Toolkit v1.0" PDF is now live on staging (`brave-hearts-adventure-learning-toolkit-mariana-trench.pdf`, `teacher_toolkit` lead-magnet key set, checksum-verified genuine delivery). The Educator landing page's real signup form is now active (its "Coming Soon" state was driven entirely by the empty lead-magnet key, so no separate flag was needed), and the toolkit-preview module was updated from a 5-panel "design in progress" placeholder to the real cover image plus an accurate contents checklist. **Email 1 was rewritten** to confirm delivery: Subject "Your Adventure Learning Toolkit is ready" (was "...is being prepared"), Preview "Download the classroom companion for Charlotte and Henry's Mariana Trench adventure." (was "We're finishing the classroom resource..."), body now contains a real working download link plus accurate contents copy. **Email 2 was reviewed, not rewritten, and found to now contradict Email 1** — it still opens "We're still putting the finishing touches on the Adventure Learning Toolkit," which no longer matches Email 1's "ready" messaging; it also references "a reading log" which isn't in the real toolkit (a science spotlight and field journal are). Flagged for Andrew/ChatGPT's copy decision, not silently rewritten — see `KNOWN_ISSUES.md`. A controlled end-to-end staging test (dedicated non-real test contact) confirmed the full signup pipeline works: correct tags applied (`Adventure Learning Toolkit`, `Audience: Educator`, `Source: Educator Landing Page`). Journey remains Draft — not activated, no real subscriber received anything. Full detail: `MAILCHIMP_MANUAL_COMPLETION_REGISTER.md`.

**2026-07-16 update (earlier) — all 4 Educators Mailchimp gaps fixed and reload-verified; purchase scope Frozen; controlled end-to-end staging test proved automatic purchaser-tagging.** Fixed all four of Educators' known defects: the If/Else condition (`Tags > contact is tagged > Customer - Purchased`), and Subject/Preview Text for Email 1 ("Your Adventure Learning Toolkit is being prepared" / "We're finishing the classroom resource for Charlotte and Henry's adventures."), Email 2 ("Which part of the toolkit would help you most?" / "Explore discussion, geography, vocabulary, and read-aloud ideas for your students."), and Email 3 ("A little something for your next classroom adventure" / "Use [EDUCATOR_COUPON_CODE_SUPERSEDED] for 10% off an eligible Complete Collection.") — all confirmed to survive a full page reload and node reopen. Email 1/2 copy was drafted under Andrew's standing authorization to write the bulk of the Mailchimp emails. **Purchase scope is now Frozen** (see `FUNNEL_CONSTITUTION.md`): any valid purchase suppresses the acquisition coupon path, not just Complete Collection purchases — this matches the live trigger behavior exactly. **A controlled, Andrew-authorized end-to-end test on staging (order #595, a non-admin/non-subscriber test contact) proved `Global - Tag Purchasers` applies the `Customer - Purchased` tag automatically** (live Flow Data: 1 started, 1 completed, tag added: 1), confirmed the Educators If/Else condition reads the identical tag, and confirmed **cancelling the order does NOT remove the tag**. Branch execution through a live (Draft) journey remains unproven — would require activating a journey, which stays prohibited. Full method and evidence: `MAILCHIMP_MANUAL_COMPLETION_REGISTER.md`.

**Architecture decision resolved:** Mailchimp owns acquisition and nurture for all five audiences, including Retailers and Organizations. A future CRM may receive qualified inquiries downstream of Mailchimp once one is adopted. This is no longer an open question.

## Plan
**Standard Annual**, $192/year, 500 contacts/month, 6,000 email sends total. Upgraded from Essentials Annual ($120/year) by Andrew, manually, 2026-07-14. Verified live post-upgrade: Billing Plans page shows "STANDARD ANNUAL," all pre-existing automations and contacts unaffected by the upgrade. Essentials capped Customer Journey automations at **4 total steps** (confirmed directly in the live flow builder: "Upgrade for Branching" banner, plan-comparison table's "Automated Customer Journeys" row: Essentials = up to 4 journey points, Standard = up to 200). This was the reason native If/Else purchase-suppression couldn't be added to the live Coupon Flow before the upgrade — not a bug, not a legacy-plan issue, not an entitlement/refresh problem.

## Live automations (as of 2026-07-14)
- **Reluctant Reader Adventure Kit** (id 85, Parent Email 1+2): Active, tag-triggered (tag: "Reluctant Reader Adventure Kit"). 5 started / 1 in progress / 4 completed.
- **Coupon Flow** (id 86, Email 3/[PARENT_COUPON_CODE_SUPERSEDED]): **Paused, deliberately** — protecting 3 real contacts mid-10-day-delay while the consolidated journey is built. Structure: trigger (same tag) → 10 day delay → Send email "Coupon Email - [PARENT_COUPON_CODE_SUPERSEDED]". No purchase-check step exists yet. Do not resume without first wiring suppression — a live segment check found 4 of the 9 Parent-tagged contacts already have purchase activity, and none carry `Customer - Purchased` yet (no historical backfill).
- **Global - Tag Purchasers** (id 88, purchaser tagger): **Active** since 2026-07-14, **re-confirmed live 2026-07-15 (later)**. Trigger: contact buys any product from the connected store. Action: add tag `Customer - Purchased`. Live Flow Data as of the re-check: 0 contacts started / 0 in progress / 0 exited early / 0 completed — this automation has never actually processed a real contact since launch. Re-entry setting ("disabled") was not re-confirmed live this round — Mailchimp requires pausing a live automation to view/edit its entry filter, and pausing a live automation was judged out of scope for a read-only audit; this detail carries forward from the 2026-07-14 finding rather than a fresh check. This is the reusable Purchase Check module referenced by the Frozen Funnel Constitution. **Scope note**: triggers on any purchase, not Collection-only — directly observed, but not confirmed anywhere as the deliberate permanent rule; flagged for Andrew's decision.
- **Parent - Acquisition Funnel** (id 89, **Draft**): the canonical consolidated journey required by the Standard-plan architecture. Trigger configured and saved (tag added: Reluctant Reader Adventure Kit). Email 1 fully built (PDF-delivery body, subject "Your Free Adventure Kit Is Here!", preview text, "Download Your Free Chapter" button linked to the live PDF). Full live structure: Trigger → Email 1 → 2-day delay → Email 2 (body + CTA link, follows up on the Chapter 7 activity, links to `/reluctant-reader-adventure-kit/` with UTM params, no coupon) → 1-week delay → If/Else (`Tags → contact is tagged → Customer - Purchased`, confirmed configured and persisted) → **Email 3 ([PARENT_COUPON_CODE_SUPERSEDED]), built and verified 2026-07-15** on the non-purchaser branch (body, Subject "A little something for your next reading adventure", Preview "Use [PARENT_COUPON_CODE_SUPERSEDED] for 10% off an eligible Complete Collection." — all confirmed to survive a full page reload) → exit. Purchaser (Yes) branch exits directly.
- **Educators - Acquisition Funnel** (id 90, **Draft**): built 2026-07-15, toolkit-delivery rewrite 2026-07-16. Full structure: Trigger "Contact tagged Adventure Learning Toolkit" → Email 1 → 2-day delay → Email 2 → 1-week delay → If/Else (`Tags > contact is tagged > Customer - Purchased`, confirmed persisted) → Email 3 (Subject/Preview confirmed persisted, body references [EDUCATOR_COUPON_CODE_SUPERSEDED]) → exit. **Email 1 rewritten 2026-07-16** for real delivery: Subject "Your Adventure Learning Toolkit is ready", Preview "Download the classroom companion for Charlotte and Henry's Mariana Trench adventure.", body links the real staging PDF (`teacher_toolkit` key now set, no more placeholder URL). **Email 2 reviewed, not rewritten — now contradicts Email 1's delivered state** (still says "still putting the finishing touches on"; also references a non-existent "reading log"), flagged for Andrew/ChatGPT. See `MAILCHIMP_MANUAL_COMPLETION_REGISTER.md` for exact detail.
- **Gift Buyer - Acquisition Funnel** (**Draft**): built 2026-07-15. Same structure. Placeholder gift-guide URL. See register doc.
- **Retailer - Acquisition Funnel** (id 92, **Draft**): built 2026-07-15. Trigger "Contact tagged Wholesale Guide" → Email 1 (campaign 8118775) → 2-day delay → Email 2 (campaign 8118776) → 1-week delay → If/Else (Tags > contact is tagged > Customer - Purchased, confirmed persisted) → Email 3 (campaign 8118777, inquiry-only, no coupon, no fabricated wholesale terms) → exit. Placeholder packet URL (`[INSERT FINAL RETAILER INFORMATION PACKET URL BEFORE ACTIVATION]`).
- **Organization - Acquisition Funnel** (id 93, **Draft**): built 2026-07-15. Trigger "Contact tagged Community Reading Kit" → Email 1 (campaign 8118778) → 2-day delay → Email 2 (campaign 8118779) → 1-week delay → If/Else (Tags > contact is tagged > Customer - Purchased, confirmed persisted) → Email 3 (campaign 8118780, partnership/group-purchase inquiry, no coupon, no fabricated partnership/impact claims) → exit. Placeholder guide URL (`[INSERT FINAL ORGANIZATION GUIDE URL BEFORE ACTIVATION]`).
- **MT Lead Magnet** (Teacher funnel): Active, unaffected by any of the above.
- **Welcome new contacts**: one Draft (never activated), one Paused (5/0/5, historical).

**Full gap list, per audience, before any of the five can be activated: `MAILCHIMP_MANUAL_COMPLETION_REGISTER.md`.**

## Purchase-sync semantics (verified via plugin debug logs, not inferred)
The Mailchimp for WooCommerce plugin syncs a paid order to Mailchimp's Orders API (`addStoreOrder`) as soon as it reaches WooCommerce status **Processing** — not gated on "Completed." Confirmed directly: orders #374 and #336 (both still Processing, never Completed) both show a successful `order_submit.success :: addStoreOrder` log line within seconds/minutes of entering Processing. Order #336's log explicitly uses "financial status" terminology (`#336 has a financial status of pending and was skipped` → later `order_submit.success`), confirming the plugin tracks a payment-status field independent of WooCommerce's own order-status label.

**Role-exclusion finding:** orders placed while logged into WordPress as user #1 (Administrator) are skipped from Mailchimp sync entirely, regardless of order status — confirmed via `order_process :: Order #XXX skipped, user #1 user role is not in the list` on orders #317, #318, #319, #321, and **#322 (a Completed order)**. This is why the plugin's own dashboard "Orders synced" count is much lower than the real WooCommerce order count — it's a role filter, not a status filter. **Administrator-placed test orders must not be used as proof that the purchase-sync/tagging pipeline works for real customers** — the role exclusion means they never reach Mailchimp at all.

## Tag/merge-field mapping
Context-aware (`Source: Parent Popup` vs `Source: Parent Landing Page` vs `Source: Teacher Popup`, etc.) — check the `bhp_mailchimp_signup_tags` filter in `functions.php` before assuming a tag applies globally. Full current tag list (9 tags as of 2026-07-14): Adventure Club, Audience: Parent/Grandparent, Audience: Teacher/Librarian, Customer - Purchased (new), Mariana Trench Classroom Guide, Reluctant Reader Adventure Kit, Source: Mariana Popup, Source: Mariana Teacher Landing Page, Source: Parent Landing Page, Source: Parent Popup.

## Mailchimp automation-builder UI notes (for future sessions)
The flow-builder Actions/Rules palette is **drag-and-drop only** — clicking a palette item does not add it to the map (this caused repeated apparent "stuck" states across multiple sessions before being root-caused 2026-07-14). Use a drag gesture from the palette item onto the canvas insertion point. Editing a **live** (non-Draft) automation requires pausing it first; Mailchimp's own pause dialog confirms contacts mid-delay are not disrupted and delay timers keep counting while paused.

**2026-07-14 finding, resolved 2026-07-15:** dragging a palette item onto the "+" node between an already-populated step and the next node had previously failed to register across 10+ attempts. **Retried 2026-07-15 in a fresh session and it worked immediately** — a Time Delay and an If/Else node were both successfully dragged onto "+" nodes between existing populated steps (after Email 2, and after the second delay). No technique change was needed beyond a fresh page load. Treat the earlier finding as environment/session-state-dependent, not a permanent platform limitation — if it recurs, try a fresh `navigate()` to the same URL before assuming it's blocked again.

**2026-07-14 findings, corrected 2026-07-15.** The 2026-07-14 session reported two silent-persistence-failure bugs (Subject/Preview Text and If/Else segment conditions reverting to empty on reload). **2026-07-15 re-testing across five separate journey builds (Gift Buyer, Retailer, Organization, Educators, and a retroactive fix to Parent) found both fields persist correctly and reliably** when: Subject/Preview Text is saved via the panel's own "Save" button followed by "Close" (not just navigating away), and the If/Else condition's tag value is selected from the dropdown suggestion that appears after typing (not left as raw unconfirmed text) before clicking "Use segment," with the node reopened afterward to visually confirm the condition label rendered. Every instance built this way survived a full page reload. The 2026-07-14 "bug" is now understood to have most likely been a coordinate-mis-click or a save-before-suggestion-resolved race in that session, not a genuine Mailchimp platform limitation — treat it as resolved, not open, going forward.

## Funnel isolation (hard rule)
Parent and Teacher funnels must stay fully isolated — separate storage keys, separate analytics event prefixes, never both rendering on the same page. Full detail: `.claude/rules/funnels.md`.

## Not yet done
- Get Andrew's/ChatGPT's decision on new Email 2 copy for Educators — its "still finishing" premise now contradicts Email 1's "ready, here's the download" messaging (see `KNOWN_ISSUES.md`).
- Get Andrew's decision on whether "any purchase suppresses" (the current live behavior) is the intended permanent rule, or whether it should be Collection-only.
- Replace all placeholder lead-magnet/guide/packet URLs across Gift Buyer, Retailer, and Organization journeys before any activation (**Educators is done** — real PDF live, Email 1 links it) — see `MAILCHIMP_MANUAL_COMPLETION_REGISTER.md`.
- Andrew's page-approval + production deploy for the Gift Buyer, Retailer, Organization, and Educators landing pages (Gift Buyer/Retailer/Organization still staging-only "Coming Soon"; Educators now shows its real, working signup form).
- Run an end-to-end purchaser-suppression test — assessed this session as **not currently safely performable**: needs either a dedicated non-admin test account + an authorized test-payment method, or explicit authorization to create a manually-set-to-Processing test order via WP-CLI. See the register doc's "End-to-end suppression test" section for the exact ask.
- Build post-purchase follow-up automations (single-book, Collection) — confirmed not built for any audience; a technical gap specification was written this session (see the register doc) but several sub-decisions still need Andrew (follow-up timing, review-request wording/platform, refund handling, audience-attribution scheme).
- Determine whether the `Customer - Purchased` tag is ever removed after a refund/cancellation — not verified, working assumption is that it is permanent.
- Migrate the 3 real contacts mid-delay in the old paused `Coupon Flow` (id 86) into the new Parent journey before retiring the old flow.
- **Mailchimp deliverability audit** — flagged as a future planned phase, not started, awaiting Andrew's explicit go-ahead.

## Removed, do not reintroduce
The Teachers-page `the_content` filter that appended a redundant lead-magnet CTA panel — removed in commit `43fdf8a`, a real signup form already existed on that page.
