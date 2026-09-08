<?php
/**
 * THE INSTANT KIT MODAL — theme 1.19.392, `CYCLE179-LD-KIT-MODAL-392`.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐ WHAT ANDREW ASKED FOR, VERBATIM (seal 1200, RELAYED through the Chief of
 *    Staff, NOT witnessed by this desk):
 * ═══════════════════════════════════════════════════════════════════════════
 *
 *   "so a user signs up- a pop up happens that shows them the reluctant
 *    reader kit- they can scroll and see the entire thing and print it - and
 *    a small bar at the top says also sent tor your email.. and they should
 *    be able to exit out and see the email - we still have email capture -
 *    they just get instant gratification with the free kit"
 *
 * ⭐ THE ONE SENTENCE THAT GOVERNS THE WHOLE FILE: the capture is unchanged
 *    and the email still sends. This adds a reward AFTER the address is
 *    already banked, and it removes nothing.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔⛔ WHY A TOKEN AND NOT A QUERY PARAMETER — the load-bearing design choice
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * The bar has to show the address the visitor typed. The obvious route is to
 * put it in the success redirect, and that route is CLOSED:
 *
 *   1. An email address in a query string is personal data in the browser
 *      history, in the server access log, in the Referer header of every
 *      asset the page then loads, and in anything the visitor pastes.
 *   2. `bhp_mailchimp_signup_redirect()` already puts an address in the URL
 *      on the ERROR path to repopulate a form, and `inc/mailchimp.php`'s own
 *      quiz docblock names that as the reason the quiz endpoint refuses to
 *      call it. ⭐ That judgement is inherited here rather than re-argued.
 *
 * ⭐ SO THE URL CARRIES AN OPAQUE, SINGLE-USE TOKEN AND NOTHING ELSE. The
 *    address lives in a transient, under a SALTED HASH of the token (never
 *    the token itself, so a database dump is not a set of working keys), and
 *    it is DELETED ON FIRST READ. A refresh, a back-navigation or a forwarded
 *    link finds nothing.
 *
 * ⭐ THE SHAPE IS DELIBERATELY `inc/conversion-token.php`'s, function for
 *    function. That file solved the same problem three weeks ago and is
 *    already staging- and production-proven. A second, differently-shaped
 *    token store would be a second thing to reason about for no gain.
 *
 * ⚠️ ONE HONEST DIFFERENCE, STATED RATHER THAN BURIED: the conversion token
 *    stores NO PII by whitelist, and says so. This one stores the address, on
 *    purpose, because the founder's spec is that the bar shows it. The
 *    containment is: server side only · salted-hash key · deleted on first
 *    read · fifteen-minute ceiling · never written to a log, an option, a
 *    cookie, an event or a URL. ⛔ It is NOT a store of leads and must never
 *    be read as one; `bhp_process_signup()` remains the only place an address
 *    is handled for real.
 *
 * ⛔ ANDREW HAS A PARKED DECISION ON FAILURE-PATH EMAIL STORAGE (see
 *    `inc/mailchimp.php`). Nothing here touches it: this token is minted ONLY
 *    on the success path, at the one statement that is reached exactly when a
 *    real subscriber really landed. A failed signup mints nothing, so a failed
 *    signup stores no address and opens no modal.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐ WHY IT RENDERS FROM `wp_footer` AND NOT FROM THE KIT PAGE
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * TWELVE surfaces deliver this kit — the kit page's three forms, the
 * exit-intent popup, the parent A/B popup, the parent popup, the footer
 * capture, three blog capture bands, `/free-resources/`, `/market-capture/`,
 * `/read-aloud/` and the quiz's parent route. Every one of them returns the
 * visitor to the page they were already on. Hanging the modal off any single
 * template would have covered one of the twelve.
 *
 * ⭐ `wp_footer` covers all of them at once, and costs NOTHING on an ordinary
 *    page load: with no valid token in the request this file prints not one
 *    byte, registers no script, enqueues no style and reads no option.
 *
 * ⛔ IT MINTS NO FUNNEL STATE. No `bhp_parent_popup*` or `bhp_mariana_popup*`
 *    storage key, no `parent_popup`/`teacher_popup` analytics prefix, no
 *    `data-popup-config`, no `dataLayer` push, no suppression flag. The parent
 *    and teacher funnels are byte-untouched, which is the standing rule in
 *    `.claude/rules/funnels.md`.
 *
 * ⭐ IT DOES WEAR `.mariana-popup.is-open`, and that is the ONE deliberate
 *    coupling. `mariana-popup.js`'s `isAnotherOverlayOpen()` treats that
 *    selector as "an overlay is up" and defers its own timers. Wearing it
 *    means the exit-intent popup cannot paint over the kit the visitor just
 *    earned. `template-parts/acquisition/signup-modal.php` uses the same trick
 *    for the same reason; this follows the precedent rather than inventing a
 *    second convention. ⛔ It carries NO `data-popup-config`, so the engine
 *    never adopts it and never manages its lifecycle.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐ 1.19.404 · PREVIEW ONLY. THERE IS NO PDF IN THIS PANEL ANY MORE.
 *     `CYCLE179-LD-BUILD-404-KIT-PREVIEW-ONLY`, founder decision seal 1357.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⭐ ANDREW, VERBATIM (seal 1357, RELAYED through the Chief of Staff, NOT
 *    witnessed by this desk): *"maybe do the preview and dont let them
 *    download it or print it from there?"*
 *
 * ⭐ WHAT THE PANEL IS NOW: the ELEVEN page images, on every device, scrollable,
 *    full width. One Close button and the round close. Nothing else.
 *
 * ⛔ REMOVED IN 1.19.404, AND EVERY REMOVAL IS DELIBERATE:
 *      · the same-origin PDF `<iframe>` and the whole `__viewer` element;
 *      · `navigator.pdfViewerEnabled` — there is no branch left to choose;
 *      · `bhp_kit_modal_forced_view()` and the staging-only `bhp_kit_view`
 *        QA flag, which existed ONLY to reach the image branch on a desktop
 *        browser. The image branch is now the only branch, so the flag is
 *        dead weight and dead weight on a request-read parameter is worse
 *        than dead weight;
 *      · the **Download PDF** control, the **Print** control, and the
 *        *"Open the full kit in a new tab"* link;
 *      · ⛔⛔ the `data-bhp-kit-pdf` ATTRIBUTE. THE PDF URL DOES NOT APPEAR IN
 *        THIS PANEL'S MARKUP AT ALL. A right-click, a view-source or a
 *        DevTools read hands out nothing. The printable file arrives BY EMAIL
 *        and by no other route from here. The page IMAGES are exposed, which
 *        is intended: they are the preview.
 *
 * ⚠️ WHY THIS SUPERSEDES THE iOS WORK OF 1.19.403 RATHER THAN EXTENDING IT.
 *    Andrew opened a minted token on his own iPhone on 2026-09-07 at 21:19 and
 *    sent a screenshot: Safari reports `navigator.pdfViewerEnabled === true`
 *    while its inline frame paints PAGE ONE ONLY, oversized and unscrollable.
 *    The feature probe was therefore answering a question that Safari answers
 *    wrongly. ⛔ The fix is not a better probe. The fix is that there is
 *    nothing to probe. **Evidence class: the founder's own device, RELAYED to
 *    this desk as a screenshot; not reproduced on hardware here.**
 *
 * ⭐ THE READINESS GATE IS KEPT. `bhp_get_reluctant_reader_download()` must
 *    still report `ready`, because a site with no kit configured has nothing to
 *    email and should show the ordinary success message rather than a preview
 *    of a file that will never arrive. ⛔ Its URL is READ FOR THE GATE AND
 *    NEVER PRINTED.
 *
 * ⛔ WHAT WAS DELIBERATELY NOT TOUCHED: the single-use token, the focus trap,
 *    Escape, backdrop close, the body scroll lock, `overscroll-behavior`, and
 *    1.19.403's `dvh` panel sizing. This build changes WHAT IS IN the panel,
 *    not how the panel behaves.
 *
 * ⚠️ THE `@media print` BLOCK AT THE FOOT IS LEFT BYTE-UNTOUCHED, AND THAT IS
 *    A REPORTED DECISION RATHER THAN AN OVERSIGHT. The panel no longer OFFERS
 *    a print control. A browser-initiated print (Ctrl+P) still routes to the
 *    kit pages rather than to the page behind. Whether that should also go is
 *    Andrew's call, not this desk's, and it is raised as finding F2.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔ SUPERSEDED 1.19.404, PRESERVED SO THE MOVEMENT STAYS VISIBLE — read this
 *    only as history. Until 1.19.403 the section below described the panel.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ~~The primary reader is the browser's own PDF viewer in a same-origin
 * iframe, which scrolls all eleven pages and prints. Where no PDF viewer
 * exists — iOS Safari renders page one of a framed PDF and refuses to scroll
 * it — the fallback shows the page images the theme actually has.~~
 *
 * ⭐⭐ 1.19.392 · THE FALLBACK NOW CARRIES ALL ELEVEN PAGES, AND THE HONESTY
 *     SENTENCE IS GONE BECAUSE IT IS NO LONGER TRUE. `design-creative`
 *     rendered pages 5 to 11 at both widths for
 *     `CYCLE179-DES-KIT-SAMPLE-P5-11` (2026-09-07) and they are shipped in
 *     `assets/img/kit-sample/`, which now holds 22 files: two sizes of eleven
 *     pages. The fallback shows every one of them inline, so "scroll and see
 *     the entire thing" holds on a browser with no PDF viewer as well as on
 *     one with a viewer.
 *
 * ⛔ SUPERSEDED, PRESERVED SO THE MOVEMENT STAYS VISIBLE. Until 1.19.392 the
 *    theme had pages 1 to 4 and only 1 to 4, and the fallback said so in
 *    those words — *"Pages 5 to 11 are in the PDF file"* — and linked out.
 *    That sentence was correct then and would be a lie now, so it is removed
 *    rather than softened.
 *
 * ⛔⛔ PAGES 5 TO 11 ARE THE GATED HALF AND MUST NEVER REACH THE PUBLIC PAGE.
 *     `template-parts/acquisition/kit-sample-preview.php` builds its own
 *     explicit four-row array and is NOT driven by a directory listing, so
 *     shipping seven more files into the same folder does not leak them.
 *     ⛔ Do not "helpfully" convert that template to a loop over this
 *     function: putting page 7 on the public page gives away the cliffhanger
 *     the email gate is trading on. A test asserts the public template still
 *     names no page above 4.
 *
 * ⚠️ THE IMAGES CARRY NO BORDER OF THEIR OWN. They are faithful full-page
 *    renders; pages 3 to 7 and 9 are cream and page 11 is white, so on a
 *    light modal ground their edges do not read. The container is given a
 *    hairline and a shadow in CSS below. ⛔ Do not solve it by re-rendering
 *    with a baked-in border.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐ COPY RAILS APPLIED TO EVERY PRINTED STRING IN THIS FILE
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Standing Rules §9.1 (no "we"/"us"/"our" in customer-facing words) · §9.4
 * (American spelling) · no em dash in any printed string · no outcome claim,
 * no reading time, no page-count promise, no review, no rating, no scarcity.
 *
 * ⛔ NOTHING IS NAMED THAT THE ARTEFACT DOES NOT CONTAIN.
 */

