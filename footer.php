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
?>
</main><!-- #main -->

<footer class="site-footer" role="contentinfo">
  <div class="footer-inner">
    <div class="footer-brand">
      <div class="footer-logo">
        <?php if (has_custom_logo()): ?>
          <?php echo get_custom_logo(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core-generated logo markup. ?>
        <?php else: ?>
          <a href="<?php echo esc_url(home_url('/')); ?>">
            <?php bloginfo('name'); ?>
            <span class="tagline"><?php esc_html_e('Big Places. Brave Hearts.', 'brave-hearts'); ?></span>
          </a>
        <?php endif; ?>
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
    <p class="footer-entry-close"><?php esc_html_e('End of entry · Close the journal', 'brave-hearts'); ?></p>
  </div>
</footer>

<?php wp_footer(); ?>
</div><!-- .site-wrapper -->
</body>
</html>
