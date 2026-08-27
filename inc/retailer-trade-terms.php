<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * THE RETAILER / BOOKSELLER TRADE-TERMS REGISTRY — theme 1.19.304, 2026-08-27,
 * `CYCLE167-LD-RETAILER-PAGE`.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⭐ WHAT THIS FILE IS. The single place that answers one question: WHICH
 *    EDITIONS MAY A BOOKSELLER BE TOLD TO ORDER TODAY, and on what terms. It is
 *    read by `page-audience-retailers.php` and by nothing else.
 *
 * ---------------------------------------------------------------------------
 * ⭐⭐ WHY IT IS AN ALLOWLIST AND NOT A FILTER OVER THE CATALOG
 * ---------------------------------------------------------------------------
 * `bhp_bundle_catalog()` holds SIX chapter-book editions. Only FIVE of them can
 * be ordered through Ingram. The sixth — The Amazon HARDCOVER, `9798996810833`
 * — sits in Ingram's `Processing` state with `Enabled for Distribution: No` and
 * NO production date. A seventh ISBN exists for the colouring book,
 * `9798996810840`, in the same state.
 *
 * ⛔⛔ AN ISBN PRINTED ON A RETAILER PAGE IS A PROMISE THAT A PROFESSIONAL CAN
 *    ORDER THAT BOOK TODAY. A buyer who searches an ISBN in ipage and finds
 *    nothing does not email to ask why; they close the tab. That is the single
 *    worst failure this page can have, and it is why the set is an ALLOWLIST
 *    that a future catalog addition CANNOT silently join. Adding a title to
 *    `bhp_bundle_catalog()` must never publish it to the trade by accident.
 *
 * ⭐ FAIL-CLOSED, TWICE OVER. A row renders only when its ISBN is (a) in this
 *    registry with `orderable => true` AND (b) resolvable to a real edition in
 *    `bhp_bundle_catalog()`. The title string is taken FROM THE CATALOG, never
 *    retyped here, so the ISBN-to-title mapping has exactly one source.
 *
 * ---------------------------------------------------------------------------
 * ⭐⭐⭐ PROVENANCE OF EVERY FIGURE BELOW — read this before changing one
 * ---------------------------------------------------------------------------
 * ⭐ SOURCE: `Business OS\WORKING-DRAFTS\connected-operator\
 *    CYCLE167-GIM-INGRAM-READ-2-2026-08-27.md` §2 and §2.1.
 * ⭐ INSTRUMENT: Gimli (`connected-operator`) read each title's own
 *    distribution page at `/Titles/TitleDetails/<id>/B` inside the
 *    AUTHENTICATED IngramSpark account `9885354`, 2026-08-27 ~04:40 MDT.
 * ⭐ CLASS: OBSERVED LIVE, per that document's own claim ledger (§9).
 * ⚠️ RELAYED TO THIS FILE, NOT WITNESSED BY THIS DESK. No agent here logged
 *    into IngramSpark; the read was performed by the one role that may, and
 *    this file carries it with its date so nobody mistakes it for fresh.
 *
 * ⭐⭐ THE 55% CONTRADICTION IS CLOSED BY THAT READ, AND THE CLOSURE IS WHY
 *    THESE COLUMNS EXIST AT ALL. `17-CURRENT-OPERATING-STATE.md` said BOTH
 *    "55% CONFIRMED (FD-319)" (L4520) and "NOT a live field value, must never
 *    be reported as Ingram's current setting" (L6094, L6185) — the conflict
 *    Merry logged as `CYCLE167-MKT-T03` and correctly refused to resolve from
 *    documents. It was not resolved by picking a document. It was resolved by
 *    somebody opening the account and reading the field. That is §9.2.
 *
 * ⛔⛔ ONE CORRECTION THE RECORD NEEDED, CARRIED HERE SO IT CANNOT BE LOST:
 *    the returnable flag is **`Yes - Destroy`**, NOT the `Yes-Deliver` that
 *    `DRAFT-2026-08-19-INGRAM-PULL.md` recorded. Material, and it is the row
 *    booksellers care about most. Gimli's §6 C1.
 *
 * ⛔ NOTHING IS GLOSSED. `Yes - Destroy` is rendered as Ingram's own words.
 *    A plain-English explanation of what happens to a returned copy would be a
 *    statement about INGRAM'S returns process, which nobody in this company has
 *    verified, so it is NOT written. Stating the field is sourced; explaining
 *    the field would be invention.
 *
 * ⛔ NO TERM IS INVENTED HERE AND NONE MAY BE ADDED WITHOUT A LIVE READ.
 *    Minimum order quantity, lead time, freight, margin, trim size, page count,
 *    BISAC, imprint and carton quantity are ABSENT — every one of them is blank
 *    in the corpus (Merry §6, FMC-5) and a plausible number would be a
 *    fabrication. Ingram's own "free freight on 20+ units" is Ingram's offer to
 *    the store, NOT Brave Hearts', and restating it here would make it read as
 *    ours (`CYCLE167-MKT-T02`).
 *
 * ⚠️ RECHECK BY 2026-11-27 for discount and returns, 2026-09-27 for price —
 *    the recheck dates Gimli's own claim ledger sets. A term is a live field,
 *    and this file is a dated copy of one.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The trade registry, keyed by ISBN-13.
 *
 * ⛔ `orderable => false` rows are DELIBERATELY PRESENT rather than deleted.
 *    A row that is merely missing looks like an oversight and invites somebody
 *    to "fix" it by adding the title back. A row that says WHY it is withheld,
 *    with the live status that withheld it, defends itself.
 *
 * @return array<string,array<string,mixed>>
 */
