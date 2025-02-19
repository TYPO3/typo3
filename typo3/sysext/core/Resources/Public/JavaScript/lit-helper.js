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
import{render as s,html as i,nothing as l}from"lit";import{until as c}from"lit/directives/until.js";const d=n=>{const e=document.createElement("div");return s(n,e),e.childNodes},a=n=>{const e=document.createElement("div");return s(n,e),e.innerHTML},u=(n,...e)=>{if(!window.TYPO3||!window.TYPO3.lang||typeof window.TYPO3.lang[n]!="string")return"";let t=0;return window.TYPO3.lang[n].replace(/%[sdf]/g,r=>{const o=e[t++];switch(r){case"%s":return String(o);case"%d":return String(parseInt(o,10));case"%f":return String(parseFloat(o).toFixed(2));default:return r}})},w=n=>n.reduce((e,t)=>(e[t]=!0,e),{}),g=(n,e)=>{const t=(e||window).litNonce;return t?i`<style nonce=${t}>${n}</style>`:i`<style>${n}</style>`},m=(n,e,t=()=>l)=>c(new Promise(r=>window.setTimeout(()=>r(e()),n)),t());export{w as classesArrayToClassInfo,m as delay,u as lll,a as renderHTML,d as renderNodes,g as styleTag};
