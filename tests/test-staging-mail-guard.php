<?php
/**
 * THE STAGING ORDER-EMAIL GUARD — theme 1.19.281.
 * Workstream `CYCLE165-LD-FLOW-ADJUSTMENTS`.
 *
 * Run via WP-CLI:
 *   wp eval-file wp-content/themes/<slug>/tests/test-staging-mail-guard.php --user=1
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐ WHY THIS SUITE EXISTS: THE GUARD'S FAILURE MODE IS SILENT AND EXPENSIVE
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * A guard that suppresses too LITTLE mails Andrew during QA — annoying, and
 * the defect that prompted the build. A guard that suppresses too MUCH stops a
 * real customer's order confirmation on production, and NOBODY WOULD SEE IT
 * HAPPEN. The second failure is the one this suite is built around, which is
 * why every production-side assertion below is exercised against a simulated
 * production host rather than read out of the source.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔ WHAT THIS SUITE CANNOT PROVE, STATED PLAINLY
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * It does not place an order, does not call `wp_mail()`, and does not inspect
 * a mail server. It proves the ENABLEMENT DECISION — the value WooCommerce
 * reads before it ever builds a message. ⭐ That an admin email actually stops
 * arriving is a different claim and is verified by placing a real staging
 * order in QA.
 *
 * ⛔ NO OPTION, PRODUCT, ORDER, SETTING OR EMAIL IS WRITTEN BY THIS FILE.
 *    `$_SERVER['HTTP_HOST']` is temporarily reassigned in-process to exercise
 *    both environments and is RESTORED in every path, including on failure.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$smg_failures = 0;
$smg_passes   = 0;

function smg_assert( $condition, $label ) {
	if ( $condition ) {
		echo "PASS: {$label}\n";
		++$GLOBALS['smg_passes'];
	} else {
		echo "FAIL: {$label}\n";
		++$GLOBALS['smg_failures'];
	}
}

echo "\n══════════ THE STAGING ORDER-EMAIL GUARD — theme 1.19.281 ══════════\n";

/* ───────────────────────────────────────────────────────────────────────────
 * §1 · THE MODULE IS LOADED AND USES THE CODEBASE'S OWN HOST DETECTION
 * ─────────────────────────────────────────────────────────────────────────── */
echo "\n--- §1 the module, and the pattern it matches ---\n";

smg_assert(
	function_exists( 'bhp_staging_mail_guard_is_staging' )
	&& function_exists( 'bhp_staging_mail_guard_disable' )
	&& function_exists( 'bhp_staging_mail_guard_email_ids' ),
	'1.1 the staging mail guard is loaded'
);

/*
 * ⭐ IT MUST REUSE `BHP_Analytics_Config`, NOT INVENT A SECOND HOST TEST. Two
 *    definitions of "is this staging?" is how one of them goes stale.
 */
$smg_src = file_get_contents( get_template_directory() . '/inc/staging-mail-guard.php' );
smg_assert(
	false !== strpos( $smg_src, 'BHP_Analytics_Config::is_staging()' )
	&& false !== strpos( $smg_src, 'BHP_Analytics_Config::STAGING_HOST' ),
	'1.2 host detection routes through BHP_Analytics_Config — the theme\'s established staging guard'
);
smg_assert(
	false === strpos( $smg_src, 'update_option' ) && false === strpos( $smg_src, 'add_option' ),
	'1.3 ⛔ it writes NO WooCommerce setting — the Andrew gate is not crossed'
);
smg_assert(
	class_exists( 'BHP_Analytics_Config' )
	&& 'staging2.braveheartspublishing.com' === BHP_Analytics_Config::STAGING_HOST,
	'1.4 the staging host literal is the canonical one'
);

/* ───────────────────────────────────────────────────────────────────────────
 * §2 · ⛔⛔ THE PROPERTY THAT MATTERS — IT CANNOT FIRE ON PRODUCTION
 * ─────────────────────────────────────────────────────────────────────────── */
echo "\n--- §2 production is never suppressed ---\n";

$smg_saved_host    = isset( $_SERVER['HTTP_HOST'] ) ? $_SERVER['HTTP_HOST'] : null;
$smg_saved_present = array_key_exists( 'HTTP_HOST', $_SERVER );

