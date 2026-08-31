<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * POSITIVITY NEWS — the own-site newsletter signup page. 1.19.333
 * (2026-08-30, `CYCLE170-LD-BUNDLE`). STAGING ONLY. Slug `positivity-news`.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Carrier items **520 / 521**, relayed in the build brief: the newsletter needs
 * a signup page **on this site**, in the funnel's own brand, rather than only a
 * Mailchimp-hosted landing page.
 *
 * ---------------------------------------------------------------------------
 * ⭐ WHAT THIS IS *NOT*, AND THE DISTINCTION MATTERS FOR THE HANDOVER
 * ---------------------------------------------------------------------------
 * There is ALSO a Mailchimp-hosted landing page for this newsletter: id
 * **42351**, slug `positivity-news`, DRAFT and unpublished as of 2026-08-30
 * (`WORKING-DRAFTS\connected-operator\CYCLE170-GIM-LANDING-FILL\RESULT-42351-2026-08-30.md`).
 * ⛔ THAT PAGE IS NOT THIS PAGE AND NEITHER SUPERSEDES THE OTHER. It is
 *    Gimli's, it lives on `mailchi.mp`, and publishing it is Andrew's click.
 *    This file builds the theme-native surface at
 *    `https://braveheartspublishing.com/positivity-news/`.
 *
 * ⚠ THE TWO WILL HAVE THE SAME NAME AND THE SAME COPY. That is deliberate (one
 *   promise, two doors), but it is the sort of thing a future reader discovers
 *   as a "duplicate" and deletes. It is not a duplicate; it is two channels.
 *
 * ---------------------------------------------------------------------------
 * ⛔⛔ NO LEAD MAGNET. NONE. THIS IS THE ONE RULE THE PAGE EXISTS TO KEEP.
 * ---------------------------------------------------------------------------
 * The brief is explicit: *"NO lead magnet promise (they get the newsletter,
 * that's it — the thank-you state says so)."*
 *
 * ⛔ SO `lead_magnet` IS THE EMPTY STRING, EVERYWHERE, ON PURPOSE. Every other
 *    capture surface on this site passes a magnet key; this one must not,
 *    because a magnet key is what makes the pipe promise a PDF. Passing
 *    `reluctant_reader_adventure_kit` "so the tagging looks tidy" would enrol a
 *    newsletter subscriber in the parent funnel and promise them a download
 *    that this page never mentioned.
 *
 * ⭐ AND THE EMPTY KEY IS WHAT MAKES THE TAGGING SAFE. The priority-10 callbacks
 *    in `functions.php` all branch on `$lead_magnet`; with none set, not one of
 *    them fires and the default `['Adventure Club']` reaches this file's
 *    callback untouched.
 *
 * ---------------------------------------------------------------------------
 * ⛔ COPY PROVENANCE — carrier item 489, verbatim, via the copy deck
 * ---------------------------------------------------------------------------
 * Every visible string is copied CHARACTER-EXACT from
 * `WORKING-DRAFTS\connected-operator\CYCLE170-GIM-TRIPLE\LANDING-PAGE-COPY-DECK.md`,
 * whose own header records it as *"sourced only from carrier item 489 verbatim;
 * nothing invented"*. ⭐ It has a SECOND, INDEPENDENT WITNESS: Gimli loaded the
 * same strings into Mailchimp page 42351 and read them back out of the rendered
 * preview, character for character (`RESULT-42351-2026-08-30.md` §1). Unlike the
 * item-523 visit copy, this text is two-source verified.
 *
 * ⛔ ZERO EM DASHES AND ZERO EN DASHES, asserted mechanically by
 *    `tests/test-cycle170-bundle.php`.
 *
 * ⛔ NO CLAIM IS MADE ABOUT THE LIST. No subscriber count, no open rate, no
 *    "join thousands", no testimonial. The audience is thirteen people and §3
 *    forbids inventing a number anyway.
 *
 * @package BraveHearts
 */

