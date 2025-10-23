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
import{property as s,customElement as m}from"lit/decorators.js";import{PseudoButtonLitElement as a}from"@typo3/backend/element/pseudo-button.js";import u from"@typo3/backend/modal.js";import{SeverityEnum as d}from"@typo3/backend/enum/severity.js";var f=function(i,e,o,n){var p=arguments.length,t=p<3?e:n===null?n=Object.getOwnPropertyDescriptor(e,o):n,l;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")t=Reflect.decorate(i,e,o,n);else for(var c=i.length-1;c>=0;c--)(l=i[c])&&(t=(p<3?l(t):p>3?l(e,o,t):l(e,o))||t);return p>3&&t&&Object.defineProperty(e,o,t),t};let r=class extends a{buttonActivated(){this.url&&u.advanced({content:this.url,title:this.subject,severity:d.notice,size:u.sizes.large,type:u.types.iframe})}};f([s({type:String})],r.prototype,"url",void 0),f([s({type:String})],r.prototype,"subject",void 0),r=f([m("typo3-backend-dispatch-modal-button")],r);export{r as DispatchModalButton};
