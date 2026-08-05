<?php
/**
 * Template Name: Find Your Adventure Quiz
 * Description: Canonical, permanent home for the "Find Your Adventure"
 * audience-routing quiz (sitewide quiz routing project, 2026-07-17).
 *
 * Uses the same template-parts/quiz/audience-quiz.php component as the
 * homepage -- no duplicated quiz markup or logic. `entry_location` is set
 * to 'quiz_page' so the quiz's own outbound UTM (utm_content) reflects
 * that this interaction happened on the dedicated quiz page rather than
 * embedded on the homepage.
 */
defined('ABSPATH') || exit;
get_header();

if (have_posts()) {
    the_post();
}
?>
<section id="find-your-adventure-intro" class="homepage-section" aria-labelledby="find-your-adventure-intro-title">
  <div class="container" style="max-width:640px;margin:0 auto;text-align:center;">
    <p class="component-heading__eyebrow"><?php esc_html_e('Find Your Adventure', 'brave-hearts'); ?></p>
    <h1 id="find-your-adventure-intro-title" class="text-section-title"><?php esc_html_e('Two Quick Questions. The Right Books and Free Resource.', 'brave-hearts'); ?></h1>
    <p class="component-heading__intro text-lead"><?php esc_html_e('Whether you’re a parent, an educator, shopping for a gift, or part of a community organization, this short quiz points you to the free resource and books that fit best.', 'brave-hearts'); ?></p>
  </div>
</section>

<?php get_template_part('template-parts/quiz/audience-quiz', null, ['entry_location' => 'quiz_page']); ?>

<?php get_footer(); ?>
