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
      <p style="margin-top:12px;font-size:0.8rem;color:#6b8fa8;">
        Books in 40+ classrooms. Kirkus reviewed.
      </p>
    </div>

    <nav class="footer-nav" aria-label="Footer navigation">
      <h4>Explore</h4>
      <ul>
        <li><a href="<?php echo esc_url(home_url('/books')); ?>">Books</a></li>
        <li><a href="<?php echo esc_url(home_url('/about')); ?>">About</a></li>
        <li><a href="<?php echo esc_url(home_url('/blog')); ?>">Blog</a></li>
        <li><a href="<?php echo esc_url(home_url('/teachers-guide')); ?>">Teacher's Guide</a></li>
        <li><a href="<?php echo esc_url(home_url('/contact')); ?>">Contact</a></li>
        <?php if (function_exists('wc_get_cart_url')): ?>
        <li><a href="<?php echo esc_url(wc_get_cart_url()); ?>">Cart</a></li>
        <?php endif; ?>
      </ul>
    </nav>

    <div class="footer-contact">
      <h4>Get in touch</h4>
      <p><a href="mailto:andrew@braveheartspublishing.com">andrew@braveheartspublishing.com</a></p>
      <p style="margin-top:16px;font-size:0.85rem;">School visits, bulk orders, media:<br>use "Media" in subject line.</p>
      <?php if (is_active_sidebar('footer-3')): ?>
        <?php dynamic_sidebar('footer-3'); ?>
      <?php endif; ?>
    </div>
  </div>

  <div class="footer-bottom">
    <p>&copy; <?php echo date('Y'); ?> Brave Hearts Publishing LLC. All rights reserved.
    &nbsp;·&nbsp; <a href="<?php echo esc_url(home_url('/contact')); ?>" style="color:#6b8fa8;">Contact</a>
    <?php if (function_exists('wc_get_page_id')): ?>
    &nbsp;·&nbsp; <a href="<?php echo esc_url(get_permalink(wc_get_page_id('terms'))); ?>" style="color:#6b8fa8;">Terms</a>
    <?php endif; ?>
    </p>
  </div>
</footer>

<?php wp_footer(); ?>
</div><!-- .site-wrapper -->
</body>
</html>
