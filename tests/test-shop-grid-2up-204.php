<?php
/**
 * CARRIER ITEM 204 — THE MOBILE SHOP GRID IS 2-UP, AND THE PROOF COMES OFF IT.
 * Theme 1.19.283 / plugin 1.8.66 (unchanged). `CYCLE165-LD-SHOP-2UP`.
 * ============================================================================
 *
 * Run:
 *   wp eval-file wp-content/themes/brave-hearts-theme-deploy-explorer-expedition-guides/tests/test-shop-grid-2up-204.php \
 *      --user=1 --url=https://staging2.braveheartspublishing.com
 *
 * ⭐ ANDREW SIGNORE, carrier item 204, 2026-08-21 (⚠️ RELAYED through
 *    `chief-of-staff`, NOT witnessed first-hand by the agent that wrote this
 *    file): the mobile shop grid goes 2-UP, and ⛔ THE KIRKUS BADGE AND THE
 *    REVIEW QUOTE COME OFF THE MOBILE CARDS.
 *
 * ⭐ THE COMPARATOR EVIDENCE IS PIPPIN'S, NOT THIS FILE'S, and it is layout
 *    evidence only: Barefoot 179px · Chronicle 165px · Highlights 165px ·
 *    Powell's 195px, each measured in real Chrome at an asserted
 *    `window.innerWidth` of 390. `Business OS\WORKING-DRAFTS\commerce-cx\
 *    COMPETITOR-MOBILE-EXPERIENCE-2026-08-21.md`. ⛔ NO CONVERSION CLAIM IS
 *    MADE FROM IT HERE OR ANYWHERE, because none was observable.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔⛔ WHAT THIS SUITE CANNOT PROVE, SAID FIRST RATHER THAN BURIED
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⛔ IT CANNOT PROVE A LAYOUT. PHP has no viewport. Nothing here shows that two
 *    cards sit side by side, that a card measures 172px, or that the Kirkus
 *    badge is invisible to a parent holding a phone. ⭐ THAT PROOF IS THE
 *    BROWSER HARNESS, at an asserted `window.innerWidth` of 390 and 1440, and
 *    it is the primary evidence — this file is the REGRESSION GATE that stops
 *    the ruling being undone later by an edit nobody re-QAs.
 *
 * ⭐ SO IT ASSERTS THE TWO THINGS SOURCE *CAN* CARRY:
 *      · the MARKUP contract (the age line exists, per card, from the right
 *        source, and the `FD-549` rail attribute survived), and
 *      · the SHIPPED CSS ARTEFACT (`style.min.css` — the file the site
 *        actually serves, ⛔ not `style.css`, because a stale build artefact
 *        is precisely how a verified CSS change fails to reach a customer).
 *
 * WHAT IT ASSERTS
 *   §1  the age helper resolves per product, meta first, standing string after
 *   §2  every single-title shop card renders the age line
 *   §3  the `FD-549` rail attribute `data-bhp-card-kind` survived the change
 *   §4  the SHIPPED artefact carries the 2-up track rule, scoped to the shop
 *   §5  the SHIPPED artefact hides Kirkus + the review — ⛔ MOBILE ONLY
 *   §6  the offer/collection cards take a full row (the no-orphan rule)
 *   §7  ⛔ THE DESKTOP CARD IS UNCHANGED — the components are not hidden
 *       globally, and the age line is off by default
 *   §8  ⛔ NO REVIEW, RATING OR TRUST STRING WAS DELETED FROM ANYWHERE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * ⛔ $GLOBALS, not `global` — `wp eval-file` runs this file inside a function,
 *    so a `global $x` in a helper binds to a different, always-empty variable
 *    and the summary prints "0 failed" on a broken build. Same reason, same
 *    fix, as `test-freeship-line-parity.php`. A gate that cannot report
 *    failure is not a gate.
 */
$GLOBALS['s2u_failures'] = 0;
$GLOBALS['s2u_passes']   = 0;
function s2u_assert( $cond, $label ) {
	if ( $cond ) {
		$GLOBALS['s2u_passes']++;
		echo "PASS: $label\n";
	} else {
		$GLOBALS['s2u_failures']++;
		echo "FAIL: $label\n";
	}
}

$s2u_theme = get_template_directory();

