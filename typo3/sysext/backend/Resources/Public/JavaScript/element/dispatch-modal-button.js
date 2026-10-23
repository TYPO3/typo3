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
import{property as f,customElement as m}from"lit/decorators.js";import{PseudoButtonLitElement as a}from"@typo3/backend/element/pseudo-button.js";import l from"@typo3/backend/modal.js";import{SeverityEnum as d}from"@typo3/backend/enum/severity.js";var s=function(i,e,o,n){var p=arguments.length,t=p<3?e:n===null?n=Object.getOwnPropertyDescriptor(e,o):n,u;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")t=Reflect.decorate(i,e,o,n);else for(var c=i.length-1;c>=0;c--)(u=i[c])&&(t=(p<3?u(t):p>3?u(e,o,t):u(e,o))||t);return p>3&&t&&Object.defineProperty(e,o,t),t};let r=class extends a{constructor(){super(...arguments),this.url="",this.subject=""}buttonActivated(){this.url&&l.advanced({content:this.url,title:this.subject,severity:d.notice,size:l.sizes.large,type:l.types.iframe})}};s([f({type:String})],r.prototype,"url",void 0),s([f({type:String})],r.prototype,"subject",void 0),r=s([m("typo3-backend-dispatch-modal-button")],r);export{r as DispatchModalButton};
