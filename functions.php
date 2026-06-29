<?php
/**
 * Brave Hearts Publishing Theme Functions
 * Big Places. Brave Hearts.
 */

defined('ABSPATH') || exit;

// ============================================================
// THEME SETUP
// ============================================================
function bhp_theme_setup() {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('woocommerce');
    add_theme_support('wc-product-gallery-zoom');
    add_theme_support('wc-product-gallery-lightbox');
    add_theme_support('wc-product-gallery-slider');
    add_theme_support('html5', [
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
        'style',
        'script',
    ]);
    add_theme_support('custom-logo', [
        'height'      => 60,
        'width'       => 200,
        'flex-width'  => true,
        'flex-height' => true,
    ]);
    add_theme_support('align-wide');
    add_theme_support('responsive-embeds');
    add_theme_support('wp-block-styles');

    register_nav_menus([
        'primary' => __('Primary Navigation', 'brave-hearts'),
        'footer'  => __('Footer Navigation', 'brave-hearts'),
    ]);
}
add_action('after_setup_theme', 'bhp_theme_setup');

// ============================================================
// ENQUEUE STYLES & SCRIPTS
// ============================================================
function bhp_enqueue_assets() {
    $style_path = get_stylesheet_directory() . '/style.css';
    $nav_path   = get_template_directory() . '/assets/js/nav.js';

    wp_enqueue_style(
        'bhp-style',
        get_stylesheet_uri(),
        [],
        file_exists($style_path) ? filemtime($style_path) : '1.0.0'
    );

    wp_enqueue_style(
        'bhp-google-fonts',
        'https://fonts.googleapis.com/css2?family=Lato:wght@400;700&display=swap',
        [],
        null
    );

    wp_enqueue_script(
        'bhp-nav',
        get_template_directory_uri() . '/assets/js/nav.js',
        [],
        file_exists($nav_path) ? filemtime($nav_path) : '1.0.0',
        true
    );

    if (is_singular() && comments_open() && get_option('thread_comments')) {
        wp_enqueue_script('comment-reply');
    }
}
add_action('wp_enqueue_scripts', 'bhp_enqueue_assets');

// ============================================================
// WIDGET AREAS
// ============================================================
function bhp_register_sidebars() {
    register_sidebar([
        'name'          => __('Footer Column 3', 'brave-hearts'),
        'id'            => 'footer-3',
        'description'   => __('Add widgets for footer column 3.', 'brave-hearts'),
        'before_widget' => '<div class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h4 class="widget-title">',
        'after_title'   => '</h4>',
    ]);
}
add_action('widgets_init', 'bhp_register_sidebars');

// ============================================================
// WOOCOMMERCE
// ============================================================
remove_action('woocommerce_sidebar', 'woocommerce_get_sidebar', 10);

function bhp_woo_columns($columns) {
    return 3;
}
add_filter('loop_shop_columns', 'bhp_woo_columns');

function bhp_woo_per_page($num) {
    return 12;
}
add_filter('loop_shop_per_page', 'bhp_woo_per_page', 20);

// ============================================================
// EXCERPTS AND DOCUMENT TITLE
// ============================================================
function bhp_excerpt_length($length) {
    return 30;
}
add_filter('excerpt_length', 'bhp_excerpt_length');

function bhp_document_title_separator($sep) {
    return '·';
}
add_filter('document_title_separator', 'bhp_document_title_separator');

// ============================================================
// BODY CLASSES
// ============================================================
function bhp_body_classes($classes) {
    if (is_page()) {
        $classes[] = 'page-' . get_post_field('post_name', get_post());
    }

    if (function_exists('is_woocommerce') && is_woocommerce()) {
        $classes[] = 'woo-page';
    }

    return $classes;
}
add_filter('body_class', 'bhp_body_classes');

// ============================================================
// WOOCOMMERCE WRAPPERS
// header.php already provides the single main landmark.
// ============================================================
add_filter('woocommerce_show_page_title', '__return_false');

remove_action(
    'woocommerce_before_main_content',
    'woocommerce_output_content_wrapper',
    10
);
remove_action(
    'woocommerce_after_main_content',
    'woocommerce_output_content_wrapper_end',
    10
);

function bhp_woo_wrapper_start() {
    echo '<div class="site-container woo-container">';
}

function bhp_woo_wrapper_end() {
    echo '</div>';
}

add_action(
    'woocommerce_before_main_content',
    'bhp_woo_wrapper_start',
    10
);
add_action(
    'woocommerce_after_main_content',
    'bhp_woo_wrapper_end',
    10
);

// ============================================================
// FALLBACK MENU
// ============================================================
function bhp_fallback_menu() {
    echo '<ul>';
    echo '<li><a href="' . esc_url(home_url('/')) . '">Home</a></li>';
    echo '<li><a href="' . esc_url(home_url('/books')) . '">Books</a></li>';
    echo '<li><a href="' . esc_url(home_url('/about')) . '">About</a></li>';
    echo '<li><a href="' . esc_url(home_url('/blog')) . '">Blog</a></li>';
    echo '<li><a href="' . esc_url(home_url('/teachers-guide')) . '">Teachers</a></li>';
    echo '<li><a href="' . esc_url(home_url('/contact')) . '">Contact</a></li>';

    if (function_exists('wc_get_cart_url')) {
        echo '<li class="nav-cart"><a href="' .
            esc_url(wc_get_cart_url()) .
            '">Cart</a></li>';
    }

    echo '</ul>';
}
