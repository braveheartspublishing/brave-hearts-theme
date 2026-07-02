<?php
/** Teacher resource signup. */
defined('ABSPATH') || exit;
$args = wp_parse_args($args ?? [], [
    'id' => '', 'title' => '', 'text' => '', 'action' => '', 'lead_magnet' => 'teacher_lesson_plans',
    'source_page' => '', 'submit_label' => '', 'class' => '',
]);
$section_id = $args['id'] ?: wp_unique_id('teacher-signup-');
$heading_id = $section_id . '-title';
$form_action = bhp_get_signup_form_action($args['action'], 'teachers', 'teacher_resources');
?>
<section id="<?php echo esc_attr($section_id); ?>" class="acquisition-panel teacher-signup <?php echo esc_attr(sanitize_html_class($args['class'])); ?>" aria-labelledby="<?php echo esc_attr($heading_id); ?>">
  <div class="acquisition-panel__content">
    <p class="component-heading__eyebrow"><?php echo esc_html($form_action ? __('Free teacher resources', 'brave-hearts') : __('For educators', 'brave-hearts')); ?></p>
    <h2 id="<?php echo esc_attr($heading_id); ?>" class="text-section-title"><?php echo esc_html($args['title'] ?: __('Bring the adventure into your classroom', 'brave-hearts')); ?></h2>
    <div class="acquisition-panel__text"><?php echo wp_kses_post($form_action
        ? ($args['text'] ?: __('Get lesson plans, printables, vocabulary, discussion guides, and read aloud resources.', 'brave-hearts'))
        : __('Questions about using Brave Hearts books in a classroom, library, or homeschool setting are always welcome.', 'brave-hearts'));
    ?></div>
  </div>
  <?php if ($form_action): ?>
    <?php get_template_part('template-parts/acquisition/signup-form', null, [
        'id'              => $section_id . '-form',
        'action'          => $form_action,
        'context'         => 'teacher_resources',
        'audience_type'   => 'teachers',
        'lead_magnet'     => $args['lead_magnet'],
        'source_page'     => $args['source_page'],
        'submit_label'    => $args['submit_label'] ?: __('Get Educator Guide Updates', 'brave-hearts'),
        'privacy_text'    => __('Classroom resources and book news. Unsubscribe anytime.', 'brave-hearts'),
        'aria_labelledby' => $heading_id,
    ]); ?>
  <?php else: ?>
    <div class="acquisition-panel__action">
      <a class="btn btn-primary" href="<?php echo esc_url(add_query_arg('inquiry', 'school-library', home_url('/contact/'))); ?>"><?php esc_html_e('Ask About Educator Guides', 'brave-hearts'); ?></a>
    </div>
  <?php endif; ?>
</section>
