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
var __decorate=this&&this.__decorate||function(e,t,n,r){var o,i=arguments.length,d=i<3?t:null===r?r=Object.getOwnPropertyDescriptor(t,n):r;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)d=Reflect.decorate(e,t,n,r);else for(var a=e.length-1;a>=0;a--)(o=e[a])&&(d=(i<3?o(d):i>3?o(t,n,d):o(t,n))||d);return i>3&&d&&Object.defineProperty(t,n,d),d};define(["require","exports","lit/decorators","lit","TYPO3/CMS/Backend/Modal","TYPO3/CMS/Backend/Enum/Severity","TYPO3/CMS/Backend/NewContentElementWizard"],(function(e,t,n,r,o,i,d){"use strict";var a;Object.defineProperty(t,"__esModule",{value:!0}),t.NewContentElementWizardButton=void 0;let l=a=class extends r.LitElement{constructor(){super(),this.addEventListener("click",e=>{e.preventDefault(),this.renderWizard()})}static handleModalContentLoaded(e){e&&e.querySelector(".t3-new-content-element-wizard-inner")&&new d.NewContentElementWizard(e)}render(){return r.html`<slot></slot>`}renderWizard(){this.url&&o.advanced({content:this.url,title:this.title,severity:i.SeverityEnum.notice,size:o.sizes.medium,type:o.types.ajax,ajaxCallback:()=>a.handleModalContentLoaded(o.currentModal[0])})}};__decorate([(0,n.property)({type:String})],l.prototype,"url",void 0),__decorate([(0,n.property)({type:String})],l.prototype,"title",void 0),l=a=__decorate([(0,n.customElement)("typo3-backend-new-content-element-wizard-button")],l),t.NewContentElementWizardButton=l}));