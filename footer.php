<?php
/**
 * Brave Hearts Publishing — Footer
 */
?>
</main><!-- #main -->

<footer class="site-footer" role="contentinfo">
  <div class="footer-inner">
    <div class="footer-brand">
      <div class="footer-logo">Brave Hearts Publishing</div>
      <p>Adventure books for curious kids ages 6–9.<br>Big Places. Brave Hearts.</p>
      <p class="footer-proof">Books in 40+ classrooms. Kirkus reviewed.</p>
    </div>

    <nav class="footer-nav" aria-label="<?php esc_attr_e('Footer navigation', 'brave-hearts'); ?>">
      <h4><?php esc_html_e('Explore', 'brave-hearts'); ?></h4>
      <ul>
        <li><a href="<?php echo esc_url(home_url('/books')); ?>"><?php esc_html_e('Books', 'brave-hearts'); ?></a></li>
        <li><a href="<?php echo esc_url(home_url('/about')); ?>"><?php esc_html_e('About', 'brave-hearts'); ?></a></li>
        <li><a href="<?php echo esc_url(home_url('/blog')); ?>"><?php esc_html_e('Blog', 'brave-hearts'); ?></a></li>
        <li><a href="<?php echo esc_url(home_url('/teachers-guide')); ?>"><?php esc_html_e("Teacher's Guide", 'brave-hearts'); ?></a></li>
        <li><a href="<?php echo esc_url(home_url('/contact')); ?>"><?php esc_html_e('Contact', 'brave-hearts'); ?></a></li>
        <?php if (function_exists('wc_get_cart_url')): ?>
        <li><a href="<?php echo esc_url(wc_get_cart_url()); ?>"><?php esc_html_e('Cart', 'brave-hearts'); ?></a></li>
        <?php endif; ?>
      </ul>
    </nav>

    <div class="footer-contact">
      <h4><?php esc_html_e('Get in touch', 'brave-hearts'); ?></h4>
      <p><a href="mailto:andrew@braveheartspublishing.com">andrew@braveheartspublishing.com</a></p>
      <p class="footer-contact__note">School visits, bulk orders, media:<br>use “Media” in the subject line.</p>
      <?php if (is_active_sidebar('footer-3')): ?>
        <?php dynamic_sidebar('footer-3'); ?>
      <?php endif; ?>
    </div>
  </div>

  <div class="footer-bottom">
    <p>&copy; <?php echo esc_html(wp_date('Y')); ?> Brave Hearts Publishing LLC. <?php esc_html_e('All rights reserved.', 'brave-hearts'); ?>
    &nbsp;·&nbsp; <a class="footer-bottom__link" href="<?php echo esc_url(home_url('/contact')); ?>"><?php esc_html_e('Contact', 'brave-hearts'); ?></a>
    <?php if (function_exists('wc_get_page_id')): ?>
    &nbsp;·&nbsp; <a class="footer-bottom__link" href="<?php echo esc_url(get_permalink(wc_get_page_id('terms'))); ?>"><?php esc_html_e('Terms', 'brave-hearts'); ?></a>
    <?php endif; ?>
    </p>
  </div>
</footer>

<?php wp_footer(); ?>
</div><!-- .site-wrapper -->
</body>
</html>
