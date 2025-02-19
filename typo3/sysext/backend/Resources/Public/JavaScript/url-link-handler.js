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
import t from"@typo3/backend/link-browser.js";import l from"@typo3/core/event/regular-event.js";class u{constructor(){new l("submit",(r,n)=>{r.preventDefault();const e=n.querySelector('[name="lurl"]').value.trim();e!==""&&t.finalizeFunction(e)}).delegateTo(document,"#lurlform")}}var i=new u;export{i as default};