defined( 'ABSPATH' ) || exit;

/**
 * The query argument. Short, opaque, and deliberately unlike anything a
 * human would type by accident.
 */
const BHP_KIT_MODAL_ARG = 'bhp_kit';

/**
 * Transient key prefix. The stored key is a salted hash of the token, never
 * the token, so reading the options/transient store yields nothing usable.
 */
const BHP_KIT_MODAL_PREFIX = 'bhp_km_';

/**
 * The one lead magnet this modal is for. A signup for any other magnet mints
 * no token and opens nothing, which is why this is a constant rather than a
 * value read from the request.
 */
const BHP_KIT_MODAL_LEAD_MAGNET = 'reluctant_reader_adventure_kit';

/**
 * Token lifetime. Fifteen minutes, filterable, HARD BOUNDED so a filter can
 * never make an address-bearing transient effectively permanent.
 *
 * ⭐ In practice the token lives for the length of one 303 redirect. The
 *    ceiling exists for the slow-network case, not as a working window.
 */
function bhp_kit_modal_ttl() {
	$ttl = (int) apply_filters( 'bhp_kit_modal_ttl', 15 * MINUTE_IN_SECONDS );

	return max( 60, min( $ttl, HOUR_IN_SECONDS ) );
}

/**
 * Storage key for a token. Salted hash, never the raw token.
 */
function bhp_kit_modal_key( $token ) {
	$token = (string) $token;
	if ( '' === $token ) {
		return '';
	}

	return BHP_KIT_MODAL_PREFIX . substr( hash_hmac( 'sha256', $token, wp_salt( 'nonce' ) ), 0, 32 );
}

/**
 * Mint a single-use kit token for one real, completed kit signup.
 *
 * ⛔ CALLED FROM EXACTLY ONE PLACE — `bhp_process_signup()`, beside the
 *    conversion-token mint, which is the only statement in that function
 *    reached exactly when Mailchimp accepted a real subscriber. Adding a
 *    second caller would be adding a second definition of "converted".
 *
 * @param string $email       The address the visitor typed.
 * @param string $lead_magnet The registry key of the magnet they signed up for.
 * @return string The token, or '' if one could not be minted.
 */
