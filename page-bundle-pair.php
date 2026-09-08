<?php
/**
 * Template Name: Bundle Pair Landing Page
 * Description: Full-width template for a book + coloring book pair landing
 * page, rendering the [bhp_bundle_pair_landing] shortcode's sections
 * directly after the site header.
 *
 * ⭐ THIS IS `page-complete-collection.php`'s SHAPE, AND THAT IS THE BRIEF'S
 *    "same template family" made literal. The generic `page.php` wraps content
 *    in a narrow `.entry-content` reading column and prepends a "Field Journal"
 *    breadcrumb hero, both of which would squeeze this page's full-bleed
 *    sections - the covers hero, the dark-green value comparison and the
 *    dark-green final CTA - into a ~627px column. The collection page solved
 *    exactly this by taking a top-level, full-width template of its own, and
 *    this file is that solution applied to the pair.
 *
 * ⛔ IT ADDS NO MARKUP OF ITS OWN. Every section, every gate and every figure
 *    lives in `inc/bundle-pair-landing.php` behind the shortcode, so the page
 *    renders identically wherever the shortcode is placed and there is exactly
 *    one owner of the section order.
 *
 * @package brave-hearts
 * @since   1.19.399
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	the_content();
endwhile;

get_footer();
