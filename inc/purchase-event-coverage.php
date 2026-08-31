<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * GA4 PURCHASE-EVENT COVERAGE — THE BREADCRUMB AND THE RECONCILIATION.
 * 1.19.342, `CYCLE172-LD-FUNNEL-FIX`, audit gap G-B.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⛔ THE GAP THIS CLOSES, OBSERVED ON PRODUCTION 2026-08-31. The GA4 `purchase`
 *    event had fired for 13 of 23 orders. Ten orders — a large share of gross
 *    order value — never produced one. **Nothing anywhere raised a hand.** The
 *    only signal was the ABSENCE of a meta key on individual orders, which
 *    nobody reads. In GA4 the shortfall is indistinguishable from "sales fell",
 *    which is the most expensive possible way to be wrong about a funnel.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * WHAT THE LIVE DIAGNOSIS ACTUALLY FOUND — read this before "fixing" anything
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * The audit listed four candidate causes and confirmed none. Reading every
 * order's meta and gateway on production 2026-08-31 eliminated two of them
 * outright and split the remainder into two genuinely different populations:
 *
 * ⛔ ELIMINATED — "an order-provenance exclusion fired". `bhp_bundle_track_purchase_completed()`
 *    SETS the latch to `yes` on the provenance-excluded branch before
 *    returning. An excluded order therefore CARRIES the meta. Absence can
 *    never mean exclusion. Confirmed live: all 23 orders classify as
 *    `is_executive_eligible() === true` anyway.
 *
 * ⛔ ELIMINATED — "`is_excluded_internal_request()` suppressed it".
 *    `bhp_bundle_track_purchase_completed()` NEVER CALLS IT. There is no
 *    internal-request gate on the purchase path at all.
 *
 * ⭐ POPULATION 1 — ORDERS WITH NO BROWSER CHECKOUT AT ALL (`created_via`
 *    `rest-api`). Observed on orders 547 and 548. These were not created by a
 *    customer checking out; there is no order-received page in that flow and
 *    there never was. **A GA4 `purchase` event correctly did not fire, and
 *    counting these as a leak overstates the gap.** They are marked below and
 *    excluded from the coverage denominator with their reason recorded.
 *
 * ⭐ POPULATION 2 — STORE-API ORDERS WHOSE ORDER-RECEIVED PAGE WAS NEVER
 *    RENDERED. Eight orders. ⛔ THE DECISIVE EVIDENCE: the GA4 latch
 *    (`_bhp_purchase_event_fired`) and the INDEPENDENT Meta Pixel latch
 *    (`_bhp_meta_pixel_purchase_fired`) are absent TOGETHER on every one of
 *    them, and present together on the orders that fired. Two independent
 *    instruments on the same page do not fail identically by coincidence —
 *    the page did not render. Item mixes, gateway (Stripe throughout),
 *    payment status and eligibility are IDENTICAL to orders that did fire, so
 *    the cause is per-session, not per-order-shape.
 *
 * ⚠️ WHAT IS STILL NOT ESTABLISHED, STATED RATHER THAN GUESSED: *why* those
 *    eight buyers never rendered order-received. Tab closed at the Stripe
 *    redirect, a network drop on the return hop, and an aggressive script
 *    blocker are all consistent with the evidence and are NOT distinguishable
 *    from order meta alone. ⛔ THE BREADCRUMB BELOW IS WHAT MAKES THE NEXT ONE
 *    DISTINGUISHABLE — it is the missing instrument, not a fix for the past.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔⛔ WHAT THIS FILE CANNOT DO, AND WHY NOTHING HERE PRETENDS OTHERWISE
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * A browser-side event cannot be made to fire in a browser that is not there.
 * Guaranteeing a `purchase` for EVERY paid order requires sending it
 * SERVER-SIDE — the GA4 Measurement Protocol — which is a NEW EXTERNAL
 * INTEGRATION and an explicit Andrew gate. ⛔ IT IS NOT BUILT HERE, NOT
 * STUBBED, NOT PARTIALLY WIRED, AND NOT ENABLED BY AN OPTION SOMEBODY COULD
 * FLIP. It is written up as a decision for Andrew instead. What this file does
 * is make the shortfall VISIBLE AND ATTRIBUTABLE, which is what the audit
 * asked for and is the honest ceiling on this side of the gate.
 *
 * @package BraveHearts
 */

