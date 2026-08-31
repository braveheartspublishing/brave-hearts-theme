<?php
/**
 * CYCLE168-LD-AMITY-STOCK-SUPPRESSION (carrier item 359) — A VISIT WHOSE
 * PARENTS ARE BUYING OFF A SHELF THAT HAS NOT ARRIVED YET SEES NO QUANTITY.
 *
 * Run via WP-CLI, from the WordPress document root:
 *   wp eval-file wp-content/plugins/brave-hearts-bundle-pricing/tests/test-visit-stock-suppression.php --user=1
 *
 * ---------------------------------------------------------------------------
 * WHAT THIS SUITE IS FOR, IN DESCENDING ORDER OF WHAT IT COSTS IF IT BREAKS
 * ---------------------------------------------------------------------------
 *
 *   1. ⛔⛔ THE CONTROL PATH, AND IT IS TWO PATHS HERE, NOT ONE.
 *      · An ORDINARY shopper must be byte-identical to 1.8.74. §4.
 *      · A DALLAS HARRIS shopper must ALSO be byte-identical to 1.8.74 — its
 *        visit and cutoff both land BEFORE the Sept 7-11 restock, so its
 *        scarcity display is a true fact and the founder ruling does not
 *        reach it. A build that suppressed every visit would quietly delete a
 *        working, founder-ruled feature for the school ordering RIGHT NOW.
 *        §6 is the differential test that proves it did not. §6.
 *
 *   2. ⛔⛔ NEVER A NUMBER HIGHER THAN THE REAL ONE. The false-availability
 *      rule is a hard gate. This build may only ever REMOVE a quantity. §5
 *      asserts every suppressed surface emits NO digit at all, rather than a
 *      different digit.
 *
 *   3. ⛔⛔ PURCHASABILITY IS UNCHANGED FOR EVERYBODY. Hiding a count must not
 *      hide, add or move a refusal. §7 puts the SAME forced-closed title in
 *      front of an Amity session and a Dallas Harris session and asserts the
 *      closed map, the sold-out strings and `paperback_only` are identical. A
 *      suppression that also suppressed the sold-out badge would offer a
 *      parent a book the server is about to decline.
 *
 *   4. ⛔ IT IS NOT WOOCOMMERCE INVENTORY AND IT WRITES NOTHING. §9 re-reads
 *      the raw registry option, the shelf option, `woocommerce_stock_format`
 *      and every core product's `_manage_stock` / `_stock` / `_stock_status` /
 *      `_backorders` before and after the whole suite and asserts each is
 *      byte-identical.
 *
 *   5. ⭐ THE REGISTRY FIELD IS THE FORWARD PATH. §2 proves `hide_stock` is
 *      read tolerantly, defaults FALSE, and can turn a visit on WITHOUT a
 *      deploy — so the next school is a `wp option patch update`, not a build.
 *
 * ---------------------------------------------------------------------------
 * ⛔ WHAT IT DELIBERATELY DOES NOT DO
 * ---------------------------------------------------------------------------
 * ⛔ It writes NO option, NO product, NO price, NO stock status, NO backorder
 *    setting, NO coupon, NO shipping, tax, pickup or payment setting, on any
 *    environment. ⭐ Every state it needs is forced through a FILTER —
 *    `bhp_visit_shelf_title_counter`, `bhp_visit_shelf_title_is_closed` and
 *    `bhp_school_visit_stock_hidden_slugs` — for the same reason the 1.8.71
 *    suite does it: a suite that had to write `bhp_visit_shelf_stock` or
 *    `bhp_school_visits` could not be run safely on a live environment, and
 *    would then be skipped exactly when it mattered.
 * ⛔ It places NO order and modifies NO order.
 * ⚠ It DOES set two keys in the WooCommerce session. Both are session state,
 *   not stored settings, and both are cleared in §10.
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run this via WP-CLI (wp eval-file), not directly.\n" );
	exit( 1 );
}

/*
 * ⚠️ THE COUNTERS LIVE IN `$GLOBALS`, EXPLICITLY. `wp eval-file` includes this
 * file from INSIDE a method, so a bare top-level `$pass = 0;` is a LOCAL of
 * that method and every increment would be silently lost while PASS/FAIL lines
 * still printed. Recorded on the 1.8.72 suite, observed first-hand.
 */
$GLOBALS['bhp_pass'] = 0;
$GLOBALS['bhp_fail'] = 0;
$GLOBALS['bhp_skip'] = 0;

function bhp_t( $label, $cond, $detail = '' ) {
	if ( $cond ) {
		++$GLOBALS['bhp_pass'];
		echo "  PASS  $label" . ( '' !== $detail ? "   [$detail]" : '' ) . "\n";
	} else {
		++$GLOBALS['bhp_fail'];
		echo "  FAIL  $label" . ( '' !== $detail ? "   [$detail]" : '' ) . "\n";
	}
}