defined( 'ABSPATH' ) || exit;

/**
 * The slug.
 *
 * @return string
 */
function bhp_positivity_news_slug() {
	return 'positivity-news';
}

/**
 * The URL.
 *
 * @return string
 */
function bhp_positivity_news_url() {
	return home_url( '/' . bhp_positivity_news_slug() . '/' );
}

/**
 * The signup context this page reports into the pipe.
 *
 * ⛔ ONE PLACE, BECAUSE THIS STRING IS A JOIN KEY. It is written into the form's
 *    `bhp_context` hidden field, it is what the tag callback below branches on,
 *    and it lands in analytics as the form placement. Three consumers, one
 *    literal. `sanitize_key()` runs over it in the pipe, so it must stay
 *    lowercase and underscored.
 *
 * @return string
 */
function bhp_positivity_news_context() {
	return 'positivity_news';
}

/**
 * The approved copy, carrier item 489.
 *
 * ⛔ RETURNED AS AN ARRAY RATHER THAN TYPED INTO THE TEMPLATE so the strings can
 *    be asserted by a suite without parsing HTML, and so a copy change is one
 *    edit in one file rather than a hunt through markup.
 *
 * @return array<string,mixed>
 */
function bhp_positivity_news_copy() {
	return array(
		'headline' => 'Positivity News by Brave Hearts Publishing',
		'subhead'  => 'An ounce of positivity in a dark place.',
		'body'     => array(
			'Everyone knows the news is negative. This is the opposite.',
			'Once a month I will send you the highlights from the company. Only positive things to brighten your day, I promise.',
		),
		'submit'   => 'Subscribe',
		/*
		 * ⛔ THE THANK-YOU STATE IS WHERE "NO LEAD MAGNET" BECOMES VISIBLE TO
		 *    THE SUBSCRIBER. It says what they will get and it stops. There is
		 *    no download link, no "check your inbox for your PDF", and no
		 *    second offer anywhere on the success state.
		 */
		'thanks'   => 'Thank you for subscribing to Brave Hearts Publishing. You will get this email once a month. Only positive things, I promise.',
		'sign_off' => 'Big Places. Brave Hearts.',
	);
}

/* ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐ 1.19.337 (2026-08-30, `CYCLE170-LD-MICRO`) — THE READ-ALOUD PHOTOGRAPH.
 *     CARRIER ITEM 545.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⭐⭐ THE FOUNDER'S WORDS, verbatim, 2026-08-30, carrier item 545:
 *
 *      "Excellent if we can do a gradient picture below it of the read aloud
 *       with the kiddos that would make it pop"
 *
 *    ⛔ RELAYED through `chief-of-staff`; read first-hand at the carrier file
 *      before this function was written. NOT witnessed by this desk.
 *
 * ---------------------------------------------------------------------------
 * ⛔⛔ NO NEW PHOTOGRAPH, NO CROP, NO EDIT, NO RE-ENCODE, NO GENERATION.
 * ---------------------------------------------------------------------------
 * `adams-elementary-read-aloud-group.jpg` is a photograph ANDREW ALREADY
 * PUBLISHED. It is 1200x675, it already ships in this theme, it is already
 * rendered by `/school-read-alouds/`'s carousel, `/gallery/` and
 * `/author-visits/`, and it is the same frame the blog hero uses. ⭐ The whole
 * "gradient treatment" is CSS on top of the existing file — see the
 * `.bhp-positivity__photo` block in style.css. Not one byte of the image is
 * touched, which is what keeps §26-class asset discipline and the no-AI-images
 * rule out of this entirely.
 *
 * ⭐ CONSENT ON THIS EXACT PHOTOGRAPH IS CLOSED, not assumed: `CYCLE141-CX-48`
 *    was closed by Andrew's own attestation at carrier item 510, recorded in
 *    `page-school-read-alouds.php` section e.
 *
 * ---------------------------------------------------------------------------
 * ⛔⛔ WHY THE ALT TEXT IS CARRIED HERE AND NOT READ FROM THE REGISTRY
 * ---------------------------------------------------------------------------
 * The alt Andrew published lives in the `bhp_school_visit_notes` OPTION, and
 * the carousel reads it live from there. ⚠️ THAT OPTION DOES NOT EXIST ON
 * PRODUCTION. Verified read-only this session, 2026-08-30, `wp option get
 * bhp_school_visit_notes` against both docroots: staging returns the record,
 * production returns *"Could not get ... option. Does it exist?"*.
 *
 * ⛔ SO A REGISTRY-ONLY READ WOULD SHIP THIS PAGE WITH AN EMPTY `alt` ON
 *    PRODUCTION — an undescribed photograph of identifiable children, which is
 *    the one outcome that is worse than no photograph.
 *
 * ⭐ THE STRING BELOW IS THEREFORE THE FOUNDER-PUBLISHED ALT, COPIED
 *    CHARACTER-EXACT from the staging registry row `adams-2026-08-28` and read
 *    first-hand this session. ⛔ IT IS NOT COMPOSED, NOT SHORTENED AND NOT
 *    "IMPROVED": it is a verbatim copy of his own words with its source named,
 *    which is a quotation, not an invention.
 *
 * ⭐ THE REGISTRY STILL WINS WHEN IT IS THERE. `bhp_positivity_news_photo()`
 *    prefers the live option and falls back to the copy, so on staging the page
 *    shows exactly what the carousel shows and can never drift from it.
 * ═══════════════════════════════════════════════════════════════════════════ */

/**
 * The file this page's photograph uses.
 *
 * ⛔ ONE PLACE, because the filename is a join key: the template renders it,
 *    the stylesheet's aspect-ratio box is sized for its 1200x675, and the suite
 *    asserts it. Three consumers, one literal.
 *
 * @return string
 */
function bhp_positivity_news_photo_file() {
	return 'adams-elementary-read-aloud-group.jpg';
}

/**
 * The photograph, with its founder-published description.
 *
 * @return array{file:string,url:string,alt:string,w:int,h:int}
 */
function bhp_positivity_news_photo() {
	$file = bhp_positivity_news_photo_file();

	/*
	 * ⭐ THE REGISTRY FIRST. On any environment that HAS the option, this page
	 *    and the carousel read one string, so a future correction to the alt
	 *    lands on both surfaces at once.
	 */
	$alt = '';
	if ( function_exists( 'bhp_author_visits_gallery_photos' ) ) {
		foreach ( (array) bhp_author_visits_gallery_photos() as $row ) {
			if ( isset( $row['file'], $row['alt'] ) && $file === $row['file'] && '' !== (string) $row['alt'] ) {
				$alt = (string) $row['alt'];
				break;
			}
		}
	}

	if ( '' === $alt ) {
		/* ⛔ THE VERBATIM COPY. See the block comment above for its provenance
		   and for the measured reason this fallback is not optional. */
		$alt = __( 'Andrew Signore with a class of first and second graders and their librarian in the library at Adams Elementary in Boise, many of the children holding Adventures of Charlotte and Henry books.', 'brave-hearts' );
	}

	return array(
		'file' => $file,
		'url'  => function_exists( 'bhp_author_visits_photo_url' ) ? bhp_author_visits_photo_url( $file ) : '',
		'alt'  => $alt,
		/* ⛔ THE REAL FILE DIMENSIONS, measured on the asset. They reserve the
		   box so a late-arriving image cannot reflow the page under the reader. */
		'w'    => 1200,
		'h'    => 675,
	);
}

