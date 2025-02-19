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
import i from"@typo3/backend/link-browser.js";import a from"@typo3/core/event/regular-event.js";class l{constructor(){this.linkPageByTextfield=()=>{let e=document.getElementById("luid").value;if(!e)return;const n=parseInt(e,10);isNaN(n)||(e="t3://page?uid="+n),i.finalizeFunction(e)},new a("click",(t,e)=>{t.preventDefault(),i.finalizeFunction(e.getAttribute("href"))}).delegateTo(document,"a.t3js-pageLink"),new a("click",t=>{t.preventDefault(),this.linkPageByTextfield()}).delegateTo(document,"input.t3js-pageLink")}}var r=new l;export{r as default};
