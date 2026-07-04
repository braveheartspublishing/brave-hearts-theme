<?php
/**
 * Template Name: Adventure Kit Thank-You Page
 * Description: Parent-specific post-signup confirmation for the Reluctant
 * Reader Adventure Kit. Deliberately separate from the Mariana teacher/
 * parent thank-you page — no teacher or author-visit messaging here, and
 * it must never be reachable via an arbitrary redirect (only the
 * whitelisted 'adventure_kit_thank_you' key in inc/mailchimp.php resolves
 * to this page).
 */
defined('ABSPATH') || exit;
get_header();

$adventures = bhp_get_series_adventures();
?>
<section class="passport-status-page section" aria-labelledby="adventure-kit-thank-you-title">
  <div class="container container--content passport-status-page__inner">
    <p class="component-heading__eyebrow"><?php esc_html_e('Your adventure kit is on the way', 'brave-hearts'); ?></p>
    <h1 id="adventure-kit-thank-you-title"><?php esc_html_e('Your Reluctant Reader Adventure Kit Is on Its Way', 'brave-hearts'); ?></h1>
    <p class="text-lead"><?php esc_html_e('Your guide is on the way. Check your inbox in the next few minutes. If you do not see it, check your spam or promotions folder.', 'brave-hearts'); ?></p>
  </div>
</section>

<section class="passport-section section section--muted" aria-labelledby="adventure-kit-thank-you-choose-title">
  <div class="container">
    <header class="component-heading component-heading--center">
      <h2 id="adventure-kit-thank-you-choose-title" class="text-section-title"><?php esc_html_e('Let Your Child Choose Their Adventure', 'brave-hearts'); ?></h2>
    </header>
    <div class="grid grid--3 passport-steps">
      <?php foreach (['mariana_trench', 'mount_everest', 'amazon_rainforest'] as $key):
        $adventure = $adventures[$key] ?? [];
        if (empty($adventure['primary_url'])) {
            continue;
        }
      ?>
        <?php get_template_part('template-parts/components/book-card', null, [
            'title'       => $adventure['title'] ?? '',
            'url'         => $adventure['primary_url'],
            'image_id'    => $adventure['image_id'] ?? 0,
            'image_alt'   => $adventure['image_alt'] ?? '',
            'description' => $adventure['description'] ?? '',
            'age_range'   => $adventure['age_range'] ?? '',
            'cta_label'   => __('View the book', 'brave-hearts'),
        ]); ?>
      <?php endforeach; ?>
    </div>
    <p class="align-center">
      <a class="btn btn-outline" href="<?php echo esc_url(home_url('/books/')); ?>"><?php esc_html_e('See the Full Series', 'brave-hearts'); ?></a>
    </p>
  </div>
</section>
<?php get_footer(); ?>
