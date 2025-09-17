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
import{property as p,customElement as d}from"lit/decorators.js";import{LitElement as f,css as h,html as b}from"lit";import a from"@typo3/backend/modal.js";import{SeverityEnum as m}from"@typo3/backend/enum/severity.js";import"@typo3/backend/new-record-wizard.js";var c=function(n,t,r,o){var s=arguments.length,e=s<3?t:o===null?o=Object.getOwnPropertyDescriptor(t,r):o,l;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")e=Reflect.decorate(n,t,r,o);else for(var u=n.length-1;u>=0;u--)(l=n[u])&&(e=(s<3?l(e):s>3?l(t,r,e):l(t,r))||e);return s>3&&e&&Object.defineProperty(t,r,e),e};let i=class extends f{static{this.styles=[h`:host{cursor:pointer;appearance:button}`]}constructor(){super(),this.addEventListener("click",t=>{t.preventDefault(),this.renderWizard()}),this.addEventListener("keydown",t=>{(t.key==="Enter"||t.key===" ")&&(t.preventDefault(),this.renderWizard())})}connectedCallback(){this.hasAttribute("role")||this.setAttribute("role","button"),this.hasAttribute("tabindex")||this.setAttribute("tabindex","0")}render(){return b`<slot></slot>`}renderWizard(){this.url&&a.advanced({content:this.url,title:this.subject,severity:m.notice,size:a.sizes.large,type:a.types.ajax})}};c([p({type:String})],i.prototype,"url",void 0),c([p({type:String})],i.prototype,"subject",void 0),i=c([d("typo3-scheduler-new-task-wizard-button")],i);export{i as NewSchedulerTaskWizardButton};
