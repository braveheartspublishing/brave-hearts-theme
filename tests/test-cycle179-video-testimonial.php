<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * THE VIDEO TESTIMONIAL SUITE — theme 1.19.395,
 * `CYCLE179-LD-BUILD-395-TESTIMONIAL`. Founder seals 1294 to 1301.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Run on STAGING ONLY via:
 *   wp eval-file wp-content/themes/brave-hearts-theme-deploy-explorer-expedition-guides/tests/test-cycle179-video-testimonial.php --user=1 --url=https://staging2.braveheartspublishing.com
 *
 * ---------------------------------------------------------------------------
 * ⭐⭐ WHAT THIS SUITE IS ACTUALLY GUARDING
 * ---------------------------------------------------------------------------
 * This feature holds a member of the public's name, email address, a signed
 * release and a link to a video of their family. ⛔ THE ASSERTIONS THAT MATTER
 * ARE NOT "DOES THE FORM RENDER". They are:
 *
 *   · the post type cannot be reached from outside (§1);
 *   · a submission cannot be created without a signed, evidenced consent (§4);
 *   · a video never becomes publicly reachable (§3, §7);
 *   · a decision cannot silently mail somebody twice (§6);
 *   · unapproved copy cannot quietly stop being marked as unapproved (§8);
 *   · nothing here can send real mail from staging (§0, §6).
 *
 * ---------------------------------------------------------------------------
 * ⚠ WHAT IT WRITES: private `bhp_testimonial` posts on STAGING, each tagged
 *   with `_bhp_cycle179_probe`, and every one is force-deleted in §9. It
 *   touches NO product, price, coupon, stock, shipping, tax or payment record.
 *   It creates NO WooCommerce coupon, because nothing in this feature does.
 * ---------------------------------------------------------------------------
 */

defined( 'ABSPATH' ) || exit;

/*
 * ⛔⛔ OUTBOUND MAIL IS BLOCKED FOR THE WHOLE OF THIS SUITE.
 *
 * ⭐ This feature composes three emails to a real address a parent typed in.
 *    Two of them are triggered from a save. This include stops every one at
 *    `pre_wp_mail`, captures it instead, and PROVES the block at include time
 *    rather than assuming it.
 *
 * ⛔ NO ISO DATE APPEARS IN THIS BLOCK, AND THAT IS DELIBERATE. Two suites scan
 *    their OWN source for one and fail if they find it. The dated evidence
 *    lives in tests/bootstrap-mail-guard.php, which nothing scans.
 */
require_once get_template_directory() . '/tests/bootstrap-mail-guard.php';

$GLOBALS['bhp_vt_pass']  = 0;
$GLOBALS['bhp_vt_fail']  = 0;
$GLOBALS['bhp_vt_posts'] = array();

/**
 * One assertion.
 *
 * @param string $label  What is being asserted.
 * @param bool   $ok     The result.
 * @param string $detail Extra context printed on failure.
 * @return void
 */
function bhp_vt_ok( $label, $ok, $detail = '' ) {
	if ( $ok ) {
		$GLOBALS['bhp_vt_pass']++;
		echo "PASS  {$label}\n";
		return;
	}
	$GLOBALS['bhp_vt_fail']++;
	echo "FAIL  {$label}" . ( $detail ? "  -- {$detail}" : '' ) . "\n";
}

/**
 * Create one probe submission.
 *
 * @param array $meta Extra meta to set.
 * @return int Post id.
 */
function bhp_vt_make( $meta = array() ) {
	$id = wp_insert_post(
		array(
			'post_type'   => BHP_TESTIMONIAL_CPT,
			'post_status' => 'private',
			'post_title'  => 'CYCLE179 probe ' . wp_generate_password( 8, false, false ),
		),
		true
	);
	if ( is_wp_error( $id ) ) {
		return 0;
	}
	$GLOBALS['bhp_vt_posts'][] = (int) $id;
	update_post_meta( $id, '_bhp_cycle179_probe', '1' );
	update_post_meta( $id, BHP_TESTIMONIAL_STATUS_META, 'submitted' );
	foreach ( $meta as $k => $v ) {
		update_post_meta( $id, $k, $v );
	}
	return (int) $id;
}

echo "\n=== CYCLE179 VIDEO TESTIMONIAL SUITE ===\n\n";

/* ═══════════════════════════════════════════════════════════════════════════
 * §0 — THE ENVIRONMENT AND THE MAIL GUARD
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⛔ THIS SECTION RUNS FIRST AND EVERYTHING ELSE DEPENDS ON IT. A suite that
 *    exercises three email paths on a host that can actually send is a suite
 *    that mails strangers.
 */
echo "-- §0 environment and mail guard --\n";

bhp_vt_ok(
	'§0.1 the feature is loaded',
	function_exists( 'bhp_testimonial_validate_video_url' )
		&& function_exists( 'bhp_testimonial_score' )
		&& function_exists( 'bhp_testimonial_render_form' )
);

bhp_vt_ok(
	'§0.2 the staging detector exists and answers',
	function_exists( 'bhp_staging_mail_guard_is_staging' ),
	'inc/staging-mail-guard.php did not load'
);

bhp_vt_ok(
	'§0.3 mail from this feature is CAPTURED, not sent',
	function_exists( 'bhp_testimonial_should_capture_mail' ) && bhp_testimonial_should_capture_mail() === true,
	'capture is OFF -- refusing to treat a send as a pass'
);

