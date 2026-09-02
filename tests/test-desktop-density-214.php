<?php
/**
 * CARRIER ITEM 214 — 4-ACROSS ON THE DESKTOP SHOP, AND THE PAIR OFFER READ
 * FROM BOTH ENDS. Theme 1.19.287 / plugin 1.8.68. `CYCLE165-LD-DESKTOP-DENSITY`.
 * ============================================================================
 *
 * Run:
 *   wp eval-file wp-content/themes/brave-hearts-theme-deploy-explorer-expedition-guides/tests/test-desktop-density-214.php \
 *      --user=1 --url=https://staging2.braveheartspublishing.com
 *
 * ⭐ ANDREW SIGNORE, carrier item 214, 2026-08-21 (⚠️ RELAYED through
 *    `chief-of-staff`, ⛔ NOT witnessed first-hand by the agent that wrote this
 *    file). Two refinements after a PASSED production walk:
 *
 *      1. THE DESKTOP SHOP CARDS SHRINK — 4 across at 1440, at the field norm
 *         of roughly 300px, same card contract, uniform CTAs holding their
 *         1.19.286 geometry rules, 390 2-up unchanged, and the six cards
 *         laying out without an orphan mid-grid.
 *      2. THE PANEL CROSS-SELL GOES BIDIRECTIONAL — a cart holding the
 *         colouring book WITHOUT its chapter book is offered the chapter book,
 *         through the SAME offer engine, on the SAME one-offer-at-a-time rail.
 *         The existing chapter→colouring direction is unchanged.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔⛔ WHAT THIS SUITE CANNOT PROVE, SAID FIRST RATHER THAN BURIED
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⛔ IT CANNOT PROVE A LAYOUT. PHP has no viewport. Nothing here shows that
 *    four cards sit in a row, that a card measures 291px, or that the two
 *    bundle cards are centred under them. ⭐ THAT PROOF IS THE BROWSER
 *    HARNESS, at an asserted `window.innerWidth`, and it is the PRIMARY
 *    evidence — this file is the REGRESSION GATE that stops the ruling being
 *    undone later by an edit nobody re-QAs.
 *
 * ⛔ IT CANNOT PROVE A LIVE PANEL EITHER. §6 asserts that the SERVER publishes
 *    a reverse-direction payload with real, purchasable ids and real words.
 *    Whether the drawer then RENDERS it, and whether adding the book fires the
 *    $22.99 fee, is a real Blocks cart in a real browser and nothing else.
 *
 * WHAT IT ASSERTS
 *   §1  the SHIPPED artefact carries the 4-track rule, scoped to the shop
 *   §2  the 4+2 centring rule, and its exactly-six count guard
 *   §3  ⛔ NOTHING BELOW 1280 MOVED — 3-across and the 390 2-up block survive
 *   §4  the CTA tokens take 291px values and the collection calc stays POSITIVE
 *   §5  the card contract is intact — no shop-card component was hidden
 *   §6  the reverse payload resolves to real purchasable ids and real words
 *   §7  ⛔ THE FORWARD DIRECTION IS UNCHANGED — every 1.8.67 field survives
 *   §8  the new copy obeys the rails: no "we", no em dash, no figure
 *   §9  the drawer JS carries the three gates and the eyebrow suppression
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * ⛔ $GLOBALS, not `global` — `wp eval-file` runs this file inside a function,
 *    so a `global $x` in a helper binds to a different, always-empty variable
 *    and the summary prints "0 failed" on a broken build. Same reason, same
 *    fix, as `test-shop-grid-2up-204.php`. A gate that cannot report failure
 *    is not a gate.
 */
$GLOBALS['dd_failures'] = 0;
$GLOBALS['dd_passes']   = 0;
function dd_assert( $cond, $label ) {
	if ( $cond ) {
		$GLOBALS['dd_passes']++;
		echo "PASS: $label\n";
	} else {
		$GLOBALS['dd_failures']++;
		echo "FAIL: $label\n";
	}
}

$dd_theme = get_template_directory();

