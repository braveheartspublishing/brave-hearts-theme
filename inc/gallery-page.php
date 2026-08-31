<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * GALLERY — THE CATEGORY MAP. Theme 1.19.319 (2026-08-29,
 * `CYCLE169-LD-READALOUD-TRUST-GALLERY`), PHASE 2 SKELETON.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * The data half of `/gallery/`. `page-gallery.php` holds the markup; this holds
 * the decisions, mirroring the split already used by `/author-visits/`.
 *
 * Andrew Signore, carrier item 432: *"a gallery on the main nav bar of all the
 * picture from all the read alouds and farmers markets etc with meta tags of
 * course"*.
 *
 * ⛔ IT INTRODUCES NO NEW PHOTO SOURCE. The read-alouds category is exactly
 *    `bhp_author_visits_gallery_photos()` — the same founder-cleared set the
 *    visits page renders, through the same validation, with the same alt-text
 *    requirement. One source of truth means a photograph cannot be cleared for
 *    one surface and uncleared on the other.
 *
 * ⛔ THE MARKETS CATEGORY IS DELIBERATELY EMPTY, AND THAT IS A FINDING RATHER
 *    THAN AN OVERSIGHT. Andrew named farmers markets explicitly. The folder
 *    those photographs were expected in was opened on 2026-08-29 and contains
 *    NO FILES. So the category is declared with an empty photo list, the page
 *    hides it, and nothing is invented, substituted or generated to fill it.
 *    When real market photographs exist they are added the same way the Adams
 *    set was: cleared by Andrew, given alt text, and registered in an option.
 *
 * ⚠ MARKET PHOTOGRAPHS WILL NEED A CLEARANCE PASS THE CLASSROOM SET DID NOT.
 *   The Adams photographs are covered by signed school permission slips (item
 *   368). A market crowd shot has no such coverage: members of the public in
 *   frame have consented to nothing. The rule for that set, when it arrives, is
 *   no identifiable stranger's face without clearance, and when in doubt the
 *   photograph is excluded and flagged rather than cropped and hoped about.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐ 1.19.325 (2026-08-29, `CYCLE169-LD-READALOUD-FUNNEL`) — THE PAGE IS NO
 *    LONGER A GALLERY. IT IS A READ-ALOUD FUNNEL, AND THE GALLERY IS ONE
 *    SECTION INSIDE IT.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Founder rulings, carrier items 480 and 481, relayed in the build brief:
 * the staged `/gallery/` page was **REJECTED as final** — *"a photo wall, not
 * a funnel"* — and read-alouds are **currently free**.
 *
 * ⛔ `bhp_gallery_sections()` BELOW IS UNCHANGED, DELIBERATELY. The photographs
 *    and their captions are approved and this lane does not touch approved
 *    assets. Everything the rebuild needed was ADDED beside it.
 *
 * ⛔ NOT ONE WORD OF FOUNDER-VOICE PROSE IS WRITTEN BY THIS FILE. Every
 *    narrative passage on the page is a `bhp_readaloud_funnel_copy_slots()`
 *    entry that renders as a loud, deliberately ugly
 *    `[PENDING READ-BACK — do not publish]` block. A concurrent marketing lane
 *    is drafting the real passages and they land after Andrew's read-back.
 *    **If a future pass is tempted to "just write something reasonable" into
 *    one of these slots, that is the exact failure §27 exists to prevent.**
 *
 * ⛔ THERE IS NO PRICE ANYWHERE ON THIS PAGE AND NONE EXISTS TO PUT THERE.
 *    `bhp_readaloud_funnel_show_pricing()` is the structural slot, and it is
 *    gated OFF. See its docblock.
 *
 * @package BraveHearts
 */

defined( 'ABSPATH' ) || exit;