function bhp_skip( $label, $why ) {
	++$GLOBALS['bhp_skip'];
	echo "  SKIP  $label   [$why]\n";
}

function bhp_h( $title ) {
	echo "\n" . str_repeat( '=', 78 ) . "\n$title\n" . str_repeat( '=', 78 ) . "\n";
}

/** Put the CLI request into a named visit session, or clear it with null. */
function bhp_ss_flag( $slug ) {
	if ( ! function_exists( 'WC' ) || ! WC()->session ) {
		return false;
	}
	if ( null === $slug ) {
		WC()->session->__unset( BHP_SCHOOL_VISIT_SESSION_KEY );
		WC()->session->__unset( BHP_SCHOOL_VISIT_SET_AT_SESSION_KEY );
		return true;
	}
	WC()->session->set( BHP_SCHOOL_VISIT_SESSION_KEY, $slug );
	WC()->session->set( BHP_SCHOOL_VISIT_SET_AT_SESSION_KEY, time() );
	return true;
}

echo "\nCYCLE168-LD-AMITY-STOCK-SUPPRESSION — per-visit stock privacy suite\n";
echo 'SITE: ' . home_url() . "\n";
echo 'PLUGIN: ' . ( defined( 'BHP_BUNDLE_PRICING_VERSION' ) ? BHP_BUNDLE_PRICING_VERSION : '?' ) . "\n";

/* =========================================================================
 * §0 — THE MODULE LOADED AT ALL
 * ====================================================================== */
bhp_h( '§0 — module present' );

$required = array(
	'bhp_school_visit_flag_is_on',
	'bhp_school_visit_stock_hidden_slugs',
	'bhp_school_visit_record_hides_stock',
	'bhp_school_visit_hide_stock_for_request',
	'bhp_school_visit_stock_privacy_active',
	'bhp_school_visit_stock_format_no_amount',
	'bhp_school_visit_strip_availability_quantity',
	'bhp_school_visit_hide_formatted_stock_quantity',
	// The 1.8.72 surface this build gates.
	'bhp_visit_shelf_counter_for_request',
	'bhp_visit_shelf_counter_map_for_request',
	'bhp_visit_shelf_render_counter',
	'bhp_visit_shelf_closed_map_for_request',
);
foreach ( $required as $fn ) {
	bhp_t( "function exists: $fn", function_exists( $fn ) );
}

if ( $GLOBALS['bhp_fail'] > 0 ) {
	echo "\nABORTING: the module is not loaded. Nothing below would mean anything.\n";
	exit( 1 );
}

/* =========================================================================
 * §1 — SNAPSHOT EVERYTHING THIS SUITE MUST NOT CHANGE
 *
 * ⛔ TAKEN FIRST, RAW, AND COMPARED IN §9. Not "we do not write anything" as a
 *    claim, but as a re-read.
 * ====================================================================== */
bhp_h( '§1 — pre-suite snapshot (compared in §9)' );

$snap_visits    = wp_json_encode( get_option( 'bhp_school_visits', array() ) );
$snap_shelf     = wp_json_encode( get_option( 'bhp_visit_shelf_stock', array() ) );
$snap_stock_fmt = wp_json_encode( get_option( 'woocommerce_stock_format', null ) );

$core_ids = array();
if ( function_exists( 'bhp_bundle_catalog' ) ) {
	$cat = bhp_bundle_catalog();
	foreach ( array( 'paperback', 'hardcover' ) as $fmt ) {
		if ( empty( $cat[ $fmt ] ) || ! is_array( $cat[ $fmt ] ) ) {
			continue;
		}
		foreach ( $cat[ $fmt ] as $ed ) {
			if ( ! empty( $ed['product_id'] ) ) {
				$core_ids[] = (int) $ed['product_id'];
			}
			if ( ! empty( $ed['variation_id'] ) ) {
				$core_ids[] = (int) $ed['variation_id'];
			}
		}
	}
}
$core_ids  = array_values( array_unique( $core_ids ) );
$snap_meta = array();
foreach ( $core_ids as $pid ) {
	$snap_meta[ $pid ] = array(
		'manage'     => (string) get_post_meta( $pid, '_manage_stock', true ),
		'stock'      => (string) get_post_meta( $pid, '_stock', true ),
		'status'     => (string) get_post_meta( $pid, '_stock_status', true ),
		'backorders' => (string) get_post_meta( $pid, '_backorders', true ),
	);
}
bhp_t( 'core product ids resolved from the catalog', count( $core_ids ) >= 6, implode( ',', $core_ids ) );
echo '  snapshot: ' . wp_json_encode( $snap_meta ) . "\n";

