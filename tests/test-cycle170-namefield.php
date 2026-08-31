<?php
/**
 * CYCLE170-LD-NAMEFIELD — the optional first-name field on /positivity-news/.
 * Theme 1.19.340 (2026-08-31). STAGING ONLY. `wp eval-file` from the site root.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔ WHAT THIS SUITE IS FOR — four quiet failure modes, one per section
 * ═══════════════════════════════════════════════════════════════════════════
 *
 *   1 · THE FIELD RENDERS BUT IS SECRETLY REQUIRED. `show_name` and
 *       `require_name` are one boolean apart in `signup-form.php`, and
 *       `require_name` implies `show_name`. A page that passed the wrong one
 *       LOOKS IDENTICAL in a screenshot: same label, same box, same position.
 *       The difference only appears when a visitor leaves it blank and is
 *       bounced with `missing_name`. §2 asserts the negative in the RENDERED
 *       markup — no `required`, no `aria-required`, no `bhp_require_name`
 *       hidden field — because that is the only place the difference exists.
 *
 *   2 · THE NAME RENDERS BUT NEVER ARRIVES. The input could carry a `name`
 *       attribute the handler does not read, and the form would submit
 *       happily, redirect to the thank-you state, and drop the name silently.
 *       §3 drives the real `bhp_process_signup()` and reads `FNAME` back out
 *       of the transport payload. ⛔ Not out of the template, not out of a
 *       comment: out of the recorded call the API client would have made.
 *
 *   3 · THE EMPTY NAME BREAKS THE SIGNUP. §4 submits with `name => ''` and
 *       asserts the subscription still SUCCEEDS and that `FNAME` is ABSENT
 *       rather than present-and-empty. An empty merge field is not the same
 *       object as no merge field, and Mailchimp treats them differently.
 *
 *   4 · THE TAGS MOVED. Adding a field to a form is exactly the kind of edit
 *       that quietly re-routes it. §5 asserts the two tags are still the two
 *       tags, spelled the same way, and that `lead_magnet` is still empty.
 *
 * ⛔ NOTHING HERE SENDS MAIL, and nothing reaches Mailchimp. On staging the
 *    transport is `BHP_Mailchimp_Staging_Stub`, which records the payload and
 *    returns; there is no HTTP call in it. The suite refuses to run at all if
 *    the stub is not the active transport (§0), so it can never transmit.
 *
 * ⛔ NOTHING HERE WRITES a theme file, an option or a post. It clears and
 *    reads one transient the stub already owns.
 *
 * ⚠️ ONE DISCLOSED SIDE EFFECT: §3 and §4 each run a real signup, so each
 *   appends one row to the staging `bhp_lead_event` log, exactly as
 *   `test-capture-pipe-endtoend.php` already does. Staging only.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function bhp_nf_assert( $cond, $msg, $detail = '' ) {
	if ( ! isset( $GLOBALS['bhp_nf_pass'] ) ) {
		$GLOBALS['bhp_nf_pass'] = 0;
		$GLOBALS['bhp_nf_fail'] = 0;
	}
	if ( $cond ) {
		++$GLOBALS['bhp_nf_pass'];
		echo "  PASS  {$msg}\n";
	} else {
		++$GLOBALS['bhp_nf_fail'];
		echo "  FAIL  {$msg}" . ( '' !== $detail ? "  [{$detail}]" : '' ) . "\n";
	}
}

echo "\n=== CYCLE170-LD-NAMEFIELD · theme 1.19.340 ===\n";

/* ═══════════════════════════════════════════════════════════════════════════
 * 0 · PRECONDITIONS — the version pin, and the refusal to run off the stub
 * ═══════════════════════════════════════════════════════════════════════════ */

echo "\n=== 0 · PRECONDITIONS ===\n";

$bhp_nf_css = (string) file_get_contents( get_template_directory() . '/style.css' );
preg_match( '/^Version:\s*(\S+)/m', $bhp_nf_css, $bhp_nf_vm );
$bhp_nf_ver = isset( $bhp_nf_vm[1] ) ? $bhp_nf_vm[1] : '';
bhp_nf_assert( '1.19.341' === $bhp_nf_ver, "style.css declares 1.19.341, got '{$bhp_nf_ver}'" );

