<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐⭐ 1.19.350 — THE SCHOOL-VISIT BAND. `CYCLE179-LD-350-BUILD`.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⭐ THE PROBLEM IT SOLVES, MEASURED RATHER THAN ASSERTED. On 2026-09-02
 *    `commerce-cx` captured the flagged and the unflagged shop page at four
 *    viewports through one pipeline. THE PNGs HASHED THE SAME:
 *
 *      2ee3cc6952674ad954a397eef81494f0  FLAGGED-375x812   = UNFLAGGED-375x812
 *      553327143a117c88cd76a5e51cdc916c  FLAGGED-1440x900  = UNFLAGGED-1440x900
 *
 *    A parent arriving from the flyer QR code and a stranger arriving from
 *    Google saw the same first screen, pixel for pixel. Everything the visit
 *    flag did happened 800px down. There was no school name, no deadline, no
 *    pickup line anywhere above the fold, and `.bhp-school-pickup-notice` — the
 *    only place the pickup promise is stated at all — renders on
 *    `woocommerce_thankyou`, AFTER the parent has paid.
 *
 * ⛔⛔ AND THE DEADLINE WAS INVISIBLE. `bhp_school_visit_last_order_date()`
 *     returns visit minus 2. For Liberty on 2026-09-04 that is 2026-09-02, and
 *     from 00:00 on 2026-09-03 `bhp_school_visit_is_open_on()` stops resolving
 *     the slug entirely, so the same QR code silently becomes a plain shop
 *     link: no counters, no pickup, shipping back, hardcovers back. Nothing on
 *     the page said so. That is `E1`, the escalation with the shortest fuse.
 *
 * ⭐ THIS FILE LIVES IN THE THEME AND NOT IN THE PLUGIN, DELIBERATELY. It reads
 *    the plugin's existing functions and adds no state, no option, no session
 *    key and no registry field. ⛔ That matters for the release: production runs
 *    bundle plugin 1.8.78 and staging 1.8.79, so a plugin-side band would have
 *    made a theme release into a theme-and-plugin release. Every plugin call
 *    below is `function_exists()`-guarded and the band simply does not render
 *    where the plugin is absent or older.
 *
 * ⛔ IT WRITES NOTHING. No session, no cookie, no option, no registry row, no
 *    cart, no order, no product, no price, no stock, no shipping setting. It
 *    reads three values and prints a sentence.
 *
 * ⛔ NO INVENTED URGENCY. The only urgency on this page is the real deadline
 *    and the real shelf counts, and the counts are owned by
 *    `bhp_visit_shelf_render_counter()` in the plugin, which this file does not
 *    touch. ⛔ No "we" (§9.1). ⛔ No em dash (608a). ⛔ No outcome claim.
 *
 * @package Brave_Hearts
 * @since   1.19.350
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * ⭐ 1.19.353 (`CYCLE179-LD-F10`): the visit slug THIS REQUEST names, or ''.
 *
 * ⛔ IT IS A READ AND NOTHING ELSE. It sets no session, writes no cookie,
 *    resolves nothing and grants nothing. It answers one question: did the
 *    person who loaded this URL name a visit in it?
 *
 * ⭐ `?bhp_visit=clear` returns '' ON PURPOSE. The clear token is the plugin's
 *    explicit way OUT of a flagged session; it is not the name of a visit, and
 *    it must never reach `bhp_school_visit_records()` and be looked up as one.
 *    The plugin's `bhp_school_visit_capture_intent()` checks it before the
 *    registry for the same reason, and this mirrors that order.
 *
 * ⭐ EXTRACTED SO IT CAN BE TESTED. `bhp_visit_band_state()` had this inline,
 *    which meant a suite could only exercise the URL path by mutating `$_GET`
 *    inside the function under test. It is one function with one input now.
 *
 * @since 1.19.353
 * @return string A sanitised slug, or '' when this request names no visit.
 */