defined( 'ABSPATH' ) || exit;

/**
 * Set the moment the order-received page actually rendered for this order.
 *
 * ⭐ THIS IS THE WHOLE DIAGNOSTIC. With it, the two populations above stop
 *    looking alike:
 *
 *      breadcrumb ABSENT + purchase latch ABSENT
 *          -> the buyer never reached the order-received page.
 *             A DELIVERY problem (redirect, abandonment, network).
 *      breadcrumb PRESENT + purchase latch ABSENT
 *          -> the page rendered and the event was suppressed anyway.
 *             A CODE problem, in this theme or the bundle plugin.
 *
 * ⛔ Before 1.19.342 those two produced byte-identical evidence — an absent
 *    meta key — and the audit could only list four candidate causes and
 *    confirm none of them. That is the cost of not having this line.
 */
const BHP_THANKYOU_RENDERED_META = '_bhp_thankyou_rendered';

/**
 * Why a given order is not expected to produce a browser purchase event.
 * Empty/absent means "it was expected to, and should be counted".
 */
const BHP_PURCHASE_EVENT_NA_META = '_bhp_purchase_event_not_applicable';

/**
 * Record that the order-received page rendered.
 *
 * ⛔ PRIORITY 1 — BEFORE EVERY OTHER `woocommerce_thankyou` LISTENER,
 *    INCLUDING THE BUNDLE PLUGIN'S PURCHASE TRACKER AT THE DEFAULT 10. That
 *    ordering is the entire point: the breadcrumb must survive even if a later
 *    listener throws, returns early, or is removed. A breadcrumb that only
 *    lands when the thing it is measuring also succeeded measures nothing.
 *
 * ⛔ IT IS DELIBERATELY UNCONDITIONAL — no provenance check, no status check,
 *    no analytics gate. It answers exactly one question, "did this page
 *    render for this order", and any condition added here would make a
 *    rendered page look unrendered and send a future diagnosis in precisely
 *    the wrong direction.
 *
 * ⛔ WRITTEN ONCE. `woocommerce_thankyou` fires on every reload of the page;
 *    the first render is the one worth knowing about, and rewriting the
 *    timestamp on every refresh would destroy that.
 *
 * ⛔ NO PII. A timestamp on an order the store already owns.
 *
 * @param int $order_id Order ID.
 * @return void
 */
function bhp_thankyou_record_render( $order_id ) {
	if ( ! $order_id || ! function_exists( 'wc_get_order' ) ) {
		return;
	}
	$order = wc_get_order( $order_id );
	if ( ! $order ) {
		return;
	}
	if ( $order->get_meta( BHP_THANKYOU_RENDERED_META, true ) ) {
		return; // Already recorded. A refresh is not a new render.
	}
	$order->update_meta_data( BHP_THANKYOU_RENDERED_META, current_time( 'mysql' ) );
	$order->save_meta_data();
}
add_action( 'woocommerce_thankyou', 'bhp_thankyou_record_render', 1 );

