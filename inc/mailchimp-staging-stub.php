<?php
/**
 * STAGING-ONLY MAILCHIMP TRANSPORT STUB — theme 1.19.296, 2026-08-27.
 * `CYCLE167-LD-CAPTURE-FIX-BUILD`, implementing FIX-2 (interim) of
 * `CYCLE167-LD-CAPTURE-PIPE-DIAGNOSIS` §7.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔⛔ THE PROBLEM THIS SOLVES, STATED PLAINLY BECAUSE IT IS THE REAL P0
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * There was NO environment in which the email pipe could be exercised end to
 * end. Both halves of that are true at once and each blocks the other:
 *
 *   1. Staging has NO Mailchimp API key. `bhp_mailchimp_signup_is_ready()`
 *      therefore returned false, `bhp_get_signup_form_action()` returned '',
 *      and EVERY signup form on staging rendered `action=""` — a form that
 *      looks perfect and posts nowhere.
 *   2. ⛔ AND YOU CANNOT JUST ADD THE KEY. Staging's audience ID is
 *      BYTE-IDENTICAL to production's. A working key on staging would write
 *      test subscribers straight into Andrew's live audience — an audience
 *      of thirteen people, where a handful of `+bhptest` rows would be a
 *      material corruption of the only list the business has.
 *
 * ⭐ SO THE LEVER IS A STUB, NEVER A CREDENTIAL. Nothing here reads, stores,
 *    requests or requires an API key. There is no key in this file, no key in
 *    any option it touches, and no code path that would use one.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔⛔ INERT ON PRODUCTION BY CONSTRUCTION — the guard is the whole safety story
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * `BHP_Analytics_Config::is_staging()` compares the REAL HTTP host, which is
 * the same instrument `bhp_get_popup_ab_forced_variant()` already trusts to
 * keep a QA override off production. If that class is missing, or the host is
 * not staging, THIS FILE REGISTERS NO HOOKS AT ALL and the site behaves
 * exactly as it did at 1.19.295.
 *
 * ⛔ THE GUARD IS DELIBERATELY NOT A CONSTANT, AN OPTION OR A QUERY PARAMETER.
 *    Each of those can be set on production by something other than the
 *    person reading this file. The host cannot.
 *
 * ⛔ AND IT FAILS CLOSED. Every early return below leaves production on the
 *    real transport. There is no branch in which a production request reaches
 *    the recorder.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐ WHAT IT DOES, AND THE ONE THING IT DELIBERATELY DOES NOT DO
 * ═══════════════════════════════════════════════════════════════════════════
 *
 *   · Forces `bhp_mailchimp_signup_is_ready` true ON STAGING ONLY, so forms
 *     render a real `action` and the POST path actually runs.
 *   · Supplies a list ID ON STAGING ONLY when none resolves, so the handler
 *     has something to pass. ⛔ It is a SYNTHETIC id that is deliberately NOT
 *     production's — see BHP_Mailchimp_Staging_Stub::LIST_ID. The end-to-end
 *     test asserts that the configured list is not production's, and that
 *     assertion is the `CYCLE166-OPS-011` guard expressed in code rather than
 *     in somebody's memory.
 *   · Routes `add_list_member()` / `update_list_member_tags()` into a
 *     recorder that stores the payload in a transient and returns a
 *     plausible-shaped success object.
 *
 * ⛔ IT SENDS NOTHING, ANYWHERE. There is no HTTP call in this file. The
 *    recorder is the terminus. "Reached the API boundary" means the payload
 *    arrived at the point where the real client would transmit — which is
 *    exactly the assertion the brief asked for, and it is honest about
 *    stopping there rather than claiming a delivery nobody observed.
 *
 * ⚠ WHAT THIS IS NOT: it is NOT the full FIX-2. The full fix is a SEPARATE
 *   staging Mailchimp audience plus a staging-only key, and both of those are
 *   ⛔ ANDREW'S GATE (creating an audience and issuing a credential are
 *   external-system actions). This is the interim that needs neither, and it
 *   should ship regardless of what he decides about the full one.
 */

defined('ABSPATH') || exit;

/**
 * Recording transport. Shaped like the two methods `bhp_process_signup()`
 * actually calls on the MC4WP client, and nothing else — a stub that grew a
 * wider surface than its caller uses would start lying about compatibility.
 */
class BHP_Mailchimp_Staging_Stub {

	/**
	 * ⛔ SYNTHETIC, AND DELIBERATELY NOT PRODUCTION'S AUDIENCE ID. The whole
	 *    point of the stub is that staging can never address the live list.
	 *    The end-to-end suite asserts this value is in play and that it does
	 *    not equal the production id.
	 */
	const LIST_ID = 'stagingstub0';