function bhp_visit_band_request_slug() {
    $param = defined('BHP_SCHOOL_VISIT_PARAM') ? (string) BHP_SCHOOL_VISIT_PARAM : 'bhp_visit';
    $token = defined('BHP_SCHOOL_VISIT_CLEAR_TOKEN') ? (string) BHP_SCHOOL_VISIT_CLEAR_TOKEN : 'clear';

    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display-only read of a public link; it grants nothing.
    $slug = isset($_GET[$param]) ? sanitize_key(wp_unslash($_GET[$param])) : '';

    if ('' === $slug || $token === $slug) {
        return '';
    }

    return $slug;
}

/**
 * ⭐⭐ 1.19.353 (`CYCLE179-LD-F10`): THE ORDERING RULE, AS A PURE FUNCTION.
 *
 * ⭐ WHY IT IS SEPARATE FROM `bhp_visit_band_state()`, AND IT IS A TESTING
 *    ARGUMENT RATHER THAN AN AESTHETIC ONE. The rule this release exists to
 *    change is four lines of decision surrounded by three collaborators that a
 *    suite cannot stand up: there is no WooCommerce session under WP-CLI, and
 *    faking one would mean writing a session or a registry row, which is a DATA
 *    change this desk does not make. ⛔ The first version of the suite tried to
 *    redefine the plugin's own functions instead, and SKIPPED all four cases on
 *    any environment where the plugin was actually loaded, which is every
 *    environment that matters. A skipped case is not a covered case.
 *
 * ⭐ SPLIT THIS WAY, THE FOUR CASES ARE ORDINARY FUNCTION CALLS. The gathering
 *    of the inputs stays in `bhp_visit_band_state()`, where it touches the
 *    live system; the decision lives here, where it touches nothing.
 *
 * ⛔ IT READS NOTHING AND WRITES NOTHING. No superglobal, no session, no
 *    option, no registry, no clock. Every input is a parameter.
 *
 * @since 1.19.353
 * @param string     $slug    The slug this request names, or '' for none.
 * @param array|null $named   The registry row for `$slug`, or null when the
 *                            slug names no registered visit at all.
 * @param array|null $live    The resolver's answer for `$slug`: the row while
 *                            ordering is open, null once it has closed.
 * @param array|null $session The session's live visit, or null.
 * @return array{state:string, record:array|null}
 */
function bhp_visit_band_decide($slug, $named, $live, $session) {
    // ⭐ THE URL WINS, and only when it actually names a registered visit.
    if ('' !== (string) $slug && is_array($named) && !empty($named['school'])) {
        return (is_array($live) && !empty($live['school']))
            ? ['state' => 'open',   'record' => $live]
            : ['state' => 'closed', 'record' => $named];
    }

    // The session, unchanged from 1.19.352.
    if (is_array($session) && !empty($session['school'])) {
        return ['state' => 'open', 'record' => $session];
    }

    return ['state' => 'none', 'record' => null];
}

/**
 * What band, if any, does this request want?
 *
 * ⭐⭐ TWO STATES, AND THE SECOND IS THE WHOLE POINT OF `R4`.
 *
 *    `open`   this request names a live visit: an explicit `?bhp_visit=` slug
 *             that resolves, or failing that a session that holds one.
 *    `closed` this request names a slug that IS a real registered visit but is
 *             past its online close. Until 1.19.350 that request rendered the
 *             ordinary storefront with no explanation.
 *
 * ⭐⭐ 1.19.353 (`CYCLE179-LD-F10`): THE URL OUTRANKS THE SESSION. The full
 *     reasoning, and the live reproduction it was written from, sit on
 *     `bhp_visit_band_state()` below and are not restated here.
 *
 * ⚠️ AND THAT SUPERSEDES A SENTENCE THIS HEADER USED TO CARRY. It read:
 *    *"`bhp_school_visit_active()` is the SAME test the counters use, so the
 *    band and the counters can never disagree about whether a visit is on."*
 *    ⛔ THAT IS NO LONGER TRUE ON ONE EDGE, so it is corrected rather than
 *    left standing: on an explicit URL slug for a CLOSED visit, carried by a
 *    browser whose session still holds an OPEN one, the band names the URL's
 *    school while the plugin's counters still count the session's shelf.
 *    ⭐ It is preserved above rather than deleted because a reader arriving
 *    from the 1.19.350 release notes needs to know the invariant moved, and
 *    which way. Registered as `CYCLE179-LD-10`; the counter half is the
 *    plugin's and needs a ruling.
 *
 * ⛔ THE CLOSED BRANCH DELIBERATELY BYPASSES `bhp_school_visit_resolve()` AND
 *    GOES STRAIGHT TO `bhp_school_visit_records()`. `resolve()` returns null for
 *    a closed visit BY DESIGN — that is the entitlement gate and it must not be
 *    softened. This is a DISPLAY read of the same registry, and it grants
 *    nothing: no session is set, no shipping method appears, no counter renders,
 *    no gate opens. A closed slug is exactly as unentitled after this file as
 *    before it; it just gets a sentence.
 *
 * ⚠️ KNOWN LIMITATION, RECORDED RATHER THAN HIDDEN. A parent whose session was
 *    flagged BEFORE the close and who returns WITHOUT the URL gets no band:
 *    `bhp_school_visit_active()` clears the expired session and returns null,
 *    and by then nothing in the request names the visit. Detecting that would
 *    need a session breadcrumb written by the plugin, which is a plugin change
 *    and outside this release. ⭐ The flyer QR case — the one `E1` is actually
 *    about, because the QR carries the slug on every scan — IS covered.
 *
 * @since 1.19.350
 * @return array{state:string, record:array|null}
 */
