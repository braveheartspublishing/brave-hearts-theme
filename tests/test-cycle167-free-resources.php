<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * THE `/free-resources/` HUB SUITE — theme 1.19.301, 2026-08-27,
 * `CYCLE167-LD-FREE-RESOURCES-HUB`.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Run on STAGING (never production) via:
 *   wp eval-file wp-content/themes/brave-hearts-theme-deploy-explorer-expedition-guides/tests/test-cycle167-free-resources.php --user=1
 *
 * ---------------------------------------------------------------------------
 * ⭐ THE THREE THINGS THIS SUITE EXISTS TO CATCH, ALL OF WHICH ARE SILENT
 * ---------------------------------------------------------------------------
 *
 * ⛔⛔ ONE — A HALF-RENAMED NAV. The visible desktop label is a CSS
 *     pseudo-element (`.menu-item--educator-guides > a::after`) sitting on an
 *     anchor whose own text is `font-size: 0 !important`. Change the PHP title
 *     alone and every desktop visitor reads "Expedition Guides" on a link that
 *     goes to `/free-resources/`: no error, no warning, and nothing else in
 *     this repository would have failed. §1 asserts BOTH strings moved and that
 *     the old one survives nowhere but in a comment.
 *
 * ⛔⛔ TWO — A PARENT HUB THAT QUIETLY EATS THE TEACHER FUNNEL. This page links
 *     to `/teachers/` by the founder's own instruction (carrier item 300), and
 *     adjacency is how a magnet walks across a funnel boundary. §3 asserts the
 *     educator audience, the educator magnet, the teacher storage prefix, the
 *     teacher event prefix and the teacher thank-you path appear NOWHERE in the
 *     hub, and that `/teachers/` is still linked and still live.
 *
 * ⛔⛔ THREE — A DEAD DOWNLOAD BUTTON. Merry's §23 walk is explicit about the
 *     cost: *"a padded one is unrecoverable once a parent has clicked a dead
 *     promise."* §8 does not merely check that the shipped rows resolve; it
 *     INJECTS a row pointing at a file that does not exist and proves the guard
 *     drops it. A presence guard nobody has seen fail is not a guard.
 *
 * ---------------------------------------------------------------------------
 * ⛔ WHAT A PASS HERE DOES **NOT** PROVE — read before over-reading one.
 * ---------------------------------------------------------------------------
 * This is PHP, CSS and source level, plus several EXECUTED filters. It cannot
 * see layout, wrapping, tap targets, console cleanliness or where anything sits
 * on a phone. Those claims carry browser evidence at a stated `window.innerWidth`
 * in the handoff and are NOT inferred from a PASS below.
 *
 * ⛔ IT WRITES NOTHING. No option, no post, no product, no setting, no
 *    subscriber, and it leaves no filter registered.
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
$GLOBALS['bhp_fr_pass'] = 0;
$GLOBALS['bhp_fr_fail'] = 0;

function bhp_fr_ok( $label, $cond, $detail = '' ) {
	if ( $cond ) {
		$GLOBALS['bhp_fr_pass']++;
		echo "PASS  {$label}\n";
	} else {
		$GLOBALS['bhp_fr_fail']++;
		echo "FAIL  {$label}" . ( $detail ? '  -- ' . substr( (string) $detail, 0, 400 ) : '' ) . "\n";
	}
}

function bhp_fr_head( $title ) {
	echo "\n=== {$title} ===\n";
}

function bhp_fr_file( $rel ) {
	$path = get_template_directory() . '/' . ltrim( $rel, '/' );
	return file_exists( $path ) ? (string) file_get_contents( $path ) : '';
}

/**
 * A file's CODE with every comment removed.
 *
 * ⛔⛔ LOAD-BEARING, NOT TIDY. This suite scans for the RETIRED nav label, and
 *     every file it scans deliberately QUOTES that label in a comment in order
 *     to record what changed and why. A raw `strpos()` would match the
 *     EXPLANATION and report a defect that does not exist — and an author who
 *     hit that false positive would be tempted to delete the historical record
 *     to make a test go green, which is how a codebase loses the account of its
 *     own decisions. `token_get_all()` is the lexer PHP itself uses and can
 *     tell a comment from the same characters inside a string literal; a regex
 *     cannot.
 */
