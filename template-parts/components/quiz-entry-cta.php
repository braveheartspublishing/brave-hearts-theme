<?php
/**
 * Find Your Adventure — sitewide quiz launcher (2026-07-17, modal reuse).
 *
 * Originally rendered a plain link to the canonical /find-your-adventure/
 * page. Andrew's follow-up requirement: the quiz itself must be usable from
 * every eligible page without navigating away, reusing the same shared quiz
 * component and routing rather than a second implementation. This template
 * part now renders both the launcher button and a hidden modal containing
 * template-parts/quiz/audience-quiz.php. The canonical /find-your-adventure/
 * page remains as a standalone destination for anyone who lands on it
 * directly (sitemap, search, a shared link). The small "Open the full quiz
 * page" link that used to sit inside the modal was removed 2026-07-29 --
 * offering the same quiz twice inside the quiz was redundant and pushed the
 * result CTA further down the dialog.
 *
 * Rendered from footer.php, gated by bhp_should_show_quiz_cta() in
 * functions.php (reuses the existing bhp_should_show_any_popup() exclusion
 * set -- cart/checkout/account/order-received/privacy/terms/admin/all 4
 * audience landing templates/thank-you pages -- plus excludes the homepage,
 * which already embeds the full quiz, and the canonical quiz page itself).
 *
 * Analytics: the launcher reuses the theme's existing generic
 * data-bhp-event / data-bhp-impression-event handlers in nav.js for
 * quiz_cta_viewed / quiz_cta_clicked (unchanged event names -- the launcher
 * is a <button> now instead of an <a>, but the attributes and events are
 * identical). Modal-open/close events are pushed directly by
 * assets/js/quiz-modal.js using the same window.dataLayer convention as
 * every other event in this theme -- no new analytics platform, no second
 * dispatch mechanism.
 *
 * `$args['location']` (optional): overrides the auto-detected entry
 * location. Left unset by footer.php's normal call, so the value always
 * reflects the actual page type (blog_archive, blog_post, shop, product,
 * about, information_page) via bhp_get_quiz_entry_location() rather than a
 * flat "footer" placement value.
 *
 * `data-bhp-quiz-autoopen` (2026-07-17 timer/scroll trigger follow-up): a
 * simple true/false flag read by assets/js/quiz-modal.js to decide whether
 * to arm the 9s-timer/40%-scroll auto-open trigger on this render. Computed
 * server-side via bhp_should_autoopen_quiz() so page-type exclusions (e.g.
 * /teachers/, which keeps the manual launcher but not automatic opening)
 * live in one place alongside the theme's other page-eligibility functions,
 * rather than duplicating URL/template checks in JS.
 */
defined('ABSPATH') || exit;

$location = !empty($args['location']) ? sanitize_key($args['location']) : bhp_get_quiz_entry_location();

/**
 * Optional `id` (2026-07-31, quiz consolidation): stamps a stable anchor id on
 * the launcher wrapper.
 *
 * ⚠ NO CALLER PASSES IT AS OF 1.19.270 (2026-08-19,
 *   `CYCLE165-LD-ITERATE-6-PATH-LINE`). footer.php used to pass
 *   'find-your-adventure' on the homepage only, preserving the deep-link
 *   contract left behind by the removed inline homepage quiz section. The
 *   founder has now removed this whole band from the homepage, so there is no
 *   homepage launcher to stamp. The argument is KEPT, not deleted -- it is the
 *   supported way to reinstate a stable anchor if one is ever needed again,
 *   and deleting it would make that a code change rather than an argument.
 */
$cta_id = !empty($args['id']) ? sanitize_html_class($args['id']) : '';
$autoopen_eligible = function_exists('bhp_should_autoopen_quiz') && bhp_should_autoopen_quiz();

$launcher_id = function_exists('wp_unique_id') ? wp_unique_id('bhp-quiz-launcher-') : uniqid('bhp-quiz-launcher-');
$modal_id    = function_exists('wp_unique_id') ? wp_unique_id('bhp-quiz-modal-') : uniqid('bhp-quiz-modal-');
$title_id    = $modal_id . '-title';
?>
<div class="bhp-quiz-cta"<?php echo $cta_id ? ' id="' . esc_attr($cta_id) . '"' : ''; ?> data-bhp-impression-event="quiz_cta_viewed" data-bhp-source="<?php echo esc_attr($location); ?>" data-bhp-entry-location="<?php echo esc_attr($location); ?>">
  <p class="bhp-quiz-cta__text">
    <strong><?php esc_html_e('Not sure which Brave Hearts path fits?', 'brave-hearts'); ?></strong>
    <?php esc_html_e('Two questions will match you with the best next step for your reader, classroom, gift, or program.', 'brave-hearts'); ?>
  </p>
  <button
    type="button"
    class="btn btn-secondary bhp-quiz-cta__button"
    id="<?php echo esc_attr($launcher_id); ?>"
    aria-haspopup="dialog"
    aria-expanded="false"
    aria-controls="<?php echo esc_attr($modal_id); ?>"
    data-bhp-quiz-launcher
    data-bhp-event="quiz_cta_clicked"
    data-bhp-source="<?php echo esc_attr($location); ?>"
    data-bhp-entry-location="<?php echo esc_attr($location); ?>"
  ><?php esc_html_e('Find My Best Next Step', 'brave-hearts'); ?></button>
</div>

<div class="bhp-quiz-modal" id="<?php echo esc_attr($modal_id); ?>" data-bhp-quiz-modal data-bhp-quiz-modal-location="<?php echo esc_attr($location); ?>" data-bhp-quiz-autoopen="<?php echo $autoopen_eligible ? 'true' : 'false'; ?>" hidden>
  <div class="bhp-quiz-modal__backdrop" data-bhp-quiz-modal-backdrop></div>
  <div class="bhp-quiz-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="<?php echo esc_attr($title_id); ?>">
    <button type="button" class="bhp-quiz-modal__close" data-bhp-quiz-modal-close aria-label="<?php esc_attr_e('Close quiz', 'brave-hearts'); ?>">
      <span aria-hidden="true">&times;</span>
    </button>
    <h2 id="<?php echo esc_attr($title_id); ?>" class="screen-reader-text"><?php esc_html_e('Find Your Adventure quiz', 'brave-hearts'); ?></h2>
    <?php get_template_part('template-parts/quiz/audience-quiz', null, ['entry_location' => $location, 'in_modal' => true]); ?>
  </div>
</div>
