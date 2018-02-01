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
 * Module: TYPO3/CMS/Recordlist/Tooltip
 * API for tooltip windows powered by Twitter Bootstrap.
 * @exports TYPO3/CMS/Recordlist/Tooltip
 */
define(['jquery', 'TYPO3/CMS/Backend/Tooltip'], function($, Tooltip) {
  'use strict';

  $(function() {
    Tooltip.initialize('.table-fit a[title]', {
      delay: {
        show: 500,
        hide: 100
      },
      trigger: 'hover',
      container: 'body'
    });
  });

});
