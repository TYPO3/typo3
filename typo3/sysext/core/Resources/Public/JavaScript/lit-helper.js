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
import{render as s,html as a,nothing as d}from"lit";import{until as c}from"lit/directives/until.js";import{topWindow as r}from"@typo3/core/utility/top-window.js";const u=e=>{const n=document.createElement("div");return s(e,n),n.childNodes},g=e=>{const n=document.createElement("div");return s(e,n),n.innerHTML},w=(e,...n)=>{let t=null;if(window.TYPO3&&window.TYPO3.lang&&typeof window.TYPO3.lang[e]=="string"?t=window.TYPO3.lang:r.TYPO3&&r.TYPO3.lang&&typeof r.TYPO3.lang[e]=="string"&&(t=r.TYPO3.lang),t===null)return"";let o=0;return t[e].replace(/%[sdf]/g,i=>{const l=n[o++];switch(i){case"%s":return String(l);case"%d":return String(parseInt(l,10));case"%f":return String(parseFloat(l).toFixed(2));default:return i}})},T=e=>e.reduce((n,t)=>(n[t]=!0,n),{}),f=(e,n)=>{const t=(n||window).litNonce;return t?a`<style nonce=${t}>${e}</style>`:a`<style>${e}</style>`},m=(e,n,t=()=>d)=>c(new Promise(o=>window.setTimeout(()=>o(n()),e)),t());export{T as classesArrayToClassInfo,m as delay,w as lll,g as renderHTML,u as renderNodes,f as styleTag};
