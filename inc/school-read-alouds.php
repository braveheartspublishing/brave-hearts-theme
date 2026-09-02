<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * SCHOOL READ-ALOUDS — THE MERGE. 1.19.326 (2026-08-30,
 * `CYCLE170-LD-SCHOOL-READALOUD-MERGE`). STAGING ONLY.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Andrew Signore, founder ruling of Sunday 2026-08-30, relayed in the build
 * brief: *"Start the merge of School read aloud page- should have old visits,
 * new visits, a gallery, and a way to schedule a read aloud via calendly or a
 * self created calendar schedule that shows morning or Afternoon option on the
 * day they want."*
 *
 * ---------------------------------------------------------------------------
 * ⭐⭐ WHAT THIS RESOLVES, AND IT IS THE POINT OF THE WHOLE LANE.
 * ---------------------------------------------------------------------------
 * The site had grown FOUR read-aloud surfaces, and three of them overlapped.
 * `marketing-growth` recorded it as conflict **C-A** on 2026-08-29 and did not resolve it,
 * correctly, because it was Andrew's to resolve. He resolved it: **ONE page.**
 *
 *   /author-visits/  upcoming visits, past read-alouds, gallery, booking CTA
 *   /gallery/        the 1.19.325 read-aloud funnel (staging page 6087)
 *   → BOTH now point at ONE destination: `/school-read-alouds/`
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔⛔ SUPERSEDED AT 1.19.333 (`CYCLE170-LD-BUNDLE`), AND THE BLOCK ABOVE IS
 *     KEPT RATHER THAN DELETED SO THE MOVEMENT STAYS LEGIBLE.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Andrew Signore, carrier item **524**: the end state is **TWO pages, not one.**
 *
 *   /author-visits/       PARENT page. Upcoming visits, order buttons, How It
 *                         Works. ⛔ NO REDIRECT, EVER. Untouched by this lane.
 *   /school-read-alouds/  TEACHER page. Hero, what a visit looks like, the
 *                         About passages, the scheduler, proof, one tail ask.
 *
 * ⛔ SO "BOTH now point at ONE destination" IS NO LONGER TRUE, AND ONLY
 *    `/gallery/` FOLDS IN. The 1.19.332 sentence is retained above because a
 *    future reader will otherwise re-derive the merge from the option name and
 *    put `author-visits` back. See `bhp_school_readalouds_merged_slugs()`.
 *
 * ⛔ 1.19.333 ALSO MAKES THIS PAGE `noindex` (item 525, not for SEO) and takes
 *    every PARENT-funnel ask off it (the quiz band, the footer capture and the
 *    capture overlays). Both are at the foot of this file.
 *
 * ⛔ TWO SURFACES ARE **NOT** PART OF THIS MERGE AND ARE NOT TOUCHED, NOT
 *    REDIRECTED AND NOT RENAMED. Naming them here because their slugs are one
 *    character apart from each other and from this one, which is exactly the
 *    trap `marketing-growth` flagged as conflict C-F:
 *      · `/read-aloud/`  — the QR take-home landing page a child's colouring
 *        sheet points at. Printed material points at it. Breaking it breaks paper.
 *      · `/read-alouds/` — page id 108, an older classroom-resources article.
 *
 * ---------------------------------------------------------------------------
 * ⛔⛔ NOTHING IS FORKED. THE MERGED PAGE COMPOSES EXISTING HELPERS.
 * ---------------------------------------------------------------------------
 * `bhp_author_visits_rows()`, `bhp_author_visits_past_rows()`,
 * `bhp_gallery_sections()`, `bhp_readaloud_funnel_copy_slots()`,
 * `bhp_readaloud_funnel_render_slot()` and
 * `bhp_readaloud_funnel_show_pricing()` are all called, none is copied and none
 * is edited. ⭐ THAT IS WHY THE 1.19.325 SUITE (165 assertions) AND THE VISITS
 * SUITES STILL ASSERT LIVE CODE after this merge rather than asserting a dead
 * twin. A merge that duplicates its sources is how two pages start telling a
 * librarian two different things, which is the defect this page exists to end.
 *
 * ⭐ THE PLACEHOLDER MECHANISM IS KEPT EXACTLY AS BUILT, ON PURPOSE AND ON
 *    INSTRUCTION. Every founder-voice passage still renders as a loud
 *    `[PENDING READ-BACK — do not publish]` block, and approved copy still
 *    lands through `add_filter('bhp_readaloud_funnel_copy_slots', …)` with no
 *    template edit. ⛔ IF A LATER PASS IS TEMPTED TO "TIDY" THOSE BLOCKS, it
 *    removes the only visual signal that the page is unfinished.
 *
 * ⛔ COPY RAILS. Andrew's I-voice, no "we"/"us"/"our" in any visible string
 *    (§9.1). No em dashes. No price, fee or rate. No review, rating,
 *    testimonial, reaction, statistic or award. No child named, no librarian
 *    named, no school invented. Reading age 6–9, never 5–9. American spelling.
 *
 * @package BraveHearts
 */

defined( 'ABSPATH' ) || exit;

/**
 * The one slug.
 *
 * @return string
 */
function bhp_school_readalouds_slug() {
	return 'school-read-alouds';
}

/**
 * The one URL.
 *
 * @return string
 */
function bhp_school_readalouds_url() {
	return home_url( '/' . bhp_school_readalouds_slug() . '/' );
}

/**
 * The merged page's primary call to action.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐ THE ONE BEHAVIOURAL CHANGE FROM 1.19.325: THIS SCROLLS, IT DOES NOT MAIL.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⛔ At 1.19.325 the hero button was a `mailto:`. `marketing-growth` flagged the honesty gap
 *    in her read-back sheet (decision 4): the button says "Book" and a `mailto:`
 *    books nothing, it opens a mail client. The founder's 2026-08-30 ruling
 *    closes that gap by asking for a real scheduler, so the button now takes the
 *    visitor to the scheduler on the same page. ⭐ THE LABEL IS UNCHANGED AND IS
 *    STILL HIS OWN CAPITALISATION from carrier item 481.
 *
 * ⛔ THIS IS A SEPARATE FUNCTION FROM `bhp_readaloud_funnel_cta()` AND THAT IS
 *    DELIBERATE. That one still serves `/gallery/` and its 165-assertion suite
 *    asserts its `mailto:`. Changing it would have edited another surface's
 *    contract to save one function.
 *
 * @return array{href:string,label:string,email:string,anchor:string}
 */
function bhp_school_readalouds_cta() {
	$anchor = 'readaloud-scheduler';

	return apply_filters(
		'bhp_school_readalouds_cta',
		array(
			'anchor' => $anchor,
			'href'   => '#' . $anchor,
			'label'  => __( 'Book a FREE read-aloud', 'brave-hearts' ),
			/*
			 * ⭐ THE ADDRESS STAYS VISIBLE BESIDE THE FORM, AND IS NOT REMOVED
			 *    JUST BECAUSE A FORM NOW EXISTS. A librarian who would rather
			 *    write an email should not be forced through a form, and this
			 *    address is already public on this site and is the route Andrew
			 *    gave parents himself (carrier item 377).
			 */
			'email'  => 'andrew@braveheartspublishing.com',
		)
	);
}

/**
 * Whether `/gallery/` redirects to the merged page.
 *
 * ⛔ 1.19.333 — THIS SWITCH NO LONGER GOVERNS `/author-visits/` IN ANY STATE.
 *    See `bhp_school_readalouds_merged_slugs()`: item 524 removed that slug
 *    from the merge entirely, so the only surface this can ever 301 is
 *    `/gallery/`. The historical block below is preserved because it records
 *    WHY the switch is an option rather than a hardcoded `true`, and that
 *    reasoning is unchanged.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔⛔ OFF ON PRODUCTION BY CONSTRUCTION. READ THIS BEFORE CHANGING IT.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⭐ HISTORICAL, AND STILL THE REASON FOR THE MECHANISM: `/author-visits/` IS
 *    LIVE ON PRODUCTION (page 592) AND IS REACHED FROM PRINTED QR CODES TAPED
 *    TO CLASSROOM DOORS. A redirect that shipped silently with a theme deploy
 *    would have repointed printed paper at a page that does not exist on
 *    production yet, and it would have done it the moment somebody deployed
 *    this version for an unrelated reason. ⛔ That risk is now closed at the
 *    SLUG LIST rather than at this flag, which is the stronger place to close
 *    it: no option value and no filter can reopen it.
 *
 * ⛔ SO THE DEFAULT IS THE STAGING DETECTOR, NOT `true`. On any host that is not
 *    the staging literal this returns FALSE and `/gallery/` behaves exactly as
 *    it does today. Turning it on for production is a founder decision plus an
 *    explicit flip. ⭐ `/gallery/` DOES NOT EXIST ON PRODUCTION (verified
 *    read-only 2026-08-30), so on production this switch currently has nothing
 *    to act on at all; it is set so that a later `/gallery/` cannot appear
 *    un-unified.
 *
 * @return bool
 */