function bhp_visit_band_state() {
    /*
     * ═══════════════════════════════════════════════════════════════════════
     * ⭐⭐⭐ 1.19.353, STEP 1. THE SLUG IN THE URL WINS. `CYCLE179-LD-F10`.
     * ═══════════════════════════════════════════════════════════════════════
     *
     * ⛔ THE DEFECT THIS CLOSES, REPRODUCED LIVE BEFORE IT WAS WRITTEN, not
     *    inferred from the code. On staging at 1.19.352, at an asserted
     *    `window.innerWidth` of 1440: a browser holding one school's live
     *    session opened a DIFFERENT school's QR URL, and the band still read
     *    the FIRST school's name and the FIRST school's hand-delivery date.
     *    The URL named one school; the page named another.
     *
     * ⛔ WHY THE SUPERSEDED ORDER PRODUCED THAT. The session was consulted
     *    FIRST and the URL was consulted only when the session had nothing to
     *    say (`'none' === $out['state']`). The plugin's own
     *    `bhp_school_visit_capture_intent()` replaces the session when the new
     *    slug RESOLVES, so the open-to-open hop was already correct. It is the
     *    open-to-CLOSED hop that was not: an unresolvable slug is a deliberate
     *    no-op in the plugin, so the old session survived and kept the band.
     *    ⭐ That is the live case. A closed visit is exactly the one whose
     *    parents are still scanning the flyer, because the flyer is in their
     *    child's bag on the morning of the visit.
     *
     * ⭐ THE RULE, AND IT IS ONE SENTENCE: an explicit `?bhp_visit=<slug>` that
     *    NAMES A REGISTERED VISIT decides this band, open or closed, whatever
     *    the session holds.
     *
     * ⛔ IT STILL GRANTS NOTHING. This is the same DISPLAY read of the same
     *    registry the closed branch has performed since 1.19.351, and the note
     *    on that branch applies unchanged: `resolve()` returning null is the
     *    entitlement gate and is NOT softened here. No session is written, no
     *    session is cleared, no shipping method appears, no counter renders, no
     *    gate opens. A closed slug is exactly as unentitled after this change
     *    as before it. It gets a sentence, and only a sentence.
     *
     * ⛔ AN UNKNOWN SLUG IS STILL A NO-OP, AND THAT IS DELIBERATE. A slug that
     *    is absent from the registry names no visit, so it cannot replace one:
     *    the session is consulted as before. Making a mistyped or truncated URL
     *    blank a legitimately flagged parent's band would be a new defect, and
     *    it is the same reasoning `bhp_school_visit_capture_intent()` already
     *    records for the session itself. ⭐ `?bhp_visit=clear` is unchanged and
     *    is still the only explicit way out.
     *
     * ⚠️ KNOWN DIVERGENCE ON THIS ONE EDGE, RECORDED RATHER THAN HIDDEN AND
     *    ESCALATED AS `CYCLE179-LD-10`. On session-A-open plus URL-slug-B-
     *    closed the BAND now names B while the per-card counters, which are the
     *    plugin's and are driven by the session, still count A's shelf. Making
     *    those agree means clearing or overriding a session, which is an
     *    ENTITLEMENT change, is the plugin's, and is explicitly outside this
     *    brief. The band is the sentence a parent reads first, so naming the
     *    right school there is the larger half of the fix; the counter half
     *    needs a ruling.
     */
    $slug  = bhp_visit_band_request_slug();
    $named = null;
    $live  = null;

    if ('' !== $slug && function_exists('bhp_school_visit_records')) {
        $records = bhp_school_visit_records();

        if (isset($records[$slug]) && !empty($records[$slug]['school'])) {
            $named = $records[$slug];
            $live  = function_exists('bhp_school_visit_resolve')
                ? bhp_school_visit_resolve($slug)
                : null;
        }
    }

    /*
     * ⭐ STEP 2. THE SESSION, READ ONLY WHEN THE URL NAMED NO REGISTERED
     *    VISIT. Byte-for-byte the behaviour it had at 1.19.352 for every
     *    request that carries no `?bhp_visit=` at all, which is every ordinary
     *    shopper and every parent who arrived through the link earlier and came
     *    back. ⛔ It is not read at all when the URL already decided, so this
     *    change cannot cause a session read that 1.19.352 would not have made.
     */
    $session = (null === $named && function_exists('bhp_school_visit_active'))
        ? bhp_school_visit_active()
        : null;

    $out = bhp_visit_band_decide($slug, $named, $live, $session);

    /**
     * The visit band's state for this request.
     *
     * ⭐ A TEST SEAM, not a configuration point. A suite has no WooCommerce
     *    session and no registry row to stand a `closed` case on, and the
     *    alternative — writing a registry row to exercise a closed visit — is a
     *    data change this desk does not make. ⛔ Filtering it changes what is
     *    PRINTED and nothing else: it opens no gate, grants no entitlement,
     *    renders no counter and changes no shipping method.
     *
     * @since 1.19.350
     * @param array{state:string, record:array|null} $out
     */
    return (array) apply_filters('bhp_visit_band_state', $out);
}

