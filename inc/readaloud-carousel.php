<?php
/**
 * Brave Hearts — the read-aloud photo carousel's data and assets.
 *
 * Andrew's carrier item 497: "I want a large carousel gallery on the read aloud
 * page and I want you to add the read aloud pictures from last year too."
 * ⛔ RELAYED THROUGH GANDALF, NOT WITNESSED BY THIS FILE'S AUTHOR.
 *
 * ---------------------------------------------------------------------------
 * ⛔ WHY THE ARCHIVE PHOTOGRAPHS ARE IN CODE AND NOT IN THE VISIT REGISTRY
 * ---------------------------------------------------------------------------
 *
 * The three Adams photographs come from `bhp_author_visits_gallery_photos()`,
 * i.e. from the `bhp_school_visit_notes` OPTION, and they keep coming from
 * there — their alt text is the founder-published string and it is read live,
 * never copied. Adding a photograph there means adding a VISIT RECORD, and a
 * visit record needs a school and a date.
 *
 * ⛔ THE SCHOOL OF THE MAY 2026 EVENT IS NOT KNOWN TO THIS BUILD. The folder is
 *    flat, the filenames are camera serials, and the EXIF carries a timestamp
 *    and a camera model and nothing else. Inventing a school name to satisfy a
 *    registry shape would be exactly the never-invent failure, so the archive
 *    photographs are a THEME-ASSET LIST with a generic, truthful caption
 *    instead. When Andrew names the school, moving them into the registry is a
 *    data change and this list shrinks to nothing.
 *
 * ⛔ AND A SECOND REASON, which is the same one 1.19.329 gave for the hero: a
 *    registry row would have to be written as an OPTION on each environment
 *    separately, so staging and production would diverge on data rather than on
 *    code. A theme asset ships with the theme.
 *
 * ---------------------------------------------------------------------------
 * ⚠️⚠️ THE ARCHIVE SET IS BLOCKED BY AN OPEN CONSENT FINDING. READ THIS.
 * ---------------------------------------------------------------------------
 *
 * `CYCLE141-CX-48` (commerce-cx, 2026-08-03) names THESE EXACT SOURCE FILES —
 * "three classroom read-aloud photographs (2026-05-11) ... with many
 * identifiable children ... NOT installed, NOT uploaded, NOT cropped. Hard
 * consent gate." It is cited as still holding in
 * `docs/RELEASES/WAVE_E_UNIFORM_FUNNEL_LEXILE_VENDOR_1_19_146.md` §8, and it
 * was never promoted into `04-DECISIONS-REQUIRED-REGISTER.md`.
 *
 * ⛔ THIS BUILD DOES NOT RESOLVE IT AND MUST NOT BE READ AS RESOLVING IT.
 *    Andrew's carrier item 497 asks for these photographs and the three Adams
 *    photographs he published himself show dozens of identifiable children, so
 *    his practice and this finding point opposite ways. Standing Rules §7:
 *    record both, resolve neither. It is escalated in the deploy record as a
 *    ⛔ PRODUCTION BLOCKER on the archive set only.
 *
 * ⭐ REMOVING THE ARCHIVE SET IS ONE EDIT: empty the array in
 *    `bhp_readaloud_archive_photos()`. The carousel then holds the three Adams
 *    photographs and nothing else changes — no markup, no CSS, no test that
 *    was not written to survive it.
 */

defined( 'ABSPATH' ) || exit;

/**
 * The archive photographs — earlier school events, newest first.
 *
 * ⛔ CAPTIONS ARE WHAT IS KNOWN, NOT WHAT WOULD READ BEST. The month and year
 *    are the file's own EXIF `DateTimeOriginal` (2026-05-11, iPhone 14 Plus).
 *    The school is NOT known, so no school is named. See the header.
 *
 * ⛔ AND THE VERB IS "VISIT", NOT "READ-ALOUD", AND THAT IS DELIBERATE. In all
 *    three frames Andrew is standing and talking beside a projected slide. He
 *    is not shown reading from a book in any of them. The section they sit in
 *    is headed "School read-alouds"; the CAPTION says only what the photograph
 *    shows. If he confirms the day was a read-aloud, the caption is one string.
 *
 * ⛔ ALT TEXT CARRIES NO REACTION, OUTCOME OR RESULT CLAIM, names no child and
 *    names no staff member — Standing Rules §3. The suite asserts all three.
 *
 * @return array<int,array<string,mixed>>
 */