function bhp_school_readalouds_unify_redirects() {
	$default = function_exists( 'bhp_staging_mail_guard_is_staging' )
		? bhp_staging_mail_guard_is_staging()
		: false;

	/*
	 * ═══════════════════════════════════════════════════════════════════════
	 * ⭐ 1.19.332 — THE PRODUCTION SHIP SWITCH. An OPTION, NOT A HARDCODED
	 *    `true`, and the difference is the whole point.
	 * ═══════════════════════════════════════════════════════════════════════
	 *
	 * The redirects have to be turnable ON for production, and everything in
	 * the block above is still true: /author-visits/ is reached from printed
	 * QR codes taped to classroom doors, and paper cannot be recalled.
	 *
	 * ⛔ HARDCODING `true` WOULD ARM THE REDIRECT INSIDE THE THEME ARTEFACT,
	 *    so it would fire the instant anybody deployed this version for an
	 *    unrelated reason. An option arms it in a SEPARATE, DELIBERATE act,
	 *    after the destination page exists.
	 *
	 * ⭐ AND IT MAKES ROLLBACK A ONE-LINE COMMAND rather than a theme
	 *    redeploy: `wp option delete bhp_school_readalouds_unify` restores
	 *    today's behaviour in full, with no build, no ZIP and no downtime.
	 *
	 * The three states, and staging is unaffected in all of them:
	 *   unset  the staging detector decides. Staging ON, production OFF.
	 *          This is today's behaviour, byte for byte.
	 *   "1"    forced ON. The production ship step sets this.
	 *   "0"    forced OFF everywhere, including staging. A kill switch that
	 *          works even if something else set the option.
	 *
	 * ⛔ THIS REMAINS BELT AND BRACES, NOT THE ONLY GUARD.
	 *    `bhp_school_readalouds_maybe_redirect()` below still refuses to
	 *    redirect unless the destination page exists AND is published. So
	 *    setting this option before the page is created is harmless: it does
	 *    nothing at all rather than 301ing live pages into a 404.
	 */
	$forced = get_option( 'bhp_school_readalouds_unify', '' );
	if ( '1' === (string) $forced ) {
		$default = true;
	} elseif ( '0' === (string) $forced ) {
		$default = false;
	}

	return (bool) apply_filters( 'bhp_school_readalouds_unify_redirects', $default );
}

/**
 * The slugs that fold into the merged page.
 *
 * ⛔ EXACTLY ONE, LISTED AS A LITERAL. Never derived from a pattern like
 *    "anything containing read-aloud", which would have swallowed
 *    `/read-aloud/` and `/read-alouds/` — two live pages that are not part of
 *    this merge. A literal list cannot grow by accident.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔⛔ 1.19.333 — `author-visits` IS REMOVED FROM THIS LIST, PERMANENTLY.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Andrew Signore, carrier item **524**, relayed in the `CYCLE170-LD-BUNDLE`
 * brief: the two-page architecture is settled. **`/author-visits/` stays
 * parent-only and gets NO redirect ever**; `/school-read-alouds/` is the
 * teacher page. They are two audiences with two jobs, not one page with a
 * duplicate.
 *
 * ⛔ THIS IS NOT A DEFERRAL AND IT IS NOT A FLAG SET TO OFF. The slug is GONE
 *    from the list, so no option, no filter and no environment can 301
 *    `/author-visits/` at this page. `bhp_school_readalouds_unify` now governs
 *    `/gallery/` and nothing else, and its name is kept rather than renamed
 *    because it is already set on staging and renaming it would silently arm
 *    a fresh, unset option.
 *
 * ⭐ WHY THAT MATTERS BEYOND TIDINESS: `/author-visits/` IS LIVE ON PRODUCTION
 *    (page 592) AND IS REACHED FROM PRINTED QR CODES TAPED TO CLASSROOM DOORS.
 *    Paper cannot be recalled. Under 1.19.332 the production ship step would
 *    have repointed all of that paper at a teacher page carrying no order
 *    button. Item 524 is the reason that never happens.
 *
 * ⚠ THE FILTER IS KEPT so a future surface can be folded in deliberately. It
 *   is NOT the way to re-add `author-visits`: doing that reverses a founder
 *   ruling from a callback, which is exactly the move this comment exists to
 *   prevent.
 *
 * @return string[]
 */
function bhp_school_readalouds_merged_slugs() {
	return apply_filters(
		'bhp_school_readalouds_merged_slugs',
		array( 'gallery' )
	);
}

/**
 * 301 the merged-away surface onto the one page.
 *
 * ⛔ 301 AND NOT 302. The end state is permanent, so the status code says so and
 *    search engines are told to move the equity rather than to keep both.
 *
 * ⛔ IT NEVER REDIRECTS ONTO ITSELF, and it does nothing at all unless the
 *    destination page actually exists — a redirect to a 404 is worse than the
 *    duplicate it was trying to fix.
 *
 * @return void
 */
function bhp_school_readalouds_maybe_redirect() {
	if ( is_admin() || ! bhp_school_readalouds_unify_redirects() ) {
		return;
	}
	if ( ! is_page() ) {
		return;
	}

	$post = get_queried_object();
	if ( ! $post instanceof WP_Post ) {
		return;
	}

	$slug = (string) $post->post_name;
	if ( $slug === bhp_school_readalouds_slug() ) {
		return; // ⛔ Never redirect the destination onto itself.
	}
	if ( ! in_array( $slug, bhp_school_readalouds_merged_slugs(), true ) ) {
		return;
	}

	// ⛔ The destination must exist. No redirect into a 404.
	$target = get_page_by_path( bhp_school_readalouds_slug() );
	if ( ! $target instanceof WP_Post || 'publish' !== $target->post_status ) {
		return;
	}

	wp_safe_redirect( get_permalink( $target ), 301 );
	exit;
}
add_action( 'template_redirect', 'bhp_school_readalouds_maybe_redirect', 1 );

/**
 * Render the request scheduler.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔⛔ IT ASKS. IT DOES NOT BOOK. Every visible string in here is written so a
 *     teacher cannot come away believing a date is held for her.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⭐ WHY RADIOS AND CHECKBOXES RATHER THAN A DATEPICKER WIDGET. A radio group
 *    is a one-of-many control with no script; two checkboxes are two
 *    independent yes/no answers with no script. So this whole section works
 *    with JavaScript off, is keyboard navigable and screen-readable for free,
 *    and — the part that matters — **the offered dates are generated on the
 *    server and re-validated on the server.** A JS calendar that "only allows
 *    school days" allows whatever a POST body contains.
 *
 * ⭐ THE TAP TARGETS ARE SIZED IN THE STYLESHEET, NOT LEFT TO THE BROWSER. This
 *    form's primary reader is a teacher on a phone. `.readaloud-sched__day` and
 *    `.readaloud-sched__slot` both carry a minimum touch size; if a later pass
 *    shrinks them, the founder's "finger-friendly" requirement quietly breaks.
 *
 * @return void
 */
