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
define(["require","exports"],(function(t,e){"use strict";Object.defineProperty(e,"__esModule",{value:!0});class r{static toFormData(t){const e=r.flattenObject(t),n=new FormData;for(const[t,r]of Object.entries(e))n.set(t,r);return n}static toSearchParams(t){if("string"==typeof t)return t;if(t instanceof Array)return t.join("&");const e=r.flattenObject(t),n=new URLSearchParams;for(const[t,r]of Object.entries(e))n.set(t,r);return decodeURI(n.toString())}static flattenObject(t,e=""){return Object.keys(t).reduce((n,c)=>{const s=e.length?e+"[":"",o=e.length?"]":"";return"object"==typeof t[c]?Object.assign(n,r.flattenObject(t[c],s+c+o)):n[s+c+o]=t[c],n},{})}}e.InputTransformer=r}));