<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * THE VIDEO TESTIMONIAL QUEUE — CORE. Theme 1.19.395, 2026-09-07,
 * `CYCLE179-LD-BUILD-395-TESTIMONIAL`. Founder seals 1294 to 1301.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * A parent or grandparent submits a short video of themselves with their
 * reader. Nothing they send is published by this code. The submission lands in
 * a PRIVATE custom post type, Andrew scores it against a published checklist,
 * and a passing submission earns the printed coloring book through a coupon he
 * creates himself.
 *
 * ⛔ THIS FILE PUBLISHES NOTHING, MAILS NOTHING AND CREATES NO COUPON. It is
 *    the domain layer: the post type, the status flow, the checklist, the
 *    consent record and the two upload routes. The form and the emails live in
 *    `inc/video-testimonial-form.php`; the review screen lives in
 *    `inc/video-testimonial-admin.php`.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐⭐ THE UPLOAD ROUTE, AND THE MEASUREMENT THAT DECIDED IT
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Seal 1301 requires that a submitted video is reviewed before anything is
 * published. Seal 1295's consent record only means something if the video it
 * attaches to is not already public. So the build brief asked for a private
 * uploads directory with a deny rule, OR a link route.
 *
 * ⛔⛔ THE DENY RULE DOES NOT WORK ON THIS HOST, AND THAT IS MEASURED, NOT
 *     ASSUMED. On staging2 a directory was created under `wp-content/uploads/`
 *     carrying a well-formed `Require all denied`, then a well-formed
 *     `<FilesMatch "\.(?i:mp4|mov|m4v|webm|txt)$">` deny, and both files were
 *     then requested over HTTPS from outside:
 *
 *         probe.mp4  ->  HTTP 200, sentinel body returned
 *         probe.txt  ->  HTTP 200, sentinel body returned
 *         probe.php  ->  HTTP 403
 *
 * ⭐ THE PHP 403 IS THE PROOF THAT THE FILE WAS BEING READ AT ALL. PHP is
 *    handled by Apache, which honours `.htaccess`. Static files are served by
 *    the front-end web server, which never consults it. ⛔ So EVERY byte
 *    written anywhere under the document root on this host is reachable by
 *    anyone who has or guesses the URL, an unguessable filename included —
 *    an unguessable URL is an obscurity, not an access control, and it travels
 *    in Referer headers, share sheets and browser history.
 *
 * ⭐ THEREFORE, AND THIS IS THE WHOLE REASON THE DESIGN LOOKS THE WAY IT DOES:
 *
 *    ROUTE A — LINK (PRIMARY). The submitter pastes a link to a video they
 *    already host: unlisted YouTube, Vimeo, Google Drive or Dropbox. The site
 *    stores a string. Nothing is uploaded, nothing is stored, no storage grows
 *    and there is no file on this server to leak. `bhp_testimonial_validate_video_url()`
 *    is the entire attack surface.
 *
 *    ROUTE B — DIRECT UPLOAD (SECONDARY). The bytes are written OUTSIDE the
 *    document root entirely, to `bhp_testimonial_private_dir()`, and can only
 *    be read back through an `admin_post` endpoint that demands
 *    `manage_options` and a nonce. ⛔ THE MEDIA LIBRARY IS NEVER USED AND MUST
 *    NEVER BE. `wp_handle_upload()` writes into `wp-content/uploads`, which is
 *    exactly the directory the measurement above disqualified.
 *
 * ⚠ WHAT IS NOT VERIFIED ABOUT ROUTE B AT THE TIME OF WRITING: the web SAPI's
 *   `upload_max_filesize`. The CLI SAPI reports 256M and the front-end proxy
 *   accepted a 100 MB POST body with HTTP 200 (measured), but the CLI ini is
 *   not proof of the web ini. `bhp_testimonial_upload_cap_bytes()` therefore
 *   floors itself against `wp_max_upload_size()` at request time rather than
 *   trusting a constant, and the review screen prints the number it actually
 *   resolved to.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔⛔ NO CHILD'S NAME. ANYWHERE. EVER.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Seal 1301 and seal 1275. ⭐ THE ENFORCEMENT IS THAT THERE IS NO FIELD. A
 * validator that strips a child's name is a field that collected one; the form
 * has no input for it, the post type has no meta key for it, and the review
 * screen says so on screen so nobody adds one back into the title.
 *
 * ⛔ The post title is generated from the submitter's own surname and a
 *    date, never from anything about the child.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐ PASSING IS INDEPENDENT OF SENTIMENT, BY DESIGN
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Every checklist item below is an objective, observable property of the
 * recording. ⛔ NOT ONE OF THEM ASKS WHETHER THE SPEAKER LIKED THE BOOK. That
 * is a legal requirement, not a preference: an incentive conditioned on a
 * positive opinion is a deceptive endorsement. `bhp_testimonial_sentiment_notice()`
 * carries the sentence, and the review screen prints it where the scorer reads
 * it, not in a document nobody opens.
 *
 * ⚠ The `CYCLE179-ADS-TESTIMONIAL-DOCTRINE` workstream is the authority on the
 *   release wording and may tighten this. Nothing here contradicts it; the
 *   terms page carries a placeholder slot for that text.
 */

