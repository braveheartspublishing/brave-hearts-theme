<?php
/**
 * Phase 1A CPA (cost-per-acquisition) targets, one row per offer TYPE
 * (not per specific 2-book combination -- a CPA ceiling is a marketing
 * policy decision made per offer category, not per exact title pairing).
 *
 * Every offer type's "contribution before ads" is the CONSERVATIVE
 * (lowest) figure among that type's real combinations/titles from
 * BHP_Offer_Economics::offer_table() -- e.g. the two-hardcover bundle
 * figure uses the Everest+Amazon combination (its most expensive to
 * print), not an average, so a CPA ceiling set from this table is never
 * accidentally too generous for the actual worst-case combination a
 * customer might buy.
 *
 * APPROVED COMPANY POLICY (Andrew, 2026-07-06) -- see ANDREW_SPECIFIED_TARGETS:
 * Only the two Complete Collections have an approved preferred target CPA
 * and safer operating range; both are cold-acquisition candidates.
 *
 * The other four offers (single paperback, single hardcover, two-paperback
 * bundle, two-hardcover bundle) are explicitly NOT approved for cold paid
 * acquisition. For these, this class still computes contribution and
 * theoretical break-even (real math, always shown), and a CALCULATED,
 * clearly-labeled "model estimate" operating ceiling (the same 59%/82%
 * ratio extrapolated from Andrew's two approved examples, for
 * informational/monitoring consistency only) -- but `target_cpa` is null
 * and `cold_acquisition_approved` is false, so no caller can accidentally
 * present a definitive preferred-target number that was never approved.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BHP_CPA_Model {

	const STATUS_GREEN  = 'green';
	const STATUS_YELLOW = 'yellow';
	const STATUS_RED     = 'red';
	const STATUS_STOP    = 'stop';

	/**
	 * Ratio of an internal banding threshold / model-estimate-ceiling
	 * midpoint to (reserve-adjusted) contribution-before-ads. Both are
	 * back-derived from Andrew's own explicit Complete Paperback/Hardcover
	 * Collection numbers (target $13 / breakeven ~$21.95 = 59%; ceiling
	 * midpoint $18 / breakeven ~$21.95 = 82%; hardcover: $18/$30.36=59%,
	 * $25/$30.36=82%) purely so the non-approved offers' MODEL ESTIMATE
	 * ceiling uses a consistent methodology rather than an arbitrary one.
	 * This ratio itself is NOT approved company policy for any offer
	 * other than the two Collections -- see the class docblock.
	 */
	const TARGET_RATIO  = 0.59;
	const CEILING_RATIO = 0.82;
	const CEILING_BAND  = 0.05; // +/- band around the ceiling midpoint, e.g. $18 -> $17.10-$18.90

	/**
	 * Andrew's own explicitly APPROVED (2026-07-06) policy inputs for the
	 * two Complete Collection offers -- kept verbatim rather than
	 * recomputed from the generic ratio, since these are deliberate
	 * business decisions, not derived math. The corresponding theoretical
	 * break-even IS recomputed (see build_table()) because it now includes
	 * the refund/replacement reserve the original figure did not -- both
	 * of Andrew's given targets remain comfortably below the corrected
	 * break-even ($20.51 / $28.20), so no conflict results.
	 *
	 * ═══════════════════════════════════════════════════════════════════
	 * ⛔ 1.8.23 (2026-08-04) — THE LAST SENTENCE ABOVE IS NO LONGER TRUE,
	 *    AND THESE NUMBERS ARE DELIBERATELY LEFT ALONE ANYWAY.
	 * ═══════════════════════════════════════════════════════════════════
	 *
	 * Free collection shipping (Option B) moves break-even, because
	 * break-even CPA simply IS contribution before ads. The recomputed
	 * figures are **$16.79 (paperback)** and **$23.56 (hardcover)**, which
	 * `test-offer-economics.php` now asserts. Against them:
	 *
	 *     paperback : approved ceiling $17.50-$18.50  >  break-even $16.79
	 *     hardcover : approved ceiling $24.00-$26.00  >  break-even $23.56
	 *
	 * ⛔ SPENDING TO EITHER CEILING WOULD NOW LOSE MONEY ON EVERY ORDER.
	 *    Registered by Frodo as `CYCLE143-FIN-11`.
	 *
	 * ⛔ WHY THIS BUILD DID NOT SIMPLY LOWER THEM: **approving a CPA target
	 *    or ceiling is Andrew's decision and no one else's.** The relayed
	 *    ruling says the table was re-approved "at the new break-evens" and
	 *    that the "ceilings move with it" — but it states no ceiling
	 *    figures, and an engineer choosing $16.00 or $15.50 here would be
	 *    minting an approved-looking number Andrew never said. The constant
	 *    is labelled "Andrew's own explicitly APPROVED policy inputs"; a
	 *    value he did not give cannot be written into it.
	 *
	 * ✅ WHAT THIS BUILD DID INSTEAD: computed the breach as a fact
	 *    (`ceiling_exceeds_breakeven` in build_table()) and surfaced it on
	 *    the dashboard, so the incoherence is visible to whoever reads the
	 *    table rather than hidden behind two numbers that look approved.
	 *    Replacing these two rows is a one-line edit the moment Andrew's
	 *    ceiling figures exist.
	 */
	const ANDREW_SPECIFIED_TARGETS = array(
		BHP_Offer_Economics::COMPLETE_PAPERBACK_SET => array(
			'target_cpa'    => 13.00,
			'ceiling_low'   => 17.50,
			'ceiling_high'  => 18.50,
			'strategic'     => array( BHP_Offer_Economics::STRATEGIC_COLD ),
			'statement'     => 'Primary cold-acquisition candidate.',
		),
		BHP_Offer_Economics::COMPLETE_HARDCOVER_SET => array(
			'target_cpa'    => 18.00,
			'ceiling_low'   => 24.00,
			'ceiling_high'  => 26.00,
			'strategic'     => array( BHP_Offer_Economics::STRATEGIC_COLD, BHP_Offer_Economics::STRATEGIC_PREMIUM_GIFT ),
			'statement'     => 'Premium/gift acquisition candidate.',
		),
	);

	/**
	 * Approved (2026-07-06) strategic-use statements for the four offers
	 * NOT cleared for cold paid acquisition -- exact wording, not a
	 * paraphrase, so the dashboard never implies a broader endorsement
	 * than Andrew actually gave.
	 */
	const NON_COLD_STRATEGIC_STATEMENTS = array(
		BHP_Offer_Economics::SINGLE_PAPERBACK     => 'Organic/search entry offer; not approved for cold paid acquisition.',
		BHP_Offer_Economics::SINGLE_HARDCOVER     => 'Organic/search or gift entry offer; not approved for cold paid acquisition.',
		BHP_Offer_Economics::TWO_PAPERBACK_BUNDLE => 'Retargeting, upsell, or incomplete-series offer; no approved cold-acquisition target.',
		BHP_Offer_Economics::TWO_HARDCOVER_BUNDLE => 'Retargeting, upsell, or gift offer; no approved cold-acquisition target.',
	);

	private static function strategic_for_type( $offer_type ) {
		$map = array(
			BHP_Offer_Economics::SINGLE_PAPERBACK       => array( BHP_Offer_Economics::STRATEGIC_ORGANIC_ONLY ),
			BHP_Offer_Economics::SINGLE_HARDCOVER       => array( BHP_Offer_Economics::STRATEGIC_RETARGETING ),
			BHP_Offer_Economics::TWO_PAPERBACK_BUNDLE   => array( BHP_Offer_Economics::STRATEGIC_UPSELL, BHP_Offer_Economics::STRATEGIC_RETARGETING ),
			BHP_Offer_Economics::TWO_HARDCOVER_BUNDLE   => array( BHP_Offer_Economics::STRATEGIC_UPSELL, BHP_Offer_Economics::STRATEGIC_PREMIUM_GIFT ),
		);
		return $map[ $offer_type ] ?? array();
	}

	private static function offer_type_labels() {
		return array(
			BHP_Offer_Economics::SINGLE_PAPERBACK       => __( 'Single paperback', 'bhp-bundle-pricing' ),
			BHP_Offer_Economics::SINGLE_HARDCOVER       => __( 'Single hardcover', 'bhp-bundle-pricing' ),
			BHP_Offer_Economics::TWO_PAPERBACK_BUNDLE   => __( 'Two-paperback bundle', 'bhp-bundle-pricing' ),
			BHP_Offer_Economics::TWO_HARDCOVER_BUNDLE   => __( 'Two-hardcover bundle', 'bhp-bundle-pricing' ),
			BHP_Offer_Economics::COMPLETE_PAPERBACK_SET => __( 'Complete Paperback Collection', 'bhp-bundle-pricing' ),
			BHP_Offer_Economics::COMPLETE_HARDCOVER_SET => __( 'Complete Hardcover Collection', 'bhp-bundle-pricing' ),
		);
	}

	/**
	 * The conservative (lowest) contribution-before-acquisition among all
	 * real offer_table() rows of a given offer type. Returns null (never
	 * a guessed number) if any row of that type has an UNKNOWN cost basis
	 * -- an offer with a missing cost mapping must not receive a margin
	 * or CPA recommendation built partly on an incomplete cost.
	 */
	private static function conservative_contribution_by_type() {
		$rows = BHP_Offer_Economics::offer_table();
		$min = array();
		$has_unknown = array();
		foreach ( $rows as $row ) {
			$type = $row['offer_type'];
			if ( 'unknown' === $row['basis'] ) {
				$has_unknown[ $type ] = true;
				continue;
			}
			if ( ! isset( $min[ $type ] ) || $row['contribution_before_acquisition'] < $min[ $type ] ) {
				$min[ $type ] = $row['contribution_before_acquisition'];
			}
		}
		foreach ( array_keys( $has_unknown ) as $type ) {
			unset( $min[ $type ] ); // any unknown-cost combination of this type invalidates a reliable "conservative" figure for the whole type
		}
		return $min;
	}

	/**
	 * Full CPA table: one row per offer type. Collections carry Andrew's
	 * approved target/ceiling; the other four carry contribution/break-even
	 * (always real) plus a clearly-labeled model-estimate ceiling only --
	 * `target_cpa` is null and `cold_acquisition_approved` is false for
	 * those, so no caller can present an unapproved number as policy.
	 */
	public static function build_table() {
		$contribution_by_type = self::conservative_contribution_by_type();
		$labels = self::offer_type_labels();
		$rows = array();

		foreach ( $labels as $type => $label ) {
			if ( ! array_key_exists( $type, $contribution_by_type ) ) {
				// Either no offer of this type exists, or at least one of
				// its combinations has an unknown cost -- surface an
				// explicit unknown row rather than silently omitting it.
				$rows[] = array(
					'offer_type'                       => $type,
					'offer_label'                       => $label,
					'contribution_before_acquisition'   => null,
					'theoretical_breakeven_cpa'          => null,
					'cold_acquisition_approved'          => false,
					'target_cpa'                         => null,
					'ceiling_basis'                      => 'unknown',
					'safer_ceiling_low'                  => null,
					'safer_ceiling_high'                 => null,
					'hard_stop_cpa'                       => null,
					'strategic_labels'                    => array(),
					'strategic_statement'                 => 'Unknown cost -- no margin or CPA recommendation available until the missing cost mapping is added.',
					'target_source'                       => 'Unavailable: at least one combination of this offer type has an unmapped print cost',
					'basis'                                => 'unknown',
				);
				continue;
			}

			$contribution = $contribution_by_type[ $type ];

			// Theoretical break-even CPA = contribution before acquisition
			// itself: spend exactly this much and the resulting
			// contribution-after-acquisition is zero. This is always the
			// mathematically absolute hard stop -- spending beyond it
			// guarantees a negative-contribution order regardless of any
			// other policy choice.
			$breakeven = $contribution;
			$hard_stop = $breakeven;

			if ( isset( self::ANDREW_SPECIFIED_TARGETS[ $type ] ) ) {
				$policy = self::ANDREW_SPECIFIED_TARGETS[ $type ];
				$rows[] = array(
					'offer_type'                       => $type,
					'offer_label'                       => $label,
					'contribution_before_acquisition'   => round( $contribution, 2 ),
					'theoretical_breakeven_cpa'          => round( $breakeven, 2 ),
					'cold_acquisition_approved'          => true,
					'target_cpa'                         => $policy['target_cpa'],
					'ceiling_basis'                       => 'approved',
					'safer_ceiling_low'                   => $policy['ceiling_low'],
					'safer_ceiling_high'                  => $policy['ceiling_high'],
					/*
					 * ⭐ 1.8.23 — arithmetic, not policy. True when the
					 *    approved ceiling has drifted at or above the live
					 *    break-even, which is the state free collection
					 *    shipping put both collections into
					 *    (`CYCLE143-FIN-11`). It sets no target, changes no
					 *    approved figure and recommends nothing; it reports
					 *    that spending to the ceiling would now lose money.
					 *    Computed every load, so it clears itself the moment
					 *    Andrew's revised ceilings land above it again.
					 */
					'ceiling_exceeds_breakeven'           => ( (float) $policy['ceiling_high'] >= (float) $breakeven ),
					'hard_stop_cpa'                        => $hard_stop,
					'strategic_labels'                     => $policy['strategic'],
					'strategic_statement'                  => $policy['statement'],
					'target_source'                        => 'Andrew-approved policy input (2026-07-06), not derived',
					'basis'                                 => 'estimated',
					// Internal only, used by classify_cpa()'s green threshold --
					// identical to the publicly-displayed target for approved offers.
					'_banding_target'                       => $policy['target_cpa'],
				);
			} else {
				$ceiling_mid = $breakeven * self::CEILING_RATIO;
				$ceiling_low = round( $ceiling_mid * ( 1 - self::CEILING_BAND ), 2 );
				$ceiling_high = round( $ceiling_mid * ( 1 + self::CEILING_BAND ), 2 );
				$banding_target = round( $breakeven * self::TARGET_RATIO, 2 ); // internal monitoring threshold only -- never shown as an approved target

				$rows[] = array(
					'offer_type'                       => $type,
					'offer_label'                       => $label,
					'contribution_before_acquisition'   => round( $contribution, 2 ),
					'theoretical_breakeven_cpa'          => round( $breakeven, 2 ),
					'cold_acquisition_approved'          => false,
					'target_cpa'                         => null, // NOT approved -- do not assign a definitive preferred target without real campaign data
					'ceiling_basis'                       => 'model_estimate',
					'safer_ceiling_low'                   => $ceiling_low,
					'safer_ceiling_high'                  => $ceiling_high,
					'hard_stop_cpa'                        => $hard_stop,
					'strategic_labels'                     => self::strategic_for_type( $type ),
					'strategic_statement'                  => self::NON_COLD_STRATEGIC_STATEMENTS[ $type ] ?? 'No approved cold-acquisition target.',
					'target_source'                        => 'Model estimate only (59%/82% ratio extrapolated from the two approved Collection targets) -- NOT approved company policy for this offer; shown for informational/monitoring purposes only',
					'basis'                                 => 'estimated',
					'_banding_target'                       => $banding_target,
				);
			}
		}

		return $rows;
	}

	/**
	 * Classifies an actual/hypothetical CPA against one offer type's
	 * thresholds. Pure function so it's directly unit-testable. Uses the
	 * internal `_banding_target` for the GREEN threshold even on
	 * non-approved offers (so real ad performance can still be monitored
	 * against a consistent scale) -- this is a monitoring convenience,
	 * NOT a claim that the offer is approved for cold acquisition; see
	 * `cold_acquisition_approved` for that.
	 *
	 * @return string one of STATUS_GREEN|STATUS_YELLOW|STATUS_RED|STATUS_STOP
	 */
	public static function classify_cpa( $offer_type, $actual_cpa ) {
		$table = self::build_table();
		foreach ( $table as $row ) {
			if ( $row['offer_type'] !== $offer_type ) {
				continue;
			}
			if ( 'unknown' === $row['basis'] || null === $row['hard_stop_cpa'] ) {
				return self::STATUS_STOP; // unknown-cost offer -- never a reliable recommendation
			}
			$cpa = (float) $actual_cpa;
			$banding_target = $row['_banding_target'] ?? $row['target_cpa'];

			/*
			 * ═══════════════════════════════════════════════════════════
			 * ⛔ 1.8.23 — THE HARD STOP IS TESTED FIRST. It used to be
			 *    tested LAST, and that was safe only while an unstated
			 *    invariant held.
			 * ═══════════════════════════════════════════════════════════
			 *
			 * The old order asked "is it under the ceiling?" before "is it
			 * under break-even?", which is correct **only if the ceiling is
			 * below break-even.** That was true of every approved figure
			 * until free collection shipping moved break-even down to
			 * $16.79 / $23.56 while the approved ceilings stayed at
			 * $18.50 / $26.00.
			 *
			 * ⛔ THE DEFECT THIS FIXES IS REAL AND WAS LIVE THE MOMENT THE
			 *    RULING LANDED: a paperback-collection CPA of $18.00 loses
			 *    money on every order, and the old ordering graded it
			 *    YELLOW, i.e. acceptable, because $18.00 <= the $18.50
			 *    ceiling. Nothing else in the dashboard would have caught
			 *    it.
			 *
			 * ✅ THIS IS A ONE-DIRECTION CHANGE. It can only move a rating
			 *    toward STOP, never away from it, and it is a no-op
			 *    wherever the ceiling still sits below break-even (which is
			 *    every model-estimate row, by construction, since those
			 *    ceilings are 82% of break-even). It sets no target and
			 *    changes no approved figure.
			 */
			if ( $cpa > (float) $row['hard_stop_cpa'] ) {
				return self::STATUS_STOP;
			}
			if ( null !== $banding_target && $cpa <= $banding_target ) {
				return self::STATUS_GREEN;
			}
			if ( $cpa <= $row['safer_ceiling_high'] ) {
				return self::STATUS_YELLOW;
			}
			return self::STATUS_RED;
		}
		return self::STATUS_STOP; // unknown offer type -- fail safe, never silently "green"
	}
}