function bhp_school_readalouds_render_scheduler() {
	/*
	 * ⭐⭐ 1.19.335 (`CYCLE170-LD-WEEKPICKER`, carrier item 534) — THE EMPTINESS
	 *     TEST IS NOW THE WEEK LIST, and it has to be, because the week list is
	 *     what the form now asks for. Testing the day list would render a form
	 *     with no card to pick on the one edge where days exist but the twelve
	 *     week cards do not.
	 */
	$weeks    = bhp_readaloud_scheduler_weeks();
	$slots    = bhp_readaloud_scheduler_slots();
	$weekdays = bhp_readaloud_scheduler_weekdays();
	$cities   = bhp_readaloud_scheduler_cities();
	$cta      = bhp_school_readalouds_cta();

	$status = isset( $_GET['bhp_readaloud'] ) ? sanitize_key( wp_unslash( $_GET['bhp_readaloud'] ) ) : '';
	$msg    = bhp_readaloud_request_status_message( $status );

	/*
	 * ⭐ 1.19.331: THE `$by_month` GROUPING THAT USED TO STAND HERE IS GONE, and
	 *    deliberately rather than by oversight. It flattened the offered dates
	 *    into "month -> list of pickable days", which is precisely the shape a
	 *    real calendar cannot be drawn from: a grid needs the days NOT offered
	 *    too, in their true weekday columns.
	 *    `bhp_readaloud_scheduler_months()` walks whole calendar months instead.
	 *
	 * ⛔ `$dates` IS STILL READ, and is still the emptiness test immediately
	 *    below. "Is there anything at all to ask for" is a question about the
	 *    OFFERED list, not about how many squares a grid would draw - a month
	 *    grid is never empty, so testing the grid would render a form with a
	 *    calendar full of dead days and no way to submit.
	 */
	?>
	<section id="readaloud-scheduler" class="section readaloud-sched" aria-labelledby="readaloud-sched-title">
	  <div class="container container--content">
	    <header class="component-heading">
	      <?php
	      /*
	       * ⭐ 1.19.333 (`CYCLE170-LD-BUNDLE`) — THE SECTION HEADER, RENAMED.
	       *    Was "Ask me for a day". The brief's wording, item 519's
	       *    conversion restructure: the scheduler is no longer a polite
	       *    request buried below the proof, it is the page's offer, and its
	       *    heading now states the offer in three short sentences.
	       *
	       * ⭐ 1.19.334 (`CYCLE170-LD-MVP`) — THE APOSTROPHE IS NOW THE
	       *    TYPOGRAPHIC ONE (U+2019), AND THE FLAG BELOW IS CLOSED.
	       *
	       *    1.19.333 shipped a STRAIGHT apostrophe (U+0027) because that is
	       *    how the brief typed it, and flagged the divergence for `chief-of-staff`
	       *    rather than deciding it. ⭐ HE RULED FOR HOUSE TYPOGRAPHY: every
	       *    other visible string on this page uses U+2019, and one straight
	       *    quote in a section heading reads as a typo on the founder's own
	       *    return gate.
	       *
	       * ⛔ ONE CHARACTER MOVED. Not a word, not a space, not the
	       *    capitalisation — this is still item 481's own wording. The
	       *    matching assertion in `tests/test-cycle170-bundle.php` moved with
	       *    it; a string test that still expected U+0027 would have failed
	       *    green-for-the-wrong-reason if only one of the two had changed.
	       *
	       * ⛔ "Free." IS ITEM 481's OWN FACT, not a new claim, and it is the
	       *    same fact the hero note and the chips already carry. No price,
	       *    no rate and no figure appears anywhere on this page.
	       */
	      ?>
	      <?php
	      /*
	       * ⭐⭐ 1.19.335 (`CYCLE170-LD-WEEKPICKER`) — ONE WORD MOVED: "day" → "week".
	       *
	       *    ⛔ IT IS NOT A COPY PREFERENCE, IT IS THE SAME HONESTY RULE THE
	       *    1.19.334 CALENDAR FLOOR SERVED. The control now asks for a week, so
	       *    a headline that says "Pick a day" would state a mechanism the page
	       *    does not have — the exact defect the floor closed in the other
	       *    direction, where the copy said October and the calendar offered
	       *    September. This build AGREES with the brief's change and records
	       *    that it was asked to flag disagreement and has none.
	       *
	       *    ⛔ NOTHING ELSE IN THE STRING MOVED. Not "I’ll", not the
	       *    capitalisation, not "Free." — that is still item 481's own fact and
	       *    the same fact the hero note and the chips carry. The apostrophe is
	       *    still U+2019 (the 1.19.334 ruling). No price appears on this page.
	       */
	      ?>
	      <h2 id="readaloud-sched-title" class="text-section-title"><?php esc_html_e( 'Pick a week. I’ll come read to your class. Free.', 'brave-hearts' ); ?></h2>
	    </header>

	    <?php if ( $msg ) : ?>
	      <?php
	      /*
	       * ⛔ `role="status"` + `aria-live="polite"` so the outcome is announced
	       *    after the redirect. A visitor using a screen reader otherwise
	       *    lands back on the form with no idea whether anything happened.
	       */
	      ?>
	      <div class="readaloud-sched__status readaloud-sched__status--<?php echo esc_attr( $msg['tone'] ); ?>" role="status" aria-live="polite" tabindex="-1">
	        <p class="readaloud-sched__status-title"><?php echo esc_html( $msg['title'] ); ?></p>
	        <p class="readaloud-sched__status-text"><?php echo esc_html( $msg['text'] ); ?></p>
	      </div>
	    <?php endif; ?>

	    <?php if ( empty( $weeks ) ) : ?>

	      <?php
	      /*
	       * ⛔ HONEST EMPTY STATE. If no week can be offered the form is not
	       *    rendered at all rather than rendered with nothing to pick. It
	       *    promises nothing about when weeks will exist.
	       */
	      ?>
	      <div class="author-visits-empty">
	        <p><?php esc_html_e( 'I do not have weeks open to ask for right now.', 'brave-hearts' ); ?></p>
	        <p>
	          <?php esc_html_e( 'Email me and I will tell you what I can do.', 'brave-hearts' ); ?>
	          <a href="<?php echo esc_url( 'mailto:' . $cta['email'] . '?subject=' . rawurlencode( 'Read-aloud request' ) ); ?>"><?php echo esc_html( $cta['email'] ); ?></a>
	        </p>
	      </div>

	    <?php else : ?>

	      <?php
	      /*
	       * ═══════════════════════════════════════════════════════════════════
	       * ⭐⭐⭐ THE HONEST LINE — carrier item 534, VERBATIM, ABOVE THE PICKER.
	       * ═══════════════════════════════════════════════════════════════════
	       *
	       * ⛔ IT IS PRINTED FIRST, BEFORE THE CARDS, BECAUSE IT IS THE REASON THE
	       *    CARDS ARE WEEKS. A teacher who meets a week picker with no
	       *    explanation reads it as a vaguer version of a calendar. A teacher
	       *    who reads this first understands she is being asked for the thing
	       *    he can actually answer.
	       *
	       * ⛔ ONE STRING, FROM ONE FUNCTION, SO IT CANNOT DRIFT.
	       *    `bhp_readaloud_scheduler_honest_line()` is the only place these
	       *    words exist in the theme, and the suite asserts them character by
	       *    character against item 534.
	       *
	       * ⛔ IT IS NOT A CLAIM THIS BUILD MADE. It is the founder's statement
	       *    about his own work, relayed through `chief-of-staff` in the brief.
	       */
	      ?>
	      <p class="readaloud-sched__honest">
	        <?php echo esc_html( bhp_readaloud_scheduler_honest_line() ); ?>
	      </p>

	      <p class="readaloud-sched__lead">
	        <?php esc_html_e( 'Pick the week that works for your class. Add a backup week if you have one, and tell me which days and which part of the day suit you. Sending this asks me for the week, it does not book it. I reply to every request myself.', 'brave-hearts' ); ?>
	      </p>

	      <form class="readaloud-sched__form"
	            action="<?php echo esc_url( bhp_readaloud_scheduler_form_action() ); ?>"
	            method="post">

	        <input type="hidden" name="action" value="<?php echo esc_attr( BHP_READALOUD_REQUEST_ACTION ); ?>" />
	        <input type="hidden" name="source_page" value="<?php echo esc_url( bhp_school_readalouds_url() ); ?>" />
	        <?php
	        /*
	         * ⭐ 1.19.336 — THE CAMPAIGN FALLBACK FIELD. `CYCLE170-LD-CHAIN`.
	         *
	         * ⛔⛔ 1.19.342 (`CYCLE172-LD-FUNNEL-FIX`, G-A): THE VALUE IS NO LONGER
	         *     RENDERED SERVER-SIDE. It is emitted empty and filled by
	         *     `assets/js/bhp-attr-now.js` from the visitor's own
	         *     `location.search`, in the visitor's own browser.
	         *
	         * ⛔ THE SUPERSEDED GUARD, PRESERVED SO IT IS NOT RE-DERIVED: this
	         *    field used to be *"emitted only when the page URL actually
	         *    carried something, which is the cache-poisoning guard the signup
	         *    form already relies on"*. ⭐ THAT GUARD DOES NOT HOLD ON THIS
	         *    HOST. SiteGround's full-page cache strips `utm_*` and `fbclid`
	         *    from the cache key, so the campaign render and the clean render
	         *    share ONE cache entry — verified live on production 2026-08-31,
	         *    where an anonymous no-query GET returned a real visitor's
	         *    `fbclid`. "Only sometimes" is not a guard when the cache key
	         *    cannot see the sometimes.
	         *
	         * ⛔ `source_page` is NOT widened to carry any of this — it is
	         *    host-checked and reused as a redirect target.
	         */
	        ?>
	        <input type="hidden" name="bhp_attr_now" value="" data-bhp-attr-now />
	        <?php wp_nonce_field( BHP_READALOUD_REQUEST_ACTION, 'bhp_readaloud_nonce', false ); ?>

	        <?php
	        /*
	         * ⛔ THE HONEYPOT. Hidden from people and from assistive technology,
	         *    visible to a bot that fills every input it finds. It is NOT
	         *    `type="hidden"` — a bot skips those — and it is NOT hidden by
	         *    JavaScript, because a form that only resists spam when scripts
	         *    run does not resist spam.
	         */
	        ?>
	        <div class="readaloud-sched__hp" aria-hidden="true">
	          <label for="bhp-readaloud-hp"><?php esc_html_e( 'Leave this field empty', 'brave-hearts' ); ?></label>
	          <input type="text" id="bhp-readaloud-hp" name="bhp_readaloud_hp" tabindex="-1" autocomplete="off" value="" />
	        </div>

	        <?php /* ── THE WEEK ────────────────────────────────────────────── */ ?>
	        <fieldset class="readaloud-sched__fieldset readaloud-sched__fieldset--weeks">
	          <legend class="readaloud-sched__legend"><?php esc_html_e( 'Which week?', 'brave-hearts' ); ?></legend>
	          <p class="readaloud-sched__hint"><?php esc_html_e( 'Pick a first choice. A backup week is optional and it helps.', 'brave-hearts' ); ?></p>

	          <?php
	          /*
	           * ═══════════════════════════════════════════════════════════════
	           * ⛔⛔ THE WEEK CARDS — 1.19.335 (`CYCLE170-LD-WEEKPICKER`, item 534).
	           *     THIS REPLACES THE MONTH GRID THAT STOOD HERE AT 1.19.331-334.
	           * ═══════════════════════════════════════════════════════════════
	           *
	           * ⛔⛔ THE CONTROL IS STILL A RADIO GROUP — TWO OF THEM. Every card
	           *     carries one `visit_week` radio (the first choice) and one
	           *     `visit_week_backup` radio (the backup). Two independent radio
	           *     groups over the same set of cards is what "pick one, and
	           *     optionally a second" looks like with NO SCRIPT AT ALL: a radio
	           *     group is already a one-of-many control, and two of them are
	           *     already two independent one-of-many answers.
	           *
	           * ⛔ NO SQUARE, NO SPAN, NO DEAD CARD. The month grid drew every day
	           *    of every month and marked most of them dead, because a calendar
	           *    that skips its weekends stops being a calendar. A week LIST has
	           *    no such obligation: an unofferable week is simply not a card.
	           *    ⭐ SO THERE IS NOTHING HERE FOR A DEVTOOLS PANEL TO RE-ENABLE —
	           *    the same property the dead `<span>` gave the grid, reached by
	           *    not rendering the thing at all.
	           *
	           * ⛔ AND THE SERVER GATE MOVED WITH THE CONTROL.
	           *    `bhp_readaloud_scheduler_week_is_offered()` re-derives the list
	           *    on POST and refuses anything outside it, so a posted September
	           *    Monday is refused by the handler and not merely absent from
	           *    this list. Drawing cards grants no permission; the offer list
	           *    does, and it is the same one `build_dates()` has always held.
	           *
	           * ⭐ A LIST, NOT A TABLE, AND THAT IS THE ACCESSIBILITY ARGUMENT.
	           *    The month grid used real `<table>` semantics because a calendar
	           *    IS a two-dimensional relation (weekday column x week row). A
	           *    week picker is one-dimensional, so a `<ul>` of cards is the
	           *    honest structure and a screen reader announces "list, 12 items"
	           *    instead of navigating a grid that has one real axis.
	           *
	           * ⭐ NO PAGER, NO ARROWS, NOTHING TO SCROLL PAST. Twelve cards fit on
	           *    a page. The month grid needed a pager because it drew ~120
	           *    squares across four months; this draws twelve rows.
	           */
	          ?>
	          <?php
	          /*
	           * ═══════════════════════════════════════════════════════════════
	           * ⭐⭐⭐ THE SEPTEMBER LINE — 1.19.339 (`CYCLE170-LD-FINAL2`,
	           *      carrier item 562). ⛔ IT REPLACES FOUR CARDS. IT IS NOT
	           *      ADDED BESIDE THEM.
	           * ═══════════════════════════════════════════════════════════════
	           *
	           * ⛔⛔ THE FOUR SEPTEMBER CARDS THAT STOOD AT THE TOP OF THIS
	           *     LIST AT 1.19.336-1.19.338 ARE GONE FROM THE RENDER. The
	           *     superseded block is quoted verbatim in the docblock of
	           *     `bhp_readaloud_scheduler_september_line()`, and the DATA
	           *     behind it — `closed_weeks()`, its four founder-sealed rows,
	           *     its status labels and its self-expiry — is UNTOUCHED and
	           *     still asserted by `test-cycle170-final.php` §2.
	           *     ⭐ ONLY THE DRAWING STOPPED.
	           *
	           * ⛔⛔ NO SERVER-SIDE VALIDATION MOVED WITH IT. The 1.19.334 floor
	           *     still refuses every September day inside `build_dates()`,
	           *     and `week_is_offered()` still re-derives the offer list on
	           *     POST. September was refused before this change and is
	           *     refused identically after it. ⭐ THIS RELEASE REMOVES CARD
	           *     UI, NOT A GATE.
	           *
	           * ⛔ IT SITS ABOVE THE CALENDAR CARD, NOT INSIDE IT, and that is
	           *    deliberate. On the page ground it is one quiet sentence; on
	           *    the navy masthead it would have been the loudest thing in the
	           *    component — which is exactly the weight the four cards had
	           *    and exactly the weight the founder asked to remove.
	           */
	          ?>
	          <p class="readaloud-sched__september">
	            <?php echo esc_html( bhp_readaloud_scheduler_september_line() ); ?>
	          </p>

	          <?php
	          /*
	           * ═══════════════════════════════════════════════════════════════
	           * ⭐⭐⭐ THE MONTH SELECTOR — 1.19.339, carrier item 562.
	           *      TABS, NOT A `<select>`, AND THE CHOICE IS ARGUED HERE.
	           * ═══════════════════════════════════════════════════════════════
	           *
	           * ⭐ THE BRIEF LEFT THE PATTERN TO THIS LANE AND ASKED FOR THE
	           *    REASONING. Month TABS, printed as a second row of the navy
	           *    masthead, in the calendar's own gold-on-navy register.
	           *
	           *    ⛔ A NATIVE `<select>` LOSES TWICE. It is a chrome-grey OS
	           *       widget dropped onto a component whose entire job is to
	           *       read as a wall calendar hung in a classroom; and it HIDES
	           *       the other months behind a click, so a teacher cannot see
	           *       at a glance that November and December exist at all. The
	           *       tab strip shows all three months and costs one row.
	           *
	           *    ⭐ AND THE WIDTH IS BOUNDED, WHICH IS THE USUAL ARGUMENT FOR
	           *       A SELECT AND DOES NOT APPLY HERE. `week_count()` caps the
	           *       list at twelve weeks, so the month count cannot plausibly
	           *       exceed four. A tab strip that cannot overflow is a tab
	           *       strip.
	           *
	           * ═══════════════════════════════════════════════════════════════
	           * ⛔⛔ PROGRESSIVE ENHANCEMENT, AND IT IS NOT DECORATION. READ THIS
	           *     BEFORE CHANGING ANY ATTRIBUTE BELOW.
	           * ═══════════════════════════════════════════════════════════════
	           *
	           * ⛔ THE TABS ARE PRINTED AS REAL `<a href="#panel-id">` ANCHORS,
	           *    AND THE PANELS ARE PRINTED WITH NO `hidden` ATTRIBUTE. So
	           *    with JavaScript off, EVERY WEEK OF EVERY MONTH IS ON THE PAGE
	           *    and each anchor jumps to its month. That is byte-for-byte the
	           *    1.19.338 behaviour plus three in-page links. ⭐ NOTHING IS
	           *    BEHIND A CONTROL THAT NEEDS A SCRIPT TO OPEN IT.
	           *
	           * ⛔⛔ THAT IS NOT A NICETY. This scheduler's stated design
	           *     property since 1.19.326 is that it works with no script at
	           *     all. A CSS-only or JS-built tab would put nine offerable
	           *     weeks behind a control a scriptless browser cannot operate,
	           *     and it would do it silently.
	           *
	           * ⭐ `assets/js/readaloud-calendar.js` UPGRADES what is already
	           *    here: `role="tablist"` / `role="tab"` / `role="tabpanel"`, the
	           *    roving `tabindex`, `aria-selected`, `aria-controls`, arrow and
	           *    Home/End keys, and `hidden` on every panel but the first.
	           *    ⛔ IT MANUFACTURES NO DOM NODE AND NO CONTROL, and
	           *    `test-cycle170-final.php` asserts that by needle over that
	           *    file's raw text. ⚠️ The needle is the DOM method's own name, so
	           *    neither that file's comments nor this one write it out — see
	           *    the note beside the tab block in the script.
	           *
	           * ⛔⛔ AND IT MOVES `required` WITH THE ACTIVE TAB. That is a real
	           *     browser defect being handled, not tidiness: a `required`
	           *     radio inside a `hidden` container CANNOT BE FOCUSED, so
	           *     Chrome refuses to submit and reports "An invalid form
	           *     control is not focusable" TO THE CONSOLE AND NOWHERE ELSE.
	           *     A teacher would press the button and watch nothing happen.
	           *     The mechanism is in the script, beside this reasoning.
	           *
	           * ⛔ SELECTION SURVIVES A TAB SWITCH AND IS STILL POSTED. A hidden
	           *    container hides its inputs; it does not remove them from the
	           *    form — only `disabled` does that, and nothing here is
	           *    disabled. A teacher may pick her first choice in October and
	           *    her backup in December and both post, exactly as they did
	           *    from the flat list.
	           */
	          $bhp_week_months = function_exists( 'bhp_readaloud_scheduler_group_weeks_by_month' )
	            ? bhp_readaloud_scheduler_group_weeks_by_month( $weeks )
	            : array( array( 'key' => 'all', 'label' => '', 'full' => '', 'weeks' => $weeks ) );
	          ?>
	          <div class="readaloud-sched__cal readaloud-sched__weeks" data-bhp-cal data-bhp-cal-count="<?php echo esc_attr( (string) count( $weeks ) ); ?>">

	            <div class="readaloud-sched__cal-nav">
	              <?php
	              /*
	               * ⛔ AMENDED AT 1.19.336. WAS: "Weeks I can be asked for".
	               *
	               * ⭐ THE 1.19.336 SEPTEMBER CARDS PUT FOUR WEEKS IN THIS LIST
	               *    THAT HE CANNOT BE ASKED FOR, so the old masthead became
	               *    false about its own contents the moment they were added.
	               *    ⛔ This is the SAME honesty defect the 1.19.334 floor
	               *    closed in the other direction (a calendar offering
	               *    September under a hero that said October onward), and it
	               *    is fixed the same way: the words are brought to the
	               *    control, not the control to the words.
	               *
	               * ⛔ UI CHROME, NOT FOUNDER-SEALED COPY. No approved string is
	               *    edited by this change — the four card labels, the four
	               *    status words and the honest line are untouched. ⚠️ Flagged
	               *    to `chief-of-staff` in the deploy plan rather than assumed.
	               */
	              ?>
	              <?php
	              /*
	               * ⚠️ 1.19.339 REMOVED THE FOUR SEPTEMBER CARDS, so the
	               *    condition that forced the 1.19.336 rename no longer
	               *    holds and "Weeks I can be asked for" would be true of
	               *    this list again. ⛔ IT IS NOT REVERTED. "The weeks
	               *    ahead" is still literally true of what sits under it,
	               *    reverting would be a copy change nobody asked for, and
	               *    `test-cycle170-final.php` §5c asserts the superseded
	               *    string stays gone. Flagged to `chief-of-staff`, not decided here.
	               */
	              ?>
	              <p class="readaloud-sched__cal-title"><?php esc_html_e( 'The weeks ahead', 'brave-hearts' ); ?></p>

	              <?php if ( count( $bhp_week_months ) > 1 ) : ?>
	                <?php
	                /*
	                 * ⛔ THE STRIP IS PRINTED ONLY WHEN THERE IS MORE THAN ONE
	                 *    MONTH TO CHOOSE BETWEEN. One tab is not a choice; it
	                 *    is a label pretending to be a control, and a teacher
	                 *    who clicks it learns nothing. When the offer list
	                 *    narrows to a single month this whole strip disappears
	                 *    and the picker is the flat list it was at 1.19.338.
	                 *
	                 * ⛔ `aria-label` ON THE STRIP, NOT A SECOND VISIBLE
	                 *    HEADING. The fieldset's legend already says "Which
	                 *    week?"; another line between it and the cards is one
	                 *    more thing between the teacher and the control.
	                 *
	                 * ⭐ THE VISIBLE TAB IS THE BARE MONTH NAME, and the
	                 *    `.screen-reader-text` span carries the month, the YEAR
	                 *    and the week count. A sighted reader has three short
	                 *    words; a screen-reader user gets "October 2026, 5
	                 *    weeks" and knows what is behind the tab before opening
	                 *    it.
	                 */
	                ?>
	                <div class="readaloud-sched__monthtabs"
	                     data-bhp-monthtabs
	                     aria-label="<?php esc_attr_e( 'Choose a month', 'brave-hearts' ); ?>">
	                  <?php foreach ( $bhp_week_months as $bhp_m_i => $bhp_month ) : ?>
	                    <a class="readaloud-sched__monthtab<?php echo ( 0 === (int) $bhp_m_i ) ? ' is-active' : ''; ?>"
	                       id="bhp-monthtab-<?php echo esc_attr( $bhp_month['key'] ); ?>"
	                       href="#bhp-monthpanel-<?php echo esc_attr( $bhp_month['key'] ); ?>"
	                       data-bhp-monthtab="bhp-monthpanel-<?php echo esc_attr( $bhp_month['key'] ); ?>">
	                      <?php echo esc_html( $bhp_month['label'] ); ?>
	                      <span class="screen-reader-text">
	                        <?php
	                        printf(
	                          /* translators: 1: month and year, such as "October 2026". 2: how many weeks that month holds. */
	                          esc_html( _n( '%1$s, %2$d week', '%1$s, %2$d weeks', count( $bhp_month['weeks'] ), 'brave-hearts' ) ),
	                          esc_html( $bhp_month['full'] ),
	                          (int) count( $bhp_month['weeks'] )
	                        );
	                        ?>
	                      </span>
	                    </a>
	                  <?php endforeach; ?>
	                </div>
	              <?php endif; ?>
	            </div>

	            <?php foreach ( $bhp_week_months as $bhp_m_i => $bhp_month ) : ?>
	            <div class="readaloud-sched__monthpanel"
	                 id="bhp-monthpanel-<?php echo esc_attr( $bhp_month['key'] ); ?>"
	                 data-bhp-monthpanel="<?php echo esc_attr( $bhp_month['key'] ); ?>"
	                 aria-labelledby="bhp-monthname-<?php echo esc_attr( $bhp_month['key'] ); ?>">

	              <?php
	              /*
	               * ⛔ THE PANEL'S OWN NAME, AND IT CARRIES THE YEAR WHERE THE
	               *    TAB DOES NOT. A screen-reader user arriving on a panel
	               *    does not have the card spans in view, and "October"
	               *    alone does not say which October.
	               *
	               * ⭐ WITH JS ON, the script adds `screen-reader-text` to this
	               *    paragraph: the tab above is already saying the month
	               *    visibly, and printing it twice is chrome. ⛔ IT IS NOT
	               *    REMOVED — it is the panel's accessible name.
	               *    ⭐ WITH JS OFF it stays visible, and it is then the only
	               *    thing separating one month's cards from the next.
	               */
	              ?>
	              <p class="readaloud-sched__monthname" id="bhp-monthname-<?php echo esc_attr( $bhp_month['key'] ); ?>">
	                <?php echo esc_html( $bhp_month['full'] ); ?>
	              </p>

	            <ul class="readaloud-sched__weeklist">
	              <?php
	              /*
	               * ═════════════════════════════════════════════════════════════
	               * ⛔⛔ THE FOUR SEPTEMBER CARDS STOOD HERE AT 1.19.336-1.19.338
	               *     AND ARE REMOVED AT 1.19.339 (`CYCLE170-LD-FINAL2`,
	               *     carrier item 562). THEY ARE REPLACED BY THE ONE LINE
	               *     ABOVE THIS CALENDAR CARD.
	               * ═════════════════════════════════════════════════════════════
	               *
	               * ⭐ THE REMOVED MARKUP IS NOT LOST. It is filed verbatim at
	               *    `_pre-edit-backups6-08-30-cycle170-final2-1.19.339	               *    REMOVED-september-cards-block.txt`, and the whole file is
	               *    in that same backup directory. Restoring the block is a
	               *    paste, and the data it read is still live.
	               *
	               * ⛔ NOTHING BEHIND IT MOVED. `closed_weeks()` still returns
	               *    its four founder-sealed rows (items 537/538/540) and still
	               *    self-expires; `closed_week_status_label()` is untouched;
	               *    `test-cycle170-final.php` §2 still asserts all of it. Only
	               *    the rendering stopped, and only on this surface.
	               *
	               * ⛔⛔ AND NO GATE MOVED. The cards were DISPLAY ONLY - they
	               *     carried no `<input>`, no `name`, no `value` - so removing
	               *     them removes nothing submittable and grants nothing. The
	               *     1.19.334 floor and `week_is_offered()` refuse September
	               *     exactly as they did before.
	               */
	              ?>
	              <?php foreach ( $bhp_month['weeks'] as $w_i => $week ) : ?>
	                <?php
	                $w_first_id  = 'bhp-week-' . $week['value'];
	                $w_backup_id = 'bhp-week-backup-' . $week['value'];
	                ?>
	                <li class="readaloud-sched__week" data-bhp-week="<?php echo esc_attr( $week['value'] ); ?>">

	                  <p class="readaloud-sched__week-label"><?php echo esc_html( $week['label'] ); ?></p>
	                  <p class="readaloud-sched__week-range"><?php echo esc_html( $week['range'] ); ?></p>

	                  <div class="readaloud-sched__week-picks">

	                    <?php
	                    /*
	                     * ⭐ THE `required` ATTRIBUTE IS ON EVERY FIRST-CHOICE RADIO,
	                     *    exactly as it was on every day radio at 1.19.334. HTML
	                     *    treats a radio group as satisfied when ANY member is
	                     *    checked, so this is one requirement, not twelve.
	                     *
	                     * ⛔ THE BACKUP GROUP CARRIES NO `required`. It is optional
	                     *    by design and by the handler, which accepts an empty
	                     *    backup and rejects only a backup that is invalid or
	                     *    identical to the first choice.
	                     */
	                    ?>
	                    <input class="readaloud-sched__week-input readaloud-sched__week-input--first"
	                           type="radio"
	                           id="<?php echo esc_attr( $w_first_id ); ?>"
	                           name="visit_week"
	                           value="<?php echo esc_attr( $week['value'] ); ?>"
	                           required />
	                    <label class="readaloud-sched__week-pick readaloud-sched__week-pick--first" for="<?php echo esc_attr( $w_first_id ); ?>">
	                      <span aria-hidden="true"><?php esc_html_e( 'First choice', 'brave-hearts' ); ?></span>
	                      <span class="screen-reader-text">
	                        <?php
	                        printf(
	                          /* translators: 1: week label, 2: the school days in that week. */
	                          esc_html__( 'First choice: %1$s, %2$s', 'brave-hearts' ),
	                          esc_html( $week['label'] ),
	                          esc_html( $week['range'] )
	                        );
	                        ?>
	                      </span>
	                    </label>

	                    <input class="readaloud-sched__week-input readaloud-sched__week-input--backup"
	                           type="radio"
	                           id="<?php echo esc_attr( $w_backup_id ); ?>"
	                           name="visit_week_backup"
	                           value="<?php echo esc_attr( $week['value'] ); ?>" />
	                    <label class="readaloud-sched__week-pick readaloud-sched__week-pick--backup" for="<?php echo esc_attr( $w_backup_id ); ?>">
	                      <span aria-hidden="true"><?php esc_html_e( 'Backup', 'brave-hearts' ); ?></span>
	                      <span class="screen-reader-text">
	                        <?php
	                        printf(
	                          /* translators: 1: week label, 2: the school days in that week. */
	                          esc_html__( 'Backup choice: %1$s, %2$s', 'brave-hearts' ),
	                          esc_html( $week['label'] ),
	                          esc_html( $week['range'] )
	                        );
	                        ?>
	                      </span>
	                    </label>

	                  </div>
	                </li>
	              <?php endforeach; ?>
	            </ul>
	            </div>
	            <?php endforeach; ?>

	            <?php
	            /*
	             * ⭐ THE RUNNING SUMMARY. Printed `hidden` and empty; filled by
	             *    `assets/js/readaloud-calendar.js` when either group changes.
	             *
	             * ⛔ IT IS AN ENHANCEMENT, NOT A REQUIREMENT, and that is why it is
	             *    empty without the script: every card is on screen at once, so
	             *    both checked radios are always visible and there is nothing a
	             *    summary must tell anyone. It earns its place on a phone, where
	             *    the first choice can be twelve cards above the backup.
	             *
	             * `role="status"` so a change is announced once rather than read as
	             * a silent repaint.
	             */
	            ?>
	            <p class="readaloud-sched__cal-summary"
	               data-bhp-cal-summary
	               data-bhp-cal-summary-first="<?php esc_attr_e( 'First choice:', 'brave-hearts' ); ?>"
	               data-bhp-cal-summary-backup="<?php esc_attr_e( 'Backup:', 'brave-hearts' ); ?>"
	               role="status"
	               hidden></p>
	          </div>
	        </fieldset>

	        <?php /* ── WHICH DAYS COULD WORK ───────────────────────────────── */ ?>
	        <fieldset class="readaloud-sched__fieldset readaloud-sched__fieldset--weekdays">
	          <legend class="readaloud-sched__legend"><?php esc_html_e( 'Which days could work for your class?', 'brave-hearts' ); ?></legend>
	          <?php
	          /*
	           * ⭐⭐ OPTIONAL, AND IT SAYS SO ON SCREEN. Item 534's whole point is
	           *     that the exact day is settled by reply once his hospital
	           *     schedule posts. A teacher who does not yet know which days work
	           *     must still be able to send the request, so this fieldset carries
	           *     no `required` on any input and the handler accepts an empty set.
	           *
	           * ⛔ THESE ARE PREFERENCES, NOT A SECOND DATE PICKER. Nothing here
	           *    narrows or widens the set of weeks that can be asked for; the
	           *    values are carried to his inbox and are used by nothing else.
	           */
	          ?>
	          <p class="readaloud-sched__hint"><?php esc_html_e( 'Optional. Tick as many as you like, or leave them all blank.', 'brave-hearts' ); ?></p>
	          <div class="readaloud-sched__slots readaloud-sched__weekdays">
	            <?php foreach ( $weekdays as $wd_key => $wd_label ) : ?>
	              <?php $wd_id = 'bhp-weekday-' . $wd_key; ?>
	              <input class="readaloud-sched__slot-input"
	                     type="checkbox"
	                     id="<?php echo esc_attr( $wd_id ); ?>"
	                     name="weekdays[]"
	                     value="<?php echo esc_attr( $wd_key ); ?>" />
	              <label class="readaloud-sched__slot readaloud-sched__slot--weekday" for="<?php echo esc_attr( $wd_id ); ?>"><?php echo esc_html( $wd_label ); ?></label>
	            <?php endforeach; ?>
	          </div>
	        </fieldset>

	        <?php /* ── MORNING / AFTERNOON ─────────────────────────────────── */ ?>
	        <fieldset class="readaloud-sched__fieldset readaloud-sched__fieldset--slots">
	          <legend class="readaloud-sched__legend"><?php esc_html_e( 'Morning or afternoon?', 'brave-hearts' ); ?></legend>
	          <?php
	          /*
	           * ⭐ CHECKBOXES, NOT A RADIO PAIR, AND THAT IS THE FOUNDER'S MODEL
	           *    RATHER THAN A UI PREFERENCE: *"I can do a morning visit or an
	           *    afternoon visit ans possibly 2 in one day."* Ticking both says
	           *    "either one works", which is the answer that gets a school a
	           *    yes fastest.
	           *
	           * ⚠⚠ 1.19.335 — REFRAMED AS A PREFERENCE, AND STILL REQUIRED. THE
	           *    HINT MOVED; THE HANDLER DID NOT.
	           *
	           *    The brief reframes these as preferences alongside the new
	           *    optional weekday boxes, AND states that request mechanics are
	           *    unchanged. Those two pull in opposite directions on exactly one
	           *    question: does an empty answer still redirect `noslot`?
	           *    ⛔ THIS BUILD KEPT THE REQUIREMENT — "mechanics unchanged" is the
	           *    narrower reading, and morning-versus-afternoon is the one
	           *    preference that materially changes whether he can take the visit
	           *    at all. The WEEKDAY boxes, which the brief calls optional in so
	           *    many words, are genuinely optional and carry no `required`.
	           *    ⭐ FLAGGED TO `chief-of-staff` RATHER THAN DECIDED SILENTLY. Making these
	           *    optional is a one-line handler change if he rules the other way.
	           */
	          ?>
	          <p class="readaloud-sched__hint"><?php esc_html_e( 'Which part of the day suits you better? Tick both if either one works.', 'brave-hearts' ); ?></p>
	          <div class="readaloud-sched__slots">
	            <?php foreach ( $slots as $key => $label ) : ?>
	              <?php $sid = 'bhp-slot-' . $key; ?>
	              <input class="readaloud-sched__slot-input"
	                     type="checkbox"
	                     id="<?php echo esc_attr( $sid ); ?>"
	                     name="slots[]"
	                     value="<?php echo esc_attr( $key ); ?>" />
	              <label class="readaloud-sched__slot" for="<?php echo esc_attr( $sid ); ?>"><?php echo esc_html( $label ); ?></label>
	            <?php endforeach; ?>
	          </div>
	        </fieldset>

	        <?php /* ── WHO AND WHERE ────────────────────────────────────────── */ ?>
	        <div class="readaloud-sched__grid">

	          <p class="readaloud-sched__field">
	            <label class="readaloud-sched__label" for="bhp-sched-school"><?php esc_html_e( 'School or library', 'brave-hearts' ); ?></label>
	            <input class="readaloud-sched__input" type="text" id="bhp-sched-school" name="school" required autocomplete="organization" />
	          </p>

	          <p class="readaloud-sched__field">
	            <label class="readaloud-sched__label" for="bhp-sched-city"><?php esc_html_e( 'City', 'brave-hearts' ); ?></label>
	            <select class="readaloud-sched__input" id="bhp-sched-city" name="city" required>
	              <option value=""><?php esc_html_e( 'Choose a city', 'brave-hearts' ); ?></option>
	              <?php foreach ( $cities as $key => $label ) : ?>
	                <option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option>
	              <?php endforeach; ?>
	            </select>
	            <?php
	            /*
	             * ⛔ THE SERVICE-AREA NOTE IS ALWAYS VISIBLE, NOT REVEALED BY
	             *    SCRIPT WHEN "Somewhere else" IS PICKED. A warning that only
	             *    appears when JavaScript runs is a warning that sometimes
	             *    does not appear. It warns; it does not refuse.
	             */
	            ?>
	            <span class="readaloud-sched__note"><?php echo esc_html( bhp_readaloud_scheduler_area_note() ); ?></span>
	          </p>

	          <p class="readaloud-sched__field">
	            <label class="readaloud-sched__label" for="bhp-sched-contact"><?php esc_html_e( 'Your name', 'brave-hearts' ); ?></label>
	            <input class="readaloud-sched__input" type="text" id="bhp-sched-contact" name="contact" required autocomplete="name" />
	          </p>

	          <p class="readaloud-sched__field">
	            <label class="readaloud-sched__label" for="bhp-sched-email"><?php esc_html_e( 'Your email', 'brave-hearts' ); ?></label>
	            <input class="readaloud-sched__input" type="email" id="bhp-sched-email" name="email" required autocomplete="email" inputmode="email" />
	          </p>

	          <p class="readaloud-sched__field readaloud-sched__field--wide">
	            <label class="readaloud-sched__label" for="bhp-sched-grades"><?php esc_html_e( 'Which grades?', 'brave-hearts' ); ?></label>
	            <input class="readaloud-sched__input" type="text" id="bhp-sched-grades" name="grades" required placeholder="<?php esc_attr_e( 'First and second grade', 'brave-hearts' ); ?>" />
	          </p>

	          <p class="readaloud-sched__field readaloud-sched__field--wide">
	            <label class="readaloud-sched__label" for="bhp-sched-notes"><?php esc_html_e( 'Anything else I should know?', 'brave-hearts' ); ?> <span class="readaloud-sched__optional"><?php esc_html_e( '(optional)', 'brave-hearts' ); ?></span></label>
	            <textarea class="readaloud-sched__input readaloud-sched__textarea" id="bhp-sched-notes" name="notes" rows="3"></textarea>
	          </p>

	        </div>

	        <?php
	        /*
	         * ═══════════════════════════════════════════════════════════════════
	         * ⭐⭐ 1.19.337 (2026-08-30, `CYCLE170-LD-MICRO`) — THE TOOLKIT OPT-IN.
	         *     CARRIER ITEMS 553 AND 554.
	         * ═══════════════════════════════════════════════════════════════════
	         *
	         * ⭐⭐ THE FOUNDER'S WORDS, verbatim, 2026-08-30, carrier item 553:
	         *
	         *      "If a teacher fills out their information for the read aloud-
	         *       they should be put into the funnel and we should smuggle the
	         *       consent into"
	         *
	         *    ⛔⛔ AND THE PART THAT MATTERS MORE THAN THE INSTRUCTION: `chief-of-staff`
	         *      PUSHED BACK ON "smuggle" — buried consent is a spam-complaint,
	         *      a Mailchimp-compliance and a brand risk — and proposed a
	         *      VISIBLE, PRE-CHECKED, CLEARLY LABELLED box instead. ⭐ ANDREW
	         *      TOOK THE PUSHBACK, in his own words at the same item:
	         *
	         *          "Ok thats great- I thought we did the consent like that for
	         *           something else the marketing consent thing"
	         *
	         *      and re-confirmed it once more at carrier item 554:
	         *
	         *          "im ok with the preselected free kit on the read aloud page
	         *           - good"
	         *
	         *    ⛔ RELAYED through `chief-of-staff`; all three read first-hand at
	         *      the carrier file before this control was written. NOT witnessed
	         *      by this desk.
	         *
	         * ⛔⛔ THERE IS NO SMUGGLED CONSENT ON THIS FORM AND THERE MUST NEVER
	         *     BE ONE. This box is inside the visible flow, immediately above
	         *     the submit button, at full contrast, with a real `<label>` a tap
	         *     anywhere on the text will toggle. ⛔ It is NOT visually hidden,
	         *     NOT inside a collapsed panel, NOT below the button, NOT in fine
	         *     print, and its label names the thing being sent.
	         *
	         * ---------------------------------------------------------------------
	         * ⭐ HIS RECOLLECTION WAS RIGHT, AND THAT IS WHY THIS IS NOT A NEW
	         *    PATTERN
	         * ---------------------------------------------------------------------
	         * "the marketing consent thing" is `bhp_register_marketing_consent_fields()`
	         * in functions.php — the WooCommerce checkout opt-in whose wire is
	         * `inc/checkout-optin-sync.php`. ⭐ Same shape: one visible checkbox, one
	         * honest label, one scoped signup context, its own tags, and a wire that
	         * can never break the transaction it rides on. ⛔ THE ONE DELIBERATE
	         * DIFFERENCE: checkout's box ships UNCHECKED, this one ships CHECKED, and
	         * that is the founder's explicit decision at items 553 and 554 rather
	         * than an inherited default.
	         *
	         * ⚠️ THE HONEST NOTE ON "PRE-CHECKED", RECORDED RATHER THAN GLOSSED:
	         *   a pre-ticked marketing box is not GDPR-valid consent in the EEA/UK.
	         *   This is a US-audience form for Boise-area schools, the founder ruled
	         *   it explicitly and twice, and the box is visible and trivially
	         *   untickable — so it ships as ruled. ⛔ IF THIS FORM IS EVER OFFERED
	         *   TO AN EEA/UK AUDIENCE, THE DEFAULT MUST FLIP TO UNCHECKED. Flagged
	         *   to Andrew in the release record, not decided here.
	         *
	         * ⛔ THE LABEL IS THE FOUNDER-SEALED STRING, CHARACTER-EXACT. No "we"
	         *    (§9.1 — the sentence is "Also send me…", the teacher's own voice),
	         *    no em dash, no outcome claim, and it promises exactly two things:
	         *    the Toolkit and classroom resources emails.
	         *
	         * ⛔ THE BOOKING IS NOT CONDITIONAL ON IT. Unticked, the request is sent
	         *    exactly as it was at 1.19.336 and nothing reaches Mailchimp. The
	         *    handler treats this as an independent, non-blocking side effect —
	         *    see `bhp_readaloud_request_enroll_educator()`.
	         */
	        ?>
	        <p class="readaloud-sched__optin">
	          <input class="readaloud-sched__optin-input"
	                 type="checkbox"
	                 id="bhp-sched-optin"
	                 name="toolkit_optin"
	                 value="yes"
	                 checked />
	          <label class="readaloud-sched__optin-label" for="bhp-sched-optin">
	            <?php esc_html_e( 'Also send me the free Adventure Learning Toolkit and my classroom resources emails.', 'brave-hearts' ); ?>
	          </label>
	        </p>

	        <p class="readaloud-sched__submit">
	          <button class="btn btn-primary readaloud-sched__btn" type="submit"><?php esc_html_e( 'Send this request', 'brave-hearts' ); ?></button>
	        </p>

	        <?php
	        /*
	         * ⛔⛔ THE TENTATIVE LINE SITS UNDER THE BUTTON, BEFORE THE CLICK, NOT
	         *     ONLY ON THE THANK-YOU STATE. A visitor should know what the
	         *     button does before they press it, not after.
	         */
	        ?>
	        <p class="readaloud-sched__tentative">
	          <?php esc_html_e( 'This sends me a request. It does not book the week. Nothing is confirmed until I email you back with the exact day and time.', 'brave-hearts' ); ?>
	        </p>

	        <p class="readaloud-sched__alt">
	          <?php esc_html_e( 'You can also just email me:', 'brave-hearts' ); ?>
	          <a href="<?php echo esc_url( 'mailto:' . $cta['email'] . '?subject=' . rawurlencode( 'Read-aloud request' ) ); ?>"><?php echo esc_html( $cta['email'] ); ?></a>
	        </p>

	      </form>

	    <?php endif; ?>
	  </div>
	</section>
	<?php
}

/* ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐ 1.19.333 (2026-08-30, `CYCLE170-LD-BUNDLE`) — THE SINGLE TAIL ASK, AND
 *     THE FUNNEL-SEPARATION DOCTRINE APPLIED TO THIS PAGE.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⛔⛔ THIS IS A RULE CHANGE, NOT A BUG FIX, AND IT IS DOCUMENTED AS ONE.
 *
 * `CYCLE170-LD-SHIP-PREP`'s finding **b** recorded that a PARENT-funnel capture
 * overlay renders on `/school-read-alouds/`, a TEACHER page, and it recorded a
 * contradiction it correctly refused to resolve:
 *
 *   · the read-back sheet §5.4: *"The parent popup must be suppressed on this
 *     page."*
 *   · `.claude/rules/funnels.md`: the parent funnel is *"sitewide except
 *     `/teachers/`"* — an exception list of exactly one slug, written before
 *     this page existed.
 *
 * ⭐ THE `CYCLE170-LD-BUNDLE` BRIEF RESOLVES IT: **the funnel-separation
 *    doctrine wins over the stale exception list.** The rule is no longer
 *    "every page except the one slug `teachers`"; it is **"a teacher page
 *    carries the teacher funnel and nothing else."** `/school-read-alouds/` is
 *    a teacher page, so the parent asks come off it.
 *
 * ⚠ `.claude/rules/funnels.md` STILL SAYS "sitewide except /teachers/" AND IS
 *   NOW STALE. This file cannot fix it — a repo rules file is Business
 *   Operations & Knowledge's to amend — so the amendment is handed over in the
 *   deploy bundle rather than silently assumed. ⛔ Until it lands, THIS COMMENT
 *   is the record of which source governs.
 *
 * ---------------------------------------------------------------------------
 * ⭐ FILTERS, NOT AN EDIT TO `functions.php`'s EXCLUSION ARRAYS.
 * ---------------------------------------------------------------------------
 * Every gate below already ships its own filter, and `inc/read-aloud-landing.php`
 * established this exact idiom for this exact reason (see
 * `bhp_read_aloud_suppress_exit_intent()`): the proven parent/teacher/audience
 * eligibility logic stays byte-untouched, so a mistake here cannot cost an
 * existing funnel its surface. The whole footprint is this file plus the
 * `require` line that already exists.
 *
 * ⛔ EVERY CALLBACK RETURNS `$show` UNCHANGED OFF-TEMPLATE. Not one other page
 *    on the site changes behaviour by a single pixel.
 *
 * ⛔ THE TEACHER CAPTURE IS NOT TOUCHED. `page-school-read-alouds.php`'s
 *    educator toolkit band is the page's ONE tail ask and it stays. This block
 *    removes competitors; it does not remove the offer.
 * ═══════════════════════════════════════════════════════════════════════════ */

