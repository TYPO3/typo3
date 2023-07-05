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
define(["require","exports"],(function(t,e){"use strict";class r{static trimExplode(t,e){return e.split(t).map(t=>t.trim()).filter(t=>""!==t)}static trimItems(t){return t.map(t=>t instanceof String?t.trim():t)}static intExplode(t,e,r=!1){return e.split(t).map(t=>parseInt(t,10)).filter(t=>!isNaN(t)||r&&0===t)}static isNumber(t){return!isNaN(parseFloat(t.toString()))&&isFinite(t)}static getParameterFromUrl(t,e){if("function"!=typeof t.split)return"";const r=t.split("?");let i="";if(r.length>=2){const t=r.join("?"),n=encodeURIComponent(e)+"=",a=t.split(/[&;]/g);for(let t=a.length;t-- >0;)if(-1!==a[t].lastIndexOf(n,0)){i=a[t].split("=")[1];break}}return i}static updateQueryStringParameter(t,e,r){const i=new RegExp("([?&])"+e+"=.*?(&|$)","i"),n=t.includes("?")?"&":"?";return t.match(i)?t.replace(i,"$1"+e+"="+r+"$2"):t+n+e+"="+r}static convertFormToObject(t){const e={};return t.querySelectorAll("input, select, textarea").forEach(t=>{const r=t.name,i=t.value;if(r)if("input"===t.tagName.toLowerCase()&&"checkbox"==t.type){const n=t;void 0===e[r]&&(e[r]=[]),n.checked&&e[r].push(i)}else e[r]=i}),e}static mergeDeep(...t){const e=t=>t&&"object"==typeof t;return t.reduce((t,i)=>(Object.keys(i).forEach(n=>{const a=t[n],s=i[n];Array.isArray(a)&&Array.isArray(s)?t[n]=a.concat(...s):e(a)&&e(s)?t[n]=r.mergeDeep(a,s):t[n]=s}),t),{})}static urlsPointToSameServerSideResource(t,e){if(!t||!e)return!1;const i=window.location.origin;try{const n=new URL(t,r.isValidUrl(t)?void 0:i),a=new URL(e,r.isValidUrl(e)?void 0:i),s=n.origin+n.pathname+n.search;return s===a.origin+a.pathname+a.search}catch(t){return!1}}static isValidUrl(t){try{return new URL(t),!0}catch(t){return!1}}}return r}));