/**
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐⭐ 1.19.350-FIX — THE ONE DEADLINE ANY CUSTOMER-FACING SURFACE MAY PRINT.
 *      `CYCLE179-LD-350-FIX` fix 1, on the `chief-of-staff` review of seal 753.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⭐ THE DEFECT IT CLOSES. Until this function existed, the band computed its
 *    own date (`bhp_school_visit_last_order_date()`, the GATE) while
 *    `/author-visits/` printed the registry's own `cutoff` (the STATED
 *    deadline). Two customer-facing surfaces, two different sentences, no
 *    shared code between them. THAT is the bug: not which date is right, but
 *    that nothing forced them to be the same date.
 *
 * ⛔⛔ READ THIS BEFORE CHANGING THE RULE BELOW. THE BRIEF THAT COMMISSIONED
 *     THIS FUNCTION SAID *"the gate uses the registry cutoff"*. ⚠️ IT DOES NOT,
 *     AND THE CODE IS UNAMBIGUOUS. `bhp_school_visit_is_open_on()` routes to
 *     `bhp_school_visit_last_order_date()`, which is `visit - 2` and reads no
 *     `cutoff` at all. The plugin's own docblock states it: *"IT IS DISPLAY
 *     ONLY FROM 1.8.56. It no longer gates anything."* The stated cutoff has
 *     not gated anything since plugin 1.8.56, and
 *     `tests/test-school-visit-pickup.php` asserts exactly that. ⛔ THE
 *     MISREADING IS RECORDED RATHER THAN QUIETLY CORRECTED, because a future
 *     reader arriving from the brief needs to know why this code does not do
 *     what that sentence describes.
 *
 * ⭐⭐ THE RULE, AND IT IS ONE SENTENCE: **print the date parents were told,
 *     unless the site would refuse an order on that date, in which case print
 *     the last date the site will actually accept one.**
 *
 *        stated cutoff, when it is on or before the online close
 *        the online close,  when the stated cutoff falls AFTER it
 *
 * ⛔ WHY IT CANNOT SIMPLY BE THE GATE DATE — AND THIS IS A FOUNDER
 *    INSTRUCTION, NOT A PREFERENCE. Andrew Signore, RELAYED (and recorded as
 *    relayed, per Standing Rules §9.2 rule 2) on
 *    `bhp_school_visit_last_order_date()`: *"We say 3 days before but the
 *    online cutoff is 1 day before so they can sneak in after their deadline.
 *    Gives them a time crunch they need to meet."* ⛔ NO COPY ANYWHERE MAY
 *    MENTION THE GRACE WINDOW. Printing `visit - 2` on a conventional row
 *    would advertise it on every surface at once. So the stated cutoff wins
 *    whenever it is honest to print it, which on a conventionally-entered row
 *    (`cutoff` = `visit - 3`) is always.
 *
 * ⛔ WHY IT CANNOT SIMPLY BE THE STATED CUTOFF EITHER. A hand-entered row whose
 *    `cutoff` is LATER than `visit - 2` prints a deadline the button will
 *    already have refused. That edge was recorded on
 *    `bhp_school_visit_last_order_date()` as hypothetical (*"All three real
 *    rows are visit - 3 and none is affected"*) and it is now LIVE: the
 *    production `liberty-2026-09-04` row carries `cutoff` `2026-09-03`, which
 *    is `visit - 1`. A deadline the site refuses is a false statement to a
 *    parent, and it outranks the wording preference above.
 *
 * ⛔ THIS CLAMPS A DISPLAY, NEVER THE GATE. `bhp_school_visit_last_order_date()`
 *    and `bhp_school_visit_is_open_on()` are not touched, not wrapped and not
 *    filtered. Entitlement, shipping, counters and the checkout behave exactly
 *    as before this function existed. The docblock on the gate function says
 *    clamping *there* would silently override the operator; that reasoning is
 *    about the gate and it still holds. This is the sentence, not the gate.
 *
 * ⛔ IT INVENTS NO DATE. Both candidates are read: one from the registry row,
 *    one from the plugin's own arithmetic. FAILS CLOSED — when the visit date
 *    is unusable it returns '' and every caller prints nothing rather than
 *    something wrong.
 *
 * @since 1.19.350
 * @param array $record Visit record: needs `date`, optionally `cutoff`.
 * @return string `Y-m-d`, or '' when no honest date can be computed.
 */
