<?php
/**
 * Template Name: Brave Hearts Contact Page
 * Description: General contact, read-aloud requests, school outreach, and media inquiries.
 */
defined('ABSPATH') || exit;
get_header();

if (have_posts()) {
    the_post();
}

$page_id = get_queried_object_id();
$contact_field = static function ($key, $fallback = '') use ($page_id) {
    $field_name = 'bhp_contact_' . sanitize_key($key);
    $stored = $page_id ? get_post_meta($page_id, $field_name, true) : '';
    $value = ($stored !== '') ? $stored : $fallback;
    return apply_filters('bhp_contact_field_' . sanitize_key($key), $value, $page_id);
};

$page_url = get_permalink($page_id) ?: home_url('/contact/');

/*
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐⭐ 1.19.394 — "REQUEST A READ-ALOUD" LEAVES THE CONTACT PAGE.
 *      `CYCLE179-CX-BUILD-394`, item 6.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⭐ ANDREW SIGNORE, 2026-09-07, verbatim. ⚠ RELAYED through the supervising
 *    session — NOT witnessed first-hand here (Standing Rules §9.2 rule 2):
 *    *"The request a read aloud on the contact page needs to go to the read
 *    aloud page - it currently stays on the contact us page - incorrect."*
 *
 * ⛔ THE SUPERSEDED LINE, PRESERVED SO THE MOVEMENT IS VISIBLE:
 *      ~~$read_aloud_url = add_query_arg('inquiry', 'read-aloud', $page_url) . '#contact-form';~~
 *
 *    VERIFIED LIVE on staging 2026-09-07 before the change: all THREE controls
 *    labelled "Request a Read-Aloud" on `/contact/` resolved to
 *    `https://staging2.braveheartspublishing.com/contact/?inquiry=read-aloud#contact-form`
 *    — the page the reader is already standing on. The button reloaded the
 *    same page and scrolled down. That is the defect, and it is a trust
 *    defect rather than a cosmetic one: a control that appears to do nothing
 *    teaches a parent that the buttons on this site do not work.
 *
 * ⛔⛔ THE DESTINATION IS `/school-read-alouds/`, NOT `/read-aloud/`, AND THE
 *     DIFFERENCE MATTERS ENOUGH TO WRITE DOWN. The build brief guessed
 *     `/read-aloud/`. ⚠ THAT GUESS IS WRONG, and it was checked rather than
 *     followed. Both pages were fetched live on staging, 2026-09-07:
 *
 *       /read-aloud/          H1 "You met Charlotte & Henry today!"
 *                             `page-read-aloud.php` — the TAKE-HOME landing
 *                             page a child reaches by scanning the QR printed
 *                             on the coloring sheet AFTER a visit. It cannot
 *                             book anything.
 *       /school-read-alouds/  H1 "Book a free read-aloud"
 *                             `page-school-read-alouds.php` — the TEACHER
 *                             page with the scheduler on it. This is where a
 *                             request is actually made.
 *
 *     ⭐ AND THE SITE ALREADY AGREES: the primary navigation item labelled
 *        "Read-Alouds" points at `/school-read-alouds/` (`functions.php`
 *        line ~1532). Sending the button somewhere else would have created
 *        the very page-to-page inconsistency he is objecting to.
 *
 * ⚠ ROUTED, NOT DECIDED HERE: three other surfaces carry a read-aloud control
 *   that still lands on the contact form — `page-mariana-guide-teacher.php`
 *   ("Request an Author Visit") and `page-mariana-guide-thank-you.php`
 *   ("Request a Read-Aloud or Author Visit"), both of which name an AUTHOR
 *   VISIT and may belong at `/author-visits/` instead. Those are reported
 *   upward for Andrew rather than changed on this desk's judgement.
 *
 * ⛔ `$school_url` AND `$general_url` ARE UNTOUCHED. A schools-and-media
 *    inquiry and a general question both genuinely belong in the form on this
 *    page, and nothing in his instruction reaches them.
 */