defined( 'ABSPATH' ) || exit;

/**
 * The private post type that holds every submission.
 */
const BHP_TESTIMONIAL_CPT = 'bhp_testimonial';

/**
 * The version string stamped into every consent record.
 *
 * ⛔ PLACEHOLDER. It changes to a real date the day the release text is
 *    approved and pasted into the terms page. A consent record that cannot say
 *    WHICH text was agreed to is not evidence of anything, which is why this is
 *    recorded per submission rather than read live at review time.
 */
const BHP_TESTIMONIAL_TERMS_VERSION = 'PLACEHOLDER-2026-09-07';

/**
 * The `admin_post` action the submission form posts to.
 */
const BHP_TESTIMONIAL_ACTION = 'bhp_testimonial_submit';

/**
 * The `admin_post` action that streams one private video back to an admin.
 */
const BHP_TESTIMONIAL_STREAM_ACTION = 'bhp_testimonial_stream';

/**
 * Page slugs. Both pages are created by hand on each environment.
 */
const BHP_TESTIMONIAL_PAGE_SLUG  = 'share-your-reader';
const BHP_TESTIMONIAL_TERMS_SLUG = 'video-testimonial-terms';

/*
 * ---------------------------------------------------------------------------
 * THE POST TYPE
 * ---------------------------------------------------------------------------
 */

/**
 * Register the private submission post type.
 *
 * ⛔⛔ EVERY VISIBILITY FLAG BELOW IS SET DELIBERATELY AND THE SUITE ASSERTS
 *     EACH ONE. This post type holds a member of the public's name, email
 *     address, a signed release and a link to a video of their family. A
 *     default is not a decision.
 *
 *   `public              => false`  the master switch; everything else follows
 *                                   it, and is ALSO stated so a future edit to
 *                                   one flag cannot quietly open the rest.
 *   `publicly_queryable  => false`  no front-end URL resolves to one
 *   `exclude_from_search => true`   never in site search results
 *   `has_archive         => false`  no archive page exists to be crawled
 *   `rewrite             => false`  no permalink structure is generated
 *   `query_var           => false`  `?bhp_testimonial=` resolves to nothing
 *   `show_in_rest        => false`  ⛔ THE ONE THAT MATTERS MOST. A REST-exposed
 *                                   post type is readable over HTTP by anyone
 *                                   the endpoint's own permission check lets
 *                                   through, and it is the flag people forget
 *                                   because the block editor asks for it.
 *   `show_in_nav_menus   => false`  cannot be added to a menu by accident
 *   `show_ui             => true`   ⭐ TRUE ON PURPOSE. Andrew must be able to
 *                                   see the queue. Admin visibility is not
 *                                   public visibility.
 *
 * ⭐ `supports` is `title` ONLY. No editor, no excerpt, no thumbnail, no
 *    comments, no revisions. Everything real lives in meta, written by code
 *    that validated it. An open `editor` on this post type would be a place
 *    for a child's name to be typed.
 *
 * @return void
 */
