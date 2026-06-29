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
        $terms_url = get_permalink($terms_page_id);
    }
}
?>
</main><!-- #main -->

<footer class="site-footer" role="contentinfo">
  <div class="footer-inner">
    <div class="footer-brand">
      <div class="footer-logo">Brave Hearts Publishing</div>
      <p>Early chapter STEM adventures for ages 6–9 that build courage, curiosity, and character.<br>Big Places. Brave Hearts.</p>
      <p class="footer-proof">Real places. Real science. Real adventure. Real courage.</p>
      <p class="footer-proof">As an Amazon Associate, Brave Hearts Publishing earns from qualifying purchases.</p>
    </div>

    <nav class="footer-nav" aria-label="<?php esc_attr_e('Footer navigation', 'brave-hearts'); ?>">
      <h4><?php esc_html_e('Explore', 'brave-hearts'); ?></h4>
      <?php
      wp_nav_menu([
          'theme_location' => 'footer',
          'menu_class'     => '',
          'container'      => false,
          'fallback_cb'    => 'bhp_footer_fallback_menu',
      ]);
      ?>
    </nav>

    <div class="footer-contact">
      <h4><?php esc_html_e('Get in touch', 'brave-hearts'); ?></h4>
      <p><a href="mailto:Asignore19@icloud.com">Asignore19@icloud.com</a></p>
      <p class="footer-contact__note">Classroom read alouds, school visits, bulk orders, media inquiries, and upcoming releases.</p>
      <?php if (is_active_sidebar('footer-3')): ?>
        <?php dynamic_sidebar('footer-3'); ?>
      <?php endif; ?>
    </div>
  </div>

  <div class="footer-bottom">
    <p>&copy; <?php echo esc_html(wp_date('Y')); ?> Brave Hearts Publishing LLC. <?php esc_html_e('All rights reserved.', 'brave-hearts'); ?>
      &nbsp;·&nbsp; <a class="footer-bottom__link" href="<?php echo esc_url($privacy_url); ?>"><?php esc_html_e('Privacy Policy', 'brave-hearts'); ?></a>
      &nbsp;·&nbsp; <a class="footer-bottom__link" href="<?php echo esc_url($terms_url); ?>"><?php esc_html_e('Terms', 'brave-hearts'); ?></a>
      &nbsp;·&nbsp; <a class="footer-bottom__link" href="<?php echo esc_url(home_url('/contact/')); ?>"><?php esc_html_e('Contact', 'brave-hearts'); ?></a>
    </p>
  </div>
</footer>

<?php wp_footer(); ?>
</div><!-- .site-wrapper -->
</body>
</html>
