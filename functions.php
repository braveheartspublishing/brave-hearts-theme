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
    add_image_size('bhp-book-card', 480, 640, false);
    add_image_size('bhp-card-landscape', 640, 420, true);
    add_theme_support('woocommerce');
    add_theme_support('wc-product-gallery-zoom');
    add_theme_support('wc-product-gallery-lightbox');
    add_theme_support('wc-product-gallery-slider');
    add_theme_support('html5', ['search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script']);
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

// Make the design-system button variants available in the block editor.
function bhp_register_block_styles() {
    $styles = [
        'bhp-primary'   => __('Primary', 'brave-hearts'),
        'bhp-secondary' => __('Secondary', 'brave-hearts'),
        'bhp-outline'   => __('Outline', 'brave-hearts'),
        'bhp-ghost'     => __('Ghost', 'brave-hearts'),
    ];

    foreach ($styles as $name => $label) {
        register_block_style('core/button', [
            'name'  => $name,
            'label' => $label,
        ]);
    }
}
add_action('init', 'bhp_register_block_styles');

// ============================================================
// ENQUEUE STYLES & SCRIPTS
// ============================================================
function bhp_enqueue_assets() {
    $theme_version = wp_get_theme()->get('Version');

    wp_enqueue_style('bhp-style', get_stylesheet_uri(), [], $theme_version);
    wp_enqueue_style('bhp-google-fonts',
        'https://fonts.googleapis.com/css2?family=Lato:wght@400;700&display=swap',
        [], null
    );
    wp_enqueue_script('bhp-nav', get_template_directory_uri() . '/assets/js/nav.js', [], $theme_version, true);

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
// WOOCOMMERCE: REMOVE SIDEBAR, ADJUST COLUMNS
// ============================================================
remove_action('woocommerce_sidebar', 'woocommerce_get_sidebar', 10);

function bhp_woo_columns($columns) { return 3; }
add_filter('loop_shop_columns', 'bhp_woo_columns');

function bhp_woo_per_page($num) { return 12; }
add_filter('loop_shop_per_page', 'bhp_woo_per_page', 20);

// ============================================================
// EXCERPT LENGTH
// ============================================================
function bhp_excerpt_length($length) { return 30; }
add_filter('excerpt_length', 'bhp_excerpt_length');

// ============================================================
// DOCUMENT TITLE SEPARATOR
// ============================================================
function bhp_document_title_separator($sep) { return '·'; }
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
// WOOCOMMERCE BREADCRUMBS — SIMPLIFIED
// ============================================================
add_filter('woocommerce_show_page_title', '__return_false');

// ============================================================
// REMOVE WOOCOMMERCE DEFAULT WRAPPERS (we use our own)
// ============================================================
remove_action('woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10);
remove_action('woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10);

function bhp_woo_wrapper_start() {
    echo '<div class="site-container woo-container">';
}
function bhp_woo_wrapper_end() {
    echo '</div>';
}
add_action('woocommerce_before_main_content', 'bhp_woo_wrapper_start', 10);
add_action('woocommerce_after_main_content', 'bhp_woo_wrapper_end', 10);

// ============================================================
// FALLBACK MENU (before nav is assigned in WP admin)
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
        echo '<li class="nav-cart"><a href="' . esc_url(wc_get_cart_url()) . '">Cart</a></li>';
    }
    echo '</ul>';
}

// ============================================================
// HOMEPAGE CONTENT AND FEATURED BOOK DATA
// ============================================================
/**
 * Read an editable front-page custom field with a filterable fallback.
 * Field names use the public bhp_home_* prefix so they remain available
 * through WordPress's standard Custom Fields interface.
 */
function bhp_get_homepage_field($key, $fallback = '') {
    $page_id    = get_queried_object_id();
    $field_name = 'bhp_home_' . sanitize_key($key);
    $stored     = $page_id ? get_post_meta($page_id, $field_name, true) : '';
    $value      = ($stored !== '') ? $stored : $fallback;

    return apply_filters('bhp_homepage_field_' . sanitize_key($key), $value, $page_id);
}

/**
 * Build book-card arguments from featured products or a future Book post type.
 * Marking a WooCommerce product as featured automatically adds it to the pool.
 */
/**
 * Return normalized book formats from WooCommerce attributes and variations.
 */
function bhp_get_product_formats($product) {
    if (!$product || !is_a($product, 'WC_Product')) {
        return [];
    }

    $values = [
        $product->get_attribute('pa_format'),
        $product->get_attribute('format'),
        $product->get_attribute('pa_book-format'),
        $product->get_attribute('book-format'),
        get_post_meta($product->get_id(), 'bhp_book_formats', true),
    ];

    if ($product->is_type('variable')) {
        foreach ($product->get_children() as $variation_id) {
            $variation = wc_get_product($variation_id);
            if (!$variation) {
                continue;
            }
            foreach ($variation->get_attributes() as $attribute => $value) {
                if (strpos($attribute, 'format') !== false && $value) {
                    $values[] = $value;
                }
            }
        }
    }

    $formats = [];
    foreach ($values as $value) {
        foreach (preg_split('/[,|]+/', (string) $value) as $format) {
            $format = trim(str_replace(['-', '_'], ' ', wp_strip_all_tags($format)));
            if (!$format) {
                continue;
            }

            $normalized = strtolower($format);
            if (in_array($normalized, ['hardback', 'hard cover', 'hardcover'], true)) {
                $format = 'Hardcover';
            } elseif (in_array($normalized, ['paper back', 'paperback'], true)) {
                $format = 'Paperback';
            } elseif (in_array($normalized, ['ebook', 'e book', 'kindle ebook', 'kindle'], true)) {
                $format = 'Kindle';
            } else {
                $format = ucwords($format);
            }

            $formats[$format] = $format;
        }
    }

    $ordered = [];
    foreach (['Paperback', 'Hardcover', 'Kindle'] as $preferred) {
        if (isset($formats[$preferred])) {
            $ordered[] = $preferred;
            unset($formats[$preferred]);
        }
    }

    return array_merge($ordered, array_values($formats));
}

/**
 * Build book-card arguments from every live product or a future Book post type.
 */
function bhp_get_homepage_books($limit = -1) {
    $limit     = ((int) $limit === -1) ? -1 : max(1, absint($limit));
    $post_type = post_type_exists('product') ? 'product' : (post_type_exists('book') ? 'book' : '');
    $cards     = [];

    if (!$post_type) {
        return apply_filters('bhp_homepage_books', $cards, $limit);
    }

    $query_args = [
        'post_type'        => $post_type,
        'post_status'      => 'publish',
        'posts_per_page'   => $limit,
        'orderby'          => ['menu_order' => 'ASC', 'date' => 'ASC'],
        'no_found_rows'    => true,
        'suppress_filters' => false,
    ];

    if ($post_type === 'product' && taxonomy_exists('product_cat')) {
        $series_slugs = apply_filters('bhp_homepage_book_category_slugs', ['charlotte-henry', 'charlotte-and-henry', 'books']);
        foreach ($series_slugs as $series_slug) {
            if (term_exists($series_slug, 'product_cat')) {
                $query_args['tax_query'] = [[
                    'taxonomy' => 'product_cat',
                    'field'    => 'slug',
                    'terms'    => sanitize_title($series_slug),
                ]];
                break;
            }
        }
    }

    $books = get_posts($query_args);

    foreach ($books as $book) {
        $product     = ($post_type === 'product' && function_exists('wc_get_product')) ? wc_get_product($book->ID) : null;
        $image_id    = get_post_thumbnail_id($book->ID);
        $image_alt   = $image_id ? get_post_meta($image_id, '_wp_attachment_image_alt', true) : '';
        $description = has_excerpt($book) ? get_the_excerpt($book) : '';
        $review      = get_post_meta($book->ID, 'bhp_review_label', true);

        if (!$review && stripos(get_the_title($book), 'Mariana Trench') !== false) {
            $review = __('Kirkus reviewed', 'brave-hearts');
        }

        $cards[] = [
            'product_id'   => $book->ID,
            'title'        => get_the_title($book),
            'url'          => get_permalink($book),
            'image_id'     => $image_id,
            'image_alt'    => $image_alt,
            'badge'        => get_post_meta($book->ID, 'bhp_book_badge', true),
            'age_range'    => get_post_meta($book->ID, 'bhp_age_range', true) ?: __('Ages 6–9', 'brave-hearts'),
            'formats'      => $product ? bhp_get_product_formats($product) : array_filter(array_map('trim', explode(',', (string) get_post_meta($book->ID, 'bhp_book_formats', true)))),
            'rating'       => $product ? (float) $product->get_average_rating() : 0,
            'review_count' => $product ? (int) $product->get_rating_count() : 0,
            'review'       => $review,
            'description'  => $description,
            'price'        => $product ? wp_strip_all_tags($product->get_price_html()) : get_post_meta($book->ID, 'bhp_book_price', true),
            'cta_label'    => __('Shop this book', 'brave-hearts'),
        ];
    }

    return apply_filters('bhp_homepage_books', $cards, $limit);
}

/**
 * Resolve a Learning Hub topic to an existing or future WordPress category URL.
 */
function bhp_get_learning_category_url($slug) {
    $category = get_category_by_slug(sanitize_title($slug));
    return $category ? get_category_link($category) : home_url('/category/' . sanitize_title($slug) . '/');
}

/**
 * Required footer links when no editor-managed footer menu is assigned.
 */
function bhp_footer_fallback_menu() {
    $privacy_url = get_privacy_policy_url() ?: home_url('/privacy-policy/');
    $terms_url   = home_url('/terms/');

    if (function_exists('wc_get_page_id')) {
        $terms_page_id = wc_get_page_id('terms');
        if ($terms_page_id > 0) {
            $terms_url = get_permalink($terms_page_id);
        }
    }

    $links = [
        __('Books', 'brave-hearts')             => home_url('/books/'),
        __('Teacher Resources', 'brave-hearts') => home_url('/teachers/'),
        __('Blog', 'brave-hearts')              => home_url('/blog/'),
        __('Media', 'brave-hearts')             => home_url('/media/'),
        __('Contact', 'brave-hearts')           => home_url('/contact/'),
        __('Privacy Policy', 'brave-hearts')    => $privacy_url,
        __('Terms', 'brave-hearts')             => $terms_url,
    ];

    echo '<ul>';
    foreach ($links as $label => $url) {
        echo '<li><a href="' . esc_url($url) . '">' . esc_html($label) . '</a></li>';
    }
    echo '</ul>';
}

