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
var __decorate=this&&this.__decorate||function(e,t,o,r){var c,l=arguments.length,i=l<3?t:null===r?r=Object.getOwnPropertyDescriptor(t,o):r;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)i=Reflect.decorate(e,t,o,r);else for(var n=e.length-1;n>=0;n--)(c=e[n])&&(i=(l<3?c(i):l>3?c(t,o,i):c(t,o))||i);return l>3&&i&&Object.defineProperty(t,o,i),i};define(["require","exports","lit","lit/decorators","TYPO3/CMS/Backend/Notification","TYPO3/CMS/Core/lit-helper"],(function(e,t,o,r,c,l){"use strict";Object.defineProperty(t,"__esModule",{value:!0});let i=class extends o.LitElement{constructor(){super(),this.addEventListener("click",e=>{e.preventDefault(),this.copyToClipboard()})}render(){return o.html`<slot></slot>`}copyToClipboard(){if("string"!=typeof this.text||!this.text.length)return console.warn("No text for copy to clipboard given."),void c.error((0,l.lll)("copyToClipboard.error"));if(navigator.clipboard)navigator.clipboard.writeText(this.text).then(()=>{c.success((0,l.lll)("copyToClipboard.success"),"",1)}).catch(()=>{c.error((0,l.lll)("copyToClipboard.error"))});else{const e=document.createElement("textarea");e.value=this.text,document.body.appendChild(e),e.focus(),e.select();try{document.execCommand("copy")?c.success((0,l.lll)("copyToClipboard.success"),"",1):c.error((0,l.lll)("copyToClipboard.error"))}catch(e){c.error((0,l.lll)("copyToClipboard.error"))}document.body.removeChild(e)}}};__decorate([(0,r.property)({type:String})],i.prototype,"text",void 0),i=__decorate([(0,r.customElement)("typo3-copy-to-clipboard")],i)}));