/* =========================================================================
 * §1b — ⭐⭐ THE AMITY ROW, INJECTED THROUGH A READ FILTER. NOTHING IS WRITTEN.
 *
 * ⛔ THE SUITE MUST NOT WRITE `bhp_school_visits`, AND THIS IS HOW IT AVOIDS
 *    IT. The registry is a production data row and writing one — on ANY
 *    environment — is an approval gate, not a test convenience. So when the
 *    environment being tested does not carry the Amity row, it is added to the
 *    READ for the duration of this CLI process only, through WordPress's own
 *    `option_bhp_school_visits` filter. Nothing reaches the database, no
 *    other request sees it, and §9 removes the filter and re-reads the STORED
 *    option to prove the row on disk never moved.
 *
 * ⭐ ON AN ENVIRONMENT THAT ALREADY CARRIES THE ROW — production does, read
 *    first-hand 2026-08-28 — the filter is NOT installed and the suite tests
 *    the real stored row. The injection is a fallback, not the subject.
 *
 * ⚠️ THE INJECTED ROW IS A FAITHFUL COPY OF PRODUCTION'S, read first-hand over
 *    SSH on 2026-08-28: school "Amity Elementary", date 2026-09-14, cutoff
 *    2026-09-11, time "11:00 AM". It deliberately carries NO `hide_stock` key,
 *    so what §5 proves is the CODE-LEVEL ruling — the path that will actually
 *    be in force on production the moment 1.8.75 installs, with no data write.
 * ====================================================================== */
$GLOBALS['bhp_ss_injected'] = false;
$GLOBALS['bhp_ss_inject']   = function ( $value ) {
	if ( ! is_array( $value ) ) {
		$value = array();
	}
	if ( ! isset( $value['amity-2026-09-14'] ) ) {
		$value['amity-2026-09-14'] = array(
			'school' => 'Amity Elementary',
			'date'   => '2026-09-14',
			'cutoff' => '2026-09-11',
			'time'   => '11:00 AM',
		);
	}
	return $value;
};

$stored_visits = get_option( 'bhp_school_visits', array() );
if ( ! is_array( $stored_visits ) || ! isset( $stored_visits['amity-2026-09-14'] ) ) {
	add_filter( 'option_bhp_school_visits', $GLOBALS['bhp_ss_inject'], 5 );
	$GLOBALS['bhp_ss_injected'] = true;
	echo "  NOTE: this environment has no stored amity row. Injected through a\n";
	echo "        READ FILTER for this process only. NOTHING IS WRITTEN.\n";
} else {
	echo "  NOTE: this environment carries the stored amity row. No injection.\n";
}
bhp_t(
	'amity-2026-09-14 is readable as a registry record',
	isset( bhp_school_visit_records()['amity-2026-09-14'] ),
	$GLOBALS['bhp_ss_injected'] ? 'injected via read filter' : 'stored on this environment'
);

/* =========================================================================
 * §2 — THE REGISTRY FIELD: OPTIONAL, TOLERANT, DEFAULTS FALSE
 * ====================================================================== */
bhp_h( '§2 — `hide_stock` registry field' );

$truth = array(
	array( true, true ),
	array( false, false ),
	array( 1, true ),
	array( 0, false ),
	array( '1', true ),
	array( '0', false ),
	array( 'yes', true ),
	array( 'YES', true ),
	array( ' true ', true ),
	array( 'on', true ),
	array( 'no', false ),
	array( 'false', false ),
	array( '', false ),
	array( null, false ),
	array( array(), false ),
	array( 'banana', false ),
);
foreach ( $truth as $row ) {
	bhp_t(
		'flag_is_on(' . wp_json_encode( $row[0] ) . ') === ' . wp_json_encode( $row[1] ),
		bhp_school_visit_flag_is_on( $row[0] ) === $row[1]
	);
}

$records = bhp_school_visit_records();
bhp_t( 'registry returned at least one record', ! empty( $records ), (string) count( $records ) );
foreach ( $records as $slug => $rec ) {
	bhp_t( "record `$slug` carries a hide_stock key", array_key_exists( 'hide_stock', $rec ) );
	bhp_t( "record `$slug` hide_stock is a real bool", is_bool( $rec['hide_stock'] ) );
}

/*
 * ⭐ THE FORWARD PATH, PROVEN WITHOUT WRITING THE REGISTRY. A row that arrives
 *    with `hide_stock` set is honoured even though its slug is in no code list.
 */
