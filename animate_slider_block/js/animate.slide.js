/**
 * @file
 * Apply Animate slider.
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  /**
   * Attaches the animate slider.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.animateSlider = {
    attach: function (context, settings) {
      $.each(drupalSettings.sliderDetails, function (key, val) {
        $('#slider-' + key).animateSlider({
          autoplay: true,
          interval: 5000,
          animations: val.config
        });
      });
    }
  };
})(jQuery, Drupal, drupalSettings);
