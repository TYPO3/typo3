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
define(["require","exports","TYPO3/CMS/Core/Event/RegularEvent","TYPO3/CMS/Core/DocumentService","TYPO3/CMS/Backend/FormEngine"],(function(e,t,n,s,c){"use strict";return new class{constructor(){this.initialize=(e,t)=>{let s=document.querySelector(e);t=t||{},new n("change",e=>{const t=e.target,n=t.parentElement.querySelector(".input-group-icon");null!==n&&(n.innerHTML=t.options[t.selectedIndex].dataset.icon);const s=t.closest(".t3js-formengine-field-item").querySelector(".t3js-forms-select-single-icons");if(null!==s){const e=s.querySelector(".item.active");null!==e&&e.classList.remove("active");const n=s.querySelector('[data-select-index="'+t.selectedIndex+'"]');null!==n&&n.closest(".item").classList.add("active")}}).bindTo(s),t.onChange instanceof Array?new n("change",()=>{c.processOnFieldChange(t.onChange)}).bindTo(s):"function"==typeof t.onChange&&new n("change",t.onChange).bindTo(s),new n("click",(e,t)=>{const n=t.closest(".t3js-forms-select-single-icons").querySelector(".item.active");null!==n&&n.classList.remove("active"),s.selectedIndex=parseInt(t.dataset.selectIndex,10),s.dispatchEvent(new Event("change")),t.closest(".item").classList.add("active")}).delegateTo(s.closest(".form-control-wrap"),".t3js-forms-select-single-icons .item:not(.active) a")}}initializeOnReady(e,t){s.ready().then(()=>{this.initialize(e,t)})}}}));