/**
 * The SHIPPED stylesheet, with whitespace flattened.
 *
 * ⛔ `style.min.css`, NOT `style.css`, AND THE CHOICE IS THE POINT. From
 *    1.19.201 the root stylesheet is served from the build artefact
 *    (`bhp_minified_style_src()`). Asserting the source file would pass on a
 *    build that was never run — the one failure mode that puts verified CSS
 *    in the repository and nothing on the customer's screen.
 * ⭐ Whitespace is flattened because a minifier's line breaks are not a
 *    contract; the declarations are.
 */
function s2u_shipped_css( $path ) {
	if ( ! file_exists( $path ) ) {
		return '';
	}
	return preg_replace( '/\s+/', ' ', (string) file_get_contents( $path ) );
}

$s2u_css     = s2u_shipped_css( $s2u_theme . '/style.min.css' );
$s2u_fmt_css = s2u_shipped_css( $s2u_theme . '/assets/css/book-formats.min.css' );

/**
 * Every `@media (max-width: 640px)` block in the shipped artefact, brace-matched.
 *
 * ⭐⭐ THE WHOLE SUITE TURNS ON THIS SEPARATION, so it is done once, here, and
 *    both halves are kept. §4–§6 assert what must be INSIDE the mobile block;
 *    §7 asserts what must be OUTSIDE it.
 *
 * ⛔ WITHOUT IT, "the Kirkus badge is hidden" passes on a build that hides the
 *    badge at EVERY width — which is not the ruling, and which would silently
 *    strip a real trust element from the desktop storefront while the mobile
 *    screenshot looked perfect.
 *
 * ⛔ THE BLOCKS ARE KEPT AS AN ARRAY, NOT ONLY AS ONE CONCATENATED STRING, AND
 *    THAT IS A BUG FIX RECORDED RATHER THAN HIDDEN. §7 originally built its
 *    "outside" text as `str_replace( $concatenated, '', $css )` — which
 *    removes NOTHING when there is more than one such block, because the
 *    concatenation appears nowhere in the file. §7.4 therefore failed on a
 *    correct build. Removing the blocks ONE AT A TIME is what makes it true.
 */
$s2u_blocks = array();
if ( preg_match_all( '/@media\s*\(max-width:\s*640px\)\s*\{/', $s2u_css, $s2u_mm, PREG_OFFSET_CAPTURE ) ) {
	foreach ( $s2u_mm[0] as $s2u_hit ) {
		$s2u_i     = $s2u_hit[1] + strlen( $s2u_hit[0] );
		$s2u_start = $s2u_i;
		$s2u_depth = 1;
		$s2u_len   = strlen( $s2u_css );
		while ( $s2u_i < $s2u_len && $s2u_depth > 0 ) {
			if ( '{' === $s2u_css[ $s2u_i ] ) {
				$s2u_depth++;
			} elseif ( '}' === $s2u_css[ $s2u_i ] ) {
				$s2u_depth--;
			}
			$s2u_i++;
		}
		$s2u_blocks[] = substr( $s2u_css, $s2u_start, $s2u_i - $s2u_start );
	}
}
$s2u_mobile = implode( ' ', $s2u_blocks );

echo "\n=== §1 · THE AGE LINE RESOLVES, IT IS NOT A LITERAL IN A TEMPLATE ===\n";

s2u_assert(
	function_exists( 'bhp_shop_card_age_range' ),
	'1.1 bhp_shop_card_age_range() exists'
);