function bhp_testimonial_register_post_type() {
	register_post_type(
		BHP_TESTIMONIAL_CPT,
		array(
			'labels'              => array(
				'name'          => __( 'Video Testimonials', 'brave-hearts' ),
				'singular_name' => __( 'Video Testimonial', 'brave-hearts' ),
				'menu_name'     => __( 'Video Testimonials', 'brave-hearts' ),
				'all_items'     => __( 'Review Queue', 'brave-hearts' ),
				'edit_item'     => __( 'Review Submission', 'brave-hearts' ),
				'search_items'  => __( 'Search Submissions', 'brave-hearts' ),
				'not_found'     => __( 'No submissions yet.', 'brave-hearts' ),
			),
			'public'              => false,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_nav_menus'   => false,
			'show_in_admin_bar'   => false,
			'show_in_rest'        => false,
			'has_archive'         => false,
			'rewrite'             => false,
			'query_var'           => false,
			'can_export'          => false,
			'delete_with_user'    => false,
			'menu_icon'           => 'dashicons-format-video',
			'menu_position'       => 26,
			'capability_type'     => 'post',
			'map_meta_cap'        => true,
			'supports'            => array( 'title' ),
		)
	);
}
add_action( 'init', 'bhp_testimonial_register_post_type' );

/**
 * Keep the post type out of the Rank Math sitemap, belt and braces.
 *
 * ⚠ A post type registered `public => false` is ALREADY excluded by Rank Math,
 *   and this filter is therefore redundant TODAY. It is here because the cost
 *   of the redundancy is one function and the cost of the omission, the day
 *   somebody flips `public` to debug something and forgets, is a search engine
 *   indexing a page of submitters' names. ⭐ Redundant guards on privacy are
 *   worth their weight; redundant guards on layout are not.
 *
 * @param bool   $exclude   Rank Math's answer.
 * @param string $post_type The post type being considered.
 * @return bool
 */
function bhp_testimonial_sitemap_exclude( $exclude, $post_type = '' ) {
	if ( BHP_TESTIMONIAL_CPT === $post_type ) {
		return true;
	}
	return $exclude;
}
add_filter( 'rank_math/sitemap/exclude_post_type', 'bhp_testimonial_sitemap_exclude', 10, 2 );

/**
 * Never let a submission reach a front-end query, whatever asks.
 *
 * ⛔ `publicly_queryable => false` already stops a direct URL. This stops the
 *    OTHER way one leaks: a theme or plugin running `WP_Query` with
 *    `post_type => 'any'` on the front end. `'any'` honours
 *    `exclude_from_search`, so this is again redundant today and again cheap.
 *
 * @param WP_Query $q The query.
 * @return void
 */
function bhp_testimonial_never_on_front_end( $q ) {
	if ( is_admin() || ! $q instanceof WP_Query ) {
		return;
	}
	$types = $q->get( 'post_type' );
	if ( empty( $types ) || 'any' === $types ) {
		return;
	}
	$types = (array) $types;
	if ( in_array( BHP_TESTIMONIAL_CPT, $types, true ) ) {
		$q->set( 'post_type', array_values( array_diff( $types, array( BHP_TESTIMONIAL_CPT ) ) ) );
	}
}
add_action( 'pre_get_posts', 'bhp_testimonial_never_on_front_end' );

/*
 * ---------------------------------------------------------------------------
 * THE STATUS FLOW
 * ---------------------------------------------------------------------------
 */

/**
 * The meta key holding a submission's status.
 */
const BHP_TESTIMONIAL_STATUS_META = '_bhp_testimonial_status';

/**
 * Every status a submission can hold, in order.
 *
 * ⭐ `published` means "Andrew has chosen to use this somewhere", which is an
 *    act OUTSIDE this system. It is recorded here so the log seal 1301 asks
 *    for can answer "published or not" for every submission. ⛔ NOTHING IN
 *    THIS THEME PUBLISHES A SUBMISSION. Setting this status displays no video
 *    anywhere.
 *
 * @return array<string,string> status => human label
 */
function bhp_testimonial_statuses() {
	return array(
		'submitted' => __( 'Submitted, not yet reviewed', 'brave-hearts' ),
		'passed'    => __( 'Passed the checklist', 'brave-hearts' ),
		'failed'    => __( 'Did not meet the checklist', 'brave-hearts' ),
		'published' => __( 'Used publicly (recorded here only)', 'brave-hearts' ),
	);
}

/**
 * Is this a status this system recognises?
 *
 * @param string $status Candidate.
 * @return bool
 */
