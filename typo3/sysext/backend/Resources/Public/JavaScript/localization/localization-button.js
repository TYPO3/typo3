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
import{html as m}from"lit";import{PseudoButtonLitElement as f}from"@typo3/backend/element/pseudo-button.js";import{property as p,customElement as y}from"lit/decorators.js";import{ifDefined as b}from"lit/directives/if-defined.js";import{SeverityEnum as s}from"@typo3/backend/enum/severity.js";import d from"@typo3/backend/modal.js";import g from"~labels/backend.wizards.localization";var i=function(n,t,o,a){var c=arguments.length,e=c<3?t:a===null?a=Object.getOwnPropertyDescriptor(t,o):a,l;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")e=Reflect.decorate(n,t,o,a);else for(var u=n.length-1;u>=0;u--)(l=n[u])&&(e=(c<3?l(e):c>3?l(t,o,e):l(t,o))||e);return c>3&&e&&Object.defineProperty(t,o,e),e};let r=class extends f{buttonActivated(){const t=m`<typo3-backend-localization-wizard record-type=${this.recordType} record-uid=${this.recordUid} target-language=${this.targetLanguage} return-url=${b(this.returnUrl)}></typo3-backend-localization-wizard>`;d.advanced({title:g.get("localization_wizard.modal.title"),content:t,severity:s.notice,size:d.sizes.medium,staticBackdrop:!0,buttons:[]})}};i([p({type:String,attribute:"record-type"})],r.prototype,"recordType",void 0),i([p({type:Number,attribute:"record-uid"})],r.prototype,"recordUid",void 0),i([p({type:Number,attribute:"target-language"})],r.prototype,"targetLanguage",void 0),i([p({type:String,attribute:"return-url"})],r.prototype,"returnUrl",void 0),r=i([y("typo3-backend-localization-button")],r);export{r as LocalizationButton};