/**
 * Is the current request the merged read-aloud page?
 *
 * ⭐ TEMPLATE-BASED, NOT SLUG-BASED, and that is the same instrument every
 *    exclusion list in `functions.php` already uses (`is_page_template()`). It
 *    survives a slug rename and it cannot be satisfied by an unrelated page
 *    that happens to be named the same thing on another environment.
 *
 * ⛔ THE SLUG TEST IS A SECOND LIMB, NOT THE FIRST. A page created without its
 *    `_wp_page_template` meta set — which is exactly the failure the deploy
 *    plan's STEP 3 warns about, because WP-CLI silently ignores
 *    `--page_template` on some versions — would otherwise keep the parent
 *    funnel on a teacher page with nothing to notice.
 *
 * @return bool
 */
function bhp_school_readalouds_is_page() {
	if ( is_admin() ) {
		return false;
	}
	if ( is_page_template( 'page-school-read-alouds.php' ) ) {
		return true;
	}
	return is_page( bhp_school_readalouds_slug() );
}

/**
 * Suppress every PARENT-funnel automatic surface on the teacher page.
 *
 * ⛔ ONE CALLBACK, FOUR FILTERS, BECAUSE THE FOUR SURFACES ARE THE SAME
 *    DECISION. The timed parent popup, the A/B capture popup and the
 *    exit-intent modal are three renderings of ONE offer (the Reluctant Reader
 *    Adventure Kit, `bhp_parent_popup` / `parent_popup`), and the quiz launcher
 *    is the sitewide "Find My Best Next Step" band the brief names. Writing
 *    four near-identical callbacks would let them drift apart.
 *
 * ⚠ TWO OF THE FOUR ARE ALREADY FALSE EVERYWHERE TODAY, AND THIS IS STILL NOT
 *   REDUNDANT. `bhp_show_parent_popup` is filtered false sitewide, and the A/B
 *   popup's surface list is homepage + blog posts + `/complete-collection/`, so
 *   neither reaches this page as the code stands. Both are ONE LINE from being
 *   reopened — the A/B surface list has been widened three times already
 *   (1.19.241, 1.19.267, 1.19.296) — and the next widening must not quietly
 *   re-admit a parent overlay to a teacher page.
 *
 * @param bool $show Whether the surface may render.
 * @return bool
 */