	const TRANSIENT = 'bhp_mailchimp_stub_last_payload';

	/** Kept short: this is a test artefact, not a record. */
	const TTL = 900;

	/**
	 * Register only on staging. Every failure mode leaves the real transport
	 * in place.
	 */
	/**
	 * ⭐ 1.19.296 — IS THIS THE STAGING INSTALL?
	 *
	 * ⛔⛔ `BHP_Analytics_Config::is_staging()` COMPARES `$_SERVER['HTTP_HOST']`,
	 *     WHICH IS NOT SET UNDER WP-CLI. That is correct for its own job
	 *     (analytics and pixel gating are about browser requests) but it meant
	 *     this stub was inert under `wp eval-file` — so the end-to-end suite,
	 *     the one instrument this whole exercise exists to build, could not
	 *     run. Found by running the suite, not by reading the code.
	 *
	 * ⭐ SO CLI IS DETECTED SEPARATELY, VIA `home_url()`. That value lives in
	 *    the database and is genuinely per-environment, so it cannot be
	 *    spoofed by a request header.
	 *
	 * ⛔ `is_staging()` ITSELF IS NOT TOUCHED. Widening it would have changed
	 *    analytics, consent and Meta-pixel gating for every CLI context on both
	 *    environments — a large blast radius for a test-harness problem.
	 *
	 * ⛔ PRODUCTION CLI STILL GETS NOTHING: its `home_url()` is the production
	 *    domain, so both limbs are false and no hook is registered.
	 */
	public static function is_staging_install() {
		if ( class_exists( 'BHP_Analytics_Config' ) && BHP_Analytics_Config::is_staging() ) {
			return true;
		}

		if ( ! ( defined( 'WP_CLI' ) && WP_CLI ) ) {
			return false;
		}

		$host = (string) wp_parse_url( home_url(), PHP_URL_HOST );
		return class_exists( 'BHP_Analytics_Config' )
			&& strtolower( $host ) === BHP_Analytics_Config::STAGING_HOST;
	}

	public static function init() {
		if ( ! self::is_staging_install() ) {
			return;
		}

		add_filter( 'bhp_mailchimp_signup_is_ready', '__return_true', 99 );
		add_filter( 'bhp_mailchimp_api_transport', array( __CLASS__, 'transport' ), 10, 1 );
		add_filter( 'bhp_mailchimp_list_id', array( __CLASS__, 'list_id' ), 99 );
	}

	/**
	 * Only supply an id when nothing else resolved one, so a staging install
	 * that has been given its own real audience keeps it.
	 */
	public static function list_id( $list_id ) {
		return $list_id ? $list_id : self::LIST_ID;
	}

	public static function transport( $api ) {
		return is_object( $api ) ? $api : new self();
	}

	/** @return array The last recorded payload, or [] if none. */
	public static function last_payload() {
		$payload = get_transient( self::TRANSIENT );
		return is_array( $payload ) ? $payload : array();
	}

	public static function clear() {
		delete_transient( self::TRANSIENT );
	}

	private static function record( $entry ) {
		$payload            = self::last_payload();
		$payload['calls'][] = $entry;
		$payload['at']      = current_time( 'mysql' );
		set_transient( self::TRANSIENT, $payload, self::TTL );
	}

	/**
	 * Mirrors the MC4WP signature `bhp_process_signup()` calls.
	 *
	 * @return object A plausible-shaped subscriber, so the success path runs.
	 */
	public function add_list_member( $list_id, $args, $update_existing = false ) {
		unset( $update_existing );
		self::record(
			array(
				'method'  => 'add_list_member',
				'list_id' => (string) $list_id,
				'args'    => $args,
			)
		);

		return (object) array(
			'id'            => 'stub-' . md5( (string) ( isset( $args['email_address'] ) ? $args['email_address'] : '' ) ),
			'email_address' => isset( $args['email_address'] ) ? $args['email_address'] : '',
			'status'        => isset( $args['status'] ) ? $args['status'] : 'subscribed',
			'merge_fields'  => isset( $args['merge_fields'] ) ? (object) $args['merge_fields'] : (object) array(),
			'_bhp_stub'     => true,
		);
	}

	public function update_list_member_tags( $list_id, $email, $args ) {
		self::record(
			array(
				'method'  => 'update_list_member_tags',
				'list_id' => (string) $list_id,
				'email'   => (string) $email,
				'args'    => $args,
			)
		);
		return true;
	}
}

BHP_Mailchimp_Staging_Stub::init();
