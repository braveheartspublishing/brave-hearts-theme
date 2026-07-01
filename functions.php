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
    load_theme_textdomain('brave-hearts', get_template_directory() . '/languages');

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
        'https://fonts.googleapis.com/css2?family=Archivo:wght@500;600;700&family=Caveat:wght@500;600;700&family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;1,400;1,500;1,600&family=EB+Garamond:ital,wght@0,400;0,500;1,400&family=Lato:wght@400;700&display=swap',
        [], null
    );
    wp_enqueue_script('bhp-nav', get_template_directory_uri() . '/assets/js/nav.js', [], $theme_version, true);
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
        'before_title'  => '<h2 class="widget-title">',
        'after_title'   => '</h2>',
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
    $links = [
        __('Home', 'brave-hearts')              => home_url('/'),
        __('Books', 'brave-hearts')             => home_url('/books/'),
        __('Educator Expedition Guides', 'brave-hearts') => home_url('/teachers/'),
        __('About', 'brave-hearts')             => home_url('/about/'),
        __('Blog', 'brave-hearts')              => home_url('/blog/'),
        __('Contact', 'brave-hearts')           => home_url('/contact/'),
    ];

    echo '<ul>';
    foreach ($links as $label => $url) {
        echo '<li><a href="' . esc_url($url) . '">' . esc_html($label) . '</a></li>';
    }
    echo '</ul>';
}

/**
 * Normalize a visitor-facing link and reject common empty placeholder values.
 */
function bhp_get_safe_link_url($url, $fallback = '') {
    if (!is_scalar($url)) {
        $url = '';
    }

    $url = trim((string) $url);
    if (in_array(strtolower($url), ['', 'null', 'undefined'], true)) {
        $url = '';
    }

    if ($url !== '' && $url[0] === '#') {
        return preg_match('/^#[A-Za-z][A-Za-z0-9_-]*$/', $url) ? $url : '';
    }

    if ($url !== '' && strpos($url, '/') === 0) {
        $url = home_url($url);
    }

    $url = $url ? esc_url_raw($url, ['http', 'https']) : '';
    if ($url && wp_http_validate_url($url)) {
        return $url;
    }

    if ($fallback && $fallback !== $url) {
        return bhp_get_safe_link_url($fallback);
    }

    return '';
}

/**
 * Keep the legacy Teacher Resources route and menu entries canonical.
 */