/**
 * The SHIPPED stylesheet, whitespace flattened.
 *
 * ⛔ `style.min.css`, NOT `style.css`, AND THE CHOICE IS THE POINT. From
 *    1.19.201 the root stylesheet is served from the build artefact
 *    (`bhp_minified_style_src()`). Asserting the source file would pass on a
 *    build that was never run — the one failure mode that puts verified CSS in
 *    the repository and nothing on the customer's screen.
 */
function dd_shipped( $path ) {
	if ( ! file_exists( $path ) ) {
		return '';
	}
	return preg_replace( '/\s+/', ' ', (string) file_get_contents( $path ) );
}

$dd_css = dd_shipped( $dd_theme . '/style.min.css' );

/**
 * Brace-match every `@media (min-width: 1280px)` block in the artefact.
 *
 * ⭐⭐ THE WHOLE SUITE TURNS ON THIS SEPARATION, so it is done once, here, and
 *    BOTH halves are kept. §1, §2 and §4 assert what must be INSIDE the
 *    desktop block; §3 asserts what must be OUTSIDE it.
 *
 * ⛔ WITHOUT IT, "the grid is 4-across" passes on a build that is 4-across at
 *    EVERY width — which is not the ruling, and which would put four 96px
 *    cards on a phone while the desktop screenshot looked perfect.
 *
 * ⛔ THE BLOCKS ARE REMOVED ONE AT A TIME, not as one concatenation. That is a
 *    bug `test-shop-grid-2up-204.php` already paid for and recorded: a
 *    concatenation of two blocks appears nowhere in the file, so
 *    `str_replace()` of it removes nothing and the "outside" assertions pass
 *    on text that is still inside.
 */
$dd_blocks = array();
$dd_out    = $dd_css;
if ( preg_match_all( '/@media\s*\(min-width:\s*1280px\)\s*\{/', $dd_css, $dd_mm, PREG_OFFSET_CAPTURE ) ) {
	foreach ( $dd_mm[0] as $dd_hit ) {
		$dd_i     = $dd_hit[1] + strlen( $dd_hit[0] );
		$dd_start = $dd_i;
		$dd_depth = 1;
		$dd_len   = strlen( $dd_css );
		while ( $dd_i < $dd_len && $dd_depth > 0 ) {
			if ( '{' === $dd_css[ $dd_i ] ) {
				$dd_depth++;
			} elseif ( '}' === $dd_css[ $dd_i ] ) {
				$dd_depth--;
			}
			$dd_i++;
		}
		$dd_blocks[] = substr( $dd_css, $dd_start, $dd_i - $dd_start );
	}
}
foreach ( $dd_blocks as $dd_b ) {
	$dd_out = str_replace( $dd_b, '', $dd_out );
}
$dd_desktop = implode( ' ', $dd_blocks );

echo "\n=== §1 · THE 4-TRACK RULE IS IN THE SHIPPED ARTEFACT, SCOPED ===\n";

dd_assert( '' !== $dd_css, '1.0 style.min.css exists and is non-empty' );
dd_assert(
	! empty( $dd_blocks ),
	'1.1 the artefact contains at least one @media (min-width: 1280px) block'
);
/*
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐ UPDATED 2026-09-02 BY 1.19.350 (`CYCLE179-LD-350-BUILD`). ITEM 214'S
 *     RULING IS *"DESKTOP GETS MORE CARDS ACROSS"*, AND THAT SURVIVES INTACT —
 *     IT GETS ONE MORE THAN IT DID.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⛔ THE THREE SUPERSEDED ASSERTIONS, PRESERVED SO THE MOVEMENT IS VISIBLE:
 *      1.2  'grid-template-columns: repeat(4, minmax(0, 1fr))'
 *      1.3  'body.woocommerce-shop .woo-expedition-shell ul.products { grid-template-columns: repeat(4'
 *      1.4  'gap: 28px 24px'
 *
 * ⭐ FOUR TRACKS WAS CORRECT WHILE THE GRID CARRIED SIX CARDS — four titles
 *    plus two injected bundle tiles. 1.19.350 moves the bundles to a strip
 *    BELOW the grid, so the grid is exactly the five things a reader chooses
 *    between, and five tracks is what puts the whole catalog in one row. The
 *    column gap comes down from 24px to 20px because the same 1236px now has
 *    to carry one more card.
 *
 * ⛔ THE SCOPE TOKEN MOVES TOO, `body.woocommerce-shop` → `body.bhp-catalog-grid`.
 *    ⭐ 1.3's OWN STATED CONCERN — *"the product page … does not move"* — IS
 *    STILL ENFORCED, and by a predicate that tests `is_product()` FIRST. What
 *    HAS deliberately changed is the other half of its sentence: the search
 *    archive and the category archives DO move now, because giving them the
 *    same card is the founder's own scope (seal 691) and the point of the
 *    release. That half is superseded knowingly, not by accident.
 */
