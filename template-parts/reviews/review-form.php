<?php
/**
 * The write-a-review form.
 *
 * Posts to `wp-comments-post.php`, exactly as WooCommerce's own review form
 * does, so the review is stored, typed, rated, purchase-verified and moderated
 * by the stock WordPress/WooCommerce pipeline. See inc/reviews.php.
 *
 * ⛔ NO NONCE, DELIBERATELY — UNCHANGED BY THE 1.19.165 VALIDATION WORK. This
 *    form is rendered inside a page SiteGround full-page caches. A cached nonce
 *    goes stale and rejects genuine reviews with no visible cause and no
 *    server-side trace. WordPress's own comment form has never carried one for
 *    the same reason. CSRF risk here is submitting a review in someone's name
 *    that a human then has to approve — the honeypot, WordPress flood control,
 *    duplicate detection and the enforced moderation hold in inc/reviews.php
 *    cover it. The new validation adds a gate; it does not add authority, so it
 *    does not change that reasoning.
 *
 * ⛔ `novalidate` IS ALSO DELIBERATE AND IS KEPT. The star picker is five real
 *    radios sized 1×1 at `opacity:0` — the correct accessible technique, and
 *    what lets `input:checked ~ label` colour the stars. Chrome will not show a
 *    native validation bubble for a control it cannot display; it blocks the
 *    submit with a console warning and NO visible message. Removing `novalidate`
 *    would therefore trade a bad error page for a form that silently refuses to
 *    submit. The replacement is ours, at both ends — assets/js/reviews.js in the
 *    page, and bhp_review_intercept_submission() on the server, which is the one
 *    that actually decides. See the long note in inc/reviews.php.
 *
 * @var array $args {
 *     @type string $key          adventure key.
 *     @type string $context      'product' | 'standalone'.
 *     @type bool   $show_heading Render the form's own <h3>. Default true.
 * }
 */

defined('ABSPATH') || exit;

$key     = isset($args['key']) ? $args['key'] : '';
$context = isset($args['context']) && 'standalone' === $args['context'] ? 'standalone' : 'product';
$target  = bhp_review_target_id($key);

if (!$key || !$target) {
    return;
}

/*
 * CYCLE142-CX-077 — the same sentence twice, one above the other.
 *
 * With no approved reviews the section header already reads "Write a Review for
 * <title>", and this form's own heading said exactly the same thing directly
 * beneath it. The caller decides, because only the caller knows what its own
 * header says: review-section.php passes false in the empty state and true once
 * the header changes to "Reader Reviews of <title>", and the standalone page
 * leaves it true because its H1 is the book title alone.
 */
$show_heading = !isset($args['show_heading']) || (bool) $args['show_heading'];

$title      = bhp_review_book_title($key);
$uid        = 'bhp-review-' . $context;
$rating_req = bhp_review_rating_required_for($target);

// Failed-submission state, if this page view is the result of one.
$errors    = bhp_review_errors_for($context);
$messages  = bhp_review_error_messages();
$err_field = bhp_review_error_fields();
$v_rating  = (int) bhp_review_value_for($context, 'rating', 0);
$v_comment = (string) bhp_review_value_for($context, 'comment', '');
$v_author  = (string) bhp_review_value_for($context, 'author', '');
$v_email   = (string) bhp_review_value_for($context, 'email', '');

/** Does a named field have an outstanding error from the last submission? */
$field_error = static function ($field) use ($errors, $err_field, $messages) {
    foreach ($errors as $code) {
        if (isset($err_field[$code]) && $err_field[$code] === $field) {
            return isset($messages[$code]) ? $messages[$code] : '';
        }
    }
    return '';
};

$e_rating  = $field_error('rating');
$e_comment = $field_error('comment');
$e_author  = $field_error('author');
$e_email   = $field_error('email');

// Fixed, cache-safe return target — never derived from the current request URL.
$return_url = 'standalone' === $context
    ? bhp_review_page_url($key)
    : get_permalink($target);