function bhp_visit_deadline_display(array $record) {
    if (!function_exists('bhp_school_visit_last_order_date') || empty($record['date'])) {
        return '';
    }

    $gate = (string) bhp_school_visit_last_order_date((string) $record['date']);
    if ('' === $gate) {
        return ''; // ⛔ Fail closed. No usable visit date, no deadline, no sentence.
    }

    $stated = isset($record['cutoff']) ? trim((string) $record['cutoff']) : '';

    // ⭐ `Y-m-d` strings compare correctly as strings. The plugin's own
    //    `bhp_school_visit_is_open_on()` relies on the same property.
    $out = ('' !== $stated && $stated <= $gate) ? $stated : $gate;

    /**
     * The one deadline a customer-facing surface may print for this visit.
     *
     * ⛔ A SEAM FOR TESTS AND FOR A FOUNDER RULING, not a configuration point.
     *    Filtering it changes what is PRINTED and nothing else: it opens no
     *    gate, grants no entitlement, renders no counter and changes no
     *    shipping method.
     *
     * @since 1.19.350
     * @param string $out    `Y-m-d`.
     * @param array  $record The visit record it was derived from.
     * @param string $gate   The online close, `visit - 2`.
     */
    return (string) apply_filters('bhp_visit_deadline_display', $out, $record, $gate);
}

