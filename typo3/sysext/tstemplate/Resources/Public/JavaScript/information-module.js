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
import t from"@typo3/core/event/regular-event.js";class r{constructor(){this.registerEventListeners()}registerEventListeners(){new t("typo3:datahandler:process",o=>{const e=o.detail.payload;e.action==="delete"&&!e.hasErrors&&document.location.reload()}).bindTo(document)}}var a=new r;export{a as default};
