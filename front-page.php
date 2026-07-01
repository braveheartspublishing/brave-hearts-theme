<?php
/**
 * Brave Hearts Publishing — Adventure Gateway Homepage
 *
 * Content can be overridden with public bhp_home_* custom fields on the
 * static front page. Repeating card collections are filterable for future
 * structured-content integrations.
 */
defined('ABSPATH') || exit;

get_header();

if (have_posts()) {
    the_post();
}

$page_id = get_queried_object_id();

// Load the live book collection once for the hero preview, destinations, and book grid.
$featured_books = bhp_get_homepage_books(-1);
$find_home_book = static function ($destination) use ($featured_books) {
    $fallback = [];
    foreach ($featured_books as $book) {
        if (stripos($book['title'], $destination) !== false) {
            $formats = is_array($book['formats'] ?? null) ? $book['formats'] : [];
            if (in_array('Paperback', $formats, true) || stripos($book['title'], 'paperback') !== false) {
                return $book;
            }
            if (!$fallback) {
                $fallback = $book;
            }
        }
    }
    return $fallback;
};

$hero_preview_books = array_values(array_filter([
    $find_home_book('Mariana Trench'),
    $find_home_book('Mount Everest'),
    $find_home_book('Amazon'),
], static function ($book) {
    return !empty($book['image_id']) && !empty($book['url']) && !empty($book['title']);
}));
$hero_preview_ids = array_map(static function ($book) {
    return (int) ($book['product_id'] ?? 0);
}, $hero_preview_books);
foreach ($featured_books as $book) {
    $book_id = (int) ($book['product_id'] ?? 0);
    if (count($hero_preview_books) >= 3) {
        break;
    }
    if (!empty($book['image_id']) && !empty($book['url']) && !empty($book['title']) && !in_array($book_id, $hero_preview_ids, true)) {
        $hero_preview_books[] = $book;
        $hero_preview_ids[] = $book_id;
    }
}

// 1. Hero: begin with wonder and invite visitors into the real world.
$hero_title = bhp_get_homepage_field('hero_title', __("Big Places.\nBrave Hearts.", 'brave-hearts'));
if (preg_match('/^Big Places\.\s*Brave Hearts\.$/i', trim($hero_title))) {
    $hero_title = __("Big Places.\nBrave Hearts.", 'brave-hearts');
}
$hero_eyebrow = bhp_get_homepage_field('hero_eyebrow', __('Stories that begin on the page and continue outside', 'brave-hearts'));
if (trim($hero_eyebrow) === 'Bridge books for ages 6–9') {
    $hero_eyebrow = __('Stories that begin on the page and continue outside', 'brave-hearts');
}
$hero_text = __('<p>A child closes the book. The next morning, the sky, birds, and trees are the same—but now they notice, wonder, ask, and explore.</p><ul class="home-hero__destinations"><li>Nearly 11 km deep</li><li>8,849 m high</li><li>A living canopy</li></ul>', 'brave-hearts');

if ($hero_preview_books) {
    ob_start();
    ?>
    <div class="home-hero__book-preview" role="group" aria-labelledby="home-hero-books-label">
      <p id="home-hero-books-label" class="home-hero__book-preview-label"><?php esc_html_e('Real places. Doors into wonder.', 'brave-hearts'); ?></p>
      <ul class="home-hero__book-stack">
        <?php foreach (array_slice($hero_preview_books, 0, 3) as $book_index => $book): ?>
          <li>
            <a href="<?php echo esc_url($book['url']); ?>">
              <?php echo wp_get_attachment_image((int) $book['image_id'], 'bhp-book-card', false, [
                  'class'   => 'home-hero__book-cover',
                  'alt'     => $book['image_alt'] ?: sprintf(__('%s book cover', 'brave-hearts'), $book['title']),
                  'loading' => $book_index === 0 ? 'eager' : 'lazy',
                  'sizes'   => '(max-width: 640px) 28vw, 180px',
              ]); ?>
              <span><?php echo esc_html($book['title']); ?></span>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php
    $hero_text .= ob_get_clean();
}

$hero_primary_label = bhp_get_homepage_field('hero_primary_label', __('Choose Your First Adventure', 'brave-hearts'));
if (in_array(trim($hero_primary_label), ['Shop the Books', 'Choose a Real-World Adventure'], true)) {
    $hero_primary_label = __('Choose Your First Adventure', 'brave-hearts');
}