/**
 * The "Order by" sentence for a visit record. `R3`, seal 698.
 *
 * ⭐ IT SAYS "TODAY" WHEN IT IS TODAY. A bare date is a number a parent has to
 *    compare against their own calendar while standing in a school corridor.
 *    On the last order date the sentence says so in the word, and still carries
 *    the date so nobody has to trust the word alone.
 *
 * ⛔ THE DATE IS NOT COMPUTED HERE. It comes from
 *    `bhp_visit_deadline_display()` — the SAME function `/author-visits/` and
 *    the closed band read — which is the entire point of the 350-FIX pass.
 *    `bhp_school_visit_today()` owns "now" through the plugin's movable clock.
 *    ⛔ FAILS CLOSED: no honest date, no sentence.
 *
 * ⚠️⚠️ AND IT GOES SILENT ON A DATE THAT HAS ALREADY PASSED, WHICH IS A REAL
 *    SUBTRACTION AND IS WRITTEN OUT RATHER THAN ASSUMED. On a conventionally
 *    entered row the stated cutoff is `visit - 3` and the button stays live
 *    through `visit - 2`. On that one extra day the honest options are: print
 *    the passed stated date (a future deadline that is already in the past —
 *    a parent reads it as "missed" and leaves, while the button still works),
 *    print the gate date (⛔ advertises the grace window the founder
 *    instruction forbids), or print no date. ⭐ THIS PRINTS NO DATE. The band
 *    still names the school and still states the hand-delivery promise; only
 *    the deadline sentence is withheld, and only on a day when every available
 *    sentence would be either false or forbidden.
 *    ⚠️ RECORDED FOR RATIFICATION: if Andrew wants urgency on the grace day,
 *    the remedy is a ruling on which of the two forbidden sentences he
 *    prefers, not a third date invented here.
 *
 * @since 1.19.350
 * @param array $record Visit record.
 * @return string Empty when no honest date can be printed.
 */
function bhp_visit_band_order_by_line(array $record) {
    $last = bhp_visit_deadline_display($record);
    if ('' === $last) {
        return ''; // ⛔ Fail closed. No date, no sentence.
    }

    $today = function_exists('bhp_school_visit_today') ? (string) bhp_school_visit_today() : '';

    // ⛔ The deadline has passed but ordering is still open. See the docblock:
    //    every printable sentence here is either false or forbidden, so none
    //    is printed.
    if ('' !== $today && $last < $today) {
        return '';
    }

    $ts    = strtotime($last . ' 12:00:00');
    $human = $ts ? wp_date('l, F j', $ts) : $last;

    if ('' !== $today && $today === $last) {
        return sprintf(
            /* translators: %s: a date, e.g. Wednesday, September 2. */
            __('Order by today, %s', 'brave-hearts'),
            $human
        );
    }

    return sprintf(
        /* translators: %s: a date, e.g. Friday, September 4. */
        __('Order by %s', 'brave-hearts'),
        $human
    );
}

/**
 * ⭐⭐ THE BAND. First element below the header, above the catalog band.
 *
 * ⛔ `R11` BY CONSTRUCTION: on an unflagged session `bhp_visit_band_state()`
 *    returns `none` and this function returns before emitting a byte. There is
 *    no hidden element, no empty span and no `display:none` placeholder for an
 *    ordinary shopper's HTML to carry.
 *
 * ⛔ EVERY VALUE IS READ, NONE IS TYPED. School from the registry record, date
 *    from the plugin's own arithmetic, pickup sentence from
 *    `bhp_school_pickup_label()`. ⭐ Nothing here can state a school name, a
 *    date or a delivery promise the plugin would not state.
 *
 * @since 1.19.350
 * @return void
 */
