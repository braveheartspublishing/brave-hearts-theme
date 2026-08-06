<?php
/**
 * Canonical cost configuration for dashboard profit estimation.
 *
 * Single source of truth for every dollar figure the dashboard's profit
 * estimate depends on -- nothing here should ever be duplicated as a
 * literal number anywhere else in the dashboard code. Every value is
 * dated and labeled exact/estimated/unavailable so a stale or missing
 * assumption is visible instead of silently baked into a KPI.
 *
 * ---------------------------------------------------------------------
 * WHERE THE NUMBERS LIVE, AND WHY THEY ARE NOT IN THIS FILE (since 1.8.29)
 * ---------------------------------------------------------------------
 * This plugin is published in a public repository. Print costs, postage
 * quotes, processing assumptions and reserve rates are the company's
 * unit economics; committing them publishes the cost side of every price
 * on the storefront. So this file now carries the MODEL -- the keys,
 * the shapes, the provenance labels, the effective dates and all of the
 * costing logic -- and reads the AMOUNTS from a single site option:
 *
 *     get_option( 'bhp_cost_model' )
 *
 * The option is seeded per environment, out of band, by the operator.
 * It is never written by this plugin, never exported, never printed to a
 * page, and never committed. An unseeded environment is a supported,
 * loud state: every getter reports basis 'unavailable', every aggregate
 * reports cost_basis 'unavailable', and an admin notice says so.
 *
 * The shape of the option is exactly self::model_keys() -- a flat map of
 * dot-path key to float. Anything absent is unavailable, never zero-by-
 * assumption: a missing print cost that silently costed as $0 would
 * overstate profit, which is the specific failure this design prevents.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BHP_Cost_Config {

	/**
	 * Site option holding the unit-economics amounts. Seeded per
	 * environment by the operator; never written by this plugin.
	 */
	const MODEL_OPTION = 'bhp_cost_model';

	/**
	 * In-request cache so a dashboard render that touches the model
	 * dozens of times performs one option read, not dozens.
	 */
	protected static $model_cache = null;

	/**
	 * Every amount the model must supply, as a flat dot-path list. This
	 * IS the contract between this file and the seeded option: a key
	 * here and absent there is 'unavailable', and a key there and absent
	 * here is ignored. Keeping the list in code (and the values out of
	 * it) is what lets an unseeded environment name exactly what it is
	 * missing without the file naming any figure.
	 */
	public static function model_keys() {
		return array(
			'print_cost_per_unit.paperback',
			'print_cost_per_unit.hardcover',
			'observed_bundle_print_cost.2x_paperback',
			'observed_bundle_print_cost.3x_paperback',
			'observed_bundle_print_cost.2x_hardcover',
			'observed_bundle_print_cost.3x_hardcover',
			'bookvault_shipping_cost',
			'bookvault_postage.single_paperback',
			'bookvault_postage.multi_or_hardcover',
			'stripe_fee.percentage',
			'stripe_fee.fixed',
			'title_print_cost.paperback.mariana',
			'title_print_cost.paperback.everest',
			'title_print_cost.paperback.amazon',
			'title_print_cost.hardcover.mariana',
			'title_print_cost.hardcover.everest',
			'title_print_cost.hardcover.amazon',
			'refund_reserve.percentage',
			'replacement_reserve.percentage',
			'chargeback_reserve.percentage',
			'sample_allocation_per_unit',
		);
	}

	/**
	 * The seeded model, filtered. The filter exists so a private
	 * environment can supply the amounts from somewhere other than the
	 * option (an mu-plugin, a constant, a secrets file) without either
	 * the values or that mechanism entering this repository.
	 */
	public static function model() {
		if ( null === self::$model_cache ) {
			$stored = get_option( self::MODEL_OPTION, array() );
			if ( ! is_array( $stored ) ) {
				$stored = array();
			}
			/**
			 * Filter the unit-economics amount map.
			 *
			 * @param array $stored dot-path key => float
			 */
			self::$model_cache = (array) apply_filters( 'bhp_cost_model', $stored );
		}
		return self::$model_cache;
	}

	/**
	 * Drop the in-request cache. Used by tests that swap the model.
	 */
	public static function flush_model_cache() {
		self::$model_cache = null;
	}

	/**
	 * Which model keys are missing. Empty array means fully seeded.
	 */
	public static function missing_model_keys() {
		$model   = self::model();
		$missing = array();
		foreach ( self::model_keys() as $key ) {
			if ( ! isset( $model[ $key ] ) || ! is_numeric( $model[ $key ] ) ) {
				$missing[] = $key;
			}
		}
		return $missing;
	}

	/**
	 * True only when every key in model_keys() resolves to a number.
	 * Partial seeding is NOT "seeded" -- a half-populated model produces
	 * arithmetic that looks complete and is not.
	 */
	public static function is_seeded() {
		return array() === self::missing_model_keys();
	}

	/**
	 * One amount, or null when the model does not supply it.
	 */
	protected static function amount( $key ) {
		$model = self::model();
		return isset( $model[ $key ] ) && is_numeric( $model[ $key ] ) ? (float) $model[ $key ] : null;
	}

	/**
	 * One amount as a float safe for arithmetic. Callers MUST check the
	 * accompanying 'basis' before treating the result as meaningful --
	 * 0.0 here means "unavailable", never "free".
	 */
	protected static function amount_or_zero( $key ) {
		$value = self::amount( $key );
		return null === $value ? 0.0 : $value;
	}

	/**
	 * The provenance label for one key: the supplied label when the
	 * amount exists, 'unavailable' when it does not.
	 */
	protected static function basis( $key, $label ) {
		return null === self::amount( $key ) ? 'unavailable' : $label;
	}

	/**
	 * Print cost per unit, keyed by format. Source: prior Bookvault test
	 * order quotes (see docs/DECISIONS.md and the Phase 3 Bookvault
	 * go/no-go reports). These are per-book print costs, not shipping.
	 */
	public static function print_cost_per_unit() {
		return array(
			'paperback' => array(
				'amount'         => self::amount_or_zero( 'print_cost_per_unit.paperback' ),
				'basis'          => self::basis( 'print_cost_per_unit.paperback', 'estimated' ),
				'source'         => 'Bookvault test-order quotes, prior Bookvault verification phases',
				'effective_date' => '2026-07-05',
				'note'           => 'Midpoint of the observed per-paperback range, used until a confirmed per-order value is available.',
			),
			'hardcover' => array(
				'amount'         => self::amount_or_zero( 'print_cost_per_unit.hardcover' ),
				'basis'          => self::basis( 'print_cost_per_unit.hardcover', 'estimated' ),
				'source'         => 'Bookvault test-order quotes, prior Bookvault verification phases',
				'effective_date' => '2026-07-05',
				'note'           => 'Midpoint of the observed per-hardcover range, used until a confirmed per-order value is available.',
			),
		);
	}

	/**
	 * Multi-book production cost quotes actually observed during testing,
	 * kept for reference/cross-check against the per-unit figures above.
	 * The dashboard computes multi-unit print cost as quantity x per-unit
	 * cost rather than re-deriving from these bundle quotes, since
	 * per-unit is the more composable primitive -- these are retained
	 * only as a documented sanity-check source.
	 */
	public static function observed_bundle_print_cost_quotes() {
		$quotes = array();
		foreach ( array( '2x_paperback', '3x_paperback', '2x_hardcover', '3x_hardcover' ) as $key ) {
			$path            = 'observed_bundle_print_cost.' . $key;
			$quotes[ $key ] = array(
				'amount'         => self::amount_or_zero( $path ),
				'basis'          => self::basis( $path, 'estimated' ),
				'effective_date' => '2026-07-05',
			);
		}
		return $quotes;
	}

	/**
	 * Bookvault fulfillment/shipping cost -- the multi-book Media Mail
	 * quote observed during testing. Applied once per order (not per
	 * unit) since it's a single shipment cost regardless of book count
	 * within the tested multi-book quotes.
	 */
	public static function bookvault_shipping_cost() {
		return array(
			'amount'         => self::amount_or_zero( 'bookvault_shipping_cost' ),
			'basis'          => self::basis( 'bookvault_shipping_cost', 'estimated' ),
			'source'         => 'Multi-book Media Mail quote observed during Bookvault testing',
			'effective_date' => '2026-07-05',
			'note'           => 'Same quoted figure observed for both paperback and hardcover multi-book shipments in prior testing; applied per order.',
		);
	}

	/**
	 * Stripe processing fee assumption -- standard published US rate,
	 * not a merchant-specific confirmed rate.
	 */
	public static function stripe_fee_formula() {
		return array(
			'percentage'     => self::amount_or_zero( 'stripe_fee.percentage' ),
			'fixed'          => self::amount_or_zero( 'stripe_fee.fixed' ),
			'basis'          => ( null === self::amount( 'stripe_fee.percentage' ) || null === self::amount( 'stripe_fee.fixed' ) ) ? 'unavailable' : 'estimated',
			'source'         => "Stripe's standard published US rate; not confirmed against this account's actual negotiated rate",
			'effective_date' => '2026-07-05',
		);
	}

	public static function estimate_stripe_fee( $order_total ) {
		$formula = self::stripe_fee_formula();
		if ( 'unavailable' === $formula['basis'] ) {
			return 0.0;
		}
		return round( ( (float) $order_total * $formula['percentage'] ) + $formula['fixed'], 2 );
	}

	/**
	 * Per-title, per-format print cost (Phase 1A). Source: Andrew's
	 * Phase 1A working economics figures (2026-07-06), consistent with
	 * the previously observed per-format ranges -- this table is the
	 * primary costing input wherever the specific title(s) in an
	 * order/offer are known, because "do not assume every two-book
	 * combination has the same print cost" is only possible with
	 * per-title data. print_cost_per_unit() above remains for any caller
	 * that only knows a format+quantity, not specific titles.
	 */
	public static function title_print_cost() {
		$sources = array(
			'paperback' => array(
				'mariana' => "Andrew's Phase 1A working economics figures, consistent with prior Bookvault test-order quotes",
				'everest' => "Andrew's Phase 1A working economics figures, consistent with prior Bookvault test-order quotes",
				'amazon'  => "Andrew's Phase 1A working economics figures -- explicitly flagged as using the Everest figure as a placeholder pending a title-specific Bookvault quote for The Amazon",
			),
			'hardcover' => array(
				'mariana' => "Andrew's Phase 1A working economics figures, consistent with prior Bookvault test-order quotes",
				'everest' => "Andrew's Phase 1A working economics figures, consistent with prior Bookvault test-order quotes",
				'amazon'  => "Andrew's Phase 1A working economics figures",
			),
		);

		$table = array();
		foreach ( $sources as $format => $titles ) {
			foreach ( $titles as $title_key => $source ) {
				$path = 'title_print_cost.' . $format . '.' . $title_key;
				$table[ $format ][ $title_key ] = array(
					'amount'         => self::amount_or_zero( $path ),
					'basis'          => self::basis( $path, 'estimated' ),
					'source'         => $source,
					'effective_date' => '2026-07-06',
				);
			}
		}
		return $table;
	}

	/**
	 * Print cost for a specific set of distinct titles in one format.
	 * For 3 distinct titles (a complete collection), the OBSERVED combined
	 * Bookvault quote (observed_bundle_print_cost_quotes()) is used in
	 * preference to summing the three per-title figures: the observed
	 * quote is a real multi-book quote, while the per-title sum is a
	 * composed estimate that can differ by a few cents (quote rounding
	 * and granularity, not a modeling error). For 1 or 2 distinct titles,
	 * no observed quote exists per specific combination, so the per-title
	 * figures are summed directly -- this correctly distinguishes one
	 * two-book combination from another rather than applying one flat
	 * number to every pair.
	 *
	 * If any requested title has no amount in the model for this format,
	 * the cost for that title is UNKNOWN, not zero -- the returned
	 * 'basis' is 'unknown' and the amount excludes the unmapped title(s),
	 * so a caller must check 'basis' before trusting 'amount' as complete.
	 * Silently treating an unmapped title as $0 would understate real
	 * print cost and overstate profit for that combination.
	 *
	 * @param string   $format      'paperback' | 'hardcover'
	 * @param string[] $title_keys  distinct title keys present, e.g. ['everest','amazon']
	 * @return array { @type float $amount, @type string $basis, @type string $source, @type string[] $unknown_titles }
	 */
	public static function combo_print_cost( $format, array $title_keys ) {
		$title_keys = array_values( array_unique( $title_keys ) );
		$count = count( $title_keys );

		if ( 3 === $count ) {
			$quotes = self::observed_bundle_print_cost_quotes();
			$key = ( '3x_' . $format );
			if ( isset( $quotes[ $key ] ) ) {
				return array(
					'amount'          => $quotes[ $key ]['amount'],
					'basis'           => $quotes[ $key ]['basis'],
					'source'          => 'Observed combined Bookvault quote for all three titles (preferred over summing per-title figures)',
					'unknown_titles'  => array(),
				);
			}
		}

		$titles = self::title_print_cost();
		$sum = 0.0;
		$unknown_titles = array();
		$unavailable = false;
		foreach ( $title_keys as $key ) {
			if ( isset( $titles[ $format ][ $key ] ) ) {
				$sum += (float) $titles[ $format ][ $key ]['amount'];
				if ( 'unavailable' === $titles[ $format ][ $key ]['basis'] ) {
					$unavailable = true;
				}
			} else {
				$unknown_titles[] = $key;
			}
		}

		if ( empty( $unknown_titles ) && $unavailable ) {
			$basis = 'unavailable';
		} elseif ( empty( $unknown_titles ) ) {
			$basis = 'estimated';
		} else {
			$basis = 'unknown';
		}

		return array(
			'amount'         => round( $sum, 2 ),
			'basis'          => $basis,
			'source'         => empty( $unknown_titles )
				? 'Sum of per-title print cost figures (' . implode( '+', $title_keys ) . ')'
				: 'Incomplete: no print cost mapping for ' . implode( ', ', $unknown_titles ) . ' -- amount reflects only the mapped title(s), not the full combination',
			'unknown_titles' => $unknown_titles,
		);
	}

	/**
	 * Bookvault fulfillment/shipping (postage) cost, which is NOT a flat
	 * figure across every order -- a single-paperback shipment and a
	 * multi-book/hardcover shipment are separately evidenced amounts, and
	 * this function is the single place that distinction is made.
	 *
	 * CORRECTED 2026-08-06 -- THE ORDERING WAS BACKWARDS. This docblock
	 * previously read that "Phase 1A working economics show a single
	 * paperback order costs more to ship than any bundle or a single
	 * hardcover order." That is REFUTED. It is quoted here rather than
	 * deleted so it is not re-derived from some other stale carrier.
	 * Both figures are now ACTUAL, from two separate real shipments
	 * (see the 'source' strings below), and the multi-book/hardcover
	 * figure is the LARGER of the two. Any code, comment, document or
	 * test that assumes the reverse is stale -- the invariant in
	 * tests/test-cost-model-source.php was one such carrier and was
	 * flipped in the same change as this comment.
	 *
	 * The 'basis' labels below are deliberately UNCHANGED by that
	 * correction. Basis vocabulary is a model-wide concept; promoting
	 * these two entries out of 'estimated' is a separate reviewed
	 * decision, not a side effect of re-citing their provenance.
	 *
	 * @param string $format               'paperback' | 'hardcover'
	 * @param int    $distinct_title_count how many distinct catalog titles are in the order (1 = a single, 2 or 3 = a bundle/collection)
	 * @return array { @type float $amount, @type string $basis, @type string $source, @type string $effective_date }
	 */
	public static function bookvault_postage_for_offer( $format, $distinct_title_count ) {
		if ( 'paperback' === $format && 1 === (int) $distinct_title_count ) {
			return array(
				'amount'         => self::amount_or_zero( 'bookvault_postage.single_paperback' ),
				'basis'          => self::basis( 'bookvault_postage.single_paperback', 'estimated' ),
				'source'         => 'ACTUAL -- founder test order: a single paperback copy ordered by Andrew Signore through the live store and shipped by Bookvault; attested by him 2026-08-06',
				'effective_date' => '2026-08-06',
			);
		}
		return array(
			'amount'         => self::amount_or_zero( 'bookvault_postage.multi_or_hardcover' ),
			'basis'          => self::basis( 'bookvault_postage.multi_or_hardcover', 'estimated' ),
			'source'         => 'ACTUAL -- Bookvault invoiced order 2848396, 2026-08-06; also applied to single-hardcover orders',
			'effective_date' => '2026-08-06',
		);
	}

	/**
	 * The Bookvault postage figure for a real order, given the distinct
	 * title count present per format. Only a paperback-only order with
	 * exactly one distinct title gets the single-paperback figure; every
	 * other combination (any hardcover present, or 2-3 distinct titles)
	 * uses the bundle/single-hardcover figure. The routing is unchanged;
	 * only the word "higher" was removed from this sentence on
	 * 2026-08-06, because it asserted an ordering that is refuted -- see
	 * bookvault_postage_for_offer() above.
	 *
	 * @param int $paperback_distinct_count
	 * @param int $hardcover_distinct_count
	 */
	public static function bookvault_postage_for_order( $paperback_distinct_count, $hardcover_distinct_count ) {
		if ( 1 === (int) $paperback_distinct_count && 0 === (int) $hardcover_distinct_count ) {
			return self::bookvault_postage_for_offer( 'paperback', 1 );
		}
		return self::bookvault_postage_for_offer( 'hardcover', 1 ); // any non-single-paperback-only case resolves to the multi/hardcover figure
	}

	/**
	 * Reserve assumptions (Phase 1A) -- APPROVED 2026-07-06 by Andrew as
	 * provisional PLANNING assumptions, not as historical/observed rates
	 * (this store has no statistically meaningful genuine-refund sample to
	 * derive a rate from -- see docstring below). Configurable holdbacks
	 * used in the PROSPECTIVE offer-economics/CPA model
	 * (BHP_Offer_Economics) and, as a conservative supplementary
	 * forward-looking figure, alongside (never instead of) a real period's
	 * ACTUAL refund data in the dashboard's executive summary -- see
	 * docs/economics-model.md for why these two uses are kept conceptually
	 * separate, and for the before/after-reserves comparison Andrew
	 * approved these to support.
	 *
	 * Defaults are deliberately conservative estimates, not a derived
	 * empirical rate: as of 2026-07-06 this store has exactly one refund
	 * record in its entire verified-customer-order history, and that
	 * order is itself a confirmed internal test (see
	 * docs/order-provenance-audit.md) -- with zero genuine customer refund
	 * events observed, there is no statistically meaningful sample to
	 * derive a rate from ("do not invent unsupported precision"). Andrew
	 * has approved these rates for planning purposes only; MUST be
	 * reviewed and replaced with an empirical rate once genuine
	 * customer-order volume is sufficient (a rule of thumb: at least ~100
	 * genuine paid orders) -- see docs/economics-model.md.
	 */
	public static function refund_reserve_percentage() {
		return array(
			'percentage'     => self::amount_or_zero( 'refund_reserve.percentage' ),
			'basis'          => self::basis( 'refund_reserve.percentage', 'estimated' ),
			'source'         => 'Andrew-approved (2026-07-06) provisional planning assumption, not a historical/observed rate; conservative industry-typical default for physical children\'s-book retail. Review once genuine customer-order volume is sufficient (see docstring).',
			'effective_date' => '2026-07-06',
		);
	}

	public static function replacement_reserve_percentage() {
		return array(
			'percentage'     => self::amount_or_zero( 'replacement_reserve.percentage' ),
			'basis'          => self::basis( 'replacement_reserve.percentage', 'estimated' ),
			'source'         => 'Andrew-approved (2026-07-06) provisional planning assumption, not a historical/observed rate; conservative default for lost/damaged-in-transit replacements -- print cost per unit is low, so this is a small holdback. Review once genuine customer-order volume is sufficient (see docstring).',
			'effective_date' => '2026-07-06',
		);
	}

	/**
	 * Andrew's explicit decision (2026-07-06) not to apply a chargeback
	 * reserve yet, rather than guessing a rate with even less supporting
	 * data than the refund/replacement reserves above. Kept as its own
	 * configurable, centralized value (not simply removed) so it can be
	 * set to a real rate later without a code change, and so
	 * BHP_Offer_Economics::reserve_amount()'s optional chargeback
	 * parameter continues to have a documented source.
	 */
	public static function chargeback_reserve_percentage() {
		return array(
			'percentage'     => self::amount_or_zero( 'chargeback_reserve.percentage' ),
			'basis'          => self::basis( 'chargeback_reserve.percentage', 'estimated' ),
			'source'         => 'Andrew-approved (2026-07-06): not yet observed on this store and not estimated pending real data; review once genuine customer-order volume is sufficient',
			'effective_date' => '2026-07-06',
		);
	}

	/**
	 * Per-unit creator/sample allocation. No creator sampling program
	 * exists yet (explicitly out of scope for Phase 1A) -- present as a
	 * documented, labeled placeholder so the TikTok formulas in
	 * BHP_Offer_Economics have a real config key to read from once
	 * sampling begins, rather than a magic constant buried in a formula.
	 */
	public static function sample_allocation_per_unit() {
		return array(
			'amount'         => self::amount_or_zero( 'sample_allocation_per_unit' ),
			'basis'          => self::basis( 'sample_allocation_per_unit', 'not_applicable' ),
			'source'         => 'No creator sampling program exists yet -- placeholder for a future Phase',
			'effective_date' => '2026-07-06',
		);
	}

	/**
	 * Full cost/profit breakdown for one order given its format unit
	 * counts. $units = array( 'paperback' => int, 'hardcover' => int ).
	 * Every input dollar amount (revenue, shipping, discount) must be
	 * supplied by the caller from the real order -- this function never
	 * invents a revenue figure.
	 *
	 * If the cost model is unseeded, every cost field is returned as null
	 * and 'cost_basis' is 'unavailable'. It is never returned as a number
	 * computed against zero costs, which would report the full order
	 * total as profit.
	 */
	public static function estimate_order_profit( $product_revenue, $shipping_collected, $discount_total, $order_total, $units ) {
		if ( ! self::is_seeded() ) {
			return self::unavailable_profit( $product_revenue, $shipping_collected, $discount_total );
		}

		$print_costs = self::print_cost_per_unit();
		$shipping_cost = self::bookvault_shipping_cost();

		$print_cost_total = 0.0;
		foreach ( $units as $format => $qty ) {
			if ( isset( $print_costs[ $format ] ) && $qty > 0 ) {
				$print_cost_total += $print_costs[ $format ]['amount'] * (int) $qty;
			}
		}

		$stripe_fee = self::estimate_stripe_fee( $order_total );

		$estimated_profit = ( (float) $product_revenue + (float) $shipping_collected )
			- (float) $discount_total
			- $stripe_fee
			- $print_cost_total
			- $shipping_cost['amount'];

		return array(
			'product_revenue'       => round( (float) $product_revenue, 2 ),
			'shipping_collected'    => round( (float) $shipping_collected, 2 ),
			'discount_total'        => round( (float) $discount_total, 2 ),
			'estimated_stripe_fee'  => $stripe_fee,
			'estimated_print_cost'  => round( $print_cost_total, 2 ),
			'estimated_ship_cost'   => $shipping_cost['amount'],
			'estimated_profit'      => round( $estimated_profit, 2 ),
			'cost_basis'            => 'estimated', // never 'exact' until real Bookvault invoice data is wired in
		);
	}

	/**
	 * Exact-costing counterpart to estimate_order_profit(): uses the
	 * specific titles present (via combo_print_cost(), so one two-book
	 * combination can cost a few cents more than another) and the
	 * order-composition-aware postage (via bookvault_postage_for_order())
	 * instead of a flat per-unit average and a flat postage figure.
	 * estimate_order_profit() is retained, unchanged in contract, for
	 * callers that only know format+quantity.
	 *
	 * If any unit's title has no print-cost mapping, that unit's cost is
	 * excluded from 'estimated_print_cost' (never silently treated as $0)
	 * and 'cost_basis' is 'unknown' instead of 'estimated' -- callers must
	 * check this before treating 'estimated_profit' as complete/trustworthy.
	 *
	 * @param float $product_revenue
	 * @param float $shipping_collected
	 * @param float $discount_total
	 * @param float $order_total
	 * @param array $paperback_titles title_key => qty map (may be empty)
	 * @param array $hardcover_titles title_key => qty map (may be empty)
	 */
	public static function estimate_order_profit_precise( $product_revenue, $shipping_collected, $discount_total, $order_total, array $paperback_titles, array $hardcover_titles ) {
		if ( ! self::is_seeded() ) {
			$out = self::unavailable_profit( $product_revenue, $shipping_collected, $discount_total );
			$out['unknown_titles'] = array();
			return $out;
		}

		$pb_distinct = array_keys( $paperback_titles );
		$hc_distinct = array_keys( $hardcover_titles );

		$print_cost_total = 0.0;
		$unknown_titles = array();
		foreach ( $paperback_titles as $title_key => $qty ) {
			$combo = self::combo_print_cost( 'paperback', array( $title_key ) );
			$print_cost_total += $combo['amount'] * (int) $qty;
			if ( 'unknown' === $combo['basis'] ) {
				$unknown_titles[] = 'paperback:' . $title_key;
			}
		}
		foreach ( $hardcover_titles as $title_key => $qty ) {
			$combo = self::combo_print_cost( 'hardcover', array( $title_key ) );
			$print_cost_total += $combo['amount'] * (int) $qty;
			if ( 'unknown' === $combo['basis'] ) {
				$unknown_titles[] = 'hardcover:' . $title_key;
			}
		}

		$postage = self::bookvault_postage_for_order( count( $pb_distinct ), count( $hc_distinct ) );
		$stripe_fee = self::estimate_stripe_fee( $order_total );

		$estimated_profit = ( (float) $product_revenue + (float) $shipping_collected )
			- (float) $discount_total
			- $stripe_fee
			- $print_cost_total
			- $postage['amount'];

		return array(
			'product_revenue'       => round( (float) $product_revenue, 2 ),
			'shipping_collected'    => round( (float) $shipping_collected, 2 ),
			'discount_total'        => round( (float) $discount_total, 2 ),
			'estimated_stripe_fee'  => $stripe_fee,
			'estimated_print_cost'  => round( $print_cost_total, 2 ),
			'estimated_ship_cost'   => $postage['amount'],
			'estimated_profit'      => round( $estimated_profit, 2 ),
			'cost_basis'            => empty( $unknown_titles ) ? 'estimated' : 'unknown', // 'estimated' never means 'exact' -- see docs/economics-model.md
			'unknown_titles'        => $unknown_titles,
		);
	}

	/**
	 * The shape both profit functions return when the model is unseeded.
	 * Revenue-side fields are still echoed back, because they come from
	 * the real order and are known; every cost-side field is null and the
	 * basis says so.
	 */
	protected static function unavailable_profit( $product_revenue, $shipping_collected, $discount_total ) {
		return array(
			'product_revenue'       => round( (float) $product_revenue, 2 ),
			'shipping_collected'    => round( (float) $shipping_collected, 2 ),
			'discount_total'        => round( (float) $discount_total, 2 ),
			'estimated_stripe_fee'  => null,
			'estimated_print_cost'  => null,
			'estimated_ship_cost'   => null,
			'estimated_profit'      => null,
			'cost_basis'            => 'unavailable',
		);
	}

	/**
	 * Admin notice for an unseeded (or partially seeded) environment.
	 * Registered by dashboard-bootstrap.php. Names the option and the
	 * count of missing keys -- never a value, and never a figure.
	 */
	public static function render_unseeded_notice() {
		if ( self::is_seeded() || ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}
		$missing = self::missing_model_keys();
		echo '<div class="notice notice-warning"><p><strong>' .
			esc_html__( 'Brave Hearts dashboard: unit-economics model not configured.', 'bhp-bundle-pricing' ) .
			'</strong> ' .
			esc_html(
				sprintf(
					/* translators: 1: number of missing keys, 2: total keys, 3: option name */
					__( '%1$d of %2$d cost values are missing from the %3$s option, so profit, contribution and reserve figures are reported as unavailable rather than estimated. Seed the option for this environment to restore them.', 'bhp-bundle-pricing' ),
					count( $missing ),
					count( self::model_keys() ),
					self::MODEL_OPTION
				)
			) .
			'</p></div>';
	}
}
