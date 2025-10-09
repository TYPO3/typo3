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
import{property as a,customElement as d}from"lit/decorators.js";import{LitElement as h,css as f,html as b}from"lit";import c from"@typo3/backend/modal.js";import{SeverityEnum as m}from"@typo3/backend/enum/severity.js";var p=function(n,t,r,i){var s=arguments.length,e=s<3?t:i===null?i=Object.getOwnPropertyDescriptor(t,r):i,l;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")e=Reflect.decorate(n,t,r,i);else for(var u=n.length-1;u>=0;u--)(l=n[u])&&(e=(s<3?l(e):s>3?l(t,r,e):l(t,r))||e);return s>3&&e&&Object.defineProperty(t,r,e),e};let o=class extends h{static{this.styles=[f`:host{cursor:pointer;appearance:button}`]}constructor(){super(),this.addEventListener("click",t=>{t.preventDefault(),this.renderModal()}),this.addEventListener("keydown",t=>{(t.key==="Enter"||t.key===" ")&&(t.preventDefault(),this.renderModal())})}connectedCallback(){this.hasAttribute("role")||this.setAttribute("role","button"),this.hasAttribute("tabindex")||this.setAttribute("tabindex","0")}render(){return b`<slot></slot>`}renderModal(){this.url&&c.advanced({content:this.url,title:this.subject,severity:m.notice,size:c.sizes.large,type:c.types.ajax})}};p([a({type:String})],o.prototype,"url",void 0),p([a({type:String})],o.prototype,"subject",void 0),o=p([d("typo3-scheduler-setup-check-button")],o);export{o as SetupCheckButton};
