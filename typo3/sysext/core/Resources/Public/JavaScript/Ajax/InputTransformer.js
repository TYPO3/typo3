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
define(["require","exports"],(function(t,e){"use strict";Object.defineProperty(e,"__esModule",{value:!0});class n{static byHeader(t,e={}){return e.hasOwnProperty("Content-Type")&&e["Content-Type"].includes("application/json")?JSON.stringify(t):n.toFormData(t)}static toFormData(t){const e=n.flattenObject(t),r=new FormData;for(const[t,n]of Object.entries(e))r.set(t,n);return r}static toSearchParams(t){if("string"==typeof t)return t;if(t instanceof Array)return t.join("&");const e=n.flattenObject(t),r=new URLSearchParams;for(const[t,n]of Object.entries(e))r.set(t,n);return decodeURI(r.toString())}static flattenObject(t,e=""){return Object.keys(t).reduce((r,o)=>{const s=e.length?e+"[":"",a=e.length?"]":"";return"object"==typeof t[o]?Object.assign(r,n.flattenObject(t[o],s+o+a)):r[s+o+a]=t[o],r},{})}}e.InputTransformer=n}));