if ( function_exists( 'bhp_shop_card_age_range' ) && function_exists( 'bhp_book_registry' ) ) {
	/*
	 * ⭐ RESOLVED THROUGH REAL PRODUCT IDS, NOT MADE-UP ONES, AND THROUGH THE
	 *    SAME SOURCE THE CARD ITSELF READS — `bhp_book_purchase_data()`, which
	 *    reads `WC_Product` every time. If the registry moves, this assertion
	 *    moves with it rather than pinning a stale ID that would go on passing
	 *    after the product it names had gone.
	 */
	$s2u_ids = array();
	foreach ( array_keys( bhp_book_registry() ) as $s2u_key ) {
		$s2u_data = function_exists( 'bhp_book_purchase_data' ) ? bhp_book_purchase_data( $s2u_key ) : null;
		if ( $s2u_data && ! empty( $s2u_data['paperback']['product_id'] ) ) {
			$s2u_ids[] = (int) $s2u_data['paperback']['product_id'];
		}
	}

	s2u_assert( ! empty( $s2u_ids ), '1.2 at least one canonical product id resolved live' );

	foreach ( $s2u_ids as $s2u_id ) {
		$s2u_meta = get_post_meta( $s2u_id, 'bhp_age_range', true );
		$s2u_got  = bhp_shop_card_age_range( $s2u_id );
		s2u_assert(
			'' !== trim( (string) $s2u_got ),
			sprintf( '1.3 product %d resolves a non-empty age band ("%s")', $s2u_id, $s2u_got )
		);
		s2u_assert(
			$s2u_meta ? ( $s2u_got === $s2u_meta ) : ( 'Ages 6–9' === $s2u_got ),
			sprintf(
				'1.4 product %d — %s',
				$s2u_id,
				$s2u_meta ? 'per-product meta WINS over the standing string' : 'falls back to the standing "Ages 6–9"'
			)
		);
		/*
		 * ⛔ THE RAIL, ASSERTED RATHER THAN TRUSTED: ages 6-9, NEVER 5-9
		 *    (copy rail §9.1). This is the one string on the new element and
		 *    it is the one the standing rules name explicitly.
		 */
		s2u_assert(
			false === strpos( (string) $s2u_got, '5-9' )
				&& false === strpos( (string) $s2u_got, '5–9' )
				&& false === strpos( (string) $s2u_got, '5 - 9' ),
			sprintf( '1.5 ⛔ product %d does NOT say 5-9 in any dash form (copy rail §9.1)', $s2u_id )
		);
	}
}

echo "\n=== §2 · EVERY SINGLE-TITLE CARD RENDERS IT ===\n";

/*
 * ⭐ RENDERED, NOT INFERRED — as far as PHP can go. The card-meta callbacks are
 *    invoked inside a real loop context via `setup_postdata()`, so this tests
 *    the actual output of the actual hook, not a re-implementation of it.
 * ⛔ It still is not a layout proof. See the header.
 */
$s2u_probe_ids = array();
if ( function_exists( 'bhp_book_registry' ) && function_exists( 'bhp_book_purchase_data' ) ) {
	foreach ( array_keys( bhp_book_registry() ) as $s2u_key ) {
		$s2u_d = bhp_book_purchase_data( $s2u_key );
		if ( $s2u_d && ! empty( $s2u_d['paperback']['product_id'] ) ) {
			$s2u_probe_ids[ 'chapter:' . $s2u_key ] = (int) $s2u_d['paperback']['product_id'];
		}
	}
}
if ( function_exists( 'bhp_colouring_product_ids' ) ) {
	foreach ( bhp_colouring_product_ids() as $s2u_slug => $s2u_cid ) {
		if ( $s2u_cid ) {
			$s2u_probe_ids[ 'colouring:' . $s2u_slug ] = (int) $s2u_cid;
		}
	}
}

s2u_assert( ! empty( $s2u_probe_ids ), '2.1 at least one shop-card product resolved live' );

