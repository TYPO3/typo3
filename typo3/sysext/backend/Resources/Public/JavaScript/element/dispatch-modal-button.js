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
import{property as p,customElement as h}from"lit/decorators.js";import{LitElement as f,css as m,html as b}from"lit";import l from"@typo3/backend/modal.js";import{SeverityEnum as y}from"@typo3/backend/enum/severity.js";import{KeyTypesEnum as u}from"@typo3/backend/enum/key-types.js";var d=function(n,t,r,o){var s=arguments.length,e=s<3?t:o===null?o=Object.getOwnPropertyDescriptor(t,r):o,a;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")e=Reflect.decorate(n,t,r,o);else for(var c=n.length-1;c>=0;c--)(a=n[c])&&(e=(s<3?a(e):s>3?a(t,r,e):a(t,r))||e);return s>3&&e&&Object.defineProperty(t,r,e),e};let i=class extends f{static{this.styles=[m`:host{cursor:pointer;appearance:button}`]}connectedCallback(){super.connectedCallback(),this.hasAttribute("role")||this.setAttribute("role","button"),this.hasAttribute("tabindex")||this.setAttribute("tabindex","0"),this.addEventListener("click",this.triggerWizard),this.addEventListener("keydown",this.triggerWizard)}disconnectedCallback(){super.disconnectedCallback(),this.removeEventListener("click",this.triggerWizard),this.removeEventListener("keydown",this.triggerWizard)}render(){return b`<slot></slot>`}triggerWizard(t){t instanceof KeyboardEvent?(t.key===u.ENTER||t.key===u.SPACE)&&t.preventDefault():t.preventDefault(),this.renderWizard()}renderWizard(){this.url&&l.advanced({content:this.url,title:this.subject,severity:y.notice,size:l.sizes.large,type:l.types.iframe})}};d([p({type:String})],i.prototype,"url",void 0),d([p({type:String})],i.prototype,"subject",void 0),i=d([h("typo3-backend-dispatch-modal-button")],i);export{i as DispatchModalButton};
