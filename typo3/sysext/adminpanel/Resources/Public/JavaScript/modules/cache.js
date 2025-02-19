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
(()=>{class o{constructor(){this.buttons=document.querySelectorAll('[data-typo3-role="clearCacheButton"]'),this.buttons.forEach(e=>{e.addEventListener("click",()=>{const n=e.dataset.typo3AjaxUrl,t=new XMLHttpRequest;t.open("GET",n),t.send(),t.onload=()=>{location.reload()}})})}}window.addEventListener("load",()=>new o,!1)})();
