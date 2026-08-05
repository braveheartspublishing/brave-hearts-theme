<?php
/**
 * Template Name: Mariana Trench Parent Landing Page
 * Description: Signup page for the Mariana Trench parent/homeschool reading
 * companion. The guide itself does not exist yet, so the signup CTA stays in
 * a "coming soon" state — wired but inactive — until an admin sets the PDF
 * URL under Settings → Lead Magnets. No signups are collected for a guide
 * that can't yet be delivered.
 */
defined('ABSPATH') || exit;
get_header();

$page_id = get_queried_object_id();
$source_page = get_permalink($page_id) ?: home_url('/');
$download = bhp_get_mariana_guide_download('parents_families');
$adventure = bhp_get_series_adventures()['mariana_trench'] ?? [];

$value_points = [
    __('Screen-free learning', 'brave-hearts'),
    __('Ocean science', 'brave-hearts'),
    __('Reading comprehension', 'brave-hearts'),
    __('Deep-sea exploration', 'brave-hearts'),
    __('Ages 6–9', 'brave-hearts'),
    __('Easy activity for home or homeschool use', 'brave-hearts'),
];
?>
<section class="passport-page-hero section section--dark" aria-labelledby="mariana-parent-hero-title">
  <div class="container container--content passport-page-hero__inner">
    <p class="component-heading__eyebrow"><?php esc_html_e('A free reading companion', 'brave-hearts'); ?></p>
    <h1 id="mariana-parent-hero-title" class="text-hero"><?php esc_html_e('Turn reading time into a deep-sea science adventure.', 'brave-hearts'); ?></h1>
    <p class="text-lead"><?php esc_html_e('Download a free 2-page Mariana Trench reading companion for children ages 6–9, featuring discussion prompts and a simple ocean-science activity inspired by Adventures of Charlotte and Henry: The Mariana Trench.', 'brave-hearts'); ?></p>
  </div>
</section>

<section class="passport-section section" aria-labelledby="mariana-parent-values-title">
  <div class="container">
    <header class="component-heading component-heading--center">
      <h2 id="mariana-parent-values-title" class="text-section-title"><?php esc_html_e('Made for Curious Young Readers', 'brave-hearts'); ?></h2>
    </header>
    <ul class="grid grid--3 passport-steps mariana-value-points">
      <?php foreach ($value_points as $point): ?>
        <li class="feature-card mariana-value-points__item"><?php echo esc_html($point); ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>

<div class="container passport-signup-wrap section section--muted">
  <?php if ($download['ready']): ?>
    <?php get_template_part('template-parts/acquisition/lead-magnet-cta', null, [
        'id'                   => 'mariana-parent-signup',
        'lead_magnet'          => 'mariana_trench_parent_guide',
        'audience_type'        => 'parents_families',
        'title'                => __('Send Me the Free Guide', 'brave-hearts'),
        'text'                 => __('A free 2-page reading companion with discussion prompts and a simple ocean-science activity.', 'brave-hearts'),
        'submit_label'         => __('Send Me the Free Guide', 'brave-hearts'),
        'source_page'          => $source_page,
        'success_redirect_key' => 'mariana_guide_thank_you',
    ]); ?>
  <?php else: ?>
    <aside class="acquisition-panel lead-magnet-cta" aria-labelledby="mariana-parent-coming-soon-title">
      <div class="acquisition-panel__content">
        <p class="component-heading__eyebrow"><?php esc_html_e('Coming soon', 'brave-hearts'); ?></p>
        <h3 id="mariana-parent-coming-soon-title"><?php esc_html_e('Send Me the Free Guide', 'brave-hearts'); ?></h3>
        <p><?php esc_html_e('The parent reading companion is still being finished. Check back soon to get your free copy by email.', 'brave-hearts'); ?></p>
      </div>
      <span class="btn btn-primary" aria-disabled="true"><?php esc_html_e('Coming Soon', 'brave-hearts'); ?></span>
    </aside>
  <?php endif; ?>
</div>

<section class="passport-section section" aria-labelledby="mariana-parent-next-title">
  <div class="container container--content align-center">
    <h2 id="mariana-parent-next-title" class="text-section-title"><?php esc_html_e('Continue the Adventure', 'brave-hearts'); ?></h2>
    <p class="text-lead"><?php esc_html_e('Charlotte and Henry descend to the deepest place on Earth. Bring the full story home.', 'brave-hearts'); ?></p>
    <?php if (!empty($adventure['primary_url'])): ?>
      <a class="btn btn-secondary" href="<?php echo esc_url($adventure['primary_url']); ?>"><?php esc_html_e('Continue the Adventure', 'brave-hearts'); ?></a>
    <?php endif; ?>
  </div>
</section>
<?php get_footer(); ?>
