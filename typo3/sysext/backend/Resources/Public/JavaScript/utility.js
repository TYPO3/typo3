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
class c{static trimExplode(e,r){return r.split(e).map(t=>t.trim()).filter(t=>t!=="")}static trimItems(e){return e.map(r=>r instanceof String?r.trim():r)}static intExplode(e,r,t=!1){return r.split(e).map(i=>parseInt(i,10)).filter(i=>!isNaN(i)||t&&i===0)}static isNumber(e){return!isNaN(parseFloat(e.toString()))&&isFinite(e)}static convertFormToObject(e){const r={};return e.querySelectorAll("input, select, textarea").forEach(t=>{const i=t.name,n=t.value;if(i)if(t.tagName.toLowerCase()==="input"&&t.type=="checkbox"){const s=t;r[i]===void 0&&(r[i]=[]),s.checked&&r[i].push(n)}else r[i]=n}),r}static mergeDeep(...e){const r=t=>t&&typeof t=="object";return e.reduce((t,i)=>(Object.keys(i).forEach(n=>{const s=t[n],a=i[n];Array.isArray(s)&&Array.isArray(a)?t[n]=s.concat(...a):r(s)&&r(a)?t[n]=c.mergeDeep(s,a):t[n]=a}),t),{})}static urlsPointToSameServerSideResource(e,r){if(!e||!r)return!1;const t=window.location.origin;try{const i=new URL(e,c.isValidUrl(e)?void 0:t),n=new URL(r,c.isValidUrl(r)?void 0:t),s=i.origin+i.pathname+i.search,a=n.origin+n.pathname+n.search;return s===a}catch{return!1}}static isValidUrl(e){try{return new URL(e),!0}catch{return!1}}}export{c as default};