get_template_part('template-parts/components/hero', null, [
    'id'             => 'home-hero',
    'eyebrow'        => $hero_eyebrow,
    'title'          => $hero_title,
    'text'           => $hero_text,
    'image_id'       => (int) bhp_get_homepage_field('hero_image_id', 0),
    'class'          => $hero_preview_books ? 'home-hero--with-books' : '',
    'primary_link'   => [
        'url'   => bhp_get_homepage_field('hero_primary_url', '#explore-world'),
        'label' => $hero_primary_label,
    ],
    'secondary_link' => [],
]);

// 2. Philosophy: connect the opening sense of wonder to the purpose behind every story.
?>
<section id="home-philosophy" class="homepage-section home-philosophy section" aria-labelledby="home-philosophy-title">
  <div class="container home-philosophy__inner">
    <header class="component-heading component-heading--center home-philosophy__heading">
      <p class="component-heading__eyebrow"><?php echo esc_html(bhp_get_homepage_field('philosophy_eyebrow', __('Our philosophy', 'brave-hearts'))); ?></p>
      <h2 id="home-philosophy-title" class="text-section-title"><?php echo esc_html(bhp_get_homepage_field('philosophy_title', __('Nature is the greatest classroom on Earth.', 'brave-hearts'))); ?></h2>
      <p class="component-heading__intro text-lead"><?php echo esc_html(bhp_get_homepage_field('philosophy_intro', __('A Brave Hearts story is a beginning: adventure awakens curiosity, truth gives it somewhere to go, and character helps a child carry each discovery into the world.', 'brave-hearts'))); ?></p>
    </header>
    <ul class="home-philosophy__pillars" aria-label="<?php esc_attr_e('How Brave Hearts stories guide young readers', 'brave-hearts'); ?>">
      <li>
        <span class="home-philosophy__sequence" aria-hidden="true">01</span>
        <span><?php esc_html_e('Adventure opens the door.', 'brave-hearts'); ?></span>
      </li>
      <li>
        <span class="home-philosophy__sequence" aria-hidden="true">02</span>
        <span><?php esc_html_e('Truth deepens the wonder.', 'brave-hearts'); ?></span>
      </li>
      <li>
        <span class="home-philosophy__sequence" aria-hidden="true">03</span>
        <span><?php esc_html_e('Character carries it home.', 'brave-hearts'); ?></span>
      </li>
    </ul>
    <p class="home-philosophy__closing"><?php esc_html_e('The last page is not the end.', 'brave-hearts'); ?><br><strong><?php esc_html_e('It is an invitation to look up.', 'brave-hearts'); ?></strong></p>
  </div>
</section>
<?php

// 3. Founder origin: a compact trust bridge from philosophy to the adventures.
?>
<section id="first-reader" class="homepage-section home-origin" aria-labelledby="first-reader-title">
  <div class="container">
    <div class="home-origin__card">
      <div class="home-origin__visual">
        <div class="home-origin__journal">
          <span class="home-origin__journal-kicker"><?php esc_html_e('Field Journal - Entry 01', 'brave-hearts'); ?></span>
          <div class="home-origin__portrait" role="img" aria-label="<?php esc_attr_e('Founder portrait reserved for an approved photograph', 'brave-hearts'); ?>">
            <span aria-hidden="true">&#9638;</span>
            <em><?php esc_html_e('A candid photograph of Andrew, outdoors - to be added.', 'brave-hearts'); ?></em>
            <small><?php esc_html_e('Portrait reserved', 'brave-hearts'); ?></small>
          </div>
          <strong><?php esc_html_e('One child. One loyal dog.', 'brave-hearts'); ?><br><?php esc_html_e('One lasting gift.', 'brave-hearts'); ?></strong>
          <span class="home-origin__journal-meta"><?php esc_html_e('Andrew - Founder', 'brave-hearts'); ?></span>
        </div>
      </div>
      <div class="home-origin__content">
        <p class="component-heading__eyebrow"><?php esc_html_e('The first reader', 'brave-hearts'); ?></p>
        <h2 id="first-reader-title"><?php esc_html_e('It Began With One Child and One Loyal Dog', 'brave-hearts'); ?></h2>
        <p><?php esc_html_e('Before there was a company, a website, or a single illustration, there was one little girl. Her name is Charlotte. She is my niece - and she is real.', 'brave-hearts'); ?></p>
        <p><?php esc_html_e('Henry is real, too. He carries a piece of Toby - the small dog of my own childhood, who used to climb into my backpack because he wanted to come along. A companion who never solves the problem for you, but never leaves while you solve it yourself.', 'brave-hearts'); ?></p>
        <blockquote><?php esc_html_e('I wanted to give her something that would outlast every birthday - not just a story, but a compass. A way of looking at the world.', 'brave-hearts'); ?></blockquote>
        <p class="home-origin__byline"><?php esc_html_e('Andrew - Founder, Brave Hearts Publishing', 'brave-hearts'); ?></p>
        <a class="home-origin__link" href="<?php echo esc_url(home_url('/about/')); ?>"><?php esc_html_e('Read the story behind Brave Hearts', 'brave-hearts'); ?> <span aria-hidden="true">&rarr;</span></a>
      </div>
    </div>
  </div>