/*
 * ⛔ THE HARD STOP. If the stub is not the transport, a signup in §3/§4 would
 *    attempt the REAL client against Andrew's thirteen-person audience. The
 *    suite aborts rather than degrading to a source-only check, because a
 *    source-only check that reports PASS here would be the fabricated-check
 *    failure class.
 */
if ( ! class_exists( 'BHP_Mailchimp_Staging_Stub' ) || ! BHP_Mailchimp_Staging_Stub::is_staging_install() ) {
	echo "  ABORT  not a staging install, or the stub class is absent. Refusing to run a live signup.\n";
	echo "\n=== CYCLE170-LD-NAMEFIELD: ABORTED ===\n";
	return;
}
$bhp_nf_transport = apply_filters( 'bhp_mailchimp_api_transport', null );
if ( ! ( $bhp_nf_transport instanceof BHP_Mailchimp_Staging_Stub ) ) {
	echo "  ABORT  the active transport is NOT the recording stub. Refusing to transmit.\n";
	echo "\n=== CYCLE170-LD-NAMEFIELD: ABORTED ===\n";
	return;
}
bhp_nf_assert( true, '⛔ the recording stub is the active transport (nothing can transmit)' );

/* ═══════════════════════════════════════════════════════════════════════════
 * 1 · THE PAGE TEMPLATE'S ARGUMENTS
 * ═══════════════════════════════════════════════════════════════════════════ */

echo "\n=== 1 · TEMPLATE ARGUMENTS ===\n";

$bhp_nf_tpl = (string) file_get_contents( get_template_directory() . '/page-positivity-news.php' );

bhp_nf_assert( false !== strpos( $bhp_nf_tpl, "'show_name'     => true," ), "page template passes 'show_name' => true" );
bhp_nf_assert( false !== strpos( $bhp_nf_tpl, "'require_name'  => false," ), "⛔ page template passes 'require_name' => false (optional means optional)" );
bhp_nf_assert( false !== strpos( $bhp_nf_tpl, "'First name (optional)'" ), 'the label is the founder-ordered wording' );
bhp_nf_assert( false !== strpos( $bhp_nf_tpl, "'lead_magnet'   => ''," ), '⛔ lead_magnet is STILL the empty string' );

/*
 * ⛔ THE COPY-DECK STRINGS ARE UNTOUCHED. This build added a FIELD LABEL, not
 *    body copy, and this assertion is what proves it rather than asserting it.
 */
$bhp_nf_copy = bhp_positivity_news_copy();
bhp_nf_assert( 'Positivity News by Brave Hearts Publishing' === $bhp_nf_copy['headline'], 'carrier item 489 headline is byte-untouched' );
bhp_nf_assert( 'An ounce of positivity in a dark place.' === $bhp_nf_copy['subhead'], 'carrier item 489 subhead is byte-untouched' );
bhp_nf_assert( 'Subscribe' === $bhp_nf_copy['submit'], 'carrier item 489 submit label is byte-untouched' );

/* ⛔ ZERO EM DASHES AND ZERO EN DASHES in anything this page shows a visitor,
   including the new label. Same mechanical assertion the bundle suite makes. */
$bhp_nf_label = 'First name (optional)';
bhp_nf_assert(
	false === strpos( $bhp_nf_label, "\xE2\x80\x94" ) && false === strpos( $bhp_nf_label, "\xE2\x80\x93" ),
	'the new label carries no em dash and no en dash'
);
/* ⛔ AND NO "we". §9.1 — this is customer-facing wording. */
bhp_nf_assert( ! preg_match( '/\b(we|us|our)\b/i', $bhp_nf_label ), '§9.1 the new label uses no company "we"' );

/* ═══════════════════════════════════════════════════════════════════════════
 * 2 · THE RENDERED MARKUP — fetched from the live page, not from the template
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⭐ `wp_remote_get()` AGAINST THE REAL URL, because the question is what a
 *    visitor's browser receives. A template read would prove the source says
 *    the right thing and prove nothing about the page.
 */

