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
 * Module: TYPO3/CMS/Backend/NewMultiplePages
 * JavaScript functions for creating multiple pages
 */
define(['jquery'], function($) {
  'use strict';

  /**
   * @type {{lineCounter: number, containerSelector: string, addMoreFieldsButtonSelector: string, doktypeSelector: string}}
   * @exports TYPO3/CMS/Backend/NewMultiplePages
   */
  var NewMultiplePages = {
    lineCounter: 5,
    containerSelector: '.t3js-newmultiplepages-container',
    addMoreFieldsButtonSelector: '.t3js-newmultiplepages-createnewfields',
    doktypeSelector: '.t3js-newmultiplepages-select-doktype',
    templateRow: '.t3js-newmultiplepages-newlinetemplate'
  };

  /**
   * Add further input rows
   */
  NewMultiplePages.createNewFormFields = function() {
    for (var i = 0; i < 5; i++) {
      var label = NewMultiplePages.lineCounter + i + 1;
      var line = $(NewMultiplePages.templateRow).html()
        .replace(/\[0\]/g, (NewMultiplePages.lineCounter + i))
        .replace(/\[1\]/g, label);
      $(line).appendTo(NewMultiplePages.containerSelector);
    }
    NewMultiplePages.lineCounter += 5;
  };

  /**
   * @param {Object} $selectElement
   */
  NewMultiplePages.actOnTypeSelectChange = function($selectElement) {
    var $optionElement = $selectElement.find(':selected');
    var $target = $($selectElement.data('target'));
    $target.html($optionElement.data('icon'));
  };

  /**
   * Register listeners
   */
  NewMultiplePages.initializeEvents = function() {
    $(NewMultiplePages.addMoreFieldsButtonSelector).on('click', function() {
      NewMultiplePages.createNewFormFields();
    });

    $(document).on('change', NewMultiplePages.doktypeSelector, function() {
      NewMultiplePages.actOnTypeSelectChange($(this));
    });
  };

  $(NewMultiplePages.initializeEvents);

  return NewMultiplePages;
});