</section>
<?php

// 4. Explore the World: destination gateways remain filterable as the series grows.
$mariana_book = $find_home_book('Mariana Trench');
$everest_book = $find_home_book('Mount Everest');
$amazon_book = $find_home_book('Amazon');

$adventure_cards = apply_filters('bhp_homepage_adventure_cards', [
    [
        'eyebrow'   => __('11°21\'N 142°12\'E - 10,935 m down', 'brave-hearts'),
        'title'     => __('Mariana Trench', 'brave-hearts'),
        'text'      => __('<p class="hub-card__question">What glows where sunlight has never reached?</p>', 'brave-hearts'),
        'url'       => !empty($mariana_book['url']) ? $mariana_book['url'] : home_url('/books/'),
        'cta_label' => __('Explore the depths', 'brave-hearts'),
        'image_id'  => $mariana_book['image_id'] ?? 0,
        'class'     => 'hub-card--destination',
    ],
    [
        'eyebrow'   => __('27°59\'N 86°55\'E - 8,849 m up', 'brave-hearts'),
        'title'     => __('Mount Everest', 'brave-hearts'),
        'text'      => __('<p class="hub-card__question">What can you see from the top of the world?</p>', 'brave-hearts'),
        'url'       => !empty($everest_book['url']) ? $everest_book['url'] : home_url('/books/'),
        'cta_label' => __('Explore the heights', 'brave-hearts'),
        'image_id'  => $everest_book['image_id'] ?? 0,
        'class'     => 'hub-card--destination',
    ],
    [
        'eyebrow'   => __('3°28\'S 62°13\'W - The green heart', 'brave-hearts'),
        'title'     => __('Amazon Rainforest', 'brave-hearts'),
        'text'      => __('<p class="hub-card__question">What secrets live in the world\'s green heart?</p>', 'brave-hearts'),
        'url'       => !empty($amazon_book['url']) ? $amazon_book['url'] : home_url('/books/'),
        'cta_label' => __('Explore the canopy', 'brave-hearts'),
        'image_id'  => $amazon_book['image_id'] ?? 0,
        'class'     => 'hub-card--destination',
    ],
], $page_id);
?>
<section id="explore-world" class="homepage-section home-destinations section" aria-labelledby="explore-world-title">
  <div class="container">
    <header class="component-heading">
      <p class="component-heading__eyebrow"><?php echo esc_html(bhp_get_homepage_field('explore_eyebrow', __('From the deepest ocean to the highest mountain', 'brave-hearts'))); ?></p>
      <h2 id="explore-world-title" class="text-section-title"><?php echo esc_html(bhp_get_homepage_field('explore_title', __('Choose Your Adventure', 'brave-hearts'))); ?></h2>
      <p class="component-heading__intro text-lead"><?php echo esc_html(bhp_get_homepage_field('explore_intro', __('Each adventure begins with a real place - and opens a door into wonder.', 'brave-hearts'))); ?></p>
      <p class="home-destinations__promise"><?php esc_html_e('The question comes first. The book is the passport.', 'brave-hearts'); ?></p>
    </header>
    <div class="grid grid--3 homepage-grid homepage-grid--adventures">
      <?php foreach ($adventure_cards as $card): ?>
        <?php get_template_part('template-parts/components/hub-card', null, $card); ?>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section id="featured-books" class="homepage-section home-books-path" aria-labelledby="home-books-path-title">
  <div class="container home-books-path__inner">
    <div>
      <h2 id="home-books-path-title"><?php esc_html_e('Find the Adventure That Fits Your Reader', 'brave-hearts'); ?></h2>
      <p><?php esc_html_e('Picture books - Chapter books - Editions - Gift sets', 'brave-hearts'); ?></p>
    </div>
    <a class="btn btn-secondary" href="<?php echo esc_url(home_url('/books/')); ?>"><?php esc_html_e('Explore every format and edition', 'brave-hearts'); ?></a>
  </div>
</section>

