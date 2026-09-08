# PREPARED, NOT APPLIED — `brave-hearts-theme\docs\RUNBOOK.md` (+ a two-line `PROJECT_STATE.md` note)

**Consolidated.** This supersedes and replaces every per-round RUNBOOK block in the
CYCLE179-LD-REVIEW-SEQ deliverable (round 11 §8b, round 12 §12, round 13 §11b,
round 14 §15b, R15.11b, R16.11b, R17.13b, R18.7b, R18.7c). Apply **only** what is
in the three parts below. Nothing else from those rounds is applied.

Three separate edits, in this order:

| # | File | What |
|---|---|---|
| 1 | `docs\RUNBOOK.md` | **Replace** the review-ask section wholesale (Part 1) |
| 2 | `docs\RUNBOOK.md` | **Correct** the canonical deploy-ZIP build block (Part 2, R18.7c) |
| 3 | `docs\PROJECT_STATE.md` | **Add** a two-line superseding note (Part 3) |

Public-repo safe as written: no agent aliases, no `Business OS` paths, no customer
names, addresses, emails or order contents.

---

# PART 1 — replace the review-ask section

**Exact replacement boundary.** In `docs\RUNBOOK.md`, delete from the line

```
## Review-ask engine — staging QA and the production go-live gates (1.19.371)
```

through the **end of its subsection E** (the last line is
`order meta keys plus its own options; nothing else.`), and paste the block below
in its place. Nothing above or below that range changes in Part 1.

⛔ **`<slug>` below is `brave-hearts-theme-deploy-explorer-expedition-guides`.
`<doc_root>` is the environment's WordPress root.**

---