function bhp_testimonial_status_is_valid( $status ) {
	return array_key_exists( (string) $status, bhp_testimonial_statuses() );
}

/**
 * May a submission move from one status to another?
 *
 * ⭐ THE ONLY MOVE THAT IS FORBIDDEN IS THE ONE THAT WOULD BE A LIE. `failed`
 *    cannot become `published`, because publishing a submission that was told
 *    it did not qualify is the single outcome the fail note promises will not
 *    happen. Everything else stays open: a fail can be reconsidered to a pass
 *    (seal 1301 gives the submitter a second try), and a pass can be walked
 *    back if the scorer got it wrong.
 *
 * ⛔ A no-op move (`$from === $to`) is allowed. It is what an admin save does
 *    when nothing changed, and refusing it would fail every ordinary save.
 *
 * @param string $from Current status.
 * @param string $to   Requested status.
 * @return bool
 */
function bhp_testimonial_status_can_move( $from, $to ) {
	if ( ! bhp_testimonial_status_is_valid( $from ) || ! bhp_testimonial_status_is_valid( $to ) ) {
		return false;
	}
	if ( $from === $to ) {
		return true;
	}
	if ( 'failed' === $from && 'published' === $to ) {
		return false;
	}
	if ( 'submitted' === $to && 'submitted' !== $from ) {
		return false; // A reviewed submission does not become unreviewed.
	}
	return true;
}

/**
 * Read one submission's status.
 *
 * @param int $post_id Submission id.
 * @return string One of bhp_testimonial_statuses(); 'submitted' when unset.
 */
function bhp_testimonial_get_status( $post_id ) {
	$status = (string) get_post_meta( (int) $post_id, BHP_TESTIMONIAL_STATUS_META, true );
	return bhp_testimonial_status_is_valid( $status ) ? $status : 'submitted';
}

/*
 * ---------------------------------------------------------------------------
 * THE CHECKLIST
 * ---------------------------------------------------------------------------
 */

/**
 * The scorecard, seal 1301, verbatim in substance.
 *
 * ⛔ EVERY ITEM IS AN OBSERVABLE PROPERTY OF THE RECORDING. Read the list and
 *    check: not one asks what the speaker thought of the book. See
 *    `bhp_testimonial_sentiment_notice()`.
 *
 * @return array<string,string> key => the question the scorer answers
 */
function bhp_testimonial_checklist() {
	return array(
		'length'      => __( 'Runs between 30 and 90 seconds', 'brave-hearts' ),
		'upright'     => __( 'Filmed upright, not sideways', 'brave-hearts' ),
		'adult_voice' => __( 'A parent or grandparent is the one speaking', 'brave-hearts' ),
		'book_shown'  => __( 'The book is visible on camera', 'brave-hearts' ),
		'audible'     => __( 'Speech is audible, with no music over it', 'brave-hearts' ),
		'no_brands'   => __( 'No other brand or logo is on camera', 'brave-hearts' ),
		'own_words'   => __( 'Spoken in their own words, not read from a script', 'brave-hearts' ),
	);
}

/**
 * The automatic fails, seal 1301.
 *
 * ⭐ SEPARATE FROM THE CHECKLIST ABOVE BECAUSE THEY BEHAVE DIFFERENTLY. A
 *    checklist item that is not met can be fixed on a second try. An automatic
 *    fail ticked here ends the submission whatever else is ticked, and
 *    `bhp_testimonial_score()` enforces that rather than leaving it to the
 *    scorer's arithmetic.
 *
 * @return array<string,string> key => the condition that fails it outright
 */
function bhp_testimonial_auto_fails() {
	return array(
		'under_30'      => __( 'Shorter than 30 seconds', 'brave-hearts' ),
		'no_book'       => __( 'The book never appears', 'brave-hearts' ),
		'child_alone'   => __( 'A child on camera with no adult', 'brave-hearts' ),
		'read_script'   => __( 'Read from a script', 'brave-hearts' ),
		'profanity'     => __( 'Contains profanity', 'brave-hearts' ),
		'not_own_child' => __( 'The child is not the submitter\'s own', 'brave-hearts' ),
		'ai_generated'  => __( 'Generated rather than filmed', 'brave-hearts' ),
	);
}

