# PRODUCTION RELEASE — theme 1.19.157 · the Bookvault dispatch tracker

- **Released to production:** 2026-08-03
- **Theme:** 1.19.156 → **1.19.157** · **Bundle plugin:** 1.8.16 (unchanged)
- **Commits:** `652da0f` (the tracker) · `f6a781e` (CLI synopsis + integration-suite fixture)
- **Branch:** `feature/bookvault-dispatch-tracker-1.19.157`
- **Ships in:** ⚠️ **DRY MODE.** The tracker writes **no** meta, **no** order note and **no** status
  change until it is explicitly switched live.
- **Current production version — VERIFIED LIVE 2026-08-03:** theme **1.19.157**, plugin **1.8.16**.

---

## ⛔ PROVENANCE

⛔ **This record was not written by the agent that built the tracker, nor by the one that deployed
it.** It is assembled by `business-ops-knowledge` from the builder's own commit messages (quoted
verbatim), `git show --stat`, the operations register, and this session's own live verification.
⛔ **A prepared release report from the building session does not exist** — both `lead-developer`
working-draft folders were listed in full and neither contains a tracker report.
**No QA step is claimed here that is not evidenced.**

---

## 1 · Why this exists

**E2, the "Your books have shipped" email, is Variant A.** It is a true sentence **only** under an
operating rule — mark the order complete after dispatch — that a person has to remember to follow.

⭐ **The tracker is what turns that rule into a fact.** In the builder's own words:

> ```
> Scheduled checker that polls Bookvault's v3 API for orders WooCommerce
> still has in processing, and completes them ONLY on an unambiguous
> dispatch signal - which is what makes E2's 'Your books have shipped' a
> true sentence rather than an operating promise somebody has to remember.
> ```

---

## 2 · What shipped — the builder's own description, verbatim from `652da0f`

> ```
> - inc/bookvault-tracker.php: 3-hourly WP-Cron poll of GET /v3/Order by
>   PodRef (the plugin's own BVRef order meta), a pure evaluate() state
>   machine, evidence order-note + tracking meta, and the completed
>   transition that fires E2. Ships in DRY mode: writes no meta, no note
>   and no status until switched to live.
> - Reads Progress.Status and never Order.Status - two fields, same name,
>   only one of them is fulfilment.
> - Requires strict IsDispatched === true AND a real Dispatched timestamp
>   AND a post-dispatch Progress.Status. Any error, missing field, unknown
>   value or self-contradiction logs, skips and retries next run.
> - Two idempotency guards run BEFORE any API call, so a completed order
>   is never re-transitioned or re-emailed.
> - Kill switch bhp_tracker_enabled, dry-run option and filter, WP-CLI
>   'wp bhp-tracker run|status|log'.
> - Records the four Deck 3.2 Variant B fields on the order without
>   changing any customer-facing copy. Variant B itself stays unbuilt.
> - Never logs, echoes or stores the API credential.
> - tests/: 80+ state-machine assertions plus an end-to-end integration
>   suite with the API mocked and outbound mail short-circuited.
> ```

⭐ **Three design choices worth carrying forward, because each prevents a specific customer-visible
failure:**

1. **`Progress.Status`, never `Order.Status`** — *"two fields, same name, only one of them is
   fulfilment."* Reading the wrong one would announce shipping that had not happened.
2. **Three independent conditions required** — `IsDispatched === true` **and** a real `Dispatched`
   timestamp **and** a post-dispatch `Progress.Status`. **Any ambiguity logs, skips and retries.**
   ⭐ **The failure mode is "do nothing", never "email the customer anyway."**
3. **Idempotency guards run BEFORE the API call**, so a completed order is never re-transitioned or
   re-emailed.

### Files changed

**`652da0f` — 5 files, +2,086 / −1**

| File | Δ |
|---|---|
| `inc/bookvault-tracker.php` **(NEW)** | +1,068 |
| `tests/test-bookvault-tracker.php` **(NEW)** | +544 |
| `tests/test-bookvault-tracker-integration.php` **(NEW)** | +464 |
| `functions.php` | +9 |
| `style.css` | 2 |

**`f6a781e` — 2 files, +42 / −4.** The builder's own account:

> ```
> - WP-CLI parses a second ': ' continuation line in an OPTIONS block as
>   synopsis tokens, which produced ten 'invalid synopsis part' warnings on
>   every 'wp bhp-tracker run'. Each option description is now one line.
> - The integration suite halted at the no-credential guard on staging
>   (correct behaviour, wrong for the test), so 23 assertions could never
>   reach the code they targeted. It now proves the no-credential halt as
>   its own case, installs a placeholder for the mocked-HTTP cases, and
>   restores the environment's original credential state in 'finally'.
> ```