function bhp_school_readalouds_suppress_parent_surfaces( $show ) {
	return bhp_school_readalouds_is_page() ? false : $show;
}
add_filter( 'bhp_show_parent_popup', 'bhp_school_readalouds_suppress_parent_surfaces' );
add_filter( 'bhp_show_parent_ab_popup', 'bhp_school_readalouds_suppress_parent_surfaces' );
add_filter( 'bhp_show_exit_intent_popup', 'bhp_school_readalouds_suppress_parent_surfaces' );
add_filter( 'bhp_show_quiz_cta', 'bhp_school_readalouds_suppress_parent_surfaces' );

/**
 * Suppress the sitewide footer capture block on the teacher page.
 *
 * ⛔ SEPARATE FROM THE FOUR ABOVE ON PURPOSE. `bhp_should_show_footer_capture()`
 *    does NOT route through `bhp_should_show_any_popup()` — it is static markup,
 *    not an overlay, and it keeps its own exclusion list. Folding it into the
 *    same `add_filter` line would read as though one gate governed both.
 *
 * ⛔ ITS OFFER IS THE PARENT MAGNET. `.claude/rules/funnels.md` already states
 *    the principle this applies: *"A capture FORM for the parent magnet on the
 *    teacher page would be a funnel-isolation breach ... The rule is applied to
 *    the form, which is what changes funnel state."* That sentence was written
 *    about `/teachers/`; item 524's two-page architecture is what extends it
 *    here.
 *
 * @param bool $show Whether the footer capture may render.
 * @return bool
 */
