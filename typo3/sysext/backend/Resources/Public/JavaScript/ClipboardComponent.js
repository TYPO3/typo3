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
define(["require","exports"],(function(e,t){"use strict";class c{static setCheckboxValue(e,t){const c="CBC["+e+"]",s=document.querySelector('form[name="dblistForm"] [name="'+c+'"]');null!==s&&(s.checked=t)}constructor(){this.registerCheckboxTogglers()}registerCheckboxTogglers(){const e=".t3js-toggle-all-checkboxes";document.addEventListener("click",t=>{let s,o=t.target;if(!o.matches(e)){let t=o.closest(e);if(null===t)return;o=t}t.preventDefault(),"checked"in o.dataset&&"none"!==o.dataset.checked?(o.dataset.checked="none",s=!1):(o.dataset.checked="all",s=!0);const n=o.dataset.checkboxesNames.split(",");for(let e of n)c.setCheckboxValue(e,s)})}}return new c}));