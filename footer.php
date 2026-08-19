<?php
/**
 * Brave Hearts Publishing — Footer
 */
defined('ABSPATH') || exit;

$privacy_url = get_privacy_policy_url() ?: home_url('/privacy-policy/');
$terms_url   = home_url('/terms/');
if (function_exists('wc_get_page_id')) {
    $terms_page_id = wc_get_page_id('terms');
    if ($terms_page_id > 0) {
        $terms_url = bhp_get_safe_link_url(get_permalink($terms_page_id), $terms_url);
    }
}
$privacy_url = bhp_get_safe_link_url($privacy_url, home_url('/privacy-policy/'));
$terms_url = bhp_get_safe_link_url($terms_url, home_url('/terms/'));
$shipping_policy_url = bhp_get_safe_link_url(home_url('/shipping-policy/'));
$returns_url = home_url('/refund_returns/');
if (function_exists('wc_get_page_id')) {
    $returns_page_id = wc_get_page_id('refund_returns');
    if ($returns_page_id > 0) {
        $returns_url = get_permalink($returns_page_id);
    }
}
// Phase 1D: the Refund and Returns Policy page already existed but was
// never linked anywhere on the site (found during the product/collection
// trust-elements audit) -- this only adds the missing link, following the
// exact existing pattern used for Shipping Policy above.
$returns_url = bhp_get_safe_link_url($returns_url, home_url('/refund_returns/'));
/*
 * ═══════════════════════════════════════════════════════════════════════
 * ⭐⭐ 1.19.254 (2026-08-19, `CYCLE165-LD-COLLECTION-CONVERSION`) R-9 — ON
 *     THE COLLECTION PAGE, THE LAST THING BEFORE THE FOOTER IS THE ORDER.
 * ═══════════════════════════════════════════════════════════════════════
 *
 * MEASURED on production: on /complete-collection/ the quiz launcher (top
 * 5584) and the inline email capture (top 6154) sit between the page's final
 * purchase CTA and the footer. On a page whose single job is the order, the
 * two things immediately after the closing CTA are two competing
 * conversions.
 *
 * ⛔ NEITHER IS REMOVED, ON EITHER PAGE, AT ANY WIDTH. This REORDERS them and
 *    nothing else: on this one page they render AFTER the audience router
 *    instead of before it. Removing either is a different decision (D-6) and
 *    is the founder's, not this file's. The homepage-only exit popup is a
 *    separate component entirely and is untouched.
 *
 * ⛔ PAGE-SCOPED BY CONSTRUCTION. `bhp_book_is_collection_page()` is true only
 *    for a page whose content carries the collection shortcode, so every
 *    other page on the site renders this footer in exactly the order 1.19.253
 *    rendered it. If the theme ever loses that helper the condition is false
 *    and the ORIGINAL order stands — it fails back to today's behaviour, not
 *    to a broken one.
 *
 * ⛔ BOTH ELIGIBILITY GATES ARE UNTOUCHED. `bhp_should_show_quiz_cta()` and
 *    `bhp_should_show_footer_capture()` still decide WHETHER each renders;
 *    this only decides WHERE. A page that was not getting one still does not.
 *
 * ⛔ FUNNEL ISOLATION IS UNAFFECTED: no storage key, no analytics prefix and
 *    no lead-magnet key is read or written here.
 */
$bhp_defer_conversions = function_exists('bhp_book_is_collection_page') && bhp_book_is_collection_page();
$bhp_show_quiz_cta     = function_exists('bhp_should_show_quiz_cta') && bhp_should_show_quiz_cta();
$bhp_show_capture      = function_exists('bhp_should_show_footer_capture') && bhp_should_show_footer_capture();
?>
</main><!-- #main -->

<?php if ($bhp_show_quiz_cta && !$bhp_defer_conversions): ?>
  <?php
  // The homepage no longer renders an inline quiz section, so the launcher
  // here inherits the '#find-your-adventure' anchor id it used to carry --
  // homepage only, so the id stays unique on every page.
  get_template_part('template-parts/components/quiz-entry-cta', null, is_front_page() ? ['id' => 'find-your-adventure'] : []);
  ?>
<?php endif; ?>

