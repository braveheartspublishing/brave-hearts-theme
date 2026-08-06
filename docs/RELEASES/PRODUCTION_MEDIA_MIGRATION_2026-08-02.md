# Production media migration — the "Look Inside" gallery set — 2026-08-02

> ## ⚠️ THIS RECORD WAS WRITTEN AFTER THE FACT, ON 2026-08-02, BY A LATER SESSION
>
> **The session that performed the migration was terminated by a connection loss before it could write its release record.** This file is the reconstruction the release closeout requires, and it is built from two sources, both named: **(a)** live verification performed by the author of this file, over SSH and over HTTP, against the production site itself; and **(b)** the pre-write gate log that the migrating session recorded in the writer-lock convention before it was terminated.
>
> ⛔ **Every claim below is marked `VERIFIED LIVE` (with how, by this session) or `FROM THE MIGRATING SESSION'S OWN GATE LOG` (i.e. read from a record, not re-observed).** Nothing is asserted from memory, and no verification is reported that was not run.
>
> **This record supersedes nothing.** It fills a gap. The deploy it follows is recorded in `PRODUCTION_RELEASE_1_19_142.md` and `TRUST_AND_CONTENT_CORRECTIONS_1_19_142.md`.

**Date:** 2026-08-02 · **Environment:** production · **Type:** media-library import only · **Theme/plugin versions:** unchanged by this work (1.19.142 / 1.8.10)

---

## 1 · Why this migration existed

The 1.19.142 production release shipped with **one acceptance criterion failed, and reported as a failure rather than absorbed into its success count**: the "Look Inside" galleries rendered on staging but **not** on production, because **0 of 29** gallery media slugs existed in the production media library.

`inc/book-media.php` resolves its registry by slug and **fails closed** — an unresolved slug renders nothing at all rather than an empty frame. Production therefore showed no gallery section, no broken image and zero console errors, and looked exactly as it had at 1.19.121. **That was never a regression and no rollback was warranted.** It was tracked as `CYCLE141-LD-1`.

The media had been deliberately withheld from the earlier deploy, because storefront use of that image set is a creative approval that belongs to the owner, not an engineering decision.

## 2 · The approval this ran on

The migration proceeded on the owner's explicit approval, recorded at the time with its provenance: he reviewed the artefacts and **knowingly approved retaining the current Mariana images temporarily**, with the authentic reshoot **queued** in `docs/ROADMAP.md` rather than treated as done.

⛔ **That approval reached the migrating session by relay, not first-hand, and its own record says so.** This file does not upgrade a relayed approval into a witnessed one. The corroboration the migrating session recorded, and which is independently checkable in this repository, is `docs/ROADMAP.md` ("Authentic Mariana interior reshoot and gallery replacement", status **QUEUED**) and `docs/CURRENT_TASK.md`, which notes that Everest shares the same root cause and belongs in the same queue.

⚠️ **One precision, preserved rather than smoothed over:** the ROADMAP entry is titled Mariana-only. **Widening its scope to Everest is the owner's call and was deliberately not performed.**

## 3 · Pre-write gates — FROM THE MIGRATING SESSION'S OWN GATE LOG

These were recorded before anything was imported. They are quoted here because they are the reason the import was safe; **they were not re-run by this session.**

| Gate | Result |
|---|---|
| 24 target slugs checked against production (`wp post list --post_type=any --post_status=any --name=<slug>`) | **0 hits, all 24** |
| Broad SQL sweep, `post_name LIKE '%-look-%'` on production | **none** |
| 24 target file basenames under production `wp-content/uploads` | **0 hits** |
| Registered image subsizes, both environments | **identical, 11 each** |
| Production MIME allowlist | `mp4`, `webm` both permitted |

**Production was clean, so no rename, no `-1` suffix and no deletion was required.** That gate exists because a WordPress `-1` slug suffix silently breaks slug-based resolution — the gallery would have failed closed again, for a new and much harder-to-find reason.

**Pre-write backup declared and taken:** `~/bhp-PROD-backup-media-20260802/` — full database dump, attachment manifest, and a product commerce-guard snapshot.

## 4 · What was written

- **24 new attachment records**, plus their files and derivatives, imported with the exact slugs the registry resolves, with `alt` and `title` carried over from staging.
- `wp media regenerate` limited to those 24 new attachment IDs.
- `wp sg purge` on production.

⛔ **Not touched:** the 122 attachments already present on production were not modified, renamed or deleted. No theme or plugin file. No WooCommerce product, variation, price, stock, SKU, coupon, shipping, tax, payment or checkout field. Staging was read-only throughout.

## 5 · Verification — VERIFIED LIVE BY THIS SESSION, 2026-08-02

This is the part that was missing, and it is the reason this file is worth writing rather than backdating a claim.

### 5.1 Attachment count — `VERIFIED LIVE` (SSH, `wp post list --post_type=attachment --format=count`)

