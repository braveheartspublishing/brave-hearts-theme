<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * COUPON AUTO-APPLY COUNTER — theme 1.19.337, 2026-08-30, `CYCLE170-LD-MICRO`.
 * The diagnostic gap item in the micro-build brief.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ---------------------------------------------------------------------------
 * ⭐⭐ THE QUESTION THIS EXISTS TO MAKE ANSWERABLE, AND IT IS ONE QUESTION
 * ---------------------------------------------------------------------------
 *
 * WooCommerce records how many times a coupon was REDEEMED — `usage_count` on
 * the coupon post, and a row per order in `<prefix>wc_order_coupon_lookup`. ⛔ IT
 * RECORDS NOTHING AT ALL ABOUT A DISCOUNT THAT WENT ON A CART AND NEVER REACHED
 * AN ORDER. So today the store can answer *"how many people bought with this
 * code"* and cannot answer *"how many people were given this code and then did
 * not buy"* — which is the number that says whether a coupon campaign has a
 * TRAFFIC problem or a CHECKOUT problem, and they need opposite fixes.
 *
 * ⭐ THIS FILE SUPPLIES THE MISSING HALF: one server-side increment each time
 *    the auto-apply feature SUCCESSFULLY puts a code on a cart. Subtracting
 *    Woo's redeemed count from this one makes *"applied but did not buy"*
 *    computable for the first time.
 *
 * ⚠️ AND THE SUBTRACTION IS AN ESTIMATE, NOT AN IDENTITY. Said here rather than
 *   discovered by whoever first quotes the number:
 *     · ⛔ A SHOPPER CAN BE COUNTED MORE THAN ONCE. A new browser session, a
 *       cleared cookie or a second device is a second apply. This counts
 *       APPLICATIONS, not people, and its name says so.
 *     · ⛔ IT COUNTS ONLY THE AUTO-APPLY PATH. A code typed into the coupon box
 *       by hand is not counted and must not be read as one — see the scope
 *       note below.
 *     · ⛔ THE TWO NUMBERS ARE ON DIFFERENT CLOCKS. An apply on the 30th can
 *       become a redemption on the 31st, so a same-day difference is noise.
 *       Compare over a window, never day against day.
 *   ⭐ Being able to compute a bounded estimate is the whole win; presenting it
 *     as an exact person count would be the failure.
 *
 * ---------------------------------------------------------------------------
 * ⛔⛔ WHAT IS STORED — AND THE LIST IS EXHAUSTIVE
 * ---------------------------------------------------------------------------
 *
 * A coupon CODE and a DATE, and an integer. That is the entire record.
 *
 * ⛔ NO PII OF ANY KIND. No email, no name, no user id, no customer id, no
 *    order id, no cart contents, no IP address, no user agent, no session or
 *    session key, no referrer and no timestamp finer than a calendar day.
 *    ⭐ Day granularity is a deliberate privacy floor, not laziness: a
 *      per-second log of applies on a low-volume store is close to a
 *      per-visitor log, and nothing this number is for needs the hour.
 *
 * ⛔ NO COOKIE IS READ OR WRITTEN AND NO CONSENT POSTURE IS TOUCHED. This runs
 *    entirely server-side, after the fact, on a cart action the visitor has
 *    already taken. There is no client-side component, no beacon, no pixel, and
 *    nothing to declare to WPConsent. ⭐ That is precisely why it is an option
 *    increment rather than an analytics event.
 *
 * ---------------------------------------------------------------------------
 * ⛔ SCOPE — WHAT IS AND IS NOT COUNTED, STATED SO NOBODY OVER-READS IT
 * ---------------------------------------------------------------------------
 *
 *   ✅ COUNTED: `bhp_coupon_url_maybe_apply()` in `inc/coupon-url-apply.php` —
 *      the link-carried auto-apply. That is "the auto-apply feature" the brief
 *      names, and it is theme code, which is what this release ships.
 *
 *   ⛔ NOT COUNTED, AND THIS IS A REAL GAP RATHER THAN A DESIGN CHOICE:
 *      `bhp_typ_maybe_apply_auto_coupon()` in the BUNDLE PLUGIN
 *      (`plugins/brave-hearts-bundle-pricing/includes/bundle-cart.php`) is a
 *      second auto-apply path. ⛔ The bundle plugin is a SEPARATE DEPLOYABLE,
 *      still at 1.8.76, and is not part of theme 1.19.337. Reaching into it
 *      from here would put a theme release's change inside a plugin artefact
 *      that this release does not ship or version.
 *      ⭐ THE WIRE IS PRE-BUILT AND IS ONE LINE. `bhp_coupon_apply_counter_record()`
 *        is hooked to the public action `bhp_coupon_auto_applied`, so that path
 *        joins the count the day somebody adds
 *        `do_action( 'bhp_coupon_auto_applied', $code, 'bundle' );` beside its
 *        own successful `apply_coupon()`. ⚠️ Until that ships, THE COUNT IS
 *        PARTIAL AND ANY READING OF IT MUST SAY SO.
 *
 *   ⛔ NOT COUNTED, ON PURPOSE: a code typed into the coupon box by hand. That
 *      is a different behaviour with a different meaning — the shopper already
 *      had the code — and folding it in would make the number unreadable.
 *
 * ---------------------------------------------------------------------------
 * ⛔ IT CAN NEVER BREAK A CART
 * ---------------------------------------------------------------------------
 * It runs on an action, after the discount is already on. Every write is
 * wrapped and every failure is silent. ⭐ A lost diagnostic count is a
 * measurement loss; a broken cart is a business loss, and this file is not
 * permitted to trade the second for the first.
 *
 * @package BraveHearts
 */

