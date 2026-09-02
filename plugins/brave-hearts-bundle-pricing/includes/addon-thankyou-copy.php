<?php
/**
 * Brave Hearts Bundle Pricing - THE COPY FILE.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * ⭐ THIS FILE IS THE ONLY PLACE CUSTOMER-FACING WORDS LIVE for the
 *    activity-book thank-you email AND for the add-on-only cart guard.
 * ═══════════════════════════════════════════════════════════════════════
 *
 * It exists as its own file for one reason: swapping the approved copy in
 * must be a ONE-FILE change. The email class, the two templates and the
 * cart guard all read from `bhp_bundle_addon_thankyou_copy()` and contain
 * no strings of their own.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * ⭐ STATUS: `APPROVED` (1.8.22). Was `APPROVED-PENDING-ANDREW-CONFIRM`
 *    (1.8.21), and `PLACEHOLDER` before that.
 * ═══════════════════════════════════════════════════════════════════════
 *
 * ⛔ THE PLACEHOLDER PROSE THAT USED TO BE HERE IS GONE. Every customer-
 *    facing string below is now the `marketing-growth` drafted copy,
 *    read this session from the DRIVE mount at
 *
 *      Business OS\WORKING-DRAFTS\marketing-growth\
 *      DRAFT-2026-08-04-UPSELL-THANKYOU-EMAIL.md
 *
 *    (DRIVE mount only. The path is written relative to `Business OS\`
 *    because the absolute Drive path contains an em dash and this plugin
 *    carries none.)
 *
 * ⭐ THE FILE'S ABSENCE AT BUILD TIME WAS AN INSTRUMENT FAILURE, NOT AN
 *    ABSENCE. The 1.8.21 build session recorded, twice and in good faith,
 *    that the draft "WAS NOT PRESENT ON DISK". It was. A fresh directory
 *    listing of the same DRIVE folder this session returned it among 50
 *    entries. Recorded rather than quietly overwritten, because the next
 *    session that cannot see a Drive path should try a fresh listing
 *    before concluding the file does not exist.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * ⭐ BOTH ANDREW CONFIRMS ARE CLOSED (2026-08-04). The 1.8.21 text of this
 *    block is preserved verbatim below rather than deleted, so the reason
 *    each gate existed survives its closure.
 * ═══════════════════════════════════════════════════════════════════════
 *
 * ⛔ HISTORICAL, 1.8.21 - "TWO OPEN ANDREW CONFIRMS - why the status is
 *    not a bare `APPROVED`":
 *
 *   1. LICENCE WORDING. "Print the pages you like, as many times as you
 *      like. It is yours to keep." is IN, on the `chief-of-staff` review direction.
 *      It is a licence statement and no licence text exists for this
 *      product. The `marketing-growth` gate G-B; its conflict `CYCLE143-MKT-123`. The
 *      teacher-class-set question is the one that will actually be asked.
 *   2. SIGN-OFF NAME. "Andrew" is IN, on the same direction. The build
 *      brief said "from Brave Hearts Publishing"; every shipped
 *      transactional email in the theme signs "Andrew". The `marketing-growth` gate G-C;
 *      her conflict `CYCLE143-MKT-125`.
 *
 * ⭐ HOW EACH CLOSED - Andrew Signore, 2026-08-04, MESSAGES 35-36,
 *    witnessed by the main session and relayed to this build session by
 *    `chief-of-staff`. ⚠ RELAYED, not witnessed first-hand here.
 *    Recorded at `Business OS\WORKING-DRAFTS\chief-of-staff\
 *    OVERNIGHT-EXECUTION-REGISTER-2026-08-04.md` lines 167-170.
 *
 *   1. LICENCE. His word was "Classroom ok". The gate is CLOSED and the
 *      sentence CHANGED: the grant is now personal AND classroom printing,
 *      which is exactly the teacher-class-set question the 1.8.21 note
 *      predicted would be asked. The sentence now reads
 *
 *        "Print the pages you like, as many times as you like, for your
 *         home or your classroom. It is yours to keep."
 *
 *      ⚠ MINIMAL EDIT ON PURPOSE. One clause added inside the `marketing-growth`
 *        sentence; her cadence, her verbs and her closing line are
 *        untouched. It grants a permission and claims NOTHING - no
 *        licence text, no rights statement, no school or district
 *        entitlement, no redistribution or resale permission, and no
 *        teacher, classroom or reading claim of any kind. `CYCLE143-MKT-123`
 *        closes.
 *   2. SIGN-OFF. "Activity book email approved" - the email as deployed on
 *      staging 1.8.21, which signs "Andrew". The gate is CLOSED and NO
 *      STRING CHANGED. `CYCLE143-MKT-125` closes.
 *
 * `status` is therefore `APPROVED` and `open_confirms` is EMPTY. Anything
 * else in this file is unchanged approved copy: the sign-off, the subject,
 * the paragraphs and the guard message are byte-identical to 1.8.21.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * ⛔ ONE SENTENCE OF THE APPROVED DRAFT WAS DELIBERATELY OMITTED
 * ═══════════════════════════════════════════════════════════════════════
 *
 * The `marketing-growth` paragraph 2 ends "...and the answer key is on the last two
 * pages." ⛔ OMITTED on the `chief-of-staff` review direction. It is TRUE OF v4 AND
 * OF NO OTHER VERSION (v1/v2: one page at 20; v3: two pages at 20 and 21;
 * v4: two pages at 25 and 26), and which PDF the live product will carry
 * is pending Andrew's v4/v5 word. The `marketing-growth` conflict `CYCLE143-MKT-121`
 * says it in her words: "Attach v4, or delete the clause. Do not reword it
 * to be vaguely true." It is deleted, not reworded.
 *
 * ⚠ The test suite ASSERTS its absence by string, so it cannot creep back
 *   in without a deliberate test change.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * ⛔ HARD CONSTRAINTS ON EVERY STRING IN THIS FILE
 * ═══════════════════════════════════════════════════════════════════════
 *
 *   - NO EM DASH (U+2014) and no en dash (U+2013). Sitewide rule, commit
 *     3ef65be. Asserted by the test suite by codepoint, not by eye.
 *   - NOTHING ON THE NEVER-INVENT LIST: no review, rating, testimonial,
 *     statistic, award, endorsement, parent or teacher reaction, classroom
 *     or reading result, medical or developmental claim, sales figure.
 *   - READING AGE: the band is "6 to 9" and NOTHING ELSE IS EVER
 *     PERMITTED. ⚠ CHANGED FROM THE 1.8.21 PLACEHOLDER, which claimed no
 *     age at all. The approved copy DOES carry the band, and it is sourced
 *     (the `marketing-growth` claim 6: company audience of record, printed in B1 and B2
 *     front matter, on the activity book cover). Standing Rules §9 fixes
 *     it at 6-9 and never 5-9. The test suite now screens for ANY age band
 *     other than "6 to 9" rather than for any age band at all, which is a
 *     STRICTER and more useful screen than the one it replaces.
 *   - NO PAGE COUNT. The point-of-sale string `bhp_bundle_addon_copy()`
 *     carries "26 pages"; this email deliberately does not, so a version
 *     change cannot make the delivery email false.
 *   - ACTIVITY INVENTORY: permitted, and it agrees with the point of sale.
 *     Sale says "coloring, mazes and word searches"; this says "coloring
 *     pages, word searches and mazes". Same three, no fourth, no drift.
 *   - NO DELIVERY, TRANSIT OR PRODUCTION TIME CLAIM of any kind.
 *   - NO COUPON, DISCOUNT, SECOND UPSELL, REVIEW ASK OR LEAD MAGNET.
 *   - NO PRICE. The customer has already paid.
 *
 * ⭐ THE DOWNLOAD LINK IS NOT COPY AND IS NOT IN THIS FILE. It is
 *    WooCommerce's own signed permission URL, read from the order at send
 *    time. See `bhp_bundle_addon_order_downloads()`.
 *
 * @package brave-hearts-bundle-pricing
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Every customer-facing string for the add-on thank-you email and the
 * add-on-only cart guard.
 *
 * ⭐ RENDER ORDER, and why the array has the shape it has.
 *
 *    The `marketing-growth` drafted body puts the download button after the FIRST
 *    paragraph and three more paragraphs after it. The 1.8.21 template
 *    rendered every paragraph before the button, so loading her copy into
 *    the old shape would have reordered approved prose. The template gained
 *    a `paragraphs_after` loop instead. Reordering an approved email to fit
 *    a template is a silent rewrite of locked copy; adding six lines to a
 *    template is not.
 *
 *      Hi {first name},              <- template, from the order
 *      paragraphs[]                  <- 1 paragraph
 *      download_lead                 <- EMPTY, so no heading renders
 *      download_button x N           <- the signed link
 *      paragraphs_after[]            <- 3 paragraphs
 *      link_note                     <- EMPTY, so nothing renders
 *      (rule)
 *      closing
 *      signoff[] + signoff_tagline
 *      (WooCommerce footer)
 *
 * ⚠ `download_lead` and `link_note` are EMPTY STRINGS ON PURPOSE, not by
 *   oversight. Both held placeholder prose that is not in the approved
 *   draft. Both templates skip an empty value. They are kept as keys, not
 *   deleted, so that if Andrew later wants either line back it is a
 *   one-string change in this file and nothing else.
 *
 * @return array
 */