function bhp_fr_code_only( $rel ) {
	$src = bhp_fr_file( $rel );
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

/** CSS with its block comments stripped, for exactly the same reason. */
function bhp_fr_css_only( $rel ) {
	$src = bhp_fr_file( $rel );
	return '' === $src ? '' : (string) preg_replace( '!/\*.*?\*/!s', '', $src );
}

/** Every translatable literal in a file — i.e. its visitor-facing strings. */
function bhp_fr_visible_strings( $code ) {
	$strings = array();
	if ( preg_match_all( "/(?:esc_html_e|esc_attr_e|esc_html__|esc_attr__|__)\(\s*(['\"])(.*?)(?<!\\\\)\\1/s", $code, $m ) ) {
		$strings = $m[2];
	}
	return $strings;
}

/**
 * The shared copy rails, applied to any blob of customer-facing text.
 *
 * ⭐ ONE IMPLEMENTATION, TWO CALLERS — the template's own literals (§6) and the
 *    downloads registry's RENDERED strings (§6k). The registry copy lives in
 *    `functions.php`, so a template-only scan would have missed five cards
 *    worth of customer-facing sentences entirely.
 */
function bhp_fr_copy_rails( $prefix, $copy ) {
	bhp_fr_ok( "{$prefix} ⛔ VOICE §9.1 — no \"we\", \"us\" or \"our\"", 0 === preg_match( '/\b(we|us|our)\b/i', $copy ), $copy );
	bhp_fr_ok( "{$prefix} ⛔ no em dash", false === strpos( $copy, "\xE2\x80\x94" ) );
	bhp_fr_ok( "{$prefix} ⛔ never \"5 to 9\"", false === strpos( $copy, '5 to 9' ) );
	bhp_fr_ok( "{$prefix} ⛔ no review, rating, award, urgency or scarcity claim", 0 === preg_match( '/\b(rating|reviews?|stars?|awards?|best-?sell\w*|hurry|limited time|only \d+ left)\b/i', $copy ) );
	bhp_fr_ok( "{$prefix} ⛔ no outcome claim about a child", 0 === preg_match( '/\b(will (?:love|read|improve)|turns? your|makes? your child|guaranteed|proven)\b/i', $copy ) );
	bhp_fr_ok( "{$prefix} ⛔ no price literal", 0 === preg_match( '/\$\s?\d/', $copy ) );
	bhp_fr_ok( "{$prefix} ⛔ AMERICAN spelling — \"coloring\", never \"colouring\"", 0 === preg_match( '/colour/i', $copy ) );
	/* ⛔ NO "COMING SOON" PADDING. Merry's walk: a promise with no file behind it
	 *   is the dead click that costs a parent's trust. */
	bhp_fr_ok( "{$prefix} ⛔⛔ no \"coming soon\" and no unbacked promise of future files", 0 === preg_match( '/\b(coming soon|check back|more to come|stay tuned)\b/i', $copy ) );
}

$tpl      = 'page-free-resources.php';
$tpl_raw  = bhp_fr_file( $tpl );
$tpl_code = bhp_fr_code_only( $tpl );
$fn_code  = bhp_fr_code_only( 'functions.php' );
$css      = bhp_fr_css_only( 'style.css' );

/* ═══════════════════════════════════════════════════════════════════════════
 * §0 · PRECONDITIONS — refuse to run rather than produce a false PASS.
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_fr_head( '§0 PRECONDITIONS' );

bhp_fr_ok(
	'§0.1 theme version is 1.19.301 or later',
	version_compare( (string) wp_get_theme()->get( 'Version' ), '1.19.301', '>=' ),
	'got ' . wp_get_theme()->get( 'Version' )
);
bhp_fr_ok( '§0.2 the hub template exists and is readable', '' !== $tpl_code );
bhp_fr_ok( '§0.3 style.css is readable', '' !== $css );
bhp_fr_ok(
	'§0.4 the PHP comment stripper actually strips (it is what keeps §1 honest)',
	'' !== $fn_code && false === strpos( $fn_code, 'THE ONE THAT WOULD HAVE SHIPPED THE BUG' )
);
bhp_fr_ok(
	'§0.5 the CSS comment stripper actually strips',
	'' !== $css && false === strpos( $css, 'SUPERSEDED VALUE, PRESERVED VERBATIM' )
);

/* ═══════════════════════════════════════════════════════════════════════════
 * §1 · THE NAV DOOR — label, target, and the CSS half nobody remembers.
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_fr_head( '§1 THE NAV DOOR' );

bhp_fr_ok(
	'§1a the retarget filter is registered on wp_nav_menu_objects',
	false !== has_filter( 'wp_nav_menu_objects', 'bhp_free_resources_nav_item' )
);
bhp_fr_ok(
	'§1b ⛔ it runs at priority 26 — AFTER canonicalize(10), which sets the class it reads',
	26 === has_filter( 'wp_nav_menu_objects', 'bhp_free_resources_nav_item' ),
	'got ' . var_export( has_filter( 'wp_nav_menu_objects', 'bhp_free_resources_nav_item' ), true )
);

/*
 * ⭐⭐ §1c AND §1d WERE THE ASSERTIONS THIS FILE WAS ORIGINALLY WRITTEN FOR, AND
 *     THEY ARE REWRITTEN AT 1.19.303 BECAUSE THE FOUNDER SUPERSEDED THE THING
 *     THEY PINNED — not because they failed.
 *
 *     Their 1.19.301 form asserted that the desktop label was a CSS
 *     pseudo-element:
 *         §1c  false !== strpos( $css, "content: 'Free Resources'" )
 *         §1d  false === strpos( $css, "content: 'Expedition Guides'" )
 *
 * ⭐ CARRIER ITEM 311 retired that mechanism. The founder asked for FREE over
 *    RESOURCES "just like Adventure Books" with fonts, spacing and style
 *    matching the rest of the bar — and the pseudo-element could not match it,
 *    because it sat outside `@container (max-width: 1236px)`'s
 *    `.site-nav a { font-size: 10.5px; letter-spacing: .14em }` and rendered
 *    with NO tracking at all (measured live on staging at 1.19.302).
 *
 * ⭐⭐ SO §1c IS INVERTED, AND THE INVERSION IS THE POINT: there must now be NO
 *     `content:` label on that selector, because the ONE remaining label site
 *     is PHP. §1d is KEPT EXACTLY AS IT WAS — "Expedition Guides" must still
 *     appear in no rule anywhere, and that assertion is not weakened by the
 *     mechanism changing underneath it.
 */
bhp_fr_ok(
	'§1c ⭐⭐ the pseudo-element label is RETIRED — no `content:` label survives on the nav selector (item 311)',
	false === strpos( $css, "content: 'Free Resources'" )
		&& false !== strpos( $css, '.menu-item--educator-guides > a::after' )
		&& false !== strpos( $css, 'content: none' )
);
bhp_fr_ok(
	'§1c2 ⛔⛔ …and `font-size: 0` is gone, so the PHP title is what a desktop visitor reads',
	false === strpos( $css, "font-size: 0 !important;\n    display: flex" )
);
bhp_fr_ok(
	'§1d ⛔⛔ and NO stylesheet RULE still prints "Expedition Guides"',
	false === strpos( $css, "content: 'Expedition Guides'" )
);
bhp_fr_ok(
	'§1e the PHP title is the founder\'s label',
	false !== strpos( $fn_code, "__('Free Resources', 'brave-hearts')" )
);
/*
 * ⭐⭐ §1e2 TO §1e5 — THE NAV NOW SHARES ADVENTURE BOOKS' MECHANISM, NOT A COPY
 *     OF ITS LOOK. Carrier item 311: "just like Adventure Books". These four
 *     assert SAMENESS rather than resemblance, because a duplicated rule set
 *     satisfies a screenshot and drifts the first time somebody edits one copy.
 */
bhp_fr_ok(
	'§1e2 ⭐ the label is emitted as the SAME two `.site-nav__label-line` spans Adventure Books uses',
	false !== strpos( $fn_code, "'<span class=\"site-nav__label-line\">' . esc_html__('Free', 'brave-hearts') . '</span><span class=\"site-nav__label-line\">' . esc_html__('Resources', 'brave-hearts') . '</span>'" )
);
bhp_fr_ok(
	'§1e3 ⭐⭐ the stacking rule is SHARED, not duplicated — one selector list carries both items',
	false !== strpos( $css, ".site-nav .menu-item--adventure-books > a,\n.site-nav .menu-item--free-resources > a {" )
);
bhp_fr_ok(
	'§1e4 ⭐ and both un-stack together in the mobile dropdown (Adventure Books\' own documented behaviour)',
	false !== strpos( $css, ".site-nav .menu-item--adventure-books > a,\n  .site-nav .menu-item--free-resources > a { flex-direction: row; gap: .35em; }" )
);
bhp_fr_ok(
	'§1e5 ⛔ the accessible name is restored — two block spans must not read as two links',
	function_exists( 'bhp_free_resources_nav_aria_label' )
		&& false !== has_filter( 'nav_menu_link_attributes', 'bhp_free_resources_nav_aria_label' )
);
/*
 * ⛔ §1e6 EXECUTES the aria-label filter rather than reading that it is hooked.
 *    A registered filter is not a working one — the rule this suite already
 *    applies at §2.
 */
$aria_item          = new stdClass();
$aria_item->classes = array( 'menu-item--educator-guides', 'menu-item--free-resources' );
$aria_out           = bhp_free_resources_nav_aria_label( array(), $aria_item );
bhp_fr_ok(
	'§1e6 ⭐ …and executing it yields aria-label="Free Resources"',
	isset( $aria_out['aria-label'] ) && 'Free Resources' === $aria_out['aria-label'],
	isset( $aria_out['aria-label'] ) ? $aria_out['aria-label'] : 'absent'
);
bhp_fr_ok(
	'§1f ⛔ the PRIMARY-nav fallback agrees with the live nav (no two-label site)',
	false !== strpos( $fn_code, "__('Free Resources', 'brave-hearts')    => home_url('/free-resources/')" )
);
/*
 * ⭐⭐ §1f2 PINS A DELIBERATE NON-CHANGE, AND THE FIRST DRAFT OF THIS SUITE GOT
 *     IT WRONG — recorded because the wrong version is the one a future author
 *     will be tempted to write again.
 *
 * ⛔ The naive assertion is "the old pair appears NOWHERE in functions.php".
 *    That FAILED, correctly, and the code was right: `bhp_footer_fallback_menu()`
 *    STILL carries `'Expedition Guides' => /teachers/` ON PURPOSE. `footer.php`
 *    stopped calling that function at 1.19.269 and the `footer` menu location is
 *    not rendered at all, so editing it would be churn with a failing test
 *    attached — and its own suite pins its current contents.
 *
 * ⭐ SO THE HONEST ASSERTION IS A COUNT, NOT AN ABSENCE: exactly ONE active
 *    occurrence survives, and it is the footer's. If a second ever appears, the
 *    primary fallback has been reverted and the site has two labels again.
 */
bhp_fr_ok(
	'§1f2 ⭐ exactly ONE active "Expedition Guides => /teachers/" pair remains, and it is the unrendered FOOTER fallback',
	1 === substr_count( $fn_code, "__('Expedition Guides', 'brave-hearts') => home_url('/teachers/')" ),
	(string) substr_count( $fn_code, "__('Expedition Guides', 'brave-hearts') => home_url('/teachers/')" )
);
bhp_fr_ok(
	'§1g the filter is location-guarded to the primary menu',
	false !== strpos( $fn_code, "'primary' !== \$args->theme_location" )
);

/*
 * ⛔ THE NAV IS THEME CODE, NOT A MENU RECORD — asserted against the LIVE
 *    database rather than believed. If this ever fails, somebody has edited the
 *    stored menu and the deploy packet's "there is no wp-admin step" line has
 *    become wrong.
 */
$stored_titles = array();
$loc           = get_nav_menu_locations();
if ( ! empty( $loc['primary'] ) ) {
	foreach ( (array) wp_get_nav_menu_items( $loc['primary'] ) as $mi ) {
		$stored_titles[] = $mi->title;
	}
}
bhp_fr_ok(
	'§1h ⭐ the STORED menu carries neither label — so the nav ships in the theme ZIP',
	! in_array( 'Free Resources', $stored_titles, true )
		&& ! in_array( 'Expedition Guides', $stored_titles, true ),
	'stored: ' . implode( ' | ', $stored_titles )
);

/* ═══════════════════════════════════════════════════════════════════════════
 * §2 · THE RETARGET ACTUALLY HAPPENS — the filter is EXECUTED, not read.
 *
 * ⛔ A REGISTERED FILTER IS NOT A WORKING ONE. This builds a menu object of the
 *    exact shape priority 10 produces and runs the real callback over it.
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_fr_head( '§2 THE RETARGET, EXECUTED' );

$fake          = new stdClass();
$fake->ID      = 0;
$fake->url     = home_url( '/teachers/' );
$fake->title   = 'Expedition Guides';
$fake->classes = array( 'menu-item--educator-guides' );
$fake->current = false;

$primary_args                 = new stdClass();
$primary_args->theme_location = 'primary';

$out = bhp_free_resources_nav_item( array( $fake ), $primary_args );
$got = is_array( $out ) && isset( $out[0] ) ? $out[0] : null;

bhp_fr_ok( '§2a the filter returns the item', null !== $got );
/*
 * ⭐ §2b IS REWRITTEN AT 1.19.303 (carrier item 311). Its 1.19.301 form was
 *    `'Free Resources' === $got->title`, which stopped being true the moment
 *    the label became two spans. The assertion is STRENGTHENED rather than
 *    relaxed: it pins the exact span markup, AND that the two words are still
 *    the founder's two words in his order.
 */
bhp_fr_ok(
	'§2b ⭐ its label is now the two stacked spans, FREE over RESOURCES',
	$got && '<span class="site-nav__label-line">Free</span><span class="site-nav__label-line">Resources</span>' === $got->title,
	$got ? $got->title : ''
);
/*
 * ⚠ §2b2 WAS WRONG ON ITS FIRST RUN AND THE BUG WAS IN THE TEST, NOT THE CODE —
 *   recorded because it is a trap anyone pinning a two-span label will hit.
 *   It asserted `'Free Resources' === wp_strip_all_tags( $title )`. Stripping
 *   tags from two ADJACENT spans yields "FreeResources" with NO space, because
 *   the space between the words is a LINE BREAK produced by CSS, not a
 *   character in the markup.
 * ⭐ THAT IS THE ADVENTURE BOOKS PATTERN BEHAVING EXACTLY AS IT ALREADY DOES —
 *   its own title strips to "AdventureBooks", which is why that item has
 *   carried an explicit `aria-label` since 2026-07-16 and why this one now does
 *   too (§1e5/§1e6). The accessible name is the aria-label; the spans are
 *   presentation. Matching Adventure Books means inheriting this property, and
 *   the mitigation came with it.
 * ⛔ SO THE ASSERTION IS REWRITTEN TO PIN WHAT ACTUALLY MATTERS: the two words,
 *   in the founder's order, each in its own line span — without depending on
 *   whitespace that was never there.
 */
bhp_fr_ok(
	'§2b2 ⛔ …and the visible words are exactly "Free" then "Resources", in that order, one line span each',
	$got && 1 === preg_match(
		'~^<span class="site-nav__label-line">Free</span><span class="site-nav__label-line">Resources</span>$~',
		(string) $got->title
	),
	$got ? wp_strip_all_tags( $got->title ) : ''
);
bhp_fr_ok(
	'§2b3 ⚠ the tags-stripped form is "FreeResources" — documented, and why the aria-label is mandatory',
	$got && 'FreeResources' === wp_strip_all_tags( (string) $got->title )
		&& 'Free Resources' === ( bhp_free_resources_nav_aria_label( array(), $got )['aria-label'] ?? '' ),
	$got ? wp_strip_all_tags( (string) $got->title ) : ''
);
bhp_fr_ok(
	'§2c ⭐ its target is now /free-resources/',
	$got && '/free-resources' === untrailingslashit( (string) wp_parse_url( $got->url, PHP_URL_PATH ) ),
	$got ? $got->url : ''
);
bhp_fr_ok( '§2d it carries the new hook class', $got && in_array( 'menu-item--free-resources', (array) $got->classes, true ) );
bhp_fr_ok( '§2e ⛔ it KEEPS the class the CSS label keys on', $got && in_array( 'menu-item--educator-guides', (array) $got->classes, true ) );

/*
 * ⛔ A non-primary menu must come back untouched.
 *
 * ⚠ THE FIRST DRAFT OF THIS ASSERTION FAILED, AND THE BUG WAS IN THE TEST —
 *   recorded because it is a trap anyone writing the next filter suite will hit.
 *   It reused `clone $fake`, but `$fake` had ALREADY BEEN MUTATED by §2a above:
 *   PHP passes objects by handle, so the filter rewrote the original object, not
 *   a copy, and the "clone" was a clone of the retargeted item. ⭐ A FRESH
 *   OBJECT IS BUILT HERE so the assertion tests the filter rather than the
 *   leftovers of the previous assertion.
 */
$virgin          = new stdClass();
$virgin->ID      = 0;
$virgin->url     = home_url( '/teachers/' );
$virgin->title   = 'Expedition Guides';
$virgin->classes = array( 'menu-item--educator-guides' );
$virgin->current = false;

$footer_args                 = new stdClass();
$footer_args->theme_location = 'footer';
$out2                        = bhp_free_resources_nav_item( array( $virgin ), $footer_args );
bhp_fr_ok(
	'§2f ⛔ a NON-primary menu is left completely alone',
	isset( $out2[0] ) && 'Expedition Guides' === $out2[0]->title && $out2[0]->url === home_url( '/teachers/' ),
	isset( $out2[0] ) ? $out2[0]->title . ' | ' . $out2[0]->url : 'no item'
);

/* ⛔ Idempotence: running it twice must not accumulate classes or change the
 *   result. Filters are re-entered more often than anyone expects. */
$twice = bhp_free_resources_nav_item( $out, $primary_args );
bhp_fr_ok(
	'§2g ⛔ running it a second time changes nothing',
	isset( $twice[0] ) && '<span class="site-nav__label-line">Free</span><span class="site-nav__label-line">Resources</span>' === $twice[0]->title
		&& count( $twice[0]->classes ) === count( array_unique( $twice[0]->classes ) )
);
/*
 * ⛔⛔ §2g2 — THE NESTING TRAP THE SPAN LABEL CREATES, AND IT IS WHY §2g MATTERS
 *     MORE AT 1.19.303 THAN IT DID AT 1.19.301. When the title was a plain
 *     string, re-entering the filter rewrote it harmlessly. Now the filter
 *     writes MARKUP, so a careless implementation that appended rather than
 *     assigned would nest spans inside spans on every re-entry — invisible in
 *     one render, unbounded across a page that renders the menu twice.
 */
bhp_fr_ok(
	'§2g2 ⛔⛔ running it twice does NOT nest the label spans',
	isset( $twice[0] ) && 2 === substr_count( (string) $twice[0]->title, '<span' ),
	isset( $twice[0] ) ? substr_count( (string) $twice[0]->title, '<span' ) . ' spans' : 'no item'
);

/* ═══════════════════════════════════════════════════════════════════════════
 * §3 · ⛔⛔ FUNNEL ISOLATION — the rail most at risk on this page.
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_fr_head( '§3 FUNNEL ISOLATION — PARENT HUB, TEACHER LINK, NO CROSSING' );

foreach ( array(
	'educators'                 => 'the educator audience',
	'teacher_adventure_toolkit' => 'the educator lead magnet',
	'bhp_mariana_popup'         => 'the teacher storage prefix',
	'teacher_popup'             => 'the teacher event prefix',
	'mariana-guide-thank-you'   => 'the teacher thank-you path',
	'teacher_resources'         => 'the teacher capture context',
) as $needle => $what ) {
	bhp_fr_ok(
		"§3a ⛔ the hub contains {$what} NOWHERE (\"{$needle}\")",
		false === strpos( $tpl_code, $needle )
	);
}

bhp_fr_ok(
	'§3b ⭐ the hub declares the PARENT audience and the PARENT magnet',
	false !== strpos( $tpl_code, 'parents_families' )
		&& false !== strpos( $tpl_code, 'reluctant_reader_adventure_kit' )
);
bhp_fr_ok(
	'§3c ⭐ /teachers/ is still LINKED from the hub (the founder\'s own condition, item 300)',
	false !== strpos( $tpl_code, "home_url('/teachers/')" )
);
bhp_fr_ok(
	'§3d ⭐ and /teachers/ is still a live, PUBLISHED page — it lost a door, not its page',
	( $t = get_page_by_path( 'teachers' ) ) && 'publish' === $t->post_status
);
bhp_fr_ok(
	'§3e ⛔ the hub renders exactly ONE signup form of its own',
	1 === substr_count( $tpl_code, 'template-parts/acquisition/signup-form' ),
	(string) substr_count( $tpl_code, 'template-parts/acquisition/signup-form' )
);
/*
 * ⚠ SOURCE-LEVEL, AND LABELLED AS SUCH. `bhp_should_show_footer_capture()`
 *   depends on the main query, which this suite does not have. What is asserted
 *   is that the hub template is NOT in that function's exclusion list — which is
 *   the thing an author could accidentally change. That the band actually
 *   renders is a BROWSER claim and carries browser evidence in the handoff.
 */
bhp_fr_ok(
	'§3f ⭐ the hub is NOT in the footer-capture exclusion list (so the offer repeats at the foot)',
	false === strpos( $fn_code, "'page-free-resources.php',\n        'page-adventure-kit-thank-you.php'" )
		&& false === strpos( $fn_code, "'page-free-resources.php'," )
);

/* ═══════════════════════════════════════════════════════════════════════════
 * §4 · ⛔⛔ NO NEW MAILCHIMP TAG IS MINTED — the filter chain is EXECUTED.
 *
 * ⭐⭐ THE MOST LOAD-BEARING SECTION IN THIS FILE, and the one most likely to be
 *     broken by somebody doing the right thing for the wrong reason. A new tag
 *     string in Andrew's LIVE audience splits that surface's segment in two,
 *     silently, with no error anywhere. The hub's `free_resources_hub` context
 *     is an ANALYTICS identity; no tag callback matches it, so it must fall
 *     through to the existing base map. ⛔ Asserted by RUNNING the filters, not
 *     by reading them.
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_fr_head( '§4 THE PLUMBING — NO NEW TAG' );

$tags = apply_filters(
	'bhp_mailchimp_signup_tags',
	array(),
	'free_resources_hub',
	'parents_families',
	'reluctant_reader_adventure_kit',
	home_url( '/free-resources/' )
);

bhp_fr_ok( '§4a the hub context resolves to tags at all', is_array( $tags ) && count( $tags ) > 0, print_r( $tags, true ) );
bhp_fr_ok(
	'§4b ⭐ it resolves to the EXISTING trio, character for character',
	array( 'Reluctant Reader Adventure Kit', 'Audience: Parent/Grandparent', 'Source: Parent Landing Page' ) === array_values( (array) $tags ),
	print_r( $tags, true )
);
bhp_fr_ok(
	'§4c ⛔⛔ no tag names the hub, the page or a new source',
	0 === preg_match( '/free.?resources/i', implode( ' | ', (array) $tags ) ),
	print_r( $tags, true )
);
bhp_fr_ok(
	'§4d ⛔ and no educator tag appears on a parent submission',
	0 === preg_match( '/\b(Educator|Teacher|Toolkit)\b/i', implode( ' | ', (array) $tags ) ),
	print_r( $tags, true )
);

/* ═══════════════════════════════════════════════════════════════════════════
 * §5 · §26 — AFFILIATE LINKS. The hub carries none and must import none.
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_fr_head( '§5 §26 AFFILIATE SAFETY' );

foreach ( array( 'amzn.to', 'amazon.com/dp', 'tag=', 'bravehearts0e-20' ) as $needle ) {
	bhp_fr_ok(
		"§5 ⛔ the hub template contains no \"{$needle}\"",
		false === stripos( $tpl_code, $needle )
	);
}

/* ═══════════════════════════════════════════════════════════════════════════
 * §6 · THE COPY RAILS — the template's own strings AND the registry's.
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_fr_head( '§6 COPY RAILS' );

$tpl_strings = bhp_fr_visible_strings( $tpl_code );
$tpl_copy    = implode( ' ', $tpl_strings );

bhp_fr_ok( '§6a there are visible strings to check', count( $tpl_strings ) > 10, (string) count( $tpl_strings ) );
bhp_fr_copy_rails( '§6 template', $tpl_copy );
bhp_fr_ok( '§6c ⭐ the I-voice is actually present', 1 === preg_match( '/\b(I|my)\b/', $tpl_copy ) );
bhp_fr_ok( '§6e ⭐ the age band 6 to 9 is stated on the page', false !== strpos( $tpl_copy, '6 to 9' ) );

/*
 * ⭐ §6k — THE DOWNLOAD CARDS' OWN COPY, which lives in `functions.php` and
 *    which a template-only scan would have missed entirely. These are the five
 *    sentences a parent actually reads before tapping a file.
 */
$dl_rows = function_exists( 'bhp_free_resources_downloads' ) ? bhp_free_resources_downloads() : array();
$dl_copy = '';
foreach ( (array) $dl_rows as $r ) {
	$dl_copy .= ' ' . ( $r['title'] ?? '' ) . ' ' . ( $r['description'] ?? '' ) . ' ' . ( $r['cta'] ?? '' ) . ' ' . ( $r['meta'] ?? '' ) . ' ' . ( $r['alt_label'] ?? '' );
}
bhp_fr_ok( '§6k there is registry copy to check', strlen( trim( $dl_copy ) ) > 50 );
bhp_fr_copy_rails( '§6k registry', $dl_copy );

/* ⛔ THE COLORING CARD MUST SAY THE FULL BOOK IS PAID. Three free pages out of
 *   a paid book, described without that fact, is a true sentence assembled into
 *   a false impression — and Merry's walk flagged exactly this trap. */
bhp_fr_ok(
	'§6l ⛔⛔ the coloring card states that the full book is a paid product',
	false === strpos( $dl_copy, 'coloring book' ) || false !== stripos( $dl_copy, 'paid' )
);

/* ═══════════════════════════════════════════════════════════════════════════
 * §7 · THE JUMP BAR — every anchor lands somewhere that exists.
 *
 * ⛔ A jump bar pointing at a section that does not render is a dead link the
 *    reader blames on us, and it is invisible to every other test here.
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_fr_head( '§7 THE JUMP BAR ANCHORS RESOLVE' );

preg_match_all( '/href="#([a-z0-9-]+)"/i', $tpl_raw, $hrefs );
preg_match_all( '/\bid="([a-z0-9-]+)"/i', $tpl_raw, $ids );
$targets = array_values( array_unique( $hrefs[1] ) );
$present = array_values( array_unique( $ids[1] ) );

bhp_fr_ok( '§7a the jump bar has four anchors', count( $targets ) >= 4, implode( ',', $targets ) );
foreach ( $targets as $t ) {
	bhp_fr_ok( "§7b anchor #{$t} has a matching id in this template", in_array( $t, $present, true ) );
}

/* ═══════════════════════════════════════════════════════════════════════════
 * §8 · THE DOWNLOADS REGISTRY — presence-guarded, never a dead button.
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_fr_head( '§8 THE UNGATED DOWNLOADS' );

bhp_fr_ok( '§8a the registry function exists and returns an array', is_array( $dl_rows ) );
bhp_fr_ok( '§8a2 ⭐ it ships real files (the site had ZERO ungated PDFs this morning)', is_array( $dl_rows ) && count( $dl_rows ) >= 1, (string) count( (array) $dl_rows ) );

foreach ( (array) $dl_rows as $i => $row ) {
	$rel = (string) ( $row['file'] ?? '' );
	bhp_fr_ok( "§8b row {$i} names a file that is REALLY on disk", '' !== $rel && file_exists( get_theme_file_path( $rel ) ), $rel );
	bhp_fr_ok( "§8c row {$i} resolves to an http(s) URL", ! empty( $row['url'] ) && 0 === strpos( (string) $row['url'], 'http' ), (string) ( $row['url'] ?? '' ) );
	bhp_fr_ok( "§8d row {$i} has a title, a description, a CTA and a key", ! empty( $row['title'] ) && ! empty( $row['description'] ) && ! empty( $row['cta'] ) && ! empty( $row['key'] ) );
	/* ⛔ The size is COMPUTED, so it can never disagree with the file. */
	bhp_fr_ok( "§8e row {$i} meta names the real byte size", ! empty( $row['meta'] ) && false !== strpos( (string) $row['meta'], size_format( (int) filesize( get_theme_file_path( $rel ) ) ) ), (string) ( $row['meta'] ?? '' ) );
	if ( ! empty( $row['alt_label'] ) ) {
		bhp_fr_ok( "§8f row {$i} secondary file also exists", ! empty( $row['alt_url'] ) && file_exists( get_theme_file_path( (string) $row['alt_file'] ) ) );
	}
}

