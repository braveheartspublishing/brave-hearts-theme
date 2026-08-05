/**
 * Auto-selects a variable product's only real variation option on page
 * load. Every current book product has exactly one variation ("Perfect
 * Bound" / "Case Bound"), so the dropdown is a formality, not a real
 * customer decision -- but WooCommerce won't show the variation price or
 * the real Add to Cart button until a selection is made. Auto-selecting
 * removes that extra click and lets price + Add to Cart appear
 * immediately, right after the format control, with no waiting.
 *
 * Deliberately scoped to forms with exactly one non-placeholder option --
 * a product with genuine multiple formats to choose from is left alone.
 */
(function ($) {
	'use strict';
	$(function () {
		$('.variations_form').each(function () {
			var $form = $(this);
			var $selects = $form.find('select');
			if ($selects.length !== 1) {
				return;
			}
			var $select = $selects.first();
			var $realOptions = $select.find('option').not('[value=""]');
			if ($realOptions.length !== 1) {
				return;
			}
			$select.val($realOptions.first().val()).trigger('change');
		});
	});
})(jQuery);