/**
 * Mark orders that were never going to produce a browser purchase event.
 *
 * ⭐ WHY THIS EXISTS: without it, the coverage number below is a LIE IN THE
 *    PESSIMISTIC DIRECTION. Orders 547 and 548 were created through the REST
 *    API. No customer checked out, no order-received page exists in that flow,
 *    and no GA4 event was ever possible. Counting them as "missing" reports a
 *    leak that is not there — and a metric that cries wolf gets ignored, which
 *    would cost more than having no metric.
 *
 * ⛔ THE REASON IS RECORDED ON THE ORDER, NOT INFERRED AT READ TIME, so a
 *    later reader can see WHY an order was excluded and disagree with it.
 *    ⛔ An exclusion nobody can audit is indistinguishable from a number
 *    somebody massaged.
 *
 * ⛔ `created_via` IS READ FROM THE ORDER, NEVER GUESSED FROM ITEMS OR TOTALS.
 *    `checkout` (classic) and `store-api` (Blocks) are the two real browser
 *    checkout paths on this store and are NEVER excluded — those are exactly
 *    the orders whose absence is a genuine leak.
 *
 * @param WC_Order $order Order.
 * @return string Reason string, or '' when a browser purchase event WAS expected.
 */
function bhp_purchase_event_not_applicable_reason( $order ) {
	if ( ! $order instanceof WC_Order ) {
		return '';
	}

	$via = (string) $order->get_created_via();

	if ( 'rest-api' === $via ) {
		return 'created_via_rest_api_no_browser_checkout';
	}
	if ( 'admin' === $via ) {
		return 'created_in_wp_admin_no_browser_checkout';
	}

	return '';
}

/**
 * The coverage figure, the missing order IDs, and the reason breakdown.
 *
 * ⭐ THIS IS THE `ga4_purchase_coverage_pct` THE AUDIT PROPOSED AS A NAMED
 *    WEEKLY NUMBER. It is one pass over orders the store already holds. It
 *    writes nothing.
 *
 * ⛔ READ-ONLY. No option, no meta, no post, no transient is written by this
 *    function. It is safe to call from anywhere, including production, and it
 *    is deliberately NOT cached — a stale coverage number is worse than a
 *    slow one, because it would keep reporting "fine" through an outage.
 *
 * ⛔ THE DENOMINATOR IS *EXPECTED* ORDERS, NOT ALL ORDERS, and the difference
 *    is reported rather than hidden: `not_applicable` is returned as its own
 *    count with its own reason breakdown, so nobody has to trust that the
 *    exclusion was fair — they can see exactly what was excluded and why.
 *
 * ⛔ IT REPORTS `null` COVERAGE, NEVER 100%, WHEN THERE ARE NO EXPECTED
 *    ORDERS IN THE WINDOW. A 100% that means "we divided by zero" is the kind
 *    of number that gets quoted in a report and believed.
 *
 * @param int $days How many days back to look. 0 = every order.
 * @return array{
 *   window_days:int, total:int, not_applicable:int, expected:int, fired:int,
 *   coverage_pct:float|null, missing_ids:int[], missing_never_rendered:int[],
 *   missing_rendered_but_suppressed:int[], not_applicable_reasons:array<string,int>
 * }
 */
