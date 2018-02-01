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
 * Module: TYPO3/CMS/Recordlist/FieldSelectBox
 * Check-all / uncheck-all for the Database Recordlist fieldSelectBox
 * @exports TYPO3/CMS/Recordlist/FieldSelectBox
 */
define(['jquery'], function($) {
  'use strict';

  $(function() {
    $('.fieldSelectBox .checkAll').change(function() {
      var checked = $(this).prop('checked');
      var $checkboxes = $('.fieldSelectBox tbody').find(':checkbox');
      $checkboxes.each(function() {
        if (!$(this).prop('disabled')) {
          $(this).prop('checked', checked);
        }
      });
    });
  });

});
