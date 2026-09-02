<?php
/**
 * Brave Hearts Bundle Pricing - THE VOCABULARY CARD ACTIVITY, THE SECOND
 * FREE GIVEAWAY.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * WHAT THIS IS
 * ═══════════════════════════════════════════════════════════════════════
 *
 * Andrew Signore, 2026-08-09/10, verbatim (⛔ RELAYED through the Chief of
 * Staff in the build brief and NOT witnessed first-hand by the agent that
 * wrote this file). The ruling, as carried:
 *
 *   the Vocabulary Cards PDF is approved and becomes the SECOND free
 *   giveaway: (1) "FREE Vocabulary Card Activity" joins the FREE bullet
 *   lists (bold, own line, ALL-CAPS FREE, per the standing format) on ALL
 *   collection and funnel pages; (2) the PDF is delivered in the purchase
 *   thank-you email ALONGSIDE the activity book - same grant mechanics as
 *   the activity book (any-book purchase).
 *
 * ⚠ "SAME GRANT MECHANICS AS THE ACTIVITY BOOK" IS AN INTERPRETATION THE
 *   BRIEF STATES AS SUCH: it is recorded there as the `chief-of-staff` recorded
 *   interpretation of 'along with the activity book'". It is implemented
 *   here exactly as briefed and is flagged rather than presented as
 *   Andrew's own words.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * ⭐⭐ HOW IT IS DELIVERED, AND WHY IT IS NOT A SECOND PRODUCT
 * ═══════════════════════════════════════════════════════════════════════
 *
 * The activity book already rides on `BHP-ACTIVITY-BOOK-01`, which the
 * cart grants at $0.00 on any cart holding at least one adventure
 * (`addon-free-with-collection.php`). WooCommerce then grants a real,
 * order-scoped download permission on completion, and
 * `WC_Order::get_downloadable_items()` hands back the signed URL that
 * `addon-thankyou-email.php` prints.
 *
 * The vocabulary cards join THAT product as a SECOND downloadable FILE.
 * Consequences, each one checked against WooCommerce 10.9.1 source read
 * on the running store rather than assumed:
 *
 *   - `wc_downloadable_product_permissions()` loops
 *     `array_keys( $product->get_downloads() )` and grants one permission
 *     per file (`wc-order-functions.php:494`). Two files, two permissions,
 *     one order, no second product and no second grant rule.
 *   - `WC_Order_Item_Product::get_item_downloads()` builds each row's
 *     name, id and signed `download_url` from
 *     `$product->get_file( $download_id )` plus the permission row
 *     (`class-wc-order-item-product.php:445`). The link is core's, signed
 *     with the order key and the customer's email hash.
 *   - The order-received page and WooCommerce's own completed-order email
 *     render both rows through core's `order/order-downloads.php` with no
 *     template override.
 *
 * ⛔⛔ AND YET NO PRODUCT RECORD IS TOUCHED. The second file is injected at
 *     READ time through `woocommerce_product_get_downloads`, the ordinary
 *     `WC_Data::get_prop()` filter for this property. It is the exact
 *     class of intervention `addon-free-with-collection.php` already makes
 *     for the cart-line price: real to every reader, persisted nowhere.
 *
 *     This file calls no `update_post_meta()`, no `update_option()`, no
 *     `$product->save()` and no WooCommerce settings writer. Changing a
 *     product record is an Andrew gate and is NOT what this does.
 *
 * ⛔ THE ADMIN SAVE PATH IS DEFENDED, NOT ASSUMED SAFE. A filter on a read
 *    accessor is visible to the product-data metabox too, and a save from
 *    that screen would otherwise persist the injected row into the product
 *    record - turning a code-side injection into exactly the record
 *    mutation this design exists to avoid. Two defences, either sufficient:
 *      1. the injection is skipped while the ADD-ON PRODUCT ITSELF is the
 *         post being edited at `post.php` / `post-new.php` (and only then,
 *         so an order status change from wp-admin still grants normally);
 *      2. `woocommerce_admin_process_product_object` strips our file from
 *         the object before every admin save, unconditionally.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * ⭐ THE FILE IS PER-ENVIRONMENT AND IS NEVER NAMED BY AN ABSOLUTE PATH
 * ═══════════════════════════════════════════════════════════════════════
 *
 * The activity book's own file lives outside the web root, in a private
 * directory, and that directory is DIFFERENT on staging and production.
 * Rather than hardcode either, or add an option that has to be seeded in
 * the right order, this file reads the directory off the activity book's
 * existing download and puts the vocabulary cards beside it. One artefact
 * per environment, uploaded the same way, discovered rather than
 * configured.
 *
 * ✅ IT FAILS CLOSED, AND THAT IS THE PRODUCTION ORDERING GUARANTEE. With
 *    no `BHP-ACTIVITY-BOOK-01`, no existing download to locate the
 *    directory from, or no vocabulary-cards PDF on disk, every function
 *    here returns early: no download is injected, no permission is
 *    granted, no email button is added and every "FREE Vocabulary Card
 *    Activity" bullet disappears with no copy edit and no deploy. An
 *    environment that has the code but not the asset behaves byte-for-byte
 *    like one that has neither.
 *
 * @package brave-hearts-bundle-pricing
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The file name the vocabulary-cards PDF is uploaded under, on every
 * environment.
 *
 * ⚠ Lower case and hyphenated, matching `adventure-activity-book-v4.pdf`
 *   which sits in the same directory. The version suffix is part of the
 *   name on purpose: a v2 is a new file next to the old one, never an
 *   overwrite, so an already-granted permission can never start serving
 *   different content than the customer was promised.
 *
 * @return string
 */
