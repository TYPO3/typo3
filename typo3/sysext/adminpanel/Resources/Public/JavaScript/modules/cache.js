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
"use strict";(()=>{class t{constructor(){this.buttons=document.querySelectorAll('[data-typo3-role="clearCacheButton"]'),this.buttons.forEach((t=>{t.addEventListener("click",(()=>{const e=t.dataset.typo3AjaxUrl,o=new XMLHttpRequest;o.open("GET",e),o.send(),o.onload=()=>{location.reload()}}))}))}}window.addEventListener("load",(()=>new t),!1)})();