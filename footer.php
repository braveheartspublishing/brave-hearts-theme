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
?>
</main><!-- #main -->

<?php if (function_exists('bhp_should_show_quiz_cta') && bhp_should_show_quiz_cta()): ?>
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
  if (function_exists('bhp_should_show_footer_capture') && bhp_should_show_footer_capture()) {
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
      <h2><?php esc_html_e('Explore', 'brave-hearts'); ?></h2>
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
      <h2><?php esc_html_e('Learn', 'brave-hearts'); ?></h2>
      <ul>
        <li><a href="<?php echo esc_url(home_url('/blog/')); ?>"><?php esc_html_e('Learning Hub', 'brave-hearts'); ?></a></li>
        <li><a href="<?php echo esc_url(home_url('/teachers/')); ?>"><?php esc_html_e('For Teachers', 'brave-hearts'); ?></a></li>
        <li><a href="<?php echo esc_url(home_url('/teachers/#family-resources')); ?>"><?php esc_html_e('For Families', 'brave-hearts'); ?></a></li>
      </ul>
    </nav>

    <div class="footer-contact">
      <h2><?php esc_html_e('Connect', 'brave-hearts'); ?></h2>
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
