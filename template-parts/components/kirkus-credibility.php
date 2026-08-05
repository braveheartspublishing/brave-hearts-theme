<?php
/**
 * Kirkus Reviews credibility component.
 *
 * Modes:
 *   'expanded'    Full approved quote + attribution + reviewed title +
 *                 optional link to the official review. Used on the
 *                 homepage and the Mariana Trench product page.
 *   'compact'     Small trust-marker badge, no quote body. Used on shop/
 *                 archive product cards so the full quote isn't repeated
 *                 on every card.
 *   'series_note' A single factual line for OTHER Charlotte & Henry
 *                 titles (Mount Everest, Amazon Rainforest) that makes
 *                 clear only the Mariana Trench book was reviewed --
 *                 never implies those titles were individually reviewed.
 *
 * All approved content (quote/attribution/title/URL) comes from
 * bhp_get_kirkus_review_data() -- never hardcode it here or in a caller.
 * No Review or AggregateRating schema is emitted; this is plain semantic
 * HTML (blockquote/cite), never microdata.
 */
defined('ABSPATH') || exit;

$args = wp_parse_args($args ?? [], [
    'mode'      => 'expanded',
    'show_link' => true,
    'source'    => '',
    'class'     => '',
]);

$data = bhp_get_kirkus_review_data();
if (empty($data['quote']) || empty($data['attribution']) || empty($data['reviewed_title'])) {
    return; // Missing configuration -- render nothing rather than a broken/empty block.
}

$mode        = in_array($args['mode'], ['expanded', 'compact', 'series_note'], true) ? $args['mode'] : 'expanded';
$book_key    = 'mariana_trench';
$source      = sanitize_key($args['source']);
$review_url  = !empty($data['review_url']) ? esc_url_raw($data['review_url']) : '';
$show_link   = $args['show_link'] && $review_url;

$wrapper_classes = 'kirkus-credibility kirkus-credibility--' . $mode;
if ($args['class']) {
    $wrapper_classes .= ' ' . sanitize_html_class($args['class']);
}
?>
<div
  class="<?php echo esc_attr($wrapper_classes); ?>"
  data-bhp-impression-event="kirkus_component_impression"
  data-bhp-book="<?php echo esc_attr($book_key); ?>"
  data-bhp-source="<?php echo esc_attr($source); ?>"
>
<?php if ($mode === 'series_note'): ?>
  <p class="kirkus-credibility__note">
    <?php
    printf(
        /* translators: %s: reviewed book title, e.g. "Adventures of Charlotte & Henry: The Mariana Trench" */
        esc_html__('From the same series: our debut title, %s, was reviewed by Kirkus Reviews.', 'brave-hearts'),
        '<strong>' . esc_html($data['reviewed_title']) . '</strong>' // phpcs:ignore -- static, escaped title only
    );
    ?>
    <?php if ($show_link): ?>
      <a
        href="<?php echo esc_url($review_url); ?>"
        rel="noopener noreferrer"
        target="_blank"
        data-bhp-event="kirkus_review_link_click"
        data-bhp-book="<?php echo esc_attr($book_key); ?>"
        data-bhp-source="<?php echo esc_attr($source); ?>"
      ><?php esc_html_e('Read the review', 'brave-hearts'); ?><span class="screen-reader-text"><?php esc_html_e(' (opens in a new tab)', 'brave-hearts'); ?></span></a>
    <?php endif; ?>
  </p>

<?php elseif ($mode === 'compact'): ?>
  <span class="kirkus-credibility__badge">
    <span class="kirkus-credibility__badge-attribution"><?php echo esc_html($data['attribution']); ?></span>
  </span>

<?php else: /* expanded */ ?>
  <figure class="kirkus-credibility__card">
    <blockquote class="kirkus-credibility__quote">
      <p>&ldquo;<?php echo esc_html($data['quote']); ?>&rdquo;</p>
    </blockquote>
    <figcaption class="kirkus-credibility__attribution">
      <cite class="kirkus-credibility__source"><?php echo esc_html($data['attribution']); ?></cite>
      <span class="kirkus-credibility__title"><?php echo esc_html($data['reviewed_title']); ?></span>
      <?php if ($show_link): ?>
        <a
          class="kirkus-credibility__link"
          href="<?php echo esc_url($review_url); ?>"
          rel="noopener noreferrer"
          target="_blank"
          data-bhp-event="kirkus_review_link_click"
          data-bhp-book="<?php echo esc_attr($book_key); ?>"
          data-bhp-source="<?php echo esc_attr($source); ?>"
        ><?php
            printf(
                /* translators: %s: reviewed book title */
                esc_html__('Read the full Kirkus review of %s', 'brave-hearts'),
                esc_html($data['reviewed_title'])
            );
        ?><span class="screen-reader-text"><?php esc_html_e(' (opens in a new tab)', 'brave-hearts'); ?></span></a>
      <?php endif; ?>
    </figcaption>
  </figure>
<?php endif; ?>
</div>