$synthetic = array(
	'slug'       => 'some-future-school-2027-01-01',
	'school'     => 'Some Future School',
	'date'       => '2027-01-01',
	'cutoff'     => '2026-12-29',
	'time'       => '',
	'hide_stock' => true,
);
bhp_t(
	'a registry row with hide_stock=true hides, with no code change',
	true === bhp_school_visit_record_hides_stock( $synthetic )
);
$synthetic['hide_stock'] = false;
bhp_t(
	'the same row with hide_stock=false does NOT hide',
	false === bhp_school_visit_record_hides_stock( $synthetic )
);
unset( $synthetic['hide_stock'] );
bhp_t(
	'the same row with NO hide_stock key does NOT hide',
	false === bhp_school_visit_record_hides_stock( $synthetic )
);
bhp_t( 'a non-array record never hides', false === bhp_school_visit_record_hides_stock( null ) );

/* =========================================================================
 * §3 — THE CODE-LEVEL RULING, AND WHICH VISITS IT REACHES
 *
 * ⛔ THE POINT OF THIS SECTION IS THE *NEGATIVE* ROWS. Amity being on the list
 *    is one assertion; the other three visits being OFF it is the assertion
 *    that keeps a working feature working for the schools ordering now.
 * ====================================================================== */
bhp_h( '§3 — the founder ruling, per visit' );

$hidden = bhp_school_visit_stock_hidden_slugs();
bhp_t( 'amity-2026-09-14 is in the code-level hidden set', in_array( 'amity-2026-09-14', $hidden, true ), implode( ',', $hidden ) );

foreach ( array( 'adams-2026-08-28', 'dallas-harris-2026-09-03', 'liberty-2026-09-04' ) as $other ) {
	bhp_t( "$other is NOT in the hidden set", ! in_array( $other, $hidden, true ) );
	if ( isset( $records[ $other ] ) ) {
		bhp_t( "$other record does NOT hide stock", false === bhp_school_visit_record_hides_stock( $records[ $other ] ) );
	} else {
		bhp_skip( "$other record check", 'that visit is not in this environment\'s registry' );
	}
}

if ( isset( $records['amity-2026-09-14'] ) ) {
	bhp_t( 'amity record hides stock', true === bhp_school_visit_record_hides_stock( $records['amity-2026-09-14'] ) );
} else {
	bhp_skip( 'amity record check', 'amity-2026-09-14 is not in this environment\'s registry' );
}

// The filter, so Andrew can switch it off the day the restock is counted.
add_filter( 'bhp_school_visit_stock_hidden_slugs', '__return_empty_array', 99 );
bhp_t( 'the hidden set is filterable to empty', array() === bhp_school_visit_stock_hidden_slugs() );
remove_filter( 'bhp_school_visit_stock_hidden_slugs', '__return_empty_array', 99 );
bhp_t( 'and it restores when the filter is removed', in_array( 'amity-2026-09-14', bhp_school_visit_stock_hidden_slugs(), true ) );

/* =========================================================================
 * §4 — THE ORDINARY SHOPPER. ZERO CHANGE.
 * ====================================================================== */
bhp_h( '§4 — unflagged session' );

if ( ! function_exists( 'WC' ) || ! WC()->session ) {
	bhp_skip( '§4-§8 session assertions', 'no WooCommerce session in CLI' );
	$has_session = false;
} else {
	$has_session = true;
	bhp_ss_flag( null );

	bhp_t( 'unflagged: hide_stock_for_request() is false', false === bhp_school_visit_hide_stock_for_request() );
	bhp_t( 'unflagged: stock_privacy_active() is false', false === bhp_school_visit_stock_privacy_active() );
	bhp_t( 'unflagged: counter map is empty', array() === bhp_visit_shelf_counter_map_for_request() );
	bhp_t( 'unflagged: closed map is empty', array() === bhp_visit_shelf_closed_map_for_request() );
	bhp_t(
		'unflagged: woocommerce_stock_format read is the STORED value',
		wp_json_encode( get_option( 'woocommerce_stock_format', null ) ) === $snap_stock_fmt,
		$snap_stock_fmt
	);
	bhp_t(
		'unflagged: an availability string carrying a number is untouched',
		'12 in stock' === bhp_school_visit_strip_availability_quantity( '12 in stock', wc_get_product( $core_ids[0] ) )
	);
}

/* =========================================================================
 * §5 — THE AMITY SESSION. NO QUANTITY, ANYWHERE.
 *
 * ⭐ THE SHELF IS FORCED INTO THE DISPLAY WINDOW THROUGH THE 1.8.72 FILTER,
 *    NOT BY WRITING THE BASELINE OPTION. Whatever the real shelf happens to be
 *    on this environment, every title is made to want to print a number — so a
 *    silent "nothing printed" cannot pass for a suppression that never ran.
 * ====================================================================== */
bhp_h( '§5 — amity session: every quantity suppressed' );

$forced = 4; // Inside 2..BHP_VISIT_SHELF_COUNTER_MAX, so 1.8.74 WOULD print it.
$force_counter = function ( $out, $slug, $live, $ceiling ) use ( $forced ) {
	unset( $out, $slug, $live, $ceiling );
	return $forced;
};