<?php
// 6. Learning Hub: educational depth extends curiosity beyond the books.
$learning_cards = apply_filters('bhp_homepage_learning_cards', [
    ['title' => __('Animals', 'brave-hearts'), 'text' => __('Meet the wildlife behind the adventures.', 'brave-hearts'), 'link' => ['url' => bhp_get_learning_category_url('animals'), 'label' => __('Explore animals', 'brave-hearts')], 'class' => 'feature-card--field-note'],
    ['title' => __('Science', 'brave-hearts'), 'text' => __('Understand the forces shaping our world.', 'brave-hearts'), 'link' => ['url' => bhp_get_learning_category_url('science'), 'label' => __('Explore science', 'brave-hearts')], 'class' => 'feature-card--field-note'],
    ['title' => __('Geography', 'brave-hearts'), 'text' => __('Find the real places behind every journey.', 'brave-hearts'), 'link' => ['url' => bhp_get_learning_category_url('geography'), 'label' => __('Explore geography', 'brave-hearts')], 'class' => 'feature-card--field-note'],
    ['title' => __('Conservation', 'brave-hearts'), 'text' => __('Learn how curiosity can become care.', 'brave-hearts'), 'link' => ['url' => bhp_get_learning_category_url('conservation'), 'label' => __('Explore conservation', 'brave-hearts')], 'class' => 'feature-card--field-note'],
    ['title' => __('Explorers', 'brave-hearts'), 'text' => __('Meet courageous thinkers past and present.', 'brave-hearts'), 'link' => ['url' => bhp_get_learning_category_url('explorers'), 'label' => __('Meet explorers', 'brave-hearts')], 'class' => 'feature-card--field-note'],
    ['title' => __('Activities', 'brave-hearts'), 'text' => __('Keep learning with hands-on discoveries.', 'brave-hearts'), 'link' => ['url' => bhp_get_learning_category_url('activities'), 'label' => __('Try an activity', 'brave-hearts')], 'class' => 'feature-card--field-note'],
], $page_id);
foreach ($learning_cards as &$learning_card) {
    $topic_slug = sanitize_title($learning_card['title'] ?? '');
    $fallback_url = bhp_get_learning_category_url($topic_slug);
    $learning_link = is_array($learning_card['link'] ?? null) ? $learning_card['link'] : [];
    $learning_link['url'] = bhp_get_safe_link_url($learning_link['url'] ?? '', $fallback_url);
    $learning_card['link'] = $learning_link;
}
unset($learning_card);
?>
<section id="learning-hub" class="homepage-section learning-hub--ecosystem section section--muted" aria-labelledby="learning-hub-title">
  <div class="container">
    <header class="component-heading component-heading--center">
      <p class="component-heading__eyebrow"><?php echo esc_html(bhp_get_homepage_field('learning_eyebrow', __('The Learning Hub', 'brave-hearts'))); ?></p>
      <h2 id="learning-hub-title" class="text-section-title"><?php echo esc_html(bhp_get_homepage_field('learning_title', __('Follow Curiosity Into the Real World', 'brave-hearts'))); ?></h2>
      <p class="component-heading__intro text-lead"><?php echo esc_html(bhp_get_homepage_field('learning_intro', __('Field notes, guides, and activities that turn a story into a lifetime of looking closer - at home and in the classroom.', 'brave-hearts'))); ?></p>
    </header>
    <div class="grid grid--3 homepage-grid homepage-grid--learning">
      <?php foreach ($learning_cards as $card): ?>
        <?php get_template_part('template-parts/components/feature-card', null, $card); ?>
      <?php endforeach; ?>
    </div>
    <div class="component-section-action">
      <a class="btn btn-secondary" href="<?php echo esc_url(home_url('/blog/')); ?>"><?php esc_html_e('Open the Learning Hub', 'brave-hearts'); ?></a>
    </div>
  </div>
</section>

<?php // 7. Teachers and Families. ?>
<section id="teacher-resources" class="homepage-section home-together section" aria-labelledby="home-together-title">
  <div class="container home-together__inner">
    <p class="component-heading__eyebrow"><?php esc_html_e('Teachers & Families', 'brave-hearts'); ?></p>
    <h2 id="home-together-title" class="text-section-title"><?php esc_html_e('Continue the Adventure Together', 'brave-hearts'); ?></h2>
    <p class="home-together__intro"><?php esc_html_e('The best classroom has no walls. Bring Charlotte and Henry\'s expeditions into your room and your home with guides built to spark real questions - not fill worksheets.', 'brave-hearts'); ?></p>
    <div class="home-together__paths">
      <div>
        <h3><?php esc_html_e('For Classrooms', 'brave-hearts'); ?></h3>
        <p><?php esc_html_e('Read-alouds, discussion prompts, and expedition projects for young explorers.', 'brave-hearts'); ?></p>
      </div>
      <div>
        <h3><?php esc_html_e('For Families', 'brave-hearts'); ?></h3>
        <p><?php esc_html_e('Weekend adventures and backyard field notes to try after the last page.', 'brave-hearts'); ?></p>
      </div>
    </div>
    <a class="btn btn-primary" href="<?php echo esc_url(home_url('/teachers/')); ?>"><?php esc_html_e('Explore teacher & family guides', 'brave-hearts'); ?></a>
  </div>