/**
 * The read-aloud booking route.
 *
 * ⛔ A `mailto:`, NOT A NEW FORM — the same deliberate scope line drawn on
 *    `/author-visits/` at 1.19.319, and drawn again here rather than quietly
 *    re-decided. A request form is fields plus validation plus storage plus
 *    spam handling plus notification; that is its own build with its own
 *    approval surface. The address is already public on this site and is the
 *    route Andrew gave parents himself (item 377).
 *
 * ⛔ THE SUBJECT IS PRE-FILLED AND THE BODY IS NOT. A prefilled body puts words
 *    in a stranger's mouth and gets deleted before it is read.
 *
 * @return array{email:string,subject:string,href:string,label:string}
 */
function bhp_readaloud_funnel_cta() {
	$email   = 'andrew@braveheartspublishing.com';
	$subject = 'Read-aloud request';

	return apply_filters(
		'bhp_readaloud_funnel_cta',
		array(
			'email'   => $email,
			'subject' => $subject,
			'href'    => 'mailto:' . $email . '?subject=' . rawurlencode( $subject ),
			/*
			 * Founder-ruled wording, item 481 by way of the build brief:
			 * "Book a FREE read-aloud". The capitalised FREE is his, not a
			 * house style choice, and it is not "corrected" here.
			 */
			'label'   => __( 'Book a FREE read-aloud', 'brave-hearts' ),
		)
	);
}

/**
 * Every copy slot on the page that a human still has to fill.
 *
 * ⭐ THIS FUNCTION IS THE WHOLE POINT OF THE REBUILD'S HONESTY POSITION. The
 *    page has to exist and be walkable today, and the passages that carry it
 *    do not exist yet. Rather than invent them, each one is declared here with
 *    a slot id, the section it belongs to, and a plain description of what the
 *    marketing lane is writing — and it RENDERS as an unmistakable placeholder.
 *
 * ⛔ `pending` is `true` for every entry today. When real copy arrives, the
 *    lane that lands it replaces `pending` with the approved text and the
 *    placeholder styling disappears on its own. Nothing else has to change.
 *
 * ⛔ NO ENTRY BELOW CONTAINS DRAFT COPY, SUGGESTED COPY, OR "SOMETHING TO GET
 *    US STARTED". The `spec` strings describe the brief; they are not prose
 *    anybody could mistake for Andrew's, and the renderer marks them as such.
 *
 * @return array<string,array{section:string,label:string,spec:string,pending:bool}>
 */
function bhp_readaloud_funnel_copy_slots() {
	$slots = array(
		'founder-1'   => array(
			'section' => 'founder',
			'label'   => __( 'FOUNDER INTRO — PARAGRAPH 1', 'brave-hearts' ),
			'spec'    => __( 'Who is coming to read, in his own words. Awaiting the marketing lane draft and Andrew\'s morning read-back.', 'brave-hearts' ),
			'pending' => true,
		),
		'founder-2'   => array(
			'section' => 'founder',
			'label'   => __( 'FOUNDER INTRO — PARAGRAPH 2', 'brave-hearts' ),
			'spec'    => __( 'What a read-aloud actually is on the day. Awaiting the marketing lane draft and Andrew\'s morning read-back.', 'brave-hearts' ),
			'pending' => true,
		),
		'founder-3'   => array(
			'section' => 'founder',
			'label'   => __( 'FOUNDER INTRO — PARAGRAPH 3', 'brave-hearts' ),
			'spec'    => __( 'Why he does it. Awaiting the marketing lane draft and Andrew\'s morning read-back.', 'brave-hearts' ),
			'pending' => true,
		),
		'educators-1' => array(
			'section' => 'educators',
			'label'   => __( 'TEACHERS AND LIBRARIANS — LEAD PARAGRAPH', 'brave-hearts' ),
			'spec'    => __( 'What a teacher or librarian gets, and what booking one involves. Awaiting the marketing lane draft and Andrew\'s morning read-back.', 'brave-hearts' ),
			'pending' => true,
		),
	);

	/**
	 * Filter the funnel's copy slots.
	 *
	 * The route by which approved copy lands without editing this file. An
	 * entry whose `pending` is false and whose `copy` is a non-empty string
	 * renders as ordinary prose with no placeholder chrome.
	 *
	 * @param array $slots Slot map.
	 */
	return apply_filters( 'bhp_readaloud_funnel_copy_slots', $slots );
}