$read_aloud_url = home_url('/school-read-alouds/');
$school_url = add_query_arg('inquiry', 'school-library', $page_url) . '#contact-form';
$general_url = $page_url . '#contact-form';
$contact_email = sanitize_email($contact_field('email', 'andrew@braveheartspublishing.com'));
$form_action = bhp_get_contact_form_action($contact_field('form_action', ''));
$form_ready = (bool) $form_action;
$hero_image_id = (int) $contact_field('hero_image_id', 0);

get_template_part('template-parts/components/hero', null, [
    'id'       => 'contact-hero',
    'class'    => 'contact-hero',
    'eyebrow'  => __('Brave Hearts Publishing', 'brave-hearts'),
    'title'    => __('Let’s Start the Next Adventure', 'brave-hearts'),
    'text'     => __('Questions about books, classroom visits, read-alouds, or Brave Hearts Publishing? Reach out below.', 'brave-hearts'),
    'image_id' => $hero_image_id,
    'primary_link' => [
        'url'   => $general_url,
        'label' => __('Contact Us', 'brave-hearts'),
    ],
    'secondary_link' => [
        'url'   => $read_aloud_url,
        'label' => __('Request a Read-Aloud', 'brave-hearts'),
    ],
]);

$contact_paths = apply_filters('bhp_contact_paths', [
    [
        'title' => __('General Questions', 'brave-hearts'),
        'text'  => __('Ask about books, orders, the Adventure Club, upcoming releases, or Brave Hearts Publishing.', 'brave-hearts'),
        'link'  => ['url' => $general_url, 'label' => __('Send a question', 'brave-hearts')],
        'class' => 'contact-path-card--general',
    ],
    [
        'title' => __('Read-Aloud Visits', 'brave-hearts'),
        'text'  => __('Tell us about your classroom, library, homeschool group, audience, location, and preferred timing.', 'brave-hearts'),
        'link'  => ['url' => $read_aloud_url, 'label' => __('Request a read-aloud', 'brave-hearts')],
        'class' => 'contact-path-card--read-aloud',
    ],
    [
        'title' => __('Schools, Libraries & Media', 'brave-hearts'),
        'text'  => __('Reach out about school outreach, library connections, classroom packs, media, podcasts, and partnerships.', 'brave-hearts'),
        'link'  => ['url' => $school_url, 'label' => __('Start an inquiry', 'brave-hearts')],
        'class' => 'contact-path-card--organizations',
    ],
], $page_id);
?>
<section id="contact-paths" class="contact-section section" aria-labelledby="contact-paths-title">
  <div class="container">
    <header class="component-heading component-heading--center">
      <?php /* ⭐ 1.19.269 item 5 (founder ruling, 2026-08-19) — REMOVED, restates "How Can We Help?":
              <p class="component-heading__eyebrow"><?php esc_html_e('Choose the right path', 'brave-hearts'); ?></p>
         The keep/remove test is stated once, in full, in `page-about.php`. */ ?>
      <h2 id="contact-paths-title" class="text-section-title"><?php esc_html_e('How Can We Help?', 'brave-hearts'); ?></h2>
    </header>
    <div class="grid grid--3 contact-path-grid">
      <?php foreach ($contact_paths as $path): ?>
        <?php get_template_part('template-parts/components/feature-card', null, $path); ?>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section id="contact-form" class="contact-section contact-form-section section section--muted" aria-labelledby="contact-form-title">
  <div class="container contact-form-section__layout">
    <div class="contact-form-section__intro">
      <?php /* ⭐ 1.19.269 item 5 (founder ruling, 2026-08-19) — REMOVED, restates "Tell Us About Your Next Adventure":
              <p class="component-heading__eyebrow"><?php esc_html_e('Send a message', 'brave-hearts'); ?></p>
         The keep/remove test is stated once, in full, in `page-about.php`. */ ?>
      <h2 id="contact-form-title" class="text-section-title"><?php esc_html_e('Tell Us About Your Next Adventure', 'brave-hearts'); ?></h2>
      <p class="text-lead"><?php echo esc_html($form_ready
          ? __('Choose the inquiry type that best fits your question. Read-aloud and school links automatically select the matching path.', 'brave-hearts')
          : __('Use the email option to tell us about your question, classroom, library, event, or partnership.', 'brave-hearts'));
      ?></p>
    </div>
    <?php get_template_part('template-parts/contact/contact-form', null, [
        'id'          => 'brave-hearts-contact-form',
        'action'      => $form_action,
        'source_page' => $page_url,
        'fallback_email' => $contact_email,
    ]); ?>
  </div>
