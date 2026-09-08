# PREPARED, NOT APPLIED — `brave-hearts-theme\docs\CHANGELOG.md`

**Consolidated.** This supersedes and replaces every per-round CHANGELOG block in
the CYCLE179-LD-REVIEW-SEQ deliverable (round 11 §8b, round 12 §12, round 13 §11a,
round 14 §15a, R15.11a, R16.11a, R17.13a, R18.7a). Apply **only** what is between
the fences below. Nothing else from those rounds is applied.

**Where it goes:** `brave-hearts-theme\docs\CHANGELOG.md`, as the new **top entry**,
immediately under the three header lines (`# Changelog — Brave Hearts Publishing`,
the blank line, and the "Major milestones only…" line) and **above** the
`## 2026-09-05 - STAGING CANDIDATE THEME 1.19.371 …` entry.

**Two housekeeping notes for whoever pastes it:**

1. The three existing staging-candidate entries for `1.19.369`, `1.19.370` and
   `1.19.371` are **left in place, unedited**. They are correct for the moments
   they describe. This entry supersedes them on the version number only, and says
   so in its own first line.
2. **Public-repo safe as written:** no agent aliases, no `Business OS` paths, no
   customer names, addresses, emails or order contents. Two bare WooCommerce order
   ids appear nowhere in this entry. Do not add any while editing.

---

```markdown
## 1.19.362 – 1.19.380 — 2026-09-05 — the review-ask sequence and the school-visit email set

Shipped as one release; supersedes the `1.19.369`, `1.19.370` and `1.19.371`
staging-candidate entries below on the version number only. Nineteen internal
builds, one customer-visible change set.

**The review-ask sequence (new).** A three-touch Amazon review ask driven by
approved copy sets, with a separate web lane. Copy sets carry their own
`delay_days` and an `approved` flag, and the engine refuses to render an
unapproved or incomplete set rather than sending a half-merged letter. The
sequence is OFF by default: `bhp_review_ask_enabled` is unset and must be set
deliberately.

**The school-visit fork of the completed-order email (day 0).** An order carrying
`_bhp_school_visit_slug` gets a body written for a book handed to a child at a
read-aloud, not a posted parcel. The three sentences that describe a shipment
("left our print partner", "no tracking number from our printer", "if anything
arrives damaged") are false for a hand-delivered order and are replaced wholesale
rather than edited around. Every ordinary order email is byte-identical to
1.19.361.

**Copy and voice.**
- Standing voice rule enforced across the whole set: no "we/us/our" in
  customer-facing words. The suites test it with word boundaries so "week",
  "answers" and "however" cannot produce a false failure.
- No em dashes anywhere in the copy.
- Quoted third-party words are never re-pronouned. Rewriting a "we" inside a real
  customer review would fabricate a customer statement.
- One greeting per email. The template's own "Hi %s," is suppressed where the
  approved copy opens with its own.
- One sign-off per email: the signature block (Andrew Signore / Author | Brave
  Hearts Publishing / Big Places. Brave Hearts.), shared by the review ask and the
  day-0 email from a single function so the two cannot drift apart. The plain
  sign-off and plain tagline are removed from every copy set, superseded lines
  preserved in place.

**Every book on the order is named.** The review ask's `{BookTitle}` slot resolved
to the first chapter book on an order, so a parent who bought three books read a
sentence naming one. It now resolves to all of them, joined as a natural list
("The Mariana Trench and Mount Everest"; "The Mariana Trench, Mount Everest and
The Amazon") by the same join the day-0 email already used — extracted to one
function so the two emails cannot describe the same order differently. The
five-star row still rates one book, because one review page exists per title, so
its caption names that book through a separate `{FirstBookTitle}` slot rather
than listing books the row cannot rate. Both slots are send gates: an order that
cannot resolve either is declined rather than mailed with a gap in the sentence.
A single-book order renders exactly as it did before.

**The receipt reads on a phone, and the sentence agrees with itself.** The
hand-delivery row of the day-0 receipt printed the pickup method name in both of
its cells; the heading now says "Hand delivery:" and the name is printed once,
beside it. On a narrow screen the order table was breaking words in half —
"Quantity", "Price" and "$11.99" each split across two lines — because a
mid-word break had been applied to every cell; it is now applied only to the
product-name cell, and the quantity and price columns hold their width. And a
parent who bought two or three books no longer reads a sentence that disagrees
with itself: "why The Mariana Trench and Mount Everest are built the way they
are". A one-book order is unchanged, word for word.

**Rendering.**
- A charset is declared on every email and on the mailer object, so a Unicode
  star row and typographic punctuation survive the wire.
- The star row is built from Unicode rather than images, with an accessible
  label, resting in pale gold `#dfc793` and filling to bold gold `#c4a15c`.
- The empty `<h1>` band above the hero is removed at the stage that renders it.
  An email that has a heading still gets one, unchanged, including every ordinary
  WooCommerce email in the store.
- Order and downloads tables are made readable at 375px: left-aligned cells,
  wrapped text, column headings kept, and no table wider than the viewport.
- The hand-delivery row in the day-0 order summary shows the approved pickup
  label alone, left-aligned, instead of the pickup name twice followed by the
  checkout's forty-word explanation. Ordinary order emails are untouched.

