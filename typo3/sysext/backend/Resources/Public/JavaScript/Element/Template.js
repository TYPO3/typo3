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
define(["require","exports","TYPO3/CMS/Core/SecurityUtility"],(function(t,e,i){"use strict";Object.defineProperty(e,"__esModule",{value:!0}),e.unsafe=e.html=e.Template=void 0;class n{constructor(t,e,...n){this.securityUtility=new i,this.unsafe=t,this.strings=e,this.values=n,this.closures=new Map}getHtml(t=null){return null===t&&(t=this),this.strings.map((e,i)=>void 0===this.values[i]?e:e+this.getValue(this.values[i],t)).join("")}getElement(){const t=document.createElement("template");return t.innerHTML=this.getHtml(),t}mountTo(t,e=!1){e&&(t.innerHTML="");const i=this.getElement().content.cloneNode(!0),n=new RegExp("^@closure:(.+)$");i.querySelectorAll("[\\@click]").forEach(t=>{const e=t.getAttribute("@click"),i=n.exec(e),s=this.closures.get(i[1]);null!==i&&null!==s&&(t.removeAttribute("@click"),t.addEventListener("click",t=>s.call(null,t)))}),t.appendChild(i)}getValue(t,e){if(t instanceof Array)return t.map(t=>this.getValue(t,e)).filter(t=>""!==t).join("");if(t instanceof Function){const i=this.securityUtility.getRandomHexValue(20);return e.closures.set(i,t),"@closure:"+i}return t instanceof n||t instanceof Object&&t.constructor===this.constructor?t.getHtml(e):t instanceof Object?JSON.stringify(t):this.unsafe?(t+"").trim():this.securityUtility.encodeHtml(t).trim()}}e.Template=n,e.html=(t,...e)=>new n(!1,t,...e),e.unsafe=(t,...e)=>new n(!0,t,...e)}));