/**
 * Render one copy slot.
 *
 * ⛔ THE PLACEHOLDER IS UGLY ON PURPOSE. Hazard stripes, a heavy dashed red
 *    rule, monospace, shouting capitals. Nobody looking at this page — Andrew
 *    included — should be able to mistake one of these blocks for approved
 *    copy, and a placeholder that looks like finished design is exactly how
 *    unapproved words reach a customer.
 *
 * @param string $slot_id Key in `bhp_readaloud_funnel_copy_slots()`.
 * @return void
 */
function bhp_readaloud_funnel_render_slot( $slot_id ) {
	$slots = bhp_readaloud_funnel_copy_slots();
	if ( ! isset( $slots[ $slot_id ] ) ) {
		return;
	}
	$slot = $slots[ $slot_id ];

	$approved = empty( $slot['pending'] ) && ! empty( $slot['copy'] );
	if ( $approved ) {
		echo '<p class="readaloud-funnel__copy">' . esc_html( (string) $slot['copy'] ) . '</p>';
		return;
	}
	?>
	<div class="bhp-copy-placeholder" data-copy-slot="<?php echo esc_attr( $slot_id ); ?>" role="note">
		<p class="bhp-copy-placeholder__flag"><?php echo esc_html( '[PENDING READ-BACK — do not publish]' ); ?></p>
		<p class="bhp-copy-placeholder__label"><?php echo esc_html( $slot['label'] ); ?></p>
		<p class="bhp-copy-placeholder__spec"><?php echo esc_html( $slot['spec'] ); ?></p>
	</div>
	<?php
}

/**
 * Whether the structural pricing slot is shown.
 *
 * ⛔⛔ IT IS OFF, AND IT IS OFF BECAUSE THERE IS NO PRICE. Item 481: read-alouds
 *     are currently FREE. The section exists as a structural slot so that a
 *     future ruling has somewhere obvious to land, and it renders `hidden` with
 *     `display:none` and **carries no figure, no currency symbol and no fee
 *     word of any kind**. A test asserts that.
 *
 * ⛔ TURNING THIS ON IS NOT A DEVELOPER'S DECISION. Charging for a read-aloud
 *    is a founder ruling, and the copy that would go in the slot does not
 *    exist. Flipping the filter without both is how a page starts quoting a
 *    price nobody approved.
 *
 * @return bool Always false unless a filter says otherwise.
 */
function bhp_readaloud_funnel_show_pricing() {
	return (bool) apply_filters( 'bhp_readaloud_funnel_show_pricing', false );
}

/**
 * The gallery's categories, in render order.
 *
 * Shape, per key:
 *   title  string  The section heading.
 *   photos array   Rows shaped like `bhp_author_visits_gallery_photos()`.
 *
 * @return array<string,array{title:string,photos:array}>
 */
function bhp_gallery_sections() {
	$read_alouds = function_exists( 'bhp_author_visits_gallery_photos' ) ? bhp_author_visits_gallery_photos() : array();

	$sections = array(
		'read-alouds' => array(
			'title'  => __( 'School read-alouds', 'brave-hearts' ),
			'photos' => $read_alouds,
		),
		'markets'     => array(
			// ⛔ EMPTY BY FACT, NOT BY PLACEHOLDER. See the file header.
			'title'  => __( 'Markets and events', 'brave-hearts' ),
			'photos' => array(),
		),
	);

	/**
	 * Filter the gallery's categories.
	 *
	 * The route by which a future photo set — markets, festivals, bookshops —
	 * is added without editing this file. It is NOT a route for hardcoding
	 * photographs: every entry still passes through the same validation and
	 * still requires alt text.
	 *
	 * @param array $sections Category map.
	 */
	return apply_filters( 'bhp_gallery_sections', $sections );
}
