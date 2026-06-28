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

    <button class="nav-toggle" aria-label="Toggle navigation" aria-expanded="false">&#9776;</button>

    <nav class="site-nav" role="navigation" aria-label="Primary navigation">
      <?php
      wp_nav_menu([
        'theme_location' => 'primary',
        'menu_class'     => '',
        'container'      => false,
        'fallback_cb'    => 'bhp_fallback_menu',
      ]);
      ?>
    </nav>
  </div>
</header>

<main class="site-main" id="main">