echo "\n=== 2 · RENDERED MARKUP ===\n";

$bhp_nf_res  = wp_remote_get( bhp_positivity_news_url(), array( 'timeout' => 45, 'sslverify' => false ) );
$bhp_nf_code = wp_remote_retrieve_response_code( $bhp_nf_res );
$bhp_nf_html = (string) wp_remote_retrieve_body( $bhp_nf_res );

bhp_nf_assert( 200 === (int) $bhp_nf_code, "/positivity-news/ returns 200, got {$bhp_nf_code}" );

/* Isolate the form so nothing elsewhere on the page can satisfy an assertion. */
$bhp_nf_form = '';
if ( preg_match( '#<form[^>]*id="bhp-positivity-signup".*?</form>#s', $bhp_nf_html, $bhp_nf_fm ) ) {
	$bhp_nf_form = $bhp_nf_fm[0];
}
bhp_nf_assert( '' !== $bhp_nf_form, 'the signup form is present in the rendered page' );

if ( '' !== $bhp_nf_form ) {
	/* The name input itself. */
	$bhp_nf_input = '';
	if ( preg_match( '#<input[^>]*id="bhp-positivity-signup-name"[^>]*>#s', $bhp_nf_form, $bhp_nf_im ) ) {
		$bhp_nf_input = $bhp_nf_im[0];
	}
	bhp_nf_assert( '' !== $bhp_nf_input, '⭐ the first-name input RENDERS' );
	bhp_nf_assert( false !== strpos( $bhp_nf_input, 'name="first_name"' ), '⭐ it posts as first_name (the name the handler already defaults to)' );
	bhp_nf_assert( false !== strpos( $bhp_nf_input, 'type="text"' ), 'it is a text input' );
	bhp_nf_assert( false !== strpos( $bhp_nf_input, 'autocomplete="given-name"' ), 'it carries autocomplete="given-name"' );

	/*
	 * ⛔⛔ THE THREE NEGATIVES. This is the section that distinguishes an
	 *     OPTIONAL field from a required one, and they cannot be seen in a
	 *     screenshot.
	 */
	bhp_nf_assert( false === strpos( $bhp_nf_input, 'required' ), '⛔ the input is NOT `required`' );
	bhp_nf_assert( false === strpos( $bhp_nf_input, 'aria-required' ), '⛔ the input carries NO `aria-required`' );
	bhp_nf_assert( false === strpos( $bhp_nf_form, 'name="bhp_require_name"' ), '⛔⛔ NO `bhp_require_name` hidden field — the pipe cannot reject a blank name' );

	/* The label, and its association with the input. */
	bhp_nf_assert( false !== strpos( $bhp_nf_form, '<label for="bhp-positivity-signup-name">First name (optional)</label>' ), '⭐ the label reads "First name (optional)" and is bound to the input' );

	/* ⛔ THE EMAIL FIELD IS UNCHANGED AND IS STILL THE REQUIRED ONE. */
	bhp_nf_assert( false !== strpos( $bhp_nf_form, 'id="bhp-positivity-signup-email"' ), 'the email input still renders' );
	if ( preg_match( '#<input[^>]*id="bhp-positivity-signup-email"[^>]*>#s', $bhp_nf_form, $bhp_nf_em ) ) {
		bhp_nf_assert( false !== strpos( $bhp_nf_em[0], 'required' ), '⛔ the EMAIL field is still required' );
	}

	/* ⛔ NAME BEFORE EMAIL in the DOM, which is also keyboard order — the brief
	   says "above the email field", and no CSS `order` is used anywhere here. */
	bhp_nf_assert(
		strpos( $bhp_nf_form, 'id="bhp-positivity-signup-name"' ) < strpos( $bhp_nf_form, 'id="bhp-positivity-signup-email"' ),
		'⭐ the name field precedes the email field in DOM (= keyboard) order'
	);

	/* ⭐ THE STACKED LAYOUT CLASS. Two fields need it; see the 1.19.191 note in
	   signup-form.php for the orphaned-field defect it prevents. */
	bhp_nf_assert( false !== strpos( $bhp_nf_form, 'acquisition-form--stacked' ), '⭐ the form carries acquisition-form--stacked (two fields)' );

	/* ⛔ ZERO PLACEHOLDERS. A placeholder is not a label and this page uses none. */
	bhp_nf_assert( ! preg_match( '#placeholder="[^"]+"#', $bhp_nf_form ), '⛔ ZERO non-empty placeholder attributes in the form' );

	/* ⛔ STILL NO LEAD MAGNET ON THE WIRE. */
	bhp_nf_assert( false !== strpos( $bhp_nf_form, 'name="lead_magnet" value=""' ), '⛔ lead_magnet posts as the EMPTY STRING' );
}

