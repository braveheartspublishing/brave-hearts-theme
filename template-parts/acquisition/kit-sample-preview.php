<?php
/**
 * Reluctant Reader Adventure Kit — the on-page, gated sample.
 * Theme 1.19.389, 2026-09-06, `CYCLE179-LD-BUILD-389`.
 *
 * ⭐ WHAT IT IS. Four page images from the real Kit, scrollable, rendered
 *    directly above the signup form on `/reluctant-reader-adventure-kit/`.
 *    A parent sees the first four pages before giving an email address, and
 *    the chapter stops mid-sentence exactly where the artefact stops it.
 *
 * ⭐ THE IMAGES SHIP INSIDE THE THEME (`assets/img/kit-sample/`), on purpose.
 *    Nothing is uploaded to the media library, so there is no attachment ID to
 *    drift, no `wp_get_attachment_image()` lookup that can return an empty
 *    string on an environment where the upload never happened, and staging and
 *    production render byte-identical files. ⛔ A media-library dependency is
 *    exactly how the founder photograph broke on staging once already.
 *
 * ⭐ THE SOURCE OF THE IMAGES, and it is named rather than assumed:
 *    `design-creative` (`CYCLE179-DES-KIT-SAMPLE`, 2026-09-06) rendered them
 *    from `BHP-RELUCTANT-READER-ADVENTURE-KIT-v2-CH10.pdf`, md5
 *    `34a343489ff12f869c60ed7ed31a4994`, crop-and-render only, no generated
 *    imagery. That desk's own note records that pages 1 to 4 are
 *    raster-identical in v2, v2.1 and v2.2, so these four are correct for the
 *    kit that is actually live.
 *
 * ⭐ THE KIT THAT IS ACTUALLY LIVE, VERIFIED IN THE SYSTEM (Standing Rules
 *    §9.2), by `lead-developer` on 2026-09-06 over SSH against the PRODUCTION
 *    document root: `bhp_lead_magnet_pdfs['adventure_kit_parent']` resolves to
 *    `.../uploads/2026/07/Reluctant-Reader-Adventure-Kit-1.pdf`, which is
 *    8,944,368 bytes, mtime 2026-09-03 19:26, md5
 *    `e227eea53ec762df4abdb6a09615a730`, `/Count 11` — byte-identical to the
 *    Drive kit of record "Reluctant Reader Adventure Kit v2.2 (Chapter 10,
 *    live 2026-09-03).pdf". **Chapter 10, "The Dive". Eleven pages.**
 *
 * ⛔ THE ALT TEXT IS `design-creative`'s, WITH ONE DELIBERATE DEPARTURE.
 *    That desk's `ALT-TEXT.md` describes page one as carrying the line "A real
 *    chapter from the book. About 10 minutes." **The duration is dropped from
 *    the alt text here.** Andrew retired duration claims from this page on
 *    2026-08-03 (the A6 note beside the signup panel), and
 *    `tests/test-cycle167-kit-page.php` §4c enforces the absence across the
 *    whole file. An alt attribute is customer-facing copy, so it is held to the
 *    same rail. ⛔ Nothing else in the alt text is altered, and no page is
 *    described as containing anything it does not contain.
 *
 * ⛔ NO CLAIM IS MADE HERE THAT THE ARTEFACT DOES NOT SUPPORT. No page count is
 *    promised, no reading time, no outcome, no review, no rating, no scarcity.
 *
 * ⭐ VOICE §9.1: "I"/"my", never "we". No em dash.
 *
 * ⛔ IT MINTS NO FUNNEL STATE. No storage prefix, no analytics prefix, no
 *    popup binding, no form. It is images and two sentences sitting above the
 *    form that was already there.
 *
 * ⚠ THE CONTAINER CARRIES THE BORDER, NOT THE IMAGES. `design-creative`'s
 *   build note: the rendered pages have no border of their own, so a page on a
 *   cream section would bleed into the background. The frame is CSS
 *   (`.parent-landing-sample__frame` in `assets/css/parent-landing.css`).
 */
defined('ABSPATH') || exit;

$bhp_kit_sample_dir = get_stylesheet_directory() . '/assets/img/kit-sample';
$bhp_kit_sample_uri = get_stylesheet_directory_uri() . '/assets/img/kit-sample';

