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
import a from"@typo3/backend/link-browser.js";import n from"@typo3/core/event/regular-event.js";class o{constructor(){new n("click",(e,t)=>{e.preventDefault();const r=t.closest("span").dataset;a.finalizeFunction(document.body.dataset.linkbrowserIdentifier+r.uid)}).delegateTo(document,"[data-close]")}}var d=new o;export{d as default};
