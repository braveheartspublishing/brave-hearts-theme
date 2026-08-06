<?php
/**
 * Classifies a WooCommerce order into exactly one primary offer type.
 *
 * Classification is driven entirely by product/variation IDs against the
 * same canonical catalog bhp_bundle_catalog() already uses for discount
 * eligibility (bundle-data.php) -- never by order discount labels or
 * product title text, per the Phase 4 spec. The pure classify_items()
 * function takes a plain array so it can be unit-tested without
 * constructing a real WC_Order.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BHP_Offer_Classifier {

	const SINGLE_PAPERBACK          = 'single_paperback';
	const SINGLE_HARDCOVER          = 'single_hardcover';
	const TWO_PAPERBACK_BUNDLE      = 'two_paperback_bundle';
	const COMPLETE_PAPERBACK_SET    = 'complete_paperback_set';
	const TWO_HARDCOVER_BUNDLE      = 'two_hardcover_bundle';
	const COMPLETE_HARDCOVER_SET    = 'complete_hardcover_collection';
	const MIXED_FORMAT              = 'mixed_format_order';
	const BOTH_COMPLETE             = 'both_complete_collections';
	const LEGACY_PRECATALOG         = 'legacy_pre_catalog';
	const OTHER                     = 'other_needs_review';

	/**
	 * Product IDs that predate the current variation-based catalog and are
	 * therefore never recognized by bhp_bundle_catalog()/catalog_index(),
	 * but are documented, understood, non-ambiguous cases -- not a
	 * classification defect. Confirmed via direct order inspection
	 * (2026-07-06): product ID 12 is a legacy, pre-variation "Mariana
	 * Trench Paperback" simple product (no SKU set) used by orders #318,
	 * #321, and one line item of #317, all placed before the catalog
	 * migrated this title to a variable product (current catalog ID:
	 * variation 334 under product 333). An order using ONLY legacy IDs is
	 * labeled LEGACY_PRECATALOG rather than the vaguer OTHER, so "needs
	 * review" is reserved for genuinely ambiguous/unrecognized records.
	 */
	const KNOWN_LEGACY_PRODUCT_IDS = array( 12 );

	public static function labels() {
		return array(
			self::SINGLE_PAPERBACK       => 'Single paperback',
			self::SINGLE_HARDCOVER       => 'Single hardcover',
			self::TWO_PAPERBACK_BUNDLE   => 'Two-paperback bundle',
			self::COMPLETE_PAPERBACK_SET => 'Complete paperback set',
			self::TWO_HARDCOVER_BUNDLE   => 'Two-hardcover bundle',
			self::COMPLETE_HARDCOVER_SET => 'Complete hardcover collection',
			self::MIXED_FORMAT           => 'Mixed-format order',
			self::BOTH_COMPLETE          => 'Both complete collections',
			self::LEGACY_PRECATALOG      => 'Legacy / pre-catalog',
			self::OTHER                  => 'Other / needs review',
		);
	}

	/**
	 * Flattened { catalog_id => array('format' => ..., 'title_key' => ...) }
	 * built once from bhp_bundle_catalog(), so lookup is a single array
	 * access per order line item rather than a nested loop each time.
	 */
	public static function catalog_index() {
		static $index = null;
		if ( null !== $index ) {
			return $index;
		}
		$index = array();
		if ( ! function_exists( 'bhp_bundle_catalog' ) ) {
			return $index;
		}
		foreach ( bhp_bundle_catalog() as $format => $titles ) {
			foreach ( $titles as $title_key => $info ) {
				$id = $info['variation_id'] ? (int) $info['variation_id'] : (int) $info['product_id'];
				$index[ $id ] = array( 'format' => $format, 'title_key' => $title_key );
			}
		}
		return $index;
	}

	public static function all_catalog_product_ids() {
		return array_keys( self::catalog_index() );
	}

	/**
	 * @param array $items List of ['id' => int, 'quantity' => int] --
	 *              id is the variation ID for variable products, or the
	 *              product ID for simple products (matching Store API /
	 *              WC_Order_Item_Product convention used everywhere else
	 *              in this plugin).
	 * @return array {
	 *     @type string $offer_type
	 *     @type string $offer_label
	 *     @type int    $distinct_paperback
	 *     @type int    $distinct_hardcover
	 *     @type int    $units_paperback
	 *     @type int    $units_hardcover
	 *     @type int    $units_non_catalog
	 *     @type bool   $has_duplicate_units
	 * }
	 */
	public static function classify_items( $items ) {
		$index = self::catalog_index();

		$pb_titles = array(); // title_key => qty
		$hc_titles = array();
		$non_catalog_units = 0;
		$legacy_units      = 0;
		$unknown_units     = 0;

		foreach ( $items as $item ) {
			$id  = isset( $item['id'] ) ? (int) $item['id'] : 0;
			$qty = isset( $item['quantity'] ) ? (int) $item['quantity'] : 0;

			if ( ! isset( $index[ $id ] ) ) {
				$non_catalog_units += $qty;
				if ( in_array( $id, self::KNOWN_LEGACY_PRODUCT_IDS, true ) ) {
					$legacy_units += $qty;
				} else {
					$unknown_units += $qty;
				}
				continue;
			}

			$match = $index[ $id ];
			if ( 'paperback' === $match['format'] ) {
				$pb_titles[ $match['title_key'] ] = ( $pb_titles[ $match['title_key'] ] ?? 0 ) + $qty;
			} else {
				$hc_titles[ $match['title_key'] ] = ( $hc_titles[ $match['title_key'] ] ?? 0 ) + $qty;
			}
		}

		$distinct_pb = count( $pb_titles );
		$distinct_hc = count( $hc_titles );
		$units_pb    = array_sum( $pb_titles );
		$units_hc    = array_sum( $hc_titles );

		$has_duplicate_units = ( $units_pb > $distinct_pb ) || ( $units_hc > $distinct_hc );

		$result = array(
			'distinct_paperback'   => $distinct_pb,
			'distinct_hardcover'   => $distinct_hc,
			'units_paperback'      => $units_pb,
			'units_hardcover'      => $units_hc,
			'units_non_catalog'    => $non_catalog_units,
			'has_duplicate_units'  => $has_duplicate_units,
			// title_key => qty maps (Phase 1A economics) -- e.g. an
			// Everest+Amazon paperback order costs more to print than a
			// Mariana+Everest one, so the aggregate counts above alone are
			// not enough to cost an order precisely. See
			// BHP_Cost_Config::estimate_order_profit_precise().
			'paperback_titles'     => $pb_titles,
			'hardcover_titles'     => $hc_titles,
		);

		$offer_type = self::determine_offer_type( $distinct_pb, $distinct_hc, $units_pb, $units_hc, $legacy_units, $unknown_units );

		$labels = self::labels();
		$result['offer_type']  = $offer_type;
		$result['offer_label'] = $labels[ $offer_type ];

		return $result;
	}

	private static function determine_offer_type( $distinct_pb, $distinct_hc, $units_pb, $units_hc, $legacy_units = 0, $unknown_units = 0 ) {
		if ( 0 === $distinct_pb && 0 === $distinct_hc ) {
			// No catalog items at all. If every non-catalog unit is a known
			// legacy/pre-catalog product ID, this is a documented historical
			// case, not an ambiguous one -- reserve OTHER for orders that
			// contain at least one genuinely unrecognized product ID.
			if ( $legacy_units > 0 && 0 === $unknown_units ) {
				return self::LEGACY_PRECATALOG;
			}
			return self::OTHER;
		}

		if ( $distinct_pb > 0 && $distinct_hc > 0 ) {
			if ( 3 === $distinct_pb && 3 === $distinct_hc ) {
				return self::BOTH_COMPLETE;
			}
			return self::MIXED_FORMAT;
		}

		if ( $distinct_pb > 0 ) {
			if ( 1 === $distinct_pb ) {
				return ( 1 === $units_pb ) ? self::SINGLE_PAPERBACK : self::OTHER; // duplicate qty of one title, no bundle earned
			}
			if ( 2 === $distinct_pb ) {
				return self::TWO_PAPERBACK_BUNDLE;
			}
			if ( 3 === $distinct_pb ) {
				return self::COMPLETE_PAPERBACK_SET;
			}
		}

		if ( $distinct_hc > 0 ) {
			if ( 1 === $distinct_hc ) {
				return ( 1 === $units_hc ) ? self::SINGLE_HARDCOVER : self::OTHER;
			}
			if ( 2 === $distinct_hc ) {
				return self::TWO_HARDCOVER_BUNDLE;
			}
			if ( 3 === $distinct_hc ) {
				return self::COMPLETE_HARDCOVER_SET;
			}
		}

		return self::OTHER;
	}

	/**
	 * @param WC_Order $order
	 */
	public static function classify_order( $order ) {
		if ( ! $order instanceof WC_Order ) {
			return self::classify_items( array() );
		}
		$items = array();
		foreach ( $order->get_items() as $item ) {
			if ( ! $item instanceof WC_Order_Item_Product ) {
				continue;
			}
			$id = $item->get_variation_id() ? $item->get_variation_id() : $item->get_product_id();
			$items[] = array( 'id' => (int) $id, 'quantity' => (int) $item->get_quantity() );
		}
		return self::classify_items( $items );
	}
}
