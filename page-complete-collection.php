<?php
/**
 * Template Name: Complete Series Landing Page
 * Description: Full-width flagship template for the premium complete-
 * series bundle page (Staging Refinement Phase 2). Renders the
 * [bhp_complete_series_landing] shortcode's sections directly after the
 * site header -- no narrow .entry-content wrapper and no "Field Journal"
 * breadcrumb hero, both of which the generic page.php template adds and
 * which would otherwise squeeze this page's full-bleed sections (the
 * three-book hero, dark-green value comparison, dark-green final CTA)
 * into a ~627px reading column. Matches the same top-level, full-width
 * pattern already used by page-mariana-guide-parent.php.
 */
defined( 'ABSPATH' ) || exit;
get_header();

while ( have_posts() ) :
	the_post();
	the_content();
endwhile;

get_footer();