**Photographs.**
- Hero photographs are mapped per visit slug through one filterable table,
  `bhp_review_ask_hero_map()`. Dallas Harris (2026-09-03) and Adams (2026-08-28)
  each map a wide-room frame to day 0 and a reading frame to touch 1.
- Any unmapped visit, and the whole web lane, gets the general hero,
  `BHP_EMAIL_GENERAL_HERO` — a single constant a `wp-config.php` define can
  override. A photograph captioned for a school a family never attended is a false
  statement in a picture, which is why the fallback is general rather than the
  nearest visit.
- Touch 2 carries no photograph.
- Every hero has alt text that describes the scene and repeats any baked caption.
  No child is named, no reaction is described, and a hero file that is not on disk
  renders nothing rather than a broken-image icon.
- One gallery frame is asserted absent from the theme: it shows a second adult
  whose consent is not on record and a legible visitor badge.

**Suites.** `tests/test-cycle179-review-seq.php`,
`tests/test-cycle169-review-ask.php` and `tests/test-visit-completed-email.php`.
The visit suite writes nothing to the database: orders are built in memory and
never saved, and no email is sent, enabled or triggered, so it is safe to run on
any environment. ⚠ The figures below are the 1.19.379 staging run; the 1.19.380
section has not been run yet and its 24 assertions are NOT inside these counts.
At 1.19.379 on staging: review-seq 615 pass / 0 fail / 1 skip
(the skipped assertion needs a render of this build on disk and says so rather
than passing silently), cycle169 131 pass / 0 fail, the visit-email suite green,
and the ship-prep suite carrying only its two pre-existing version pins. Every
PHP file in the deploy artefact — 327 of them — was syntax-checked out of the ZIP
on the server before the theme was installed.

**Two build defects were caught on staging and are recorded rather than tidied
away.** (1) 1.19.378 shipped a PHP parse error: three prose apostrophes inside a
198-line single-quoted CSS string in `inc/transactional-emails.php`, the first of
which closed the string. `wp theme install --force` deletes the theme directory
before extracting, so there was no theme left to fall back to and staging returned
HTTP 500 until 1.19.379 replaced it. Production was never touched. The control
that would have caught it — linting every PHP file out of the ZIP **before**
install — is now a required step in `docs/RUNBOOK.md`. (2) The `source-md5`
recorded in `style.min.css` did not match the `style.css` inside the artefact, and
the cause was not a stale build: `git archive` was writing CRLF into every text
file (one byte per line, 18,425 lines, exactly the size difference). The canonical
build command in `docs/RUNBOOK.md` now carries
`git -c core.autocrlf=false -c core.eol=lf archive`, and the artefact is verified
LF-only with the two md5s equal.

1.19.379 carries no functional change. It repairs the parse error introduced in
1.19.378 and corrects the deploy artefact to LF line endings, so the `source-md5`
recorded in `style.min.css` again matches the `style.css` that actually ships.

**1.19.380 — two gates that decide who the first real run may write to.** A dry
run of the sequence against real completed orders showed that switching the
engine on would have mailed a year of backlog on its first morning alongside the
orders it was built for, and would have sent a reminder chasing a first ask a
retired engine had sent.

- **A backlog floor.** `BHP_REVIEW_ASK_FLOOR_DATE` is a single constant,
  filterable and overridable from `wp-config.php`. An order whose touch-1 anchor
  — the visit date in the visit lane, the completion date in the web lane — falls
  before it is declined `before_floor` and enters neither touch. The floor reads
  the same anchor the schedule reads, so the two cannot disagree about an order.
  An order with no resolvable anchor is still declined `no_anchor`, not
  `before_floor`, so a data problem cannot hide behind a policy decision.
- **A reminder can only follow this engine own first ask.** Touch 1 now writes a
  ledger key that only this sequence writes, and touch 2 requires it. A touch-1
  stamp left by the retired 21-day engine, or by the hand-send migration,
  declines `legacy_touch1` instead of producing a reminder for a letter this
  sequence never sent.
- **Both are visible before they act.** `wp bhp review-ask plan` and
  `wp bhp review-ask dry` print the floor in force, name every order either gate
  declines with its resolved anchor, and end with one summary line of counts by
  reason.

The suite gains a section covering both gates in both lanes: fixtures either side
of a filtered floor, the boundary asserted on the anchor date itself and one day
later, a legacy-stamped fixture declining and then allowed once the ledger key
exists, and the plan output asserted for the floor and summary lines.
```

---

## What is deliberately NOT in this entry

- **No production claim.** The entry does not say the release is on production,
  because at the time this file was written it was not. If it ships, add the
  standard `PRODUCTION IS NOW THEME …` heading line in the same sitting, per the
  house pattern used by the 2026-09-04 and 2026-09-03 entries.
- **No email-client claim.** Nothing in the entry asserts how the star row, the
  hover fill or the hero render in Gmail, Outlook or Apple Mail. Those are
  reviewer observations, and the RUNBOOK gates are where they belong.
- **No open-decision resolution.** The `table-layout: fixed` deviation, the
  hand-delivery sentence still printed twice in the totals cell, and the day-0
  plural grammar adjustment to approved founder copy are open items, not release
  notes. They belong in `docs/KNOWN_ISSUES.md` or the decisions register, not here.
