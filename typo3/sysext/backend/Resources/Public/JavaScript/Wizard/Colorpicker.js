/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * Module: TYPO3/CMS/Backend/Wizard/Colorpicker
 * Colorpicker JavaScript
 */
define(['jquery'], function($) {
  'use strict';

  /**
   *
   * @type {{options: {}}}
   * @exports TYPO3/CMS/Backend/Wizard/Colorpicker
   */
  var Colorpicker = {
    options: {}
  };

  /**
   *
   * @param {Object} options
   */
  Colorpicker.setFieldChangeFunctions = function(options) {
    Colorpicker.options = options;
  };

  /**
   *
   */
  Colorpicker.initializeEvents = function() {
    // Set color value
    $('.t3js-colorpicker-value').on('click', function(e) {
      e.preventDefault();
      $('#colorValue').val($(this).data('color-value'));
      $(this).closest('form').submit();
    });

    // Handle the change of the color selector
    $('.t3js-colorpicker-selector').on('change', function(e) {
      e.preventDefault();
      $('#colorValue').val($(this).val());
      $(this).closest('form').submit();
    });

    // Handle the transfer of the color value and closing of popup
    $('#colorpicker-saveclose').on('click', function(e) {
      e.preventDefault();
      var theField = $('[data-formengine-input-name="' + $('[name="fieldName"]').val() + '"]').get(0);
      if (theField) {
        theField.value = $('#colorValue').val();

        if (typeof Colorpicker.options.fieldChangeFunctions === 'function') {
          Colorpicker.options.fieldChangeFunctions();
        }
      }
      parent.close();
      return false;
    });
  };

  $(Colorpicker.initializeEvents);

  return Colorpicker;
});