function bhp_redirect_legacy_teacher_resources() {
    if (is_admin()) {
        return;
    }

    $request_path = untrailingslashit((string) wp_parse_url(wp_unslash($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH));
    $legacy_path  = untrailingslashit((string) wp_parse_url(home_url('/teachers-guide/'), PHP_URL_PATH));

    if ($request_path === $legacy_path) {
        wp_safe_redirect(home_url('/teachers/'), 301, 'Brave Hearts Theme');
        exit;
    }
}
add_action('template_redirect', 'bhp_redirect_legacy_teacher_resources');

function bhp_canonicalize_teacher_menu_items($items) {
    $home_host    = strtolower((string) wp_parse_url(home_url('/'), PHP_URL_HOST));
    $teacher_path = untrailingslashit((string) wp_parse_url(home_url('/teachers/'), PHP_URL_PATH));
    $legacy_path  = untrailingslashit((string) wp_parse_url(home_url('/teachers-guide/'), PHP_URL_PATH));
    $seen_teacher = false;

    foreach ($items as $index => $item) {
        $item_host = strtolower((string) wp_parse_url($item->url, PHP_URL_HOST));
        if ($item_host && $item_host !== $home_host) {
            continue;
        }

        $item_path = untrailingslashit((string) wp_parse_url($item->url, PHP_URL_PATH));
        if (!in_array($item_path, [$teacher_path, $legacy_path], true)) {
            continue;
        }

        if ($seen_teacher) {
            unset($items[$index]);
            continue;
        }

        $item->url     = home_url('/teachers/');
        $item->title   = __('Educator Expedition Guides', 'brave-hearts');
        $item->classes = array_values(array_unique(array_merge((array) $item->classes, ['menu-item--educator-guides'])));
        $seen_teacher = true;
    }

    return array_values($items);
}
add_filter('wp_nav_menu_objects', 'bhp_canonicalize_teacher_menu_items');

/**
 * Disable invalid links stored in editor content and normalize the legacy
 * Teacher Resources route without changing the database.
 */
function bhp_sanitize_content_links($content) {
    if (!is_string($content) || stripos($content, '<a') === false) {
        return $content;
    }

    $canonical_url  = home_url('/teachers/');
    $home_host      = strtolower((string) wp_parse_url(home_url('/'), PHP_URL_HOST));
    $canonical_path = untrailingslashit((string) wp_parse_url($canonical_url, PHP_URL_PATH));
    $legacy_path = untrailingslashit((string) wp_parse_url(home_url('/teachers-guide/'), PHP_URL_PATH));

    if (class_exists('WP_HTML_Tag_Processor')) {
        $processor = new WP_HTML_Tag_Processor($content);
        while ($processor->next_tag('A')) {
            $href = $processor->get_attribute('href');
            if (!is_string($href)) {
                continue;
            }

            $href = trim($href);
            if (in_array(strtolower($href), ['', 'null', 'undefined'], true)) {
                $processor->remove_attribute('href');
                $processor->set_attribute('aria-disabled', 'true');
                continue;
            }

            $href_host = strtolower((string) wp_parse_url($href, PHP_URL_HOST));
            $href_path = untrailingslashit((string) wp_parse_url($href, PHP_URL_PATH));
            if ($href_host && $href_host !== $home_host) {
                continue;
            }

            if ($href_path === $legacy_path && $href_path !== $canonical_path) {
                $processor->set_attribute('href', $canonical_url);
            }
        }

        return $processor->get_updated_html();
    }

    return preg_replace_callback('/<a\b[^>]*>/i', static function ($matches) use ($canonical_url, $home_host, $legacy_path, $canonical_path) {
        $tag = $matches[0];
        if (!preg_match('/\shref\s*=\s*(["\'])(.*?)\1/i', $tag, $href_match)) {
            return $tag;
        }

        $href = trim($href_match[2]);
        if (in_array(strtolower($href), ['', 'null', 'undefined'], true)) {
            return str_replace($href_match[0], ' aria-disabled="true"', $tag);
        }

        $href_host = strtolower((string) wp_parse_url($href, PHP_URL_HOST));
        $href_path = untrailingslashit((string) wp_parse_url($href, PHP_URL_PATH));
        if ($href_host && $href_host !== $home_host) {
            return $tag;
        }

        if ($href_path === $legacy_path && $href_path !== $canonical_path) {
            return str_replace($href_match[2], esc_url($canonical_url), $tag);
        }

        return $tag;
    }, $content);
}
add_filter('the_content', 'bhp_sanitize_content_links', 20);

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

    if ($product->is_type('variable') && function_exists('wc_get_product')) {
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
        $book_url    = bhp_get_safe_link_url(get_permalink($book));
        if (!$book_url) {
            continue;
        }

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
            'url'          => $book_url,
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
    $slug = sanitize_title($slug);
    $hub_page = get_page_by_path($slug, OBJECT, 'page');
    if ($hub_page && $hub_page->post_status === 'publish') {
        $hub_url = bhp_get_safe_link_url(get_permalink($hub_page));
        if ($hub_url) {
            return $hub_url;
        }
    }

    $category = get_category_by_slug($slug);
    if ($category) {
        $category_url = bhp_get_safe_link_url(get_category_link($category));
        if ($category_url) {
            return $category_url;
        }
    }

    $posts_page_id = (int) get_option('page_for_posts');
    return $posts_page_id
        ? bhp_get_safe_link_url(get_permalink($posts_page_id), home_url('/blog/'))
        : home_url('/blog/');
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
            $terms_url = bhp_get_safe_link_url(get_permalink($terms_page_id), $terms_url);
        }
    }

    $links = [
        __('Books', 'brave-hearts')             => home_url('/books/'),
        __('Teacher Resources', 'brave-hearts') => home_url('/teachers/'),
        __('About', 'brave-hearts')             => home_url('/about/'),
        __('Blog', 'brave-hearts')              => home_url('/blog/'),
        __('Contact', 'brave-hearts')           => home_url('/contact/'),
        __('Privacy Policy', 'brave-hearts')    => $privacy_url,
        __('Terms', 'brave-hearts')             => $terms_url,
        __('Adventure Club', 'brave-hearts')    => home_url('/#adventure-club'),
    ];

    echo '<ul>';
    foreach ($links as $label => $url) {
        $url = bhp_get_safe_link_url($url);
        if ($url) {
            echo '<li><a href="' . esc_url($url) . '">' . esc_html($label) . '</a></li>';
        }
    }
    echo '</ul>';
}

