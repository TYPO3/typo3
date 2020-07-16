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
define(["require","exports"],(function(t,e){"use strict";class r{static trimExplode(t,e){return e.split(t).map(t=>t.trim()).filter(t=>""!==t)}static trimItems(t){return t.map(t=>t instanceof String?t.trim():t)}static intExplode(t,e,r=!1){return e.split(t).map(t=>parseInt(t,10)).filter(t=>!isNaN(t)||r&&0===t)}static isNumber(t){return!isNaN(parseFloat(t.toString()))&&isFinite(t)}static getParameterFromUrl(t,e){if("function"!=typeof t.split)return"";const r=t.split("?");let i="";if(r.length>=2){const t=r.join("?"),n=encodeURIComponent(e)+"=",s=t.split(/[&;]/g);for(let t=s.length;t-- >0;)if(-1!==s[t].lastIndexOf(n,0)){i=s[t].split("=")[1];break}}return i}static updateQueryStringParameter(t,e,r){const i=new RegExp("([?&])"+e+"=.*?(&|$)","i"),n=t.includes("?")?"&":"?";return t.match(i)?t.replace(i,"$1"+e+"="+r+"$2"):t+n+e+"="+r}static convertFormToObject(t){const e={};return t.querySelectorAll("input, select, textarea").forEach(t=>{const r=t.name,i=t.value;r&&(t instanceof HTMLInputElement&&"checkbox"==t.type?(void 0===e[r]&&(e[r]=[]),t.checked&&e[r].push(i)):e[r]=i)}),e}static mergeDeep(...t){const e=t=>t&&"object"==typeof t;return t.reduce((t,i)=>(Object.keys(i).forEach(n=>{const s=t[n],c=i[n];Array.isArray(s)&&Array.isArray(c)?t[n]=s.concat(...c):e(s)&&e(c)?t[n]=r.mergeDeep(s,c):t[n]=c}),t),{})}}return r}));