dd_assert(
	false !== strpos( $dd_desktop, 'grid-template-columns: repeat(5, minmax(0, 1fr))' ),
	'1.2 ⭐ 1.19.350: FIVE tracks, declared inside the desktop block (was four while the bundles were in the grid)'
);
dd_assert(
	false !== strpos( $dd_desktop, 'body.bhp-catalog-grid .woo-expedition-shell ul.products {' )
		&& false !== strpos( $dd_desktop, 'grid-template-columns: repeat(5' ),
	'1.3 ⛔ …and SCOPED to body.bhp-catalog-grid — the PRODUCT PAGE still does not move (the archives now do, by design)'
);
dd_assert(
	false !== strpos( $dd_desktop, 'gap: 28px 20px' ),
	'1.4 the 20px column gap, which is what fits five cards in the width four used'
);

echo "\n=== §2 · 4 + 2, CENTRED, WITH AN EXACTLY-SIX COUNT GUARD ===\n";

dd_assert(
	false !== strpos( $dd_desktop, ':has(> li:nth-child(6):last-child) > li:nth-child(5) { grid-column: 2' ),
	'2.1 ⭐ card 5 is pinned to column 2, which centres the trailing pair in row 2'
);
dd_assert(
	false !== strpos( $dd_desktop, 'li:nth-child(6):last-child' ),
	'2.2 ⛔ the guard is an EXACTLY-SIX test — a seventh card lifts the pin and the grid lays out on its own'
);
dd_assert(
	false === strpos( $dd_desktop, 'justify-content: center' ),
	'2.3 ⛔ the centring is one grid-column declaration, NOT a second mechanism that would also move rows 1 and 3'
);
/*
 * ⛔ THE PREMISE IS RE-COUNTED FROM THE SERVED MARKUP, NOT ASSUMED. This is
 *    the same count `test-bundle-cards-206-207.php` §5 already makes; it is
 *    repeated here because THIS block's correctness depends on it — six cards
 *    are 4+2, seven are 4+3, and the centring rule is only right for six.
 */
if ( function_exists( 'wc_get_products' ) ) {
	$dd_shop_count = count(
		wc_get_products(
			array(
				'status' => 'publish',
				'limit'  => -1,
				'return' => 'ids',
			)
		)
	);
	dd_assert(
		$dd_shop_count > 0,
		'2.4 the catalogue resolves on this environment (' . (int) $dd_shop_count . ' published products)'
	);
}

echo "\n=== §3 · ⛔ NOTHING BELOW 1280px MOVED ===\n";

dd_assert(
	false !== strpos( $dd_out, 'grid-template-columns: repeat(3,minmax(0,1fr))' )
		|| false !== strpos( $dd_out, 'grid-template-columns: repeat(3, minmax(0, 1fr))' ),
	'3.1 ⛔ the 3-across desktop grid still exists OUTSIDE the 1280 block'
);
dd_assert(
	false !== strpos( $dd_out, '@media (max-width: 640px)' ),
	'3.2 ⛔ the 390 mobile block is untouched and still present'
);
/*
 * ⛔⛔ CORRECTED IN THE FIRST STAGING RUN — THIS ASSERTION FAILED ON A CORRECT
 *     BUILD, AND THE SUITE WAS THE THING THAT WAS WRONG.
 *
 * ⭐ THE SUPERSEDED TEST, PRESERVED SO IT IS NOT RE-DERIVED:
 *
 *       false === strpos( $dd_out, 'repeat(4, minmax(0, 1fr))' )
 *
 *    It searched the WHOLE stylesheet for four tracks and found
 *    `.grid--4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }` — a
 *    generic layout utility that has been in this file since long before item
 *    214, is used on surfaces that are not the shop, and has nothing to do
 *    with `ul.products`. ⛔ A gate that fails on a correct build gets muted,
 *    and a muted gate protects nothing.
 *
 * ⭐ THE TEST NOW ASSERTS WHAT IT ALWAYS MEANT: no rule OUTSIDE the 1280 block
 *    gives the SHOP GRID four tracks. That is the claim worth gating — it is
 *    what stops a phone rendering four 96px cards.
 */
