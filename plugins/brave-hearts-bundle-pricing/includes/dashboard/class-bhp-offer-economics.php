<?php
/**
 * Brave Hearts Phase 1A economics layer -- the product-cost,
 * contribution-margin, and allowable-acquisition-cost (CPA) model that
 * will later support website, Meta, TikTok Shop, and affiliate
 * profitability decisions. See docs/economics-model.md for the full
 * write-up; this class is the single computable source every number in
 * that document and on the dashboard's economics panels is drawn from.
 *
 * Two deliberately DIFFERENT contribution figures exist and must never be
 * confused:
 *
 * - Real-order profit (BHP_Cost_Config::estimate_order_profit_precise())
 *   = revenue + shipping - discount - Stripe fee - print cost - postage.
 *   This is what the dashboard shows for REAL, already-placed, executive-
 *   eligible orders (see BHP_Order_Provenance), and intentionally does
 *   NOT subtract a refund/replacement reserve, because a real order's
 *   actual refund status (if any) is already known via BHP_Refund_Metrics
 *   -- reserving against a risk that either already resolved or didn't
 *   happen would double-count it.
 *
 * - "Contribution before acquisition" (this class) = estimated gross
 *   profit - refund reserve - replacement reserve, per the Phase 1A
 *   WEBSITE ORDER formula. This is a PROSPECTIVE figure: it models a
 *   *hypothetical future* order of a given offer type, before knowing
 *   whether it will be refunded or need a replacement, which is exactly
 *   the situation a CPA/break-even decision is made in. It is only ever
 *   computed at the offer-type level (this class), never substituted into
 *   a real order's actual profit calculation.
 *
 * All prices/discounts/shipping are read from bundle-data.php (the
 * approved storefront pricing) rather than re-typed here, so this file
 * cannot silently drift from what customers are actually charged.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BHP_Offer_Economics {

	const SINGLE_PAPERBACK       = 'single_paperback';
	const SINGLE_HARDCOVER       = 'single_hardcover';
	const TWO_PAPERBACK_BUNDLE   = 'two_paperback_bundle';
	const TWO_HARDCOVER_BUNDLE   = 'two_hardcover_bundle';
	const COMPLETE_PAPERBACK_SET = 'complete_paperback_set';
	const COMPLETE_HARDCOVER_SET = 'complete_hardcover_collection';

	const STRATEGIC_ORGANIC_ONLY   = 'organic_search_only';
	const STRATEGIC_RETARGETING    = 'retargeting_candidate';
	const STRATEGIC_COLD           = 'cold_acquisition_candidate';
	const STRATEGIC_PREMIUM_GIFT   = 'premium_gift_candidate';
	const STRATEGIC_UPSELL         = 'upsell_candidate';

	public static function strategic_labels() {
		return array(
			self::STRATEGIC_ORGANIC_ONLY => __( 'Organic / search only', 'bhp-bundle-pricing' ),
			self::STRATEGIC_RETARGETING  => __( 'Retargeting candidate', 'bhp-bundle-pricing' ),
			self::STRATEGIC_COLD         => __( 'Cold acquisition candidate', 'bhp-bundle-pricing' ),
			self::STRATEGIC_PREMIUM_GIFT => __( 'Premium / gift candidate', 'bhp-bundle-pricing' ),
			self::STRATEGIC_UPSELL       => __( 'Upsell candidate', 'bhp-bundle-pricing' ),
		);
	}

	/**
	 * All distinct 2-title combinations possible from the 3-title catalog,
	 * per format. Order-independent (title keys sorted) so a combination
	 * is never accidentally listed twice under a different key order.
	 */
	public static function two_title_combinations() {
		$titles = array( 'mariana', 'everest', 'amazon' );
		$combos = array();
		for ( $i = 0; $i < count( $titles ); $i++ ) {
			for ( $j = $i + 1; $j < count( $titles ); $j++ ) {
				$combos[] = array( $titles[ $i ], $titles[ $j ] );
			}
		}
		return $combos;
	}

	/**
	 * Contribution margin, expressed as a percentage of what the customer
	 * actually pays (price + shipping collected) -- null if there is no
	 * revenue to divide by, never a divide-by-zero.
	 */
	private static function margin_pct( $contribution, $price, $shipping_collected ) {
		$revenue = (float) $price + (float) $shipping_collected;
		if ( $revenue <= 0 ) {
			return null;
		}
		return round( ( $contribution / $revenue ) * 100, 1 );
	}

	/**
	 * One row per real, purchasable SKU (Phase 1A: "one row per SKU").
	 * Pulls price/ISBN directly from bhp_bundle_catalog() and
	 * bhp_bundle_expected_price() -- never a re-typed literal -- so this
	 * table cannot drift from the live storefront catalog definition.
	 */
	public static function sku_table() {
		if ( ! function_exists( 'bhp_bundle_catalog' ) ) {
			return array();
		}
		$catalog = bhp_bundle_catalog();
		$rows = array();

		foreach ( $catalog as $format => $titles ) {
			foreach ( $titles as $title_key => $info ) {
				$price    = bhp_bundle_expected_price( $format );
				$shipping = bhp_bundle_single_shipping( $format );
				$print    = BHP_Cost_Config::combo_print_cost( $format, array( $title_key ) );
				$postage  = BHP_Cost_Config::bookvault_postage_for_offer( $format, 1 );
				$order_total = $price + $shipping;
				$stripe   = BHP_Cost_Config::estimate_stripe_fee( $order_total );

				$gross_profit = ( $price + $shipping ) - $stripe - $print['amount'] - $postage['amount'];
				$reserves     = self::reserve_amount( $price, $shipping );
				$contribution = $gross_profit - $reserves['total'];
				$has_unknown  = ! empty( $print['unknown_titles'] );

				$rows[] = array(
					'sku_key'                        => $format . ':' . $title_key,
					'title_key'                       => $title_key,
					'title_label'                     => $info['label'],
					'format'                          => $format,
					'product_id'                      => $info['product_id'],
					'variation_id'                    => $info['variation_id'],
					'isbn'                            => $info['isbn'],
					'price'                           => round( $price, 2 ),
					'shipping_collected'              => round( $shipping, 2 ),
					'print_cost'                      => $print,
					'postage'                         => $postage,
					'stripe_fee'                      => $stripe,
					'estimated_gross_profit'          => round( $gross_profit, 2 ),
					'reserves'                        => $reserves,
					'contribution_before_acquisition' => round( $contribution, 2 ),
					'contribution_margin_pct'         => self::margin_pct( $contribution, $price, $shipping ),
					'basis'                           => $has_unknown ? 'unknown' : ( BHP_Cost_Config::is_seeded() ? 'estimated' : 'unavailable' ), // price/shipping are actual (live WC); print/postage/reserves are estimated unless a title mapping is missing or the cost model is unseeded
				);
			}
		}
		return $rows;
	}

	/**
	 * Per-order reserve holdback (refund + replacement + optional
	 * chargeback), applied as a percentage of (price + shipping collected)
	 * -- i.e. of what the customer actually pays, matching how a reserve
	 * fund would actually be sized against real cash collected. Returned
	 * as a breakdown so the dashboard can show each component, not just
	 * the total.
	 */
	public static function reserve_amount( $price, $shipping_collected, $include_chargeback = false ) {
		$base = (float) $price + (float) $shipping_collected;
		$refund_cfg      = BHP_Cost_Config::refund_reserve_percentage();
		$replacement_cfg = BHP_Cost_Config::replacement_reserve_percentage();
		$chargeback_cfg  = BHP_Cost_Config::chargeback_reserve_percentage();

		$refund_amt      = round( $base * $refund_cfg['percentage'], 2 );
		$replacement_amt = round( $base * $replacement_cfg['percentage'], 2 );
		$chargeback_amt  = $include_chargeback ? round( $base * $chargeback_cfg['percentage'], 2 ) : 0.0;

		return array(
			'refund_amount'      => $refund_amt,
			'replacement_amount' => $replacement_amt,
			'chargeback_amount'  => $chargeback_amt,
			'total'              => round( $refund_amt + $replacement_amt + $chargeback_amt, 2 ),
			'basis'              => BHP_Cost_Config::is_seeded() ? 'estimated' : 'unavailable',
		);
	}

	/**
	 * One row per real, purchasable offer (Phase 1A: "one row per
	 * offer"), including every distinct 2-book combination separately --
	 * a two-paperback bundle of Everest+Amazon is NOT assumed to cost the
	 * same to print as Mariana+Everest.
	 */
	public static function offer_table() {
		$rows = array();

		foreach ( array( 'paperback', 'hardcover' ) as $format ) {
			// Singles
			$catalog = function_exists( 'bhp_bundle_catalog' ) ? bhp_bundle_catalog() : array();
			foreach ( ( $catalog[ $format ] ?? array() ) as $title_key => $info ) {
				$rows[] = self::build_offer_row(
					'single_' . $format,
					'paperback' === $format ? self::SINGLE_PAPERBACK : self::SINGLE_HARDCOVER,
					$format,
					array( $title_key ),
					bhp_bundle_expected_price( $format ),
					bhp_bundle_single_shipping( $format ),
					0.0
				);
			}

			// Two-title bundles -- every real combination, priced/discounted
			// per bhp_bundle_rules(), print-costed per the specific pair.
			$rules = function_exists( 'bhp_bundle_rules' ) ? bhp_bundle_rules( $format ) : array();
			if ( isset( $rules[2] ) ) {
				foreach ( self::two_title_combinations() as $combo ) {
					$price = ( 2 * bhp_bundle_expected_price( $format ) ) - $rules[2]['discount'];
					$rows[] = self::build_offer_row(
						'two_' . $format . '_bundle',
						'paperback' === $format ? self::TWO_PAPERBACK_BUNDLE : self::TWO_HARDCOVER_BUNDLE,
						$format,
						$combo,
						$price,
						$rules[2]['shipping'],
						$rules[2]['discount']
					);
				}
			}

			// Complete collection (all three titles)
			if ( isset( $rules[3] ) ) {
				$price = ( 3 * bhp_bundle_expected_price( $format ) ) - $rules[3]['discount'];
				$rows[] = self::build_offer_row(
					'complete_' . $format . '_collection',
					'paperback' === $format ? self::COMPLETE_PAPERBACK_SET : self::COMPLETE_HARDCOVER_SET,
					$format,
					array( 'mariana', 'everest', 'amazon' ),
					$price,
					$rules[3]['shipping'],
					$rules[3]['discount']
				);
			}
		}

		return $rows;
	}

	private static function build_offer_row( $offer_key, $offer_type, $format, array $title_keys, $price, $shipping, $discount ) {
		$print       = BHP_Cost_Config::combo_print_cost( $format, $title_keys );
		$postage     = BHP_Cost_Config::bookvault_postage_for_offer( $format, count( array_unique( $title_keys ) ) );
		$order_total = $price + $shipping;
		$stripe      = BHP_Cost_Config::estimate_stripe_fee( $order_total );

		// product_revenue (pre-discount subtotal) + shipping - discount -
		// stripe - print - postage, matching BHP_Cost_Config's real-order
		// formula exactly so the two are always comparable.
		$subtotal = ( count( $title_keys ) * self::price_for_format( $format ) );
		$gross_profit = ( $subtotal + $shipping ) - $discount - $stripe - $print['amount'] - $postage['amount'];

		$reserves     = self::reserve_amount( $price, $shipping );
		$contribution = $gross_profit - $reserves['total'];
		$has_unknown  = ! empty( $print['unknown_titles'] );

		return array(
			'offer_key'                       => $offer_key . ':' . implode( '+', $title_keys ),
			'offer_type'                      => $offer_type,
			'format'                          => $format,
			'title_keys'                      => $title_keys,
			'price'                           => round( $price, 2 ),
			'shipping_collected'              => round( $shipping, 2 ),
			'discount'                        => round( $discount, 2 ),
			'print_cost'                      => $print,
			'postage'                         => $postage,
			'stripe_fee'                      => $stripe,
			'estimated_gross_profit'          => round( $gross_profit, 2 ),
			'reserves'                        => $reserves,
			'contribution_before_acquisition' => round( $contribution, 2 ),
			'contribution_margin_pct'         => self::margin_pct( $contribution, $price, $shipping ),
			'basis'                           => $has_unknown ? 'unknown' : ( BHP_Cost_Config::is_seeded() ? 'estimated' : 'unavailable' ),
		);
	}

	private static function price_for_format( $format ) {
		return function_exists( 'bhp_bundle_expected_price' ) ? bhp_bundle_expected_price( $format ) : 0.0;
	}

	/**
	 * Website-channel formula (Phase 1A), applied to a single
	 * already-computed contribution-before-acquisition figure. Kept as an
	 * explicit named function (rather than inline subtraction wherever
	 * it's needed) so "contribution after acquisition" is always computed
	 * the same way, and so a null/unknown acquisition cost is handled
	 * consistently rather than ad hoc.
	 *
	 * @param float      $contribution_before_acquisition
	 * @param float|null $attributed_acquisition_cost  null/0 = no ad spend attributed (organic/no channel connected yet)
	 */
	public static function contribution_after_acquisition( $contribution_before_acquisition, $attributed_acquisition_cost ) {
		$spend = null === $attributed_acquisition_cost ? 0.0 : (float) $attributed_acquisition_cost;
		return round( (float) $contribution_before_acquisition - $spend, 2 );
	}

	/**
	 * Meta-channel formula (Phase 1A): identical shape to the website
	 * formula (website contribution before acquisition minus attributed
	 * Meta spend) -- kept as its own named function per the spec's
	 * explicit "META WEBSITE ORDER" formula, even though today it
	 * delegates to the same math, so a future Meta-specific adjustment has
	 * an obvious, single place to be added without touching the generic
	 * acquisition-cost formula used elsewhere.
	 */
	public static function meta_contribution_after_acquisition( $website_contribution_before_acquisition, $attributed_meta_spend ) {
		return self::contribution_after_acquisition( $website_contribution_before_acquisition, $attributed_meta_spend );
	}

	/**
	 * TikTok Shop organic-affiliate-order formula (Phase 1A). Pure
	 * function, not wired to any live TikTok data -- no TikTok Shop
	 * integration exists yet (explicitly out of scope for Phase 1A). This
	 * exists so the formula is defined, documented, and testable now, and
	 * can be plugged into real TikTok order data later without redesigning
	 * the math. All channel-fee inputs are explicit parameters, never
	 * hard-coded, since none of TikTok's actual fee percentages have been
	 * confirmed for this account yet.
	 *
	 * @param float $product_revenue_retained  what Brave Hearts actually keeps of the item price after TikTok's own price adjustments, before fees
	 * @param float $shipping_retained
	 * @param float $tiktok_platform_fee        TikTok's platform/referral fee for this order
	 * @param float $affiliate_commission       organic affiliate's commission for this order
	 * @param float $print_cost
	 * @param float $postage
	 * @param float $discounts
	 * @param float $refund_reserve
	 * @param float $replacement_reserve
	 * @param float $allocated_sample_cost      amortized per-unit creator sample cost, if any
	 */
	public static function tiktok_organic_contribution(
		$product_revenue_retained,
		$shipping_retained,
		$tiktok_platform_fee,
		$affiliate_commission,
		$print_cost,
		$postage,
		$discounts,
		$refund_reserve,
		$replacement_reserve,
		$allocated_sample_cost
	) {
		$contribution = (float) $product_revenue_retained
			+ (float) $shipping_retained
			- (float) $tiktok_platform_fee
			- (float) $affiliate_commission
			- (float) $print_cost
			- (float) $postage
			- (float) $discounts
			- (float) $refund_reserve
			- (float) $replacement_reserve
			- (float) $allocated_sample_cost;
		return round( $contribution, 2 );
	}

	/**
	 * TikTok Shop paid-affiliate (Shop Ads) order formula (Phase 1A). Same
	 * shape as the organic formula but with Shop Ads commission instead of
	 * organic affiliate commission, plus attributed TikTok ad spend --
	 * deliberately a SEPARATE function (not a flag on the organic one) so
	 * "organic affiliate commission" and "Shop Ads commission" can never
	 * be silently mixed, per the explicit instruction not to conflate them.
	 */
	public static function tiktok_paid_contribution(
		$product_revenue_retained,
		$shipping_retained,
		$tiktok_platform_fee,
		$shop_ads_commission,
		$print_cost,
		$postage,
		$discounts,
		$refund_reserve,
		$replacement_reserve,
		$allocated_sample_cost,
		$attributed_tiktok_ad_spend
	) {
		$before_ads = self::tiktok_organic_contribution(
			$product_revenue_retained,
			$shipping_retained,
			$tiktok_platform_fee,
			$shop_ads_commission,
			$print_cost,
			$postage,
			$discounts,
			$refund_reserve,
			$replacement_reserve,
			$allocated_sample_cost
		);
		return round( $before_ads - (float) $attributed_tiktok_ad_spend, 2 );
	}
}