/**
 * The two tags every signup on this page carries.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔⛔ EXACTLY TWO, SPELLED EXACTLY AS THEY EXIST IN THE AUDIENCE.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⭐ BOTH TAGS WERE CREATED IN THE LIVE AUDIENCE ON 2026-08-30 and neither
 *    existed before. That is OBSERVED, not assumed:
 *    `RESULT-42351-2026-08-30.md` §5 enumerates the audience's full 25-tag list
 *    from before the pass and neither name is in it, and records that Mailchimp's
 *    typeahead offered only `Add "Newsletter Only"` and `Add "Friends & Family"`.
 *
 * ⛔ THE SPELLING IS THEREFORE LOAD-BEARING AND CANNOT BE TIDIED. Mailchimp
 *    matches tags by name; `Friends and Family`, `Friends & family` or a
 *    trailing space each create a THIRD tag rather than joining the existing
 *    one, and the segment Andrew builds on it would silently miss these people.
 *    The ampersand survives `sanitize_text_field()` unencoded, which the suite
 *    asserts by calling the real filter rather than by reading this comment.
 *
 * ⭐ NEITHER TAG TRIGGERS A JOURNEY, and that was checked rather than hoped:
 *    `JOURNEY-TRIGGER-AUDIT-2026-08-30.md` found neither name in any journey
 *    entry trigger, and §5 above explains why that is structurally certain —
 *    they did not exist until the day they were made. ⛔ A contact tagged only
 *    with these two enters no funnel, which is the entire point of "Newsletter
 *    Only".
 *
 * ---------------------------------------------------------------------------
 * ⛔ PRIORITY 30, AND THE PRIORITY IS LOAD-BEARING RATHER THAN COSMETIC.
 * ---------------------------------------------------------------------------
 * `inc/read-aloud-landing.php` already occupies priority 20 on this filter. At
 * the same priority the two would race on registration order, which depends on
 * where a `require_once` line happens to sit in a 380 KB file. 30 makes the
 * outcome a stated rule instead of an accident. ⭐ Asserted by CALLING the real
 * filter in `tests/test-cycle170-bundle.php`, not by reading it.
 *
 * ⛔ IT RETURNS OUTRIGHT RATHER THAN APPENDING. A newsletter-only subscriber
 *    must not also carry `Adventure Club` (the pipe's default) or any audience
 *    tag: those are funnel membership, and this page sells no funnel.
 *
 * ⛔ AND IT IS SCOPED TO THIS PAGE'S CONTEXT. Off-context it returns `$tags`
 *    untouched, so no other form on the site changes by one character.
 *
 * @param array  $tags          Tags resolved so far.
 * @param string $context       Signup context.
 * @param string $audience_type Normalised audience.
 * @param string $lead_magnet   Lead-magnet key.
 * @param string $source_page   Source page URL.
 * @return array
 */
function bhp_positivity_news_mailchimp_tags( $tags, $context, $audience_type = '', $lead_magnet = '', $source_page = '' ) {
	if ( bhp_positivity_news_context() !== $context ) {
		return $tags;
	}

	return array( 'Newsletter Only', 'Friends & Family' );
}
add_filter( 'bhp_mailchimp_signup_tags', 'bhp_positivity_news_mailchimp_tags', 30, 5 );

/**
 * Is the current request the newsletter signup page?
 *
 * ⭐ TEMPLATE FIRST, SLUG SECOND — the same two-limb test and the same reason as
 *    `bhp_school_readalouds_is_page()`: a page created without its
 *    `_wp_page_template` meta would otherwise silently keep every sitewide ask
 *    on a dedicated signup destination.
 *
 * @return bool
 */
function bhp_positivity_news_is_page() {
	if ( is_admin() ) {
		return false;
	}
	if ( is_page_template( 'page-positivity-news.php' ) ) {
		return true;
	}
	return is_page( bhp_positivity_news_slug() );
}