defined( 'ABSPATH' ) || exit;

/**
 * Normalise a coupon code to the key this counter stores it under.
 *
 * ⛔ ONE PLACE, BECAUSE THE WRITER AND THE READER MUST AGREE OR THE READER
 *    SILENTLY RETURNS ZERO. `wc_format_coupon_code()` applies WooCommerce's own
 *    `woocommerce_coupon_code` filter chain, which is what makes "EXAMPLE10" and
 *    "example10" the same coupon; `strtolower()` is what WooCommerce itself
 *    stores a coupon's `post_title` as.
 *
 * ⛔⛔ THE ILLUSTRATIVE CODE ABOVE IS A PLACEHOLDER AND MUST STAY ONE. THIS
 *    REPOSITORY IS PUBLIC ON GITHUB, and a real coupon code in a public file is
 *    a live discount handed to anyone who reads it. ⭐ Standing Rules §4.1 lists
 *    coupon codes as never-public, and `C6` is the open instance of exactly this
 *    defect elsewhere in this tree. ⚠️ **Do not "improve" this comment by naming
 *    an actual campaign code**, and do not put one in the suite either.
 *
 * ⚠️ `function_exists()`-GUARDED, AND NOT DEFENSIVELY FOR THE SAKE OF IT. The
 *   WRITER can only ever run inside a live WooCommerce cart, so it is safe
 *   there — but `bhp_coupon_apply_counter_total()` is a PUBLIC READER an
 *   operator will call from `wp eval` or a suite, potentially with WooCommerce
 *   inactive. Without the guard that read is a fatal.
 *
 * @param string $code Raw code.
 * @return string Storage key.
 */
function bhp_coupon_apply_counter_key( $code ) {
	$code = (string) $code;
	if ( function_exists( 'wc_format_coupon_code' ) ) {
		$code = wc_format_coupon_code( $code );
	}
	return strtolower( trim( $code ) );
}

/**
 * The option key.
 *
 * ⛔ ONE PLACE, because this string is the join between the writer, the reader,
 *    the pruner and every WP-CLI read an operator will ever do against it.
 *
 * @return string
 */
function bhp_coupon_apply_counter_option() {
	return 'bhp_coupon_autoapply_counts';
}

/**
 * How many days of history are kept.
 *
 * ⭐ 180 DAYS. Long enough to compare a campaign against the one before it,
 *    short enough that the option cannot grow without bound on a site that runs
 *    coupons for years. ⛔ AN UNPRUNED OPTION IS THE REAL RISK HERE: options are
 *    read on every request when autoloaded, and a counter that quietly becomes
 *    a megabyte is a performance defect wearing a diagnostic's clothes. This
 *    one is stored with autoload OFF as well — belt and braces.
 *
 * @return int
 */
function bhp_coupon_apply_counter_retention_days() {
	return (int) apply_filters( 'bhp_coupon_apply_counter_retention_days', 180 );
}