/*
 * ⛔ AND THE PAGE STILL PROMISES NOTHING IT DID NOT PROMISE BEFORE — asserted
 *    over the VISIBLE CARD, not over the whole document.
 *
 * ⚠️ THE FIRST DRAFT OF THIS ASSERTION SEARCHED THE ENTIRE `$bhp_nf_html` AND
 *   WAS RED, and the reason is recorded rather than papered over: the string
 *   "instant download" appears exactly once in the response, inside the
 *   INLINED JSON CONFIG of the sitewide early-cart-capture / activity-book
 *   upsell (`functions.php` ~line 4293). ⛔ That is site chrome the header and
 *   footer put on EVERY page; it is not visible copy on this one, it predates
 *   this build, and `functions.php` is NOT among the files this build touched.
 *
 * ⛔ SO THE ASSERTION WAS WRONG, NOT THE PAGE. It is narrowed to the
 *    `.bhp-positivity` card — the region this page actually authors — which is
 *    where a lead-magnet promise would have to appear to mislead anybody.
 *    ⛔ It is NARROWED, not deleted: "no lead magnet" is the one rule this
 *    page exists to keep, and dropping the check would give that rule no
 *    mechanical guard at all.
 */
$bhp_nf_card = '';
if ( preg_match( '#<div class="bhp-positivity">.*?<p class="bhp-positivity__sign-off">.*?</p>#s', $bhp_nf_html, $bhp_nf_cm ) ) {
	$bhp_nf_card = $bhp_nf_cm[0];
}
bhp_nf_assert( '' !== $bhp_nf_card, 'the positivity card was isolated from the page chrome' );
if ( '' !== $bhp_nf_card ) {
	bhp_nf_assert( false === stripos( $bhp_nf_card, 'download' ), '⛔ the word "download" appears nowhere in the visible card' );
	bhp_nf_assert( false === stripos( $bhp_nf_card, '.pdf' ), '⛔ no PDF is linked from the visible card' );
	bhp_nf_assert( false === stripos( $bhp_nf_card, 'free ' ), '⛔ nothing in the card is offered as "free"' );
}

/* ═══════════════════════════════════════════════════════════════════════════
 * 3 · A NAME GIVEN — it must reach FNAME
 * ═══════════════════════════════════════════════════════════════════════════ */

echo "\n=== 3 · NAME GIVEN -> FNAME ===\n";

BHP_Mailchimp_Staging_Stub::clear();

$bhp_nf_email1 = 'namefield+bhptest' . time() . '@bhptest.invalid';
$bhp_nf_name1  = 'Charlotte';

$bhp_nf_r1 = bhp_process_signup(
	array(
		'email'         => $bhp_nf_email1,
		'name'          => $bhp_nf_name1,
		/* ⛔ FALSE, exactly as the page passes it. */
		'require_name'  => false,
		'context'       => bhp_positivity_news_context(),
		'audience_type' => 'general_readers',
		'lead_magnet'   => '',
		'source_page'   => bhp_positivity_news_url(),
	)
);

bhp_nf_assert( ! empty( $bhp_nf_r1['ok'] ), 'the signup succeeded', 'code=' . ( $bhp_nf_r1['code'] ?? '?' ) );
bhp_nf_assert( 'success' === ( $bhp_nf_r1['code'] ?? '' ), 'the returned code is success' );