function bhp_bundle_vocab_filename() {
	/**
	 * Filter the vocabulary-cards file name.
	 *
	 * @param string $filename File name, no path.
	 */
	return (string) apply_filters( 'bhp_bundle_vocab_filename', 'brave-hearts-vocabulary-cards-v1.pdf' );
}

/**
 * Is the second giveaway switched on at all?
 *
 * Filterable so it can be withdrawn without a code change, the same shape
 * as `bhp_bundle_addon_free_enabled()`.
 *
 * @return bool
 */
function bhp_bundle_vocab_enabled() {
	/**
	 * Switch the vocabulary-cards giveaway off.
	 *
	 * @param bool $enabled Whether the vocabulary cards are given away.
	 */
	return (bool) apply_filters( 'bhp_bundle_vocab_enabled', true );
}

/**
 * The RAW, unfiltered downloads already on a product.
 *
 * ⛔ `'edit'` CONTEXT IS LOAD-BEARING AND IS NOT A STYLE CHOICE.
 *    `WC_Data::get_prop()` applies `woocommerce_product_get_downloads`
 *    only in `'view'` context. Reading in `'view'` from inside that very
 *    filter would re-enter this file. `'edit'` returns the stored value,
 *    which is what "where does the real artefact live" needs anyway.
 *
 * @param WC_Product|null $product Product.
 * @return WC_Product_Download[]
 */
function bhp_bundle_vocab_raw_downloads( $product ) {
	if ( ! $product instanceof WC_Product ) {
		return array();
	}
	$downloads = $product->get_downloads( 'edit' );
	return is_array( $downloads ) ? $downloads : array();
}

/**
 * Where the vocabulary-cards PDF would live on THIS environment, derived
 * from the activity book's own download.
 *
 * ⛔ ONLY AN ABSOLUTE LOCAL PATH IS ACCEPTED as the anchor. The activity
 *    book is stored as a real filesystem path outside the web root, which
 *    is what makes it protected in the first place. If a future artefact
 *    is ever stored as a URL instead, this returns '' rather than
 *    inventing a sibling URL and promising a file nobody has uploaded.
 *
 * @param WC_Product|null $product Product to derive from.
 * @return string Absolute path, or '' when it cannot be derived.
 */
function bhp_bundle_vocab_path_for( $product ) {
	foreach ( bhp_bundle_vocab_raw_downloads( $product ) as $download ) {
		if ( ! is_object( $download ) || ! is_callable( array( $download, 'get_file' ) ) ) {
			continue;
		}
		$file = (string) $download->get_file();
		if ( '' === $file ) {
			continue;
		}
		// A URL, a shortcode or a relative path is not an anchor we can use.
		if ( 0 !== strpos( $file, '/' ) ) {
			continue;
		}
		$dir = dirname( $file );
		if ( '' === $dir || '.' === $dir ) {
			continue;
		}
		return $dir . '/' . bhp_bundle_vocab_filename();
	}

	return '';
}

/**
 * The vocabulary-cards path for the live add-on product, or '' when it
 * cannot be derived or the artefact is not on disk.
 *
 * Request-scoped static: this is asked once per bullet list, once per
 * product read and once per email, and `file_exists()` is a stat call.
 *
 * @return string
 */
