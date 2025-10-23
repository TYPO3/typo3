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
import{property as s,customElement as m}from"lit/decorators.js";import{PseudoButtonLitElement as y}from"@typo3/backend/element/pseudo-button.js";import l from"@typo3/backend/modal.js";import{SeverityEnum as d}from"@typo3/backend/enum/severity.js";var f=function(n,e,o,i){var p=arguments.length,t=p<3?e:i===null?i=Object.getOwnPropertyDescriptor(e,o):i,u;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")t=Reflect.decorate(n,e,o,i);else for(var c=n.length-1;c>=0;c--)(u=n[c])&&(t=(p<3?u(t):p>3?u(e,o,t):u(e,o))||t);return p>3&&t&&Object.defineProperty(e,o,t),t};let r=class extends y{buttonActivated(){this.url&&l.advanced({content:this.url,title:this.subject,severity:d.notice,size:l.sizes.large,type:l.types.ajax})}};f([s({type:String})],r.prototype,"url",void 0),f([s({type:String})],r.prototype,"subject",void 0),r=f([m("typo3-scheduler-setup-check-button")],r);export{r as SetupCheckButton};