/**
 * The most distinct codes tracked at once.
 *
 * ⛔ A HARD CEILING, because the code that reaches the writer is normalised
 *    visitor input. `bhp_coupon_url_maybe_apply()` only ever calls this with a
 *    code it has already proven exists and is published, so garbage cannot get
 *    in through the shipped path — but a future caller might be less careful,
 *    and an unbounded key space in an option is how a table row becomes a
 *    denial of service. ⭐ When the cap is hit the OLDEST-TOUCHED code is
 *    dropped, never the newest, so an active campaign cannot be evicted by a
 *    burst of noise.
 *
 * @return int
 */
function bhp_coupon_apply_counter_max_codes() {
	return (int) apply_filters( 'bhp_coupon_apply_counter_max_codes', 50 );
}

/**
 * Today, in the site's own timezone.
 *
 * ⛔ `wp_date()`, NOT `date()` AND NOT `gmdate()`. The store's coupons run to
 *    the founder's calendar; a UTC day boundary would split a US evening's
 *    applies across two rows and make a one-day campaign look like two.
 *    `bhp_readaloud_scheduler_today()` makes the same call for the same reason.
 *
 * @return string `Y-m-d`.
 */
function bhp_coupon_apply_counter_today() {
	return (string) wp_date( 'Y-m-d' );
}

/**
 * Prune a counter map to the retention window and the code ceiling.
 *
 * ⭐ PURE. It takes a map and a cutoff and returns a map, so every boundary in
 *    it is an assertion in a suite instead of something you can only observe by
 *    waiting six months. Same discipline as
 *    `bhp_readaloud_scheduler_build_weeks()`.
 *
 * @param array  $map    Map of code => (date => count).
 * @param string $cutoff Earliest `Y-m-d` to keep, inclusive.
 * @param int    $max    Maximum distinct codes.
 * @return array
 */
function bhp_coupon_apply_counter_prune( $map, $cutoff, $max ) {
	if ( ! is_array( $map ) ) {
		return array();
	}

	$out = array();
	foreach ( $map as $code => $days ) {
		if ( ! is_array( $days ) ) {
			continue;
		}
		$kept = array();
		foreach ( $days as $day => $count ) {
			$day = (string) $day;
			// A malformed key cannot be compared meaningfully, so it is dropped.
			if ( 1 !== preg_match( '/^\d{4}-\d{2}-\d{2}$/', $day ) ) {
				continue;
			}
			if ( $day < $cutoff ) {
				continue;
			}
			$count = (int) $count;
			if ( $count > 0 ) {
				$kept[ $day ] = $count;
			}
		}
		if ( $kept ) {
			ksort( $kept ); // Oldest first, so the newest day is always last.
			$out[ (string) $code ] = $kept;
		}
	}

	$max = (int) $max;
	if ( $max > 0 && count( $out ) > $max ) {
		/*
		 * ⭐ EVICT BY LAST-TOUCHED, NEWEST KEPT. `array_key_last()` on a ksorted
		 *    day map is that code's most recent day, so a code nobody has used
		 *    since spring goes before a code used this morning.
		 */
		$last = array();
		foreach ( $out as $code => $days ) {
			$keys           = array_keys( $days );
			$last[ $code ] = (string) end( $keys );
		}
		arsort( $last );
		$keep = array_slice( array_keys( $last ), 0, $max );
		$out  = array_intersect_key( $out, array_flip( $keep ) );
	}

	return $out;
}

/**
 * Record one successful automatic application.
 *
 * ⚠️⚠️ THE HONEST LIMITATION, STATED RATHER THAN HIDDEN: this is a
 *   read-modify-write on a single option and it is NOT ATOMIC. Two applies in
 *   the same millisecond on two requests can each read the same value and one
 *   increment can be lost. ⛔ THAT IS ACCEPTED, DELIBERATELY, AND HERE IS WHY:
 *   the alternative is a custom table or a transient lock, which is real
 *   schema and real risk for a DIAGNOSTIC number whose whole purpose is an
 *   order-of-magnitude comparison against Woo's redeemed count. ⭐ A lost count
 *   biases the number DOWN, i.e. toward under-stating the "applied but did not
 *   buy" gap, which is the safe direction for a number that argues for spending
 *   attention. ⛔ IT MUST NOT BE PROMOTED INTO A FINANCIAL OR REPORTED FIGURE
 *   without being rebuilt on something atomic.
 *
 * ⭐ THE CACHE IS DROPPED BEFORE THE READ so a persistent object cache cannot
 *   serve a stale value into the increment. It narrows the window; it does not
 *   close it, and this comment does not pretend otherwise.
 *
 * @param string $code   The coupon code that went on.
 * @param string $source Which auto-apply path did it. Recorded in the action
 *                       only, deliberately NOT stored — see the scope note.
 * @return bool True if a count was written.
 */