function bhp_readaloud_archive_photos() {
	$photos = array(
		array(
			'file'    => 'school-visit-2026-05-welcome.jpg',
			'alt'     => 'Andrew Signore stands at the front of a school classroom beside a projected slide reading Welcome Andrew Signore. Children sit at round tables, most of them photographed from behind.',
			'w'       => 1200,
			'h'       => 675,
			'caption' => 'A school visit, May 2026',
		),
		array(
			'file'    => 'school-visit-2026-05-slides.jpg',
			'alt'     => 'Andrew Signore gestures toward a photograph projected on a classroom screen while a class of children sits at round tables watching, most of them photographed from behind.',
			'w'       => 1200,
			'h'       => 675,
			'caption' => 'A school visit, May 2026',
		),
		array(
			'file'    => 'school-visit-2026-05-classroom.jpg',
			'alt'     => 'A wide view of a school classroom during an author visit. Andrew Signore stands at the front beside a projected screen, with children seated around several round tables, most of them photographed from behind.',
			'w'       => 1200,
			'h'       => 675,
			'caption' => 'A school visit, May 2026',
		),
	);

	/**
	 * Filter the archive photographs.
	 *
	 * The route by which this list is emptied, extended or replaced without
	 * editing this file — and the route a test uses to prove the carousel is
	 * correct with zero archive photographs in it.
	 *
	 * @param array $photos Archive rows.
	 */
	return apply_filters( 'bhp_readaloud_archive_photos', $photos );
}

/**
 * Every photograph the carousel renders, NEWEST FIRST.
 *
 * ⭐ ORDER IS THE BRIEF'S: the registry's own rows first — and the registry is
 *    already newest-first, asserted by `test-cycle169-visits-trust-gallery` —
 *    then the archive list. The Adams three keep the ORDER THEY ALREADY HAVE
 *    on the page (group, reading, class); reordering founder-approved
 *    photographs was not asked for and is not done here.
 *
 * ⛔ THE REGISTRY ROWS ARE PASSED THROUGH UNTOUCHED. Their `alt` is the string
 *    Andrew published, read live from the option on every request. Nothing here
 *    rewrites it, shortens it or falls back to a generated one.
 *
 * @return array<int,array<string,mixed>>
 */
function bhp_readaloud_carousel_photos() {
	$out = array();

	$registry = function_exists( 'bhp_author_visits_gallery_photos' ) ? bhp_author_visits_gallery_photos() : array();
	foreach ( $registry as $row ) {
		if ( empty( $row['file'] ) || empty( $row['alt'] ) ) {
			continue; // The same gate the shipped gallery applies. An undescribed photograph does not render.
		}
		$school  = isset( $row['school'] ) ? (string) $row['school'] : '';
		$display = isset( $row['date_display'] ) ? (string) $row['date_display'] : '';
		$out[]   = array(
			'file'    => (string) $row['file'],
			'alt'     => (string) $row['alt'],
			'w'       => isset( $row['w'] ) ? (int) $row['w'] : 0,
			'h'       => isset( $row['h'] ) ? (int) $row['h'] : 0,
			/* Identical composition to the shipped gallery's figcaption, so the
			   caption a visitor reads does not change wording with the layout. */
			'caption' => trim( $school . ( '' !== $display ? ', ' . $display : '' ) ),
		);
	}

	foreach ( bhp_readaloud_archive_photos() as $row ) {
		if ( empty( $row['file'] ) || empty( $row['alt'] ) ) {
			continue;
		}
		$out[] = array(
			'file'    => (string) $row['file'],
			'alt'     => (string) $row['alt'],
			'w'       => isset( $row['w'] ) ? (int) $row['w'] : 0,
			'h'       => isset( $row['h'] ) ? (int) $row['h'] : 0,
			'caption' => isset( $row['caption'] ) ? (string) $row['caption'] : '',
		);
	}

	return $out;
}

/**
 * Enqueue the carousel script, on the one template that renders it.
 *
 * ⛔ SCOPED, so no other page pays for it. The script's own guard
 *    (`[data-bhp-photo-carousel]`) means a missed enqueue degrades to "the
 *    arrows and dots never appear and the rail still swipes and scrolls",
 *    never to a JavaScript error — which is the same failure posture
 *    `bhp_enqueue_collection_band_assets()` documents for the collection band.
 */
function bhp_enqueue_readaloud_carousel_assets() {
	if ( ! is_page_template( 'page-school-read-alouds.php' ) ) {
		return;
	}
	$theme_version = wp_get_theme()->get( 'Version' );
	wp_enqueue_script(
		'bhp-photo-carousel',
		get_template_directory_uri() . '/assets/js/photo-carousel.js',
		array(),
		$theme_version,
		true
	);
}
add_action( 'wp_enqueue_scripts', 'bhp_enqueue_readaloud_carousel_assets' );
