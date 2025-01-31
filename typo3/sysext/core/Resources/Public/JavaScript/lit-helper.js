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
import{html,render,nothing}from"lit";import{until}from"lit/directives/until.js";export const renderNodes=e=>{const t=document.createElement("div");return render(e,t),t.childNodes};export const renderHTML=e=>{const t=document.createElement("div");return render(e,t),t.innerHTML};export const lll=e=>window.TYPO3&&window.TYPO3.lang&&"string"==typeof window.TYPO3.lang[e]?window.TYPO3.lang[e]:"";export const classesArrayToClassInfo=e=>e.reduce(((e,t)=>(e[t]=!0,e)),{});export const styleTag=(e,t)=>{const n=(t||window).litNonce;return n?html`<style nonce="${n}">${e}</style>`:html`<style>${e}</style>`};export const delay=(e,t,n=(()=>nothing))=>until(new Promise((n=>window.setTimeout((()=>n(t())),e))),n());