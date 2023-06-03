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
import{html,render}from"lit";export const renderNodes=e=>{const n=document.createElement("div");return render(e,n),n.childNodes};export const renderHTML=e=>{const n=document.createElement("div");return render(e,n),n.innerHTML};export const lll=e=>window.TYPO3&&window.TYPO3.lang&&"string"==typeof window.TYPO3.lang[e]?window.TYPO3.lang[e]:"";export const classesArrayToClassInfo=e=>e.reduce(((e,n)=>(e[n]=!0,e)),{});export const styleTag=(e,n)=>{const t=(n||window).litNonce;return t?html`<style nonce="${t}">${e}</style>`:html`<style>${e}</style>`};