/*
 * ⛔ HARD STOP. Not a skip, not a warning. If the capture is off, the sections
 *    below would put real messages on the wire.
 */
if ( ! bhp_testimonial_should_capture_mail() ) {
	echo "\nSUITE FAIL -- mail capture is not active. Nothing further was run.\n";
	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		WP_CLI::halt( 1 );
	}
	return;
}

/* ═══════════════════════════════════════════════════════════════════════════
 * §1 — THE POST TYPE IS PRIVATE, AND EVERY FLAG IS ASSERTED
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⭐ EACH FLAG IS ITS OWN ASSERTION RATHER THAN ONE COMBINED CHECK. A single
 *    `$ok = $a && $b && $c` line tells you the post type is wrong; seven lines
 *    tell you WHICH flag somebody flipped.
 */
echo "\n-- §1 the post type is private --\n";

$pt = get_post_type_object( BHP_TESTIMONIAL_CPT );
bhp_vt_ok( '§1.0 the post type is registered', $pt instanceof WP_Post_Type );

if ( $pt instanceof WP_Post_Type ) {
	bhp_vt_ok( '§1.1 public is false', false === $pt->public, var_export( $pt->public, true ) );
	bhp_vt_ok( '§1.2 publicly_queryable is false', false === $pt->publicly_queryable, var_export( $pt->publicly_queryable, true ) );
	bhp_vt_ok( '§1.3 exclude_from_search is true', true === $pt->exclude_from_search, var_export( $pt->exclude_from_search, true ) );
	bhp_vt_ok( '§1.4 show_in_rest is false', false === $pt->show_in_rest, var_export( $pt->show_in_rest, true ) );
	bhp_vt_ok( '§1.5 has_archive is false', false === $pt->has_archive, var_export( $pt->has_archive, true ) );
	bhp_vt_ok( '§1.6 rewrite is off', false === $pt->rewrite, var_export( $pt->rewrite, true ) );
	bhp_vt_ok( '§1.7 query_var is off', false === $pt->query_var, var_export( $pt->query_var, true ) );
	bhp_vt_ok( '§1.8 show_in_nav_menus is false', false === $pt->show_in_nav_menus, var_export( $pt->show_in_nav_menus, true ) );
	bhp_vt_ok( '§1.9 show_ui is TRUE, so the queue is usable', true === $pt->show_ui, var_export( $pt->show_ui, true ) );
	/*
	 * ⚠ ASSERTED AS "NO EDITOR", NOT AS AN EXACT LIST, AND THE REASON IS
	 *   PRACTICAL. Plugins add post-type supports to every registered type
	 *   (`custom-fields`, `revisions`) on their own hooks, so an exact-equality
	 *   assertion here would go red for a reason that is not this feature's
	 *   defect — the failure mode the RUNBOOK's own corrected `.min.css`
	 *   assertion note warns about. ⭐ The load-bearing claim is narrower and is
	 *   asserted directly: `title` is there, `editor` is NOT. The full resolved
	 *   list is printed either way so a surprise addition is still visible.
	 */
	$supports = array_keys( (array) get_all_post_type_supports( BHP_TESTIMONIAL_CPT ) );
	bhp_vt_ok( '§1.10a title is supported', in_array( 'title', $supports, true ), implode( ',', $supports ) );
	bhp_vt_ok(
		'§1.10b NO editor -- no free-text box for a child name to be typed into',
		! in_array( 'editor', $supports, true ),
		implode( ',', $supports )
	);
	echo "      (post type supports resolved to: " . implode( ', ', $supports ) . ")\n";
}

/*
 * ⛔ THE REST ROUTE IS ASKED FOR BY NAME, not inferred from the flag above.
 *    `show_in_rest => false` is the cause; the ABSENCE of a route is the
 *    effect, and it is the effect that leaks data.
 */
$routes = rest_get_server()->get_routes();
$rest_hits = array();
foreach ( array_keys( $routes ) as $route ) {
	if ( false !== strpos( $route, BHP_TESTIMONIAL_CPT ) ) {
		$rest_hits[] = $route;
	}
}
bhp_vt_ok( '§1.11 NO REST route mentions the post type', array() === $rest_hits, implode( ' ', $rest_hits ) );

/* ═══════════════════════════════════════════════════════════════════════════
 * §2 — THE STATUS FLOW
 * ═══════════════════════════════════════════════════════════════════════════
 */
echo "\n-- §2 the status flow --\n";

bhp_vt_ok(
	'§2.1 exactly the four statuses seal 1301 names',
	array( 'submitted', 'passed', 'failed', 'published' ) === array_keys( bhp_testimonial_statuses() ),
	implode( ',', array_keys( bhp_testimonial_statuses() ) )
);

bhp_vt_ok( '§2.2 an unknown status is rejected', false === bhp_testimonial_status_is_valid( 'approved' ) );
bhp_vt_ok( '§2.3 submitted -> passed is allowed', true === bhp_testimonial_status_can_move( 'submitted', 'passed' ) );
bhp_vt_ok( '§2.4 submitted -> failed is allowed', true === bhp_testimonial_status_can_move( 'submitted', 'failed' ) );
bhp_vt_ok( '§2.5 passed -> published is allowed', true === bhp_testimonial_status_can_move( 'passed', 'published' ) );