$bhp_nf_p1   = BHP_Mailchimp_Staging_Stub::last_payload();
$bhp_nf_add1 = null;
foreach ( ( $bhp_nf_p1['calls'] ?? array() ) as $bhp_nf_c ) {
	if ( 'add_list_member' === $bhp_nf_c['method'] ) {
		$bhp_nf_add1 = $bhp_nf_c;
		break;
	}
}
bhp_nf_assert( null !== $bhp_nf_add1, '⭐ the transport captured add_list_member()' );

if ( null !== $bhp_nf_add1 ) {
	$bhp_nf_mf1 = (array) ( $bhp_nf_add1['args']['merge_fields'] ?? array() );
	bhp_nf_assert( ( $bhp_nf_add1['args']['email_address'] ?? '' ) === $bhp_nf_email1, 'the email arrived intact' );
	bhp_nf_assert( isset( $bhp_nf_mf1['FNAME'] ), '⭐⭐ FNAME IS PRESENT', implode( ',', array_keys( $bhp_nf_mf1 ) ) );
	bhp_nf_assert( ( $bhp_nf_mf1['FNAME'] ?? '' ) === $bhp_nf_name1, '⭐⭐ FNAME carries the submitted name', (string) ( $bhp_nf_mf1['FNAME'] ?? '' ) );
	bhp_nf_assert( BHP_Mailchimp_Staging_Stub::LIST_ID === ( $bhp_nf_add1['list_id'] ?? '' ), "⛔ it addressed the STUB audience, never production's" );

	/* §5's tag check, read off the SAME captured call. */
	$bhp_nf_tagcall = null;
	foreach ( ( $bhp_nf_p1['calls'] ?? array() ) as $bhp_nf_c2 ) {
		if ( 'update_list_member_tags' === $bhp_nf_c2['method'] ) {
			$bhp_nf_tagcall = $bhp_nf_c2;
			break;
		}
	}
	$GLOBALS['bhp_nf_tagcall'] = $bhp_nf_tagcall;
}

/* ⛔ THE 100-CHARACTER TRUNCATION IS STILL THE ONE IN THE PIPE, and it is
   exercised rather than assumed — a 150-character name must arrive at 100. */
BHP_Mailchimp_Staging_Stub::clear();
$bhp_nf_long = str_repeat( 'A', 150 );
bhp_process_signup(
	array(
		'email'         => 'namefieldlong+bhptest' . time() . '@bhptest.invalid',
		'name'          => $bhp_nf_long,
		'require_name'  => false,
		'context'       => bhp_positivity_news_context(),
		'audience_type' => 'general_readers',
		'lead_magnet'   => '',
		'source_page'   => bhp_positivity_news_url(),
	)
);
$bhp_nf_pL = BHP_Mailchimp_Staging_Stub::last_payload();
foreach ( ( $bhp_nf_pL['calls'] ?? array() ) as $bhp_nf_c ) {
	if ( 'add_list_member' === $bhp_nf_c['method'] ) {
		$bhp_nf_fl = (string) ( $bhp_nf_c['args']['merge_fields']['FNAME'] ?? '' );
		bhp_nf_assert( 100 === strlen( $bhp_nf_fl ), 'a 150-char name is truncated to 100 by the shipped pipe', 'len=' . strlen( $bhp_nf_fl ) );
		break;
	}
}

/* ═══════════════════════════════════════════════════════════════════════════
 * 4 · NO NAME GIVEN — it must STILL SUBSCRIBE, and FNAME must be ABSENT
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⛔⛔ THIS IS THE SECTION THE WHOLE BUILD TURNS ON. "Optional" is a claim
 *     about the empty case and about nothing else.
 * ═══════════════════════════════════════════════════════════════════════════ */

echo "\n=== 4 · NO NAME -> STILL SUBSCRIBES ===\n";

BHP_Mailchimp_Staging_Stub::clear();

$bhp_nf_email2 = 'namefieldempty+bhptest' . time() . '@bhptest.invalid';

$bhp_nf_r2 = bhp_process_signup(
	array(
		'email'         => $bhp_nf_email2,
		'name'          => '',
		'require_name'  => false,
		'context'       => bhp_positivity_news_context(),
		'audience_type' => 'general_readers',
		'lead_magnet'   => '',
		'source_page'   => bhp_positivity_news_url(),
	)
);