foreach ( $s2u_probe_ids as $s2u_label => $s2u_id ) {
	$s2u_post = get_post( $s2u_id );
	if ( ! $s2u_post ) {
		s2u_assert( false, sprintf( '2.x %s (%d) — post not found', $s2u_label, $s2u_id ) );
		continue;
	}
	$GLOBALS['post']    = $s2u_post;
	$GLOBALS['product'] = wc_get_product( $s2u_id );
	setup_postdata( $s2u_post );

	ob_start();
	if ( 0 === strpos( $s2u_label, 'colouring:' ) ) {
		if ( function_exists( 'bhp_colouring_shop_card_meta' ) ) {
			bhp_colouring_shop_card_meta();
		}
	} elseif ( function_exists( 'bhp_book_shop_card_meta' ) ) {
		bhp_book_shop_card_meta();
	}
	$s2u_html = (string) ob_get_clean();
	wp_reset_postdata();

	s2u_assert(
		false !== strpos( $s2u_html, 'class="bhp-shop-ages"' ),
		sprintf( '2.2 %s (%d) renders the age line', $s2u_label, $s2u_id )
	);
	s2u_assert(
		(bool) preg_match( '/class="bhp-shop-ages">([^<]+)</', $s2u_html, $s2u_m ) && '' !== trim( $s2u_m[1] ),
		sprintf( '2.3 %s (%d) — the age line is not empty', $s2u_label, $s2u_id )
	);
	/*
	 * ⭐ ORDER MATTERS AND IS ASSERTED: his contract reads image · title ·
	 *    AGES · price(s) · CTA. The age band must precede the priced line in
	 *    the DOM, because on mobile the descriptor between them is hidden and
	 *    DOM order becomes reading order.
	 */
	$s2u_a = strpos( $s2u_html, 'bhp-shop-ages' );
	$s2u_p = strpos( $s2u_html, 'bhp-shop-format-prices' );
	s2u_assert(
		false !== $s2u_a && false !== $s2u_p && $s2u_a < $s2u_p,
		sprintf( '2.4 %s (%d) — the age line precedes the priced line', $s2u_label, $s2u_id )
	);

	/*
	 * §3 · THE FD-549 RAIL ATTRIBUTE SURVIVED.
	 * ⛔ THE REASON THIS IS HERE AT ALL. The mobile card hides the descriptor
	 *    with `display:none` — and `data-bhp-card-kind="single"`, the rail hook
	 *    every kind-aware suite reads, lives ON that descriptor. Hiding a box
	 *    does not remove an attribute, but a future agent "cleaning up" a
	 *    hidden element would take the rail hook with it.
	 */
	s2u_assert(
		false !== strpos( $s2u_html, 'data-bhp-card-kind="single"' ),
		sprintf( '3.1 %s (%d) still declares data-bhp-card-kind="single"', $s2u_label, $s2u_id )
	);
}

echo "\n=== §4 · THE SHIPPED ARTEFACT CARRIES THE 2-UP RULE ===\n";

s2u_assert( '' !== $s2u_css, '4.1 style.min.css exists and is readable' );
/*
 * ⛔⛔ AMENDED AT 1.19.284. THE SUPERSEDED ASSERTION, QUOTED SO THE MOVEMENT IS
 *    VISIBLE AND IS NOT RE-DERIVED AS A RELAXATION:
 *
 *      s2u_assert(
 *          false !== strpos( $s2u_css, 'Version: 1.19.283' ),
 *          '4.2 ⛔ the artefact was REBUILT for this release (its header names 1.19.283)'
 *      );
 *
 * ⭐ WHAT IT WAS TRYING TO PROVE WAS RIGHT: that `style.min.css` is not a
 *    stale build left over from a previous release. ⛔ HOW IT PROVED IT WAS
 *    WRONG: a hardcoded version literal only holds for exactly one release and
 *    then reports a CORRECT build as a failure. It did precisely that at
 *    1.19.284 — one of the three failures this release inherited.
 *
 * ⭐⭐ THE REPLACEMENT IS STRICTLY STRONGER, not weaker, and that is the point.
 *    It asserts TWO things the literal never could:
 *      1. the artefact's header version equals the ACTIVE THEME's version —
 *         true at every release, and false whenever a bump ships without a
 *         rebuild, which is the actual defect;
 *      2. the artefact's recorded `source-md5` equals the live md5 of
 *         `style.css` — ⛔ THIS IS THE REAL FRESHNESS PROOF. A rebuild that
 *         ran against an older source would carry the right version and the
 *         wrong rules, and the version check alone would pass it.
 */
$s2u_theme_ver = wp_get_theme()->get( 'Version' );
s2u_assert(
	false !== strpos( $s2u_css, 'Version: ' . $s2u_theme_ver ),
	"4.2a ⛔ the artefact's header names the ACTIVE theme version ({$s2u_theme_ver})"
);

/*
 * ⛔⛔ THE HASH IS TAKEN OVER LF-NORMALISED BYTES, AND THAT IS A CORRECTION MADE
 *    AFTER THIS ASSERTION FAILED ON A GENUINELY FRESH ARTEFACT.
 *
 * ⭐ WHAT HAPPENED, MEASURED RATHER THAN GUESSED (staging, 2026-08-21):
 *      live style.css as-is        = 2a4666159b240cd596c6656fbb53c2db
 *      live style.css CR-stripped  = 407016bdb5fe76dcd11ecfa549cb06c9
 *      recorded source-md5         = 407016bdb5fe76dcd11ecfa549cb06c9
 *    `tools/build-css.mjs` hashes the LF working copy, but the repo marks
 *    `style.css` as text, so `git archive` writes CRLF into the deploy ZIP and
 *    the file that lands on the server is byte-different from the file that was
 *    hashed. ⛔ THE ARTEFACT WAS FRESH; THE COMPARISON WAS WRONG.
 *
 * ⭐ NORMALISING IS NOT A WEAKENING. A stale artefact still fails: the CSS
 *    RULES would differ, and no line-ending change can disguise that. What is
 *    given up is only the ability to detect a pure line-ending change, which is
 *    not a defect and is introduced by the deploy pipeline on every release.
 */