```markdown
## Review-ask sequence and school-visit emails — staging QA and the production go-live gates (1.19.380)

⛔ **The engine sends nothing until `bhp_review_ask_enabled` is `yes`. That option
flip is Andrew's, and it is the only irreversible step in this document** — an
email that has gone to a parent cannot be recalled. Everything above it is
reversible.

`<slug>` is `brave-hearts-theme-deploy-explorer-expedition-guides`.
`<doc_root>` is the environment's WordPress root.

### A. Staging QA, in order (nothing here can email a customer)

⛔ **Step 0 is not optional and it is not new advice.** `wp theme install --force`
deletes the theme directory before it extracts, so a PHP parse error inside the
ZIP is an immediate HTTP 500 with no theme left to fall back to. That is exactly
what 1.19.378 did to staging.

```
# 0. LINT EVERY PHP FILE OUT OF THE ZIP, ON THE SERVER, BEFORE INSTALLING IT
mkdir -p ~/zipcheck && cd ~/zipcheck && rm -rf ./*
unzip -q /path/to/brave-hearts-theme-1.19.380-review-seq.zip
find . -name '*.php' -exec php -l {} \; | grep -v "No syntax errors"
#    MUST PRINT NOTHING. If it prints anything, STOP — do not install.
find . -name '*.php' | wc -l                       # expect 327 at 1.19.380

# 0b. the artefact's own line endings and CSS stamp (see the build section)
tr -cd '\r' < <slug>/style.css | wc -c              # MUST be 0
md5sum <slug>/style.css                             # MUST equal the "source-md5:"
grep -m1 'source-md5' <slug>/style.min.css          #   line in style.min.css

# 1. install the candidate
wp theme install /path/to/brave-hearts-theme-1.19.380-review-seq.zip --force
wp theme list --status=active                       # must show <slug> at 1.19.380
wp sg purge

# 2. fatal check
wp eval 'echo "ok";' --user=1

# 3. the three suites
wp eval-file wp-content/themes/<slug>/tests/test-cycle179-review-seq.php --user=1
wp eval-file wp-content/themes/<slug>/tests/test-cycle169-review-ask.php --user=1
wp eval-file wp-content/themes/<slug>/tests/test-visit-completed-email.php --user=1
#    A SKIP is not a PASS. The rendered-HTML assertions skip until a render of
#    THIS build is on disk; the summary counts them separately for that reason.

# 4. the schedule, read-only, on each of the four launch dates
wp bhp review-ask dry --as-of=2026-09-10
wp bhp review-ask dry --as-of=2026-09-11
wp bhp review-ask dry --as-of=2026-09-13
wp bhp review-ask dry --as-of=2026-09-14
wp bhp review-ask status

# 5. one rendered email per set, into a mailbox somebody will actually open
wp bhp review-ask test-send --to=<address> --set=day0   --order=<visit order id>
wp bhp review-ask test-send --to=<address> --set=touch1 --order=<visit order id>
wp bhp review-ask test-send --to=<address> --set=touch2 --order=<visit order id>
wp bhp review-ask test-send --to=<address> --set=web1   --order=<web order id>
```

`test-send` refuses to run anywhere but staging (the check is on `home_url()`,
not on `--url`), refuses without one valid `--to`, and refuses an unapproved set.

⚠ **Expect one large, benign byte diff on the first install of 1.19.379.** The
artefact is LF; earlier candidates on staging were CRLF. Nearly every text file
will report as changed. That is the line-ending correction landing once, toward
parity with production, not a regression. ⭐ Installing **1.19.380 over an
installed 1.19.379** must NOT reproduce it: both artefacts are LF, so the diff
should be confined to the files 1.19.380 actually changed
(`inc/review-ask-email.php`, `tests/test-cycle179-review-seq.php`, `style.css`,
`style.min.css`). A wide diff on that install means the build flags were dropped
again.

### 0. Before anything else is judged
0a. `wp theme list --status=active` reports version **1.19.380**.
0b. `php -l` clean on every PHP file in the artefact — proven by step 0 above, not
    by spot-checking the files this release touched.
0c. All three suites green, with SKIPs read as skips.
0d. Object cache and page cache purged after the deploy, then re-verify 0a.

### 0B. The two 1.19.380 gates that decide WHO the first run may write to
⛔ **These are read before any rendering is judged.** They are the difference
between a first run that writes to the orders the sequence was built for and one
that writes to a year of backlog.

0Ba. `wp bhp review-ask plan --dates=<the intended first send date>` prints a
     `backlog floor:` line naming the date in force. If it prints `NONE`, stop:
     the constant has been overridden or filtered off somewhere.
0Bb. Every order the floor excludes is named in that output as `DECLINED
     ... before_floor` with its resolved anchor. Read the anchors, not the
     count: a visit-lane order must report its VISIT date, a web-lane order its
     completion date.
0Bc. Every order whose only touch-1 record came from the retired 21-day engine
     reports `DECLINED ... legacy_touch1`. None of them may appear as
     `WOULD SEND touch 2`.
0Bd. The day ends with one `SUMMARY <date>: would_send=N; ...` line. Quote that
     line into the release record rather than re-deriving the counts.
0Be. ⛔ **Andrew has an open choice on the floor date and it is not a default
     to be inherited quietly.** `BHP_REVIEW_ASK_FLOOR_DATE` ships as
     `'2026-09-03'`, which excludes the 2026-08-28 school-visit orders from the
     sequence permanently. The alternative is `'2026-08-28'`, which includes
     them and makes them all due on the first morning, competing for the visit
     lane cap. Confirm which one he has ruled for before the engine is enabled.

### 1. Rendering — check the SOURCE, not only the screenshot
1a. Day 0, touch 1, touch 2 and web touch 1 all render, each with its hero (touch
    2 has none, by design).
1b. Exactly one greeting per email. No "Hi X, Hi X,".
1c. Exactly one sign-off per email: the signature block, three lines, in order —
    Andrew Signore / Author | Brave Hearts Publishing / Big Places. Brave Hearts.
    No plain-text tagline above or below it.
1d. No "we", "us" or "our" anywhere in customer-facing words.
1e. No em dash (U+2014) anywhere.
1f. **The charset first, because it invalidates every visual check below it.** In
    the SMTP plugin's own log, open the touch-1 test-send and read the raw
    `Content-Type` header: it must read `text/html; charset=UTF-8`. ⛔ If the
    charset is missing, STOP — the stars will arrive as `âââââ` and nothing
    else on this list can be judged.
1g. The star row renders as five stars, resting pale gold `#dfc793`, with its
    accessible label, in Gmail web, Gmail iOS and Apple Mail. A row of question
    marks or boxes means the charset did not reach the wire.
1g-ii. Hover, on a desktop client that runs CSS: the row fills to `#c4a15c` from
    the left up to the star under the pointer. **In a client that ignores
    `<style>` the row simply stays pale and every link still works** — an accepted
    outcome, not a failure.
1h. **No empty heading band.** View the RAW message and confirm there is no `<h1`
    anywhere in it, and that the hero touches the top of the card with no cream
    gap. ⛔ 1.19.372 passed the eyeball and still had the element and its 20px
    band in the HTML.
1i. **Then send yourself an ordinary processing-order and completed-order email
    and confirm their headings are STILL THERE.** The same code path runs on
    every WooCommerce email in the store.
1j. With images blocked, the same email still shows five stars (they are
    characters, not images), the caption and "Or open the review page". The hero
    becomes its alt text — read it and confirm it describes the photograph and
    its baked caption.
1k. Star 1 goes to `?rating=1` and star 5 to `?rating=5`. Click both and read the
    query string on the landing page.
1l. At 375px the order and downloads tables are readable, left-aligned, wrapped,
    with their column headings intact.

### 1m. Every book on the order is named (seal 1032)
1m-i.   Place a staging visit order holding **two** chapter books and preview
        touch 1. The paragraph must read "… has had The Mariana Trench and Mount
        Everest for a week and a half now." — both titles, "and", no comma.
1m-ii.  Repeat with **three** books: "The Mariana Trench, Mount Everest and The
        Amazon". ⛔ There is no comma before "and". That is deliberate and
        matches the day-0 email.
1m-iii. On both, the caption under the star row names **ONE** book — the first —
        and the "Or open the review page" link goes to **that same** book's
        review page. ⛔ Caption and destination naming different books is worse
        than either being wrong alone.
1m-iv.  Preview the **web** lane at two books. The second clause must read "They
        went out in the mail, so I never got to see who opened them." ⛔ "It went
        out" about two books is the regression this gate exists for.
1m-v.   Preview a **one-book** order in both lanes and confirm it is unchanged
        from 1.19.374, word for word.

### 1n. The day-0 receipt on a phone, and the sentence that names the books (seal 1042)
1n-i.   Open the day-0 email at **375px**. The totals block heading reads exactly
        "Hand delivery:" — nothing after the colon — and the pickup label appears
        ONCE, in the cell beside it. This gate has now caught the same defect in
        two consecutive builds (1.19.374, 1.19.376), each time because the fix was
        applied to the row's `label` while the duplicate was being printed from
        its `meta`. **Read the RENDER, not the callback.** An ordinary web receipt
        must still show its shipping method name in this heading — check one.
1n-ii.  At a 375px viewport, read `document.scrollWidth` in the client's own
        inspector, or scroll the receipt sideways with a finger. It must not move.
        At 1.19.376/1.19.377 it was 553px against a 375px viewport, and the cause
        was NOT the table width — it was `white-space: nowrap` on the totals row's
        amount cell, which holds the 70-character hand-delivery sentence rather
        than a price. **Before adding `nowrap` to any cell in
        `.email-order-details`, check what that cell actually contains in a REAL
        render.** The class is shared by three tables with three different
        payloads.
1n-iii. In the same render, "Quantity", "Price" and every "$" amount sit on ONE
        line each. ⛔ A word split across two lines ("Qu / antity") means the
        blanket word-break came back.
1n-iv.  The product name still wraps normally, at word boundaries, and the
        Downloads table still shows both live links, left-aligned.
1n-v.   Check the same render at **600px** — the narrow-screen work lives inside a
        `≤480px` media query and must be invisible above it.
1n-vi.  Place a staging visit order with **two** chapter books and preview day 0.
        The paragraph must read "… why The Mariana Trench and Mount Everest
        **are** built the way **they are**, because **they are** built for one
        particular kid." Repeat at **three** books. Preview a **one-book** order
        and confirm that paragraph is unchanged from 1.19.375, word for word.
1n-vii. ⛔ A visit order whose books cannot be resolved must still send NOTHING.
        The send gate moved from `{BookTitle(s)}` onto `{WhyBuiltLine}` in
        1.19.376.

### 2. Photographs
2a. An order with `_bhp_school_visit_slug = dallas-harris-2026-09-03` renders
    hero 01 on day 0 and hero 04 on touch 1.
2b. An order with `_bhp_school_visit_slug = adams-2026-08-28` renders
    `hero-adams-2026-08-28-01.jpg` on day 0 and `-02.jpg` on touch 1, each with
    alt text naming Adams Elementary and August 28, 2026.
2c. An order with NO visit slug, and every web-lane ask, renders
    `BHP_EMAIL_GENERAL_HERO` — as of seal 1027 that is
    `hero-read-aloud-general-adams.jpg`, the Adams library.
    ⛔ Seeing a different picture there means someone changed the constant.
2d. Every hero has non-empty alt text. No child named, no reaction described.
2e. `read-aloud-dallas-harris-2026-09-03-05.jpg` is absent from the theme and
    named by no mapping.

### 3. The day-0 order summary
3a. The hand-delivery row's label reads `Hand delivery:` and nothing more.
3b. Its value is the approved pickup label alone, left-aligned, on one or two
    lines — not the pickup name followed by a forty-word paragraph.
3c. Subtotal, tax and total are unchanged, in the same order, with the same
    values.
3d. ⛔ **Then place an ordinary web order and confirm its shipping row is exactly
    as it was in 1.19.373** — "Shipping:" and the flat rate, untouched.

### 4. Content truth
4a. The day-0 body makes no shipping, tracking or arrival claim.
4b. Every review link resolves to the correct ASIN.
4c. No unconfirmed founder specific appears anywhere ("Island Peak", "Jiri",
    "20,000 feet", "without oxygen").
4d. No review, rating, testimonial or aggregate score is asserted anywhere.

### 5. Schedule and cap, still on staging
5a. The dry runs produce the four dates: touch 1 on 09-10 (Dallas one-book),
    09-11 (Liberty one-book), 09-13 (Dallas multi), 09-14 (Liberty multi), and
    touch 2 four days after each. **No order may be declined `daily_cap_lane`**
    on any of those days.
5b. `wp bhp review-ask status` reports the engine **DISABLED** at the end of QA.

### B. Production go-live, exact commands in order

**Steps 0 to 3 change no behaviour: the engine is off, so installing the theme
ships inert code.** Step 4 is the live one.

```
# 0. ROLLBACK ARTEFACT FIRST. Do not skip.
cd <doc_root>/wp-content/themes
tar -czf ~/PROD-theme-PRE-1.19.380-$(date +%Y%m%d-%H%M).tar.gz <slug>
wp option get bhp_review_ask_enabled                 # record the answer verbatim
wp option get bhp_review_ask_stats  > ~/PRE-379-review-ask-stats.json
wp option get bhp_review_ask_log    > ~/PRE-379-review-ask-log.json

# 0b. ⛔ LINT THE ZIP ON THE SERVER BEFORE INSTALLING IT. Same gate as staging
#     step 0, and it matters more here.
mkdir -p ~/zipcheck && cd ~/zipcheck && rm -rf ./*
unzip -q /path/to/brave-hearts-theme-1.19.380-review-seq.zip
find . -name '*.php' -exec php -l {} \; | grep -v "No syntax errors"
#     MUST PRINT NOTHING. If it prints anything, STOP — production is not the
#     place to discover a parse error.

# 1. install and confirm it replaced the LIVE theme rather than adding one
wp theme install /path/to/brave-hearts-theme-1.19.380-review-seq.zip --force
wp theme list --status=active                        # <slug>, 1.19.380
wp eval 'echo "ok";' --user=1
wp sg purge

# 2. confirm the engine is still OFF after the install
wp bhp review-ask status                             # must read disabled

# 2b. THE SIGNATURE BLOCK'S SOCIAL LINE. If production has no bhp_social_links
#     option, the signature block ends on the brand line and NO social link is
#     emitted. ⛔ Nothing here is invented: supply exactly the two real URLs
#     already set on staging, or leave the option unset and accept a signature
#     with no social line. Do this BEFORE step 4.
wp option get bhp_social_links                       # record the answer verbatim
wp option update bhp_social_links '[{"label":"Facebook","url":"https://www.facebook.com/braveheartspublishing"},{"label":"Instagram","url":"https://www.instagram.com/charlotteandhenrybooks"}]' --format=json
wp option get bhp_social_links --format=json         # read it back; two entries
wp sg purge
#     ⚠ VERIFY IT REACHES THE EMAIL, not just the option table:
wp eval 'print_r( bhp_review_ask_signature() );' --user=1
#     Expect name, role, brand and a `social` array of exactly two entries.

# 3. READ-ONLY dry runs on production, for the two Dallas dates
wp bhp review-ask dry --as-of=2026-09-10
wp bhp review-ask dry --as-of=2026-09-13
#    Read the "would send" lines. They name order ids only.
#    STOP HERE. Everything past this line needs Andrew's word.
```

### C. ⛔ ANDREW'S GATE — the only irreversible command in this document

**Do not run this on inference, on a prior approval, or on "it is a small
change". It needs Andrew's explicit, current approval, and it is the last
command in the go-live sequence.**

```
# 4. THE SEND SWITCH
wp option update bhp_review_ask_enabled yes
wp bhp review-ask status                             # must now read enabled
```

### D. Immediately after enabling — prove the runner is actually scheduled

**An enabled engine with no scheduled run sends nothing and reports no error.
Check this in the same sitting as the flip, not the next morning.**

```
# 5. the daily runner must actually be scheduled
wp action-scheduler list --hook=bhp_review_ask_daily --status=pending
#    Expect at least one PENDING action with a scheduled date in the near future.
#    ⛔ An empty list is a FAILURE, not a quiet pass.

# 5b. if WP-Cron owns it instead of Action Scheduler
wp cron event list --fields=hook,next_run_relative | grep bhp_review_ask_daily

# 5c. if NEITHER shows an entry, the daily runner is not scheduled and nothing
#     will send. Load any admin page to re-run bootstrap, then re-check 5 and 5b.
#     If it is still empty, roll the switch back (section E) rather than leaving
#     an enabled engine in an unknown state.

# 6. first live morning, watch rather than assume
wp bhp review-ask status
wp bhp review-ask plan --dates=2026-09-10,2026-09-11,2026-09-13,2026-09-14
```

### E. Rollback

```
# fastest, and it stops all sending in one command
wp option update bhp_review_ask_enabled no
wp bhp review-ask status                             # must read disabled

# the social line alone, if that is the only thing to undo
wp option delete bhp_social_links                    # signature ends on the brand line

# full code rollback
cd <doc_root>/wp-content/themes
rm -rf <slug>
tar -xzf ~/PROD-theme-PRE-1.19.380-<stamp>.tar.gz
wp theme list --status=active
wp sg purge
```

**⛔ Rolling the code back does NOT unsend an email.** The option flip is the real
stop; the tarball only restores the previous behaviour for future runs.

### F. What this runbook deliberately does not do

- It does not run `wp bhp review-ask migrate`. Seal 994 made the sixteen visit
  orders ordinary engine orders, and the command now refuses all sixteen by id.
  Marking any of them would suppress touch 1 forever.
- It does not touch any WooCommerce product, price, coupon, stock, shipping, tax,
  payment or checkout setting. The engine reads orders and writes two order meta
  keys plus its own options; nothing else. The day-0 receipt change is a
  render-time override of one table cell in one email — no shipping method, zone,
  rate, title or pickup location is created or edited.
- It does not enable anything on its own. Section C is the only switch, and it is
  Andrew's, per-action and per-session.
```

---

# PART 2 (R18.7c) — correct the canonical deploy-ZIP build block

⚠ **This is not cosmetic.** The RUNBOOK's canonical `git archive` command block is
missing the line-ending flags that `.gitattributes` says the RUNBOOK carries. That
gap is what produced the 1.19.378 md5 mismatch: the ZIP's `style.css` was 927,221
bytes against the working tree's 908,796 — a difference of exactly 18,425, the
line count, one byte per line.

**Where:** `docs\RUNBOOK.md`, the section beginning
`**Rebuild the CSS artefacts first — the deploy ships them, not the sources:**`.
**Replace both fenced command blocks under that heading** (the `node
tools/build-css.mjs` pair and the `git archive` block that follows it) with the
single block below. The `--prefix` note, the `CYCLE179-LD-350` `assets/covers`
box and the superseded-command box above it are all **unchanged**.

```bash
cd "C:\BHP\brave-hearts-theme"
node tools/build-css.mjs           # AFTER the final style.css edit, Version bump included
node tools/build-css.mjs --check   # every line must read FRESH

TOP_PHP=$(git ls-tree HEAD --name-only | grep '\.php$')
# The -c flags are NOT optional. Without them git archive writes CRLF into
# every text file (core.autocrlf=true on this box), which (a) makes every
# text file in the theme differ byte-wise on deploy, destroying the
# post-deploy diff, and (b) breaks the style.css/source-md5 identity.
git -c core.autocrlf=false -c core.eol=lf archive --format=zip \
  --prefix=brave-hearts-theme-deploy-explorer-expedition-guides/ \
  -o /path/to/build.zip HEAD style.css style.min.css theme.json assets inc \
  template-parts content-engine docs tests woocommerce Brand-Soul-Audit.md \
  CLAUDE.md Homepage-Implementation-Notes.md Logo.jpg README.md Theme-Freeze.md \
  $TOP_PHP ':(exclude)assets/covers'
```

**Then ADD these three gates to the ZIP gate list** (the same list that already
carries `grep -c 'assets/covers/'  # MUST be 0`):

```markdown
- md5 of style.css extracted from the ZIP MUST equal the "source-md5:" line
  in the style.min.css extracted from the same ZIP. If they differ, the cause
  is almost certainly CRLF in the artefact, not a stale build.
- tr -cd '\r' < <extracted>/style.css | wc -c        # MUST be 0
- lint every PHP file out of the ZIP on the server BEFORE wp theme install:
  find . -name '*.php' -exec php -l {} \; | grep -v "No syntax errors"
  MUST print nothing. wp theme install --force deletes the theme directory
  before extracting, so a parse error in the ZIP is an immediate HTTP 500 with
  no theme left to fall back to. This is what 1.19.378 did.
```

⛔ **`.gitattributes` is deliberately NOT edited.** It already rejects a repo-wide
eol rule, for reasons recorded in the file itself. The defect was the build
command, not the attributes.

---

# PART 3 — the two-line superseding note for `docs\PROJECT_STATE.md`

Add at the top of the review-ask / theme-version block, above whatever it currently
says about 1.19.371 or earlier. Do not delete the superseded lines; this note
carries the correction.

```markdown
⭐ SUPERSEDED 2026-09-05: every review-ask line below dated at or before theme
1.19.371 is superseded by 1.19.380, the CYCLE179 staging candidate — 1.19.379 was
staging-green and 1.19.380 has NOT yet been run on staging,
NOT on production, and `bhp_review_ask_enabled` still unset (see `docs/RUNBOOK.md`,
"Review-ask sequence and school-visit emails — staging QA and the production
go-live gates (1.19.380)"). Production remains theme 1.19.361 / bundle plugin
1.8.83 until Andrew approves the deploy.
```

---

## Application notes

- **Order matters only between Parts 1 and 2** in the sense that both edit the same
  file; make them as two separate edits so a conflict in one does not strand the
  other.
- **Verify after applying, before committing:** `grep -c '1\.19\.371' docs/RUNBOOK.md`
  must return 0 for the review-ask section, and `grep -n 'core.autocrlf=false'
  docs/RUNBOOK.md` must return exactly one hit.
- **Nothing in these three parts changes code, ships an artefact, or enables
  anything.** They are documentation. Section C of Part 1 remains the only
  irreversible step described anywhere in them, and it is Andrew's.