bhp_nf_assert( ! empty( $bhp_nf_r2['ok'] ), '⭐⭐ AN EMPTY NAME STILL SUBSCRIBES', 'code=' . ( $bhp_nf_r2['code'] ?? '?' ) );
bhp_nf_assert( 'success' === ( $bhp_nf_r2['code'] ?? '' ), 'the returned code is success' );
bhp_nf_assert( 'missing_name' !== ( $bhp_nf_r2['code'] ?? '' ), '⛔ it was NOT rejected with missing_name' );

$bhp_nf_p2   = BHP_Mailchimp_Staging_Stub::last_payload();
$bhp_nf_add2 = null;
foreach ( ( $bhp_nf_p2['calls'] ?? array() ) as $bhp_nf_c ) {
	if ( 'add_list_member' === $bhp_nf_c['method'] ) {
		$bhp_nf_add2 = $bhp_nf_c;
		break;
	}
}
bhp_nf_assert( null !== $bhp_nf_add2, 'the transport still captured add_list_member()' );

if ( null !== $bhp_nf_add2 ) {
	$bhp_nf_mf2 = (array) ( $bhp_nf_add2['args']['merge_fields'] ?? array() );
	bhp_nf_assert( ( $bhp_nf_add2['args']['email_address'] ?? '' ) === $bhp_nf_email2, 'the email arrived intact' );
	/*
	 * ⛔ ABSENT, NOT EMPTY. `bhp_process_signup()` only sets FNAME when the
	 *    name is non-empty, and an empty merge field is a different object to
	 *    Mailchimp than no merge field.
	 */
	bhp_nf_assert( ! isset( $bhp_nf_mf2['FNAME'] ), '⭐⭐ FNAME IS ABSENT, not present-and-empty', implode( ',', array_keys( $bhp_nf_mf2 ) ) );
	bhp_nf_assert( 'subscribed' === ( $bhp_nf_add2['args']['status'] ?? '' ), 'status is subscribed' );
	/* ⛔ THE REST OF THE SEGMENTATION IS UNCHANGED BY THE ABSENT NAME. */
	bhp_nf_assert( isset( $bhp_nf_mf2['AUDIENCE'] ) || ! empty( $bhp_nf_mf2 ), 'the audience merge fields still ride along', implode( ',', array_keys( $bhp_nf_mf2 ) ) );
}

/* ═══════════════════════════════════════════════════════════════════════════
 * 5 · THE TAGS AND THE FUNNEL POSTURE ARE UNCHANGED
 * ═══════════════════════════════════════════════════════════════════════════ */

echo "\n=== 5 · TAGS UNCHANGED ===\n";

/*
 * ⭐ BY CALLING THE REAL FILTER, not by reading the callback. The ampersand in
 *    "Friends & Family" is load-bearing: Mailchimp matches tags by name, so a
 *    tidied "Friends and Family" would create a THIRD tag and the founder's
 *    segment would silently miss these people.
 */
$bhp_nf_tags = apply_filters(
	'bhp_mailchimp_signup_tags',
	array( 'Adventure Club' ),
	bhp_positivity_news_context(),
	'general_readers',
	'',
	bhp_positivity_news_url()
);

bhp_nf_assert( array( 'Newsletter Only', 'Friends & Family' ) === $bhp_nf_tags, '⭐ exactly the two tags, spelled exactly as they exist in the audience', implode( ' | ', (array) $bhp_nf_tags ) );
bhp_nf_assert( ! in_array( 'Adventure Club', (array) $bhp_nf_tags, true ), '⛔ the default Adventure Club tag is still REPLACED, not appended' );