function bhp_retailer_trade_registry() {
	$registry = array(

		// ── ORDERABLE: Title Available, Enabled for Distribution = Yes ──────
		'9798234014016' => array(
			'orderable'    => true,
			'format'       => 'paperback',
			'format_label' => __( 'Paperback', 'brave-hearts' ),
			'list_us'      => '12.99',
			'discount'     => '55%',
			'returnable'   => 'Yes - Destroy',
			'ingram_status'=> 'Title Available',
			'source'       => 'CYCLE167-GIM-INGRAM-READ-2-2026-08-27 §2 row 1',
			'read_on'      => '2026-08-27',
		),
		'9798234055873' => array(
			'orderable'    => true,
			'format'       => 'paperback',
			'format_label' => __( 'Paperback', 'brave-hearts' ),
			'list_us'      => '12.99',
			'discount'     => '55%',
			'returnable'   => 'Yes - Destroy',
			'ingram_status'=> 'Title Available',
			'source'       => 'CYCLE167-GIM-INGRAM-READ-2-2026-08-27 §2 row 2',
			'read_on'      => '2026-08-27',
		),
		'9798996810802' => array(
			'orderable'    => true,
			'format'       => 'paperback',
			'format_label' => __( 'Paperback', 'brave-hearts' ),
			'list_us'      => '12.99',
			'discount'     => '55%',
			'returnable'   => 'Yes - Destroy',
			'ingram_status'=> 'Title Available',
			'source'       => 'CYCLE167-GIM-INGRAM-READ-2-2026-08-27 §2 row 3',
			'read_on'      => '2026-08-27',
		),
		'9798996810819' => array(
			'orderable'    => true,
			'format'       => 'hardcover',
			'format_label' => __( 'Hardcover', 'brave-hearts' ),
			'list_us'      => '19.99',
			'discount'     => '55%',
			'returnable'   => 'Yes - Destroy',
			'ingram_status'=> 'Title Available',
			'source'       => 'CYCLE167-GIM-INGRAM-READ-2-2026-08-27 §2 row 4',
			'read_on'      => '2026-08-27',
		),
		'9798996810826' => array(
			'orderable'    => true,
			'format'       => 'hardcover',
			'format_label' => __( 'Hardcover', 'brave-hearts' ),
			'list_us'      => '19.99',
			'discount'     => '55%',
			'returnable'   => 'Yes - Destroy',
			'ingram_status'=> 'Title Available',
			'source'       => 'CYCLE167-GIM-INGRAM-READ-2-2026-08-27 §2 row 5',
			'read_on'      => '2026-08-27',
		),

		// ── ⛔ WITHHELD. Both read live as Processing / distribution No / no
		//    production date. NEITHER may appear on the page in any form.
		'9798996810833' => array(
			'orderable'    => false,
			'format'       => 'hardcover',
			'ingram_status'=> 'Processing',
			'withheld'     => 'Enabled for Distribution: No. Original in Production Date: "-" (never entered production). Submitted 9-AUG-26 alongside two siblings that are live.',
			'source'       => 'CYCLE167-GIM-INGRAM-READ-2-2026-08-27 §2 row 6 and §3',
			'read_on'      => '2026-08-27',
		),
		'9798996810840' => array(
			'orderable'    => false,
			'format'       => 'paperback',
			'ingram_status'=> 'Processing',
			'withheld'     => 'Colouring book. Enabled for Distribution: No. Never entered production. Submitted 20-AUG-26.',
			'source'       => 'CYCLE167-GIM-INGRAM-READ-2-2026-08-27 §2 row 7 and §3',
			'read_on'      => '2026-08-27',
		),
	);

	/**
	 * ⛔ FILTERABLE FOR TESTS AND FOR A FUTURE LIVE READ, NOT FOR CONVENIENCE.
	 *    Anything added through this filter is still subject to the catalog
	 *    join below, so a filter cannot conjure a title that does not exist.
	 */
	return (array) apply_filters( 'bhp_retailer_trade_registry', $registry );
}

