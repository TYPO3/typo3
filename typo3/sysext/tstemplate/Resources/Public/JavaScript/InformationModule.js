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
define(["require","exports","TYPO3/CMS/Core/Event/RegularEvent"],(function(e,t,r){"use strict";return new class{constructor(){this.registerEventListeners()}registerEventListeners(){new r("typo3:datahandler:process",e=>{const t=e.detail.payload;"delete"!==t.action||t.hasErrors||document.location.reload()}).bindTo(document)}}}));