/*
 * ⭐ THE ASSERTION THAT CARRIES THE PROMISE. The fail note tells a parent their
 *    video was not used. A `failed -> published` move would make that a lie,
 *    and it is the only transition the flow forbids outright.
 */
bhp_vt_ok( '§2.6 failed -> published is FORBIDDEN', false === bhp_testimonial_status_can_move( 'failed', 'published' ) );
bhp_vt_ok( '§2.7 a reviewed submission cannot become unreviewed', false === bhp_testimonial_status_can_move( 'passed', 'submitted' ) );
bhp_vt_ok( '§2.8 a no-op save is allowed', true === bhp_testimonial_status_can_move( 'passed', 'passed' ) );

$probe = bhp_vt_make();
bhp_vt_ok( '§2.9 a new submission reads as submitted', 'submitted' === bhp_testimonial_get_status( $probe ) );
update_post_meta( $probe, BHP_TESTIMONIAL_STATUS_META, 'nonsense' );
bhp_vt_ok(
	'§2.10 a corrupt stored status reads back as submitted, not as itself',
	'submitted' === bhp_testimonial_get_status( $probe ),
	bhp_testimonial_get_status( $probe )
);

/* ═══════════════════════════════════════════════════════════════════════════
 * §3 — THE URL VALIDATOR
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⛔ THE HOSTILE CASES ARE THE POINT. A `strpos( $url, 'youtube.com' )` check
 *    passes three of the rejections below, and that is the shape this
 *    validator is most often written in.
 */
echo "\n-- §3 the video link validator --\n";

$accept = array(
	'https://www.youtube.com/watch?v=abc123',
	'https://youtu.be/abc123',
	'https://vimeo.com/123456789',
	'https://drive.google.com/file/d/abc/view',
	'https://www.dropbox.com/s/abc/video.mp4',
);
foreach ( $accept as $url ) {
	$r = bhp_testimonial_validate_video_url( $url );
	bhp_vt_ok( "§3.a accepts {$url}", true === $r['ok'], $r['reason'] );
}

$reject = array(
	''                                          => 'empty',
	'not a url at all'                          => 'unparseable',
	'http://www.youtube.com/watch?v=abc'        => 'not_https',
	'https://youtube.com.evil.example/watch'    => 'host_not_allowed',
	'https://evil.example/?u=youtube.com'       => 'host_not_allowed',
	'https://evil.example/youtube.com/x'        => 'host_not_allowed',
	'javascript:alert(1)'                       => 'unparseable',
	'https://user:pw@youtube.com/watch'         => 'credentials_in_url',
	'https://braveheartspublishing.com/x.mp4'   => 'host_not_allowed',
);
foreach ( $reject as $url => $why ) {
	$r = bhp_testimonial_validate_video_url( $url );
	bhp_vt_ok(
		"§3.r rejects " . ( '' === $url ? '(empty string)' : $url ),
		false === $r['ok'],
		'accepted as ' . $r['url']
	);
}

/*
 * ⭐ A SUBDOMAIN OF AN ALLOWED HOST IS ALLOWED, and it is asserted separately
 *    because the leading-dot rule that permits it is the same rule that has to
 *    keep rejecting `youtube.com.evil.example` above.
 */
$sub = bhp_testimonial_validate_video_url( 'https://m.youtube.com/watch?v=abc' );
bhp_vt_ok( '§3.s a real subdomain of an allowed host is accepted', true === $sub['ok'], $sub['reason'] );

/* ═══════════════════════════════════════════════════════════════════════════
 * §4 — THE CONSENT RECORD
 * ═══════════════════════════════════════════════════════════════════════════
 */
echo "\n-- §4 the consent record --\n";

$fields = bhp_testimonial_consent_fields();
foreach ( array(
	'_bhp_testimonial_consent_name',
	'_bhp_testimonial_consent_checked',
	'_bhp_testimonial_consent_time',
	'_bhp_testimonial_consent_terms',
	'_bhp_testimonial_consent_ip',
	'_bhp_testimonial_guardian',
	'_bhp_testimonial_child_appears',
) as $key ) {
	bhp_vt_ok( "§4.1 the record declares {$key}", isset( $fields[ $key ] ) );
}

$record = bhp_testimonial_build_consent( 'Jane Q Sample', true, true, true, '203.0.113.9', '2026-09-07T12:00:00+00:00' );

bhp_vt_ok( '§4.2 the typed signature is stored verbatim', 'Jane Q Sample' === $record['_bhp_testimonial_consent_name'] );
bhp_vt_ok( '§4.3 the checkbox is stored as "1"', '1' === $record['_bhp_testimonial_consent_checked'] );
bhp_vt_ok( '§4.4 the timestamp is stored', '2026-09-07T12:00:00+00:00' === $record['_bhp_testimonial_consent_time'] );
bhp_vt_ok( '§4.5 the terms VERSION is stored with the record', BHP_TESTIMONIAL_TERMS_VERSION === $record['_bhp_testimonial_consent_terms'] );
bhp_vt_ok( '§4.6 the submitting address is stored', '203.0.113.9' === $record['_bhp_testimonial_consent_ip'] );
bhp_vt_ok( '§4.7 the guardian affirmation is stored', '1' === $record['_bhp_testimonial_guardian'] );