</section>

<section id="read-aloud-visits" class="contact-section contact-read-aloud section" aria-labelledby="contact-read-aloud-title">
  <div class="container contact-read-aloud__layout">
    <div class="contact-read-aloud__content">
      <p class="component-heading__eyebrow"><?php esc_html_e('For teachers, schools, and libraries', 'brave-hearts'); ?></p>
      <h2 id="contact-read-aloud-title" class="text-section-title"><?php esc_html_e('Read-Alouds and School Connections', 'brave-hearts'); ?></h2>
      <p class="text-lead"><?php esc_html_e('Brave Hearts supports classroom read-alouds, teacher outreach, and school or library connections where location, timing, and availability allow.', 'brave-hearts'); ?></p>
      <p><?php esc_html_e('Share your audience, school or organization, location, preferred timing, and the Charlotte & Henry adventure you are interested in.', 'brave-hearts'); ?></p>
    </div>
    <div class="contact-read-aloud__action">
      <a class="btn btn-primary" href="<?php echo esc_url($read_aloud_url); ?>"><?php esc_html_e('Request a Read-Aloud', 'brave-hearts'); ?></a>
      <p><?php esc_html_e('Submitting an inquiry does not guarantee availability. Every request will be reviewed individually.', 'brave-hearts'); ?></p>
    </div>
  </div>
</section>

<?php if ($form_ready): ?>
  <section id="direct-contact" class="contact-section contact-direct section section--muted" aria-labelledby="direct-contact-title">
  <div class="container container--content contact-direct__inner">
    <?php /* ⭐ 1.19.269 item 5 (founder ruling, 2026-08-19) — REMOVED, restates "Prefer Email?":
            <p class="component-heading__eyebrow"><?php esc_html_e('Direct contact', 'brave-hearts'); ?></p>
       The keep/remove test is stated once, in full, in `page-about.php`. */ ?>
    <h2 id="direct-contact-title" class="text-section-title"><?php esc_html_e('Prefer Email?', 'brave-hearts'); ?></h2>
    <p class="text-lead"><?php esc_html_e('You can always reach Brave Hearts Publishing directly by email.', 'brave-hearts'); ?></p>
    <?php if ($contact_email): ?>
      <p class="contact-direct__email"><a href="mailto:<?php echo esc_attr($contact_email); ?>"><?php echo esc_html($contact_email); ?></a></p>
    <?php endif; ?>
    <p class="contact-direct__note"><?php esc_html_e('No phone number or public mailing address is currently provided.', 'brave-hearts'); ?></p>
  </div>
  </section>
<?php endif; ?>

<section id="contact-final-cta" class="contact-final-cta final-cta section" aria-labelledby="contact-final-cta-title">
  <div class="container container--content final-cta__inner">
    <?php /* ⭐ 1.19.269 item 5 (founder ruling, 2026-08-19) — REMOVED, a slogan above a CTA:
            <p class="component-heading__eyebrow"><?php esc_html_e('Continue the adventure', 'brave-hearts'); ?></p>
       The keep/remove test is stated once, in full, in `page-about.php`. */ ?>
    <h2 id="contact-final-cta-title" class="text-section-title"><?php esc_html_e('Explore Brave Hearts Publishing', 'brave-hearts'); ?></h2>
    <div class="final-cta__actions cluster">
      <a class="btn btn-primary" href="<?php echo esc_url(home_url('/books/')); ?>"><?php esc_html_e('Shop the Books', 'brave-hearts'); ?></a>
      <a class="btn btn-secondary" href="<?php echo esc_url(home_url('/teachers/')); ?>"><?php esc_html_e('Explore Educator Guides', 'brave-hearts'); ?></a>
      <a class="btn btn-outline" href="<?php echo esc_url(home_url('/reluctant-reader-adventure-kit/')); ?>"><?php esc_html_e('Join the Adventure Club', 'brave-hearts'); ?></a>
    </div>
  </div>
</section>

<?php get_footer(); ?>
