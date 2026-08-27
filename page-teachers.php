<?php
/**
 * Template Name: Brave Hearts Teacher Resources Page
 * Description: Classroom resources, read-aloud outreach, and teacher signup.
 */
defined('ABSPATH') || exit;
get_header();

if (have_posts()) {
    the_post();
}

$page_id = get_queried_object_id();
$teacher_field = static function ($key, $fallback = '') use ($page_id) {
    $field_name = 'bhp_teachers_' . sanitize_key($key);
    $stored = $page_id ? get_post_meta($page_id, $field_name, true) : '';
    $value = ($stored !== '') ? $stored : $fallback;
    return apply_filters('bhp_teachers_field_' . sanitize_key($key), $value, $page_id);
};

$read_aloud_url = bhp_get_safe_link_url(
    $teacher_field('read_aloud_url', ''),
    add_query_arg('inquiry', 'read-aloud', home_url('/contact/'))
);
$hero_image_id = (int) $teacher_field('hero_image_id', 0);
$adventures = bhp_get_series_adventures();

$resource_categories = apply_filters('bhp_teacher_resource_categories', [
    'lesson_plans' => [
        'title' => __('Lesson Plans', 'brave-hearts'),
        'text' => __('Flexible lesson structures that connect each story to reading, writing, science, geography, and reflection.', 'brave-hearts'),
    ],
    'discussion_guides' => [
        'title' => __('Discussion Guides', 'brave-hearts'),
        'text' => __('Questions that help students discuss courage, curiosity, kindness, teamwork, and the real world behind the story.', 'brave-hearts'),
    ],
    'vocabulary' => [
        'title' => __('Vocabulary', 'brave-hearts'),
        'text' => __('Kid-friendly vocabulary support drawn from each destination, scientific idea, and chapter adventure.', 'brave-hearts'),
    ],
    'maps' => [
        'title' => __('Maps', 'brave-hearts'),
        'text' => __('Printable geography connections that help readers locate every extraordinary destination.', 'brave-hearts'),
    ],
    'printables' => [
        'title' => __('Printables', 'brave-hearts'),
        'text' => __('Low-prep activities that extend reading into observation, writing, science, and creative exploration.', 'brave-hearts'),
    ],
    'read_aloud_resources' => [
        'title' => __('Read-Aloud Resources', 'brave-hearts'),
        'text' => __('Prompts, stopping points, and discussion support for classroom, library, homeschool, and family read-alouds.', 'brave-hearts'),
    ],
], $page_id);

foreach ($resource_categories as $key => &$resource) {
    $resource_url = bhp_get_safe_link_url($teacher_field($key . '_url', ''));
    $resource['url'] = $resource_url;
}
unset($resource);

$book_resources = [
    'mariana_trench' => [
        'themes' => [__('Ocean science', 'brave-hearts'), __('Conservation', 'brave-hearts'), __('Courage', 'brave-hearts'), __('Kindness', 'brave-hearts')],
        'subjects' => [__('Science', 'brave-hearts'), __('Geography', 'brave-hearts'), __('ELA', 'brave-hearts'), __('SEL', 'brave-hearts')],
    ],
    'mount_everest' => [
        'themes' => [__('Mountain geography', 'brave-hearts'), __('Resilience', 'brave-hearts'), __('Teamwork', 'brave-hearts'), __('Courage', 'brave-hearts')],
        'subjects' => [__('Geography', 'brave-hearts'), __('Science', 'brave-hearts'), __('ELA', 'brave-hearts'), __('SEL', 'brave-hearts')],
    ],
    'amazon_rainforest' => [
        'themes' => [__('Ecosystems', 'brave-hearts'), __('Biodiversity', 'brave-hearts'), __('Conservation', 'brave-hearts'), __('Curiosity', 'brave-hearts')],
        'subjects' => [__('Science', 'brave-hearts'), __('Geography', 'brave-hearts'), __('ELA', 'brave-hearts'), __('Environmental learning', 'brave-hearts')],
    ],
];