$no_child = bhp_testimonial_build_consent( 'Jane Q Sample', true, false, false, '203.0.113.9' );
bhp_vt_ok( '§4.8 no child, no guardian flag', '' === $no_child['_bhp_testimonial_guardian'] );
bhp_vt_ok( '§4.9 no child appears is recorded as such', '' === $no_child['_bhp_testimonial_child_appears'] );

/*
 * ⛔ THE TERMS VERSION IS STILL A PLACEHOLDER, AND THE SUITE SAYS SO OUT LOUD.
 *    ⭐ THIS ASSERTION IS EXPECTED TO BE **INVERTED** THE DAY THE RELEASE TEXT IS
 *    APPROVED. It is here so that flipping the text without stamping a new
 *    version cannot pass silently, which would leave every consent record
 *    pointing at a version string that no longer describes what was signed.
 */
bhp_vt_ok(
	'§4.10 the terms version still declares itself a placeholder',
	0 === strpos( BHP_TESTIMONIAL_TERMS_VERSION, 'PLACEHOLDER-' ),
	BHP_TESTIMONIAL_TERMS_VERSION . ' -- if the release text is now approved, this assertion is the one to update'
);

/* ═══════════════════════════════════════════════════════════════════════════
 * §5 — THE CHECKLIST AND THE SCORE
 * ═══════════════════════════════════════════════════════════════════════════
 */
echo "\n-- §5 the checklist --\n";

$all = array_fill_keys( array_keys( bhp_testimonial_checklist() ), 1 );

$s = bhp_testimonial_score( $all, array() );
bhp_vt_ok( '§5.1 every box ticked and no auto-fail passes', 'passed' === $s['verdict'], $s['verdict'] );

$one_short = $all;
unset( $one_short['book_shown'] );
$s = bhp_testimonial_score( $one_short, array() );
bhp_vt_ok( '§5.2 one missing item fails', 'failed' === $s['verdict'] );
bhp_vt_ok( '§5.3 the fail names the missing item', array( 'book_shown' ) === $s['missing'], implode( ',', $s['missing'] ) );

/*
 * ⭐ THE ASSERTION THAT MAKES THE AUTO-FAILS MEAN ANYTHING. A scorer who ticks
 *    every checklist box AND ticks "generated rather than filmed" must get a
 *    fail; anything else lets an AI-generated video earn a printed book.
 */
$s = bhp_testimonial_score( $all, array( 'ai_generated' => 1 ) );
bhp_vt_ok( '§5.4 an auto-fail OUTRANKS a fully ticked checklist', 'failed' === $s['verdict'], $s['verdict'] );
bhp_vt_ok( '§5.5 the triggered auto-fail is named', array( 'ai_generated' ) === $s['triggered'] );
bhp_vt_ok( '§5.6 an auto-fail suppresses the missing list', array() === $s['missing'] );

bhp_vt_ok( '§5.7 nothing ticked fails', 'failed' === bhp_testimonial_score( array(), array() )['verdict'] );
bhp_vt_ok( '§5.8 junk input does not fatal', 'failed' === bhp_testimonial_score( 'nonsense', 42 )['verdict'] );

bhp_vt_ok(
	'§5.9 all seven auto-fails from seal 1301 are present',
	7 === count( bhp_testimonial_auto_fails() ),
	(string) count( bhp_testimonial_auto_fails() )
);

/*
 * ⛔ THE SENTIMENT RULE IS ASSERTED AS A PROPERTY OF THE CHECKLIST, not as a
 *    comment. Not one scored item may ask about an opinion.
 */
$opinion_words = array( 'like', 'liked', 'love', 'enjoy', 'positive', 'favorable', 'recommend', 'praise', 'opinion' );
$offenders     = array();
foreach ( bhp_testimonial_checklist() as $key => $label ) {
	foreach ( $opinion_words as $w ) {
		if ( preg_match( '/\b' . preg_quote( $w, '/' ) . '\b/i', $label ) ) {
			$offenders[] = $key;
		}
	}
}
bhp_vt_ok( '§5.10 NO checklist item asks about sentiment', array() === $offenders, implode( ',', $offenders ) );

bhp_vt_ok(
	'§5.11 the sentiment notice exists and says the reward does not depend on opinion',
	false !== stripos( bhp_testimonial_sentiment_notice(), 'favorable' )
		&& false !== stripos( bhp_testimonial_sentiment_notice(), 'no bearing' )
);

/* ═══════════════════════════════════════════════════════════════════════════
 * §6 — THE EMAILS AND THE MAIL GUARD
 * ═══════════════════════════════════════════════════════════════════════════
 */
echo "\n-- §6 the emails --\n";

