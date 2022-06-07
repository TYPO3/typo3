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
define(["require","exports","lit/html"],(function(e,n,r){"use strict";Object.defineProperty(n,"__esModule",{value:!0}),n.lll=n.renderHTML=n.renderNodes=void 0;n.renderNodes=e=>{const n=document.createElement("div");return(0,r.render)(e,n),n.childNodes};n.renderHTML=e=>{const n=document.createElement("div");return(0,r.render)(e,n),n.innerHTML};n.lll=e=>window.TYPO3&&window.TYPO3.lang&&"string"==typeof window.TYPO3.lang[e]?window.TYPO3.lang[e]:""}));