get_template_part('template-parts/components/hero', null, [
    'id'       => 'teachers-hero',
    'class'    => 'teachers-hero',
    'eyebrow'  => __('The Brave Hearts field guide library', 'brave-hearts'),
    'title'    => __('Explorer Expedition Guides', 'brave-hearts'),
    'text'     => __('Explore real-world articles, reading guidance, destination field notes, educator resources, and family paths - all connected to the questions behind the books.', 'brave-hearts'),
    'image_id' => $hero_image_id,
    'primary_link' => [
        'url'   => '#explore-topics',
        'label' => __('Explore the Guides', 'brave-hearts'),
    ],
    'secondary_link' => [
        'url'   => '#educator-resources',
        'label' => __('For Educators', 'brave-hearts'),
    ],
]);
?>
<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐ 1.19.296 (2026-08-27, `CYCLE167-LD-CAPTURE-FIX-BUILD`) — A CAPTURE PANEL
 *    THE EDUCATOR CAN ACTUALLY REACH. DUPLICATED HIGH, NOT MOVED.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⭐ THE FINDING (Merry, verified live and measured at a confirmed 390px):
 *    this page is **36.4 screens — 30,700px — on mobile**, and its ONLY
 *    capture surface sat at **94% depth, screen 34 of 36.4.** An educator had
 *    to read thirty-four screens before being asked for anything. Corroborated
 *    from the Mailchimp side by `connected-operator`: zero educator contacts
 *    since 2026-07-28.
 *
 * ⛔ THE EXISTING PANEL IS **NOT MOVED**, AND THAT IS DELIBERATE. It is the
 *    natural close of a long browse and it is the anchor target of the
 *    `#teacher-email-signup` link; moving it would break that link and take
 *    the ask away from the reader who did go all the way down. This is an
 *    ADDITIONAL entry point for the ~97% who never reach screen 34.
 *
 * ⭐ SAME FUNNEL, SAME TAGS, NO NEW SEGMENT MINTED. Identical `context`
 *    (`teacher_resources`), `audience_type` (`teachers`) and `lead_magnet` to
 *    the panel below, because `teacher-resource-signup.php` hardcodes the
 *    first two and this passes the same third. ⛔ A new context string would
 *    have minted a NEW tag in the live Mailchimp audience and split this
 *    surface's segment in two — a Mailchimp decision, and Andrew's, not an
 *    engineering one.
 *
 * ⛔ IDS CANNOT COLLIDE: `teacher-resource-signup.php` derives its heading id
 *    and its form id from the `id` argument, so the distinct `id` below yields
 *    a distinct section id, heading id and form id. Two forms with the same
 *    id would have broken the PRG error-feedback path for both.
 *
 * ⛔ FUNNEL ISOLATION: this is the TEACHER funnel on the teacher page. It
 *    reads and writes nothing belonging to the parent funnel, and every
 *    parent-funnel surface independently excludes `/teachers/`.
 */
?>
<div id="teacher-email-signup-top" class="container teacher-signup-wrap teacher-signup-wrap--top section">
  <?php get_template_part('template-parts/acquisition/teacher-resource-signup', null, [
      'id'           => 'teacher-resources-signup-top',
      'class'        => 'teacher-signup--compact',
      'lead_magnet'  => 'teacher_resources',
      'source_page'  => get_permalink($page_id),
      'title'        => __('Bring the adventure into your classroom', 'brave-hearts'),
      'text'         => __('Join the teacher list for resource updates, read-aloud ideas, classroom printables, and new-book news.', 'brave-hearts'),
      'submit_label' => __('Get Teacher Resource Updates', 'brave-hearts'),
  ]); ?>
</div>

<section id="explore-topics" class="guides-hub-section section" aria-labelledby="explore-topics-title">
  <div class="container">
    <header class="component-heading component-heading--center">
      <?php /* ⭐ 1.19.269 item 5 (founder ruling, 2026-08-19) — REMOVED, restates "Explore by Topic":
              <p class="component-heading__eyebrow"><?php esc_html_e('Choose a trail', 'brave-hearts'); ?></p>
         The keep/remove test is stated once, in full, in `page-about.php`. */ ?>
      <h2 id="explore-topics-title" class="text-section-title"><?php esc_html_e('Explore by Topic', 'brave-hearts'); ?></h2>
      <p class="component-heading__intro text-lead"><?php esc_html_e('Begin with the question your reader, family, or classroom is already asking.', 'brave-hearts'); ?></p>
    </header>
    <div class="guide-topic-grid">
      <?php foreach (['reading-growing','science-geography','educator-resources','book-brand-stories'] as $hub_key): $hub_posts = bhp_get_guide_posts($hub_key); ?>
        <a class="guide-topic-card" href="#<?php echo esc_attr($hub_key); ?>">
          <span class="guide-topic-card__count"><?php echo esc_html(sprintf(_n('%d field note', '%d field notes', count($hub_posts), 'brave-hearts'), count($hub_posts))); ?></span>
          <h3><?php echo esc_html(bhp_get_guide_hubs()[$hub_key]); ?></h3>
          <span><?php esc_html_e('Open this guide', 'brave-hearts'); ?> →</span>
        </a>
      <?php endforeach; ?>
      <a class="guide-topic-card" href="#family-resources">
        <span class="guide-topic-card__count"><?php esc_html_e('Family path', 'brave-hearts'); ?></span>
        <h3><?php esc_html_e('For Families', 'brave-hearts'); ?></h3>
        <span><?php esc_html_e('Explore together at home', 'brave-hearts'); ?> →</span>
      </a>
    </div>
    <div class="guide-search"><?php get_search_form(); ?></div>
  </div>