/*
 * ⭐⭐ 1.19.406 (2026-09-08, `CYCLE179-CX-BUILD-406`) — §6.2 WAS ASSERTING A
 *     THING THAT CANNOT BE TRUE ON A LONG-LIVED ENVIRONMENT, AND IT HAD
 *     STARTED FAILING FOR THAT REASON RATHER THAN FOR A REAL ONE.
 *
 * ⛔⛔ THE SUPERSEDED ASSERTION, PRESERVED STRUCK AT THE LINE:
 *
 *      ~~$before = count( bhp_testimonial_mail_log() );
 *        bhp_vt_ok( '§6.2 the capture log grew by one',
 *                   count( bhp_testimonial_mail_log() ) === $before + 1 );~~
 *
 * ⚠️ WHY IT COULD NEVER PASS AGAIN. The writer caps the option:
 *    `inc/video-testimonial-form.php:94  const BHP_TESTIMONIAL_MAIL_LOG_MAX = 30;`
 *    `inc/video-testimonial-form.php:157 array_slice( $log, 0, ...MAX )`.
 *    Every run of this suite appends one entry, so the log climbs to 30 and
 *    then STAYS at 30 — `count()` stops growing while the write keeps
 *    succeeding perfectly. Read live on staging2 2026-09-08:
 *    `wp eval 'echo count(bhp_testimonial_mail_log());'` -> 30.
 *    `CYCLE179-LD-PLUGIN-1.8.87` measured the same value and re-ran it 3/3
 *    identically, so it is deterministic saturation, not flake.
 *
 * ⛔ THE FIXTURE WAS NOT CLEARED TO MAKE THIS GREEN. Emptying the option
 *    would have hidden a test-design defect behind a passing row, and the
 *    defect is the interesting part: a counter is the wrong instrument for a
 *    capped ring buffer. ⭐ SO THE ROW NOW ASSERTS WHAT THE FEATURE ACTUALLY
 *    PROMISES — that THIS send landed at the head of the log — which is true
 *    whether the log is empty, half full or saturated, and is a STRICTLY
 *    STRONGER claim than the old count: it proves the entry is OURS, where
 *    `+1` would have been satisfied by any write at all.
 *
 * ⭐ A UNIQUE SUBJECT PER RUN is what makes that identity check possible;
 *    two runs in the same second previously wrote indistinguishable rows.
 *    §6.4 below still sees its `[PLACEHOLDER COPY]` prefix, which the
 *    composer adds, so the nonce does not disturb it.
 */
$vt_nonce   = 'vt-' . uniqid( '', true );
$before_log = bhp_testimonial_mail_log();
$before     = count( $before_log );
$before_top = ( $before_log && isset( $before_log[0]['subject'] ) ) ? (string) $before_log[0]['subject'] : '(empty log)';

$r = bhp_testimonial_send( 'bhp-cycle179+vt@example.com', 'Suite probe ' . $vt_nonce, 'Body.' );

bhp_vt_ok( '§6.1 the send was CAPTURED, not sent', true === $r['captured'] && false === $r['sent'] );

$after_log = bhp_testimonial_mail_log();
$after_top = ( $after_log && isset( $after_log[0]['subject'] ) ) ? (string) $after_log[0]['subject'] : '';

bhp_vt_ok(
	'§6.2 THIS send is the newest entry in the capture log',
	false !== strpos( $after_top, $vt_nonce ),
	sprintf( 'newest subject was %s, is now %s', $before_top, '' === $after_top ? '(empty log)' : $after_top )
);

/*
 * ⭐ AND THE CAP IS ASSERTED AS A CAP, which is the behaviour the old row was
 *    accidentally testing. Below the cap the log grows by exactly one; at the
 *    cap it holds. Stating both means neither an unbounded log nor a silently
 *    dropped write can pass.
 */
bhp_vt_ok(
	'§6.2b the log grew by one, or held at BHP_TESTIMONIAL_MAIL_LOG_MAX',
	count( $after_log ) === min( $before + 1, BHP_TESTIMONIAL_MAIL_LOG_MAX ),
	sprintf( 'before %d, after %d, max %d', $before, count( $after_log ), BHP_TESTIMONIAL_MAIL_LOG_MAX )
);

$last = bhp_testimonial_mail_log();
$last = $last ? $last[0] : array();
bhp_vt_ok( '§6.3 the captured message is stored in full', ! empty( $last['to'] ) && ! empty( $last['subject'] ) && ! empty( $last['body'] ) );
bhp_vt_ok(
	'§6.4 an unapproved-copy subject carries the placeholder prefix',
	0 === strpos( (string) $last['subject'], '[PLACEHOLDER COPY]' ),
	(string) $last['subject']
);

/*
 * ⛔ AND NOTHING REACHED wp_mail(). The bootstrap's own log is the second
 *    witness: the capture returning true is this feature's claim, and an empty
 *    `wp_mail` log for this address is the independent check on it.
 */
bhp_vt_ok(
	'§6.5 nothing for that address ever reached wp_mail()',
	array() === bhp_test_mail_find( 'bhp-cycle179+vt@example.com' ),
	'a message reached wp_mail -- the feature-level capture did not hold'
);

$c = bhp_testimonial_compose_confirmation( 'Jane' );
bhp_vt_ok( '§6.6 the confirmation names the submitter', false !== strpos( $c['body'], 'Jane' ) );
bhp_vt_ok( '§6.7 the confirmation does NOT carry a code', false === stripos( $c['body'], 'coupon code' ) );

$p = bhp_testimonial_compose_pass( 'Jane', 'SAMPLE-CODE-123' );
bhp_vt_ok( '§6.8 the passing note carries the code it was given', false !== strpos( $p['body'], 'SAMPLE-CODE-123' ) );

$f = bhp_testimonial_compose_fail( 'Jane', array( 'The book is visible on camera' ) );
bhp_vt_ok( '§6.9 the fail note names the missing item', false !== strpos( $f['body'], 'The book is visible on camera' ) );
bhp_vt_ok( '§6.10 a fail note with no reasons still says something useful', '' !== trim( bhp_testimonial_compose_fail( 'Jane', array() )['body'] ) );