$amity_live = $has_session && null !== bhp_school_visit_resolve( 'amity-2026-09-14' );
$dh_live    = $has_session && null !== bhp_school_visit_resolve( 'dallas-harris-2026-09-03' );

if ( ! $amity_live ) {
	bhp_skip( '§5 amity assertions', 'amity-2026-09-14 does not resolve to a LIVE visit on this environment' );
} else {
	add_filter( 'bhp_visit_shelf_title_counter', $force_counter, 99, 4 );
	bhp_ss_flag( 'amity-2026-09-14' );

	bhp_t( 'amity: the session really is flagged', true === bhp_school_visit_paperback_only() );
	bhp_t( 'amity: hide_stock_for_request() is TRUE', true === bhp_school_visit_hide_stock_for_request() );

	// The shelf itself still knows the number. Only the SURFACES are blind.
	bhp_t(
		'amity: the underlying shelf arithmetic is UNCHANGED (surfaces are gated, not the maths)',
		$forced === bhp_visit_shelf_title_counter( 'everest' ),
		'title_counter() still returns ' . $forced
	);

	bhp_t( 'amity: counter map is EMPTY', array() === bhp_visit_shelf_counter_map_for_request() );
	foreach ( bhp_visit_shelf_title_slugs() as $slug ) {
		bhp_t( "amity: counter_for_request($slug) is null", null === bhp_visit_shelf_counter_for_request( $slug ) );
	}
	bhp_t(
		'amity: constraining title for the box is null',
		null === bhp_visit_shelf_constraining_title_for_request( 'paperback' )
	);

	/*
	 * ⛔ THE BYTE ASSERTION. Not "the number is different" — NO DIGIT AT ALL,
	 *    and no empty span either. An ordinary parent's HTML must contain no
	 *    trace, and so must an Amity parent's.
	 */
	foreach ( bhp_visit_shelf_title_slugs() as $slug ) {
		ob_start();
		bhp_visit_shelf_render_counter( $slug );
		$markup = ob_get_clean();
		bhp_t( "amity: render_counter($slug) emits ZERO bytes", '' === $markup, var_export( $markup, true ) );
		bhp_t( "amity: render_counter($slug) emits no digit", ! preg_match( '/\d/', $markup ) );
	}

	// The WooCommerce belt.
	bhp_t( 'amity: stock_privacy_active() is TRUE', true === bhp_school_visit_stock_privacy_active() );
	bhp_t(
		'amity: woocommerce_stock_format reads as no_amount',
		'no_amount' === get_option( 'woocommerce_stock_format' )
	);
	$p = wc_get_product( $core_ids[0] );
	bhp_t(
		'amity: an availability string carrying a number is replaced, with no digit left',
		! preg_match( '/\d/', (string) bhp_school_visit_strip_availability_quantity( '12 in stock', $p ) ),
		bhp_school_visit_strip_availability_quantity( '12 in stock', $p )
	);
	bhp_t(
		'amity: a digit-free availability string is returned by IDENTITY',
		'In stock' === bhp_school_visit_strip_availability_quantity( 'In stock', $p )
	);
	bhp_t(
		'amity: the formatted-quantity filter drops the number',
		! preg_match( '/\d/', (string) bhp_school_visit_hide_formatted_stock_quantity( '9 in stock', $p ) )
	);

	remove_filter( 'bhp_visit_shelf_title_counter', $force_counter, 99 );
}

/* =========================================================================
 * §6 — ⭐⭐ THE DIFFERENTIAL. THE SAME FORCED SHELF, TWO VISITS, TWO ANSWERS.
 *
 * ⛔ THIS IS THE ASSERTION THAT MATTERS MOST, because a build that simply
 *    turned the counter off for everybody would pass every §5 assertion above
 *    and would silently delete a founder-ruled feature from the school that is
 *    ordering RIGHT NOW.
 * ====================================================================== */
bhp_h( '§6 — dallas harris still sees the real count' );