/**
 * The sentence that goes on the review screen, not in a document.
 *
 * ⛔ IT IS A FUNCTION SO THE SUITE CAN ASSERT IT IS ACTUALLY PRINTED. A rule
 *    that lives only in a comment is a rule that gets scored past.
 *
 * @return string
 */
function bhp_testimonial_sentiment_notice() {
	return __(
		'Score only what the recording shows. Whether the speaker liked the book has no bearing on whether this passes. The reward is earned by meeting the checklist, and conditioning it on a favorable opinion is not allowed.',
		'brave-hearts'
	);
}

/**
 * Turn a filled scorecard into a verdict. PURE.
 *
 * ⭐ SEPARATED FROM EVERY SCREEN AND EVERY SAVE SO THE SUITE CAN ASSERT THE
 *    RULE WITHOUT AN ADMIN SESSION, A POST OR AN EMAIL. This is the same
 *    reason `bhp_readaloud_request_compose()` is pure.
 *
 * ⛔ AN AUTOMATIC FAIL OUTRANKS A FULLY TICKED CHECKLIST. Read the order of
 *    the two blocks below: the auto-fail scan runs FIRST and returns, so a
 *    scorer who ticks every checklist box and also ticks "generated rather
 *    than filmed" gets `failed`, not `passed`.
 *
 * @param array<string,bool> $checked   Checklist keys the scorer ticked.
 * @param array<string,bool> $autofails Auto-fail keys the scorer ticked.
 * @return array{verdict:string,missing:string[],triggered:string[]}
 */
function bhp_testimonial_score( $checked, $autofails = array() ) {
	$checked   = is_array( $checked ) ? $checked : array();
	$autofails = is_array( $autofails ) ? $autofails : array();

	$triggered = array();
	foreach ( array_keys( bhp_testimonial_auto_fails() ) as $key ) {
		if ( ! empty( $autofails[ $key ] ) ) {
			$triggered[] = $key;
		}
	}
	if ( $triggered ) {
		return array(
			'verdict'   => 'failed',
			'missing'   => array(),
			'triggered' => $triggered,
		);
	}

	$missing = array();
	foreach ( array_keys( bhp_testimonial_checklist() ) as $key ) {
		if ( empty( $checked[ $key ] ) ) {
			$missing[] = $key;
		}
	}

	return array(
		'verdict'   => $missing ? 'failed' : 'passed',
		'missing'   => $missing,
		'triggered' => array(),
	);
}

/*
 * ---------------------------------------------------------------------------
 * THE CONSENT RECORD — seal 1295
 * ---------------------------------------------------------------------------
 */

/**
 * Every meta key that makes up the clickwrap evidence.
 *
 * ⭐ THE RECORD IS THE POINT, NOT THE CHECKBOX. A checkbox with no stored
 *    evidence of WHO ticked it, WHEN, against WHICH text and FROM WHERE proves
 *    nothing later. Seal 1295 names all five; this is the list the suite holds
 *    the save path to.
 *
 * @return array<string,string> meta key => what it holds
 */
function bhp_testimonial_consent_fields() {
	return array(
		'_bhp_testimonial_consent_name'     => 'The full name typed as a signature',
		'_bhp_testimonial_consent_checked'  => 'The required review checkbox, stored as "1"',
		'_bhp_testimonial_consent_time'     => 'UTC timestamp of acceptance, ISO 8601',
		'_bhp_testimonial_consent_terms'    => 'The terms version accepted',
		'_bhp_testimonial_consent_ip'       => 'The submitting IP address',
		'_bhp_testimonial_guardian'         => 'The guardian affirmation, stored as "1" or ""',
		'_bhp_testimonial_child_appears'    => 'Whether a child appears, "1" or ""',
	);
}

/**
 * Build one consent record from already-validated inputs. PURE.
 *
 * ⛔ IT DOES NOT VALIDATE AND IT DOES NOT SAVE. It is called by the handler
 *    after validation so the exact shape that gets stored can be asserted
 *    without a POST.
 *
 * @param string $name          Typed signature.
 * @param bool   $checked       Terms checkbox state.
 * @param bool   $child_appears Whether a child appears in the video.
 * @param bool   $guardian      Guardian affirmation state.
 * @param string $ip            Submitting IP.
 * @param string $now           ISO 8601 UTC timestamp; defaults to now.
 * @return array<string,string>
 */
