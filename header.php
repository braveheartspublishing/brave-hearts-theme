<?php
/**
 * Brave Hearts Publishing — Header
 */
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="profile" href="https://gmpg.org/xfn/11">
<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="skip-link screen-reader-text" href="#main"><?php esc_html_e('Skip to content', 'brave-hearts'); ?></a>

<div class="site-wrapper">

<header class="site-header" role="banner">
  <div class="header-inner">
    <div class="site-logo">
      <?php if (has_custom_logo()): ?>
        <?php the_custom_logo(); ?>
      <?php else: ?>
        <a href="<?php echo esc_url(home_url('/')); ?>">
          <?php bloginfo('name'); ?>
          <span class="tagline">Big Places. Brave Hearts.</span>
        </a>
      <?php endif; ?>
    </div>

    <button class="nav-toggle" aria-label="<?php esc_attr_e('Toggle navigation', 'brave-hearts'); ?>" aria-controls="primary-navigation" aria-expanded="false">&#9776;</button>

    <nav class="site-nav" id="primary-navigation" role="navigation" aria-label="<?php esc_attr_e('Primary navigation', 'brave-hearts'); ?>">
      <?php
      wp_nav_menu([
        'theme_location' => 'primary',
        'menu_class'     => '',
        'container'      => false,
        'fallback_cb'    => 'bhp_fallback_menu',
      ]);
      ?>
    </nav>

    <a class="header-expedition-cta" href="<?php echo esc_url(home_url('/#adventure-club')); ?>">
      <?php esc_html_e('Join the Expedition', 'brave-hearts'); ?>
    </a>
  </div>
</header>

<main class="site-main" id="main">