</section>

<?php // 8. Trust is expressed through verifiable publishing principles, not invented reviews. ?>
<section id="trust" class="homepage-section home-trust section" aria-labelledby="home-trust-title">
  <div class="container">
    <header class="component-heading component-heading--center">
      <p class="component-heading__eyebrow"><?php esc_html_e('Why parents trust Brave Hearts', 'brave-hearts'); ?></p>
      <h2 id="home-trust-title" class="text-section-title"><?php esc_html_e('Wonder First. Learning Follows - Naturally.', 'brave-hearts'); ?></h2>
    </header>
    <div class="home-trust__pillars">
      <article><span aria-hidden="true">△</span><h3><?php esc_html_e('Real places, real research', 'brave-hearts'); ?></h3><p><?php esc_html_e('Every destination is a place a child could truly stand one day. The science is checked, not invented.', 'brave-hearts'); ?></p></article>
      <article><span aria-hidden="true">◇</span><h3><?php esc_html_e('Character that carries home', 'brave-hearts'); ?></h3><p><?php esc_html_e('Courage, patience, and kindness are lived through the story - never lectured at the reader.', 'brave-hearts'); ?></p></article>
      <article><span aria-hidden="true">⊙</span><h3><?php esc_html_e('Screens down, eyes up', 'brave-hearts'); ?></h3><p><?php esc_html_e('The books are written to end outside - a walk, a sky, a question a child brings to you.', 'brave-hearts'); ?></p></article>
    </div>
    <div class="home-trust__share">
      <p><?php esc_html_e("Have your reader's story to tell?", 'brave-hearts'); ?></p>
      <a href="<?php echo esc_url(home_url('/contact/')); ?>"><?php esc_html_e('Share it with the expedition', 'brave-hearts'); ?> <span aria-hidden="true">&rarr;</span></a>
    </div>
  </div>
</section>

<?php // 9. Adventure Club expedition signup. ?>
get_template_part('template-parts/components/newsletter-signup', null, [
    'id'                => 'adventure-club',
    'eyebrow'           => bhp_get_homepage_field('newsletter_eyebrow', __('The Adventure Club', 'brave-hearts')),
    'title'             => bhp_get_homepage_field('newsletter_title', __('Join the Expedition', 'brave-hearts')),
    'text'              => bhp_get_homepage_field('newsletter_text', __('Enlist with a small band of families and teachers who believe the real world is still wild enough. No noise - just wonder, delivered.', 'brave-hearts')),
    'benefits'          => [
        ['title' => __('Be first to the next adventure', 'brave-hearts'), 'text' => __('We will write the moment Charlotte & Henry set out somewhere new.', 'brave-hearts')],
        ['title' => __('Free printable expeditions', 'brave-hearts'), 'text' => __('Teacher guides, family activities, and exploration field notes - yours to keep.', 'brave-hearts')],
    ],
    'form_action'       => bhp_get_homepage_field('newsletter_form_action', ''),
    'email_name'        => bhp_get_homepage_field('newsletter_email_name', 'email'),
    'email_label'       => bhp_get_homepage_field('newsletter_email_label', __('Email address', 'brave-hearts')),
    'email_placeholder' => bhp_get_homepage_field('newsletter_placeholder', __('Your email - no noise, just wonder', 'brave-hearts')),
    'submit_label'      => bhp_get_homepage_field('newsletter_submit_label', __('Join the Expedition', 'brave-hearts')),
    'privacy_text'      => bhp_get_homepage_field('newsletter_privacy', __('A field pass to wonder - unsubscribe anytime.', 'brave-hearts')),
    'audience_type'    => 'parents_families',
    'lead_magnet'      => 'explorer_passport',
    'source_page'      => get_permalink($page_id),
    'hidden_fields'     => apply_filters('bhp_homepage_newsletter_hidden_fields', [], $page_id),
    'class'             => 'newsletter-signup--expedition',
]);

// 10. Footer.
get_footer();
