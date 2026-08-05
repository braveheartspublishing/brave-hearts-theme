<?php
/**
 * The full-width reader-review band on the product page: approved reviews
 * first, then the write-a-review form.
 *
 * Carries BOTH anchors: `#reviews` (what the Mailchimp review-ask emails
 * already link to, and which did not exist anywhere on the site before this
 * release) and `#write-review` (the in-page invitation beneath the Kirkus
 * block).
 *
 * ⛔ NOTHING HERE INVENTS A RATING. If no review has been approved, the list
 *    is absent — not an empty five-star row, not a "be the first to rate 5
 *    stars" prompt, not a placeholder average. The only numbers shown are
 *    counted from approved reviews that real people wrote.
 *
 * @var array $args {
 *     @type string $key     adventure key.
 *     @type string $context 'product' | 'standalone'.
 * }
 */

defined('ABSPATH') || exit;

$key     = isset($args['key']) ? $args['key'] : '';
$context = isset($args['context']) ? $args['context'] : 'product';
$target  = bhp_review_target_id($key);

if (!$key || !$target) {
    return;
}

$title     = bhp_review_book_title($key);
$reviews   = bhp_review_get_approved($key, 10);
$count     = bhp_review_count($key);
$average   = bhp_review_average($key);
$submitted = bhp_review_just_submitted();
$show_verified_label = 'yes' === get_option('woocommerce_review_rating_verification_label', 'yes');
?>
<section id="reviews" class="bhp-review-section" aria-labelledby="bhp-review-section-heading">
    <div class="container">

        <header class="bhp-review-section__header">
            <h2 id="bhp-review-section-heading" class="text-section-title">
                <?php
                if ($count > 0) {
                    echo esc_html(sprintf(
                        /* translators: %s: short book title. */
                        __('Reader Reviews of %s', 'brave-hearts'),
                        $title
                    ));
                } else {
                    echo esc_html(bhp_review_invitation_heading($key));
                }
                ?>
            </h2>

            <?php if ($count > 0 && $average > 0) : ?>
                <p class="bhp-review-section__summary">
                    <?php echo bhp_review_stars_html($average); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in bhp_review_stars_html(). ?>
                    <span class="bhp-review-section__average">
                        <?php
                        echo esc_html(sprintf(
                            /* translators: 1: average rating to one decimal place, 2: number of reviews. */
                            _n('%1$s out of 5 — from %2$s reader review', '%1$s out of 5 — from %2$s reader reviews', $count, 'brave-hearts'),
                            number_format_i18n($average, 1),
                            number_format_i18n($count)
                        ));
                        ?>
                    </span>
                </p>
            <?php else : ?>
                <p class="bhp-review-section__summary bhp-review-section__summary--empty">
                    <?php
                    echo esc_html(sprintf(
                        /* translators: %s: short book title. */
                        __('No reader has reviewed %s here yet. If you have read it, yours would be the first.', 'brave-hearts'),
                        $title
                    ));
                    ?>
                </p>
            <?php endif; ?>
        </header>

        <?php if ($submitted) : ?>
            <div id="bhp-review-thanks" class="bhp-review-thanks" role="status" tabindex="-1">
                <h3 class="bhp-review-thanks__heading"><?php esc_html_e('Thank you — your review has been sent.', 'brave-hearts'); ?></h3>
                <p><?php esc_html_e('It is held until it has been read, so it will not appear on this page straight away. Nothing is edited: reviews are published as written, or not published.', 'brave-hearts'); ?></p>
            </div>
        <?php endif; ?>

        <?php if ($reviews) : ?>
            <ol class="bhp-review-list">
                <?php foreach ($reviews as $review) :
                    $rating   = (int) get_comment_meta($review->comment_ID, 'rating', true);
                    $verified = function_exists('wc_review_is_from_verified_owner')
                        && wc_review_is_from_verified_owner($review->comment_ID);
                    ?>
                    <li class="bhp-review-card" id="comment-<?php echo esc_attr($review->comment_ID); ?>">
                        <?php if ($rating) : ?>
                            <?php echo bhp_review_stars_html($rating); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in bhp_review_stars_html(). ?>
                        <?php endif; ?>

                        <div class="bhp-review-card__body">
                            <?php echo wp_kses_post(wpautop(get_comment_text($review->comment_ID))); ?>
                        </div>

                        <p class="bhp-review-card__meta">
                            <span class="bhp-review-card__author"><?php echo esc_html(get_comment_author($review->comment_ID)); ?></span>
                            <span class="bhp-review-card__sep" aria-hidden="true">&middot;</span>
                            <time datetime="<?php echo esc_attr(get_comment_date('c', $review->comment_ID)); ?>">
                                <?php echo esc_html(get_comment_date('', $review->comment_ID)); ?>
                            </time>
                            <?php if ($verified && $show_verified_label) : ?>
                                <span class="bhp-review-card__verified">
                                    <?php esc_html_e('Verified purchase', 'brave-hearts'); ?>
                                </span>
                            <?php endif; ?>
                        </p>
                    </li>
                <?php endforeach; ?>
            </ol>
        <?php endif; ?>

        <?php
        /*
         * CYCLE142-CX-079 — the empty form was still offered directly beneath
         * the thank-you panel with its values cleared, so a reviewer who did not
         * read the panel could immediately send a second one. WordPress's
         * duplicate detection catches a verbatim resubmission; a slightly
         * reworded one produced a second held review from the same person.
         *
         * After a successful submission the form is not rendered at all. The
         * `#write-review` anchor goes with it, which is correct: there is
         * nothing to write on this page view, and the anchor's job — landing an
         * email link on a form — has already been done.
         */
        ?>
        <?php if (!$submitted) : ?>
            <div class="bhp-review-section__form" id="write-review">
                <?php
                get_template_part('template-parts/reviews/review-form', null, [
                    'key'     => $key,
                    'context' => $context,
                    /*
                     * CYCLE142-CX-077. In the empty state the header above
                     * already reads "Write a Review for <title>"; letting the
                     * form print it again put the identical sentence twice on
                     * the page, one directly under the other. Once a review is
                     * approved the header becomes "Reader Reviews of <title>"
                     * and the form's own heading is useful again.
                     */
                    'show_heading' => $count > 0,
                ]);
                ?>
            </div>

            <?php $review_page = bhp_review_page_url($key); ?>
            <?php if ($review_page) : ?>
                <p class="bhp-review-section__shortcut">
                    <a href="<?php echo esc_url($review_page); ?>">
                        <?php esc_html_e('Prefer a page with nothing else on it? Use the quick review form.', 'brave-hearts'); ?>
                    </a>
                </p>
            <?php endif; ?>
        <?php endif; ?>

    </div>
</section>