// ============================================================
// CUSTOMER ACQUISITION FOUNDATION
// ============================================================
/**
 * Supported audience values for provider tags and CRM segmentation.
 */
function bhp_get_audience_types() {
    return apply_filters('bhp_audience_types', [
        'parents_families' => __('Parents / Families', 'brave-hearts'),
        'teachers'         => __('Teachers', 'brave-hearts'),
        'general_readers'  => __('General Readers', 'brave-hearts'),
    ]);
}

/**
 * Normalize an audience value so forms never emit an unknown segment.
 */
function bhp_normalize_audience_type($audience_type) {
    $audiences = bhp_get_audience_types();
    return isset($audiences[$audience_type]) ? $audience_type : 'general_readers';
}

/**
 * Lead-magnet registry. Download URLs intentionally remain empty until assets
 * and provider delivery automations are approved.
 */
function bhp_get_lead_magnets() {
    return apply_filters('bhp_lead_magnets', [
        'explorer_passport' => [
            'title'         => __('Explorer Passport', 'brave-hearts'),
            'description'   => __('Get free printable adventures that help young readers record what they discover.', 'brave-hearts'),
            'audience_type' => 'parents_families',
            'download_url'  => '',
            'status'        => 'placeholder',
        ],
        'printable_adventure_maps' => [
            'title'         => __('Printable Adventure Maps', 'brave-hearts'),
            'description'   => __('Follow Charlotte and Henry from real places on the map into the stories.', 'brave-hearts'),
            'audience_type' => 'general_readers',
            'download_url'  => '',
            'status'        => 'placeholder',
        ],
        'teacher_resources' => [
            'title'         => __('Teacher Resources', 'brave-hearts'),
            'description'   => __('Get classroom-ready updates for lesson plans, discussion guides, vocabulary, maps, printables, and read-aloud resources.', 'brave-hearts'),
            'audience_type' => 'teachers',
            'download_url'  => '',
            'status'        => 'placeholder',
        ],
        'teacher_lesson_plans' => [
            'title'         => __('Teacher Lesson Plans', 'brave-hearts'),
            'description'   => __('Bring the adventure into your classroom with practical, story-connected learning.', 'brave-hearts'),
            'audience_type' => 'teachers',
            'download_url'  => '',
            'status'        => 'placeholder',
        ],
        'reading_guides' => [
            'title'         => __('Reading Guides', 'brave-hearts'),
            'description'   => __('Help growing readers build confidence, comprehension, and curiosity.', 'brave-hearts'),
            'audience_type' => 'parents_families',
            'download_url'  => '',
            'status'        => 'placeholder',
        ],
    ]);
}

/**
 * Provider-neutral action filter. Leave the returned value empty until a
 * secure Mailchimp, HubSpot, or first-party form handler is configured.
 */