/* ═══════════════════════════════════════════════════════════════════════════
 * §7 — NOTHING PRINTS PUBLICLY, AND NOTHING IS STORED PUBLICLY
 * ═══════════════════════════════════════════════════════════════════════════
 */
echo "\n-- §7 nothing is public --\n";

$pub = bhp_vt_make(
	array(
		'_bhp_testimonial_name'      => 'Publicly Visible Sample Name',
		'_bhp_testimonial_email'     => 'bhp-cycle179+leak@example.com',
		'_bhp_testimonial_video_url' => 'https://youtu.be/leakprobe',
	)
);

bhp_vt_ok( '§7.1 a submission is stored as private', 'private' === get_post_status( $pub ) );

/*
 * ⛔ THE PERMALINK IS ASKED FOR AND THEN JUDGED. A post type with `rewrite`
 *    off still returns SOMETHING from get_permalink(); what matters is that it
 *    is not a front-end URL that resolves to the submission.
 */
$perm = (string) get_permalink( $pub );
bhp_vt_ok(
	'§7.2 no pretty permalink resolves to a submission',
	'' === $perm || false === strpos( $perm, BHP_TESTIMONIAL_CPT . '/' ),
	$perm
);

/*
 * ⛔ THIS ASSERTION IS COUNTED IN SQL, NOT THROUGH `WP_Query`, AND THE REASON
 *    IS THE GUARD THIS VERY SUITE EXISTS TO PROVE.
 *
 *    `bhp_testimonial_never_on_front_end()` strips BHP_TESTIMONIAL_CPT out of
 *    the `post_type` of any NON-ADMIN query. WP-CLI is non-admin, so a
 *    `WP_Query` asking for this post type here comes back having been rewritten
 *    to `post_type = 'post'` — and then happily counts every PUBLISHED BLOG
 *    POST on the site as though it were a submission in publish status.
 *
 * ⚠ MEASURED, NOT REASONED ABOUT: at 1.19.395 that made this line report
 *   `FAIL ... -- 37`, where 37 was the number of published blog posts and the
 *   number of testimonials in publish status was zero. The product was correct
 *   and the assertion was lying, which is the worse of the two failures because
 *   it trains a reader to ignore a red line.
 *
 * ⭐ THE INVARIANT IS ABOUT ROWS IN THE DATABASE, so it is asked of the
 *    database. Nothing can rewrite this query on its way past.
 */
global $wpdb;
$publish_rows = (int) $wpdb->get_var(
	$wpdb->prepare(
		"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = %s AND post_status = %s",
		BHP_TESTIMONIAL_CPT,
		'publish'
	)
);
bhp_vt_ok( '§7.3 no submission is ever in publish status', 0 === $publish_rows, (string) $publish_rows );

/*
 * ⭐ AND THE GUARD ITSELF IS NOW ASSERTED, rather than being an invisible
 *    reason the line above had to change shape. A front-end query that NAMES
 *    the post type must not come back carrying it.
 */
$front_end = new WP_Query(
	array(
		'post_type'      => BHP_TESTIMONIAL_CPT,
		'post_status'    => 'private',
		'posts_per_page' => 50,
	)
);
$front_end_types = array_unique( wp_list_pluck( $front_end->posts, 'post_type' ) );
bhp_vt_ok(
	'§7.3b a front-end query naming the post type never returns one',
	! in_array( BHP_TESTIMONIAL_CPT, $front_end_types, true ),
	implode( ',', $front_end_types )
);

$search = new WP_Query(
	array(
		's'              => 'Publicly Visible Sample Name',
		'post_type'      => 'any',
		'posts_per_page' => 50,
	)
);
$found_in_search = false;
foreach ( (array) $search->posts as $hit ) {
	if ( (int) $hit->ID === (int) $pub ) {
		$found_in_search = true;
	}
}
bhp_vt_ok( '§7.4 a submitter name is not returned by a post_type=any search', false === $found_in_search );

bhp_vt_ok(
	'§7.5 the sitemap filter excludes the post type',
	true === bhp_testimonial_sitemap_exclude( false, BHP_TESTIMONIAL_CPT )
);
bhp_vt_ok(
	'§7.6 the sitemap filter leaves other post types alone',
	false === bhp_testimonial_sitemap_exclude( false, 'post' )
);

/*
 * ⛔⛔ THE PRIVATE STORE IS OUTSIDE THE DOCUMENT ROOT. This is the assertion the
 *     whole upload route rests on, and it is checked against ABSPATH,
 *     WP_CONTENT_DIR and the uploads basedir separately.
 */
bhp_vt_ok(
	'§7.7 the private video store is OUTSIDE the served tree',
	true === bhp_testimonial_private_dir_is_safe(),
	bhp_testimonial_private_dir()
);
bhp_vt_ok(
	'§7.8 a store pointed inside uploads is REFUSED',
	false === bhp_testimonial_private_dir_is_safe( trailingslashit( wp_get_upload_dir()['basedir'] ) . 'anything/' )
);
bhp_vt_ok(
	'§7.9 a store pointed at ABSPATH is REFUSED',
	false === bhp_testimonial_private_dir_is_safe( ABSPATH )
);