function bhp_testimonial_build_consent( $name, $checked, $child_appears, $guardian, $ip, $now = '' ) {
	return array(
		'_bhp_testimonial_consent_name'    => (string) $name,
		'_bhp_testimonial_consent_checked' => $checked ? '1' : '',
		'_bhp_testimonial_consent_time'    => $now ? (string) $now : gmdate( 'c' ),
		'_bhp_testimonial_consent_terms'   => BHP_TESTIMONIAL_TERMS_VERSION,
		'_bhp_testimonial_consent_ip'      => (string) $ip,
		'_bhp_testimonial_guardian'        => $guardian ? '1' : '',
		'_bhp_testimonial_child_appears'   => $child_appears ? '1' : '',
	);
}

/**
 * The submitting IP, as far as it can honestly be known.
 *
 * ⚠ THIS SITE SITS BEHIND A PROXY, so `REMOTE_ADDR` is the proxy and
 *   `X-Forwarded-For` is the chain. The LEFTMOST entry is the client as
 *   reported, and it is client-supplied and therefore spoofable. ⭐ IT IS
 *   RECORDED AS EVIDENCE OF A SUBMISSION, NOT RELIED ON AS AN IDENTITY, and
 *   nothing in this feature makes a decision from it.
 *
 * @return string
 */
function bhp_testimonial_client_ip() {
	$candidate = '';
	if ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
		$chain     = explode( ',', (string) wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
		$candidate = trim( (string) reset( $chain ) );
	} elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
		$candidate = trim( (string) wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
	}
	$valid = filter_var( $candidate, FILTER_VALIDATE_IP );
	return $valid ? $valid : '';
}

/*
 * ---------------------------------------------------------------------------
 * ROUTE A — THE LINK, AND ITS VALIDATOR
 * ---------------------------------------------------------------------------
 */

/**
 * The video hosts a link may point at.
 *
 * ⭐ AN ALLOW LIST, NOT A DENY LIST, and it is matched on the REGISTRABLE HOST
 *    with a leading-dot subdomain rule. ⛔ A `strpos( $url, 'youtube.com' )`
 *    test — the shape this is most often written in — passes
 *    `https://youtube.com.evil.example/x` and `https://evil.example/?youtube.com`.
 *    Both are rejected here because the host is parsed out first and then
 *    compared whole.
 *
 * @return string[]
 */
function bhp_testimonial_allowed_video_hosts() {
	return (array) apply_filters(
		'bhp_testimonial_allowed_video_hosts',
		array(
			'youtube.com',
			'youtu.be',
			'vimeo.com',
			'drive.google.com',
			'dropbox.com',
			'icloud.com',
		)
	);
}

/**
 * Validate a submitted video link. PURE.
 *
 * ⛔ EVERY REJECTION REASON IS RETURNED RATHER THAN A BARE FALSE, because the
 *    form has to tell a parent what to fix and "invalid link" helps nobody.
 *
 * @param string $url Raw submitted string.
 * @return array{ok:bool,url:string,reason:string}
 */
function bhp_testimonial_validate_video_url( $url ) {
	$fail = function ( $reason ) {
		return array(
			'ok'     => false,
			'url'    => '',
			'reason' => $reason,
		);
	};

	$url = trim( (string) $url );
	if ( '' === $url ) {
		return $fail( 'empty' );
	}
	if ( strlen( $url ) > 500 ) {
		return $fail( 'too_long' );
	}

	$parts = wp_parse_url( $url );
	if ( ! is_array( $parts ) || empty( $parts['host'] ) || empty( $parts['scheme'] ) ) {
		return $fail( 'unparseable' );
	}

	/*
	 * ⛔ HTTPS ONLY. An http link to a family video is a link that can be read
	 *    off the wire, and every host on the allow list serves https.
	 */
	if ( 'https' !== strtolower( $parts['scheme'] ) ) {
		return $fail( 'not_https' );
	}

	if ( ! empty( $parts['user'] ) || ! empty( $parts['pass'] ) ) {
		return $fail( 'credentials_in_url' ); // https://evil@youtube.com/ style.
	}

	$host = strtolower( $parts['host'] );
	$host = preg_replace( '/^www\./', '', $host );

	$allowed = false;
	foreach ( bhp_testimonial_allowed_video_hosts() as $ok_host ) {
		$ok_host = strtolower( $ok_host );
		if ( $host === $ok_host || substr( $host, - ( strlen( $ok_host ) + 1 ) ) === '.' . $ok_host ) {
			$allowed = true;
			break;
		}
	}
	if ( ! $allowed ) {
		return $fail( 'host_not_allowed' );
	}

	$clean = esc_url_raw( $url );
	if ( '' === $clean ) {
		return $fail( 'unsafe' );
	}

	return array(
		'ok'     => true,
		'url'    => $clean,
		'reason' => '',
	);
}