/*
 * ⛔⛔ THE GUARD ITSELF IS TESTED, NOT ASSUMED. Without this, §8b is circular:
 *     it only ever sees rows that already passed the guard. Injecting a ghost
 *     row is the only way to prove the guard would actually drop one.
 */
add_filter( 'bhp_free_resources_downloads', 'bhp_fr_inject_ghost' );
function bhp_fr_inject_ghost( $rows ) {
	$rows[] = array(
		'key'         => 'ghost',
		'title'       => 'Ghost',
		'description' => 'A file that does not exist.',
		'file'        => 'assets/downloads/__does-not-exist__.pdf',
		'cta'         => 'Open nothing',
	);
	return $rows;
}
$with_ghost = bhp_free_resources_downloads();
remove_filter( 'bhp_free_resources_downloads', 'bhp_fr_inject_ghost' );

bhp_fr_ok(
	'§8g ⛔⛔ a registry row whose file is missing is DROPPED, not rendered',
	0 === count( array_filter( (array) $with_ghost, static function ( $r ) {
		return isset( $r['key'] ) && 'ghost' === $r['key'];
	} ) )
);
bhp_fr_ok( '§8h the ghost-injection test left no filter registered', false === has_filter( 'bhp_free_resources_downloads', 'bhp_fr_inject_ghost' ) );

