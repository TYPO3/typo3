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
var __decorate=this&&this.__decorate||function(t,e,o,r){var i,n=arguments.length,l=n<3?e:null===r?r=Object.getOwnPropertyDescriptor(e,o):r;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)l=Reflect.decorate(t,e,o,r);else for(var d=t.length-1;d>=0;d--)(i=t[d])&&(l=(n<3?i(l):n>3?i(e,o,l):i(e,o))||l);return n>3&&l&&Object.defineProperty(e,o,l),l};define(["require","exports","lit","lit/decorators","TYPO3/CMS/Backend/Modal"],(function(t,e,o,r,i){"use strict";var n;Object.defineProperty(e,"__esModule",{value:!0}),function(t){t.modalBody=".t3js-modal-body"}(n||(n={}));let l=class extends o.LitElement{constructor(){super(),this.addEventListener("click",t=>{t.preventDefault(),this.showTotpAuthUrlModal()})}render(){return o.html`<slot></slot>`}showTotpAuthUrlModal(){i.advanced({title:this.title,content:"",buttons:[{trigger:()=>i.dismiss(),text:this.ok||"OK",active:!0,btnClass:"btn-default",name:"ok"}],callback:t=>{(0,o.render)(o.html`
            <p>${this.description}</p>
            <pre>${this.url}</pre>
          `,t[0].querySelector(n.modalBody))}})}};__decorate([(0,r.property)({type:String})],l.prototype,"url",void 0),__decorate([(0,r.property)({type:String})],l.prototype,"title",void 0),__decorate([(0,r.property)({type:String})],l.prototype,"description",void 0),__decorate([(0,r.property)({type:String})],l.prototype,"ok",void 0),l=__decorate([(0,r.customElement)("typo3-mfa-totp-url-info-button")],l)}));