function bhp_kit_modal_mint( $email, $lead_magnet ) {
	$lead_magnet = sanitize_key( (string) $lead_magnet );
	if ( BHP_KIT_MODAL_LEAD_MAGNET !== $lead_magnet ) {
		return '';
	}

	$email = sanitize_email( (string) $email );
	if ( '' === $email || ! is_email( $email ) ) {
		return '';
	}

	$token = wp_generate_password( 32, false, false );
	$key   = bhp_kit_modal_key( $token );
	if ( '' === $key ) {
		return '';
	}

	// ⛔ WHITELIST, NOT BLACKLIST. Exactly these three keys are ever stored,
	//    so a future caller passing a name, an IP or a payload cannot widen
	//    what this transient holds by accident.
	$payload = array(
		'email'       => $email,
		'lead_magnet' => $lead_magnet,
		'minted'      => time(),
	);

	if ( ! set_transient( $key, $payload, bhp_kit_modal_ttl() ) ) {
		return '';
	}

	return $token;
}

/**
 * Append a freshly minted token to a URL.
 *
 * ⚠️ DEGRADES TOWARDS THE CUSTOMER, NEVER AGAINST THEM. If a token cannot be
 *    minted, the URL is returned untouched: the visitor still completes the
 *    signup, still lands where they were going, and still gets the kit by
 *    email. All that is lost is the instant view. ⛔ A storage failure must
 *    never be able to break a redirect.
 */
function bhp_kit_modal_add_token( $url, $email, $lead_magnet ) {
	$url = (string) $url;
	if ( '' === $url ) {
		return $url;
	}

	$token = bhp_kit_modal_mint( $email, $lead_magnet );
	if ( '' === $token ) {
		return $url;
	}

	return add_query_arg( BHP_KIT_MODAL_ARG, $token, $url );
}

/**
 * Consume the token on the current request, if there is a valid one.
 *
 * ⭐ SINGLE-USE IS ENFORCED HERE, BY DELETION BEFORE RETURN, and the result is
 *    memoised so a second caller in the same page load sees the same answer
 *    rather than a spent token.
 *
 * @return array|false The stored payload on success, false otherwise.
 */
function bhp_kit_modal_consume() {
	static $resolved = null;

	if ( null !== $resolved ) {
		return $resolved;
	}

	$resolved = false;

	if ( empty( $_GET[ BHP_KIT_MODAL_ARG ] ) ) {
		return $resolved;
	}

	$token = sanitize_text_field( wp_unslash( $_GET[ BHP_KIT_MODAL_ARG ] ) );

	// Shape check before any storage read: the mint format is exactly 32
	// alphanumerics, so anything else is not worth a database round trip.
	if ( ! preg_match( '/^[A-Za-z0-9]{32}$/', $token ) ) {
		return $resolved;
	}

	$key = bhp_kit_modal_key( $token );
	if ( '' === $key ) {
		return $resolved;
	}

	$payload = get_transient( $key );
	if ( ! is_array( $payload ) || empty( $payload['email'] ) ) {
		return $resolved;
	}

	if ( BHP_KIT_MODAL_LEAD_MAGNET !== ( isset( $payload['lead_magnet'] ) ? $payload['lead_magnet'] : '' ) ) {
		delete_transient( $key );
		return $resolved;
	}

	// ⭐ BURN IT. Everything after this point is a one-way door.
	delete_transient( $key );

	$resolved = $payload;

	return $resolved;
}

/**
 * The alt text for the eleven kit pages, in page order.
 *
 * ⭐ SOURCE OF RECORD: `design-creative`, `ALT-TEXT.md` in the round's review
 *    folder. ⛔ NOT GENERATED, NOT SUMMARISED FROM THE FILENAME. For a screen
 *    reader user these sentences are the only representation of the kit, so a
 *    `Page 5 of the kit` placeholder would be very nearly `alt=""`.
 *
 * ⛔ AN ALT ATTRIBUTE IS CUSTOMER-FACING COPY AND IS HELD TO THE COPY RAILS.
 *    `template-parts/acquisition/kit-sample-preview.php` already established
 *    that on this exact artefact: it carries no em dash and no duration
 *    claim, and it renders the printed heading as *"Chapter 10, The Dive"*
 *    because the page itself sets it with an em dash. The seven new rows
 *    follow that precedent — the two printed headings that use an em dash are
 *    written with a comma, and page 5's closing quotation is given as the
 *    single word the page ends on rather than misquoted with punctuation it
 *    does not have.
 *
 * ⚠️ ROWS 1 TO 4 ARE BYTE-COPIES OF THE PUBLIC TEMPLATE'S APPROVED STRINGS.
 *    They are duplicated rather than shared because that template builds its
 *    array inline and refactoring it would change a live public page inside a
 *    build whose brief is the modal. `test-kit-instant-modal.php` asserts the
 *    four match, so the two copies cannot drift silently.
 *
 * ⭐ NO CHILD IS NAMED. The story's characters are described by role, which is
 *    the convention rows 1 to 4 already set. Nothing in quotation marks is
 *    anything but verbatim off the page.
 */