/*
 * ⛔ AND THE FILENAME RESOLVER REFUSES TRAVERSAL. The stored name comes from
 *    meta, but meta is not a trust boundary — a plugin, an import or a bad
 *    migration can put anything there.
 */
foreach ( array( '../../wp-config.php', '/etc/passwd', 'a/b.mp4', '..', '' ) as $bad ) {
	bhp_vt_ok(
		'§7.10 the private path resolver refuses ' . ( '' === $bad ? '(empty)' : $bad ),
		'' === bhp_testimonial_private_path( $bad )
	);
}

/*
 * ⭐ THE UPLOAD CAP IS A FLOOR AGAINST THE SERVER, so it can never promise more
 *    than the server takes.
 */
bhp_vt_ok(
	'§7.11 the upload cap never exceeds what the server accepts',
	bhp_testimonial_upload_cap_bytes() <= max( 1, (int) wp_max_upload_size() ),
	'cap=' . bhp_testimonial_upload_cap_bytes() . ' server=' . (int) wp_max_upload_size()
);

/* ═══════════════════════════════════════════════════════════════════════════
 * §8 — THE FORM, THE REQUIRED FIELDS AND THE COPY RAILS
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⭐ ASSERTED AGAINST THE RENDERED MARKUP, NOT AGAINST THE SOURCE. A rail
 *    checked against source passes on a string that is never printed.
 */
echo "\n-- §8 the form and the copy rails --\n";

$html = bhp_testimonial_render_form();

bhp_vt_ok( '§8.1 the form renders', '' !== trim( $html ) && false !== strpos( $html, '<form' ) );

bhp_vt_ok(
	'§8.2 the terms checkbox is present AND required',
	(bool) preg_match( '/<input[^>]*name="bhp_t_terms"[^>]*required/i', $html )
		|| (bool) preg_match( '/<input[^>]*required[^>]*name="bhp_t_terms"/i', $html ),
	'the required attribute is missing from the terms checkbox'
);

bhp_vt_ok(
	'§8.3 the typed signature is present AND required',
	(bool) preg_match( '/<input[^>]*name="bhp_t_signature"[^>]*required/i', $html )
		|| (bool) preg_match( '/<input[^>]*required[^>]*name="bhp_t_signature"/i', $html )
);

bhp_vt_ok( '§8.4 the guardian line is present', false !== strpos( $html, 'name="bhp_t_guardian"' ) );
bhp_vt_ok(
	'§8.5 the guardian line uses the founder\'s own words',
	false !== strpos( $html, 'parent or legal guardian' )
);
bhp_vt_ok( '§8.6 a link to the full terms is present', false !== strpos( $html, 'bhp-testimonial__terms-link' ) );
bhp_vt_ok( '§8.7 the nonce field is present', false !== strpos( $html, 'bhp_testimonial_nonce' ) );
bhp_vt_ok( '§8.8 the honeypot is present', false !== strpos( $html, 'bhp_t_website' ) );
bhp_vt_ok( '§8.9 both upload routes are offered', false !== strpos( $html, 'name="bhp_t_url"' ) && false !== strpos( $html, 'name="bhp_t_file"' ) );

/*
 * ⛔⛔ THERE IS NO FIELD FOR A CHILD'S NAME AND NO FIELD FOR A SHIPPING
 *     ADDRESS. Seal 1301/1275 for the first; seal 1299 for the second, which
 *     dropped seal 1298's address collection when the coupon route was chosen.
 */
foreach ( array( 'child_name', 'childs_name', 'kid_name', 'reader_name', 'address', 'street', 'zip', 'postcode', 'city', 'state' ) as $forbidden ) {
	bhp_vt_ok(
		"§8.10 the form has NO field named {$forbidden}",
		false === stripos( $html, 'name="bhp_t_' . $forbidden . '"' )
	);
}

/*
 * ⭐ THE PLACEHOLDER BAND. While the copy is unapproved it must be visible on
 *    the page, and this assertion is what stops the marking being lost in an
 *    edit.
 */
if ( ! BHP_TESTIMONIAL_COPY_APPROVED ) {
	bhp_vt_ok(
		'§8.11 the unapproved-copy band is rendered',
		false !== strpos( $html, 'FOR ANDREW' ),
		'the placeholder marking is missing from the page'
	);
} else {
	bhp_vt_ok( '§8.11 copy is marked approved, so no band is expected', true );
}

/*
 * The copy rails, against what a customer actually reads. The markup is
 * stripped first so class names and attributes cannot mask a violation or
 * cause a false one.
 */
$text = wp_strip_all_tags( $html );

bhp_vt_ok( '§8.12 no "we" in customer copy', 0 === preg_match( '/\bwe\b/i', $text ), 'found "we"' );
bhp_vt_ok( '§8.13 no "us" in customer copy', 0 === preg_match( '/\bus\b/i', $text ), 'found "us"' );
bhp_vt_ok( '§8.14 no "our" in customer copy', 0 === preg_match( '/\bour\b/i', $text ), 'found "our"' );
bhp_vt_ok( '§8.15 no em dash in customer copy', false === strpos( $text, "\xe2\x80\x94" ) );
bhp_vt_ok( '§8.16 American spelling: no "colouring"', false === stripos( $text, 'colouring' ) );
bhp_vt_ok( '§8.17 no mention of Amazon', false === stripos( $text, 'amazon' ) );