<footer class="site-footer" role="contentinfo">
  <?php
  /* Wave 1 (2026-08-04, theme 1.19.168) — sitewide capture block, first
     thing inside the footer so it is read before the navigation columns.
     Eligibility (never on /teachers/, never on a transaction surface,
     never on a page that is already a signup destination) is decided
     server-side in bhp_should_show_footer_capture(). */
  // 1.19.254 R-9: on the collection page this block renders after the
  // audience router below instead. Same gate, same template, same markup.
  if ($bhp_show_capture && !$bhp_defer_conversions) {
      get_template_part('template-parts/acquisition/footer-capture');
  }
  ?>
  <div class="footer-inner">
    <div class="footer-brand">
      <div class="footer-logo">
        <?php /* Wave F (2026-08-03): the reversed lockup, no cream plate.
                 Same asset and the same reasoning as header.php -- see the
                 comment block there; it is not repeated. The footer ground is
                 #12271d (dark green) on the homepage and --color-navy
                 elsewhere, and the transparent reversed mark reads correctly
                 on both, which the plated navy lockup could not. */ ?>
        <a class="footer-logo__lockup" href="<?php echo esc_url(home_url('/')); ?>">
          <img class="footer-logo__mark"
               src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/brand/brave-hearts-horizontal-reversed-rose.png'); ?>"
               alt="<?php echo esc_attr(get_bloginfo('name')); ?>"
               width="338" height="100" loading="lazy" decoding="async">
        </a>
      </div>
      <p class="footer-closing"><?php esc_html_e('The real world is still wild enough.', 'brave-hearts'); ?><br><?php esc_html_e('Go look up.', 'brave-hearts'); ?></p>
      <p class="footer-proof">As an Amazon Associate, Brave Hearts Publishing earns from qualifying purchases.</p>
    </div>

    <nav class="footer-nav" aria-label="<?php esc_attr_e('Footer navigation', 'brave-hearts'); ?>">
      <?php
      /*
       * ⭐⭐ 1.19.266 (2026-08-19, CYCLE165-LD-ITERATE-2-AESTHETICS-TOKENS) —
       *     THESE THREE COLUMN LABELS WERE <h2> AND ARE NOW <p>.
       *
       * WHY. Measured on staging 1.19.264 (headless Chrome, innerWidth
       * asserted 390): "Explore", "Learn" and "Connect" render at 10.5px
       * against an 18px body — i.e. THREE headings smaller than body text,
       * on all 83 pages, 249 instances sitewide. That is the rubric's row-1
       * "no heading smaller than body" failure, and Pippin's audit §8a item 6
       * is it. (The audit describes it as "an H3 at 10.5px on the homepage";
       * the measurement says H2, three of them, on every page. The finding is
       * right, the label was not — recorded rather than quietly corrected.)
       *
       * WHY DEMOTE RATHER THAN ENLARGE. The two available fixes are: set them
       * at >=18px, or stop them being headings. Enlarging changes the footer's
       * design on every page of the site to satisfy a semantic rule; demoting
       * changes NOTHING a visitor sees — the CSS selector lists in style.css
       * now carry `.footer-col-title` beside each `h2` and every declaration
       * is identical — and removes the defect at its cause. These are column
       * LABELS, not document sections.
       *
       * NOTHING IS LOST FOR ASSISTIVE TECHNOLOGY. `.footer-nav` and
       * `.footer-learn` are <nav> landmarks that ALREADY carry their own
       * `aria-label` ("Footer navigation", "Learning navigation"), so a
       * screen-reader user reaches and identifies them by landmark, not by
       * these headings. `.footer-contact` is a <div> with no landmark, so it
       * is given `role="group"` + `aria-labelledby` pointing at its label —
       * which names the group MORE precisely than a loose <h2> did.
       *
       * ⛔ NO COPY CHANGED. Three element names and one ARIA relationship.
       */
      ?>
      <p class="footer-col-title"><?php esc_html_e('Explore', 'brave-hearts'); ?></p>
      <?php
      wp_nav_menu([
          'theme_location' => 'footer',
          'menu_class'     => '',
          'container'      => false,
          'fallback_cb'    => 'bhp_footer_fallback_menu',
      ]);
      ?>
    </nav>

    <nav class="footer-learn" aria-label="<?php esc_attr_e('Learning navigation', 'brave-hearts'); ?>">
      <p class="footer-col-title"><?php esc_html_e('Learn', 'brave-hearts'); ?></p>
      <ul>
        <li><a href="<?php echo esc_url(home_url('/blog/')); ?>"><?php esc_html_e('Learning Hub', 'brave-hearts'); ?></a></li>
        <li><a href="<?php echo esc_url(home_url('/teachers/')); ?>"><?php esc_html_e('For Teachers', 'brave-hearts'); ?></a></li>
        <li><a href="<?php echo esc_url(home_url('/teachers/#family-resources')); ?>"><?php esc_html_e('For Families', 'brave-hearts'); ?></a></li>
      </ul>
    </nav>

    <div class="footer-contact" role="group" aria-labelledby="footer-connect-title">
      <p class="footer-col-title" id="footer-connect-title"><?php esc_html_e('Connect', 'brave-hearts'); ?></p>
      <p><a href="mailto:andrew@braveheartspublishing.com">andrew@braveheartspublishing.com</a></p>
      <p><a href="<?php echo esc_url(home_url('/reluctant-reader-adventure-kit/')); ?>"><?php esc_html_e('Join the Expedition', 'brave-hearts'); ?></a></p>
      <p class="footer-contact__note">Classroom read alouds, school visits, bulk orders, media inquiries, and upcoming releases.</p>
      <?php if (is_active_sidebar('footer-3')): ?>
        <?php dynamic_sidebar('footer-3'); ?>
      <?php endif; ?>
    </div>
  </div>

  <nav class="footer-audience-cluster" aria-label="<?php esc_attr_e('Resources by reader type', 'brave-hearts'); ?>">
    <p class="footer-audience-cluster__heading"><?php esc_html_e('Resources for Every Reader', 'brave-hearts'); ?></p>
    <ul>
      <li><a href="<?php echo esc_url(home_url('/reluctant-reader-adventure-kit/')); ?>"><?php esc_html_e('Helping a reluctant reader?', 'brave-hearts'); ?></a></li>
      <li><a href="<?php echo esc_url(home_url('/gift-buyers-guide/')); ?>"><?php esc_html_e('Shopping for a meaningful gift?', 'brave-hearts'); ?></a></li>
      <li><a href="<?php echo esc_url(home_url('/educators-adventure-learning-toolkit/')); ?>"><?php esc_html_e('Teaching or homeschooling?', 'brave-hearts'); ?></a></li>
      <li><a href="<?php echo esc_url(home_url('/organizations-community-reading-kit/')); ?>"><?php esc_html_e('Planning a reading program?', 'brave-hearts'); ?></a></li>
    </ul>
  </nav>

  <?php
  /*
   * 1.19.254 R-9 — the deferred pair, in the order the specification sets:
   * audience router (immediately above) -> quiz launcher -> email capture.
   * This branch runs on the collection page ONLY; see the block at the top
   * of this file for why, and for what is deliberately NOT changed.
   */
  if ($bhp_defer_conversions) {
      if ($bhp_show_quiz_cta) {
          get_template_part('template-parts/components/quiz-entry-cta', null, []);
      }
      if ($bhp_show_capture) {
          get_template_part('template-parts/acquisition/footer-capture');
      }
  }
  ?>

  <div class="footer-bottom">
    <p>&copy; <?php echo esc_html(wp_date('Y')); ?> Brave Hearts Publishing LLC. <?php esc_html_e('All rights reserved.', 'brave-hearts'); ?>
      &nbsp;·&nbsp; <a class="footer-bottom__link" href="<?php echo esc_url($privacy_url); ?>"><?php esc_html_e('Privacy Policy', 'brave-hearts'); ?></a>
      <?php if ($shipping_policy_url): ?>
        &nbsp;·&nbsp; <a class="footer-bottom__link" href="<?php echo esc_url($shipping_policy_url); ?>"><?php esc_html_e('Shipping Policy', 'brave-hearts'); ?></a>
      <?php endif; ?>
      <?php if ($returns_url): ?>
        &nbsp;·&nbsp; <a class="footer-bottom__link" href="<?php echo esc_url($returns_url); ?>"><?php esc_html_e('Refund and Returns Policy', 'brave-hearts'); ?></a>
      <?php endif; ?>
      &nbsp;·&nbsp; <a class="footer-bottom__link" href="<?php echo esc_url($terms_url); ?>"><?php esc_html_e('Terms', 'brave-hearts'); ?></a>
      &nbsp;·&nbsp; <a class="footer-bottom__link" href="<?php echo esc_url(home_url('/contact/')); ?>"><?php esc_html_e('Contact', 'brave-hearts'); ?></a>
    </p>
    <p class="footer-entry-close"><?php esc_html_e('End of entry · Close the journal', 'brave-hearts'); ?></p>
  </div>
</footer>

<?php wp_footer(); ?>
</div><!-- .site-wrapper -->
</body>
</html>
