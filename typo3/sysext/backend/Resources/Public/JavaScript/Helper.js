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
 * @internal Use in TYPO3 core only, API can change at any time!
 */
(function() {
  "use strict";

  if (!document.currentScript) {
    return false;
  }

  const scriptElement = document.currentScript;
  switch (scriptElement.dataset.action) {
    case 'window.close':
      window.close();
      break;
    default:
  }
})();
