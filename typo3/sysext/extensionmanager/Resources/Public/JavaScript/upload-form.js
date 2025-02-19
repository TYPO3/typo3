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
import o from"@typo3/core/ajax/ajax-request.js";import l from"@typo3/core/event/regular-event.js";class r{constructor(){this.expandedUploadFormClass="transformed"}initializeEvents(){new l("click",(s,a)=>{const e=document.querySelector(".extension-upload-form");s.preventDefault(),e.classList.contains(this.expandedUploadFormClass)?(e.style.display="none",e.classList.remove(this.expandedUploadFormClass)):(e.style.display="",e.classList.add(this.expandedUploadFormClass),new o(a.href).get().then(async t=>{e.querySelector(".t3js-upload-form-target").innerHTML=await t.resolve()}))}).delegateTo(document,".t3js-upload")}}export{r as default};