if ( ! $dh_live ) {
	bhp_skip( '§6 differential', 'dallas-harris-2026-09-03 does not resolve to a LIVE visit here' );
} else {
	add_filter( 'bhp_visit_shelf_title_counter', $force_counter, 99, 4 );
	bhp_ss_flag( 'dallas-harris-2026-09-03' );

	bhp_t( 'dallas harris: the session really is flagged', true === bhp_school_visit_paperback_only() );
	bhp_t( 'dallas harris: hide_stock_for_request() is FALSE', false === bhp_school_visit_hide_stock_for_request() );

	$dh_map = bhp_visit_shelf_counter_map_for_request();
	bhp_t( 'dallas harris: counter map is NOT empty', ! empty( $dh_map ), wp_json_encode( $dh_map ) );
	foreach ( bhp_visit_shelf_title_slugs() as $slug ) {
		bhp_t( "dallas harris: counter_for_request($slug) === $forced", $forced === bhp_visit_shelf_counter_for_request( $slug ) );
	}

	ob_start();
	bhp_visit_shelf_render_counter( 'everest' );
	$dh_markup = ob_get_clean();
	bhp_t( 'dallas harris: render_counter emits markup', '' !== $dh_markup, $dh_markup );
	bhp_t( 'dallas harris: and that markup carries the real number', false !== strpos( $dh_markup, (string) $forced ) );

	bhp_t(
		'dallas harris: woocommerce_stock_format is the STORED value, untouched',
		wp_json_encode( get_option( 'woocommerce_stock_format', null ) ) === $snap_stock_fmt
	);
	bhp_t(
		'dallas harris: an availability string carrying a number is untouched',
		'12 in stock' === bhp_school_visit_strip_availability_quantity( '12 in stock', wc_get_product( $core_ids[0] ) )
	);

	remove_filter( 'bhp_visit_shelf_title_counter', $force_counter, 99 );
}

/* =========================================================================
 * §7 — ⛔⛔ PURCHASABILITY IS UNTOUCHED, AND SO IS THE SOLD-OUT SIGNAL.
 *
 * ⛔ HIDING A QUANTITY IS HONEST. HIDING A REFUSAL IS NOT. A closed title must
 *    still read "Sold out for the school visit" to an Amity parent, because
 *    the server is still going to refuse the add — that is a PURCHASABILITY
 *    signal, not a stock number, and this build must not move it.
 * ====================================================================== */
bhp_h( '§7 — the sold-out state survives the suppression' );

