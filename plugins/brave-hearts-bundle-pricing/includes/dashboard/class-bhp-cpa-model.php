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
 * APPROVED COMPANY POLICY (Andrew, 2026-07-06):
 * Only the two Complete Collections have an approved preferred target CPA
 * and safer operating range; both are cold-acquisition candidates.
 *
 * The other four offers (single paperback, single hardcover, two-paperback
 * bundle, two-hardcover bundle) are explicitly NOT approved for cold paid
 * acquisition. For these, this class still computes contribution and
 * theoretical break-even (real math, always shown), and a CALCULATED,
 * clearly-labeled "model estimate" operating ceiling (the same ratios
 * extrapolated from Andrew's two approved examples, for
 * informational/monitoring consistency only) -- but `target_cpa` is null
 * and `cold_acquisition_approved` is false, so no caller can accidentally
 * present a definitive preferred-target number that was never approved.
 *
 * ---------------------------------------------------------------------
 * WHERE THE NUMBERS LIVE, AND WHY THEY ARE NOT IN THIS FILE (since 1.8.30)
 * ---------------------------------------------------------------------
 * This plugin is published in a public repository. An approved target CPA
 * and an approved operating ceiling are acquisition policy: together with
 * the published storefront prices they disclose what the company believes
 * it can afford to pay for a customer, which is competitive information
 * and is not ours to publish. So this file now carries the MODEL -- the
 * key contract, the shapes, the provenance labels, the banding logic and
 * the offer-policy structure -- and reads the AMOUNTS from a single
 * site option:
 *
 *     get_option( 'bhp_cpa_model' )
 *
 * It is deliberately a SEPARATE option from BHP_Cost_Config::MODEL_OPTION,
 * not a section inside it. The two have different owners and different
 * lifecycles: the cost model is measured (print quotes, postage, processing
 * fees) and moves when a supplier does; this one is DECIDED, by Andrew, and
 * moves only when he says so. Folding them together would also couple their
 * seeded-ness, so an environment with real costs and no acquisition policy
 * would report its cost figures as unavailable -- which would be false.
 *
 * The option is seeded per environment, out of band, by the operator. It
 * is never written by this plugin, never exported, never printed to a
 * page, and never committed. An unseeded environment is a supported, loud
 * state: `ceiling_basis` reports 'unavailable', every target and ceiling
 * is null rather than zero, classify_cpa() fails safe to STOP, and an
 * admin notice says so.
 *
 * Nothing here is ever zero-by-assumption. A target CPA that silently
 * defaulted to $0 would grade every real campaign RED or STOP; a ceiling
 * that silently defaulted to $0 would do the same. Both would look like a
 * working dashboard reporting terrible performance, which is worse than a
 * dashboard that says it has no policy loaded.
 *
 * ---------------------------------------------------------------------
 * 1.8.31 — THE POLICY WAS RE-DECIDED, AND THE RATIOS ARE DESCRIPTIVE
 * ---------------------------------------------------------------------
 * On 2026-08-06 Andrew re-decided all four Collection policy figures -- a
 * preferred target and a hard operating stop for each -- on the contribution
 * basis re-approved against actual Bookvault print and postage costs. No code
 * in this file changed to carry that: the amounts live in the option, which is
 * the entire point of the 1.8.30 relocation, and the seed is an out-of-band
 * operator act. What changed here is only this note and the suite's pin.
 *
 * ⭐ THE RATIOS ARE A DESCRIPTION, NOT A GENERATOR. This settles a question
 *    that had been open since 1.8.23 and was raised again during the re-seed.
 *    Read `build_table()` and it is plain: for the two APPROVED Collections
 *    the target and both ceilings are read STRAIGHT FROM THE OPTION via
 *    approved_policy(), and the `ratio.*` keys are never consulted. The ratios
 *    are consulted ONLY for the four offer types that are NOT approved for
 *    cold acquisition, to keep their clearly-labelled MODEL ESTIMATE ceilings
 *    consistent with approved policy instead of arbitrary.
 *
 *    So the Collection figures are not derived from the ratios and never were;
 *    the ratios are back-derived from the Collection figures. Andrew choosing
 *    the figures directly -- in 2026-07-06 and again in 2026-08-06 -- is the
 *    normal case, not an exception to a formula.
 *
 * ⚠️ ONE SHARED RATIO SET, AND THE TWO FORMATS DO NOT AGREE EXACTLY.
 *    Describing the paperback figures against the paperback basis and the
 *    hardcover figures against the hardcover basis yields slightly different
 *    ratios. The key contract has one shared set, so the seeded ratios describe
 *    the PAPERBACK leg -- the only one whose contribution rests on a real
 *    invoice, since no hardcover order has ever been placed and its print and
 *    postage inputs are estimated and extrapolated respectively. The divergence
 *    is small, it affects only the four model-estimate rows, and the re-seed
 *    script reports it as a labelled delta on every run rather than absorbing
 *    it. Per-format ratio keys remain available as a future contract change and
 *    were deliberately not taken in 1.8.31.
 *
 * ⛔ Nothing above names a figure, and nothing in this file may. The suite
 *    protects the policy with a joint SHA-256 pin, which is a hash.
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
	 * Site option holding the acquisition-policy amounts. Seeded per
	 * environment by the operator; never written by this plugin.
	 */
	const MODEL_OPTION = 'bhp_cpa_model';

	/**
	 * In-request cache so a dashboard render that builds the table more
	 * than once performs one option read, not several.
	 */
	protected static $model_cache = null;

	/**
	 * The offer types Andrew approved for cold paid acquisition
	 * (2026-07-06). This is the approval FACT, which is policy structure
	 * rather than a figure, so it stays in code: which offers may be
	 * bought against is not confidential, what we will pay for them is.
	 */
	public static function approved_offer_types() {
		return array(
			BHP_Offer_Economics::COMPLETE_PAPERBACK_SET,
			BHP_Offer_Economics::COMPLETE_HARDCOVER_SET,
		);
	}

	/**
	 * Approved (2026-07-06) strategic-use labels and statements for the two
	 * cold-acquisition offers -- exact wording, not a paraphrase, so the
	 * dashboard never implies a broader endorsement than Andrew gave.
	 */
	protected static function approved_strategic() {
		return array(
			BHP_Offer_Economics::COMPLETE_PAPERBACK_SET => array( BHP_Offer_Economics::STRATEGIC_COLD ),
			BHP_Offer_Economics::COMPLETE_HARDCOVER_SET => array( BHP_Offer_Economics::STRATEGIC_COLD, BHP_Offer_Economics::STRATEGIC_PREMIUM_GIFT ),
		);
	}

	protected static function approved_statements() {
		return array(
			BHP_Offer_Economics::COMPLETE_PAPERBACK_SET => 'Primary cold-acquisition candidate.',
			BHP_Offer_Economics::COMPLETE_HARDCOVER_SET => 'Premium/gift acquisition candidate.',
		);
	}

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

	/**
	 * Every amount the model must supply, as a flat dot-path list. This IS
	 * the contract between this file and the seeded option: a key here and
	 * absent there is unavailable, and a key there and absent here is
	 * ignored. Keeping the list in code (and the values out of it) is what
	 * lets an unseeded environment name exactly what it is missing without
	 * the file naming any figure.
	 *
	 * The three `ratio.*` keys are the banding methodology: the
	 * target-to-break-even ratio, the ceiling-midpoint-to-break-even ratio,
	 * and the +/- band around that midpoint. They are back-derived from
	 * Andrew's own approved Collection figures purely so the non-approved
	 * offers' MODEL ESTIMATE ceiling uses a consistent methodology rather
	 * than an arbitrary one -- which is exactly why they cannot stay in a
	 * public file either: they are the approved figures expressed as
	 * fractions. The ratios themselves are NOT approved company policy for
	 * any offer other than the two Collections.
	 */
	public static function model_keys() {
		$keys = array();
		foreach ( self::approved_offer_types() as $type ) {
			$keys[] = 'target_cpa.' . $type;
			$keys[] = 'ceiling_low.' . $type;
			$keys[] = 'ceiling_high.' . $type;
		}
		$keys[] = 'ratio.target';
		$keys[] = 'ratio.ceiling';
		$keys[] = 'ratio.ceiling_band';
		return $keys;
	}

	/**
	 * The seeded model, filtered. The filter exists so a private
	 * environment can supply the amounts from somewhere other than the
	 * option (an mu-plugin, a constant, a secrets file) without either the
	 * values or that mechanism entering this repository.
	 */
	public static function model() {
		if ( null === self::$model_cache ) {
			$stored = get_option( self::MODEL_OPTION, array() );
			if ( ! is_array( $stored ) ) {
				$stored = array();
			}
			/**
			 * Filter the acquisition-policy amount map.
			 *
			 * @param array $stored dot-path key => float
			 */
			self::$model_cache = (array) apply_filters( 'bhp_cpa_model', $stored );
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
	 * Partial seeding is NOT "seeded" -- a half-populated policy produces
	 * a table that looks complete and is not.
	 */
	public static function is_seeded() {
		return array() === self::missing_model_keys();
	}

	/**
	 * One amount, or null when the model does not supply it. There is
	 * deliberately no amount_or_zero() counterpart here: unlike a cost,
	 * there is no arithmetic in this class that a zero policy figure could
	 * safely flow through.
	 */
	protected static function amount( $key ) {
		$model = self::model();
		return isset( $model[ $key ] ) && is_numeric( $model[ $key ] ) ? (float) $model[ $key ] : null;
	}

	/**
	 * A stable, order-independent fingerprint of the loaded policy, or ''
	 * when the policy is not fully seeded.
	 *
	 * This exists so a test can pin the approved policy WITHOUT naming it.
	 * An approved ceiling that is not pinned is not protected: the whole
	 * point of committing it used to be that an unauthorised edit failed a
	 * test. Moving the figures out must not quietly give that up, so the
	 * suite pins this digest instead and an unauthorised change to any
	 * target, ceiling or ratio still fails.
	 *
	 * It is ONE joint digest over every key, never a per-key digest, and
	 * that is a deliberate security property rather than a convenience: a
	 * hash of a single currency amount has a search space small enough to
	 * invert by brute force, while the joint space of all of them does
	 * not. Do not split this into per-key fingerprints.
	 *
	 * Values are normalised to four decimal places so an equal amount
	 * seeded as an int, a float or a numeric string produces one digest.
	 */
	public static function policy_fingerprint() {
		if ( ! self::is_seeded() ) {
			return '';
		}
		$parts = array();
		foreach ( self::model_keys() as $key ) {
			$parts[] = $key . '=' . number_format( (float) self::amount( $key ), 4, '.', '' );
		}
		return hash( 'sha256', self::MODEL_OPTION . "/v1\n" . implode( "\n", $parts ) );
	}

	/**
	 * Andrew's approved policy inputs for one offer type, or null when the
	 * type has no approved policy or the model has not been seeded with
	 * it. Never a partially-populated policy: a target without its
	 * ceilings would band incorrectly and silently.
	 */
	protected static function approved_policy( $type ) {
		if ( ! in_array( $type, self::approved_offer_types(), true ) ) {
			return null;
		}
		$target = self::amount( 'target_cpa.' . $type );
		$low     = self::amount( 'ceiling_low.' . $type );
		$high    = self::amount( 'ceiling_high.' . $type );
		if ( null === $target || null === $low || null === $high ) {
			return null;
		}
		$strategic  = self::approved_strategic();
		$statements = self::approved_statements();
		return array(
			'target_cpa'   => $target,
			'ceiling_low'  => $low,
			'ceiling_high' => $high,
			'strategic'    => $strategic[ $type ] ?? array(),
			'statement'    => $statements[ $type ] ?? '',
		);
	}

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
	 *
	 * Since 1.8.30 there is a third shape: an environment whose policy
	 * option is unseeded still gets its contribution and break-even --
	 * those come from the cost model and are real math, not policy -- but
	 * `ceiling_basis` reads 'unavailable' and every target/ceiling is
	 * null. It is never a row of zeroes.
	 */
	public static function build_table() {
		$contribution_by_type = self::conservative_contribution_by_type();
		$labels = self::offer_type_labels();
		$ratio_ceiling = self::amount( 'ratio.ceiling' );
		$ratio_band     = self::amount( 'ratio.ceiling_band' );
		$ratio_target   = self::amount( 'ratio.target' );
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
			// other policy choice. It is cost-model math, so it survives an
			// unseeded acquisition policy unchanged.
			$breakeven = $contribution;
			$hard_stop = $breakeven;

			$is_approved_type = in_array( $type, self::approved_offer_types(), true );
			$policy            = self::approved_policy( $type );

			if ( $is_approved_type ) {
				if ( null === $policy ) {
					// Approved for cold acquisition, but this environment
					// has no policy figures loaded. Say so; do not band.
					$strategic  = self::approved_strategic();
					$statements = self::approved_statements();
					$rows[] = array(
						'offer_type'                       => $type,
						'offer_label'                       => $label,
						'contribution_before_acquisition'   => round( $contribution, 2 ),
						'theoretical_breakeven_cpa'          => round( $breakeven, 2 ),
						'cold_acquisition_approved'          => true,
						'target_cpa'                         => null,
						'ceiling_basis'                       => 'unavailable',
						'safer_ceiling_low'                   => null,
						'safer_ceiling_high'                  => null,
						'ceiling_exceeds_breakeven'           => null,
						'hard_stop_cpa'                        => $hard_stop,
						'strategic_labels'                     => $strategic[ $type ] ?? array(),
						'strategic_statement'                  => $statements[ $type ] ?? '',
						'target_source'                        => 'Unavailable: the acquisition-policy option is not seeded on this environment',
						'basis'                                 => 'estimated',
						'_banding_target'                       => null,
					);
					continue;
				}

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
					 *
					 * ⭐ 1.8.31 — IT DID CLEAR ITSELF, EXACTLY AS DESIGNED.
					 *    Andrew's revised ceilings of 2026-08-06 sit below
					 *    live break-even in both formats, so this now reads
					 *    FALSE on both approved rows and the dashboard's
					 *    "ABOVE break-even, needs re-approval" note no longer
					 *    renders. No code was needed to turn it off, which is
					 *    the property the 1.8.23 design was after.
					 *    ⛔ The suite asserts `false` here on purpose now, and
					 *    keeps the `true` branch covered through a synthetic
					 *    pathological policy injected via the `bhp_cpa_model`
					 *    filter -- see tests/test-offer-economics.php. A guard
					 *    that only fires while a defect is live stops guarding
					 *    the moment the defect is fixed.
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
				continue;
			}

			if ( null === $ratio_ceiling || null === $ratio_band || null === $ratio_target ) {
				// No banding methodology loaded -- show the real math and
				// nothing else. A model-estimate ceiling of $0 would read as
				// "never spend anything", which is a policy claim this
				// environment has no basis to make.
				$rows[] = array(
					'offer_type'                       => $type,
					'offer_label'                       => $label,
					'contribution_before_acquisition'   => round( $contribution, 2 ),
					'theoretical_breakeven_cpa'          => round( $breakeven, 2 ),
					'cold_acquisition_approved'          => false,
					'target_cpa'                         => null,
					'ceiling_basis'                       => 'unavailable',
					'safer_ceiling_low'                   => null,
					'safer_ceiling_high'                  => null,
					'hard_stop_cpa'                        => $hard_stop,
					'strategic_labels'                     => self::strategic_for_type( $type ),
					'strategic_statement'                  => self::NON_COLD_STRATEGIC_STATEMENTS[ $type ] ?? 'No approved cold-acquisition target.',
					'target_source'                        => 'Unavailable: the acquisition-policy option is not seeded on this environment',
					'basis'                                 => 'estimated',
					'_banding_target'                       => null,
				);
				continue;
			}

			$ceiling_mid = $breakeven * $ratio_ceiling;
			$ceiling_low = round( $ceiling_mid * ( 1 - $ratio_band ), 2 );
			$ceiling_high = round( $ceiling_mid * ( 1 + $ratio_band ), 2 );
			$banding_target = round( $breakeven * $ratio_target, 2 ); // internal monitoring threshold only -- never shown as an approved target

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
				'target_source'                        => 'Model estimate only (the same target/ceiling ratios extrapolated from the two approved Collection targets) -- NOT approved company policy for this offer; shown for informational/monitoring purposes only',
				'basis'                                 => 'estimated',
				'_banding_target'                       => $banding_target,
			);
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

			/*
			 * 1.8.30 — no loaded acquisition policy means no grade.
			 *
			 * The break-even hard stop below is real math and would still
			 * work, but everything softer than STOP is a policy judgement,
			 * and this environment has no policy. Returning GREEN or YELLOW
			 * from a missing target would be inventing an approval; the
			 * fail-safe direction is the same one the unknown-offer-type
			 * case at the bottom of this method already takes.
			 */
			if ( 'unavailable' === $row['ceiling_basis'] || null === $row['safer_ceiling_high'] ) {
				return self::STATUS_STOP;
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
			 * until free collection shipping moved break-even below the
			 * approved ceilings, which stayed where Andrew set them.
			 *
			 * ⛔ THE DEFECT THIS FIXES IS REAL AND WAS LIVE THE MOMENT THE
			 *    RULING LANDED: a collection CPA between break-even and the
			 *    approved ceiling loses money on every order, and the old
			 *    ordering graded it YELLOW, i.e. acceptable, because it was
			 *    under the ceiling. Nothing else in the dashboard would
			 *    have caught it.
			 *
			 * ✅ THIS IS A ONE-DIRECTION CHANGE. It can only move a rating
			 *    toward STOP, never away from it, and it is a no-op
			 *    wherever the ceiling still sits below break-even (which is
			 *    every model-estimate row, by construction, since those
			 *    ceilings are a fraction of break-even). It sets no target
			 *    and changes no approved figure.
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
			esc_html__( 'Brave Hearts dashboard: acquisition-policy model not configured.', 'bhp-bundle-pricing' ) .
			'</strong> ' .
			esc_html(
				sprintf(
					/* translators: 1: number of missing keys, 2: total keys, 3: option name */
					__( '%1$d of %2$d acquisition-policy values are missing from the %3$s option, so preferred target CPA and operating ceilings are reported as unavailable rather than estimated, and every CPA classification fails safe to STOP. Seed the option for this environment to restore them.', 'bhp-bundle-pricing' ),
					count( $missing ),
					count( self::model_keys() ),
					self::MODEL_OPTION
				)
			) .
			'</p></div>';
	}
}
