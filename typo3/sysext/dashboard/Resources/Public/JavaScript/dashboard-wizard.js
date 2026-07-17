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
import{state as c,query as h,customElement as m}from"lit/decorators.js";import{LitElement as u,html as w}from"lit";import{AutoAdvanceEvent as b}from"@typo3/backend/wizard/events/auto-advance-event.js";import f from"@typo3/dashboard/wizard/steps/preset-step.js";import z from"@typo3/dashboard/wizard/steps/title-step.js";import{DashboardWizardSubmissionService as l}from"@typo3/dashboard/wizard/finisher/dashboard-wizard-submission-service.js";var n=function(s,t,i,o){var a=arguments.length,e=a<3?t:o===null?o=Object.getOwnPropertyDescriptor(t,i):o,d;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")e=Reflect.decorate(s,t,i,o);else for(var p=s.length-1;p>=0;p--)(d=s[p])&&(e=(a<3?d(e):a>3?d(t,i,e):d(t,i))||e);return a>3&&e&&Object.defineProperty(t,i,e),e};let r=class extends u{constructor(){super(...arguments),this.steps=[]}firstUpdated(t){super.firstUpdated(t),this.context={wizard:this.wizard,getStoreData:this.wizard.getStoreData.bind(this.wizard),setStoreData:this.wizard.setStoreData.bind(this.wizard),clearStoreData:this.wizard.clearStoreData.bind(this.wizard),getDataStore:this.wizard.getDataStore.bind(this.wizard),dispatchAutoAdvance:()=>this.wizard.dispatchEvent(new b)},this.steps=[new f(this.context),new z(this.context)],this.submissionService=new l(this.context)}createRenderRoot(){return this}render(){return w`<typo3-backend-wizard .steps=${this.steps} .submissionService=${this.submissionService} skip-summary></typo3-backend-wizard>`}};n([c()],r.prototype,"steps",void 0),n([c()],r.prototype,"submissionService",void 0),n([h("typo3-backend-wizard")],r.prototype,"wizard",void 0),r=n([m("typo3-dashboard-wizard")],r);export{r as DashboardWizard};