/*
 * ⭐ 1.19.262 (2026-08-19, CYCLE165-LD-DIRECTION1-STEP3-PRODUCT) — THE EM
 *    DASHES COME OUT OF THE FIVE RATING LABELS.
 *
 * ⭐ THESE LABELS ARE OURS, NOT A CUSTOMER'S WORDS, AND THAT DISTINCTION IS THE
 *    WHOLE REASON THIS EDIT IS ALLOWED. Standing rule §9.1a forbids altering a
 *    quoted third-party statement — the real Amazon review on these pages reads
 *    "We read a few chapters each night", and "fixing" that "we" would
 *    FABRICATE A CUSTOMER STATEMENT. Nothing in this pass touches it. The five
 *    strings below are site chrome written by this company, so the no-em-dash
 *    rail applies to them exactly as it does to any other line of ours.
 *
 * The CRO audit counted 5 em dashes per product page here. A colon does the
 * same work: "5 stars: loved it".
 *
 * ⛔ THE VALUES ARE UNTOUCHED. Only the punctuation inside the visible label
 *    moves. The 1..5 keys, the radio values, the required-rating enforcement
 *    and the stored `rating` meta are byte-identical.
 *
 * SUPERSEDED wording, retained so it is not re-derived: "5 stars — loved it",
 * "4 stars — really good", "3 stars — it was okay", "2 stars — not for us",
 * "1 star — did not work for us".
 */
$star_labels = [
    5 => __('5 stars: loved it', 'brave-hearts'),
    4 => __('4 stars: really good', 'brave-hearts'),
    3 => __('3 stars: it was okay', 'brave-hearts'),
    2 => __('2 stars: not for us', 'brave-hearts'),
    1 => __('1 star: did not work for us', 'brave-hearts'),
];
?>
<form
    class="bhp-review-form bhp-review-form--<?php echo esc_attr($context); ?>"
<?php /* NOT `write-review`. That anchor belongs to the section wrapper in
       review-section.php, and giving it to the form as well produced a
       DUPLICATE id="write-review" on the product page — caught by counting
       the id in the rendered staging HTML, not by reading the templates. */ ?>
    id="bhp-review-form-<?php echo esc_attr($context); ?>"
    action="<?php echo esc_url(site_url('/wp-comments-post.php')); ?>"
    method="post"
    novalidate="novalidate"
    data-bhp-review-form="<?php echo esc_attr($context); ?>"
    data-bhp-rating-required="<?php echo $rating_req ? '1' : '0'; ?>"