/*
 * ---------------------------------------------------------------------------
 * ROUTE B — THE PRIVATE STORE, OUTSIDE THE DOCUMENT ROOT
 * ---------------------------------------------------------------------------
 */

/**
 * Where uploaded videos are written.
 *
 * ⛔⛔ THIS PATH MUST NEVER BE INSIDE THE DOCUMENT ROOT, AND
 *     `bhp_testimonial_private_dir_is_safe()` is asserted by the suite on every
 *     run for exactly that reason. See this file's header for the measurement.
 *
 * ⭐ THE DEFAULT IS A SIBLING OF THE WORDPRESS ROOT, NOT A CHILD OF IT.
 *    `ABSPATH` is the WordPress directory; `dirname( ABSPATH )` is one level
 *    above it, which on this host is the account directory that the web server
 *    does not serve from. It is filterable so a different host can point it
 *    somewhere else without editing code.
 *
 * @return string Absolute path, trailing slash.
 */
function bhp_testimonial_private_dir() {
	$default = trailingslashit( dirname( untrailingslashit( ABSPATH ) ) ) . 'bhp-private-testimonials/';
	return trailingslashit( (string) apply_filters( 'bhp_testimonial_private_dir', $default ) );
}

/**
 * Is the configured private directory actually outside the served tree?
 *
 * ⛔ IT IS CHECKED AT RUNTIME, NOT ONLY IN A TEST. If a filter or a migration
 *    ever points this inside the document root, `bhp_testimonial_store_upload()`
 *    refuses to write rather than writing a family's video somewhere the
 *    measurement in this file's header proved is public.
 *
 * @param string $dir Directory to check; defaults to the configured one.
 * @return bool
 */
function bhp_testimonial_private_dir_is_safe( $dir = '' ) {
	$dir = $dir ? trailingslashit( $dir ) : bhp_testimonial_private_dir();

	$norm = static function ( $p ) {
		return trailingslashit( str_replace( '\\', '/', (string) $p ) );
	};
	$dir = $norm( $dir );

	$roots = array( $norm( ABSPATH ) );
	if ( defined( 'WP_CONTENT_DIR' ) ) {
		$roots[] = $norm( WP_CONTENT_DIR );
	}
	$uploads = wp_get_upload_dir();
	if ( ! empty( $uploads['basedir'] ) ) {
		$roots[] = $norm( $uploads['basedir'] );
	}

	foreach ( $roots as $root ) {
		if ( 0 === strpos( $dir, $root ) ) {
			return false;
		}
	}
	return true;
}

/**
 * The extensions Route B accepts.
 *
 * @return string[]
 */
function bhp_testimonial_allowed_upload_extensions() {
	return array( 'mp4', 'mov', 'm4v', 'webm' );
}

/**
 * The largest upload Route B will take, in bytes.
 *
 * ⚠ IT IS A FLOOR AGAINST THE SERVER, NOT A CONSTANT. `wp_max_upload_size()`
 *   resolves the web SAPI's own `upload_max_filesize` and `post_max_size` at
 *   request time. Taking the SMALLER of the two means this can only ever be a
 *   number the server will actually accept — the failure mode a hardcoded cap
 *   produces is a form that promises 100 MB and then dies silently at 64.
 *
 * @return int
 */
function bhp_testimonial_upload_cap_bytes() {
	$intended = 100 * 1024 * 1024;
	$server   = function_exists( 'wp_max_upload_size' ) ? (int) wp_max_upload_size() : 0;
	if ( $server > 0 && $server < $intended ) {
		return $server;
	}
	return $intended;
}