function bhp_get_valid_form_action($action) {
    if (!is_string($action) || trim($action) === '') {
        return '';
    }

    $action = esc_url_raw(trim($action), ['http', 'https']);
    if (!$action || !wp_http_validate_url($action)) {
        return '';
    }

    $path = untrailingslashit((string) wp_parse_url($action, PHP_URL_PATH));
    $placeholder_paths = [
        '/contact-form-placeholder',
        '/signup-placeholder',
    ];

    foreach ($placeholder_paths as $placeholder_path) {
        if ($path === $placeholder_path || strpos($path, $placeholder_path . '/') === 0) {
            return '';
        }
    }

    return $action;
}

require_once get_template_directory() . '/inc/mailchimp.php';

// ============================================================
// EXPLORER PASSPORT FOUNDATION
// ============================================================
/**
 * Central registry for current and future Explorer Passport features.
 */
function bhp_get_explorer_passport_features() {
    return apply_filters('bhp_explorer_passport_features', [
        'world_explorer_map' => [
            'title'       => __('World Explorer Map', 'brave-hearts'),
            'description' => __('Track every real place Charlotte and Henry visit and see the adventure grow around the world.', 'brave-hearts'),
            'status'      => 'placeholder',
        ],
        'adventure_stamps' => [
            'title'       => __('Adventure Stamps', 'brave-hearts'),
            'description' => __('Collect a stamp for each destination, book, and completed Brave Hearts adventure.', 'brave-hearts'),
            'status'      => 'placeholder',
        ],
        'reading_achievements' => [
            'title'       => __('Reading Achievements', 'brave-hearts'),
            'description' => __('Celebrate finished books, new reading milestones, and the curiosity to keep learning.', 'brave-hearts'),
            'status'      => 'placeholder',
        ],
        'explorer_certificates' => [
            'title'       => __('Explorer Certificates', 'brave-hearts'),
            'description' => __('Recognize readers who complete an adventure and become official Brave Hearts Explorers.', 'brave-hearts'),
            'status'      => 'placeholder',
        ],
        'future_adventure_badges' => [
            'title'       => __('Future Adventure Badges', 'brave-hearts'),
            'description' => __('Unlock new badges as future books introduce more places, science, wildlife, and acts of courage.', 'brave-hearts'),
            'status'      => 'placeholder',
        ],
    ]);
}

/**
 * Return the placeholder or approved Passport download state.
 */
function bhp_get_explorer_passport_download($requested_url = '') {
    $url = bhp_get_safe_link_url(apply_filters('bhp_explorer_passport_download_url', $requested_url));
    $ready = (bool) apply_filters('bhp_explorer_passport_download_ready', false, $url);

    return [
        'url'   => $url,
        'ready' => $ready && (bool) $url,
    ];
}

// ============================================================
// BOOKS PAGE ADVENTURE GROUPING
// ============================================================
/**
 * Group format-specific product SKUs into customer-facing adventures.
 */