>
    <?php if ($show_heading) : ?>
        <h3 class="bhp-review-form__heading">
            <?php echo esc_html(bhp_review_invitation_heading($key)); ?>
        </h3>
    <?php endif; ?>

    <?php
    /*
     * The validation summary. Always in the DOM so `role="alert"` is already
     * live when reviews.js fills it in; `hidden` is the empty state. PHP fills
     * it on a server-side rejection, JS on a client-side one, same markup both
     * ways.
     */
    ?>
    <div
        class="bhp-review-form__errors"
        id="bhp-review-errors-<?php echo esc_attr($context); ?>"
        role="alert"
        tabindex="-1"
        <?php echo $errors ? '' : 'hidden'; ?>
    >
        <p class="bhp-review-form__errors-heading">
            <?php esc_html_e('Your review has not been sent yet. Please check the following:', 'brave-hearts'); ?>
        </p>
        <ul class="bhp-review-form__errors-list">
            <?php foreach ($errors as $code) : ?>
                <?php if (isset($messages[$code])) : ?>
                    <li>
                        <?php if (isset($err_field[$code])) : ?>
                            <a href="#<?php echo esc_attr($uid . '-' . ('rating' === $err_field[$code] ? 'rating-5' : $err_field[$code])); ?>">
                                <?php echo esc_html($messages[$code]); ?>
                            </a>
                        <?php else : ?>
                            <?php echo esc_html($messages[$code]); ?>
                        <?php endif; ?>
                    </li>
                <?php endif; ?>
            <?php endforeach; ?>
        </ul>
    </div>

    <?php /* The wording each message uses, so reviews.js and PHP cannot drift
             apart and so both stay translatable from one place. */ ?>
    <script type="application/json" class="bhp-review-form__messages">
        <?php echo wp_json_encode($messages, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_json_encode with JSON_HEX_TAG; cannot terminate the script element. Consumed by JSON.parse. ?>
    </script>

    <p class="bhp-review-form__intro">
        <?php esc_html_e('Honest is what helps. A sentence or two about how it went for your reader is worth more than a perfect score.', 'brave-hearts'); ?>
    </p>

    <fieldset class="bhp-review-form__rating">
        <legend class="bhp-review-form__label" id="<?php echo esc_attr($uid . '-rating-legend'); ?>">
            <?php esc_html_e('Your rating', 'brave-hearts'); ?>
            <?php if ($rating_req) : ?><span class="bhp-review-form__req" aria-hidden="true">*</span><?php endif; ?>
        </legend>
        <?php
        /*
         * CYCLE142-CX-076 — the radios reported `invalid: "true"` to the
         * accessibility tree on first load, before the reviewer had touched
         * anything, because a `required` radio group with nothing selected is
         * `:invalid` from the moment it renders. A screen-reader user was told
         * the rating was wrong before being given a chance to answer.
         *
         * The HTML `required` attribute is therefore gone and `aria-required`
         * on the group carries the same meaning without the premature invalid
         * state. Nothing is lost: `required` was already inert here because the
         * form is `novalidate`, and the rating is now genuinely enforced in
         * reviews.js and, decisively, on the server (CYCLE142-CX-072).
         */
        ?>
        <div
            class="bhp-star-input"
            role="radiogroup"
            aria-labelledby="<?php echo esc_attr($uid . '-rating-legend'); ?>"
            <?php echo $rating_req ? 'aria-required="true"' : ''; ?>
            <?php echo $e_rating ? 'data-bhp-invalid="true"' : ''; ?>
            <?php echo $e_rating ? 'aria-describedby="' . esc_attr($uid . '-rating-error') . '"' : ''; ?>
        >
            <?php foreach ($star_labels as $value => $label) : ?>
                <input
                    type="radio"
                    name="rating"
                    id="<?php echo esc_attr($uid . '-rating-' . $value); ?>"
                    value="<?php echo esc_attr($value); ?>"
                    <?php checked($v_rating, $value); ?>
                />
                <label for="<?php echo esc_attr($uid . '-rating-' . $value); ?>">
                    <span aria-hidden="true">&#9733;</span>
                    <span class="screen-reader-text"><?php echo esc_html($label); ?></span>
                </label>
            <?php endforeach; ?>
        </div>
        <span class="bhp-review-form__error" id="<?php echo esc_attr($uid . '-rating-error'); ?>" <?php echo $e_rating ? '' : 'hidden'; ?>>
            <?php echo esc_html($e_rating); ?>
        </span>
    </fieldset>

    <p class="bhp-review-form__field bhp-review-form__field--comment">
        <label class="bhp-review-form__label" for="<?php echo esc_attr($uid . '-comment'); ?>">
            <?php
            echo esc_html(sprintf(
                /* translators: %s: short book title. */
                __('Your review of %s', 'brave-hearts'),
                $title
            ));
            ?>
            <span class="bhp-review-form__req" aria-hidden="true">*</span>
        </label>
        <textarea
            id="<?php echo esc_attr($uid . '-comment'); ?>"
            name="comment"
            rows="5"
            required
            aria-describedby="<?php echo esc_attr($uid . '-comment-error'); ?>"
            <?php echo $e_comment ? 'aria-invalid="true"' : ''; ?>
            data-bhp-review-textarea="<?php echo esc_attr($context); ?>"
            placeholder="<?php esc_attr_e('What did your reader think?', 'brave-hearts'); ?>"
        ><?php echo esc_textarea($v_comment); ?></textarea>
        <span class="bhp-review-form__error" id="<?php echo esc_attr($uid . '-comment-error'); ?>" <?php echo $e_comment ? '' : 'hidden'; ?>>
            <?php echo esc_html($e_comment); ?>
        </span>
    </p>

    <?php if (!is_user_logged_in()) : ?>
        <div class="bhp-review-form__identity">
            <p class="bhp-review-form__field">
                <label class="bhp-review-form__label" for="<?php echo esc_attr($uid . '-author'); ?>">
                    <?php esc_html_e('Name', 'brave-hearts'); ?>
                    <span class="bhp-review-form__req" aria-hidden="true">*</span>
                </label>
                <input
                    type="text"
                    id="<?php echo esc_attr($uid . '-author'); ?>"
                    name="author"
                    autocomplete="name"
                    required
                    value="<?php echo esc_attr($v_author); ?>"
                    aria-describedby="<?php echo esc_attr($uid . '-author-error'); ?>"
                    <?php echo $e_author ? 'aria-invalid="true"' : ''; ?>
                />
                <span class="bhp-review-form__error" id="<?php echo esc_attr($uid . '-author-error'); ?>" <?php echo $e_author ? '' : 'hidden'; ?>>
                    <?php echo esc_html($e_author); ?>
                </span>
            </p>
            <p class="bhp-review-form__field">
                <label class="bhp-review-form__label" for="<?php echo esc_attr($uid . '-email'); ?>">
                    <?php esc_html_e('Email', 'brave-hearts'); ?>
                    <span class="bhp-review-form__req" aria-hidden="true">*</span>
                </label>
                <input
                    type="email"
                    id="<?php echo esc_attr($uid . '-email'); ?>"
                    name="email"
                    autocomplete="email"
                    inputmode="email"
                    required
                    value="<?php echo esc_attr($v_email); ?>"
                    aria-describedby="<?php echo esc_attr($uid . '-email-error'); ?>"
                    <?php echo $e_email ? 'aria-invalid="true"' : ''; ?>
                />
                <span class="bhp-review-form__error" id="<?php echo esc_attr($uid . '-email-error'); ?>" <?php echo $e_email ? '' : 'hidden'; ?>>
                    <?php echo esc_html($e_email); ?>
                </span>
            </p>
            <p class="bhp-review-form__privacy">
                <?php esc_html_e('Your email is never published and is never added to any mailing list — it is only how we can reach you about your review.', 'brave-hearts'); ?>
            </p>
        </div>
    <?php endif; ?>

    <?php /* Honeypot. Hidden from people and from assistive technology; only a bot fills it in. */ ?>
    <div class="bhp-review-form__hp" aria-hidden="true">
        <label for="<?php echo esc_attr($uid . '-hp'); ?>"><?php esc_html_e('Leave this field empty', 'brave-hearts'); ?></label>
        <input type="text" id="<?php echo esc_attr($uid . '-hp'); ?>" name="bhp_review_hp" value="" tabindex="-1" autocomplete="off" />
    </div>

    <input type="hidden" name="comment_post_ID" value="<?php echo esc_attr($target); ?>" />
    <input type="hidden" name="comment_parent" value="0" />
    <input type="hidden" name="bhp_review_context" value="<?php echo esc_attr($context); ?>" />
    <input type="hidden" name="bhp_review_book" value="<?php echo esc_attr($key); ?>" />
    <input type="hidden" name="bhp_review_return" value="<?php echo esc_url($return_url); ?>" />

    <p class="bhp-review-form__actions">
        <button type="submit" class="btn btn-cta-primary bhp-review-form__submit" name="submit">
            <?php esc_html_e('Submit My Review', 'brave-hearts'); ?>
        </button>
    </p>

    <p class="bhp-review-form__moderation">
        <?php esc_html_e('Every review is read before it appears on the site. Nothing is edited — reviews are either published as written or not published.', 'brave-hearts'); ?>
    </p>
</form>