/* ═══════════════════════════════════════════════════════════════════════════
 * §8i · ⭐⭐ 1.19.303 — THE PAGE-ONE PREVIEW ON EVERY CARD (carrier item 311)
 * ═══════════════════════════════════════════════════════════════════════════
 * "I think there should be a picture of each one above the box description as
 *  well. So the audience can see what they are getting".
 *
 * ⛔ THE LAST CLAUSE IS THE TESTABLE ONE, and §8i5 is the assertion that
 *    actually enforces it: the preview must be derived from the PDF's OWN
 *    basename, so a card can never show a picture of a different resource. A
 *    suite that only checked "an image is present" would pass a set of pretty
 *    stock photographs, which is the exact thing he did not ask for.
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_fr_head( '§8i THE CARD PREVIEWS (item 311)' );

$preview_count = 0;
foreach ( (array) $dl_rows as $i => $row ) {
	$rel  = (string) ( $row['file'] ?? '' );
	$stem = preg_replace( '/\.pdf$/i', '', basename( $rel ) );

	bhp_fr_ok( "§8i1 row {$i} carries a resolved preview", ! empty( $row['preview'] ), $stem );
	if ( empty( $row['preview'] ) ) {
		continue;
	}
	++$preview_count;
	$p = $row['preview'];

	bhp_fr_ok(
		"§8i2 row {$i} ships BOTH derivatives, really on disk",
		file_exists( get_theme_file_path( 'assets/images/free-resources/' . $stem . '-preview.webp' ) )
			&& file_exists( get_theme_file_path( 'assets/images/free-resources/' . $stem . '-preview.jpg' ) )
	);
	/* ⛔⛔ THE CACHE-BUST. 1.19.299 proved by measurement that a fixed filename
	 *     under `max-age=31536000` never reaches a returning visitor again. */
	bhp_fr_ok(
		"§8i3 row {$i} ⛔⛔ both URLs carry ?ver= (the 1.19.299 lesson)",
		false !== strpos( (string) $p['webp'], 'ver=' ) && false !== strpos( (string) $p['jpg'], 'ver=' ),
		(string) $p['webp']
	);
	bhp_fr_ok(
		"§8i4 row {$i} ⛔ intrinsic width/height are REAL (read from the file, so no CLS)",
		(int) $p['width'] > 0 && (int) $p['height'] > 0
			&& array( (int) $p['width'], (int) $p['height'] ) === array_slice( (array) @getimagesize( get_theme_file_path( 'assets/images/free-resources/' . $stem . '-preview.jpg' ) ), 0, 2 ),
		$p['width'] . 'x' . $p['height']
	);
	/* ⭐⭐ THE HONESTY ASSERTION. */
	bhp_fr_ok(
		"§8i5 ⭐⭐ row {$i} preview is DERIVED FROM THIS RESOURCE'S OWN PDF, not a stand-in",
		false !== strpos( (string) $p['jpg'], $stem . '-preview.jpg' )
	);
	bhp_fr_ok(
		"§8i6 row {$i} ⛔ has real alt text, and it is not just the title repeated",
		'' !== trim( (string) $p['alt'] ) && strlen( (string) $p['alt'] ) > 40
			&& strtolower( trim( (string) $p['alt'] ) ) !== strtolower( trim( (string) $row['title'] ) ),
		(string) $p['alt']
	);
	/* ⛔ Alt text is customer-facing copy: every standing rail applies to it. */
	bhp_fr_ok(
		"§8i7 row {$i} ⛔ alt text carries no we/us/our and no em dash (§9.1)",
		0 === preg_match( '/\b(we|us|our)\b/i', (string) $p['alt'] )
			&& false === strpos( (string) $p['alt'], "\xE2\x80\x94" ),
		(string) $p['alt']
	);
}