if ( ! $amity_live || ! $dh_live ) {
	bhp_skip( '§7 sold-out parity', 'both amity and dallas harris must resolve live for the comparison' );
} else {
	$slugs   = bhp_visit_shelf_title_slugs();
	$victim  = ! empty( $slugs ) ? $slugs[0] : null;
	$catalog = bhp_bundle_catalog();
	$vic_pid = ( $victim && ! empty( $catalog['paperback'][ $victim ]['product_id'] ) )
		? (int) $catalog['paperback'][ $victim ]['product_id']
		: 0;

	$force_closed = function ( $closed, $slug, $remaining ) use ( $victim ) {
		unset( $closed, $remaining );
		return ( $slug === $victim );
	};
	add_filter( 'bhp_visit_shelf_title_is_closed', $force_closed, 99, 3 );

	/*
	 * ═══════════════════════════════════════════════════════════════════════
	 * ⭐⭐ 1.8.76 (`CYCLE168-LD-RETAILER-BATCH-AND-BACKORDERS`) — THIS SECTION
	 *     NOW FORCES BACKORDERS **OFF**, AND THE REASON MATTERS.
	 * ═══════════════════════════════════════════════════════════════════════
	 *
	 * ⛔ IT IS NOT A WORKAROUND FOR A BROKEN ASSERTION. §7 exists to prove one
	 *    specific thing: that hiding a QUANTITY from Amity does NOT also hide a
	 *    REFUSAL. That was 1.8.75's central safety property — "hiding a
	 *    QUANTITY is honest, hiding a REFUSAL would offer a parent a book the
	 *    server is about to decline" — and it is still true and still worth
	 *    asserting.
	 *
	 * ⭐ BUT AFTER ITEM 363 THERE IS OFTEN NO REFUSAL TO SEE. Backorders are on
	 *   by default, so an exhausted title is orderable and no sold-out label
	 *   renders for anybody. Asserting parity of a refusal requires a refusal to
	 *   exist, so this section pins the one configuration in which one does.
	 *
	 * ⭐⭐ THE POST-363 COMPOSITION IS NOT SKIPPED — it is asserted separately
	 *     in §7a immediately below, with backorders back ON. Between the two,
	 *     both modes are covered.
	 */
	add_filter( 'bhp_visit_shelf_backorder_allowed', '__return_false', 5 );

	bhp_ss_flag( 'dallas-harris-2026-09-03' );
	$dh_closed = bhp_visit_shelf_closed_map_for_request();
	$dh_pb     = bhp_school_visit_paperback_only();
	$dh_item   = bhp_visit_shelf_is_closed_item( $vic_pid, 0 );

	bhp_ss_flag( 'amity-2026-09-14' );
	$am_closed = bhp_visit_shelf_closed_map_for_request();
	$am_pb     = bhp_school_visit_paperback_only();
	$am_item   = bhp_visit_shelf_is_closed_item( $vic_pid, 0 );

	bhp_t( 'closed map is IDENTICAL for amity and dallas harris', $am_closed === $dh_closed, wp_json_encode( $am_closed ) );
	bhp_t( "amity still sees `$victim` as closed", isset( $am_closed[ $victim ] ) );
	bhp_t( 'paperback_only is IDENTICAL for both', $am_pb === $dh_pb );
	bhp_t( 'the product-level refusal predicate is IDENTICAL for both, and TRUE', $am_item === $dh_item && true === $am_item );

	/*
	 * ⭐ THE REAL LOOP SURFACE, not a stand-in: the same `global $product` and
	 *    the same function WooCommerce calls on `/shop/`.
	 */
	if ( $vic_pid ) {
		$prev_global        = isset( $GLOBALS['product'] ) ? $GLOBALS['product'] : null;
		$GLOBALS['product'] = wc_get_product( $vic_pid );

		ob_start();
		bhp_visit_shelf_loop_stock_line();
		$probe = ob_get_clean();

		bhp_t(
			'amity: a closed title still renders the sold-out label on the real loop surface',
			false !== strpos( $probe, bhp_visit_shelf_sold_out_label() ),
			$probe
		);
		bhp_t( 'amity: and that label carries no quantity', ! preg_match( '/\d/', $probe ), $probe );

		// The add-to-cart control of a closed title is still replaced.
		$html = bhp_visit_shelf_loop_add_to_cart_link( '<a class="button">Add</a>', $GLOBALS['product'] );
		bhp_t( 'amity: the closed title\'s add-to-cart control is still replaced', false !== strpos( $html, 'bhp-bundle-sold-out-button' ) );

		$GLOBALS['product'] = $prev_global;
	} else {
		bhp_skip( 'amity loop-surface sold-out probe', 'no product id resolved for the victim title' );
	}

	/* ═══════════════════════════════════════════════════════════════════════
	 * ⭐⭐ §7a — 1.8.76: SUPPRESSION **AND** BACKORDERS, TOGETHER, ON AMITY.
	 * ═══════════════════════════════════════════════════════════════════════
	 *
	 * ⭐ THIS IS THE CONFIGURATION THAT WILL ACTUALLY BE LIVE ON 2026-09-14.
	 *   Amity hides quantities (item 359) AND backorders are allowed (item
	 *   363). The two features are independent and were built nine hours apart,
	 *   so the composition is asserted rather than assumed.
	 *
	 * ⛔ THE FAILURE THIS CATCHES: a backorder line that leaked a number would
	 *    defeat the Amity suppression through a brand-new surface, which is
	 *    exactly the sort of thing a second feature does to a first one.
	 */
	remove_filter( 'bhp_visit_shelf_backorder_allowed', '__return_false', 5 );

	if ( ! function_exists( 'bhp_visit_shelf_backorder_allowed' ) ) {
		bhp_skip( '§7a suppression + backorder composition', '1.8.76 backorder module not loaded' );
	} else {
		bhp_t( 'the OFF filter is lifted; backorders are back at the shipped default', true === bhp_visit_shelf_backorder_allowed() );

		bhp_ss_flag( 'amity-2026-09-14' );

		bhp_t(
			'amity: an exhausted title is NO LONGER in the closed map (item 363 relaxed the gate)',
			! isset( bhp_visit_shelf_closed_map_for_request()[ $victim ] )
		);
		bhp_t(
			'amity: and the product-level refusal predicate now accepts it',
			false === bhp_visit_shelf_is_closed_item( $vic_pid, 0 )
		);
		bhp_t(
			'amity: the shelf FACT is unchanged and still reports it exhausted',
			true === bhp_visit_shelf_title_is_exhausted( $victim )
		);

		if ( $vic_pid ) {
			$prev_global        = isset( $GLOBALS['product'] ) ? $GLOBALS['product'] : null;
			$GLOBALS['product'] = wc_get_product( $vic_pid );

			ob_start();
			bhp_visit_shelf_loop_stock_line();
			$probe2 = ob_get_clean();

			bhp_t(
				'amity: the real loop surface now renders the BACKORDER line, not the sold-out label',
				false !== strpos( $probe2, 'bhp-bundle-backorder-label' )
				&& false === strpos( $probe2, 'bhp-bundle-sold-out-label' ),
				$probe2
			);
			/*
			 * ⛔⛔ THE ASSERTION THIS WHOLE SECTION IS FOR. Amity must never see
			 *    a quantity, and 1.8.76 introduced new markup on the exact
			 *    surface where quantities live.
			 */
			bhp_t(
				'amity: the backorder line carries NO DIGIT, so the suppression holds through the new surface',
				! preg_match( '/\d/', wp_strip_all_tags( $probe2 ) ),
				$probe2
			);
			bhp_t(
				'amity: it names no month, so it makes no restock promise either',
				0 === preg_match( '/\b(september|sept|october)\b/i', $probe2 ),
				$probe2
			);

			// ⭐ AND THE PURCHASE CONTROL IS A REAL BUTTON AGAIN.
			$html2 = bhp_visit_shelf_loop_add_to_cart_link( '<a class="button">Add</a>', $GLOBALS['product'] );
			bhp_t(
				'amity: the add-to-cart control is NOT replaced, so the parent can actually order',
				false === strpos( $html2, 'bhp-bundle-sold-out-button' ),
				$html2
			);

			$GLOBALS['product'] = $prev_global;
		}
	}

	remove_filter( 'bhp_visit_shelf_title_is_closed', $force_closed, 99 );
	remove_filter( 'bhp_visit_shelf_backorder_allowed', '__return_false', 5 );
}

