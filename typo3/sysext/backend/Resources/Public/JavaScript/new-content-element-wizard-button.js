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
import{property as u,customElement as d}from"lit/decorators.js";import{LitElement as f,css as h,html as m}from"lit";import c from"@typo3/backend/modal.js";import{SeverityEnum as b}from"@typo3/backend/enum/severity.js";import"@typo3/backend/new-record-wizard.js";var p=function(i,t,r,o){var s=arguments.length,e=s<3?t:o===null?o=Object.getOwnPropertyDescriptor(t,r):o,l;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")e=Reflect.decorate(i,t,r,o);else for(var a=i.length-1;a>=0;a--)(l=i[a])&&(e=(s<3?l(e):s>3?l(t,r,e):l(t,r))||e);return s>3&&e&&Object.defineProperty(t,r,e),e};let n=class extends f{static{this.styles=[h`:host{cursor:pointer;appearance:button}`]}constructor(){super(),this.addEventListener("click",t=>{t.preventDefault(),this.renderWizard()}),this.addEventListener("keydown",t=>{(t.key==="Enter"||t.key===" ")&&(t.preventDefault(),this.renderWizard())})}connectedCallback(){this.hasAttribute("role")||this.setAttribute("role","button"),this.hasAttribute("tabindex")||this.setAttribute("tabindex","0")}render(){return m`<slot></slot>`}renderWizard(){this.url&&c.advanced({content:this.url,title:this.subject,severity:b.notice,size:c.sizes.large,type:c.types.ajax})}};p([u({type:String})],n.prototype,"url",void 0),p([u({type:String})],n.prototype,"subject",void 0),n=p([d("typo3-backend-new-content-element-wizard-button")],n);export{n as NewContentElementWizardButton};
