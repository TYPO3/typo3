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
import s from"@typo3/backend/element-browser.js";import o from"@typo3/core/event/regular-event.js";class r{constructor(){new o("click",(a,t)=>{a.preventDefault();const e=t.closest("span").dataset;s.insertElement(e.table,e.uid,e.title,"",parseInt(t.dataset.close||"0",10)===1)}).delegateTo(document,"[data-close]")}}var l=new r;export{l as default};