$s2u_src_path = get_template_directory() . '/style.css';
$s2u_src_md5  = is_readable( $s2u_src_path )
	? md5( str_replace( "\r\n", "\n", (string) file_get_contents( $s2u_src_path ) ) )
	: '';
$s2u_rec_md5  = preg_match( '/source-md5:\s*([0-9a-f]{32})/i', $s2u_css, $s2u_mm ) ? strtolower( $s2u_mm[1] ) : '';
s2u_assert(
	'' !== $s2u_src_md5 && $s2u_src_md5 === $s2u_rec_md5,
	sprintf(
		'4.2b ⭐ the artefact was built from THIS style.css (source-md5 %s vs live %s)',
		'' !== $s2u_rec_md5 ? substr( $s2u_rec_md5, 0, 8 ) : 'absent',
		'' !== $s2u_src_md5 ? substr( $s2u_src_md5, 0, 8 ) : 'unreadable'
	)
);
s2u_assert(
	(bool) preg_match(
		'/body\.woocommerce-shop[^{]*ul\.products\s*\{[^}]*grid-template-columns:\s*repeat\(2/',
		$s2u_css
	),
	'4.3 ⭐ two grid tracks, scoped to body.woocommerce-shop'
);
/*
 * ⛔ THE SCOPE IS ASSERTED, NOT ASSUMED. `.woo-expedition-shell` also wraps the
 *    SINGLE PRODUCT page, whose related/upsell rows are `ul.products
 *    li.product` too. An unscoped rule would restyle a surface item 204 never
 *    mentioned, and it would do it silently.
 *
 * ⛔⛔ THE FIRST VERSION OF THIS ASSERTION WAS A TEST DEFECT AND IS RECORDED
 *    RATHER THAN QUIETLY REPLACED — it is the same shape as the bug the
 *    1.19.282 sweep found in `test-freeship-line-parity.php`. It read:
 *
 *      false === strpos( $css, '.woo-expedition-shell ul.products { grid-…' )
 *
 *    ⛔ THAT CAN NEVER PASS, because the forbidden literal is a SUBSTRING of
 *       the correct one: `body.woocommerce-shop .woo-expedition-shell
 *       ul.products {…}` CONTAINS `.woo-expedition-shell ul.products {…}`. It
 *       failed on a perfectly correct build. ⭐ A gate that fires on the right
 *       answer is worse than no gate — the next agent "fixes" the code.
 *
 * ⭐ THE CORRECT TEST WALKS THE RULES. Every rule inside the mobile block that
 *    sets two tracks on `ul.products` must carry `body.woocommerce-shop` in its
 *    own selector. That is the property that actually matters, and it stays
 *    true no matter how the minifier spaces the output.
 */
$s2u_unscoped = array();
if ( preg_match_all( '/([^{}]*ul\.products[^{}]*)\{([^}]*)\}/', $s2u_mobile, $s2u_rules, PREG_SET_ORDER ) ) {
	foreach ( $s2u_rules as $s2u_rule ) {
		if ( ! preg_match( '/grid-template-columns:\s*repeat\(\s*2/', $s2u_rule[2] ) ) {
			continue;
		}
		if ( false === strpos( $s2u_rule[1], 'body.woocommerce-shop' ) ) {
			$s2u_unscoped[] = trim( $s2u_rule[1] );
		}
	}
}
s2u_assert(
	empty( $s2u_unscoped ),
	sprintf(
		'4.4 ⛔ every 2-up rule in the mobile block is scoped to body.woocommerce-shop (unscoped: %s)',
		empty( $s2u_unscoped ) ? 'none' : implode( ' | ', $s2u_unscoped )
	)
);

echo "\n=== §5 · KIRKUS AND THE REVIEW COME OFF — MOBILE ONLY ===\n";