**Production: 146.** The migrating session's own Gate 2 recorded production at **122** before the import. **122 + 24 = 146.** The arithmetic closes exactly, which is independent corroboration that 24 attachments landed and that nothing else was added or removed.

### 5.2 Rendered gallery parity — `VERIFIED LIVE` (HTTP GET, logged-out, Chrome 140 UA, both environments, this session)

Counted from the served markup as `data-bhp-slide=` occurrences and the component's own `data-bhp-gallery-count`:

| Page | Production | Staging | Match |
|---|---|---|---|
| `/complete-collection/` | **9 slides**, `count="9"` | **9 slides**, `count="9"` | ✅ |
| Mariana Trench paperback | **7 slides**, `count="7"` | **7 slides**, `count="7"` | ✅ |
| Mount Everest paperback | **8 slides**, `count="8"` | **8 slides**, `count="8"` | ✅ |

**Full parity. `CYCLE141-LD-1` is closed on live evidence, not on a report.**

### 5.3 Active theme and plugin — `VERIFIED LIVE` (SSH, `wp theme list --status=active` / `wp plugin list`)

Production **theme 1.19.142**, **plugin 1.8.10** — i.e. **unchanged by this migration**, which is correct: this work imported media and deployed no code.

### 5.4 ⭐ A discrepancy that a prior record left OPEN is now CLOSED — and it was never a discrepancy

The changelog entry for this work recorded, honestly and correctly at the time, that a direct HTTP GET of `/wp-content/themes/brave-hearts-theme/style.css` returned a file whose header read **`Version: 1.14.2`**, while every other signal said the active theme was 1.19.142. It named the definitive check — `wp theme list --status=active` over SSH — as the outstanding action, and refused to explain the difference away. **That was the right call.**

**The definitive check has now been run, and the explanation is mundane: the URL that was fetched is a different theme directory.**

`VERIFIED LIVE` on production, this session:

```
active theme:                                          brave-hearts-theme-deploy-explorer-expedition-guides  1.19.142
wp-content/themes/brave-hearts-theme/style.css                                                       1.14.2
wp-content/themes/brave-hearts-theme-deploy-explorer-expedition-guides/style.css                     1.19.142
get_stylesheet() | wp_get_theme()->get('Version')      brave-hearts-theme-deploy-explorer-expedition-guides | 1.19.142
```

`brave-hearts-theme` is a **legacy, inactive theme directory that genuinely is at 1.14.2** and appears in `wp theme list` as inactive. It is not stale caching and not a deploy failure — it is a different directory that was never removed. **The active slug is `brave-hearts-theme-deploy-explorer-expedition-guides`, and that name is load-bearing** (see `CLAUDE.md`): a ZIP whose top-level prefix does not match it installs as a new, inactive theme instead of replacing the live one.

**Practical lesson worth keeping:** never read a theme version from a guessed `/wp-content/themes/<name>/style.css` URL. Read it from `wp theme list --status=active`, or from `wp_get_theme()->get('Version')`. This closes `CYCLE141-OPS-009`.

## 6 · A prior QA artefact that must not be quoted as the outcome

`screenshots-2026-08-02-wave5/qa-results.json` (held outside this repository) records **broken gallery images on production**. It was captured **mid-migration**, before the media set finished landing. The live checks in §5 were taken afterwards and supersede it.

It was **retained rather than corrected**, and the ordering is stated here so that a future reader does not mistake a mid-flight snapshot for a final result.

## 7 · Rollback

**This release has only one half — a content/media half. There is no code half**, and that is stated explicitly rather than left to be assumed, because conflating the two is the most expensive trap in this project's release procedure.

- **Media half:** `~/bhp-PROD-backup-media-20260802/` (database dump + attachment manifest, taken before any write). The 24 imported attachments are individually identifiable by slug and by their `2026/08` upload path, so a reversal is a targeted delete of a known ID set, not a restore of the whole library.
- ⛔ **Reverting this migration would return production to rendering no galleries at all** (fail-closed), which is the state 1.19.142 shipped in. It would not break the site. It is done only to correct a mistake in the import, never as collateral to a code rollback.
- **No theme or plugin rollback is implied by this record**, because no theme or plugin file was written.

## 8 · Not done

- ⛔ No theme or plugin deploy of any kind. Production versions unchanged.
- ⛔ No push, PR or merge.
- ⛔ No WooCommerce product, variation, price, stock, SKU, coupon, shipping, tax, payment or checkout field written.
- ⛔ No existing production attachment modified, renamed or deleted.
- ⛔ No write of any kind to staging — it was the read source only.
- ⛔ `docs/ROADMAP.md`'s reshoot entry **not** widened from Mariana to Everest — the owner's call.
- ⛔ The authentic Mariana interior reshoot itself is **queued, not done**. The images now live on the storefront are the AI-assisted set the owner knowingly approved as temporary, **two of which carry visible text artefacts**. That is a creative debt this record deliberately does not hide.
