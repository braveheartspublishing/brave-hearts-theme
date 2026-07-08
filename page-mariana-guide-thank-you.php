<?php
/**
 * Template Name: Mariana Guide Thank-You Page
 * Description: Shared post-signup confirmation for both the teacher and
 * parent Mariana Trench guide funnels. Adapts copy using an optional ?guide=
 * query parameter but defaults to the teacher experience (the only funnel
 * live at launch) when visited without one.
 */
defined('ABSPATH') || exit;
get_header();

$guide = isset($_GET['guide']) ? sanitize_key(wp_unslash($_GET['guide'])) : 'teacher';
if (!in_array($guide, ['teacher', 'parent'], true)) {
    $guide = 'teacher';
}

$adventure = bhp_get_series_adventures()['mariana_trench'] ?? [];
$author_visit_url = bhp_get_safe_link_url(add_query_arg('inquiry', 'read-aloud', home_url('/contact/')));

$copy = ($guide === 'parent')
    ? [
        'eyebrow' => __('Your guide is on the way', 'brave-hearts'),
        'title'   => __('Your Mariana Trench Reading Companion Is on Its Way', 'brave-hearts'),
        'text'    => __('Your guide is on the way. Check your inbox in the next few minutes. If you do not see it, check your spam or promotions folder.', 'brave-hearts'),
    ]
    : [
        'eyebrow' => __('Your classroom guide is on the way', 'brave-hearts'),
        'title'   => __('Your Mariana Trench Classroom Guide Is on Its Way', 'brave-hearts'),
        'text'    => __('Your guide is on the way. Check your inbox in the next few minutes. If you do not see it, check your spam or promotions folder.', 'brave-hearts'),
    ];
?>
<section class="passport-status-page section" aria-labelledby="mariana-thank-you-title">
  <div class="container container--content passport-status-page__inner">
    <p class="component-heading__eyebrow"><?php echo esc_html($copy['eyebrow']); ?></p>
    <h1 id="mariana-thank-you-title"><?php echo esc_html($copy['title']); ?></h1>
    <p class="text-lead"><?php echo esc_html($copy['text']); ?></p>
  </div>
</section>

<section class="passport-section section section--muted" aria-labelledby="mariana-thank-you-next-title">
  <div class="container">
    <header class="component-heading component-heading--center">
      <h2 id="mariana-thank-you-next-title" class="text-section-title"><?php esc_html_e('Continue the Adventure', 'brave-hearts'); ?></h2>
    </header>
    <div class="grid grid--3 passport-steps">
      <?php if (!empty($adventure['primary_url'])): ?>
        <?php get_template_part('template-parts/components/book-card', null, [
            'title'       => $adventure['title'] ?? __('Adventures of Charlotte and Henry: The Mariana Trench', 'brave-hearts'),
            'url'         => $adventure['primary_url'],
            'image_id'    => $adventure['image_id'] ?? 0,
            'image_alt'   => $adventure['image_alt'] ?? '',
            'description' => $adventure['description'] ?? '',
            'cta_label'   => __('View the book', 'brave-hearts'),
        ]); ?>
      <?php endif; ?>
      <?php get_template_part('template-parts/components/feature-card', null, [
          'title' => __('Explore the Full Series', 'brave-hearts'),
          'text'  => __('Follow Charlotte and Henry to more real places on the map.', 'brave-hearts'),
          'link'  => ['url' => home_url('/books/'), 'label' => __('See all books', 'brave-hearts')],
      ]); ?>
      <?php if ($guide === 'teacher'): ?>
        <?php get_template_part('template-parts/components/feature-card', null, [
            'title' => __('Bring the Story to Your Classroom', 'brave-hearts'),
            'text'  => __('Request a read-aloud or author visit where scheduling and location allow.', 'brave-hearts'),
            'link'  => ['url' => $author_visit_url, 'label' => __('Request a Read-Aloud or Author Visit', 'brave-hearts')],
        ]); ?>
      <?php endif; ?>
    </div>
  </div>
</section>
<?php get_footer(); ?>