</section>

<?php
// Educator Toolkit conversion module (2026-07-18, audit-fix Change 1;
// revised 2026-07-18 to a compact supporting band -- the first version
// read as a full landing-page hero, oversized headline + section filling
// most of the first viewport + empty right column, risking the page being
// mistaken for the Educator Toolkit landing page itself). Placed here
// (after the intro/topic-nav, before the guide/destination archive
// content), reuses the existing teacher-resources-cta component's new
// `compact` two-column mode rather than new markup. This hub itself
// remains the browse-guides destination -- get_footer()'s sitewide
// "Resources for Every Reader" cluster and this module both link to the
// toolkit page; neither replaces or redirects this hub. The secondary
// link scrolls to the destinations section immediately below (added
// `id="guide-destinations"` to that section for this anchor -- same
// same-page-anchor pattern already used by every other section on this
// page, not a new destination).
get_template_part('template-parts/components/teacher-resources-cta', null, [
    'id'      => 'educator-toolkit-cta',
    'compact' => true,
    'eyebrow' => __('Free resource for educators', 'brave-hearts'),
    'title'   => __('Bring every adventure into the classroom.', 'brave-hearts'),
    'text'    => __('Download the free Adventure Learning Toolkit for classroom, library, or homeschool use.', 'brave-hearts'),
    'link'    => [
        'url'   => home_url('/educators-adventure-learning-toolkit/'),
        'label' => __('Get the Free Educator Toolkit', 'brave-hearts'),
    ],
    'secondary_link' => [
        'url'   => '#guide-destinations',
        'label' => __('Browse the Expedition Guides ↓', 'brave-hearts'),
    ],
    'link_cta_id'            => 'educator_toolkit_teachers_hub',
    'link_cta_placement'     => 'teachers_hub_top',
    'link_cta_destination'   => 'audience_landing',
    'link_cta_audience'      => 'educators',
    'link_cta_funnel_stage'  => 'discovery',
]);
?>

<section id="guide-destinations" class="guides-hub-section guides-hub-section--destinations section section--dark" aria-labelledby="guide-destinations-title">
  <div class="container">
    <header class="component-heading component-heading--center">
      <p class="component-heading__eyebrow"><?php esc_html_e('Real places behind the stories', 'brave-hearts'); ?></p>
      <h2 id="guide-destinations-title" class="text-section-title"><?php esc_html_e('Explore by Destination', 'brave-hearts'); ?></h2>
    </header>
    <div class="guide-destination-grid">
      <?php foreach (['mariana-trench','mount-everest','amazon-rainforest'] as $destination_key): $destination_posts = bhp_get_guide_posts($destination_key); if (!$destination_posts) { continue; } // Finding #17: only render a destination once it has real content — never a "Coming Soon" card. ?>
        <a class="guide-destination-card guide-destination-card--<?php echo esc_attr($destination_key); ?>" href="#<?php echo esc_attr($destination_key); ?>">
          <p><?php echo esc_html(sprintf(_n('%d connected field note', '%d connected field notes', count($destination_posts), 'brave-hearts'), count($destination_posts))); ?></p>
          <h3><?php echo esc_html(bhp_get_guide_hubs()[$destination_key]); ?></h3>
          <span><?php esc_html_e('Enter the destination guide', 'brave-hearts'); ?> →</span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php foreach (['reading-growing','science-geography','book-brand-stories','mariana-trench','mount-everest','amazon-rainforest','family-resources'] as $hub_key): $hub_posts = bhp_get_guide_posts($hub_key); if (!$hub_posts) { continue; } ?>