bhp_fr_ok( '§8i8 ⭐ EVERY card got one — the founder said "each one"', $preview_count === count( (array) $dl_rows ), $preview_count . ' of ' . count( (array) $dl_rows ) );

/*
 * ⛔⛔ THE PREVIEW GUARD IS TESTED THE SAME WAY THE CARD GUARD IS — by injecting
 *     a row whose PDF exists but whose preview does not. Without this, §8i1 is
 *     circular in exactly the way §8g exists to prevent. ⭐ The row must SURVIVE
 *     (a missing preview costs a picture, never a download) with `preview`
 *     empty (so the template renders no broken image).
 */
add_filter( 'bhp_free_resources_downloads', 'bhp_fr_inject_previewless' );
function bhp_fr_inject_previewless( $rows ) {
	$rows[] = array(
		'key'         => 'previewless',
		'title'       => 'Previewless',
		'description' => 'A real PDF with no preview rendered for it.',
		/* A file that genuinely exists, so only the PREVIEW is missing. */
		'file'        => 'assets/downloads/stop-breathe-think-act-poster-ink-saver.pdf',
		'cta'         => 'Open it',
		'pages'       => 1,
		'preview_alt' => 'Unused, because no derivative exists for this stem.',
	);
	return $rows;
}
$with_previewless = bhp_free_resources_downloads();
remove_filter( 'bhp_free_resources_downloads', 'bhp_fr_inject_previewless' );

