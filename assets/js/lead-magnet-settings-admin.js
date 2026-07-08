/**
 * Media Library picker for the Brave Hearts Lead Magnets settings page.
 * Restricted to PDFs; writes the chosen attachment URL into the paired
 * text field rather than uploading or modifying anything automatically.
 */
jQuery(function ($) {
  $('.bhp-media-select').on('click', function (event) {
    event.preventDefault();

    var $button = $(this);
    var $field = $button.siblings('.bhp-media-url-field');

    var frame = wp.media({
      title: 'Select or Upload a PDF',
      button: { text: 'Use this file' },
      library: { type: 'application/pdf' },
      multiple: false,
    });

    frame.on('select', function () {
      var attachment = frame.state().get('selection').first().toJSON();
      $field.val(attachment.url);
    });

    frame.open();
  });
});
