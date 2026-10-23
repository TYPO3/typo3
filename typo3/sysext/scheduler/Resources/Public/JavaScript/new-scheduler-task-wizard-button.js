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
import{property as f,customElement as a}from"lit/decorators.js";import{PseudoButtonLitElement as m}from"@typo3/backend/element/pseudo-button.js";import c from"@typo3/backend/modal.js";import{SeverityEnum as d}from"@typo3/backend/enum/severity.js";import"@typo3/backend/new-record-wizard.js";var l=function(i,e,r,n){var u=arguments.length,t=u<3?e:n===null?n=Object.getOwnPropertyDescriptor(e,r):n,p;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")t=Reflect.decorate(i,e,r,n);else for(var s=i.length-1;s>=0;s--)(p=i[s])&&(t=(u<3?p(t):u>3?p(e,r,t):p(e,r))||t);return u>3&&t&&Object.defineProperty(e,r,t),t};let o=class extends m{constructor(){super(...arguments),this.url="",this.subject=""}buttonActivated(){this.url&&c.advanced({content:this.url,title:this.subject,severity:d.notice,size:c.sizes.large,type:c.types.ajax})}};l([f({type:String})],o.prototype,"url",void 0),l([f({type:String})],o.prototype,"subject",void 0),o=l([a("typo3-scheduler-new-task-wizard-button")],o);export{o as NewSchedulerTaskWizardButton};