$ghost_row = array_values( array_filter( (array) $with_previewless, static function ( $r ) {
	return isset( $r['key'] ) && 'previewless' === $r['key'];
} ) );

bhp_fr_ok( '§8i9 ⭐ a row whose PREVIEW is missing still ships its download', 1 === count( $ghost_row ) && ! empty( $ghost_row[0]['url'] ) );
bhp_fr_ok( '§8i10 ⛔⛔ …and its preview is EMPTY, so the template renders no broken image', 1 === count( $ghost_row ) && empty( $ghost_row[0]['preview'] ) );
bhp_fr_ok( '§8i11 the previewless-injection test left no filter registered', false === has_filter( 'bhp_free_resources_downloads', 'bhp_fr_inject_previewless' ) );

/* The template actually renders it, and lazily. */
$tpl_303 = (string) @file_get_contents( get_theme_file_path( 'page-free-resources.php' ) );
bhp_fr_ok( '§8i12 ⭐ the template emits the preview <picture> ABOVE the description', false !== strpos( $tpl_303, 'free-resource-card__preview' )
	&& strpos( $tpl_303, 'free-resource-card__preview' ) < strpos( $tpl_303, 'free-resource-card__text' ) );
bhp_fr_ok( '§8i13 ⛔ it is lazy-loaded and async-decoded', false !== strpos( $tpl_303, 'loading="lazy" decoding="async"' ) );
bhp_fr_ok( '§8i14 ⛔ it is file_exists-guarded in the template too', false !== strpos( $tpl_303, "if (!empty(\$dl['preview']))" ) );
bhp_fr_ok( '§8i15 ⭐ the webp is offered first, with the jpg as the <img> fallback', false !== strpos( $tpl_303, 'type="image/webp"' ) );