<section id="<?php echo esc_attr($hub_key); ?>" class="guides-hub-section guide-collection section<?php echo $hub_key === 'science-geography' ? ' section--muted' : ''; ?>" aria-labelledby="<?php echo esc_attr($hub_key); ?>-title">
  <div class="container">
    <header class="component-heading">
      <p class="component-heading__eyebrow"><?php esc_html_e('Curated expedition guide', 'brave-hearts'); ?></p>
      <h2 id="<?php echo esc_attr($hub_key); ?>-title" class="text-section-title"><?php echo esc_html(bhp_get_guide_hubs()[$hub_key]); ?></h2>
    </header>
    <?php $bhp_gc_collapsible = count($hub_posts) > 6; // Finding #16: collapse long guide lists to a preview + accessible "View all" (progressive enhancement — all cards stay in the HTML for crawlers/no-JS). ?>
    <div class="guide-article-grid<?php echo $bhp_gc_collapsible ? ' guide-article-grid--collapsible' : ''; ?>"<?php echo $bhp_gc_collapsible ? ' data-disclosure-initial="6"' : ''; ?>>
      <?php foreach ($hub_posts as $guide_post) { get_template_part('template-parts/guides/article-card', null, ['post' => $guide_post]); } ?>
    </div>
  </div>
</section>
<?php endforeach; ?>

<section id="educator-resources" class="teachers-section section" aria-labelledby="teacher-resource-categories-title">
  <div class="container">
    <header class="component-heading component-heading--center">
      <p class="component-heading__eyebrow"><?php esc_html_e('Classroom-ready support', 'brave-hearts'); ?></p>
      <h2 id="teacher-resource-categories-title" class="text-section-title"><?php esc_html_e('Resources That Continue the Adventure', 'brave-hearts'); ?></h2>
      <p class="component-heading__intro text-lead"><?php esc_html_e('Use Charlotte & Henry for read-alouds, discussion, writing prompts, vocabulary, science, geography, and curiosity-driven learning.', 'brave-hearts'); ?></p>
    </header>
    <?php $educator_posts = bhp_get_guide_posts('educator-resources'); ?>
    <?php if ($educator_posts): ?>
      <?php $bhp_ed_collapsible = count($educator_posts) > 6; ?>
      <div class="guide-article-grid guide-article-grid--educators<?php echo $bhp_ed_collapsible ? ' guide-article-grid--collapsible' : ''; ?>"<?php echo $bhp_ed_collapsible ? ' data-disclosure-initial="6"' : ''; ?>>
        <?php foreach ($educator_posts as $guide_post) { get_template_part('template-parts/guides/article-card', null, ['post' => $guide_post]); } ?>
      </div>
    <?php endif; ?>
    <div class="teacher-resource-grid">
      <?php foreach ($resource_categories as $resource): ?>
        <?php get_template_part('template-parts/teachers/resource-card', null, $resource); ?>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section id="book-based-resources" class="teachers-section section section--muted" aria-labelledby="book-based-resources-title">
  <div class="container">
    <header class="component-heading component-heading--center">
      <?php /* ⭐ 1.19.269 item 5 (founder ruling, 2026-08-19) — REMOVED, a slogan; the H2 names the resources:
              <p class="component-heading__eyebrow"><?php esc_html_e('Teach through story', 'brave-hearts'); ?></p>
         The keep/remove test is stated once, in full, in `page-about.php`. */ ?>
      <h2 id="book-based-resources-title" class="text-section-title"><?php esc_html_e('Resources for Every Charlotte & Henry Adventure', 'brave-hearts'); ?></h2>
      <p class="component-heading__intro text-lead"><?php esc_html_e('Each destination creates a natural path into real science, geography, vocabulary, conservation, discussion, and writing.', 'brave-hearts'); ?></p>
    </header>
    <div class="teacher-book-grid">
      <?php foreach ($adventures as $key => $adventure): ?>
        <?php
        $card = array_merge($adventure, $book_resources[$key], [
            'resources_url' => bhp_get_safe_link_url($teacher_field($key . '_resources_url', '')),
        ]);
        get_template_part('template-parts/teachers/book-resource-card', null, $card);
        ?>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section id="read-aloud-visits" class="teachers-section teacher-read-aloud section" aria-labelledby="read-aloud-visits-title">
  <div class="container teacher-read-aloud__layout">
    <div class="teacher-read-aloud__content">
      <?php /* ⭐ 1.19.269 item 5 (founder ruling, 2026-08-19) — REMOVED, restates "Read-Alouds and Author Visits":
              <p class="component-heading__eyebrow"><?php esc_html_e('School-year outreach', 'brave-hearts'); ?></p>
         The keep/remove test is stated once, in full, in `page-about.php`. */ ?>
      <h2 id="read-aloud-visits-title" class="text-section-title"><?php esc_html_e('Read-Alouds and Author Visits', 'brave-hearts'); ?></h2>
      <p class="text-lead"><?php esc_html_e('Brave Hearts Publishing supports classroom read-alouds and author visits where scheduling and location allow.', 'brave-hearts'); ?></p>
      <p><?php esc_html_e('Visits can connect a Charlotte & Henry story to real geography, science, vocabulary, courage, and questions students are excited to explore together.', 'brave-hearts'); ?></p>
    </div>
    <div class="teacher-read-aloud__action">
      <a class="btn btn-primary" href="<?php echo esc_url($read_aloud_url); ?>"><?php esc_html_e('Request a Read-Aloud', 'brave-hearts'); ?></a>
      <p><?php esc_html_e('Availability depends on location, timing, audience, and school-year schedule.', 'brave-hearts'); ?></p>
    </div>
  </div>