/*
 * ⛔ AND THE PARENT FUNNEL IS UNTOUCHED — asserted as NON-LEAKAGE, which is
 *    the property that actually matters.
 *
 * ⚠️ THE FIRST DRAFT OF THIS ASSERTION WAS WRONG AND IS RECORDED RATHER THAN
 *   QUIETLY REPLACED. It asserted that `Adventure Club` SURVIVES off-context,
 *   on the assumption that this page's priority-30 callback is the only one on
 *   the filter. It is not: the parent funnel's own priority-10 callbacks in
 *   `functions.php` REPLACE the default outright, so `parent_popup` resolves to
 *   `Reluctant Reader Adventure Kit` / `Audience: Parent/Grandparent` /
 *   `Source: Parent Popup` — observed on staging, 2026-08-31. ⭐ That is
 *   correct, long-standing behaviour that predates this build.
 *
 * ⛔ SO THE RIGHT QUESTION IS NOT "did Adventure Club survive" — it is "did
 *    THIS PAGE'S TWO TAGS LEAK INTO ANOTHER FUNNEL", which is the isolation
 *    rule `.claude/rules/funnels.md` actually states, and which the
 *    priority-30 callback's `!== $context` early return is there to guarantee.
 */
$bhp_nf_other = apply_filters( 'bhp_mailchimp_signup_tags', array( 'Adventure Club' ), 'parent_popup', 'parents_families', 'reluctant_reader_adventure_kit', home_url( '/' ) );
bhp_nf_assert( ! in_array( 'Newsletter Only', (array) $bhp_nf_other, true ), '⛔⛔ "Newsletter Only" does NOT leak into the parent funnel', implode( ' | ', (array) $bhp_nf_other ) );
bhp_nf_assert( ! in_array( 'Friends & Family', (array) $bhp_nf_other, true ), '⛔⛔ "Friends & Family" does NOT leak into the parent funnel' );
bhp_nf_assert( in_array( 'Reluctant Reader Adventure Kit', (array) $bhp_nf_other, true ), '⭐ the parent funnel still resolves its own tags unchanged', implode( ' | ', (array) $bhp_nf_other ) );

/* ⛔ AND THE SAME NON-LEAKAGE FOR THE TEACHER FUNNEL, the other half of the
   isolation rule. */
$bhp_nf_teach = apply_filters( 'bhp_mailchimp_signup_tags', array( 'Adventure Club' ), 'teacher_popup', 'educators', 'mariana_classroom_guide', home_url( '/teachers/' ) );
bhp_nf_assert(
	! in_array( 'Newsletter Only', (array) $bhp_nf_teach, true ) && ! in_array( 'Friends & Family', (array) $bhp_nf_teach, true ),
	'⛔⛔ neither newsletter tag leaks into the teacher funnel',
	implode( ' | ', (array) $bhp_nf_teach )
);

/*
 * ⛔ THE PAGE STILL SUPPRESSES EVERY OTHER ASK — asserted on the RENDERED
 *    PAGE, not by calling the filter.
 *
 * ⚠️ WHY NOT `apply_filters( 'bhp_show_parent_popup', true )`: under
 *   `wp eval-file` there is no queried object, so `bhp_positivity_news_is_page()`
 *   is false by construction and the filter is a no-op. An assertion written
 *   that way would test the CLI environment rather than the page, and would be
 *   red for a reason that has nothing to do with this build. The honest
 *   instrument is the HTML the page actually served, which §2 already fetched.
 */
if ( '' !== $bhp_nf_html ) {
	bhp_nf_assert( 1 === preg_match_all( '#<form[^>]*class="[^"]*acquisition-form#', $bhp_nf_html ), '⛔ exactly ONE acquisition form on the page (no popup stacked on the signup destination)', (string) preg_match_all( '#<form[^>]*class="[^"]*acquisition-form#', $bhp_nf_html ) );
	bhp_nf_assert( false !== stripos( $bhp_nf_html, 'noindex' ), '⛔ the page is still noindex' );
}

$bhp_nf_p = isset( $GLOBALS['bhp_nf_pass'] ) ? $GLOBALS['bhp_nf_pass'] : 0;
$bhp_nf_f = isset( $GLOBALS['bhp_nf_fail'] ) ? $GLOBALS['bhp_nf_fail'] : 0;
echo "\n=== CYCLE170-LD-NAMEFIELD: PASS: {$bhp_nf_p}  FAIL: {$bhp_nf_f} ===\n";
echo( 0 === $bhp_nf_f ? "ALL PASS\n" : "SOME FAILED\n" );
