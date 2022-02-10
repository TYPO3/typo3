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
export class InputTransformer{static byHeader(t,e={}){return e.hasOwnProperty("Content-Type")&&e["Content-Type"].includes("application/json")?JSON.stringify(t):InputTransformer.toFormData(t)}static toFormData(t){const e=InputTransformer.filter(InputTransformer.flattenObject(t)),r=new FormData;for(const[t,n]of Object.entries(e))r.set(t,n);return r}static toSearchParams(t){if("string"==typeof t)return t;if(t instanceof Array)return t.join("&");const e=InputTransformer.filter(InputTransformer.flattenObject(t)),r=new URLSearchParams;for(const[t,n]of Object.entries(e))r.set(t,n);return decodeURI(r.toString())}static flattenObject(t,e=""){return Object.keys(t).reduce((r,n)=>{const a=e.length?e+"[":"",o=e.length?"]":"";return"object"==typeof t[n]?Object.assign(r,InputTransformer.flattenObject(t[n],a+n+o)):r[a+n+o]=t[n],r},{})}static filter(t){return Object.keys(t).forEach(e=>{void 0===t[e]&&delete t[e]}),t}}