/**
 * One ask on this page, and it is the one the page is for.
 *
 * ⛔ THIS IS THE SITE'S EXISTING RULE, NOT A NEW ONE. `bhp_should_show_any_popup()`
 *    already excludes every dedicated signup destination by template —
 *    `page-reluctant-reader-adventure-kit.php`, the four audience pages, both
 *    Mariana guides — with the reason stated there: *"never stack a sitewide or
 *    another audience's popup on top of it."* This page is a dedicated signup
 *    destination, so it takes the same treatment.
 *
 * ⛔ DONE THROUGH EACH GATE'S OWN FILTER RATHER THAN BY ADDING TWO MORE ENTRIES
 *    TO TWO ARRAYS IN `functions.php`. Same reasoning as
 *    `inc/read-aloud-landing.php` and `inc/school-read-alouds.php`: the proven
 *    eligibility logic stays byte-untouched, and this feature's whole footprint
 *    is its own two files plus one `require` line.
 *
 * ⛔ EVERY CALLBACK RETURNS `$show` UNCHANGED OFF-TEMPLATE.
 *
 * @param bool $show Whether the surface may render.
 * @return bool
 */
function bhp_positivity_news_suppress_asks( $show ) {
	return bhp_positivity_news_is_page() ? false : $show;
}
add_filter( 'bhp_show_parent_popup', 'bhp_positivity_news_suppress_asks' );
add_filter( 'bhp_show_parent_ab_popup', 'bhp_positivity_news_suppress_asks' );
add_filter( 'bhp_show_exit_intent_popup', 'bhp_positivity_news_suppress_asks' );
add_filter( 'bhp_show_quiz_cta', 'bhp_positivity_news_suppress_asks' );
add_filter( 'bhp_show_footer_capture', 'bhp_positivity_news_suppress_asks' );

/* ═══════════════════════════════════════════════════════════════════════════
 * ⭐ `noindex`, AND NOT IN THE NAVIGATION EITHER.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * The brief: *"noindex, NOT in nav."* This page is the destination of a link in
 * an email, a signature, a social post or a card — a place somebody is SENT, not
 * a place they should find in a search result competing with the books.
 *
 * ⛔ THE "NOT IN NAV" HALF IS NOT ENFORCEABLE FROM HERE AND IS NOT PRETENDED TO
 *    BE. A WordPress menu is data, not code: nothing in this file can stop
 *    somebody adding the page in wp-admin. What this file CAN do is refuse to
 *    add it, which it does by doing nothing — the deploy plan's nav step adds
 *    "Read-Alouds" and this page deliberately is not in it.
 *
 * ⛔ BOTH ROBOTS FILTERS ARE SET, because WordPress writes the robots meta and
 *    Rank Math overwrites it. Setting one is how a page ships believing it is
 *    noindexed and is not (`inc/reviews.php` records the same finding).
 *
 * ⛔ `follow` IS KEPT. The page links to nothing but this site; nofollow would
 *    buy nothing and would drop internal equity for no reason.
 * ═══════════════════════════════════════════════════════════════════════════ */

/**
 * WordPress core's robots meta.
 *
 * @param array<string,mixed> $robots Directives.
 * @return array<string,mixed>
 */
function bhp_positivity_news_robots( $robots ) {
	if ( ! bhp_positivity_news_is_page() ) {
		return $robots;
	}
	$robots['noindex'] = true;
	unset( $robots['index'] );
	return $robots;
}
add_filter( 'wp_robots', 'bhp_positivity_news_robots' );

/**
 * Rank Math's robots meta, which overwrites core's.
 *
 * @param array<string,string> $robots Rank Math directives.
 * @return array<string,string>
 */
function bhp_positivity_news_rankmath_robots( $robots ) {
	if ( ! bhp_positivity_news_is_page() ) {
		return $robots;
	}
	return array(
		'index'  => 'noindex',
		'follow' => 'follow',
	);
}
add_filter( 'rank_math/frontend/robots', 'bhp_positivity_news_rankmath_robots' );