s2u_assert(
	'' !== $s2u_mobile,
	sprintf( '5.1 a max-width:640px block exists in the shipped artefact (%d found)', count( $s2u_blocks ) )
);
s2u_assert(
	false !== strpos( $s2u_mobile, '.kirkus-credibility' )
		&& (bool) preg_match( '/\.kirkus-credibility[^{]*\{[^}]*display:\s*none/', $s2u_mobile ),
	'5.2 ⭐ ITEM 204 — the Kirkus badge is hidden INSIDE the mobile block'
);
s2u_assert(
	(bool) preg_match( '/\.amazon-review-showcase[^{]*\{[^}]*display:\s*none/', $s2u_mobile ),
	'5.3 ⭐ ITEM 204 — the review quote is hidden INSIDE the mobile block'
);
s2u_assert(
	(bool) preg_match( '/\.woo-card__eyebrow[^{]*\{[^}]*display:\s*none/', $s2u_mobile ),
	'5.4 the eyebrow is off the mobile card (not in his enumerated contract)'
);
s2u_assert(
	(bool) preg_match( '/\.bhp-shop-ages[^{]*\{[^}]*display:\s*block/', $s2u_mobile ),
	'5.5 …and the age line is turned ON inside the same block'
);

echo "\n=== §6 · HONEST ROWS — NO ORPHAN CELL (REWRITTEN AT 1.19.284, ITEM 206) ===\n";

/*
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔⛔⛔ THE 1.19.283 §6 WAS SUPERSEDED BY CARRIER ITEM 206 — NOT RELAXED.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⛔ THE TWO SUPERSEDED ASSERTIONS, QUOTED VERBATIM so a future session can see
 *    exactly what stopped being true and does not read this as a weakened gate:
 *
 *      6.1 ⭐ the pair-offer card takes a full-width row
 *          preg_match( '/li\.bhp-shop-offer-item[^{]*\{[^}]*grid-column:\s*1\s*\/\s*-1/', $mobile )
 *      6.2 ⭐ the Complete Collection card takes a full-width row
 *          preg_match( '/bhp-shop-collection-item[^{]*\{[^}]*grid-column:\s*1\s*\/\s*-1/', $mobile )
 *
 * ⭐⭐ AND THE OLD §6.3 PREDICTED THIS EXACT MOMENT. It read: *"If a composite
 *    is ever registered, this assertion still holds and the full-row choice can
 *    be revisited DELIBERATELY rather than by accident."* This is that
 *    revisiting, and it is deliberate: the guard was written to fire, it fired,
 *    and the decision is being recorded rather than the assertion quietly
 *    deleted.
 *
 * ⭐ WHY THE PREMISE DIED. The full-row rule's stated reason was never taste —
 *    it was that both offer cards rendered IMAGELESS (`FD-549` R2.3, no bundle
 *    composite existed) and *"an imageless card squeezed into a 172px cell
 *    beside a card that has a cover reads as a broken tile."* At 1.19.284 two
 *    real composites exist and are registered as attachments, so both cards
 *    carry a picture in the same slot as the card beside them. ⛔ THERE IS NO
 *    IMAGELESS TILE LEFT TO PROTECT.
 *
 * ⛔ THE NO-ORPHAN RULE ITSELF IS UNCHANGED AND IS STILL THE POINT OF §6. It is
 *    now enforced by ARITHMETIC on the SERVED grid (§6.3) rather than by
 *    exempting two cards from it.
 */

/*
 * ⛔ THE INVERSION, ASSERTED. The superseded rule must be GONE from the shipped
 *    artefact, not merely overridden by a later block — a `grid-column: 1 / -1`
 *    still present and out-ranked by specificity is a one-edit revert away from
 *    silently returning, and CSS order bugs of that shape are invisible on
 *    screen until a fourth chapter book lands.
 */
s2u_assert(
	! preg_match( '/li\.bhp-shop-offer-item[^{}]*\{[^}]*grid-column:\s*1\s*\/\s*-1/', $s2u_mobile )
		&& ! preg_match( '/bhp-shop-collection-item[^{}]*\{[^}]*grid-column:\s*1\s*\/\s*-1/', $s2u_mobile ),
	'6.1 ⭐ ITEM 206 — neither bundle card is forced to a full-width row any more'
);

