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
class i{static createUrl(t,e){const s=new URL(t,window.origin);if(e!==void 0){const n=i.createSearchParams(e);for(const[f,r]of n)s.searchParams.set(f,r)}return s}static createSearchParams(t){return t instanceof URLSearchParams?t:typeof t=="string"?new URLSearchParams(t):new URLSearchParams(i.flattenObject(t))}static flattenObject(t,e=""){return Object.keys(t).reduce((s,n)=>{if(t[n]===void 0||t[n]===null)return s;const f=e.length?e+"[":"",r=e.length?"]":"";return typeof t[n]=="object"?{...s,...i.flattenObject(Array.isArray(t[n])?Object.fromEntries(t[n].map((c,a)=>[a,c])):t[n],f+n+r)}:{...s,[f+n+r]:String(t[n])}},{})}}export{i as UrlFactory};