function bhp_get_series_adventures() {
    $definitions = [
        'mariana_trench' => [
            'title'       => __('The Mariana Trench', 'brave-hearts'),
            'destination' => __('Mariana Trench · Western Pacific Ocean', 'brave-hearts'),
            'description' => __('Charlotte and Henry descend to the deepest place on Earth, meeting remarkable ocean life and discovering science, conservation, and the courage to keep going.', 'brave-hearts'),
            'matches'     => ['mariana trench', 'mariana'],
        ],
        'mount_everest' => [
            'title'       => __('Mount Everest', 'brave-hearts'),
            'destination' => __('Mount Everest · Himalayas', 'brave-hearts'),
            'description' => __('Charlotte and Henry journey toward the world’s highest mountain in an adventure shaped by geography, resilience, teamwork, and courage.', 'brave-hearts'),
            'matches'     => ['mount everest', 'everest'],
        ],
        'amazon_rainforest' => [
            'title'       => __('The Amazon Rainforest', 'brave-hearts'),
            'destination' => __('Amazon Rainforest · South America', 'brave-hearts'),
            'description' => __('Charlotte and Henry enter the world’s largest tropical rainforest to discover extraordinary wildlife, connected ecosystems, conservation, and wonder.', 'brave-hearts'),
            'matches'     => ['amazon rainforest', 'amazon', 'rainforest'],
        ],
    ];

    $products = bhp_get_homepage_books(-1);
    $adventures = [];

    foreach ($definitions as $key => $definition) {
        $adventures[$key] = array_merge($definition, [
            'key'             => $key,
            'age_range'       => __('Ages 6–9', 'brave-hearts'),
            'formats'         => [],
            'image_id'        => 0,
            'image_alt'       => '',
            'primary_url'     => '',
            'formats_url'     => '',
            'amazon_url'      => '',
            'matching_skus'   => 0,
            'available'       => false,
        ]);
    }

    foreach ($products as $product) {
        $product_title = strtolower(wp_strip_all_tags($product['title']));
        $adventure_key = '';

        foreach ($definitions as $key => $definition) {
            foreach ($definition['matches'] as $match) {
                if (strpos($product_title, $match) !== false) {
                    $adventure_key = $key;
                    break 2;
                }
            }
        }

        if (!$adventure_key) {
            continue;
        }

        $adventure = &$adventures[$adventure_key];
        $product_formats = is_array($product['formats']) ? $product['formats'] : [];

        if (strpos($product_title, 'paperback') !== false && !in_array('Paperback', $product_formats, true)) {
            $product_formats[] = 'Paperback';
        }
        if ((strpos($product_title, 'hardcover') !== false || strpos($product_title, 'hardback') !== false) && !in_array('Hardcover', $product_formats, true)) {
            $product_formats[] = 'Hardcover';
        }
        if ((strpos($product_title, 'kindle') !== false || strpos($product_title, 'ebook') !== false) && !in_array('Kindle', $product_formats, true)) {
            $product_formats[] = 'Kindle';
        }

        $is_paperback = in_array('Paperback', $product_formats, true) || strpos($product_title, 'paperback') !== false;
        $adventure['matching_skus']++;
        $adventure['available'] = true;
        $adventure['formats'] = array_values(array_unique(array_merge($adventure['formats'], $product_formats)));

        if (!$adventure['primary_url'] || $is_paperback) {
            $adventure['primary_url'] = $product['url'];
            if (!empty($product['image_id'])) {
                $adventure['image_id'] = $product['image_id'];
                $adventure['image_alt'] = $product['image_alt'];
            }
        } elseif (!$adventure['image_id'] && !empty($product['image_id'])) {
            $adventure['image_id'] = $product['image_id'];
            $adventure['image_alt'] = $product['image_alt'];
        }

        unset($adventure);
    }

    foreach ($adventures as &$adventure) {
        if ($adventure['available']) {
            $ordered_formats = [];
            foreach (['Paperback', 'Hardcover', 'Kindle'] as $preferred_format) {
                if (in_array($preferred_format, $adventure['formats'], true)) {
                    $ordered_formats[] = $preferred_format;
                }
            }
            $adventure['formats'] = array_merge(
                $ordered_formats,
                array_values(array_diff($adventure['formats'], $ordered_formats))
            );
            $adventure['formats_url'] = bhp_get_safe_link_url(add_query_arg([
                's'         => $adventure['title'],
                'post_type' => 'product',
            ], home_url('/shop/')), home_url('/shop/'));
        }
    }
    unset($adventure);

    return apply_filters('bhp_series_adventures', $adventures, $products);
}

// ============================================================
// CONTACT FORM FOUNDATION
// ============================================================
/**
 * Provider-neutral contact action. Keep empty until an approved external or
 * first-party handler is configured with validation and spam protection.
 */
function bhp_get_contact_form_action($requested_action = '') {
    return bhp_get_valid_form_action(apply_filters('bhp_contact_form_action', $requested_action));
}