function bhp_kit_modal_page_alt() {
	return array(
		1  => __( 'Kit cover on a deep navy ground: the Brave Hearts compass mark, a gold "Free Printable Adventure" badge, the title "The Reluctant Reader Adventure Kit", a line describing it as a real chapter from the book, and the underwater cover of the Mariana Trench book below it.', 'brave-hearts' ),
		2  => __( 'Page two of the kit, a note for parents and grandparents on cream: the headline "If Reading Has Started to Feel Like a Battle, Try Something Different", three numbered tips to let them choose the pace, ask adventure questions, and stop at the cliffhanger, and a dark green box reassuring parents the kit is not a reading assessment.', 'brave-hearts' ),
		3  => __( 'The chapter opener from the book, reproduced exactly as printed and framed in white on the kit\'s cream page: a pencil drawing of a deep-sea submersible being lifted by a crane from a research ship, the heading "Chapter 10, The Dive", and the first lines of the chapter.', 'brave-hearts' ),
		4  => __( 'The chapter continues, reproduced as printed: the submersible swings out over open sea, the countdown runs down to a splash, and the sub starts going down. The page stops mid-sentence.', 'brave-hearts' ),
		5  => __( 'The chapter continues, reproduced as printed on the kit\'s cream page: the sub sinks fast into blue with faces pressed to the small round windows, the light outside goes from bright blue to darker blue to blue-black, and the page ends on the word "nothing".', 'brave-hearts' ),
		6  => __( 'The chapter continues, reproduced as printed, with a pencil drawing across the top: inside the submersible, a girl rests one hand on a large round porthole with her dog in her lap and the pilot at the controls behind her, dark water beyond the glass. Below the drawing an italic Ocean Fact explains that the deeper you go the heavier the water above you becomes, creating powerful pressure. Then the ocean turns completely black, a long creaking sound drifts in, and the pilot explains calmly that it is the ocean squeezing the sub, which is safe inside, strong as a turtle\'s shell.', 'brave-hearts' ),
		7  => __( 'The last kit page of the chapter, reproduced as printed: a small line-drawn sun-over-water ornament marks a scene break, then the lights go out inside the submersible, the two friends can barely see each other in the dark, and she reaches out and holds his paw. The chapter stops there.', 'brave-hearts' ),
		8  => __( 'A navy page headed "Wonder for a Moment": the printed question asks the reader what they think the story\'s main character does next, now that the lights have just gone out. Below it a cream writing panel with a thin gold keyline and four ruled lines gives space to answer in pen, and an italic line underneath says to say it out loud or write it down, and that there is no wrong answer.', 'brave-hearts' ),
		9  => __( 'An activity page on cream headed "Explorer Activity, What would YOU dive in?", asking the child to draw the machine they would take to the bottom of the ocean. On the left, five "Think it through" prompts about warmth, seeing in the dark, resisting the ocean\'s squeeze, the sound it makes, and where the dog sits. On the right, a large empty "Field sketch" box to draw in, with a ruled "Name your sub" line under it. At the foot, a navy Ocean Fact box explains that a round shell spreads the squeeze evenly, like a turtle\'s shell.', 'brave-hearts' ),
		10 => __( 'A navy page headed "The Adventure Continues", inviting the child to pick the next adventure. Three book covers sit side by side: Volume I The Mariana Trench underwater, Volume II Mount Everest in the snow, and Volume III The Amazon in the rainforest, each with a one-line description on cream beneath it. A gold "Choose an adventure" button follows, then the line "Short chapters. Illustrations throughout. Real places. Big adventures.", and at the foot the Brave Hearts compass mark, the words Brave Hearts Publishing, the line "Big Places. Brave Hearts.", the website address and the copyright notice.', 'brave-hearts' ),
		11 => __( 'A black-and-white coloring page: a detailed line drawing of a deep-sea submersible with a large round front window, robotic arm, thruster and landing skids, hovering over a rocky seabed with coral, a jellyfish, an anglerfish, rising bubbles and light beams coming down from the surface. A navy band across the foot carries the compass mark, the line "Color it in, then scan to see all three adventures", the website address, and a QR code.', 'brave-hearts' ),
	);
}

/**
 * The page images the theme actually ships, in page order.
 *
 * ⭐ ELEVEN as of 1.19.392. A page is listed here only if BOTH of its files
 *    exist on disk, so a theme deployed without `assets/img/kit-sample/`
 *    renders the link-out fallback instead of a column of broken images, and
 *    a partially-shipped folder degrades to whatever really landed rather
 *    than claiming pages it cannot show.
 */
function bhp_kit_modal_sample_pages() {
	$dir  = get_template_directory() . '/assets/img/kit-sample/';
	$uri  = get_template_directory_uri() . '/assets/img/kit-sample/';
	$alt  = bhp_kit_modal_page_alt();
	$out  = array();

	for ( $n = 1; $n <= 11; $n++ ) {
		$slug  = sprintf( 'kit-sample-p%02d', $n );
		$small = $slug . '-600.jpg';
		$large = $slug . '-1200.jpg';

		if ( ! file_exists( $dir . $small ) || ! file_exists( $dir . $large ) ) {
			continue;
		}

		/*
		 * ⛔⛔ THE WIDTH AND HEIGHT ARE NOT COSMETIC AND THEY ARE NOT FOR
		 *     LAYOUT SHIFT. WITHOUT THEM THE FALLBACK DOES NOT WORK AT ALL.
		 *
		 * ⭐ OBSERVED ON STAGING 1.19.392, NOT REASONED ABOUT. With no
		 *    intrinsic dimensions a not-yet-loaded `loading="lazy"` image has
		 *    ZERO height. All eleven therefore collapsed into a 1,114 px
		 *    stack inside a 323 px scroller, nothing below the first ever
		 *    came near enough to the viewport to trigger a fetch, and the
		 *    check found `naturalWidth === 0` on pages 2 to 11 even after
		 *    each had been scrolled to. ⛔ The fallback existed to let a
		 *    visitor with no PDF viewer scroll the whole kit, and it was
		 *    showing them page one and ten invisible boxes.
		 *
		 * ⭐ WITH THE ATTRIBUTES the browser reserves the real box before the
		 *    bytes arrive, the stack becomes its true height, and lazy
		 *    loading resolves each page as it approaches. The CSS keeps
		 *    `height:auto`, so the attributes set the ratio and never the
		 *    rendered size.
		 *
		 * ⭐ MEASURED, NOT ASSUMED. `getimagesize()` reads the file that is
		 *    actually on disk, so a re-render at a different size cannot
		 *    leave a stale hardcoded number behind. It runs only on a request
		 *    that already carries a valid token, which is at most one page
		 *    load per conversion. A file it cannot measure contributes no
		 *    attributes rather than a guessed pair.
		 */
		$size = @getimagesize( $dir . $large );
		$w    = ( is_array( $size ) && ! empty( $size[0] ) ) ? (int) $size[0] : 0;
		$h    = ( is_array( $size ) && ! empty( $size[1] ) ) ? (int) $size[1] : 0;

		$out[] = array(
			'n'      => $n,
			'src'    => $uri . $large,
			'srcset' => $uri . $small . ' 600w, ' . $uri . $large . ' 1200w',
			'alt'    => isset( $alt[ $n ] ) ? $alt[ $n ] : '',
			'w'      => $w,
			'h'      => $h,
		);
	}

	return $out;
}

