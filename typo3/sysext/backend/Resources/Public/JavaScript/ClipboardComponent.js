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
define(["require","exports"],(function(e,t){"use strict";class c{static setCheckboxValue(e,t){const c="CBC["+e+"]",s=document.querySelector('input[name="'+c+'"]');null!==s&&(s.checked=t)}constructor(){this.registerCheckboxTogglers()}registerCheckboxTogglers(){const e=".t3js-toggle-all-checkboxes";document.addEventListener("click",t=>{let s,n=t.target;if(!n.matches(e)){let t=n.closest(e);if(null===t)return;n=t}t.preventDefault(),"checked"in n.dataset&&"none"!==n.dataset.checked?(n.dataset.checked="none",s=!1):(n.dataset.checked="all",s=!0);const o=n.dataset.checkboxesNames.split(",");for(let e of o)c.setCheckboxValue(e,s)})}}return new c}));