⭐ **The second item is the better find: a test suite that was silently passing without reaching
23 of its own assertions.** A guard doing exactly the right thing in production was doing the wrong
thing to the tests, and the suite reported success either way.

---

## 3 · Deployment and arming — the three-step sequence

| Step | What happened | Basis |
|---|---|---|
| **1** | Bookvault **v3 API key generated** by the owner in the vendor portal | Operations register, 2026-08-03 |
| **2** | **1.19.157 deployed to production** — backup taken first, artefact md5-verified, `php -l` clean. Post-deploy tracker status: **dormant / dry / no-credential**, which is the correct state for a build shipped without its credential | Operations register |
| **3** | ⭐ **Credential installed by the owner, in the owner's own terminal.** First authenticated live read: **both open orders returned `SentToPrint`**, `examined=2 skipped=2 errors=0` | Operations register |

### Credential handling — recorded because it is the standard, not an aside

⛔ **The API credential was generated by the owner, installed by the owner, and never entered a
chat session.** The code itself *"never logs, echoes or stores the API credential."*
⛔ **No agent holds it. This record's author neither holds it nor looked for it.**

### Current operating state

- **Polls every 3 hours in DRY mode.** ⛔ **It changes nothing.**
- **~2026-08-11 to 08-12**, the log is expected to flip to `DISPATCH` at the two real open orders
  (`BV2845712` / #417 and `BV2848396` / #493) — **the live-fire test.**
- **Flip-live is a separate, supervised act at that test.** It is not scheduled and not automatic.
- **The manual fallback remains intact**, which is what the commissioning instruction required.

### Rollback path

| Layer | Action |
|---|---|
| **Fastest — no deploy** | Kill switch `bhp_tracker_enabled`, or the dry-run option/filter. ⭐ **Because it ships DRY, the tracker is already in its safe state; no rollback is needed to make it harmless.** |
| **Full** | `wp theme install --force` the retained 1.19.156 artefact, then `wp sg purge` |

---

## 4 · Verification — per claim, with the gaps named

| Claim | Basis |
|---|---|
| **Production is serving theme 1.19.157 and plugin 1.8.16** | ⭐ **VERIFIED LIVE 2026-08-03** by HTTP against the production home page (HTTP 200): **11 theme assets enqueued at `ver=1.19.157`**, 4 plugin assets at `ver=1.8.16` |
| Worktree version | ⭐ **VERIFIED** — `style.css` reads `Version: 1.19.157` |
| Branch and HEAD | ⭐ **VERIFIED** — `feature/bookvault-dispatch-tracker-1.19.157` at `f9f5c7e`; `git status --porcelain` **empty** |
| Both branches and the documentation commit are on origin | ⭐ **VERIFIED** — pushed on the owner's explicit instruction |
| API payload shape and both orders at `SentToPrint` | **Owner-executed, reported through the operations register.** ⭐ This closed the build's unverified-payload gap: the tracker had until then never been run against the real API |
| 80+ state-machine assertions; integration suite | **Builder-reported** in the commit messages |

### ⛔ NOT VERIFIED — stated rather than implied

- ⛔ **The tracker has never completed an order and has never caused an email to be sent.** The one
  authenticated read observed both orders at `SentToPrint`, which is **pre-dispatch**.
- ⛔ **The dispatch path itself is untested against real data.** That is exactly what the 8/11–8/12
  live-fire test is for.
- ⛔ **`wp theme list --status=active` over SSH was NOT run by this record's author**, who holds no
  SSH credentials. The HTTP enqueue-version check is strong live evidence, **not** the definitive
  instrument.
- ⛔ **No WP-Cron scheduling was independently observed by this record's author.**

---

## 5 · ⚠️ Identifier warning — do not close an unrelated issue

The operations register describes the authenticated read as closing an issue identified as
`CYCLE142-LD-16`. ⛔ **That identifier is already in use, by the capstone technical audit, for a
homepage image-weight defect that is still OPEN and is described there as the largest remaining
mobile performance win.** `LD-17`, `-18` and `-19` are likewise image items in that audit.

⛔ **Do not mark any of those four closed on the strength of this release.** The collision is
registered and routed; it is **not** resolved here, and this release record attaches **no `LD`
number** to the tracker's payload verification.

---

## 6 · Boundaries

⛔ Ships **DRY** — no meta, note or status change · **no WooCommerce product, price, coupon,
promotion, stock, shipping, tax, payment or checkout mutation on any environment** · no customer
email caused by this release · the credential never entered a chat or any file in this repository.

---

*Recorded 2026-08-03 by `business-ops-knowledge`. Indexed in `AI_CONTEXT_INDEX.md`. Companion
record: `PRODUCTION_RELEASE_1_19_156.md`. Live-fire test window: ~2026-08-11 to 08-12.*