/*
 * ⛔⛔ `bhp_kit_modal_forced_view()` WAS DELETED IN 1.19.404, AND ITS ABSENCE IS
 *     ASSERTED BY A TEST SO IT CANNOT CREEP BACK.
 *
 * ⭐ WHAT IT WAS: a staging-only QA flag (`?bhp_kit_view=images|pdf`, gated on
 *    `BHP_Analytics_Config::is_staging()`) that forced one of the modal's TWO
 *    reader branches. It existed for exactly one reason -- every desktop
 *    browser on this machine reports `navigator.pdfViewerEnabled === true`, so
 *    the image branch could not otherwise be inspected without hand-editing
 *    the DOM, which proves nothing about what the theme ships.
 *
 * ⭐ WHY IT IS GONE: 1.19.404 has ONE branch. The images are what every device
 *    gets. A flag that selects between one thing and nothing is not a QA aid,
 *    it is a request-read parameter with no job, and the safest version of a
 *    parameter with no job is the one that does not exist.
 *
 * ⚠️ THE FINDING IT CARRIED IS STILL TRUE AND IS KEPT HERE RATHER THAN LOST
 *    WITH THE CODE: `wp_get_environment_type()` returns `local` on PRODUCTION
 *    AND on staging2 -- measured over WP-CLI against both installs on
 *    2026-09-07, and independently recorded in `inc/amazon-reviews.php`.
 *    ⛔ ANY future code that gates behaviour on it is gating on nothing.
 *    `BHP_Analytics_Config::is_staging()` is the theme's one working test.
 */

/**
 * Render the modal, but only for a request that carries a live token.
 *
 * ⛔ THREE GATES, ALL OF WHICH MUST PASS: a valid unspent token · the token's
 *    magnet is the kit · the kit PDF is configured and ready. The third is the
 *    same `bhp_get_reluctant_reader_download()` flag the kit page's own panel
 *    is gated on, so an environment with no PDF set shows the visitor their
 *    ordinary success message rather than an empty reader.
 */