function bhp_visit_band_render() {
    if (!function_exists('bhp_catalog_grid_context') || !bhp_catalog_grid_context()) {
        return;
    }

    $state  = bhp_visit_band_state();
    $record = isset($state['record']) && is_array($state['record']) ? $state['record'] : null;

    if ('none' === $state['state'] || null === $record) {
        return;
    }

    $school = (string) $record['school'];

    if ('closed' === $state['state']) {
        /*
         * ⭐⭐ `R4`, AND IT IS THE HIGHEST-VALUE ITEM IN THE VISIT SPEC. The
         *    superseded behaviour was SILENCE: a parent scanning the flyer QR
         *    code the morning of the visit landed on the ordinary storefront
         *    with shipping and hardcovers back and no explanation.
         *
         * ⛔ IT DOES NOT APOLOGISE AND IT DOES NOT PROMISE. It states the date
         *    ordering closed and states that the books can still be ordered and
         *    posted, which is true and is the only remaining route. ⛔ No
         *    coupon, no "contact me", no outcome claim, no "we".
         */
        /*
         * ⭐ 1.19.350-FIX — THE SAME SOURCE AS THE OPEN BAND AND AS
         *    `/author-visits/`: `bhp_visit_deadline_display()`.
         *
         * ⛔ SUPERSEDED, PRESERVED SO THE MOVEMENT IS VISIBLE AND IS NOT
         *    RE-DERIVED: this block called `bhp_school_visit_last_order_date()`
         *    directly, so a closed Dallas Harris band said *"closed on Tuesday,
         *    September 1"* while `/author-visits/` said *"Order by Monday,
         *    August 31"* for the same visit — two surfaces, two dates, and the
         *    difference was the grace window, printed.
         *
         * ⚠️ IT IS THEREFORE THE PUBLISHED DEADLINE, NOT THE LAST MINUTE THE
         *    BUTTON ACTUALLY WORKED, and on a conventional row those are one
         *    day apart. That understatement is deliberate and it is the
         *    conservative direction: the window is shut either way, nobody is
         *    promised anything, and the alternative advertises a grace window
         *    the founder instruction says is never advertised.
         */
        $closed_on = '';
        $last      = bhp_visit_deadline_display($record);
        if ('' !== $last) {
            $ts = strtotime($last . ' 12:00:00');
            if ($ts) {
                $closed_on = wp_date('l, F j', $ts);
            }
        }
        ?>
        <aside class="bhp-visit-band bhp-visit-band--closed" role="status">
          <div class="bhp-visit-band__inner">
            <p class="bhp-visit-band__school"><?php echo esc_html($school); ?></p>
            <p class="bhp-visit-band__line">
              <?php
              if ('' !== $closed_on) {
                  printf(
                      /* translators: 1: school name, 2: a date, e.g. Wednesday, September 2. */
                      esc_html__('Ordering for %1$s closed on %2$s.', 'brave-hearts'),
                      esc_html($school),
                      esc_html($closed_on)
                  );
              } else {
                  printf(
                      /* translators: %s: school name. */
                      esc_html__('Ordering for %s has closed.', 'brave-hearts'),
                      esc_html($school)
                  );
              }
              ?>
            </p>
            <p class="bhp-visit-band__note"><?php esc_html_e('The books can still be ordered here and posted to your home.', 'brave-hearts'); ?></p>
          </div>
        </aside>
        <?php
        return;
    }

    $order_by = bhp_visit_band_order_by_line($record);
    $pickup   = function_exists('bhp_school_pickup_label') ? (string) bhp_school_pickup_label($record) : '';
    ?>
    <aside class="bhp-visit-band bhp-visit-band--open" role="status">
      <div class="bhp-visit-band__inner">
        <p class="bhp-visit-band__school"><?php echo esc_html($school); ?></p>
        <?php if ('' !== $order_by): ?>
          <p class="bhp-visit-band__order-by"><?php echo esc_html($order_by); ?></p>
        <?php endif; ?>
        <p class="bhp-visit-band__note">
          <?php
          /*
           * ⛔ THE PICKUP SENTENCE IS THE PLUGIN'S OWN LABEL, not a paraphrase.
           *    `bhp_school_pickup_label()` is what the CHECKOUT prints, so the
           *    shop page and the checkout state one arrangement in one form.
           *    The short form beside it is the founder's standing wording for
           *    this offer and carries no promise the order cannot keep.
           */
          if ('' !== $pickup) {
              echo esc_html($pickup);
          } else {
              esc_html_e('Signed and hand delivered at the visit. No shipping.', 'brave-hearts');
          }
          ?>
        </p>
      </div>
    </aside>
    <?php
}
add_action('woocommerce_before_main_content', 'bhp_visit_band_render', 4);

