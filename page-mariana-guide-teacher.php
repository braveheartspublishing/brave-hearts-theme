<?php
/**
 * Template Name: Mariana Trench Teacher Landing Page
 * Description: Focused signup page for the Mariana Trench classroom guide.
 */
defined('ABSPATH') || exit;
get_header();

$page_id = get_queried_object_id();
$source_page = get_permalink($page_id) ?: home_url('/');
$download = bhp_get_mariana_guide_download('teachers');
$adventure = bhp_get_series_adventures()['mariana_trench'] ?? [];
$author_visit_url = bhp_get_safe_link_url(add_query_arg('inquiry', 'read-aloud', home_url('/contact/')));

$value_points = [
    __('Printable', 'brave-hearts'),
    __('Grades 1–3', 'brave-hearts'),
    __('Reading comprehension', 'brave-hearts'),
    __('Marine ecosystems', 'brave-hearts'),
    __('Bioluminescence', 'brave-hearts'),
    __('Classroom or library use', 'brave-hearts'),
    __('Minimal preparation', 'brave-hearts'),
];
?>
<section class="passport-page-hero section section--dark" aria-labelledby="mariana-teacher-hero-title">
  <div class="container container--content passport-page-hero__inner">
    <p class="component-heading__eyebrow"><?php esc_html_e('A free classroom guide', 'brave-hearts'); ?></p>
    <h1 id="mariana-teacher-hero-title" class="text-hero"><?php esc_html_e('A free, no-prep Mariana Trench reading companion for Grades 1–3.', 'brave-hearts'); ?></h1>
    <p class="text-lead"><?php esc_html_e('Introduce young readers to deep-ocean exploration, marine life, and bioluminescence with a printable 2-page companion to Adventures of Charlotte and Henry: The Mariana Trench.', 'brave-hearts'); ?></p>
  </div>
</section>

<section class="passport-section section" aria-labelledby="mariana-teacher-values-title">
  <div class="container">
    <header class="component-heading component-heading--center">
      <h2 id="mariana-teacher-values-title" class="text-section-title"><?php esc_html_e('Built for a Busy Classroom', 'brave-hearts'); ?></h2>
    </header>
    <ul class="grid grid--3 passport-steps mariana-value-points">
      <?php foreach ($value_points as $point): ?>
        <li class="feature-card mariana-value-points__item"><?php echo esc_html($point); ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>

<section class="passport-section section section--muted" aria-labelledby="mariana-teacher-standards-title">
  <div class="container container--content">
    <h2 id="mariana-teacher-standards-title" class="text-section-title"><?php esc_html_e('Standards Connections', 'brave-hearts'); ?></h2>
    <p><?php esc_html_e('Includes connections to Common Core ELA and NGSS concepts listed in the guide.', 'brave-hearts'); ?></p>
    <p class="text-caption"><?php esc_html_e('Common Core ELA (Grades 1–3): RL.1.3 · RL.2.6 · RL.3.3 · SL.1.1 · SL.2.1 · SL.3.1 — NGSS ESS2: Earth’s Systems (Oceans and Water Systems).', 'brave-hearts'); ?></p>
  </div>
</section>

<div class="container passport-signup-wrap section section--muted">
  <?php if ($download['ready']): ?>
    <?php get_template_part('template-parts/acquisition/lead-magnet-cta', null, [
        'id'                   => 'mariana-teacher-signup',
        'lead_magnet'          => 'mariana_trench_classroom_guide',
        'audience_type'        => 'teachers',
        'title'                => __('Get the Classroom Guide', 'brave-hearts'),
        'text'                 => __('A free, no-prep 2-page companion built for Grades 1–3 read-alouds and classroom discussion.', 'brave-hearts'),
        'submit_label'         => __('Email Me the Free Guide', 'brave-hearts'),
        'source_page'          => $source_page,
        'success_redirect_key' => 'mariana_guide_thank_you',
        'require_name'         => true,
    ]); ?>
  <?php else: ?>
    <aside class="acquisition-panel lead-magnet-cta" aria-labelledby="mariana-teacher-unavailable-title">
      <div class="acquisition-panel__content">
        <p class="component-heading__eyebrow"><?php esc_html_e('Free printable resource', 'brave-hearts'); ?></p>
        <h3 id="mariana-teacher-unavailable-title"><?php esc_html_e('Get the Classroom Guide', 'brave-hearts'); ?></h3>
        <p><?php esc_html_e('Signup is temporarily unavailable while the guide file is connected. Please check back soon.', 'brave-hearts'); ?></p>
      </div>
    </aside>
  <?php endif; ?>
</div>

<section class="passport-section section" aria-labelledby="mariana-teacher-next-title">
  <div class="container container--content align-center">
    <h2 id="mariana-teacher-next-title" class="text-section-title"><?php esc_html_e('Continue the Adventure', 'brave-hearts'); ?></h2>
    <p class="text-lead"><?php esc_html_e('Charlotte and Henry descend to the deepest place on Earth. Bring the full story to your classroom, library, or shelf.', 'brave-hearts'); ?></p>
    <div class="cluster">
      <?php if (!empty($adventure['primary_url'])): ?>
        <a class="btn btn-secondary" href="<?php echo esc_url($adventure['primary_url']); ?>"><?php esc_html_e('Continue the Adventure', 'brave-hearts'); ?></a>
      <?php endif; ?>
      <a class="btn btn-outline" href="<?php echo esc_url($author_visit_url); ?>"><?php esc_html_e('Request an Author Visit', 'brave-hearts'); ?></a>
    </div>
  </div>
</section>
<?php get_footer(); ?>
