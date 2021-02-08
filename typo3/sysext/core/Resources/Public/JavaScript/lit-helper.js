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
define(["require","exports","lit-html","lit-html/directives/unsafe-html","lit-html/directives/until","TYPO3/CMS/Backend/Icons","TYPO3/CMS/Backend/Element/SpinnerElement"],(function(e,n,t,r,i,l){"use strict";Object.defineProperty(n,"__esModule",{value:!0}),n.icon=n.lll=n.renderHTML=n.renderNodes=void 0,n.renderNodes=e=>{const n=document.createElement("div");return t.render(e,n),n.childNodes},n.renderHTML=e=>{const n=document.createElement("div");return t.render(e,n),n.innerHTML},n.lll=e=>window.TYPO3&&window.TYPO3.lang&&"string"==typeof window.TYPO3.lang[e]?window.TYPO3.lang[e]:"",n.icon=(e,n="small")=>{const d=l.getIcon(e,n).then(e=>t.html`${r.unsafeHTML(e)}`);return t.html`${i.until(d,t.html`<typo3-backend-spinner size="${n}"></typo3-backend-spinner>`)}`}}));