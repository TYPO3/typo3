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
define(["require","exports"],(function(e,i){"use strict";Object.defineProperty(i,"__esModule",{value:!0}),i.MessageUtility=void 0;class t{static getOrigin(){return window.origin}static verifyOrigin(e){return t.getOrigin()===e}static send(e,i=window){i.postMessage(e,t.getOrigin())}}i.MessageUtility=t}));