/* =========================================================================
 * §8 — THE ADMIN CARVE-OUT
 * ====================================================================== */
bhp_h( '§8 — wp-admin still sees the truth' );

if ( ! $amity_live ) {
	bhp_skip( '§8 admin carve-out', 'amity does not resolve live here' );
} else {
	bhp_ss_flag( 'amity-2026-09-14' );
	bhp_t( 'front-end: privacy active', true === bhp_school_visit_stock_privacy_active() );

	// `is_admin()` is driven by WP_ADMIN, which WP-CLI leaves false. Simulate
	// the branch by asking the predicate the same question the filter asks.
	if ( defined( 'WP_ADMIN' ) && WP_ADMIN ) {
		bhp_t( 'admin: privacy NOT active', false === bhp_school_visit_stock_privacy_active() );
	} else {
		bhp_skip( 'admin: privacy NOT active', 'WP-CLI is not an admin request; asserted by code review of the is_admin() guard' );
	}
}

/* =========================================================================
 * §9 — ⛔⛔ NOTHING WAS WRITTEN. RE-READ, DO NOT ASSERT.
 * ====================================================================== */
bhp_h( '§9 — no writes' );

bhp_ss_flag( null ); // Read the options back as an ORDINARY request would.

/*
 * ⛔ THE INJECTION FILTER COMES OFF FIRST, so the assertion below reads the
 *    STORED row and not the one this process invented. Removing it after the
 *    comparison would make the comparison meaningless.
 */
if ( $GLOBALS['bhp_ss_injected'] ) {
	remove_filter( 'option_bhp_school_visits', $GLOBALS['bhp_ss_inject'], 5 );
	bhp_t(
		'the injected amity row is gone the moment the filter is removed',
		! isset( bhp_school_visit_records()['amity-2026-09-14'] )
	);
}

bhp_t( '`bhp_school_visits` is byte-identical', wp_json_encode( get_option( 'bhp_school_visits', array() ) ) === $snap_visits );
bhp_t( '`bhp_visit_shelf_stock` is byte-identical', wp_json_encode( get_option( 'bhp_visit_shelf_stock', array() ) ) === $snap_shelf );
bhp_t( '`woocommerce_stock_format` is byte-identical', wp_json_encode( get_option( 'woocommerce_stock_format', null ) ) === $snap_stock_fmt );

$after_meta = array();
foreach ( $core_ids as $pid ) {
	$after_meta[ $pid ] = array(
		'manage'     => (string) get_post_meta( $pid, '_manage_stock', true ),
		'stock'      => (string) get_post_meta( $pid, '_stock', true ),
		'status'     => (string) get_post_meta( $pid, '_stock_status', true ),
		'backorders' => (string) get_post_meta( $pid, '_backorders', true ),
	);
}
bhp_t( 'every core product stock meta row is byte-identical', $after_meta === $snap_meta, wp_json_encode( $after_meta ) );
bhp_t(
	'every core product is still instock',
	count( $after_meta ) === count( array_filter( $after_meta, function ( $m ) { return 'instock' === $m['status']; } ) )
);

/* =========================================================================
 * §10 — CLEANUP
 * ====================================================================== */
bhp_h( '§10 — cleanup' );

if ( $has_session ) {
	bhp_ss_flag( null );
	bhp_t( 'visit session flag cleared', ! bhp_school_visit_paperback_only() );
	bhp_t( 'and privacy is off again', false === bhp_school_visit_hide_stock_for_request() );
}
if ( function_exists( 'WC' ) && WC()->cart ) {
	WC()->cart->empty_cart();
}
if ( function_exists( 'wc_clear_notices' ) ) {
	wc_clear_notices();
}

echo "\n" . str_repeat( '=', 78 ) . "\n";
printf( "RESULT: %d passed, %d failed, %d skipped\n", $GLOBALS['bhp_pass'], $GLOBALS['bhp_fail'], $GLOBALS['bhp_skip'] );
echo str_repeat( '=', 78 ) . "\n";

if ( $GLOBALS['bhp_fail'] > 0 ) {
	echo "SUITE FAILED\n";
	exit( 1 );
}