/**
 * The ISBNs this page must NEVER print, with the reason attached.
 *
 * @return array<string,string> ISBN => reason
 */
function bhp_retailer_withheld_isbns() {
	$out = array();
	foreach ( bhp_retailer_trade_registry() as $isbn => $row ) {
		if ( empty( $row['orderable'] ) ) {
			$out[ $isbn ] = isset( $row['withheld'] ) ? $row['withheld'] : 'Not orderable.';
		}
	}
	return $out;
}

/**
 * The rendered set: registry rows that are orderable AND resolve to a real
 * catalog edition, joined to the catalog's own title string.
 *
 * ⭐ ORDER IS DELIBERATE: paperbacks first, then hardcovers, each in series
 *    order. Paperback leads because it is the site-wide first-seen format
 *    (`bhp_bundle_default_format()`, 1.8.57) and because a first trade order is
 *    likelier to be paperback.
 *
 * @return array<int,array<string,string>>
 */
function bhp_retailer_orderable_titles() {
	if ( ! function_exists( 'bhp_bundle_catalog' ) ) {
		return array(); // ⛔ fail closed: no catalog, no ISBNs on the page.
	}

	$catalog  = bhp_bundle_catalog();
	$registry = bhp_retailer_trade_registry();

	// ISBN => label, flattened from the catalog. The catalog is the ONLY source
	// of the human-readable title for an ISBN.
	$labels = array();
	foreach ( $catalog as $format => $titles ) {
		foreach ( (array) $titles as $edition ) {
			if ( ! empty( $edition['isbn'] ) && ! empty( $edition['label'] ) ) {
				$labels[ (string) $edition['isbn'] ] = array(
					'label'  => (string) $edition['label'],
					'format' => (string) $format,
				);
			}
		}
	}

	$series_order = array( 'mariana', 'everest', 'amazon' );
	$rows         = array();

	foreach ( array( 'paperback', 'hardcover' ) as $format ) {
		foreach ( $series_order as $slug ) {
			if ( empty( $catalog[ $format ][ $slug ]['isbn'] ) ) {
				continue;
			}
			$isbn = (string) $catalog[ $format ][ $slug ]['isbn'];

			if ( empty( $registry[ $isbn ]['orderable'] ) ) {
				continue; // ⛔ withheld, or unknown to the registry. Both fail closed.
			}
			if ( empty( $labels[ $isbn ]['label'] ) ) {
				continue;
			}

			$term = $registry[ $isbn ];

			$rows[] = array(
				'isbn'         => $isbn,
				'label'        => $labels[ $isbn ]['label'],
				'format_label' => isset( $term['format_label'] ) ? $term['format_label'] : ucfirst( $format ),
				'list_us'      => isset( $term['list_us'] ) ? $term['list_us'] : '',
				'discount'     => isset( $term['discount'] ) ? $term['discount'] : '',
				'returnable'   => isset( $term['returnable'] ) ? $term['returnable'] : '',
			);
		}
	}

	return $rows;
}

/**
 * True when every orderable row carries the same discount and the same returns
 * value, which is what lets the page state them ONCE above the table instead of
 * repeating a column. ⛔ If a future live read ever splits them, this returns
 * false and the page renders the per-row columns instead of a false summary.
 *
 * @return bool
 */
function bhp_retailer_terms_are_uniform() {
	$rows = bhp_retailer_orderable_titles();
	if ( count( $rows ) < 2 ) {
		return false;
	}
	$discounts = array_unique( wp_list_pluck( $rows, 'discount' ) );
	$returns   = array_unique( wp_list_pluck( $rows, 'returnable' ) );

	return ( 1 === count( $discounts ) && 1 === count( $returns ) && '' !== reset( $discounts ) );
}