/*
 * ⚠⚠ THE REVIEW RAIL IS SCOPED, AND THE SCOPING IS THE INTERESTING PART.
 *
 * ⛔ A flat ban on the word "review" is WRONG HERE and the first version of
 *    this assertion was exactly that. It failed, correctly, on the founder's
 *    own required consent wording: seal 1295 asks for a checkbox reading
 *    *"I have reviewed the terms and release"*, and that sentence has to be on
 *    the page.
 *
 * ⭐ THE RAIL IS ABOUT A CLAIM, NOT A WORD. What must never appear is the
 *    PRODUCT-REVIEW sense: asking for a review, a customer review, a star
 *    rating. "Reviewed the terms" is a different sense of an ordinary English
 *    word. So the consent sentence is removed FIRST and named explicitly, then
 *    the product-review phrases are hunted in what is left — which also means
 *    the consent sentence going missing is caught by §8.2, not hidden here.
 */
$review_ok = 'I have reviewed the terms and release.';
bhp_vt_ok(
	'§8.18a the consent sentence is present in the founder\'s own words',
	false !== strpos( $text, $review_ok )
);
$scoped = str_replace( $review_ok, '', $text );
$scoped = str_replace( 'Read the full terms and release', '', $scoped );
bhp_vt_ok(
	'§8.18b no product-review language anywhere else in customer copy',
	0 === preg_match( '/\b(customer|verified|star|online|honest|leave a|write a|post a)\s+review|\breviews\b|\breview us\b/i', $scoped ),
	'product-review language reached customer copy'
);
bhp_vt_ok( '§8.19 no mention of a rating', 0 === preg_match( '/\brating(s)?\b|\bstars?\b/i', $text ) );

/*
 * ⭐ PUBLICATION IS NEVER PROMISED, AND THE PAGE SAYS SO RATHER THAN MERELY
 *    STAYING SILENT. Silence is what a hopeful reader fills in themselves.
 */
bhp_vt_ok(
	'§8.20 the page states that qualifying is not a promise of publication',
	false !== stripos( $text, 'not a promise' ) || false !== stripos( $text, 'is not a promise' ),
	'the page never says publication is not promised'
);

/* The same rails against every email a customer receives. */
foreach ( array(
	'confirmation' => bhp_testimonial_compose_confirmation( 'Jane' ),
	'pass'         => bhp_testimonial_compose_pass( 'Jane', 'CODE-1' ),
	'fail'         => bhp_testimonial_compose_fail( 'Jane', array( 'The book is visible on camera' ) ),
) as $which => $mail ) {
	$body = $mail['subject'] . "\n" . $mail['body'];
	bhp_vt_ok( "§8.21 {$which} email: no \"we\"", 0 === preg_match( '/\bwe\b/i', $body ) );
	bhp_vt_ok( "§8.22 {$which} email: no \"our\"", 0 === preg_match( '/\bour\b/i', $body ) );
	bhp_vt_ok( "§8.23 {$which} email: no em dash", false === strpos( $body, "\xe2\x80\x94" ) );
	bhp_vt_ok( "§8.24 {$which} email: no Amazon", false === stripos( $body, 'amazon' ) );
	bhp_vt_ok( "§8.25 {$which} email: no review or rating", 0 === preg_match( '/\breview(s|ed|ing)?\b|\brating(s)?\b/i', $body ) );
	bhp_vt_ok( "§8.26 {$which} email: American spelling", false === stripos( $body, 'colouring' ) );
}

/* ═══════════════════════════════════════════════════════════════════════════
 * §9 — CLEANUP
 * ═══════════════════════════════════════════════════════════════════════════
 */
echo "\n-- §9 cleanup --\n";

$deleted = 0;
foreach ( array_unique( $GLOBALS['bhp_vt_posts'] ) as $id ) {
	if ( get_post( $id ) ) {
		wp_delete_post( $id, true );
		$deleted++;
	}
}
bhp_vt_ok( '§9.1 every probe submission was deleted', $deleted === count( array_unique( $GLOBALS['bhp_vt_posts'] ) ), "deleted={$deleted}" );

$leftover = 0;
foreach ( array_unique( $GLOBALS['bhp_vt_posts'] ) as $id ) {
	if ( get_post( $id ) ) {
		$leftover++;
	}
}
bhp_vt_ok( '§9.2 nothing is left behind', 0 === $leftover, "leftover={$leftover}" );

$strays = get_posts(
	array(
		'post_type'      => BHP_TESTIMONIAL_CPT,
		'post_status'    => 'any',
		'posts_per_page' => 100,
		'fields'         => 'ids',
		'meta_key'       => '_bhp_cycle179_probe',
		'meta_value'     => '1',
	)
);
bhp_vt_ok( '§9.3 no probe submission from any earlier run survives', array() === $strays, implode( ',', (array) $strays ) );

echo "\n";
echo "PASSED: {$GLOBALS['bhp_vt_pass']}   FAILED: {$GLOBALS['bhp_vt_fail']}\n";
echo ( 0 === $GLOBALS['bhp_vt_fail'] ? "SUITE PASS\n" : "SUITE FAIL\n" );
if ( $GLOBALS['bhp_vt_fail'] > 0 && defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::halt( 1 );
}
