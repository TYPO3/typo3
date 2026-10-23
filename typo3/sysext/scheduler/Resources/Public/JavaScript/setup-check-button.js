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
import{property as f,customElement as m}from"lit/decorators.js";import{PseudoButtonLitElement as h}from"@typo3/backend/element/pseudo-button.js";import s from"@typo3/backend/modal.js";import{SeverityEnum as y}from"@typo3/backend/enum/severity.js";var l=function(n,e,o,u){var i=arguments.length,t=i<3?e:u===null?u=Object.getOwnPropertyDescriptor(e,o):u,p;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")t=Reflect.decorate(n,e,o,u);else for(var c=n.length-1;c>=0;c--)(p=n[c])&&(t=(i<3?p(t):i>3?p(e,o,t):p(e,o))||t);return i>3&&t&&Object.defineProperty(e,o,t),t};let r=class extends h{constructor(){super(...arguments),this.url="",this.subject=""}buttonActivated(){this.url&&s.advanced({content:this.url,title:this.subject,severity:y.notice,size:s.sizes.large,type:s.types.ajax})}};l([f({type:String})],r.prototype,"url",void 0),l([f({type:String})],r.prototype,"subject",void 0),r=l([m("typo3-scheduler-setup-check-button")],r);export{r as SetupCheckButton};