function bhp_bundle_vocab_file() {
	static $resolved = null;

	/*
	 * ⛔ THE KILL SWITCH IS TESTED BEFORE THE CACHE, ON PURPOSE. A static
	 *    that short-circuits `bhp_bundle_vocab_enabled()` would make the
	 *    filter unable to withdraw the offer once anything had already
	 *    asked - including in a test process, and including in the one
	 *    request where somebody most wants it off. Only the PATH LOOKUP is
	 *    cached; the switch is read every time.
	 */
	if ( ! bhp_bundle_vocab_enabled() ) {
		return '';
	}

	if ( null !== $resolved ) {
		return $resolved;
	}
	$resolved = '';

	if ( ! function_exists( 'bhp_bundle_addon_product_ids' ) || ! function_exists( 'wc_get_product' ) ) {
		return $resolved;
	}

	$ids = bhp_bundle_addon_product_ids();
	if ( empty( $ids ) ) {
		return $resolved;
	}

	$product = wc_get_product( $ids[0] );
	if ( ! $product instanceof WC_Product ) {
		return $resolved;
	}

	$path = bhp_bundle_vocab_path_for( $product );
	if ( '' === $path || ! @file_exists( $path ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- an open_basedir restriction must read as "no artefact", never as a warning in a customer's page.
		return $resolved;
	}

	$resolved = $path;
	return $resolved;
}

/**
 * Is the giveaway actually deliverable on this environment right now?
 *
 * ⭐ THIS IS THE FUNCTION EVERY MESSAGING SURFACE MUST GATE ON, in this
 *    plugin and in the theme. It is the vocabulary-cards counterpart of
 *    `bhp_bundle_addon_free_with_collection()`, and it is deliberately
 *    STRICTER than "the PDF is on disk": the cards ride on the activity
 *    book's grant, so a page may only promise them while the activity
 *    book itself is live. If the add-on product stops resolving, both
 *    bullets disappear together, because both promises fail together.
 *
 * @return bool
 */
function bhp_bundle_vocab_cards_live() {
	if ( ! function_exists( 'bhp_bundle_addon_free_with_collection' ) ) {
		return false;
	}
	if ( ! bhp_bundle_addon_free_with_collection() ) {
		return false;
	}
	return '' !== bhp_bundle_vocab_file();
}

/**
 * The download id WooCommerce will key this file by.
 *
 * `md5()` of the file, which is WooCommerce's own convention for the
 * downloads array key (`WC_Product_Download::set_id()` is fed the same
 * shape by the product data store). Deterministic, so a permission row
 * granted today still matches the injected file tomorrow.
 *
 * @return string 32-char hash, or '' when there is no file.
 */
function bhp_bundle_vocab_download_id() {
	$file = bhp_bundle_vocab_file();
	return '' === $file ? '' : md5( $file );
}

/**
 * The customer-facing NAME of the file.
 *
 * ⭐ THIS ONE STRING DRIVES THREE SURFACES: the order-received downloads
 *    table, WooCommerce's own completed-order email downloads section, and
 *    the label resolution in the add-on thank-you email. It is the exact
 *    label the brief specifies.
 *
 * ⛔ NO EM DASH, no en dash, no price, no page count, no reading age, no
 *    outcome claim, and nothing on the never-invent list. It names an
 *    artefact and its format, and claims nothing else.
 *
 * @return string
 */
function bhp_bundle_vocab_download_name() {
	return __( 'Vocabulary Card Activity (printable PDF)', 'bhp-bundle-pricing' );
}

/**
 * The offer as ONE bullet line, for the FREE bullet lists.
 *
 * ⛔ NO DOLLAR ANCHOR, DELIBERATELY, AND IT IS THE BRIEF'S OWN WORD. The
 *    activity book's bullet carries "a $5.00 savings" because Andrew asked
 *    for that figure and WooCommerce holds a real $5.00 record behind it.
 *    The vocabulary cards have no price record, so any figure here would
 *    be invented. The line therefore says what is true and stops.
 *
 * ⛔ "FREE" IS UPPERCASE IN THE STRING ITSELF, never via `text-transform`,
 *    for the reason already recorded on the other two lines: a CSS
 *    transform leaves the accessible name, the plain-text fallback and any
 *    copy audit reading "Free". The bold and the bullet are the caller's
 *    markup; the caps and the wording are the string's.
 *
 * @return string
 */
function bhp_bundle_vocab_free_offer_line() {
	return __( 'FREE Vocabulary Card Activity', 'bhp-bundle-pricing' );
}

/* ═══════════════════════════════════════════════════════════════════════
 * 1 · THE INJECTION.
 * ═══════════════════════════════════════════════════════════════════════ */

add_filter( 'woocommerce_product_get_downloads', 'bhp_bundle_vocab_inject_download', 10, 2 );
add_filter( 'woocommerce_product_variation_get_downloads', 'bhp_bundle_vocab_inject_download', 10, 2 );

/**
 * Append the vocabulary cards to the add-on product's downloadable files.
 *
 * @param WC_Product_Download[]|mixed $downloads Stored downloads.
 * @param WC_Product|null             $product   Product being read.
 * @return array
 */
function bhp_bundle_vocab_inject_download( $downloads, $product = null ) {
	if ( ! is_array( $downloads ) ) {
		return $downloads;
	}
	if ( ! $product instanceof WC_Product ) {
		return $downloads;
	}
	if ( ! function_exists( 'bhp_bundle_is_addon_item' ) ) {
		return $downloads;
	}

	$product_id = (int) $product->get_id();
	if ( ! $product_id || ! bhp_bundle_is_addon_item( $product_id, $product_id ) ) {
		return $downloads;
	}
	if ( bhp_bundle_vocab_editing_addon_product( $product_id ) ) {
		return $downloads;
	}

	$file = bhp_bundle_vocab_file();
	if ( '' === $file ) {
		return $downloads;
	}

	$id = md5( $file );
	if ( isset( $downloads[ $id ] ) ) {
		return $downloads; // Already real on the record. Nothing to add.
	}
	if ( ! class_exists( 'WC_Product_Download' ) ) {
		return $downloads;
	}

	$download = new WC_Product_Download();
	$download->set_id( $id );
	$download->set_name( bhp_bundle_vocab_download_name() );
	$download->set_file( $file );

	$downloads[ $id ] = $download;

	return $downloads;
}

/**
 * Are we rendering the add-on product's own edit screen right now?
 *
 * ⛔ NARROW ON PURPOSE. A blanket `is_admin()` skip would break the grant,
 *    because completing an order from wp-admin runs
 *    `wc_downloadable_product_permissions()` in an admin request. This
 *    matches ONLY the classic post editor showing THIS product, which is
 *    the single screen whose save could persist the injection.
 *
 * @param int $product_id The add-on product id.
 * @return bool
 */
function bhp_bundle_vocab_editing_addon_product( $product_id ) {
	if ( ! is_admin() || wp_doing_ajax() ) {
		return false;
	}
	if ( ! isset( $GLOBALS['pagenow'] ) || ! in_array( $GLOBALS['pagenow'], array( 'post.php', 'post-new.php' ), true ) ) {
		return false;
	}

	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- reading which post is on screen, not acting on input.
	$editing = 0;
	if ( isset( $_GET['post'] ) ) {
		$editing = (int) $_GET['post'];
	} elseif ( isset( $_POST['post_ID'] ) ) {
		$editing = (int) $_POST['post_ID'];
	}
	// phpcs:enable WordPress.Security.NonceVerification.Recommended

	return $editing > 0 && $editing === (int) $product_id;
}

/* ═══════════════════════════════════════════════════════════════════════
 * 2 · THE SAVE GUARD. THE INJECTION CAN NEVER BECOME A RECORD.
 * ═══════════════════════════════════════════════════════════════════════ */

add_action( 'woocommerce_admin_process_product_object', 'bhp_bundle_vocab_strip_before_save', 5 );

/**
 * Strip our injected file from a product about to be saved in wp-admin.
 *
 * Runs before `$product->save()`. It removes ONLY a download whose file is
 * exactly the path this file injects, so a genuine artefact that Andrew
 * later attaches by hand under the same name is left alone in every other
 * respect - and once it IS on the record, the injection stops (see the
 * `isset()` guard above) and this guard has nothing left to remove.
 *
 * @param WC_Product $product Product being saved.
 * @return void
 */
function bhp_bundle_vocab_strip_before_save( $product ) {
	if ( ! $product instanceof WC_Product ) {
		return;
	}
	$file = bhp_bundle_vocab_file();
	if ( '' === $file ) {
		return;
	}

	$downloads = $product->get_downloads( 'edit' );
	if ( ! is_array( $downloads ) || empty( $downloads ) ) {
		return;
	}

	$kept    = array();
	$removed = false;
	foreach ( $downloads as $key => $download ) {
		if ( is_object( $download ) && is_callable( array( $download, 'get_file' ) ) && $file === (string) $download->get_file() ) {
			$removed = true;
			continue;
		}
		$kept[ $key ] = $download;
	}

	if ( $removed ) {
		$product->set_downloads( $kept );
	}
}

/* ═══════════════════════════════════════════════════════════════════════
 * 3 · TELLING THE TWO DOWNLOADS APART, FOR THE EMAIL.
 * ═══════════════════════════════════════════════════════════════════════ */

/**
 * Is this `get_downloadable_items()` row the vocabulary cards?
 *
 * Matched on the download id, which is the file hash, never on the display
 * name - a name is copy and copy changes.
 *
 * @param array|mixed $row Row from `WC_Order::get_downloadable_items()`.
 * @return bool
 */
function bhp_bundle_vocab_is_download_row( $row ) {
	if ( ! is_array( $row ) || empty( $row['download_id'] ) ) {
		return false;
	}
	$id = bhp_bundle_vocab_download_id();
	return '' !== $id && $id === (string) $row['download_id'];
}
