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
define(["require","exports"],(function(e,t){"use strict";class r{static setCheckboxValue(e,t){const r="CBC["+e+"]",s=document.querySelector('form[name="dblistForm"] [name="'+r+'"]');null!==s&&(s.checked=t)}constructor(){this.registerCheckboxTogglers()}registerCheckboxTogglers(){const e="a.t3js-toggle-all-checkboxes";document.addEventListener("click",t=>{let s,c=t.target;if(!c.matches(e)){let t=c.closest(e);if(null===t)return;c=t}t.preventDefault(),""===c.getAttribute("rel")?(c.setAttribute("rel","allChecked"),s=!0):(c.setAttribute("rel",""),s=!1);const l=c.dataset.checkboxesNames.split(",");for(let e of l)r.setCheckboxValue(e,s)})}}return new r}));