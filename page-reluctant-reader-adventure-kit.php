<?php
/**
 * Template Name: Reluctant Reader Adventure Kit Landing Page
 * Description: Sitewide-facing parent/grandparent landing page for the
 * Reluctant Reader Adventure Kit lead magnet. Stays in a "coming soon"
 * state — form wired but inactive — until the Adventure Kit PDF is set
 * under Settings → Lead Magnets. Never substitutes the Mariana classroom
 * guide or applies teacher tags to a parent lead.
 */
defined('ABSPATH') || exit;
get_header();

$page_id = get_queried_object_id();
$source_page = get_permalink($page_id) ?: home_url('/');
$download = bhp_get_reluctant_reader_download();
$adventures = bhp_get_series_adventures();
$thank_you_url = home_url('/adventure-kit-thank-you/');

$pain_points = [
    __('“Reading is boring.”', 'brave-hearts'),
    __('Gives up after just a few pages', 'brave-hearts'),
    __('Books at their level feel babyish', 'brave-hearts'),
    __('Exciting chapter books feel too hard', 'brave-hearts'),
    __('Too much screen time, not enough reading', 'brave-hearts'),
    __('Reading time keeps turning into a battle', 'brave-hearts'),
];

$kit_includes = [
    __('A sample chapter from Adventures of Charlotte and Henry', 'brave-hearts'),
    __('An explorer activity to try together', 'brave-hearts'),
    __('Five low-pressure discussion prompts', 'brave-hearts'),
    __('A "Choose Your Next Adventure" section', 'brave-hearts'),
    __('A printable completion badge', 'brave-hearts'),
    __('Simple reading tips for parents', 'brave-hearts'),
];

$strengths = [
    __('Short, manageable chapters', 'brave-hearts'),
    __('Illustrations throughout', 'brave-hearts'),
    __('Real places, real science, real history', 'brave-hearts'),
    __('Recurring characters kids look forward to seeing again', 'brave-hearts'),
    __('Ages 6–9', 'brave-hearts'),
    __('Educational without feeling like homework', 'brave-hearts'),
];
?>
<section class="passport-page-hero section section--dark" aria-labelledby="adventure-kit-hero-title">
  <div class="container container--content passport-page-hero__inner">
    <p class="component-heading__eyebrow"><?php esc_html_e('Free reading adventure', 'brave-hearts'); ?></p>
    <h1 id="adventure-kit-hero-title" class="text-hero"><?php esc_html_e('For Kids Who Love Adventure—but Do Not Love Reading Yet', 'brave-hearts'); ?></h1>
    <p class="text-lead"><?php esc_html_e('Download a free 20-minute reading adventure for children ages 6–9 who lose interest in long chapters, struggle to find books they enjoy, or need a more exciting path into independent reading.', 'brave-hearts'); ?></p>
  </div>
</section>

<section class="passport-section section" aria-labelledby="adventure-kit-pain-title">
  <div class="container container--content">
    <h2 id="adventure-kit-pain-title" class="text-section-title"><?php esc_html_e('Sound Familiar?', 'brave-hearts'); ?></h2>
    <ul class="grid grid--3 passport-steps mariana-value-points">
      <?php foreach ($pain_points as $point): ?>
        <li class="feature-card mariana-value-points__item"><?php echo esc_html($point); ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>

<section class="passport-section section section--muted" aria-labelledby="adventure-kit-includes-title">
  <div class="container">
    <header class="component-heading component-heading--center">
      <h2 id="adventure-kit-includes-title" class="text-section-title"><?php esc_html_e('What’s Inside the Kit', 'brave-hearts'); ?></h2>
    </header>
    <ul class="grid grid--3 passport-steps mariana-value-points">
      <?php foreach ($kit_includes as $item): ?>
        <li class="feature-card mariana-value-points__item"><?php echo esc_html($item); ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>

<section class="passport-section section" aria-labelledby="adventure-kit-why-title">
  <div class="container container--content">
    <h2 id="adventure-kit-why-title" class="text-section-title"><?php esc_html_e('Why Manageable Chapters and Choice Matter', 'brave-hearts'); ?></h2>
    <p class="text-lead"><?php esc_html_e('A reluctant reader rarely needs an easier book — they need a shorter finish line and a story worth reaching it for. Short chapters let a child feel the win of finishing something. Letting them choose which real place to explore next turns reading into their idea, not an assignment.', 'brave-hearts'); ?></p>
  </div>
</section>

<section class="passport-section section section--muted" aria-labelledby="adventure-kit-strengths-title">
  <div class="container">
    <header class="component-heading component-heading--center">
      <h2 id="adventure-kit-strengths-title" class="text-section-title"><?php esc_html_e('Real-World Adventures Kids Actually Want to Finish', 'brave-hearts'); ?></h2>
    </header>
    <ul class="grid grid--3 passport-steps mariana-value-points">
      <?php foreach ($strengths as $strength): ?>
        <li class="feature-card mariana-value-points__item"><?php echo esc_html($strength); ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>

<div class="container passport-signup-wrap section section--muted">
  <?php if ($download['ready']): ?>
    <?php get_template_part('template-parts/acquisition/lead-magnet-cta', null, [
        'id'                   => 'adventure-kit-signup',
        'lead_magnet'          => 'reluctant_reader_adventure_kit',
        'audience_type'        => 'parents_families',
        'title'                => __('Send Me the Free Adventure Kit', 'brave-hearts'),
        'text'                 => __('A free 20-minute reading adventure with a sample chapter, an explorer activity, and simple ways to make reading feel fun again.', 'brave-hearts'),
        'submit_label'         => __('Send Me the Free Adventure Kit', 'brave-hearts'),
        'source_page'          => $source_page,
        'success_redirect_key' => 'adventure_kit_thank_you',
        'require_name'         => true,
    ]); ?>
    <p class="text-caption align-center"><?php esc_html_e('Free printable PDF. No purchase required.', 'brave-hearts'); ?></p>
  <?php else: ?>
    <aside class="acquisition-panel lead-magnet-cta" aria-labelledby="adventure-kit-coming-soon-title">
      <div class="acquisition-panel__content">
        <p class="component-heading__eyebrow"><?php esc_html_e('Coming soon', 'brave-hearts'); ?></p>
        <h3 id="adventure-kit-coming-soon-title"><?php esc_html_e('Send Me the Free Adventure Kit', 'brave-hearts'); ?></h3>
        <p><?php esc_html_e('The Adventure Kit is still being finished. Check back soon to get your free copy by email.', 'brave-hearts'); ?></p>
      </div>
      <span class="btn btn-primary" aria-disabled="true"><?php esc_html_e('Coming Soon', 'brave-hearts'); ?></span>
    </aside>
  <?php endif; ?>
</div>

<section class="passport-section section" aria-labelledby="adventure-kit-choose-title">
  <div class="container">
    <header class="component-heading component-heading--center">
      <h2 id="adventure-kit-choose-title" class="text-section-title"><?php esc_html_e('Choose Your Adventure', 'brave-hearts'); ?></h2>
      <p class="text-lead"><?php esc_html_e('Let your child pick where the story goes next.', 'brave-hearts'); ?></p>
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