function bhp_school_readalouds_suppress_footer_capture( $show ) {
	return bhp_school_readalouds_is_page() ? false : $show;
}
add_filter( 'bhp_show_footer_capture', 'bhp_school_readalouds_suppress_footer_capture' );

/* ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐ 1.19.341 (`CYCLE171-LD-341` item 3) — SUPERSEDES THE 1.19.333 BLOCK BELOW.
 *     THE PAGE IS INDEXABLE. BOTH ROBOTS FILTERS ARE GONE.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⭐ FOUNDER REVERSAL, 2026-08-31, of carrier item 525's "not for SEO" ruling.
 *    Relayed in the build brief as: *"Yes... make it indexable."* Recorded with
 *    its provenance (§9.2 rule 2) because a relayed quote and a first-hand one
 *    are different evidence.
 *
 * ⛔ WHAT WAS REMOVED, NAMED SO IT IS NOT RE-DERIVED AS MISSING:
 *    `bhp_school_readalouds_robots()` on `wp_robots`, and
 *    `bhp_school_readalouds_rankmath_robots()` on `rank_math/frontend/robots`.
 *    Both the functions AND their registrations are deleted.
 *
 * ⛔ THEY WERE DELETED RATHER THAN LEFT RETURNING THEIR INPUT. A callback that
 *    is registered and does nothing still reports success to every reader of
 *    this file — the same "a tunable that tunes nothing is worse than an absent
 *    one" reasoning `bhp_blog_capture_band_after_paragraph()` records for the
 *    retired heading anchor. With no theme filter in the way, the page's OWN
 *    Rank Math `rank_math_robots` post meta governs, which is the stated intent
 *    of the reversal.
 *
 * ⭐ THE POST META IS ALREADY `index,follow` ON PRODUCTION — set and verified
 *    stored as a proper array by chief-of-staff on 2026-08-31, BEFORE this code
 *    change. So the deploy does not need a WP-CLI meta step the way 1.19.333's
 *    deploy plan did; it needs the meta CONFIRMED, which the deploy plan lists.
 *
 * ⭐ THE XML SITEMAP FOLLOWS THE META, NOT THIS FILE. Rank Math reads
 *    `rank_math_robots` post meta for sitemap inclusion (the 1.19.333 block
 *    below states this correctly and it is still true). The `noindex` meta was
 *    what excluded the URL; with the meta at `index,follow` and no frontend
 *    filter overriding it, the page is eligible for `page-sitemap.xml`.
 *
 * ⚠⚠ WHAT STAGING CANNOT PROVE, STATED PLAINLY RATHER THAN GLOSSED:
 *    staging2 is SITE-WIDE `noindex` at the environment level, so a staging page
 *    fetch will show `noindex` no matter what this file does. Staging can prove
 *    (a) the two callbacks no longer exist, (b) nothing is registered on either
 *    hook for this page, and (c) the stored meta resolves to index,follow.
 *    It CANNOT prove the rendered production robots tag or sitemap membership.
 *    Those are POST-DEPLOY PRODUCTION CHECKS and are listed as such in
 *    DEPLOY-PLAN.md. Do not report them as verified from staging.
 *
 * ⛔ `inc/positivity-news.php`'s robots filters are UNTOUCHED. That page STAYS
 *    noindex by the founder's ruling the same night. Do not generalise this
 *    reversal to it.
 *
 * ⛔ The `follow` half of the old behaviour is moot now, but the reasoning the
 *    block below gives for it — this page links to the shop, the books and the
 *    educator resources, so nofollow would leak internal link equity — is why
 *    an indexable state is the more natural one for it anyway.
 *
 * ───────────────────────────────────────────────────────────────────────────
 * ⛔ SUPERSEDED 1.19.333 BLOCK, PRESERVED VERBATIM BELOW rather than deleted,
 *    so the movement stays visible and the reasoning is not re-derived. Every
 *    statement in it was true of 1.19.333. Its RULING is reversed; its
 *    TECHNICAL observations (noindex is not nofollow, a robots.txt block hides
 *    the directive rather than serving it, WordPress writes the robots meta and
 *    Rank Math overwrites it, the sitemap reads post meta not the frontend
 *    filter) all still hold and are the reason the removal is safe.
 * ───────────────────────────────────────────────────────────────────────────
 *
 * ⭐ 1.19.333 — `noindex`. THE PAGE STAYS IN THE NAV; IT LEAVES THE INDEX.
 *
 * [SUPERSEDED 2026-08-31 — reversed by the founder ruling above.]
 *
 * Andrew Signore, carrier item **525**, relayed in the brief: this page is
 * **not for SEO**. It is the destination of a personal ask to a named school,
 * of an email, and of the primary navigation. It is not a page anybody should
 * arrive at from a search for "author visits Boise".
 *
 * ⛔ `noindex` IS NOT `nofollow` HERE, AND THE DIFFERENCE IS DELIBERATE. The
 *    review pages in `inc/reviews.php` take both because they are dead ends
 *    reached from an email. This page links to the shop, to the books and to
 *    the educator resources; telling crawlers to ignore those links would leak
 *    internal link equity away from pages that DO want to rank.
 *
 * ⛔ AND IT IS NOT `robots.txt`. `inc/seo-hygiene.php` records Google's own
 *    position at length: a URL a crawler cannot fetch is a URL whose `noindex`
 *    it never sees. The directive has to be served on the page.
 *
 * ⛔ BOTH FILTERS ARE SET, for the reason `inc/reviews.php` already states:
 *    WordPress writes the robots meta and Rank Math overwrites it. Setting one
 *    is how a page ships believing it is noindexed and is not.
 *
 * ⭐ THE XML SITEMAP IS A SEPARATE MECHANISM AND IS HANDLED AS A DEPLOY STEP.
 *    Rank Math reads its own `rank_math_robots` POST META for sitemap
 *    inclusion, NOT the frontend filter below — so the filter alone would
 *    produce a page that says "noindex" and is still advertised in the
 *    sitemap. The meta is set by WP-CLI in the deploy plan, not from theme
 *    code, because writing post meta on every front-end request is not a
 *    theme's job.
 * ═══════════════════════════════════════════════════════════════════════════ */

/*
 * ⭐⭐ 1.19.341 — `bhp_school_readalouds_robots()` and
 *     `bhp_school_readalouds_rankmath_robots()` STOOD HERE and are DELETED.
 *
 * They forced `noindex` on `wp_robots` and `rank_math/frontend/robots`
 * respectively. The founder reversed item 525 on 2026-08-31; see the block
 * comment above for the full record, including what staging can and cannot
 * prove about the result. Nothing in this theme now writes a robots directive
 * for /school-read-alouds/ — the page's own `rank_math_robots` post meta
 * governs, which is exactly what the reversal asked for.
 *
 * ⛔ DO NOT "RESTORE" THESE BY REFLEX if a future reader finds the page in the
 *    index and assumes a regression. Being in the index IS the ruled state.
 *
 * ⛔ `inc/positivity-news.php` keeps its equivalent pair. That page stays
 *    noindex. `tests/test-cycle170-bundle.php` § 6 asserts BOTH halves — this
 *    page's absence and that page's presence — so the two cannot drift into
 *    each other.
 */