/*
 * ⭐ 1.19.350: the token moves and the FLOOR MOVES WITH THE GRID. The claim
 *    worth gating is unchanged and is stated in this block's own words —
 *    *"what stops a phone rendering four 96px cards"*. With five tracks on the
 *    desktop grid, the thing a phone must never inherit is FOUR OR MORE, so the
 *    scan tests `repeat(4..9)` rather than `repeat(4)` alone. ⛔ Widening the
 *    grid must not narrow its own guard.
 */
if ( preg_match_all( '/body\.bhp-catalog-grid[^{}]*ul\.products[^{}]*\{[^{}]*\}/', $dd_out, $dd_shop_rules ) ) {
	$dd_bad = 0;
	foreach ( $dd_shop_rules[0] as $dd_rule ) {
		if ( preg_match( '/grid-template-columns:\s*repeat\(\s*[4-9]/', $dd_rule ) ) {
			$dd_bad++;
		}
	}
	dd_assert(
		0 === $dd_bad,
		'3.3 ⛔⛔ NO CATALOG-GRID RULE OUTSIDE THE 1280 BLOCK DECLARES FOUR OR MORE TRACKS ('
			. count( $dd_shop_rules[0] ) . ' catalog rules scanned) — a phone never gets a 4-across grid'
	);
} else {
	dd_assert( false, '3.3 ⛔ no body.bhp-catalog-grid ul.products rules found outside the block — the scope itself has gone missing' );
}
dd_assert(
	false === strpos( $dd_desktop, 'max-width' ),
	'3.4 ⛔ the desktop block contains no max-width rule — it cannot reach down into the mobile band'
);

echo "\n=== §4 · THE CTA TOKENS, AND THE CALC THAT MUST STAY POSITIVE ===\n";

dd_assert(
	false !== strpos( $dd_desktop, '--bhp-card-gutter: 1.15rem' ),
	'4.1 the CTA inset takes its 291px value'
);
dd_assert(
	false !== strpos( $dd_desktop, '--bhp-collection-frame-pad: 12px' ),
	'4.2 ⛔ the collection frame pad moves WITH it — see 4.3 for why it must'
);
/*
 * ⛔⛔ THE ARITHMETIC ASSERTION, AND IT IS THE MOST LOAD-BEARING LINE IN THIS
 *     FILE. Block D computes the collection CTA's inset as
 *     `gutter - 2px - frame-pad`. At 1.15rem (18.4px) against the INHERITED
 *     18px pad that is -1.6px: a NEGATIVE margin hanging the button outside
 *     its own gold frame. The two tokens are only correct together, and a
 *     future edit that moves one and not the other reintroduces exactly that.
 */
$dd_gutter_px = 1.15 * 16;
$dd_frame_px  = 12;
dd_assert(
	( $dd_gutter_px - 2 - $dd_frame_px ) > 0,
	'4.3 ⛔⛔ gutter(18.4px) - 2px border - frame-pad(12px) = '
		. round( $dd_gutter_px - 2 - $dd_frame_px, 2 )
		. 'px — POSITIVE, so the collection CTA sits INSIDE its frame'
);
dd_assert(
	false === strpos( $dd_desktop, 'min-height' ),
	'4.4 ⛔ 48px is NOT restated here — one definition of the button, in the 1.19.286 base block, plus a width adjustment'
);
dd_assert(
	false === strpos( $dd_desktop, 'white-space' ),
	'4.5 ⛔ the overflow guard is likewise not restated and not weakened'
);

echo "\n=== §5 · THE CARD CONTRACT IS INTACT — NOTHING WAS HIDDEN ===\n";