function bhp_kit_modal_render() {
	if ( is_admin() || is_feed() || is_embed() ) {
		return;
	}

	$payload = bhp_kit_modal_consume();
	if ( ! $payload ) {
		return;
	}

	if ( ! function_exists( 'bhp_get_reluctant_reader_download' ) ) {
		return;
	}

	$download = bhp_get_reluctant_reader_download();
	if ( empty( $download['ready'] ) || empty( $download['url'] ) ) {
		return;
	}

	/*
	 * ⛔⛔ `$download['url']` IS READ FOR THE READINESS GATE ABOVE AND IS
	 *     DELIBERATELY NOT ASSIGNED TO A VARIABLE HERE. 1.19.404 prints no PDF
	 *     URL anywhere in this panel -- not in an attribute, not in an href,
	 *     not in the inline script. Founder decision seal 1357.
	 *     ⛔ Do not reintroduce a `$pdf` local "just for the print handler" or
	 *        "just for a fallback link". A test asserts the URL is absent from
	 *        the rendered markup.
	 */
	$email = $payload['email'];
	$pages = bhp_kit_modal_sample_pages();
	?>
<div class="bhp-kit-modal mariana-popup is-open" id="bhp-kit-modal" role="dialog" aria-modal="true" aria-labelledby="bhp-kit-modal-title" data-bhp-kit-modal>
	<div class="bhp-kit-modal__backdrop" data-bhp-kit-close></div>
	<div class="bhp-kit-modal__dialog" role="document">
		<h2 id="bhp-kit-modal-title" class="bhp-kit-modal__sr"><?php esc_html_e( 'Your Reluctant Reader Adventure Kit', 'brave-hearts' ); ?></h2>

		<?php
		/*
		 * ⭐⭐ THE BAR LINE IS THE FOUNDER'S OWN SENTENCE PLUS HIS OWN APPROVED
		 *     ADDITION, AND IT IS REPRODUCED WORD FOR WORD.
		 *
		 * ⭐ Andrew, verbatim (seal 1358, RELAYED, not witnessed by this desk):
		 *    *"Well they have to put their email in and submit before they can
		 *    get the preview - then at the top of the preview it states \" This
		 *    Free Chapter Activity was sent to your email, see your email to
		 *    download it now\""*
		 *
		 * ⭐ The trailing clause *", with your PARENT10 code for 10% off the
		 *    Collection"* is his APPROVED addition (seal 1362). It is the one
		 *    payload of email E1 that this panel would otherwise not reproduce.
		 *
		 * ⛔⛔ THE STRING IS LOCKED PROSE. Standing Rules §9 -- approved copy is
		 *     not silently rewritten. ⛔ `PARENT10` must survive VERBATIM: it is
		 *     a real coupon code a parent will type, so a line break inside it,
		 *     a lower-casing, a smart-quote pass or a "10 %" spacing fix would
		 *     hand out a code that does not work. It is NOT wrapped in its own
		 *     element, deliberately, so the sentence stays one text node and an
		 *     exact-string assertion can prove it shipped intact.
		 *
		 * ⚠️ IT WRAPS, AND WRAPPING IS THE DESIGN. At 320 this is four or five
		 *    rendered lines. The brief's allowance is explicit -- two rows at
		 *    phone widths are fine -- and a truncated coupon code would be
		 *    worse than a taller bar. ⛔ Do not add `text-overflow:ellipsis` to
		 *    this line. The ADDRESS below it still ellipsises, because an
		 *    address is recognisable from its head and a coupon is not.
		 *
		 * ⭐ COPY RAILS CHECKED ON THIS EXACT STRING: no "we/us/our" (§9.1),
		 *    American spelling (§9.4), no em dash, no outcome claim.
		 */
		?>
		<div class="bhp-kit-modal__bar">
			<p class="bhp-kit-modal__bar-text">
				<span class="bhp-kit-modal__bar-msg"><?php esc_html_e( 'This Free Chapter Activity was sent to your email, see your email to download it now, with your PARENT10 code for 10% off the Collection', 'brave-hearts' ); ?></span>
				<span class="bhp-kit-modal__bar-email"><?php echo esc_html( $email ); ?></span>
			</p>
			<button type="button" class="bhp-kit-modal__close" data-bhp-kit-close aria-label="<?php esc_attr_e( 'Close and return to the page', 'brave-hearts' ); ?>">
				<span aria-hidden="true">&#215;</span>
			</button>
		</div>

		<?php
		/*
		 * ⭐⭐ 1.19.404 · THE READER. Formerly `__fallback`, and the RENAME IS
		 *     THE POINT: this is not a degraded path any more, it is the panel.
		 *     There is no `__viewer`, no `<iframe>`, and no branch above it.
		 *     ⛔ It is NOT `hidden`, and nothing may hide it.
		 */
		?>
		<div class="bhp-kit-modal__reader" data-bhp-kit-reader>
			<?php if ( $pages ) : ?>
				<div class="bhp-kit-modal__pages">
					<?php foreach ( $pages as $page ) : ?>
						<img
							class="bhp-kit-modal__page"
							src="<?php echo esc_url( $page['src'] ); ?>"
							srcset="<?php echo esc_attr( $page['srcset'] ); ?>"
							sizes="(max-width: 640px) 100vw, 640px"
							alt="<?php echo esc_attr( $page['alt'] ); ?>"
							<?php if ( $page['w'] && $page['h'] ) : ?>
								width="<?php echo esc_attr( (string) $page['w'] ); ?>" height="<?php echo esc_attr( (string) $page['h'] ); ?>"
							<?php endif; ?>
							<?php
							/*
							 * ⛔⛔ NOT `loading="lazy"`, AND THAT IS AN
							 *     OBSERVATION, NOT A PREFERENCE.
							 *
							 * ⭐ MEASURED IN A REAL BROWSER ON STAGING
							 *    1.19.392: with the rows lazy, pages 2 to 11
							 *    stayed `complete === false` and
							 *    `naturalWidth === 0` even after the fallback
							 *    scroller had been driven to its end, and
							 *    even with correct width and height reserving
							 *    every box. Promoting a parked row back to
							 *    `eager` afterwards does NOT restart its
							 *    fetch either, and an `IntersectionObserver`
							 *    rooted at the scroller did not rescue it.
							 *    Replacing the elements with eager ones
							 *    loaded all eleven, 0 broken. THAT is the
							 *    difference that was actually tested.
							 *
							 * ⛔ SO THE ROWS ARE EAGER. The cost is real and
							 *    is accepted with its eyes open: eleven pages
							 *    at 1200 wide is roughly 1.7 MB. It is paid
							 *    ONLY inside this fallback, ONLY on the one
							 *    page view after a real conversion, and ONLY
							 *    by a browser with no PDF viewer. A visitor
							 *    who is shown one page and ten blank boxes
							 *    has not been given the kit at all, which is
							 *    the whole point of the modal.
							 *
							 * ⚠️ IF THIS IS EVER MADE LAZY AGAIN it must be
							 *    re-proved in a browser, by counting
							 *    `naturalWidth > 0` across all eleven after
							 *    scrolling — not by reasoning about
							 *    viewports.
							 */
							?>
							loading="eager"
							decoding="async"
						/>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
			<?php
			/*
			 * ⭐⭐ 1.19.404 · THE ONE LINE UNDER THE PAGES, APPROVED VERBATIM BY
			 *     ANDREW (seal 1366), FIRST PERSON, and it is here because it
			 *     is the only thing a visitor can usefully DO from this panel
			 *     now that the file itself arrives only by email.
			 *
			 * ⭐ Its two claims are both VERIFIED FACTS, not marketing:
			 *      · the FROM address on all three steps of journey 89 is
			 *        `andrew@braveheartspublishing.com` -- read in Mailchimp by
			 *        `connected-operator` on 2026-09-07 (seal 1365), which is
			 *        what unblocked this sentence;
			 *      · **TWO** more emails follow, not three. Journey 89 has
			 *        THREE steps in total (E1, E2, E3) and E1 is the one the
			 *        visitor has already been sent. ⛔ "the next three emails"
			 *        would be wrong by one and is a never-say here.
			 *
			 * ⛔ *"I read the replies myself"* is ANDREW'S OWN SENTENCE about
			 *    himself. It is not an outcome claim, not a testimonial, and it
			 *    is not this desk's to soften, embellish or make plural. §9.1:
			 *    he is the sole operator, so the voice is I, never "we".
			 *
			 * ⛔ SUPERSEDED AND DELETED IN 1.19.404, recorded so the movement
			 *    is visible rather than re-derived: two `__note` paragraphs
			 *    stood here. The first explained that *"this browser will not
			 *    display a PDF inside a page"* -- a sentence that is now false
			 *    on every device, because no browser is being asked to. The
			 *    second was the *"Open the full kit in a new tab"* link, which
			 *    is removed under seal 1357 along with Download and Print.
			 */
			?>
			<p class="bhp-kit-modal__note"><?php esc_html_e( 'Add andrew@braveheartspublishing.com to your contacts so the next two emails reach your inbox. I read the replies myself.', 'brave-hearts' ); ?></p>
		</div>

		<?php
		/*
		 * ⭐ ONE BUTTON. Close, and the round close in the bar -- two ways out
		 *    and no way to take the file. Seal 1357.
		 * ⛔ The row keeps `flex-wrap` and the stacked phone form from 1.19.403
		 *    rather than being simplified to a single centred button, because a
		 *    future approved control would otherwise land in an untested row.
		 */
		?>
		<div class="bhp-kit-modal__actions">
			<button type="button" class="bhp-kit-modal__btn bhp-kit-modal__btn--quiet" data-bhp-kit-close>
				<?php esc_html_e( 'Close', 'brave-hearts' ); ?>
			</button>
		</div>
	</div>
</div>
<style id="bhp-kit-modal-style">
/*
 * ⭐ INLINE, AND THAT IS A DECISION RATHER THAN A SHORTCUT. This block is
 *    printed on the ONE page load that follows a real conversion and on no
 *    other, so a separate stylesheet would be a file every visitor downloads
 *    to style something almost none of them will ever see. It also keeps the
 *    modal out of the minification suite's surface, which asserts a built
 *    `.min.css` beside every source file in `assets/css/`.
 */
.bhp-kit-modal{position:fixed;inset:0;z-index:100000;display:flex;align-items:center;justify-content:center;padding:0}
.bhp-kit-modal__sr{position:absolute!important;width:1px;height:1px;margin:-1px;padding:0;overflow:hidden;clip:rect(0 0 0 0);white-space:nowrap;border:0}
.bhp-kit-modal__backdrop{position:absolute;inset:0;background:rgba(10,26,38,.72)}
/*
 * 1.19.403 - THE PANEL IS BOUNDED IN BOTH AXES, AND THE HEIGHT UNIT IS THE
 * POINT. `height:100%` resolved against a `position:fixed` parent is the LARGE
 * viewport on a phone whose address bar is still showing, so the bottom of the
 * dialog -- which is where the buttons live -- can sit under the browser
 * chrome until the visitor scrolls it away. `dvh` is the unit that tracks the
 * chrome as it moves.
 *
 * THE `vh` LINE IS NOT DEAD CODE. It is declared FIRST on purpose: a browser
 * too old to parse `dvh` drops that declaration and keeps the `vh` one, which
 * is the behaviour this pair exists for. Do not "tidy" it away, and do not
 * reorder the pair.
 *
 * AT 768 AND UP the shell takes 24px of padding, so the dialog is bounded by
 * `100%` of the padded box as well as by the viewport unit -- `min()` keeps
 * whichever is smaller, which is why the desktop layout that was already right
 * stays right.
 */
/*
 * 1.19.404 - `overscroll-behavior:contain` IS ON THE DIALOG AS WELL AS ON THE
 * READER. On the reader it stops the scroll chain at the end of the eleven
 * pages; on the dialog it stops a touch that begins on the BAR or the BUTTON
 * ROW - neither of which scrolls - from being handed to the document behind.
 * The body class already sets `overflow:hidden`; this is the second net, and
 * it is the one that matters on iOS, where the body lock alone has never been
 * enough on its own.
 */
.bhp-kit-modal__dialog{position:relative;display:flex;flex-direction:column;width:min(980px,100%);max-width:100%;height:100vh;height:100dvh;max-height:100vh;max-height:100dvh;background:#fdfaf4;box-shadow:0 18px 60px rgba(10,26,38,.45);overflow:hidden;overscroll-behavior:contain}
@media (min-width:768px){.bhp-kit-modal{padding:24px}.bhp-kit-modal__dialog{height:min(92vh,100%);height:min(92dvh,100%);max-height:100%;border-radius:14px}}
/*
 * 1.19.403 - THE CLOSE CONTROL IS THE ONE THING IN THIS BAR THAT MAY NEVER BE
 * PUSHED ANYWHERE. It is `flex:0 0 40px` with a matching `min-width`, so it is
 * not merely un-shrinkable by flex -- it also cannot be squeezed by a
 * min-content contribution from its own glyph.
 *
 * THE ADDRESS NO LONGER WRAPS, AND THE BREAK-ANYWHERE DECLARATION IT USED TO
 * CARRY IS DELETED RATHER THAN OVERRIDDEN. A long address used to grow the bar
 * to two and three lines and push the reader down the panel; it now stays on
 * ONE line and ellipsises. An address the visitor just typed is recognisable
 * from its head, which is the half an ellipsis keeps. A suite assertion checks
 * the old declaration is absent from the file, so it cannot creep back.
 *
 * UNDER 480 THE LABEL SITS ABOVE THE ADDRESS rather than beside it. Measured
 * at 320: the row form leaves the address about 86px, roughly "jennifer.mc",
 * because the label eats the line. Stacking gives the address the full width
 * of the bar, and both lines are still single lines. The bar's rendered height
 * is unchanged either way, so nothing below it moves.
 */
/*
 * 1.19.404 - THE BAR CARRIES A SENTENCE NOW, NOT A LABEL, SO THE 1.19.403
 * ONE-LINE-WITH-ELLIPSIS TREATMENT IS DELIBERATELY NOT APPLIED TO IT.
 *
 * `align-items:flex-start` rather than `center`: with a message that wraps to
 * four or five lines at 320, centring would drift the round close control down
 * the bar and away from the top-right corner where a thumb looks for it.
 *
 * THE MESSAGE WRAPS AND MUST WRAP. It carries the coupon code PARENT10, and a
 * clipped or ellipsised coupon is a coupon that does not work. `overflow-wrap:
 * anywhere` is NOT set on it either - that would allow a break INSIDE the code
 * itself; normal word wrapping keeps PARENT10 whole because it is one word.
 *
 * THE ADDRESS still gets exactly 1.19.403's treatment - one line, ellipsis -
 * because an address is recognisable from its head and a coupon is not. That
 * asymmetry is the whole reason these are two elements and not one.
 */
.bhp-kit-modal__bar{display:flex;align-items:flex-start;gap:10px;padding:10px 12px;background:#0a2a43;color:#fff;flex:0 0 auto}
.bhp-kit-modal__bar-text{margin:0;font-size:.8rem;line-height:1.35;flex:1 1 auto;min-width:0;display:flex;flex-direction:column;gap:2px}
.bhp-kit-modal__bar-msg{min-width:0}
.bhp-kit-modal__bar-email{display:block;min-width:0;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
@media (min-width:480px){.bhp-kit-modal__bar-text{font-size:.875rem}}
.bhp-kit-modal__close{flex:0 0 40px;width:40px;min-width:40px;height:40px;border:0;border-radius:50%;background:rgba(255,255,255,.14);color:#fff;font-size:1.6rem;line-height:1;cursor:pointer}
.bhp-kit-modal__close:hover{background:rgba(255,255,255,.26)}
.bhp-kit-modal__close:focus-visible,.bhp-kit-modal__btn:focus-visible{outline:3px solid #ffb703;outline-offset:2px}
/*
 * THE READER FILLS WHATEVER THE BAR AND THE BUTTON ROW LEAVE. Both of those
 * are `flex:0 0 auto`, so they take their natural height first; the reader is
 * the only `flex:1 1 auto` child and `min-height:0` lets it actually shrink
 * instead of forcing the column past the panel. That is 1.19.403's sizing,
 * unchanged - it now applies to the images instead of to an iframe.
 *
 * 1.19.404 - `.bhp-kit-modal__viewer` and `.bhp-kit-modal__frame` ARE DELETED
 * with the iframe they styled. A test asserts the frame rule is absent, so a
 * copy-paste cannot quietly bring the PDF path back through the stylesheet.
 */
.bhp-kit-modal__reader{flex:1 1 auto;min-height:0;overflow-y:auto;overscroll-behavior:contain;-webkit-overflow-scrolling:touch;padding:14px;background:#e9e4da;text-align:center}
.bhp-kit-modal__pages{display:flex;flex-direction:column;gap:12px;align-items:center}
/*
 * ⭐ 1.19.392 · THE HAIRLINE IS LOAD BEARING, NOT DECORATION. The page renders
 *    carry no border of their own: pages 3 to 7 and 9 are cream and page 11
 *    is white, so against this panel a page edge would otherwise not read at
 *    all. `design-creative` asked for it in CSS rather than baked into a
 *    re-render, which is right, because a baked border would print.
 */
/*
 * ⭐ `aspect-ratio` IS THE BELT TO THE ATTRIBUTES' BRACES. The width and
 *    height attributes already reserve the box, and this holds the same
 *    ratio if a page is ever shipped without measurable dimensions, so the
 *    stack never collapses back to zero-height rows and re-breaks lazy
 *    loading. Every kit page is 1200x1553.
 */
.bhp-kit-modal__page{display:block;width:100%;max-width:640px;height:auto;aspect-ratio:1200/1553;background:#fff;border:1px solid rgba(10,26,38,.14);box-shadow:0 2px 10px rgba(10,26,38,.18)}
.bhp-kit-modal__note{margin:14px auto 0;max-width:44ch;font-size:.9rem;line-height:1.5;color:#243b4a}
/*
 * 1.19.404 - `.bhp-kit-modal__link` IS DELETED. There is no link in this
 * panel, and leaving a style for one is an invitation to add one back.
 */
/*
 * 1.19.404 - THERE IS ONE BUTTON IN THIS ROW NOW, AND THE ROW MACHINERY IS
 * KEPT ANYWAY. `flex-wrap:wrap` plus the under-480 stack are 1.19.403's, and
 * they are retained rather than simplified to a single centred button because
 * a row that has only ever been tested with one child is a row that breaks the
 * day a second approved control lands in it. It costs nothing today: one
 * `flex:1 1 auto` child fills the row at every width.
 *
 * THE COST THAT 1.19.403 STATED IS NOW REPAID. Three stacked full-width
 * buttons were about 168px of panel; one is about 64px. That is roughly 104px
 * of reader given back on a phone, which on a 568px-tall handset is a fifth of
 * the screen - and it is reader, which scrolls, rather than chrome, which does
 * not.
 *
 * ⛔ SUPERSEDED, PRESERVED so the movement is visible: this comment previously
 *    read *"UNDER 480 THE THREE BUTTONS STACK, FULL WIDTH ... in the row form
 *    at 320 they measure 135 / 72 / 74 CSS px"*. That measurement was real and
 *    is the reason the stack exists; there are simply no longer three buttons
 *    to measure. Download and Print are gone under seal 1357.
 */
.bhp-kit-modal__actions{display:flex;flex-wrap:wrap;gap:8px;padding:10px 12px;background:#fdfaf4;border-top:1px solid #e2dacd;flex:0 0 auto}
.bhp-kit-modal__btn{flex:1 1 auto;min-width:0;min-height:44px;padding:10px 14px;border:1px solid #0a2a43;border-radius:8px;background:#fff;color:#0a2a43;font-size:.95rem;font-weight:700;line-height:1.2;text-align:center;text-decoration:none;cursor:pointer;display:inline-flex;align-items:center;justify-content:center}
@media (max-width:479px){.bhp-kit-modal__actions{flex-direction:column;flex-wrap:nowrap}.bhp-kit-modal__btn{flex:0 0 auto;width:100%}}
.bhp-kit-modal__btn--primary{background:#0a2a43;color:#fff}
.bhp-kit-modal__btn--quiet{border-color:#b9ad99;color:#4a3f31}
body.bhp-kit-modal-open{overflow:hidden}
@media print{body.bhp-kit-modal-open>*:not(.bhp-kit-modal){display:none!important}.bhp-kit-modal{position:static}.bhp-kit-modal__bar,.bhp-kit-modal__actions,.bhp-kit-modal__backdrop{display:none!important}}
</style>
<script id="bhp-kit-modal-script">
(function () {
	'use strict';

	var modal = document.getElementById('bhp-kit-modal');
	if (!modal) { return; }

	var doc = document;
	var body = doc.body;
	var closer = modal.querySelector('.bhp-kit-modal__close');
	var opener = doc.activeElement;

	/*
	 * ⛔⛔ 1.19.404 · THERE IS NO READER SELECTION LOGIC IN THIS FILE ANY MORE,
	 *     AND ITS ABSENCE IS THE FIX.
	 *
	 * ⭐ DELETED HERE: `frame`, `viewer`, `fallback` and `pdf` locals ·
	 *    `forcedView()` · `usePdfFrame()` · the `navigator.pdfViewerEnabled`
	 *    probe · the `window.bhpKitModalForceView` harness hook · the whole
	 *    hide-one-show-the-other block · the Print handler and its
	 *    `contentWindow.print()` / `window.open()` pair.
	 *
	 * ⚠️ WHY THE PROBE HAD TO GO RATHER THAN BE IMPROVED. It asked the browser
	 *    "will a framed PDF render?", and iOS Safari answers TRUE and then
	 *    paints page one, oversized, unscrollable - the founder's own iPhone,
	 *    2026-09-07 21:19. A probe whose one authority lies to it is not a
	 *    probe. ⛔ Do not re-add it with a user-agent test bolted on: the
	 *    images are now the design on every device, not a fallback for some.
	 *
	 * ⛔ NOTHING IN THIS SCRIPT READS OR HOLDS THE PDF URL. There is no
	 *    `data-bhp-kit-pdf` attribute to read.
	 */

	body.classList.add('bhp-kit-modal-open');

	/* Focusable elements inside the dialog, recomputed on each Tab. The
	   `iframe` selector is kept in the list deliberately: it costs nothing,
	   and if a future approved control ever reintroduces an embedded document
	   the trap keeps working rather than silently leaking focus past it. */
	function focusables() {
		var all = modal.querySelectorAll('a[href], button:not([disabled]), iframe, [tabindex]:not([tabindex="-1"])');
		var out = [];
		for (var i = 0; i < all.length; i++) {
			var el = all[i];
			if (el.offsetParent !== null || el === doc.activeElement) { out.push(el); }
		}
		return out;
	}

	function close() {
		modal.parentNode && modal.parentNode.removeChild(modal);
		body.classList.remove('bhp-kit-modal-open');
		doc.removeEventListener('keydown', onKey, true);

		/* The token is already spent, so a refresh cannot reopen this. Tidying
		   the address bar is courtesy, and it is wrapped because a browser
		   with history writes disabled must not break the close button. */
		try {
			if (window.history && window.history.replaceState) {
				var url = new URL(window.location.href);
				url.searchParams.delete('bhp_kit');
				window.history.replaceState({}, '', url.toString());
			}
		} catch (e) {}

		try {
			if (opener && doc.contains(opener) && typeof opener.focus === 'function') {
				opener.focus();
			} else {
				var main = doc.getElementById('primary') || doc.querySelector('main') || body;
				if (main && typeof main.focus === 'function') {
					if (!main.hasAttribute('tabindex')) { main.setAttribute('tabindex', '-1'); }
					main.focus();
				}
			}
		} catch (e2) {}
	}

	function onKey(event) {
		if (event.key === 'Escape' || event.key === 'Esc') {
			event.preventDefault();
			close();
			return;
		}
		if (event.key !== 'Tab') { return; }

		var list = focusables();
		if (!list.length) { return; }
		var first = list[0];
		var last = list[list.length - 1];

		if (event.shiftKey && doc.activeElement === first) {
			event.preventDefault();
			last.focus();
		} else if (!event.shiftKey && doc.activeElement === last) {
			event.preventDefault();
			first.focus();
		} else if (!modal.contains(doc.activeElement)) {
			event.preventDefault();
			first.focus();
		}
	}

	doc.addEventListener('keydown', onKey, true);

	var closers = modal.querySelectorAll('[data-bhp-kit-close]');
	for (var c = 0; c < closers.length; c++) {
		closers[c].addEventListener('click', function (event) {
			event.preventDefault();
			close();
		});
	}

	if (closer && typeof closer.focus === 'function') { closer.focus(); }
})();
</script>
	<?php
}
add_action( 'wp_footer', 'bhp_kit_modal_render', 20 );
