<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * THE CAPTURE-COPY CONSISTENCY SUITE — theme 1.19.297, 2026-08-27,
 * `CYCLE167-LD-CAPTURE-COPY-APPLY`.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Run on STAGING (never production) via:
 *   wp eval-file wp-content/themes/brave-hearts-theme-deploy-explorer-expedition-guides/tests/test-cycle167-capture-copy.php --user=1
 *
 * ---------------------------------------------------------------------------
 * ⭐ WHAT THIS SUITE IS FOR, AND WHY IT IS A DIFFERENT SHAPE FROM ITS SIBLINGS
 * ---------------------------------------------------------------------------
 *
 * ⭐ THE FINDING IT ENFORCES (Merry, `CYCLE167-MKT-MAGNET-TEARDOWN`): TWELVE OF
 *    TWELVE email-capture surfaces described the SAME offer with a DIFFERENT
 *    name. "Try a chapter tonight" · "Start with one free chapter." · "Before
 *    you go, take the free kit." · "Send My Free Adventure Kit" · "Send me the
 *    Kit" · "Send me the free kit" · "Send me the free chapter & activity". A
 *    parent who met two of them met two offers. The founder agreed in terms:
 *    *"we need to be consistent on the email capture across the entire
 *    website"*, and then picked the one name himself (carrier item 290).
 *
 * ⛔ EVERY OTHER SUITE IN THIS REPO ASSERTS ONE SURFACE AT A TIME, AND THAT IS
 *    EXACTLY WHY THE DIVERGENCE SURVIVED TWELVE RELEASES. `test-popup-ab.php`
 *    pinned the popup's headline character for character. `test-exit-intent-
 *    trigger.php` pinned exit-intent's. `test-wave1-capture.php` pinned the
 *    footer's. All three passed, for years, while describing three different
 *    offers — because NO TEST EVER COMPARED THEM TO EACH OTHER. ⭐ THIS SUITE
 *    IS THE MISSING CROSS-SURFACE ASSERTION, and it is the durable deliverable
 *    of tonight's pass: the copy change is one release, but a per-surface guard
 *    set that cannot see a contradiction between surfaces will simply re-grow
 *    the same defect the next time somebody edits one file.
 *
 * ---------------------------------------------------------------------------
 * ⛔ WHAT A PASS HERE DOES **NOT** PROVE — read before over-reading one.
 * ---------------------------------------------------------------------------
 * This is PHP and source level. It cannot see layout, wrapping, a tap target,
 * console cleanliness, or where a panel actually sits on a rendered page. Those
 * claims carry browser evidence at a stated `window.innerWidth` in the handoff
 * and are NOT inferred from a PASS below.
 *
 * ⛔ NOR DOES IT PROVE THE PROMISE IS KEPT. "I'll send you the chapter now" is a
 *    claim about a Mailchimp journey, and no PHP assertion can verify one. That
 *    is founder-observed evidence (carrier items 292/293/294: the Active
 *    "Parent - Acquisition Funnel", step 1 sending immediately on the tag,
 *    which he read in his own journey builder and unpaused himself). This suite
 *    asserts only that the SITE says one consistent thing.
 *
 * ⛔ IT WRITES NOTHING. No option, no post, no product, no setting, no
 *    subscriber, and it registers no permanent filter.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

/*
 * ⛔ COUNTERS IN $GLOBALS, for the reason `test-cycle167-capture-fix.php`
 *    records at length: `wp eval-file` runs this file in FUNCTION scope, so a
 *    file-top `$pass = 0;` is a LOCAL and `global $pass;` inside the helper
 *    binds a different, unset global. The helper would increment one variable
 *    and the summary would read another, making the suite structurally
 *    incapable of reporting a failure. ⛔ A SUITE THAT CANNOT FAIL IS A
 *    FABRICATED VERIFICATION.
 */
$GLOBALS['bhp_ccc_pass'] = 0;
$GLOBALS['bhp_ccc_fail'] = 0;

function bhp_ccc_ok( $label, $cond, $detail = '' ) {
	if ( $cond ) {
		$GLOBALS['bhp_ccc_pass']++;
		echo "PASS  {$label}\n";
	} else {
		$GLOBALS['bhp_ccc_fail']++;
		echo "FAIL  {$label}" . ( $detail ? '  -- ' . substr( (string) $detail, 0, 400 ) : '' ) . "\n";
	}
}

function bhp_ccc_head( $title ) {
	echo "\n=== {$title} ===\n";
}

function bhp_ccc_theme_file( $rel ) {
	$path = get_template_directory() . '/' . ltrim( $rel, '/' );
	return file_exists( $path ) ? (string) file_get_contents( $path ) : '';
}

/**
 * ⭐ A FILE'S **CODE**, WITH EVERY COMMENT REMOVED — and on this suite it is
 *    load-bearing rather than tidy.
 *
 * ⛔⛔ THIS SUITE SCANS FOR RETIRED COPY STRINGS, AND EVERY FILE IT SCANS NOW
 *     CARRIES A DOCBLOCK THAT **QUOTES THE RETIRED STRING** in order to explain
 *     what changed and why. A raw `strpos()` would match the EXPLANATION and
 *     report a defect that does not exist — the identical trap the 296 suite
 *     fell into on `page-market-capture.php`'s own promises. Worse, an author
 *     who hit that false positive would be tempted to delete the historical
 *     quotation to make a test go green, which is how a codebase loses the
 *     record of its own decisions.
 *
 * ⛔ `token_get_all()` RATHER THAN A REGEX: a regex cannot tell a comment from
 *    the same characters inside a string literal. The tokenizer can, because it
 *    is the lexer PHP itself uses.
 */
function bhp_ccc_code_only( $rel ) {
	$src = bhp_ccc_theme_file( $rel );
	if ( '' === $src ) {
		return '';
	}
	$out = '';
	foreach ( token_get_all( $src ) as $t ) {
		if ( is_array( $t ) ) {
			if ( T_COMMENT === $t[0] || T_DOC_COMMENT === $t[0] ) {
				continue;
			}
			$out .= $t[1];
		} else {
			$out .= $t;
		}
	}
	return $out;
}

/* ═══════════════════════════════════════════════════════════════════════════
 * THE ONE OFFER, STATED ONCE. Every assertion below compares against these
 * three constants rather than against a literal typed at the assertion site —
 * so a future edit that changes the offer must change it HERE, in one place,
 * which is the whole point of the suite.
 * ═══════════════════════════════════════════════════════════════════════════ */
$offer_headline = 'FREE Chapter for Reluctant Readers';
$offer_button   = 'Send me the chapter';
$offer_support  = "I'll send you the chapter now, just add your email.";

/* The full support + bridge sentence carried by every surface that says what
 * arrives. ⛔ THE BRIDGE HALF IS THE HONESTY CONDITION (founder item 290(b)):
 * the offer is the CHAPTER, the artefact is the KIT, and a surface that names
 * one without the other is either a broken promise or a bait. */
$offer_bridge = $offer_support
	. ' It arrives inside my free Reluctant Reader Adventure Kit, along with a printable activity'
	. ' and tips for reading it with a 6 to 9 year old.';

/* ⛔ THE RETIRED NAMES. Each was live on some surface before 1.19.297. They are
 *    listed so a partial revert leaves a FAILING TEST rather than a site that
 *    quietly offers two things again. */
$retired = array(
	'FREE 20 Minute Reluctant Reader Kit',
	'20 Minute Reluctant Reader Kit',
	'Try a chapter tonight',
	'Start with one free chapter',
	'Before you go, take the free kit.',
	'Send My Free Adventure Kit',
	'Send me the Kit',
	'Send me the free kit',
	'Send me the free chapter & activity',
	'Join the Adventure Kit',
);

/* ⭐ THE PARENT CAPTURE SURFACES. ⛔ THIS LIST IS THE SUITE'S OWN BLIND SPOT AND
 *    IT IS NAMED RATHER THAN HIDDEN: a thirteenth parent capture surface added
 *    later is not covered until somebody adds it here. §5 mitigates that with a
 *    repo-wide sweep for the retired strings, which needs no list. */
$parent_surfaces = array(
	'template-parts/acquisition/parent-ab-popup.php',
	'template-parts/acquisition/post-end-capture.php',
	'template-parts/acquisition/post-mid-capture.php',
	'template-parts/acquisition/footer-capture.php',
	'template-parts/acquisition/exit-intent-popup.php',
	'page-market-capture.php',
	/*
	 * ⭐⭐ 1.19.301 (2026-08-27, `CYCLE167-LD-FREE-RESOURCES-HUB`) — THE
	 *     THIRTEENTH SURFACE, ADDED ON THE DAY IT WAS BUILT.
	 *
	 * ⭐ THIS IS THE BLIND SPOT THE DOCBLOCK ABOVE NAMED, BEING CLOSED ON
	 *    SCHEDULE RATHER THAN DISCOVERED LATER. That comment says in its own
	 *    words: *"a thirteenth parent capture surface added later is not covered
	 *    until somebody adds it here."* `/free-resources/` is that thirteenth
	 *    surface. It carries the founder's offer (carrier item 290) and it was
	 *    built the same night, so it joins the list in the same release rather
	 *    than becoming the next twelve-names-for-one-offer defect.
	 *
	 * ⛔ IT IS A PAGE TEMPLATE, so §8 cannot render it headless — it needs a
	 *    real main query. Its rendered evidence is a real browser at a stated
	 *    `window.innerWidth`, in the handoff, and §1 to §7 below cover its
	 *    source exactly as they cover `page-market-capture.php`.
	 */
	'page-free-resources.php',
	/*
	 * ⭐⭐⭐ 1.19.308 (2026-08-27, `CYCLE167-LD-KIT-PAGE-REFRESH`) — THE
	 *      FOURTEENTH SURFACE, AND THE ONE THIS SUITE WAS SHIPPED WITHOUT.
	 *
	 * ⛔⛔ /reluctant-reader-adventure-kit/ WAS THE LAST PARENT CAPTURE SURFACE
	 *     STILL DESCRIBING THE PRE-FREE-CHAPTER OFFER, AND IT WAS OMITTED ON
	 *     PURPOSE, NOT BY OVERSIGHT. `CYCLE167-LD-CAPTURE-COPY-APPLY` left it
	 *     alone because renaming the offer raised a question no engineer may
	 *     answer: the URL names the KIT and the offer is now the CHAPTER, so
	 *     changing the copy without changing the slug looked like a mismatch
	 *     and changing the slug would have thrown away the attribution on the
	 *     one page his August signups actually converted on. That was recorded
	 *     as G-116 and escalated rather than decided.
	 *
	 * ⭐ THE FOUNDER SETTLED IT AT CARRIER ITEM 330 by choosing this page as
	 *    the destination for the returning $0.25-CPC Meta ad. THE URL STAYS.
	 *    Only the copy moves — which is what makes this row safe to add.
	 *
	 * ⛔ IT IS A PAGE TEMPLATE, so §8 cannot render it headless — it needs a
	 *    real main query. Its rendered evidence is a real browser at a stated
	 *    `window.innerWidth`, in the handoff, exactly as `page-market-capture.
	 *    php` and `page-free-resources.php` are handled. §1 to §7 below cover
	 *    its source.
	 *
	 * ⚠ THIS PAGE CARRIES THREE FORMS, NOT ONE — the hero capture, the inline
	 *   #free panel and the CTA modal — so §2b/§2c below assert THREE
	 *   `submit_label` literals for this row where every other row asserts one.
	 *   That is the point rather than a quirk: three forms on one page is
	 *   exactly the shape in which a single edited label goes unnoticed.
	 */
	'page-reluctant-reader-adventure-kit.php',
);

/* ═══════════════════════════════════════════════════════════════════════════
 * §0 · PRECONDITIONS — refuse to run rather than produce a false PASS.
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_ccc_head( '§0 PRECONDITIONS' );

bhp_ccc_ok(
	'§0.1 theme version is 1.19.297 or later',
	version_compare( (string) wp_get_theme()->get( 'Version' ), '1.19.297', '>=' ),
	'got ' . wp_get_theme()->get( 'Version' )
);

foreach ( $parent_surfaces as $rel ) {
	bhp_ccc_ok( "§0.2 {$rel} exists and is readable", '' !== bhp_ccc_theme_file( $rel ) );
}

bhp_ccc_ok(
	'§0.3 the comment stripper actually strips (it is what keeps §5 honest)',
	false === strpos( bhp_ccc_code_only( 'template-parts/acquisition/exit-intent-popup.php' ), 'locked prose is never silently' )
);

/* ═══════════════════════════════════════════════════════════════════════════
 * §1 · ONE HEADLINE. Every parent capture surface leads with the same offer.
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_ccc_head( '§1 ONE HEADLINE ACROSS EVERY PARENT SURFACE' );

foreach ( $parent_surfaces as $rel ) {
	bhp_ccc_ok(
		"§1 {$rel} leads with the founder's headline",
		false !== strpos( bhp_ccc_code_only( $rel ), $offer_headline )
	);
}

/* ═══════════════════════════════════════════════════════════════════════════
 * §2 · ONE BUTTON, AND **NO "FREE" ON IT**.
 *
 * ⭐ The teardown pattern the founder took: FREE belongs in the headline, where
 *    it is the offer. On a button it competes with the action the button
 *    performs. ⛔ §2b asserts the absence, which is the half most likely to
 *    regress, because "free" reads like an improvement to anyone editing one
 *    file in isolation.
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_ccc_head( '§2 ONE BUTTON STRING, AND FREE IS NOT ON IT' );

foreach ( $parent_surfaces as $rel ) {
	$code = bhp_ccc_code_only( $rel );
	bhp_ccc_ok(
		"§2a {$rel} uses the sitewide send-imperative",
		false !== strpos( $code, $offer_button )
	);

	/* Every `submit_label` literal in the file, whatever quote style it uses. */
	$labels = array();
	if ( preg_match_all( "/'submit_label'\s*=>\s*__\(\s*(['\"])(.*?)\\1/s", $code, $m ) ) {
		$labels = $m[2];
	}
	bhp_ccc_ok(
		"§2b {$rel} has at least one submit_label to check",
		count( $labels ) > 0
	);
	foreach ( $labels as $label ) {
		bhp_ccc_ok(
			"§2b ⛔ {$rel} submit_label carries no form of \"free\": \"{$label}\"",
			false === stripos( $label, 'free' )
		);
		bhp_ccc_ok(
			"§2c {$rel} submit_label IS the sitewide string: \"{$label}\"",
			$offer_button === $label
		);
	}
}

/* ═══════════════════════════════════════════════════════════════════════════
 * §3 · THE SUPPORT LINE, AND THE chapter -> Kit BRIDGE.
 *
 * ⛔ THIS IS THE HONESTY SECTION AND IT IS THE MOST IMPORTANT ONE IN THE FILE.
 *    The offer reframed from Kit to Chapter; the file that actually arrives is
 *    still the Kit. That is over-delivery and it is honest ONLY IF every
 *    surface that tells the visitor what arrives names BOTH and the
 *    relationship between them. Founder item 290 condition (b), verbatim:
 *    *"delivery-side copy must bridge chapter -> kit so what arrives visibly
 *    matches what was promised."*
 *
 * ⚠ THE POPUP IS DELIBERATELY EXEMPT FROM THE BRIDGE, AND THE EXEMPTION IS
 *   STATED RATHER THAN QUIETLY ALLOWED. Its two text lines are the founder's
 *   own, and he chose not to add a third; its bridge is the KIT COVER IMAGE it
 *   renders (whose accessible name is the Kit) plus the thank-you page. §3c
 *   asserts the popup carries the support line, and §4 asserts the thank-you
 *   page carries the bridge, so the popup's path is covered end to end without
 *   padding copy he did not ask for.
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_ccc_head( '§3 THE SUPPORT LINE AND THE CHAPTER -> KIT BRIDGE' );

$bridge_surfaces = array_values( array_diff(
	$parent_surfaces,
	array( 'template-parts/acquisition/parent-ab-popup.php' )
) );

foreach ( $bridge_surfaces as $rel ) {
	$code = bhp_ccc_code_only( $rel );
	bhp_ccc_ok(
		"§3a {$rel} carries the full support + bridge sentence, character for character",
		false !== strpos( $code, $offer_bridge )
	);
	bhp_ccc_ok(
		"§3b ⛔ {$rel} names the Kit wherever it says what arrives",
		false !== strpos( $code, 'Reluctant Reader Adventure Kit' )
	);
}

$popup_code = bhp_ccc_code_only( 'template-parts/acquisition/parent-ab-popup.php' );
bhp_ccc_ok(
	'§3c the popup carries the founder\'s support line as the subhead default',
	false !== strpos( $popup_code, $offer_support )
);
bhp_ccc_ok(
	'§3d ⭐ the popup\'s bridge is its cover image — it still renders the Kit cover',
	false !== strpos( $popup_code, "bhp_get_lead_magnet_cover('reluctant_reader_adventure_kit')" )
);

/* §3e · VOICE AND CLAIM RAILS on the one sentence every surface now shares.
 * ⛔ Checked against the CONSTANT rather than per-file, because it is one
 *    sentence: if it is wrong it is wrong in six places at once. */
bhp_ccc_ok( '§3e ⛔ the shared sentence contains no em dash', false === strpos( $offer_bridge, "\xE2\x80\x94" ) );
bhp_ccc_ok( '§3e ⛔ it says "my"/"I", never "we" (VOICE §9.1)', 1 === preg_match( '/\bmy\b/', $offer_bridge ) && 0 === preg_match( '/\bwe\b/i', $offer_bridge ) );
bhp_ccc_ok( '§3e ⛔ the age range is 6 to 9, never 5 to 9', false !== strpos( $offer_bridge, '6 to 9' ) && false === strpos( $offer_bridge, '5 to 9' ) );
bhp_ccc_ok(
	'§3e ⛔ no rating, review, award, urgency or scarcity claim',
	0 === preg_match( '/\b(rating|reviews?|stars?|awards?|best-?sell\w*|hurry|limited time|only \d+ left)\b/i', $offer_bridge )
);
/* ⛔ NO OUTCOME CLAIM: the sentence says what the Kit CONTAINS, never what it
 *   will do to a child. Asserted as the absence of the outcome verbs this
 *   corpus has had to strip before. */
bhp_ccc_ok(
	'§3e ⛔ no outcome claim about the child',
	0 === preg_match( '/\b(will (?:love|read|improve)|turns? your|makes? your child|guaranteed|proven)\b/i', $offer_bridge )
);
/* ⛔ NO INVENTED CONTENTS. The Kit really holds a sample chapter, a printable
 *   activity and tips to the parent (all seven pages read from the live PDF by
 *   the 296 lane). Anything else named here would be a fabricated claim about
 *   our own product. */
bhp_ccc_ok(
	'§3e ⛔ the contents named are only the ones the real PDF contains',
	0 === preg_match( '/\b(workbook|worksheets?|audiobook|poster|sticker|lesson plans?|flashcards?)\b/i', $offer_bridge )
);

/* ═══════════════════════════════════════════════════════════════════════════
 * §4 · THE DELIVERY SIDE. Where the promise lands, it must still match.
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_ccc_head( '§4 THE THANK-YOU PAGE BRIDGES BACK' );

$ty = bhp_ccc_code_only( 'page-adventure-kit-thank-you.php' );
bhp_ccc_ok( '§4.1 the thank-you page exists', '' !== $ty );
bhp_ccc_ok(
	'§4.2 ⭐ it names the CHAPTER the visitor was promised',
	false !== stripos( $ty, 'Your chapter is on the way' )
);
bhp_ccc_ok(
	'§4.3 ⭐ it names the KIT that actually arrives (the H1 is unchanged)',
	false !== strpos( $ty, 'Your Reluctant Reader Adventure Kit Is on Its Way' )
);
bhp_ccc_ok(
	'§4.4 ⭐ and it states the RELATIONSHIP, which is what makes it a bridge',
	false !== strpos( $ty, 'Your chapter is inside the Kit' )
);
bhp_ccc_ok(
	'§4.5 the operational guidance survives the rewrite (this page reduces support email)',
	false !== strpos( $ty, 'allow up to 15 minutes' ) && false !== stripos( $ty, 'spam folder' )
);

/* ═══════════════════════════════════════════════════════════════════════════
 * §5 · NO RETIRED OFFER NAME SURVIVES ANYWHERE IN THE THEME'S CODE.
 *
 * ⭐ THIS IS THE SECTION THAT NEEDS NO SURFACE LIST, which is why it exists
 *    alongside §1-§3: it would catch a thirteenth capture surface that the list
 *    above does not know about.
 * ⛔ COMMENTS ARE STRIPPED FIRST. Six of the files below deliberately QUOTE the
 *    retired strings in their docblocks to record what changed. Matching those
 *    would report a defect that does not exist and would pressure a future
 *    author into deleting the historical record to go green.
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_ccc_head( '§5 NO RETIRED OFFER NAME SURVIVES IN CODE' );

$scan = array_merge(
	$parent_surfaces,
	array(
		'page-adventure-kit-thank-you.php',
		'template-parts/quiz/audience-quiz.php',
	)
);

foreach ( $scan as $rel ) {
	$code = bhp_ccc_code_only( $rel );
	foreach ( $retired as $old ) {
		bhp_ccc_ok(
			"§5 {$rel} no longer offers \"{$old}\"",
			false === strpos( $code, $old )
		);
	}
}

/* ═══════════════════════════════════════════════════════════════════════════
 * §6 · THE QUIZ — PARENT ROUTE MOVED, EDUCATOR ROUTE **DID NOT**.
 *
 * ⛔⛔ THIS IS THE FUNNEL-ISOLATION GUARD AND IT IS THE ONE ASSERTION IN THIS
 *     FILE THAT PROTECTS SOMETHING OTHER THAN CONSISTENCY. `.claude/rules/
 *     funnels.md` keeps the parent and teacher funnels independent. A sweep
 *     whose instruction is "make every surface say the same thing" is EXACTLY
 *     the kind of instruction that walks a parent offer onto a teacher surface
 *     by momentum. §6b and §6c make that failure loud.
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_ccc_head( '§6 THE QUIZ — PARENT MOVED, EDUCATOR UNTOUCHED' );

$quiz = bhp_ccc_code_only( 'template-parts/quiz/audience-quiz.php' );

bhp_ccc_ok(
	'§6a the parent route offers the chapter',
	false !== strpos( $quiz, $offer_button ) && false !== stripos( $quiz, 'FREE chapter, sent by email inside my Reluctant Reader Adventure Kit' )
);
bhp_ccc_ok(
	'§6b ⛔ the EDUCATOR route still offers the Adventure Learning Toolkit, unchanged',
	false !== strpos( $quiz, 'Send My Free Toolkit' )
		&& false !== strpos( $quiz, 'Your free Adventure Learning Toolkit, sent by email. No cost, no strings.' )
);
bhp_ccc_ok(
	'§6c ⛔⛔ the parent chapter offer has NOT walked onto a teacher surface',
	false === strpos( bhp_ccc_code_only( 'page-teachers.php' ), $offer_headline )
		&& false === strpos( bhp_ccc_code_only( 'page-teachers.php' ), $offer_button )
		&& false === strpos( bhp_ccc_code_only( 'template-parts/acquisition/teacher-resource-signup.php' ), $offer_headline )
);

/* ═══════════════════════════════════════════════════════════════════════════
 * §7 · ⛔⛔ THE PLUMBING DID NOT MOVE. A COPY CHANGE MUST NOT MINT A TAG.
 *
 * ⭐⭐ THE SINGLE MOST LOAD-BEARING SECTION IN THIS SUITE, AND THE ONE MOST
 *     LIKELY TO BE BROKEN BY SOMEBODY DOING THE RIGHT THING FOR THE WRONG
 *     REASON. Every `context` string below is the JOIN KEY for a tag that
 *     already exists in Andrew's LIVE Mailchimp audience ("Source: Parent Popup
 *     A/B", "Source: Blog Post", "Source: Market Event", and so on). Renaming
 *     one to match the new offer name — which looks like tidiness — would MINT
 *     A NEW TAG in a live audience and SPLIT THAT SURFACE'S SEGMENT IN TWO,
 *     silently, with no error and no failing test anywhere else in this repo.
 *
 * ⛔ THAT IS A MAILCHIMP DECISION AND IT IS ANDREW'S, NOT AN ENGINEERING ONE.
 *    The offer's NAME IN COPY changed tonight. Its PLUMBING did not.
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_ccc_head( '§7 THE PLUMBING IS UNCHANGED — NO NEW MAILCHIMP TAG IS MINTED' );

$contexts = array(
	'template-parts/acquisition/parent-ab-popup.php'   => "'parent_popup_ab'",
	'template-parts/acquisition/footer-capture.php'    => "'footer_capture'",
	'template-parts/acquisition/exit-intent-popup.php' => "'parent_popup_exit'",
	'page-market-capture.php'                          => "'market_capture'",
	/*
	 * ⭐ 1.19.301 — the hub's own analytics identity. ⛔ IT IS NOT A TAG, AND
	 *    THAT DISTINCTION IS THE WHOLE REASON A NEW CONTEXT WAS SAFE TO ADD
	 *    HERE WHEN §7's DOCBLOCK WARNS AGAINST RENAMING ONE. No
	 *    `bhp_mailchimp_signup_tags` callback matches `free_resources_hub`, so
	 *    it falls through to the BASE map and resolves to the EXISTING trio
	 *    ["Reluctant Reader Adventure Kit", "Audience: Parent/Grandparent",
	 *    "Source: Parent Landing Page"]. Nothing new is minted in Andrew's live
	 *    audience and no segment splits.
	 *    ⭐ PROVEN BY EXECUTING THE FILTER CHAIN in
	 *    `test-cycle167-free-resources.php` §4b, not by reading it.
	 */
	'page-free-resources.php'                          => "'free_resources_hub'",
	/*
	 * ⭐ 1.19.308 — the kit landing hero's own analytics identity. ⛔ IT IS NOT
	 *    A TAG. For `reluctant_reader_adventure_kit` the tag callback branches
	 *    on `$context === 'parent_popup'` and on NOTHING ELSE, so every other
	 *    context — this one included — resolves to the SAME existing trio
	 *    ["Reluctant Reader Adventure Kit", "Audience: Parent/Grandparent",
	 *    "Source: Parent Landing Page"]. That is the tag his August paid
	 *    signups already carry. ⭐ PROVEN BY EXECUTING THE FILTER CHAIN in
	 *    `test-cycle167-kit-page.php` §3, not by reading it.
	 */
	'page-reluctant-reader-adventure-kit.php'          => "'parent_landing_hero'",
);
foreach ( $contexts as $rel => $ctx ) {
	bhp_ccc_ok(
		"§7a {$rel} keeps its context {$ctx}",
		false !== strpos( bhp_ccc_code_only( $rel ), $ctx )
	);
}

/* The two blog captures share ONE context by function, deliberately, so that
 * mid and end do not split the blog's segment between them. */
foreach ( array(
	'template-parts/acquisition/post-end-capture.php',
	'template-parts/acquisition/post-mid-capture.php',
) as $rel ) {
	bhp_ccc_ok(
		"§7b {$rel} still resolves its context through bhp_blog_capture_context()",
		false !== strpos( bhp_ccc_code_only( $rel ), 'bhp_blog_capture_context()' )
	);
}

/* Every parent surface still routes to the same lead magnet, thank-you path and
 * audience. ⛔ Renaming any of these would change WHICH FILE IS SENT, not just
 * what the page calls it. */
foreach ( $parent_surfaces as $rel ) {
	$code = bhp_ccc_code_only( $rel );
	bhp_ccc_ok(
		"§7c {$rel} still requests the reluctant_reader_adventure_kit lead magnet",
		false !== strpos( $code, 'reluctant_reader_adventure_kit' )
	);
	bhp_ccc_ok(
		"§7d {$rel} still declares the parents_families audience",
		false !== strpos( $code, 'parents_families' )
	);
}

bhp_ccc_ok(
	'§7e the popup keeps its storage prefix and thank-you path (funnel identity)',
	false !== strpos( $popup_code, "'bhp_parent_popup'" )
		&& false !== strpos( $popup_code, "'adventure-kit-thank-you'" )
);

/*
 * ⭐⭐ §7f (1.19.308) — THE ONE ASSERTION THAT PROTECTS THE FOUNDER'S PAID
 *     ATTRIBUTION, AND IT IS AN ABSENCE.
 *
 * ⛔ The landing page must NEVER declare the `parent_popup` context. It is the
 *    single string that flips this magnet's third tag from "Source: Parent
 *    Landing Page" to "Source: Parent Popup" — and the landing tag is the one
 *    his August signups carry and the one the returning Meta ad will be judged
 *    on. It would be an easy thing to paste in while copying markup from the
 *    popup, which is exactly what this release did, and nothing else in the
 *    repository would notice.
 */
bhp_ccc_ok(
	'§7f ⛔⛔ the kit landing page never declares the parent_popup context',
	false === strpos(
		bhp_ccc_code_only( 'page-reluctant-reader-adventure-kit.php' ),
		"'parent_popup'"
	)
);
bhp_ccc_ok(
	'§7g the kit landing page keeps the adventure_kit_thank_you redirect on every form',
	3 === substr_count(
		bhp_ccc_code_only( 'page-reluctant-reader-adventure-kit.php' ),
		"'adventure_kit_thank_you'"
	)
);

/* ═══════════════════════════════════════════════════════════════════════════
 * §8 · THE RENDERED SURFACES, not just their source.
 *
 * ⚠ §1-§7 are SOURCE-LEVEL and are honest about it. This section actually
 *   RENDERS the three template parts that can be rendered without a main query
 *   and asserts the strings reach the output. ⛔ `page-market-capture.php` and
 *   `page-adventure-kit-thank-you.php` are page TEMPLATES needing a real query,
 *   so they are NOT rendered here and carry browser evidence in the handoff
 *   instead. A source assertion is honest about being one; it is not a
 *   substitute for the browser, and neither is this section.
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_ccc_head( '§8 RENDERED OUTPUT (the parts that can render headless)' );

foreach ( array(
	'template-parts/acquisition/parent-ab-popup',
	'template-parts/acquisition/post-end-capture',
	'template-parts/acquisition/post-mid-capture',
	'template-parts/acquisition/footer-capture',
	'template-parts/acquisition/exit-intent-popup',
) as $slug ) {
	ob_start();
	get_template_part( $slug );
	$html = (string) ob_get_clean();
	$text = wp_strip_all_tags( $html );

	bhp_ccc_ok( "§8 {$slug} renders", '' !== trim( $html ) );
	bhp_ccc_ok(
		"§8 {$slug} RENDERS the offer headline",
		false !== strpos( $text, $offer_headline )
	);
	bhp_ccc_ok(
		"§8 {$slug} RENDERS the send-imperative button",
		false !== strpos( $text, $offer_button )
	);
	foreach ( $retired as $old ) {
		bhp_ccc_ok(
			"§8 {$slug} renders no retired name: \"{$old}\"",
			false === strpos( $text, $old ) && false === strpos( $text, esc_html( $old ) )
		);
	}
}

/* ═══════════════════════════════════════════════════════════════════════════
 * §9 · THIS SUITE MUTATED NOTHING.
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_ccc_head( '§9 NO SIDE EFFECTS' );

bhp_ccc_ok(
	'§9.1 no subhead filter was left registered by this run',
	! has_filter( 'bhp_parent_popup_subhead' )
);
bhp_ccc_ok(
	'§9.2 ⛔ the theme version on disk is unchanged by running tests',
	version_compare( (string) wp_get_theme()->get( 'Version' ), '1.19.297', '>=' )
);

echo "\n============================================================\n";
printf(
	"CAPTURE-COPY CONSISTENCY: %d passed, %d failed\n",
	(int) $GLOBALS['bhp_ccc_pass'],
	(int) $GLOBALS['bhp_ccc_fail']
);
echo "============================================================\n";