/**
 * ⭐ A BODY CLASS FOR THE FLAGGED CATALOG, SO THE CARD CAN PAY FOR THE COUNTER.
 *
 * ⛔ WHY IT EXISTS, AND IT IS ARITHMETIC RATHER THAN STYLING. A flagged card
 *    carries a full-width counter line that an unflagged card does not, and the
 *    band above the grid takes another 41px. MEASURED on staging at an asserted
 *    1366x768: the unflagged card is 537px and clears the fold at y730; the
 *    flagged card is 583px and its bottom lands at y817, 49px BELOW a 768 fold.
 *    ⭐ That is the page a parent opens from a flyer QR code in a school
 *    corridor, so it is the page that can least afford to miss.
 *
 * ⛔ THE COUNTER IS NOT WHAT GIVES WAY. `VISIT-SHOP-AUDIT.md` `R5` and `R6` are
 *    explicit: the counter stays immediately below the price and immediately
 *    above the button, and its number stays the live, exact, unfloored
 *    remaining count. Nothing here touches `bhp_visit_shelf_render_counter()`,
 *    its label, its placement or its arithmetic. **The cover well pays**, in
 *    `style.css`, and only on a flagged session.
 *
 * ⛔ IT IS A CLASS, NOT A `:has()` SELECTOR, deliberately: a `:has()` rule
 *    degrades to the WRONG geometry on a browser without it, and this is the
 *    surface where a wrong geometry costs a parent the buy button.
 *
 * @since 1.19.350
 * @param string[] $classes
 * @return string[]
 */
function bhp_visit_band_body_class($classes) {
    if (!function_exists('bhp_catalog_grid_context') || !bhp_catalog_grid_context()) {
        return $classes;
    }

    /*
     * ⭐⭐ 1.19.353 (`CYCLE179-LD-F10`): THIS CLASS NOW ASKS THE SAME QUESTION
     *     THE COUNTER ASKS, WHICH IS THE ONE IT WAS ALWAYS ANSWERING.
     *
     * ⛔ SUPERSEDED LINE, PRESERVED SO THE MOVEMENT IS VISIBLE AND IS NOT
     *    RE-DERIVED:
     *
     *        $state = bhp_visit_band_state();
     *        if ('open' === $state['state']) {
     *
     * ⭐ THE REASON IT MOVED. The whole docblock above is arithmetic about a
     *    card that carries a COUNTER LINE. The counter is the plugin's, and it
     *    renders off the SESSION, via `bhp_visit_shelf_counter_for_request()` ->
     *    `bhp_school_visit_paperback_only()` -> `bhp_school_visit_request_record()`
     *    -> `bhp_school_visit_active()`. Until 1.19.353 the band state and that
     *    session could not disagree, so reading the band state was a correct
     *    way to ask the question. ⛔ 1.19.353 makes them able to disagree on
     *    exactly one edge: an explicit URL slug for a CLOSED visit, on a
     *    browser whose session still holds an OPEN one. There the band is
     *    `closed` while the counters still render.
     *
     * ⛔ HAD THIS LINE NOT MOVED, THAT EDGE WOULD HAVE SHIPPED A REGRESSION AT
     *    THE FOLD: 46px of counter line on every card with the cover well no
     *    longer paying for it, on the one page a parent opens in a school
     *    corridor. Asking the session directly keeps the geometry married to
     *    the markup it exists to pay for.
     *
     * ⭐ IT IS BEHAVIOUR-IDENTICAL TO 1.19.352 ON EVERY OTHER REQUEST, and that
     *    is provable rather than hoped: for any request where the band state is
     *    `open`, the record came either from `bhp_school_visit_active()` itself
     *    (step 2) or from a URL slug that RESOLVED, and a resolving slug is one
     *    `bhp_school_visit_capture_intent()` has already written to the session
     *    on this same request, at `template_redirect` priority 5, well before
     *    `body_class` runs.
     *
     * ⛔ IT READS. It does not create a session, and on a visitor with no
     *    WooCommerce session cookie `bhp_school_visit_active()` returns null
     *    without touching anything.
     */
    if (function_exists('bhp_school_visit_active')) {
        $record = bhp_school_visit_active();
        if (is_array($record) && !empty($record['school'])) {
            $classes[] = 'bhp-visit-active';
        }
    }

    return $classes;
}
add_filter('body_class', 'bhp_visit_band_body_class');
