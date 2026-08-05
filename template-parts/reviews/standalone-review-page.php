<?php
/**
 * `/review/<slug>/` — the two-click review page.
 *
 * This is the destination the Mailchimp review-ask emails should link to.
 * Click the button in the email (1), type and submit (2).
 *
 * ⚠ CYCLE142-CX-078 — WHAT IS ACTUALLY ON THIS PAGE. This docblock previously
 *   claimed "Nothing else is on the page: no purchase controls, no upsell, no
 *   lead magnet, no popup." Three of those four were true and measured true
 *   again in staging QA: zero upsell, zero lead magnet, zero popup, zero
 *   add-to-cart controls and zero price elements in the page BODY. The fourth
 *   was wrong. The page renders the standard site chrome — get_header() and
 *   get_footer() below — which is 10 header links including the "GET THE
 *   COMPLETE COLLECTION" purchase CTA, and 24 footer links. A header CTA is a
 *   purchase control, so the sentence overstated the page.
 *
 *   The claim is corrected here rather than the chrome being removed, because
 *   which of the two changes is right is a customer-experience decision and not
 *   a code decision: site chrome on a page an email recipient lands on cold is
 *   defensible, and stripping it is also defensible. Recorded for Andrew as an
 *   open question; the BODY of this template deliberately still contains
 *   nothing but the cover, the title and the form.
 *
 * noindex/nofollow — set in inc/reviews.php via both `wp_robots` and
 * `rank_math/frontend/robots`, because Rank Math overwrites WordPress's.
 *
 * ⛔ NO RATING, REVIEW COUNT OR AGGREGATE IS DISPLAYED OR EMITTED HERE.
 *    Nothing on this page can become structured data.
 */

defined('ABSPATH') || exit;

$key = bhp_review_current_key();

if (!$key || !bhp_review_target_id($key)) {
    // Should be unreachable — the route only matches registered slugs — but a
    // template that renders a form for no book is worse than a 404.
    global $wp_query;
    $wp_query->set_404();
    status_header(404);
    get_template_part('404');
    return;
}

$target     = bhp_review_target_id($key);
$title      = bhp_review_book_title($key);
$full_title = bhp_review_book_full_title($key);
$submitted  = bhp_review_just_submitted();
$cover      = has_post_thumbnail($target)
    ? get_the_post_thumbnail($target, 'medium', ['class' => 'bhp-review-page__cover-img', 'alt' => sprintf(
        /* translators: %s: full book title. */
        __('Cover of %s', 'brave-hearts'),
        $full_title
    )])
    : '';

get_header();
?>
<?php /* NOT a <main id="main"> element. header.php already opens
       `<main class="site-main" id="main">` on every page, and duplicating it
       here produced a duplicate id="main" AND nested <main> landmarks —
       caught by counting duplicate ids in the live DOM at both viewports,
       not by reading the template. */ ?>
<div class="bhp-review-page">
    <div class="bhp-review-page__inner">

        <?php if ($cover) : ?>
            <div class="bhp-review-page__cover"><?php echo $cover; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_the_post_thumbnail() output is already escaped. ?></div>
        <?php endif; ?>

        <p class="bhp-review-page__eyebrow"><?php esc_html_e('Adventures of Charlotte and Henry', 'brave-hearts'); ?></p>
        <h1 class="bhp-review-page__title"><?php echo esc_html($title); ?></h1>

        <?php if ($submitted) : ?>

            <div id="bhp-review-thanks" class="bhp-review-thanks bhp-review-thanks--page" role="status" tabindex="-1">
                <h2 class="bhp-review-thanks__heading"><?php esc_html_e('Thank you — your review has been sent.', 'brave-hearts'); ?></h2>
                <p><?php esc_html_e('It is held until it has been read, so it will not appear on the site straight away. Nothing is edited: reviews are published as written, or not published.', 'brave-hearts'); ?></p>
                <p class="bhp-review-page__back">
                    <a href="<?php echo esc_url(get_permalink($target)); ?>">
                        <?php
                        echo esc_html(sprintf(
                            /* translators: %s: short book title. */
                            __('Back to %s', 'brave-hearts'),
                            $title
                        ));
                        ?>
                    </a>
                </p>
            </div>

        <?php else : ?>

            <?php
            echo bhp_review_render_form($key, 'standalone'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside the template part.
            ?>

        <?php endif; ?>

    </div>
</div>
<?php
get_footer();
