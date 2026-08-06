<?php
/**
 * Null-object implementation of BHP_Analytics_Provider_Interface.
 *
 * Proves (and lets a future dashboard panel rely on) the rule that "the
 * existing operational dashboard must continue to work when no external
 * integrations are configured." Any future Meta/GA4/Pinterest/email panel
 * should be able to safely call this in place of a real provider and get
 * back well-formed "not configured" responses -- never null, never a
 * fatal error, never an empty array that could be misread as "zero spend
 * confirmed" rather than "no data available."
 *
 * NOT wired into the live dashboard yet -- dormant scaffold code, see
 * interface-bhp-analytics-provider.php's docblock.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/interface-bhp-analytics-provider.php';

class BHP_Null_Analytics_Provider implements BHP_Analytics_Provider_Interface {

	private $provider_key;
	private $display_name;

	public function __construct( $provider_key = 'none', $display_name = 'Not connected' ) {
		$this->provider_key = $provider_key;
		$this->display_name = $display_name;
	}

	public function get_provider_key() {
		return $this->provider_key;
	}

	public function get_display_name() {
		return $this->display_name;
	}

	public function is_configured() {
		return false;
	}

	public function get_configuration_issue() {
		return 'No provider configured.';
	}

	public function get_daily_metrics( $start, $end ) {
		return array(); // empty list, not null -- callers can always safely iterate
	}

	public function get_sync_status() {
		return array(
			'last_success'   => null,
			'status'         => 'never_synced',
			'failure_reason' => null,
		);
	}
}