/* ═══════════════════════════════════════════════════════════════════════════
 * §8j · ⭐⭐ 1.19.303 — "START WITH THE PRINTABLES" IS NO LONGER GREEN ON GREEN
 * ═══════════════════════════════════════════════════════════════════════════
 * Carrier item 311: "'Start with the printables' is green on a green background
 *  - should be gold or different color."
 *
 * ⭐ MEASURED, NOT AGREED WITH. Live on staging at 1.19.302 the link computed
 *    `rgb(23, 63, 47)` over the atmospheric hero, and the hero ground sampled
 *    at 147 points across the link's own box gave mean rgb(15,28,39):
 *        forest #173F2F   1.47:1  (worst-case sample 1.30:1)   ⛔ AA needs 4.5
 *        gold   #D9A45F   7.75:1  (worst-case sample 6.85:1)   ⭐ AA and AAA
 * ⚠ #D9A45F IS CORRECT *HERE* PRECISELY BECAUSE THE GROUND IS DARK. On a light
 *   ground this stylesheet requires `--color-gold-deep`, and that rule is not
 *   weakened by this one — §8j3 pins both halves so neither can be "tidied"
 *   into the other later.
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_fr_head( '§8j THE HERO LINK CONTRAST (item 311)' );

bhp_fr_ok( '§8j1 ⭐ the atmospheric-hero lead link is gold', false !== strpos( $css, '.interior-hero--atmospheric .text-lead a {' )
	&& false !== strpos( $css, 'color: var(--color-gold);' ) );
bhp_fr_ok( '§8j2 ⛔ it is the TOKEN, not a new hardcoded hex for the link colour', false === strpos( $css, ".text-lead a {\n  color: #D9A45F" ) );
bhp_fr_ok( '§8j3 ⛔⛔ the light-ground rule is UNTOUCHED — deep gold is still required on cream', false !== strpos( $css, '--color-gold-deep' ) );
bhp_fr_ok( '§8j4 ⭐ the hover state goes UP in luminance (the ground is dark)', false !== strpos( $css, '.interior-hero--atmospheric .text-lead a:focus-visible' ) );

/*
 * ⛔ THE CONTRAST IS RE-COMPUTED HERE RATHER THAN QUOTED. A number in a comment
 *    goes stale the moment somebody edits the colour; this recomputes WCAG 2.x
 *    from the two values the stylesheet actually ships against the measured
 *    ground, so changing either colour fails this assertion rather than the
 *    prose silently becoming wrong.
 */