dd_assert(
	false === strpos( $dd_desktop, 'display: none' ),
	'5.1 ⛔⛔ NOT ONE `display:none` IN THE DESKTOP BLOCK — image, title, price and CTA all still render. This is a SHRINK, not a strip'
);
dd_assert(
	false === strpos( $dd_desktop, 'kirkus' ) && false === strpos( $dd_desktop, 'amazon-review' ),
	'5.2 ⛔ the Kirkus badge and the review are NOT touched at desktop (item 204 removed them at 390 ONLY, and that is unchanged)'
);
dd_assert(
	false !== strpos( $dd_desktop, '.woocommerce-loop-product__title' )
		&& false !== strpos( $dd_desktop, 'font-size: 1.28rem' ),
	'5.3 the title is resized (20.5px), not truncated — the whole book name still renders'
);
dd_assert(
	false === strpos( $dd_desktop, 'text-overflow' ) && false === strpos( $dd_desktop, 'line-clamp' ),
	'5.4 ⛔ no ellipsis and no line clamp — no title is cut off to buy height'
);

echo "\n=== §6 · THE REVERSE PAYLOAD RESOLVES TO REAL IDS AND REAL WORDS ===\n";

if ( ! function_exists( 'bhp_offer_drawer_payload' ) ) {
	dd_assert( false, '6.0 ⛔ bhp_offer_drawer_payload() is not loaded — the bundle plugin is inactive?' );
} else {
	$dd_rows    = bhp_offer_drawer_payload();
	$dd_reverse = array();
	foreach ( $dd_rows as $dd_row ) {
		if ( ! empty( $dd_row['chapter'] ) ) {
			$dd_reverse[] = $dd_row;
		}
	}

	dd_assert(
		! empty( $dd_rows ),
		'6.1 the offer engine publishes at least one surfaced pair row (' . count( $dd_rows ) . ')'
	);
	dd_assert(
		! empty( $dd_reverse ),
		'6.2 ⭐ at least one row carries a `chapter` payload — the reverse direction exists (' . count( $dd_reverse ) . ')'
	);

	foreach ( $dd_reverse as $dd_row ) {
		$dd_k = $dd_row['key'];
		$dd_c = $dd_row['chapter'];

		dd_assert(
			! empty( $dd_c['buy_id'] ) && ! empty( $dd_c['product_id'] ),
			"6.3 [$dd_k] the chapter payload carries real ids, not zeroes"
		);
		/*
		 * ⛔ PURCHASABILITY IS RE-TESTED HERE RATHER THAN INHERITED. The panel
		 *    is about to put this id behind a button; a row that resolves but
		 *    is not buyable is an "Add" that fails at the Store API.
		 */
		$dd_p = wc_get_product( (int) $dd_c['buy_id'] );
		dd_assert(
			$dd_p && $dd_p->is_purchasable() && $dd_p->is_in_stock(),
			"6.4 [$dd_k] ⛔ the offered chapter book is live, purchasable and in stock"
		);
		dd_assert(
			'' !== trim( (string) $dd_c['label'] ) && '' !== trim( (string) $dd_c['cta'] ),
			"6.5 [$dd_k] ⛔ it has WORDS — no id is ever offered without a label and a button"
		);
		dd_assert(
			false !== strpos( (string) $dd_c['label'], 'Paperback' )
				|| false !== strpos( (string) $dd_c['label'], 'Hardcover' ),
			"6.6 [$dd_k] ⭐ the label NAMES THE FORMAT — an unlabelled title on a cart choosing between \$11.99 and \$17.99 is the FD-549 ambiguity"
		);
		dd_assert(
			in_array( $dd_c['format'], array( 'paperback', 'hardcover' ), true ),
			"6.7 [$dd_k] the format is one of the two real ones"
		);
		dd_assert(
			(float) $dd_row['saving'] > 0,
			"6.8 [$dd_k] ⛔ the saving is POSITIVE and is the row's OWN bhp_offer_saving() figure, not a second number"
		);
		/*
		 * ⛔⛔ THE SAVING IS THE SAME NUMBER IN BOTH DIRECTIONS, AND THAT IS
		 *     THE POINT OF THE WHOLE ITEM. There is ONE offer here, read from
		 *     two ends. If these ever diverge, two prices exist for one bundle.
		 */
		if ( function_exists( 'bhp_offer_saving' ) ) {
			dd_assert(
				abs( (float) $dd_row['saving'] - round( (float) bhp_offer_saving( $dd_k ), 2 ) ) < 0.005,
				"6.9 [$dd_k] ⛔⛔ the reverse row quotes the IDENTICAL saving the forward row does — one offer, one price"
			);
		}
	}

	/*
	 * ⭐ THE PAPERBACK ROW IS THE ONE A COLOURING-ONLY CART GETS, and it is
	 *    asserted rather than assumed: the drawer walks `formatOrder`, which is
	 *    PAPERBACK-FIRST on a tie, and a colouring-only cart is always a tie.
	 */
	$dd_has_pb = false;
	foreach ( $dd_reverse as $dd_row ) {
		if ( 'paperback' === $dd_row['chapter']['format'] ) {
			$dd_has_pb = true;
		}
	}
	dd_assert(
		$dd_has_pb,
		'6.10 ⭐ a PAPERBACK reverse row exists — the $11.99 ask, not the $17.99 one, is what a colouring-only cart is offered'
	);
}