try {
	$_SERVER['HTTP_HOST'] = 'braveheartspublishing.com';
	smg_assert(
		false === bhp_staging_mail_guard_is_staging(),
		'2.1 PRODUCTION host -> the guard is OFF'
	);
	smg_assert(
		true === bhp_staging_mail_guard_disable( true ),
		'2.2 …so an enabled order email STAYS ENABLED on production'
	);

	$_SERVER['HTTP_HOST'] = 'www.braveheartspublishing.com';
	smg_assert(
		false === bhp_staging_mail_guard_is_staging(),
		'2.3 the www production host -> still OFF'
	);

	/*
	 * ⛔ A HOST THIS GUARD HAS NEVER HEARD OF IS PRODUCTION. That fail-safe
	 *    direction is inherited from BHP_Analytics_Config's own docblock and
	 *    is the reason an unknown environment can never silence a customer's
	 *    order email.
	 */
	$_SERVER['HTTP_HOST'] = 'some-unknown-host.example';
	smg_assert(
		false === bhp_staging_mail_guard_is_staging(),
		'2.4 ⛔ an UNKNOWN host is treated as production — fail-safe, never fail-quiet'
	);

	/*
	 * ⛔ THE NEAR-MISS CLASS. A host that merely CONTAINS the staging host, or
	 *    is a subdomain of it, is not it. Substring matching is how a guard
	 *    like this is subverted.
	 */
	foreach ( array(
		'staging2.braveheartspublishing.com.evil.example',
		'notstaging2.braveheartspublishing.com',
		'staging2.braveheartspublishing.com.br',
	) as $smg_near ) {
		$_SERVER['HTTP_HOST'] = $smg_near;
		smg_assert(
			false === bhp_staging_mail_guard_is_staging(),
			sprintf( '2.5 ⛔ near-miss host "%s" is NOT staging — the comparison is identity, not substring', $smg_near )
		);
	}

	/* ───────────────────────────────────────────────────────────────────────
	 * §3 · AND IT DOES FIRE ON STAGING
	 * ─────────────────────────────────────────────────────────────────────── */
	echo "\n--- §3 staging IS suppressed ---\n";

	$_SERVER['HTTP_HOST'] = BHP_Analytics_Config::STAGING_HOST;
	smg_assert(
		true === bhp_staging_mail_guard_is_staging(),
		'3.1 the staging host -> the guard is ON'
	);
	smg_assert(
		false === bhp_staging_mail_guard_disable( true ),
		'3.2 …so an enabled order email is DISABLED on staging'
	);

	/* Case-insensitive, as `is_staging()` already is. */
	$_SERVER['HTTP_HOST'] = strtoupper( BHP_Analytics_Config::STAGING_HOST );
	smg_assert(
		true === bhp_staging_mail_guard_is_staging(),
		'3.3 …and an upper-cased staging host still matches'
	);

	/* ───────────────────────────────────────────────────────────────────────
	 * §4 · THE WP-CLI / CRON CLAUSE, AND EXACTLY HOW FAR IT REACHES
	 * ─────────────────────────────────────────────────────────────────────── */
	echo "\n--- §4 the no-HTTP-host clause ---\n";

	unset( $_SERVER['HTTP_HOST'] );
	$smg_home_host = strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) );
	$smg_on_stg    = ( BHP_Analytics_Config::STAGING_HOST === $smg_home_host );
	smg_assert(
		bhp_staging_mail_guard_is_staging() === $smg_on_stg,
		sprintf(
			'4.1 with no HTTP host, the guard follows home_url() (home=%s, guard=%s)',
			$smg_home_host,
			bhp_staging_mail_guard_is_staging() ? 'ON' : 'OFF'
		)
	);
	/*
	 * ⛔⛔ THE ONE THAT PROVES THE RESIDUAL RISK IS BOUNDED. Even if the
	 *     database claimed to be staging, a REAL BROWSER REQUEST on production
	 *     still carries a production HTTP host, and clause 2 requires the HTTP
	 *     host to be ABSENT. A customer checking out on production therefore
	 *     still gets their email under the worst realistic misconfiguration.
	 */
	$_SERVER['HTTP_HOST'] = 'braveheartspublishing.com';
	smg_assert(
		false === bhp_staging_mail_guard_is_staging(),
		'4.2 ⛔⛔ a production browser request is NEVER suppressed, whatever home_url() says'
	);
} finally {
	if ( $smg_saved_present ) {
		$_SERVER['HTTP_HOST'] = $smg_saved_host;
	} else {
		unset( $_SERVER['HTTP_HOST'] );
	}
}

/* ───────────────────────────────────────────────────────────────────────────
 * §5 · THE RIGHT EMAILS, AND ONLY THE RIGHT EMAILS
 * ─────────────────────────────────────────────────────────────────────────── */
echo "\n--- §5 scope: order emails only ---\n";

$smg_ids = bhp_staging_mail_guard_email_ids();
foreach ( array( 'new_order', 'cancelled_order', 'failed_order', 'customer_processing_order', 'customer_completed_order' ) as $smg_id ) {
	smg_assert(
		in_array( $smg_id, $smg_ids, true ),
		sprintf( '5.1 "%s" is suppressed on staging', $smg_id )
	);
}
/*
 * ⛔ NOT ORDER EMAILS, AND NOT THIS GUARD'S BUSINESS. Suppressing a password
 *    reset would break real QA; suppressing the lead-magnet path would break
 *    funnel QA silently, which is worse.
 */
foreach ( array( 'customer_new_account', 'customer_reset_password' ) as $smg_id ) {
	smg_assert(
		! in_array( $smg_id, $smg_ids, true ),
		sprintf( '5.2 ⛔ "%s" is NOT suppressed — it is not an order email', $smg_id )
	);
}
smg_assert(
	false === strpos( file_get_contents( get_template_directory() . '/inc/staging-mail-guard.php' ), "add_filter( 'wp_mail'" )
	&& false === strpos( $smg_src, "pre_wp_mail" ),
	'5.3 ⛔ it does NOT blanket-kill wp_mail — the funnel and Mailchimp QA paths keep working'
);

/*
 * ⭐ THE FILTERS ARE REGISTERED THROUGH WOOCOMMERCE'S OWN MECHANISM, on
 *    `woocommerce_email_init`, so a site without WooCommerce runs none of this.
 */
smg_assert(
	false !== strpos( $smg_src, "add_action( 'woocommerce_email_init'" )
	&& false !== strpos( $smg_src, "woocommerce_email_enabled_" ),
	'5.4 suppression uses WooCommerce\'s own woocommerce_email_enabled_{id} filter'
);

echo "\n=== RESULT: {$GLOBALS['smg_passes']} passed, {$GLOBALS['smg_failures']} failed ===\n";