$bhp_fr_lum = static function ( $r, $g, $b ) {
	$f = static function ( $v ) {
		$v /= 255;
		return $v <= 0.03928 ? $v / 12.92 : pow( ( $v + 0.055 ) / 1.055, 2.4 );
	};
	return 0.2126 * $f( $r ) + 0.7152 * $f( $g ) + 0.0722 * $f( $b );
};
/* The WORST-CASE (lightest) ground sampled across the link box on staging. */
$ground_l = $bhp_fr_lum( 25, 39, 49 );
$gold_l   = $bhp_fr_lum( 217, 164, 95 );
$forest_l = $bhp_fr_lum( 23, 63, 47 );
$ratio    = static function ( $a, $b ) {
	return ( max( $a, $b ) + 0.05 ) / ( min( $a, $b ) + 0.05 );
};
bhp_fr_ok(
	'§8j5 ⭐⭐ gold clears WCAG AA (4.5:1) against the WORST-CASE measured ground',
	$ratio( $gold_l, $ground_l ) >= 4.5,
	'gold ' . number_format( $ratio( $gold_l, $ground_l ), 2 ) . ':1'
);
bhp_fr_ok(
	'§8j6 ⛔ …and the colour it replaces provably did NOT (this is the defect, recorded)',
	$ratio( $forest_l, $ground_l ) < 4.5,
	'forest ' . number_format( $ratio( $forest_l, $ground_l ), 2 ) . ':1'
);

/* ═══════════════════════════════════════════════════════════════════════════
 * §9 · THE ARTICLE RAIL — real published posts, declared order preserved.
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_fr_head( '§9 THE ARTICLE RAIL' );

$arts = function_exists( 'bhp_free_resources_articles' ) ? bhp_free_resources_articles() : null;
bhp_fr_ok( '§9a the resolver exists and returns an array', is_array( $arts ) );
bhp_fr_ok( '§9b it resolves at least four real posts on this environment', is_array( $arts ) && count( $arts ) >= 4, is_array( $arts ) ? (string) count( $arts ) : 'n/a' );

foreach ( (array) $arts as $p ) {
	bhp_fr_ok( "§9c \"{$p->post_name}\" is a PUBLISHED post", 'publish' === $p->post_status && 'post' === $p->post_type );
	bhp_fr_ok( "§9d \"{$p->post_name}\" has a resolvable permalink", '' !== (string) get_permalink( $p ) );
}

/* ⛔ Declared order is preserved. `post_name__in` does not preserve it, and
 *   trusting the query's order would have shipped a rail sorted by date. */
$declared = array_values( array_filter( (array) bhp_free_resources_article_slugs(), static function ( $s ) {
	$p = get_page_by_path( $s, OBJECT, 'post' );
	return $p && 'publish' === $p->post_status;
} ) );
$actual = array_map( static function ( $p ) { return $p->post_name; }, (array) $arts );
bhp_fr_ok( '§9e ⭐ the declared order survives the query', $declared === $actual, implode( ',', $actual ) );

/*
 * ⭐ THE K3 ARTICLE. Merry's spec excluded it on a live 404 at a slug WITHOUT
 *    `/blog/`; this desk verified against production that post 638 is published
 *    at `/blog/what-to-read-after-magic-tree-house/`. Asserted conditionally so
 *    the suite is honest on an environment that genuinely lacks the post.
 */
bhp_fr_ok(
	'§9f ⭐ tonight\'s K3 article is in the rail wherever it exists',
	! get_page_by_path( 'what-to-read-after-magic-tree-house', OBJECT, 'post' )
		|| in_array( 'what-to-read-after-magic-tree-house', $actual, true )
);

/* ═══════════════════════════════════════════════════════════════════════════
 * §10 · THIS SUITE MUTATED NOTHING.
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_fr_head( '§10 NO SIDE EFFECTS' );

bhp_fr_ok( '§10.1 no downloads filter left registered', false === has_filter( 'bhp_free_resources_downloads', 'bhp_fr_inject_ghost' ) );
bhp_fr_ok( '§10.1b no previewless filter left registered either (1.19.303)', false === has_filter( 'bhp_free_resources_downloads', 'bhp_fr_inject_previewless' ) );
bhp_fr_ok( '§10.2 the retarget filter is still registered exactly once, at 26', 26 === has_filter( 'wp_nav_menu_objects', 'bhp_free_resources_nav_item' ) );
bhp_fr_ok(
	'§10.3 ⛔ the theme version on disk is unchanged by running tests',
	version_compare( (string) wp_get_theme()->get( 'Version' ), '1.19.301', '>=' )
);
bhp_fr_ok(
	'§10.4 ⭐ the aria-label filter is registered exactly once (1.19.303)',
	10 === has_filter( 'nav_menu_link_attributes', 'bhp_free_resources_nav_aria_label' )
);

echo "\n============================================================\n";
printf(
	"FREE RESOURCES HUB: %d passed, %d failed\n",
	(int) $GLOBALS['bhp_fr_pass'],
	(int) $GLOBALS['bhp_fr_fail']
);
echo "============================================================\n";