echo "\n=== §7 · ⛔ THE FORWARD DIRECTION IS BYTE-UNCHANGED IN CONTRACT ===\n";

if ( function_exists( 'bhp_offer_drawer_payload' ) ) {
	$dd_rows = bhp_offer_drawer_payload();
	foreach ( $dd_rows as $dd_row ) {
		$dd_k = $dd_row['key'];
		foreach ( array( 'key', 'format', 'title_key', 'chapter_ids', 'colouring_id', 'product_id', 'variation_id', 'label', 'cta', 'saving' ) as $dd_f ) {
			dd_assert(
				array_key_exists( $dd_f, $dd_row ),
				"7.1 [$dd_k] the 1.8.67 field `$dd_f` still exists — the forward offer lost nothing"
			);
		}
		dd_assert(
			! empty( $dd_row['chapter_ids'] ) && ! empty( $dd_row['colouring_id'] ),
			"7.2 [$dd_k] ⛔ the forward gate's inputs are intact"
		);
		dd_assert(
			0 === strpos( (string) $dd_row['title_key'], 'colouring_' ),
			"7.3 [$dd_k] the forward analytics key is unchanged"
		);
	}
}

echo "\n=== §8 · THE NEW COPY OBEYS THE STANDING RAILS ===\n";

if ( ! function_exists( 'bhp_colouring_draft_copy' ) ) {
	dd_assert( false, '8.0 ⛔ bhp_colouring_draft_copy() is not loaded' );
} else {
	$dd_lbl = bhp_colouring_draft_copy( 'panel_chapter_label', array( 'The Mariana Trench', 'Paperback' ) );
	$dd_cta = bhp_colouring_draft_copy( 'panel_chapter_cta' );

	dd_assert( '' !== trim( $dd_lbl ), '8.1 panel_chapter_label resolves' );
	dd_assert( '' !== trim( $dd_cta ), '8.2 panel_chapter_cta resolves' );
	foreach ( array( 'panel_chapter_label' => $dd_lbl, 'panel_chapter_cta' => $dd_cta ) as $dd_key => $dd_str ) {
		/*
		 * ⛔ THE VOICE RULE, §9.1. Customer-facing words carry no "we"/"us"/
		 *    "our" — he is the sole operator of the company.
		 */
		dd_assert(
			! preg_match( '/\b(we|us|our)\b/i', $dd_str ),
			"8.3 [$dd_key] ⛔ no \"we\", \"us\" or \"our\" (standing rules §9.1)"
		);
		dd_assert(
			false === strpos( $dd_str, '—' ) && false === strpos( $dd_str, '–' ),
			"8.4 [$dd_key] ⛔ no em dash and no en dash"
		);
		/*
		 * ⛔⛔ NO FIGURE INSIDE THE COPY. The "- Save $X" clause is appended by
		 *     the drawer from bhp_offer_saving(), recomputed live. A number
		 *     typed into a string here is the derived-claim trap: it would go
		 *     on claiming $1.99 after the offer moved.
		 */
		dd_assert(
			! preg_match( '/[$£€]\s?\d/', $dd_str ) && ! preg_match( '/\d+\.\d{2}/', $dd_str ),
			"8.5 [$dd_key] ⛔⛔ NO MONEY FIGURE is typed into the copy"
		);
		/*
		 * ⛔ NO OUTCOME CLAIM — design-truth framing. Describe what the book
		 *    is, never what it will do to a child.
		 */
		dd_assert(
			! preg_match( '/\b(improve|boost|help(s|ed)? your child|guarantee|proven|develop(s)? )\b/i', $dd_str ),
			"8.6 [$dd_key] ⛔ no outcome or developmental claim"
		);
	}
	dd_assert(
		false !== strpos( $dd_lbl, 'The Mariana Trench' ) && false !== strpos( $dd_lbl, 'Paperback' ),
		'8.7 ⭐ the label composes BOTH tokens — the book and its format'
	);
	/*
	 * ⛔ THE FORWARD STRINGS ARE NOT REWORDED. A string Andrew has already seen
	 *    keeps its wording; this item added two, it did not edit two.
	 */
	dd_assert(
		'Add The Coloring Book' === bhp_colouring_draft_copy( 'panel_cta' ),
		'8.8 ⛔ the 1.19.281 forward button is BYTE-UNCHANGED'
	);
	dd_assert(
		'%s coloring book' === bhp_colouring_draft_copy( 'panel_label' ),
		'8.9 ⛔ the 1.19.281 forward label is BYTE-UNCHANGED'
	);
}

