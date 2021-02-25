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
var __decorate=this&&this.__decorate||function(t,e,o,r){var i,l=arguments.length,n=l<3?e:null===r?r=Object.getOwnPropertyDescriptor(e,o):r;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)n=Reflect.decorate(t,e,o,r);else for(var d=t.length-1;d>=0;d--)(i=t[d])&&(n=(l<3?i(n):l>3?i(e,o,n):i(e,o))||n);return l>3&&n&&Object.defineProperty(e,o,n),n};define(["require","exports","lit","lit/decorators","TYPO3/CMS/Backend/Modal"],(function(t,e,o,r,i){"use strict";var l;Object.defineProperty(e,"__esModule",{value:!0}),function(t){t.modalBody=".t3js-modal-body"}(l||(l={}));let n=class extends o.LitElement{constructor(){super(),this.addEventListener("click",t=>{t.preventDefault(),this.showTotpAuthUrlModal()})}render(){return o.html`<slot></slot>`}showTotpAuthUrlModal(){i.advanced({title:this.title,buttons:[{trigger:()=>i.dismiss(),text:this.ok||"OK",active:!0,btnClass:"btn-default",name:"ok"}],callback:t=>{o.render(o.html`
            <p>${this.description}</p>
            <pre>${this.url}</pre>
          `,t[0].querySelector(l.modalBody))}})}};__decorate([r.property({type:String})],n.prototype,"url",void 0),__decorate([r.property({type:String})],n.prototype,"title",void 0),__decorate([r.property({type:String})],n.prototype,"description",void 0),__decorate([r.property({type:String})],n.prototype,"ok",void 0),n=__decorate([r.customElement("typo3-mfa-totp-url-info-button")],n)}));