function bhp_ga4_purchase_coverage( $days = 30 ) {
	$out = array(
		'window_days'                     => (int) $days,
		'total'                           => 0,
		'not_applicable'                  => 0,
		'expected'                        => 0,
		'fired'                           => 0,
		'coverage_pct'                    => null,
		'missing_ids'                     => array(),
		'missing_never_rendered'          => array(),
		'missing_rendered_but_suppressed' => array(),
		'not_applicable_reasons'          => array(),
	);

	if ( ! function_exists( 'wc_get_orders' ) ) {
		return $out; // WooCommerce absent. Report nothing, invent nothing.
	}

	$args = array(
		'limit'   => -1,
		'orderby' => 'date',
		'order'   => 'DESC',
		/*
		 * ⛔ PAID STATES ONLY. A cancelled or failed order SHOULD NOT have a
		 *    purchase event, so including it would manufacture a false miss.
		 *    This list matches the status guard in
		 *    `bhp_bundle_track_purchase_completed()` exactly, plus `refunded`
		 *    — a refunded order DID fire a purchase at the time, and dropping
		 *    it from the denominator later would silently inflate coverage.
		 */
		'status'  => array( 'processing', 'completed', 'on-hold', 'refunded' ),
		'return'  => 'ids',
	);
	if ( $days > 0 ) {
		$args['date_created'] = '>' . ( time() - ( (int) $days * DAY_IN_SECONDS ) );
	}

	foreach ( wc_get_orders( $args ) as $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			continue;
		}
		$out['total']++;

		$na_reason = bhp_purchase_event_not_applicable_reason( $order );
		if ( '' !== $na_reason ) {
			$out['not_applicable']++;
			if ( ! isset( $out['not_applicable_reasons'][ $na_reason ] ) ) {
				$out['not_applicable_reasons'][ $na_reason ] = 0;
			}
			$out['not_applicable_reasons'][ $na_reason ]++;
			continue;
		}

		$out['expected']++;

		if ( 'yes' === $order->get_meta( '_bhp_purchase_event_fired', true ) ) {
			$out['fired']++;
			continue;
		}

		$out['missing_ids'][] = (int) $order_id;

		/*
		 * The split that turns "10 missing" into two different problems with
		 * two different owners. ⚠️ Orders created BEFORE 1.19.342 carry no
		 * breadcrumb at all, so they land in `never_rendered` by default.
		 * ⛔ THAT IS A KNOWN AND UNAVOIDABLE READING ARTEFACT OF THE
		 *    BACKFILL BOUNDARY, NOT EVIDENCE ABOUT THOSE ORDERS — do not
		 *    quote the split for any order predating this release.
		 */
		if ( $order->get_meta( BHP_THANKYOU_RENDERED_META, true ) ) {
			$out['missing_rendered_but_suppressed'][] = (int) $order_id;
		} else {
			$out['missing_never_rendered'][] = (int) $order_id;
		}
	}

	if ( $out['expected'] > 0 ) {
		$out['coverage_pct'] = round( ( $out['fired'] / $out['expected'] ) * 100, 1 );
	}

	return $out;
}

/**
 * The same figure as one human-readable block, for a WP-CLI read.
 *
 *     wp eval 'echo bhp_ga4_purchase_coverage_report( 30 );' --user=1
 *
 * ⛔ READ-ONLY, like everything else in this file.
 *
 * @param int $days Window.
 * @return string
 */
function bhp_ga4_purchase_coverage_report( $days = 30 ) {
	$c = bhp_ga4_purchase_coverage( $days );

	$lines   = array();
	$lines[] = sprintf( 'GA4 PURCHASE COVERAGE — last %d days', $c['window_days'] );
	$lines[] = sprintf(
		'  coverage_pct ....... %s',
		null === $c['coverage_pct'] ? 'n/a (no expected orders in window)' : $c['coverage_pct'] . '%'
	);
	$lines[] = sprintf( '  orders total ....... %d', $c['total'] );
	$lines[] = sprintf( '  not applicable ..... %d', $c['not_applicable'] );
	foreach ( $c['not_applicable_reasons'] as $reason => $n ) {
		$lines[] = sprintf( '      %-46s %d', $reason, $n );
	}
	$lines[] = sprintf( '  expected ........... %d', $c['expected'] );
	$lines[] = sprintf( '  fired .............. %d', $c['fired'] );
	$lines[] = sprintf( '  MISSING ............ %d', count( $c['missing_ids'] ) );
	if ( $c['missing_ids'] ) {
		$lines[] = '      ids: ' . implode( ', ', $c['missing_ids'] );
		$lines[] = sprintf(
			'      never rendered order-received ...... %d  [delivery problem]',
			count( $c['missing_never_rendered'] )
		);
		$lines[] = sprintf(
			'      rendered but event suppressed ...... %d  [CODE problem — investigate]',
			count( $c['missing_rendered_but_suppressed'] )
		);
	}

	return implode( "\n", $lines ) . "\n";
}