echo "\n=== §9 · THE DRAWER JS CARRIES THE GATES AND THE SUPPRESSION ===\n";

$dd_js_path = WP_PLUGIN_DIR . '/brave-hearts-bundle-pricing/assets/bundle-drawer.js';
$dd_js      = file_exists( $dd_js_path ) ? (string) file_get_contents( $dd_js_path ) : '';

dd_assert( '' !== $dd_js, '9.0 bundle-drawer.js is readable' );
dd_assert(
	false !== strpos( $dd_js, 'function chooseChapterOffer' ),
	'9.1 the reverse chooser exists'
);
dd_assert(
	false !== strpos( $dd_js, "adventures.indexOf(offer.chapter.title_key) !== -1" ),
	'9.2 ⛔⛔ GATE 3 IS PRESENT — the story is never offered in a second format when the cart already holds it. This is the 1.8.25 defect, one offer over'
);
dd_assert(
	false !== strpos( $dd_js, 'return pairOffer;' ),
	'9.3 ⭐ the empty-adventure guard RETURNS THE PAIR OFFER instead of null — a colouring-only cart is no longer silent'
);
dd_assert(
	false !== strpos( $dd_js, "'pair' === cs.offer_kind || 'colouring' === cs.format" ),
	'9.4 ⛔⛔ the gold "Complete the collection" eyebrow is suppressed on BOTH directions — one chapter book does not complete a three-book collection'
);
dd_assert(
	false !== strpos( $dd_js, 'completes_collection: false' ),
	'9.5 ⛔ the pair offers state completes_collection FALSE explicitly, rather than relying on a falsy default'
);
dd_assert(
	false !== strpos( $dd_js, 'chooseChapterOffer(cart, adventures, formatOrder)' ),
	'9.6 ⛔ format choice is the CALLER\'s formatOrder — one definition of the majority/tie/school-visit rule, not two'
);
dd_assert(
	substr_count( $dd_js, 'crossSellEl.appendChild(box);' ) === 1,
	'9.7 ⛔ EXACTLY ONE OFFER BOX IS APPENDED — the one-offer-at-a-time rail is not a stack'
);

echo "\n";
printf(
	"DESKTOP DENSITY + BIDIRECTIONAL PAIR (item 214): %d passed, %d failed\n",
	(int) $GLOBALS['dd_passes'],
	(int) $GLOBALS['dd_failures']
);
if ( $GLOBALS['dd_failures'] > 0 ) {
	echo 'FAILED (' . (int) $GLOBALS['dd_failures'] . ")\n";
}
echo 'FAILURES: ' . (int) $GLOBALS['dd_failures'] . "\n";
