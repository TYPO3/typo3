/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with DocumentHeader source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * Module: TYPO3/CMS/Backend/SplitButtons
 * Initializes global handling of split buttons.
 */
define(['jquery', 'TYPO3/CMS/Backend/Icons'], function($, Icons) {
  'use strict';

  /**
   *
   * @type {{preSubmitCallbacks: Array}}
   * @exports TYPO3/CMS/Backend/SplitButtons
   */
  var SplitButtons = {
    preSubmitCallbacks: []
  };

  /**
   * Initializes the save handling
   */
  SplitButtons.initializeSaveHandling = function() {
    var preventExec = false;
    var elements = [
      'button[form]',
      'button[name^="_save"]',
      'a[data-name^="_save"]',
      'button[name="CMD"][value^="save"]',
      'a[data-name="CMD"][data-value^="save"]',
      'button[name^="_translation_save"]',
      'a[data-name^="_translation_save"]',
      'button[name="CMD"][value^="_translation_save"]',
      'a[data-name="CMD"][data-value^="_translation_save"]'
    ].join(',');
    $('.t3js-module-docheader').on('click', elements, function(e) {
      // prevent doubleclick double submission bug in chrome,
      // see https://forge.typo3.org/issues/77942
      if (!preventExec) {
        preventExec = true;
        var $me = $(this),
          linkedForm = $me.attr('form') || $me.attr('data-form') || null,
          $form = linkedForm ? $('#' + linkedForm) : $me.closest('form'),
          name = $me.data('name') || this.name,
          value = $me.data('value') || this.value,
          $elem = $('<input />').attr('type', 'hidden').attr('name', name).attr('value', value);

        // Run any preSubmit callbacks
        for (var i = 0; i < SplitButtons.preSubmitCallbacks.length; ++i) {
          SplitButtons.preSubmitCallbacks[i](e);

          if (e.isPropagationStopped()) {
            preventExec = false;
            return false;
          }
        }
        $form.append($elem);
        // Disable submit buttons
        $form.on('submit', function() {
          if ($form.find('.has-error').length > 0) {
            preventExec = false;
            return false;
          }

          var $affectedButton,
            $splitButton = $me.closest('.t3js-splitbutton');

          if ($splitButton.length > 0) {
            $splitButton.find('button').prop('disabled', true);
            $affectedButton = $splitButton.children().first();
          } else {
            $me.prop('disabled', true);
            $affectedButton = $me;
          }

          Icons.getIcon('spinner-circle-dark', Icons.sizes.small).done(function(markup) {
            $affectedButton.find('.t3js-icon').replaceWith(markup);
          });
        });

        if ((e.currentTarget.tagName === 'A' || $me.attr('form')) && !e.isDefaultPrevented()) {
          $form.submit();
          e.preventDefault();
        }
      }
    });
  };

  /**
   * Adds a callback being executed before submit
   *
   * @param {function} callback
   */
  SplitButtons.addPreSubmitCallback = function(callback) {
    if (typeof callback !== 'function') {
      throw 'callback must be a function.';
    }

    SplitButtons.preSubmitCallbacks.push(callback);
  };

  $(SplitButtons.initializeSaveHandling);

  return SplitButtons;
});
