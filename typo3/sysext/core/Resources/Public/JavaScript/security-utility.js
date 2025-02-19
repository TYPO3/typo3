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
class o{constructor(t=document){this.documentRef=t}getRandomHexValue(t){if(t<=0||t!==Math.ceil(t))throw new SyntaxError("Length must be a positive integer");const r=new Uint8Array(Math.ceil(t/2));return crypto.getRandomValues(r),Array.from(r).map(e=>e.toString(16).padStart(2,"0")).join("").substr(0,t)}encodeHtml(t,r=!0){const e=this.createAnvil();return r||(t=t.replace(/&[#A-Za-z0-9]+;/g,n=>(e.innerHTML=n,e.innerText))),e.innerText=t,e.innerHTML.replace(/"/g,"&quot;").replace(/'/g,"&apos;")}stripHtml(t){return new DOMParser().parseFromString(t,"text/html").body.textContent||""}debug(t){t!==this.encodeHtml(t)&&console.warn("XSS?!",t)}createAnvil(){return this.documentRef.createElement("span")}}export{o as default};
