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
import r from"@typo3/core/event/regular-event.js";import e from"@typo3/backend/icons.js";class i{constructor(){this.registerEvents()}registerEvents(){new r("click",this.toggleNewButton).delegateTo(document,'input[type="checkbox"][data-lang]')}async toggleNewButton(){const t=document.querySelector(`.t3js-language-new[data-lang="${this.dataset.lang}"]`),a=t.querySelector(".t3js-icon"),n=document.querySelectorAll(`input[type="checkbox"][data-lang="${this.dataset.lang}"]:checked`),s=new URL(location.origin+t.dataset.editUrl);n.forEach(c=>{s.searchParams.set(`cmd[pages][${c.dataset.uid}][localize]`,this.dataset.lang)});const o=n.length===0;t.href=s.toString(),t.classList.toggle("disabled",o);const l=await e.getIcon(a.dataset.identifier,e.sizes.small,null,o?e.states.disabled:e.states.default);a.replaceWith(document.createRange().createContextualFragment(l))}}var d=new i;export{d as default};
