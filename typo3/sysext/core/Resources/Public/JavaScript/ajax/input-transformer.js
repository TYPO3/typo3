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
class s{static byHeader(t,e={}){return"Content-Type"in e&&e["Content-Type"].includes("application/json")?JSON.stringify(t):s.toFormData(t)}static toFormData(t){const e=s.filter(s.flattenObject(t)),i=new FormData;for(const[n,c]of Object.entries(e))i.set(n,c);return i}static toSearchParams(t){if(typeof t=="string")return t;if(t instanceof Array)return t.join("&");const e=s.filter(s.flattenObject(t)),i=new URLSearchParams;for(const[n,c]of Object.entries(e))i.set(n,c);return decodeURI(i.toString())}static flattenObject(t,e=""){return Object.keys(t).reduce((i,n)=>{const c=e.length?e+"[":"",f=e.length?"]":"";return typeof t[n]=="object"&&t[n]!==null?Object.assign(i,s.flattenObject(t[n],c+n+f)):i[c+n+f]=t[n],i},{})}static filter(t){return Object.keys(t).forEach(e=>{typeof t[e]>"u"&&delete t[e]}),t}}export{s as InputTransformer};
