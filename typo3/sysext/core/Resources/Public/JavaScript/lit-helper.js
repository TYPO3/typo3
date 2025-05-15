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
import{render as s,html as i,nothing as a}from"lit";import{until as c}from"lit/directives/until.js";const d=e=>{const n=document.createElement("div");return s(e,n),n.childNodes},u=e=>{const n=document.createElement("div");return s(e,n),n.innerHTML},g=(e,...n)=>{let t=null;if(window.TYPO3&&window.TYPO3.lang&&typeof window.TYPO3.lang[e]=="string"?t=window.TYPO3.lang:top.TYPO3&&top.TYPO3.lang&&typeof top.TYPO3.lang[e]=="string"&&(t=top.TYPO3.lang),t===null)return"";let r=0;return t[e].replace(/%[sdf]/g,l=>{const o=n[r++];switch(l){case"%s":return String(o);case"%d":return String(parseInt(o,10));case"%f":return String(parseFloat(o).toFixed(2));default:return l}})},w=e=>e.reduce((n,t)=>(n[t]=!0,n),{}),T=(e,n)=>{const t=(n||window).litNonce;return t?i`<style nonce=${t}>${e}</style>`:i`<style>${e}</style>`},p=(e,n,t=()=>a)=>c(new Promise(r=>window.setTimeout(()=>r(n()),e)),t());export{w as classesArrayToClassInfo,p as delay,g as lll,u as renderHTML,d as renderNodes,T as styleTag};