function bhp_bundle_addon_thankyou_copy() {
	$copy = array(

		/*
		 * ⭐ `APPROVED` as of 1.8.22. Both Andrew confirms closed by his
		 *    MESSAGES 35-36 of 2026-08-04 - see the header for which word
		 *    closed which gate. Do not flip this value, in either
		 *    direction, without a founder word recorded the same way.
		 */
		'status'        => 'APPROVED',
		'source'        => 'Business OS\\WORKING-DRAFTS\\marketing-growth\\DRAFT-2026-08-04-UPSELL-THANKYOU-EMAIL.md (DRIVE mount, 2026-08-04, marketing-growth, session CYCLE143-MKT-UPSELL-EMAIL)',

		/*
		 * ⭐ EMPTY, AND THE KEY STAYS. Both items that lived here closed on
		 *    2026-08-04 (licence: "Classroom ok"; sign-off: "Activity book
		 *    email approved"). The key is kept, not deleted, so that the
		 *    next open confirm is a one-line addition here and
		 *    `bhp_bundle_addon_copy_open_confirms()` keeps its shape. The
		 *    machine-readable list is what stops a QA report describing
		 *    copy with open confirms as final.
		 */
		'open_confirms' => array(),

		'email'         => array(

			/*
			 * The `marketing-growth` Subject A, chosen over B by the `chief-of-staff` direction.
			 * 26 characters. WooCommerce's {order_number} / {order_date} /
			 * {site_title} placeholders are available and deliberately
			 * unused.
			 */
			'subject'          => __( 'Your activity book is here', 'bhp-bundle-pricing' ),

			/*
			 * ⛔ CARRIED, NOT WIRED. The `marketing-growth` preheader (inbox preview
			 *    line), 66 characters.
			 *
			 * ⚠ The preheader mechanism is `bhp_email_preheaders()` in the
			 *   THEME (`inc/transactional-emails.php`). It is a hardcoded
			 *   array keyed by email id with NO filter on it, so a plugin
			 *   cannot register into it. Verified by reading the function
			 *   this session, not assumed. Wiring it needs one line added
			 *   to that theme array:
			 *
			 *     'bhp_addon_thankyou' => __( '<this string>', 'brave-hearts' ),
			 *
			 *   That is a THEME change and this is a PLUGIN build, so it is
			 *   out of scope here and handed to `chief-of-staff` instead. Until it
			 *   lands, this email sends with NO preheader and the inbox
			 *   preview falls back to the first body line. That is a
			 *   cosmetic gap, not a defect, and it is stated rather than
			 *   implied.
			 *
			 * The `marketing-growth` shorter backup, if a theme ever needs one:
			 * "Your download is inside, and it is yours to keep." (48 chars)
			 */
			'preheader'        => __( 'Coloring pages, word searches and mazes from all three adventures.', 'bhp-bundle-pricing' ),

			/*
			 * The <h1> in the email masthead.
			 *
			 * ⭐ THE SUBJECT LINE REUSED VERBATIM, on purpose. The approved
			 *    draft specifies no heading, and inventing one would put
			 *    unapproved words at the top of an approved email. Reusing
			 *    the subject introduces zero new prose and is what
			 *    WooCommerce's own customer emails do.
			 */
			'heading'          => __( 'Your activity book is here', 'bhp-bundle-pricing' ),

			/* Body paragraphs rendered BEFORE the download. */
			'paragraphs'       => array(
				__( 'Thank you for adding The Adventure Activity Book to your order. Your download is ready.', 'bhp-bundle-pricing' ),
			),

			/*
			 * ⭐ EMPTY ON PURPOSE. The approved draft has no heading above
			 *    the button. Both templates skip an empty value.
			 */
			'download_lead'    => '',

			/*
			 * The button label.
			 *
			 * ⚠ NO `%s` ANY MORE, AND THAT IS A DELIBERATE MECHANISM
			 *   CHANGE. The 1.8.21 placeholder was "Download %s" and
			 *   interpolated WooCommerce's stored FILE name. The `marketing-growth`
			 *   approved label names the artefact instead, which is both
			 *   her exact words and safer: a file renamed in the product's
			 *   Downloads panel can no longer rewrite an approved
			 *   customer-facing string. The templates `printf()` this with
			 *   the file name still passed; PHP ignores a surplus argument.
			 */
			'download_button'  => __( 'Download the Activity Book (PDF)', 'bhp-bundle-pricing' ),

			/*
			 * ⭐ 1.8.38 - THE SECOND BUTTON. Andrew's vocabulary-cards ruling
			 *    (RELAYED through the Chief of Staff, ⛔ not witnessed
			 *    first-hand) makes the Vocabulary Card Activity a second free
			 *    giveaway delivered in THIS email, "ALONGSIDE the activity
			 *    book", with the label "Vocabulary Card Activity (printable
			 *    PDF)".
			 *
			 * ⛔ NOT ONE EXISTING STRING IN THIS FILE CHANGED TO ADD IT. The
			 *    subject, the heading, all four paragraphs, the licence
			 *    sentence, the closing, the sign-off, the tagline and both
			 *    cart-guard strings are byte-identical to 1.8.37. The approved
			 *    copy is locked and was not reopened; this is an addition
			 *    beside it, which is exactly what the brief asks for.
			 *
			 * ⛔ NO `%s`, for the same reason `download_button` has none: the
			 *    label names the artefact, so a file renamed in the product's
			 *    Downloads panel can never rewrite a customer-facing string.
			 *    The templates still `printf()` with the file name passed;
			 *    PHP ignores a surplus argument.
			 *
			 * ⛔ IT CLAIMS NOTHING. No price, no savings figure (the cards
			 *    carry no price record, so any figure would be invented), no
			 *    page count, no reading age, no card count, no outcome claim,
			 *    and nothing on the never-invent list.
			 */
			'download_button_vocab' => __( 'Download the Vocabulary Card Activity (printable PDF)', 'bhp-bundle-pricing' ),

			/* Body paragraphs rendered AFTER the download. */
			'paragraphs_after' => array(
				/*
				 * ⛔ The `marketing-growth` sentence ends here. Its clause "and the answer
				 *    key is on the last two pages" is OMITTED - version-
				 *    coupled to v4, pending Andrew's v4/v5 word. See the
				 *    file header and `CYCLE143-MKT-121`.
				 *
				 * "ages 6 to 9" is the approved band and the only one
				 * permitted anywhere in this file.
				 */
				__( 'Inside are coloring pages, word searches and mazes drawn from all three of Charlotte and Henry\'s adventures: the Mariana Trench, Mount Everest and the Amazon. It is made for readers ages 6 to 9.', 'bhp-bundle-pricing' ),

				/*
				 * ⭐ THE LICENCE SENTENCE. Gate G-B, CLOSED 2026-08-04 by
				 *    Andrew's "Classroom ok" and CHANGED by it.
				 *
				 * 1.8.21 read: "Print the pages you like, as many times as
				 * you like. It is yours to keep." 1.8.22 adds the clause
				 * "for your home or your classroom" and nothing else.
				 *
				 * ⛔ WHAT THIS SENTENCE MUST NEVER BECOME: it grants a
				 *    printing permission and states no entitlement beyond
				 *    it. No "school", "district", "site" or "unlimited"
				 *    licence; no redistribution, resale or sharing
				 *    permission; no rights or copyright text; and no
				 *    teacher, classroom or reading-outcome claim, all of
				 *    which are on the never-invent list. The test suite
				 *    asserts the exact sentence, so a broadening cannot
				 *    land without a deliberate test change.
				 */
				__( 'Print the pages you like, as many times as you like, for your home or your classroom. It is yours to keep.', 'bhp-bundle-pricing' ),

				__( 'We hope you enjoy this activity book.', 'bhp-bundle-pricing' ),
			),

			/*
			 * ⭐ EMPTY ON PURPOSE. The 1.8.21 placeholder carried a
			 *    mechanism note here ("This link is tied to your order..."),
			 *    which was true but is not in the approved draft. Both
			 *    templates skip an empty value.
			 */
			'link_note'        => '',

			/*
			 * Last line of the body. `marketing-growth` reuses the shipped E2 pattern
			 * ("reply to this email, it comes to a real person") verbatim,
			 * which is why it reads like the rest of the house voice.
			 */
			'closing'          => __( 'If the download does not open, reply to this email. It comes to a real person and we will sort it out.', 'bhp-bundle-pricing' ),

			/*
			 * ⭐ The sign-off. Gate G-C, CLOSED 2026-08-04 by Andrew's
			 *    "Activity book email approved" on the email as deployed,
			 *    which signs "Andrew". NO STRING CHANGED - the gate closed
			 *    by confirmation, not by edit.
			 *
			 * ⚠ OBSERVED DUPLICATION, disclosed rather than fixed:
			 *   WooCommerce's stored footer option is
			 *   `{site_title}<br />{store_address}`, so "Brave Hearts
			 *   Publishing" renders a SECOND time in the footer, below this
			 *   sign-off. Not corrected here, for two reasons: this is
			 *   approved copy and must not be silently rewritten, and the
			 *   footer is a store-wide WooCommerce option that no agent
			 *   changes without Andrew. Flagged to `chief-of-staff` as cosmetic.
			 */
			'signoff'          => array(
				__( 'Andrew', 'bhp-bundle-pricing' ),
				__( 'Brave Hearts Publishing', 'bhp-bundle-pricing' ),
			),

			/* Rendered in italics under the sign-off. The company brand line. */
			'signoff_tagline'  => __( 'Big Places. Brave Hearts.', 'bhp-bundle-pricing' ),
		),

		'cart_guard'    => array(

			/*
			 * ⛔ THE GUARD MESSAGE. Shown when the cart contains the
			 *    activity book and nothing else. The `marketing-growth` PRIMARY
			 *    recommended string, verbatim.
			 *
			 * ⚠ NO `%s` ANY MORE. The 1.8.21 placeholder interpolated the
			 *   live product title. The approved string NAMES the product
			 *   in Andrew-facing prose instead, so the sentence can no
			 *   longer be rewritten by a product rename.
			 *
			 * ⭐ VERIFIED LIVE, not assumed: staging product 833's title
			 *    reads exactly "The Adventure Activity Book"
			 *    (`wp post get 833 --field=post_title`, staging,
			 *    2026-08-04). The literal and the old interpolation would
			 *    render identically today; the literal cannot drift.
			 */
			'addon_only'         => __( 'The Adventure Activity Book is a companion download, so it needs at least one Charlotte and Henry book in your cart. Add a book and you can check out.', 'bhp-bundle-pricing' ),

			/*
			 * ⭐ NOW IDENTICAL TO `addon_only` BY CONSTRUCTION, and kept
			 *    rather than deleted. It used to be the fallback for "the
			 *    product title would not resolve". With no interpolation
			 *    there is nothing left to fail, so the two are the same
			 *    sentence. The key stays so `bhp_bundle_addon_only_message()`
			 *    keeps its shape and so a future copy that DOES interpolate
			 *    has its fallback slot waiting.
			 */
			'addon_only_generic' => __( 'The Adventure Activity Book is a companion download, so it needs at least one Charlotte and Henry book in your cart. Add a book and you can check out.', 'bhp-bundle-pricing' ),

			/*
			 * The `marketing-growth` shorter alternate, carried but NOT USED. Recorded so
			 * that choosing it later (her gate G-E) is a one-line swap and
			 * nobody has to reopen a Drive document to find the wording:
			 *
			 *   "The Adventure Activity Book goes out with a book order.
			 *    Please add at least one Charlotte and Henry book to your
			 *    cart to check out."
			 */
		),
	);

	/**
	 * Filter the add-on thank-you and cart-guard copy.
	 *
	 * Exists so a copy change can be staged from a one-line snippet during
	 * review without editing the file. It is not the intended long-term
	 * route: the file above is.
	 *
	 * @param array $copy Copy array.
	 */
	return apply_filters( 'bhp_bundle_addon_thankyou_copy', $copy );
}

