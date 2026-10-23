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
import{property as m,customElement as f}from"lit/decorators.js";import{PseudoButtonLitElement as a}from"@typo3/backend/element/pseudo-button.js";import c from"@typo3/backend/modal.js";import{SeverityEnum as d}from"@typo3/backend/enum/severity.js";import"@typo3/backend/new-record-wizard.js";var s=function(r,e,o,i){var u=arguments.length,t=u<3?e:i===null?i=Object.getOwnPropertyDescriptor(e,o):i,p;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")t=Reflect.decorate(r,e,o,i);else for(var l=r.length-1;l>=0;l--)(p=r[l])&&(t=(u<3?p(t):u>3?p(e,o,t):p(e,o))||t);return u>3&&t&&Object.defineProperty(e,o,t),t};let n=class extends a{constructor(){super(...arguments),this.url="",this.subject=""}buttonActivated(){this.url&&c.advanced({content:this.url,title:this.subject,severity:d.notice,size:c.sizes.large,type:c.types.ajax})}};s([m({type:String})],n.prototype,"url",void 0),s([m({type:String})],n.prototype,"subject",void 0),n=s([f("typo3-backend-new-content-element-wizard-button")],n);export{n as NewContentElementWizardButton};
