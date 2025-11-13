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
import{html as m}from"lit";import{PseudoButtonLitElement as f}from"@typo3/backend/element/pseudo-button.js";import{property as d,customElement as y}from"lit/decorators.js";import{SeverityEnum as b}from"@typo3/backend/enum/severity.js";import{lll as s}from"@typo3/core/lit-helper.js";import u from"@typo3/backend/modal.js";var l=function(i,t,r,n){var a=arguments.length,e=a<3?t:n===null?n=Object.getOwnPropertyDescriptor(t,r):n,c;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")e=Reflect.decorate(i,t,r,n);else for(var p=i.length-1;p>=0;p--)(c=i[p])&&(e=(a<3?c(e):a>3?c(t,r,e):c(t,r))||e);return a>3&&e&&Object.defineProperty(t,r,e),e};let o=class extends f{buttonActivated(){const t=m`<typo3-backend-localization-wizard record-type=${this.recordType} record-uid=${this.recordUid} target-language=${this.targetLanguage}></typo3-backend-localization-wizard>`;u.advanced({title:s("localization_wizard.modal.title"),content:t,severity:b.notice,size:u.sizes.medium,staticBackdrop:!0,buttons:[]})}};l([d({type:String,attribute:"record-type"})],o.prototype,"recordType",void 0),l([d({type:Number,attribute:"record-uid"})],o.prototype,"recordUid",void 0),l([d({type:Number,attribute:"target-language"})],o.prototype,"targetLanguage",void 0),o=l([y("typo3-backend-localization-button")],o);export{o as LocalizationButton};
