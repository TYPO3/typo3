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
 * Module: TYPO3/CMS/Lowlevel/ConfigurationView
 * JavaScript for Configuration View
 * @exports TYPO3/CMS/Lowlevel/ConfigurationView
 */
define(['jquery', 'TYPO3/CMS/Backend/jquery.clearable'], function($) {

  var $searchFields = $('input[name="searchString"]');
  var searchResultShown = ('' !== $searchFields.first().val());

  // make search field clearable
  $searchFields.clearable({
    onClear: function() {
      if (searchResultShown) {
        $(this).closest('form').submit();
      }
    }
  });

  if (self.location.hash) {
    // scroll page down, so the just opened subtree is visible after reload and not hidden by doc header
    $("html, body").scrollTop((document.documentElement.scrollTop || document.body.scrollTop) - 80);
  }
});