/*
 * ⭐ EACH ENTRY IS RENDERED ONLY IF ITS FILE IS ACTUALLY ON DISK. A theme
 *    deployed without the images shows the heading-less section not at all,
 *    rather than four broken image icons above the one email field this page
 *    exists to fill.
 */
$bhp_kit_sample_pages = [
    [
        'slug'  => 'kit-sample-p01',
        'label' => __('Page one', 'brave-hearts'),
        'alt'   => __('Kit cover on a deep navy ground: the Brave Hearts compass mark, a gold "Free Printable Adventure" badge, the title "The Reluctant Reader Adventure Kit", a line describing it as a real chapter from the book, and the underwater cover of the Mariana Trench book below it.', 'brave-hearts'),
    ],
    [
        'slug'  => 'kit-sample-p02',
        'label' => __('Page two', 'brave-hearts'),
        'alt'   => __('Page two of the kit, a note for parents and grandparents on cream: the headline "If Reading Has Started to Feel Like a Battle, Try Something Different", three numbered tips to let them choose the pace, ask adventure questions, and stop at the cliffhanger, and a dark green box reassuring parents the kit is not a reading assessment.', 'brave-hearts'),
    ],
    [
        'slug'  => 'kit-sample-p03',
        'label' => __('Page three', 'brave-hearts'),
        'alt'   => __('The chapter opener from the book, reproduced exactly as printed and framed in white on the kit\'s cream page: a pencil drawing of a deep-sea submersible being lifted by a crane from a research ship, the heading "Chapter 10, The Dive", and the first lines of the chapter.', 'brave-hearts'),
    ],
    [
        'slug'  => 'kit-sample-p04',
        'label' => __('Page four', 'brave-hearts'),
        'alt'   => __('The chapter continues, reproduced as printed: the submersible swings out over open sea, the countdown runs down to a splash, and the sub starts going down. The page stops mid-sentence.', 'brave-hearts'),
    ],
];

$bhp_kit_sample_render = [];
foreach ($bhp_kit_sample_pages as $bhp_kit_sample_page) {
    $bhp_kit_sample_large = $bhp_kit_sample_dir . '/' . $bhp_kit_sample_page['slug'] . '-1200.jpg';
    $bhp_kit_sample_small = $bhp_kit_sample_dir . '/' . $bhp_kit_sample_page['slug'] . '-600.jpg';
    if (file_exists($bhp_kit_sample_large) && file_exists($bhp_kit_sample_small)) {
        $bhp_kit_sample_render[] = $bhp_kit_sample_page;
    }
}

if (empty($bhp_kit_sample_render)) {
    return;
}
?>
<div class="parent-landing-sample" data-bhp-kit-sample>
  <h3 class="parent-landing-sample__title"><?php esc_html_e('See the first four pages', 'brave-hearts'); ?></h3>
  <p class="parent-landing-sample__lead"><?php esc_html_e('Here are the first four pages. The rest of the chapter arrives by email, with the activity pages.', 'brave-hearts'); ?></p>

  <div class="parent-landing-sample__frame">
    <ul class="parent-landing-sample__strip" tabindex="0" role="list" aria-label="<?php esc_attr_e('The first four pages of the Reluctant Reader Adventure Kit', 'brave-hearts'); ?>">
      <?php foreach ($bhp_kit_sample_render as $bhp_kit_sample_page): ?>
        <li class="parent-landing-sample__item">
          <figure class="parent-landing-sample__figure">
            <img
              class="parent-landing-sample__img"
              src="<?php echo esc_url($bhp_kit_sample_uri . '/' . $bhp_kit_sample_page['slug'] . '-600.jpg'); ?>"
              srcset="<?php echo esc_attr($bhp_kit_sample_uri . '/' . $bhp_kit_sample_page['slug'] . '-600.jpg 600w, ' . $bhp_kit_sample_uri . '/' . $bhp_kit_sample_page['slug'] . '-1200.jpg 1200w'); ?>"
              sizes="(max-width: 640px) 78vw, 260px"
              width="600" height="776"
              loading="lazy" decoding="async"
              alt="<?php echo esc_attr($bhp_kit_sample_page['alt']); ?>">
            <figcaption class="parent-landing-sample__caption"><?php echo esc_html($bhp_kit_sample_page['label']); ?></figcaption>
          </figure>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>

  <p class="parent-landing-sample__continues"><?php esc_html_e('The chapter continues in the kit.', 'brave-hearts'); ?></p>
</div>