/*
 * ⭐⭐ THE COMPOSITES ARE REGISTERED — AND THIS IS NOW A GATE, NOT A STATE NOTE.
 *    It is the ENTIRE premise of dropping the full-row rule. If the composites
 *    stop resolving, the cards go back to being imageless in a 172px cell,
 *    which is the exact "broken tile" the superseded rule existed to prevent —
 *    and this build would then be wrong rather than merely different.
 *
 * ⛔ IT DOES NOT ASSERT A HARDCODED ATTACHMENT ID. Ids are environment-local;
 *    staging's 4570 is not production's 4570. It asserts that the SLUGS RESOLVE
 *    on whatever environment the suite is running against, which is the thing
 *    that actually has to be true.
 */
if ( function_exists( 'bhp_offer_composite_attachment_id' ) && function_exists( 'bhp_offer_composite_slugs' ) ) {
	foreach ( bhp_offer_composite_slugs() as $s2u_ck => $s2u_slug ) {
		$s2u_cid = (int) bhp_offer_composite_attachment_id( $s2u_ck );
		s2u_assert(
			$s2u_cid > 0 && 'attachment' === get_post_type( $s2u_cid ),
			sprintf(
				'6.2 ⭐ the "%s" composite resolves to a real attachment (slug %s -> ID %d)',
				$s2u_ck,
				$s2u_slug,
				$s2u_cid
			)
		);
	}
} else {
	s2u_assert( false, '6.2 ⛔ bhp_offer_composite_slugs()/bhp_offer_composite_attachment_id() missing — item 206 did not load' );
}

/*
 * ⛔⛔ THE NO-ORPHAN RULE, RE-PROVED BY COUNTING THE SERVED GRID RATHER THAN BY
 *    EXEMPTING CARDS FROM IT. Six cards in a 2-track grid is three clean rows.
 *    An ODD count would leave exactly the half-width orphan cell the 1.19.283
 *    rule was working around — so a seventh card cannot silently orphan the row
 *    without this failing.
 *
 * ⭐ COUNTED FROM THE RENDERED `ul.products` ON THE LIVE ENVIRONMENT, not from a
 *    template and not from a constant. The count is reported either way so the
 *    number is in the log even when it passes.
 */
/*
 * ⛔ THE SHOP ARCHIVE IS FETCHED FRESH HERE. It is NOT reused from `$s2u_html`
 *    above — that variable holds one card's META FRAGMENT from the §2 loop, not
 *    the archive document, and counting `<li>` in it would return 0 on a
 *    correct build. (Caught by running the suite, not by reading it.)
 */
$s2u_shop_url  = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : '';
$s2u_shop_doc  = '';
if ( '' !== $s2u_shop_url ) {
	$s2u_resp = wp_remote_get( $s2u_shop_url, array( 'timeout' => 45, 'sslverify' => false ) );
	if ( ! is_wp_error( $s2u_resp ) && 200 === (int) wp_remote_retrieve_response_code( $s2u_resp ) ) {
		$s2u_shop_doc = (string) wp_remote_retrieve_body( $s2u_resp );
	}
}

if ( '' !== $s2u_shop_doc && preg_match( '#<ul[^>]*class="[^"]*\bproducts\b[^"]*"[^>]*>(.*?)</ul>#s', $s2u_shop_doc, $s2u_ul ) ) {
	$s2u_card_n = preg_match_all( '/<li[^>]*\bclass="[^"]*\bproduct\b[^"]*"/', $s2u_ul[1] );
	s2u_assert(
		$s2u_card_n > 0 && 0 === $s2u_card_n % 2,
		sprintf( '6.3 ⛔ the served grid holds an EVEN number of cards, so 2-up leaves no orphan cell (%d cards)', $s2u_card_n )
	);
	/*
	 * ⭐ AND BOTH BUNDLE CARDS ARE ACTUALLY IN THAT COUNT. An even number that
	 *    happened to exclude the two offer cards would pass 6.3 while item 206
	 *    was entirely missing from the grid.
	 */
	s2u_assert(
		1 === substr_count( $s2u_ul[1], 'bhp-shop-offer-item' )
			&& 1 === substr_count( $s2u_ul[1], 'bhp-shop-collection-item' ),
		sprintf(
			'6.4 ⭐ both bundle cards are IN the served grid (pair %d, collection %d)',
			substr_count( $s2u_ul[1], 'bhp-shop-offer-item' ),
			substr_count( $s2u_ul[1], 'bhp-shop-collection-item' )
		)
	);
} else {
	s2u_assert( false, '6.3 ⛔ the served ul.products could not be isolated to count cards' );
}

