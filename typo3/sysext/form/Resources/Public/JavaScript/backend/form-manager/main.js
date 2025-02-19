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
import l from"@typo3/core/document-service.js";import"@typo3/backend/input/clearable.js";class a{constructor(){this.clearableElements=null,l.ready().then(()=>{this.clearableElements=document.querySelectorAll(".t3js-clearable"),this.initializeClearableElements()})}initializeClearableElements(){this.clearableElements.forEach(e=>e.clearable())}}var t=new a;export{t as default};
