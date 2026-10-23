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
import{html as m}from"lit";import{PseudoButtonLitElement as f}from"@typo3/backend/element/pseudo-button.js";import{property as u,customElement as s}from"lit/decorators.js";import{ifDefined as y}from"lit/directives/if-defined.js";import{SeverityEnum as b}from"@typo3/backend/enum/severity.js";import l from"@typo3/backend/modal.js";import g from"~labels/backend.wizards.localization";var i=function(n,t,o,a){var c=arguments.length,e=c<3?t:a===null?a=Object.getOwnPropertyDescriptor(t,o):a,p;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")e=Reflect.decorate(n,t,o,a);else for(var d=n.length-1;d>=0;d--)(p=n[d])&&(e=(c<3?p(e):c>3?p(t,o,e):p(t,o))||e);return c>3&&e&&Object.defineProperty(t,o,e),e};let r=class extends f{constructor(){super(...arguments),this.recordType="",this.recordUid=0}buttonActivated(){const t=m`<typo3-backend-localization-wizard record-type=${this.recordType} record-uid=${this.recordUid} target-language=${this.targetLanguage} return-url=${y(this.returnUrl)}></typo3-backend-localization-wizard>`;l.advanced({title:g.get("localization_wizard.modal.title"),content:t,severity:b.notice,size:l.sizes.medium,staticBackdrop:!0,buttons:[]})}};i([u({type:String,attribute:"record-type"})],r.prototype,"recordType",void 0),i([u({type:Number,attribute:"record-uid"})],r.prototype,"recordUid",void 0),i([u({type:Number,attribute:"target-language"})],r.prototype,"targetLanguage",void 0),i([u({type:String,attribute:"return-url"})],r.prototype,"returnUrl",void 0),r=i([s("typo3-backend-localization-button")],r);export{r as LocalizationButton};