/**
 * True only while the copy is still the unapproved 1.8.21 placeholder.
 *
 * ⚠ SEMANTICS CHANGED IN THE COPY SWAP, deliberately and stated. It used
 *   to read `'APPROVED' !== $copy['status']`, which would call the loaded
 *   approved copy a placeholder purely because two confirms are open. It
 *   now tests for the placeholder state itself. `APPROVED` and
 *   `APPROVED-PENDING-ANDREW-CONFIRM` both return false;
 *   `bhp_bundle_addon_copy_open_confirms()` is what reports the open
 *   items, and the test suite prints them.
 *
 * It deliberately does NOT gate sending: a delivery email that silently
 * refuses to deliver a file the customer paid for, because a word is not
 * final, is a worse failure than imperfect wording on staging. Production
 * is gated by Andrew's deploy approval, which is the correct gate.
 *
 * @return bool
 */
function bhp_bundle_addon_copy_is_placeholder() {
	$copy = bhp_bundle_addon_thankyou_copy();
	return ! isset( $copy['status'] ) || 'PLACEHOLDER' === $copy['status'];
}

/**
 * The open Andrew confirms on the loaded copy, if any.
 *
 * Empty array means nothing is outstanding. Read by the test suite, which
 * prints one line naming each, so a staging QA report cannot describe copy
 * with open confirms as final.
 *
 * @return array
 */
function bhp_bundle_addon_copy_open_confirms() {
	$copy = bhp_bundle_addon_thankyou_copy();

	if ( empty( $copy['open_confirms'] ) || ! is_array( $copy['open_confirms'] ) ) {
		return array();
	}

	return $copy['open_confirms'];
}
