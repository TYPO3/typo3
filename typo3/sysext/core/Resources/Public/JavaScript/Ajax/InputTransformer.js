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
define(["require","exports"],(function(t,e){"use strict";Object.defineProperty(e,"__esModule",{value:!0}),e.InputTransformer=void 0;class r{static byHeader(t,e={}){return e.hasOwnProperty("Content-Type")&&e["Content-Type"].includes("application/json")?JSON.stringify(t):r.toFormData(t)}static toFormData(t){const e=r.filter(r.flattenObject(t)),n=new FormData;for(const[t,r]of Object.entries(e))n.set(t,r);return n}static toSearchParams(t){if("string"==typeof t)return t;if(t instanceof Array)return t.join("&");const e=r.filter(r.flattenObject(t)),n=new URLSearchParams;for(const[t,r]of Object.entries(e))n.set(t,r);return decodeURI(n.toString())}static flattenObject(t,e=""){return Object.keys(t).reduce((n,o)=>{const s=e.length?e+"[":"",a=e.length?"]":"";return"object"==typeof t[o]?Object.assign(n,r.flattenObject(t[o],s+o+a)):n[s+o+a]=t[o],n},{})}static filter(t){return Object.keys(t).forEach(e=>{void 0===t[e]&&delete t[e]}),t}}e.InputTransformer=r}));