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
class c{static trimExplode(r,t){return t.split(r).map(e=>e.trim()).filter(e=>e!=="")}static trimItems(r){return r.map(t=>t instanceof String?t.trim():t)}static intExplode(r,t,e=!1){return t.split(r).map(a=>parseInt(a,10)).filter(a=>!isNaN(a)||e&&a===0)}static isNumber(r){return!isNaN(parseFloat(r.toString()))&&isFinite(r)}static updateQueryStringParameter(r,t,e){console.warn("Utility.updateQueryStringParameter() has been marked as deprecated and will be removed in TYPO3 v14.");const a=new RegExp("([?&])"+t+"=.*?(&|$)","i"),n=r.includes("?")?"&":"?";return r.match(a)?r.replace(a,"$1"+t+"="+e+"$2"):r+n+t+"="+e}static convertFormToObject(r){const t={};return r.querySelectorAll("input, select, textarea").forEach(e=>{const a=e.name,n=e.value;if(a)if(e.tagName.toLowerCase()==="input"&&e.type=="checkbox"){const i=e;t[a]===void 0&&(t[a]=[]),i.checked&&t[a].push(n)}else t[a]=n}),t}static mergeDeep(...r){const t=e=>e&&typeof e=="object";return r.reduce((e,a)=>(Object.keys(a).forEach(n=>{const i=e[n],s=a[n];Array.isArray(i)&&Array.isArray(s)?e[n]=i.concat(...s):t(i)&&t(s)?e[n]=c.mergeDeep(i,s):e[n]=s}),e),{})}static urlsPointToSameServerSideResource(r,t){if(!r||!t)return!1;const e=window.location.origin;try{const a=new URL(r,c.isValidUrl(r)?void 0:e),n=new URL(t,c.isValidUrl(t)?void 0:e),i=a.origin+a.pathname+a.search,s=n.origin+n.pathname+n.search;return i===s}catch{return!1}}static isValidUrl(r){try{return new URL(r),!0}catch{return!1}}}export{c as default};