echo "\n=== §7 · THE DESKTOP CARD IS UNCHANGED ===\n";

/*
 * ⛔ THE HALF OF THE RULING THAT IS EASIEST TO BREAK AND HARDEST TO NOTICE.
 *    "Take it off mobile" is one selector away from "take it off everywhere",
 *    and the difference is invisible to anyone testing on a phone.
 */
/*
 * ⛔ ONE AT A TIME. See the extraction note above — removing the concatenation
 *    removes nothing, and §7.4 then fails on a correct build.
 */
$s2u_outside = $s2u_css;
foreach ( $s2u_blocks as $s2u_block ) {
	$s2u_outside = str_replace( $s2u_block, '', $s2u_outside );
}
s2u_assert(
	! preg_match( '/(^|[},])\s*\.kirkus-credibility\s*\{[^}]*display:\s*none/', $s2u_outside ),
	'7.1 ⛔ Kirkus is NOT hidden globally — the desktop card keeps its badge'
);
s2u_assert(
	! preg_match( '/(^|[},])\s*\.amazon-review-showcase\s*\{[^}]*display:\s*none/', $s2u_outside ),
	'7.2 ⛔ the review is NOT hidden globally — the desktop card keeps its quote'
);
s2u_assert(
	(bool) preg_match( '/\.bhp-shop-ages\s*\{\s*display:\s*none/', $s2u_fmt_css ),
	'7.3 ⭐ the age line is OFF by default, so the desktop card gains nothing'
);
s2u_assert(
	false === strpos( $s2u_outside, 'bhp-shop-descriptor[data-bhp-card-kind="single"]' ),
	'7.4 the descriptor is hidden ONLY inside the mobile block'
);

echo "\n=== §8 · NOTHING WAS DELETED FROM THE TRUST LAYER ===\n";

/*
 * ⛔⛔ THE ASSERTION THAT MATTERS MOST AND COSTS THE LEAST. Standing rules §2
 *    and §3: reviews, ratings and endorsements are never invented — and by the
 *    same rule they are never quietly destroyed to make a layout easier. This
 *    release moved a CSS boundary. It touched no review, no rating, no
 *    attribution and no Kirkus datum, and this section is what says so
 *    executably rather than in a comment.
 */
if ( function_exists( 'bhp_render_kirkus_credibility' ) ) {
	$s2u_k = bhp_render_kirkus_credibility( 'compact', array( 'source' => 'test', 'show_link' => false ) );
	s2u_assert( '' !== trim( (string) $s2u_k ), '8.1 the Kirkus component still renders' );
}
if ( function_exists( 'bhp_render_amazon_review_showcase' ) ) {
	$s2u_r = bhp_render_amazon_review_showcase( 'mariana_trench', 'compact', array( 'source' => 'test', 'max_reviews' => 1 ) );
	s2u_assert( '' !== trim( (string) $s2u_r ), '8.2 the Amazon review component still renders' );
	s2u_assert(
		false !== strpos( (string) $s2u_r, 'amazon-review-card__attribution' ),
		'8.3 ⭐ …and the review is still ATTRIBUTED (standing rules §2/§3)'
	);
}
s2u_assert(
	has_action( 'woocommerce_after_shop_loop_item_title', 'bhp_woocommerce_loop_kirkus_badge' ) !== false,
	'8.4 ⛔ the shop-card Kirkus HOOK is still registered — nothing was unhooked'
);
s2u_assert(
	has_action( 'woocommerce_after_shop_loop_item_title', 'bhp_woocommerce_loop_amazon_review_badge' ) !== false,
	'8.5 ⛔ the shop-card review HOOK is still registered — nothing was unhooked'
);

echo "\n";
printf(
	"SHOP GRID 2-UP (item 204): %d passed, %d failed\n",
	(int) $GLOBALS['s2u_passes'],
	(int) $GLOBALS['s2u_failures']
);
if ( $GLOBALS['s2u_failures'] > 0 ) {
	echo 'FAILED (' . (int) $GLOBALS['s2u_failures'] . ")\n";
}
echo 'FAILURES: ' . (int) $GLOBALS['s2u_failures'] . "\n";
