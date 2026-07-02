<?php
/** Accessible site search form. */
defined('ABSPATH') || exit;
$search_id = wp_unique_id('site-search-');
?>
<form role="search" method="get" class="search-form expedition-search" action="<?php echo esc_url(home_url('/')); ?>">
  <label for="<?php echo esc_attr($search_id); ?>"><?php esc_html_e('Search the Brave Hearts journal', 'brave-hearts'); ?></label>
  <div class="expedition-search__controls">
    <input id="<?php echo esc_attr($search_id); ?>" type="search" name="s" value="<?php echo esc_attr(get_search_query()); ?>" placeholder="<?php esc_attr_e('Search books, field notes, and resources', 'brave-hearts'); ?>">
    <button class="btn btn-primary" type="submit"><?php esc_html_e('Search', 'brave-hearts'); ?></button>
  </div>
</form>