/**
 * Write one uploaded video into the private store.
 *
 * ⛔ `wp_handle_upload()` IS DELIBERATELY NOT USED. It writes into
 *    `wp-content/uploads`, and this file's header records the measurement that
 *    disqualifies that directory. `move_uploaded_file()` is used directly, and
 *    the PHP upload-error code is read before anything else so a truncated
 *    upload is reported rather than stored.
 *
 * @param array $file One entry from $_FILES.
 * @return array{ok:bool,name:string,bytes:int,reason:string}
 */
function bhp_testimonial_store_upload( $file ) {
	$fail = function ( $reason ) {
		return array(
			'ok'     => false,
			'name'   => '',
			'bytes'  => 0,
			'reason' => $reason,
		);
	};

	if ( ! is_array( $file ) || ! isset( $file['error'] ) ) {
		return $fail( 'no_file' );
	}
	if ( UPLOAD_ERR_NO_FILE === (int) $file['error'] ) {
		return $fail( 'no_file' );
	}
	if ( UPLOAD_ERR_INI_SIZE === (int) $file['error'] || UPLOAD_ERR_FORM_SIZE === (int) $file['error'] ) {
		return $fail( 'too_large' );
	}
	if ( UPLOAD_ERR_OK !== (int) $file['error'] ) {
		return $fail( 'upload_error' );
	}

	$tmp = isset( $file['tmp_name'] ) ? (string) $file['tmp_name'] : '';
	if ( '' === $tmp || ! is_uploaded_file( $tmp ) ) {
		return $fail( 'not_an_upload' );
	}

	$bytes = (int) filesize( $tmp );
	if ( $bytes <= 0 ) {
		return $fail( 'empty' );
	}
	if ( $bytes > bhp_testimonial_upload_cap_bytes() ) {
		return $fail( 'too_large' );
	}

	$ext = strtolower( pathinfo( (string) ( isset( $file['name'] ) ? $file['name'] : '' ), PATHINFO_EXTENSION ) );
	if ( ! in_array( $ext, bhp_testimonial_allowed_upload_extensions(), true ) ) {
		return $fail( 'bad_extension' );
	}

	/*
	 * ⛔ THE FILE TYPE IS CHECKED AGAINST THE BYTES, NOT THE FILENAME. A
	 *    submitter can name anything `.mp4`. This is defence in depth rather
	 *    than the whole defence, since the store is outside the document root
	 *    and nothing there is ever executed.
	 */
	$type = wp_check_filetype_and_ext( $tmp, 'video.' . $ext );
	if ( empty( $type['type'] ) || 0 !== strpos( (string) $type['type'], 'video/' ) ) {
		return $fail( 'not_a_video' );
	}

	if ( ! bhp_testimonial_private_dir_is_safe() ) {
		return $fail( 'unsafe_store' ); // ⛔ Refuse rather than write it somewhere public.
	}

	$dir = bhp_testimonial_private_dir();
	if ( ! wp_mkdir_p( $dir ) ) {
		return $fail( 'store_unavailable' );
	}

	$name = wp_generate_password( 32, false, false ) . '.' . $ext;
	if ( ! @move_uploaded_file( $tmp, $dir . $name ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors
		return $fail( 'store_write_failed' );
	}
	@chmod( $dir . $name, 0640 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors

	return array(
		'ok'     => true,
		'name'   => $name,
		'bytes'  => $bytes,
		'reason' => '',
	);
}

/**
 * Resolve a stored filename to its absolute path, refusing traversal.
 *
 * ⛔ THE FILENAME IS REGENERATED FROM ITS OWN BASENAME AND COMPARED. Anything
 *    carrying a slash, a `..` or a null byte fails that comparison, so no meta
 *    value — however it got written — can address a file outside the store.
 *
 * @param string $name Stored filename.
 * @return string Absolute path, or '' when it does not resolve safely.
 */
function bhp_testimonial_private_path( $name ) {
	$name = (string) $name;
	if ( '' === $name || $name !== basename( $name ) || false !== strpos( $name, "\0" ) ) {
		return '';
	}
	if ( ! preg_match( '/^[A-Za-z0-9]{8,64}\.[a-z0-9]{2,5}$/', $name ) ) {
		return '';
	}
	$path = bhp_testimonial_private_dir() . $name;
	return file_exists( $path ) ? $path : '';
}