</section>

<div id="teacher-email-signup" class="container teacher-signup-wrap section section--muted">
  <?php get_template_part('template-parts/acquisition/teacher-resource-signup', null, [
      'id'           => 'teacher-resources-signup',
      'lead_magnet'  => 'teacher_resources',
      'source_page'  => get_permalink($page_id),
      'title'        => __('Bring the adventure into your classroom', 'brave-hearts'),
      'text'         => __('Join the teacher list for resource updates, read-aloud ideas, classroom printables, and new-book news.', 'brave-hearts'),
      'submit_label' => __('Get Teacher Resource Updates', 'brave-hearts'),
  ]); ?>
</div>

<section id="teachers-final-cta" class="teachers-final-cta final-cta section" aria-labelledby="teachers-final-cta-title">
  <div class="container container--content final-cta__inner">
    <?php /* ⭐ 1.19.269 item 5 (founder ruling, 2026-08-19) — REMOVED, a slogan above a CTA:
            <p class="component-heading__eyebrow"><?php esc_html_e('Books first. Learning always.', 'brave-hearts'); ?></p>
       The keep/remove test is stated once, in full, in `page-about.php`. */ ?>
    <h2 id="teachers-final-cta-title" class="text-section-title"><?php esc_html_e('Start a Classroom Adventure', 'brave-hearts'); ?></h2>
    <p class="text-lead final-cta__text"><?php esc_html_e('Choose a book, invite a read-aloud, or join the Adventure Club for future resources and new adventures.', 'brave-hearts'); ?></p>
    <div class="final-cta__actions cluster">
      <a class="btn btn-primary" href="<?php echo esc_url(home_url('/books/')); ?>"><?php esc_html_e('Shop the Books', 'brave-hearts'); ?></a>
      <a class="btn btn-secondary" href="<?php echo esc_url($read_aloud_url); ?>"><?php esc_html_e('Request a Read-Aloud', 'brave-hearts'); ?></a>
      <a class="btn btn-outline" href="<?php echo esc_url(home_url('/reluctant-reader-adventure-kit/')); ?>"><?php esc_html_e('Join the Adventure Club', 'brave-hearts'); ?></a>
    </div>
  </div>
</section>

<?php
// Finding #16: progressive-disclosure enhancement for the guide lists. Runs
// only when JS is available, so no-JS users and crawlers still get every card
// (all cards are already in the HTML above). Collapses each long grid to its
// first 6 cards and injects an accessible toggle (aria-expanded / aria-controls).
?>
<script>
(function () {
  var grids = document.querySelectorAll('.guide-article-grid--collapsible');
  Array.prototype.forEach.call(grids, function (grid, i) {
    var cards = grid.querySelectorAll('.guide-article-card');
    var initial = parseInt(grid.getAttribute('data-disclosure-initial'), 10) || 6;
    if (cards.length <= initial) { return; }
    if (!grid.id) { grid.id = 'bhp-guide-grid-' + i; }
    grid.classList.add('is-collapsed');
    var moreLabel = 'View all ' + cards.length + ' field notes';
    var lessLabel = 'Show fewer field notes';
    var btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'guide-disclosure-toggle';
    btn.setAttribute('aria-expanded', 'false');
    btn.setAttribute('aria-controls', grid.id);
    btn.textContent = moreLabel;
    grid.parentNode.insertBefore(btn, grid.nextSibling);
    btn.addEventListener('click', function () {
      var collapsed = grid.classList.toggle('is-collapsed');
      btn.setAttribute('aria-expanded', String(!collapsed));
      btn.textContent = collapsed ? moreLabel : lessLabel;
      if (collapsed) { btn.scrollIntoView({ block: 'center' }); }
    });
  });
})();
</script>
<?php get_footer(); ?>