function bhp_coupon_apply_counter_record( $code, $source = 'url' ) {
	$code = bhp_coupon_apply_counter_key( $code );
	if ( '' === $code ) {
		return false;
	}

	$option = bhp_coupon_apply_counter_option();
	$today  = bhp_coupon_apply_counter_today();

	try {
		wp_cache_delete( $option, 'options' );
		$map = get_option( $option, array() );
		if ( ! is_array( $map ) ) {
			$map = array();
		}

		if ( ! isset( $map[ $code ] ) || ! is_array( $map[ $code ] ) ) {
			$map[ $code ] = array();
		}
		$map[ $code ][ $today ] = ( isset( $map[ $code ][ $today ] ) ? (int) $map[ $code ][ $today ] : 0 ) + 1;

		$cutoff = (string) wp_date( 'Y-m-d', strtotime( '-' . bhp_coupon_apply_counter_retention_days() . ' days', (int) current_time( 'timestamp' ) ) );
		$map    = bhp_coupon_apply_counter_prune( $map, $cutoff, bhp_coupon_apply_counter_max_codes() );

		/*
		 * ⛔ `autoload` IS `no`, AND IT IS NOT OPTIONAL. An autoloaded option is
		 *    read into memory on EVERY request on the site, including every
		 *    cached page miss. A growing diagnostic counter must never be in
		 *    that set.
		 */
		update_option( $option, $map, false );
	} catch ( Throwable $exception ) {
		return false; // ⛔ Silent. A counter never costs a cart. See the header.
	}

	/**
	 * Fires after an automatic coupon application has been counted.
	 *
	 * ⭐ The observability hook. It carries the SOURCE, which the stored record
	 *    deliberately does not, so a future consumer can separate the paths
	 *    without the option growing a dimension it does not need today.
	 *
	 * @param string $code   Normalised coupon code.
	 * @param string $source Which auto-apply path applied it.
	 */
	do_action( 'bhp_coupon_apply_counted', $code, (string) $source );

	return true;
}

/**
 * The listener.
 *
 * ⭐ THE FEATURE FIRES AN ACTION AND THIS FILE LISTENS, rather than
 *    `coupon-url-apply.php` calling this function directly. ⛔ That indirection
 *    is what lets the bundle plugin's own auto-apply path join the count with
 *    one line and no coupling in either direction — see the scope note in the
 *    header. It is also what lets a test count applications without a cart.
 */
add_action( 'bhp_coupon_auto_applied', 'bhp_coupon_apply_counter_record', 10, 2 );

/**
 * Read the whole counter.
 *
 * ⭐ The operator's instrument. Over SSH:
 *
 *     wp option get bhp_coupon_autoapply_counts --format=json --user=1
 *
 * ⛔ READ-ONLY. Nothing in this function writes.
 *
 * @return array<string,array<string,int>>
 */
function bhp_coupon_apply_counter_all() {
	$map = get_option( bhp_coupon_apply_counter_option(), array() );
	return is_array( $map ) ? $map : array();
}

/**
 * Total applications for one code, optionally within a date window.
 *
 * ⛔ THE WINDOW IS INCLUSIVE AT BOTH ENDS and both bounds are optional, so a
 *    caller comparing against `wc_order_coupon_lookup` can align the two on the
 *    same dates rather than eyeballing them. See the header's warning about
 *    comparing a single day.
 *
 * @param string $code  Coupon code.
 * @param string $from  Optional `Y-m-d` lower bound.
 * @param string $to    Optional `Y-m-d` upper bound.
 * @return int
 */
function bhp_coupon_apply_counter_total( $code, $from = '', $to = '' ) {
	$code = bhp_coupon_apply_counter_key( $code );
	$map  = bhp_coupon_apply_counter_all();
	if ( '' === $code || ! isset( $map[ $code ] ) || ! is_array( $map[ $code ] ) ) {
		return 0;
	}

	$total = 0;
	foreach ( $map[ $code ] as $day => $count ) {
		$day = (string) $day;
		if ( '' !== $from && $day < $from ) {
			continue;
		}
		if ( '' !== $to && $day > $to ) {
			continue;
		}
		$total += (int) $count;
	}

	return $total;
}
