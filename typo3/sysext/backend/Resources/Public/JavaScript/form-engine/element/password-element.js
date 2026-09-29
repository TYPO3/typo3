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
import i from"@typo3/core/document-service.js";import{selector as s}from"@typo3/core/literals.js";class o extends HTMLElement{constructor(){super(...arguments),this.element=null,this.passwordPolicyInfo=null,this.passwordPolicySet=!1,this.copyToClipboard=null}async connectedCallback(){if(this.element!==null)return;const e=this.getAttribute("recordFieldId");e!==null&&(await i.ready(),this.element=this.querySelector(s`#${e}`),this.element&&(this.passwordPolicyInfo=this.querySelector(s`#password-policy-info-${this.element.id}`),this.passwordPolicySet=(this.getAttribute("passwordPolicy")||"")!=="",this.copyToClipboard=this.querySelector("typo3-copy-to-clipboard"),this.registerEventHandler()))}registerEventHandler(){if(this.passwordPolicySet&&this.passwordPolicyInfo!==null&&(this.element.addEventListener("focusin",()=>{this.passwordPolicyInfo.classList.remove("hidden")}),this.element.addEventListener("focusout",()=>{this.passwordPolicyInfo.classList.add("hidden")})),this.copyToClipboard!==null){const e=()=>{const t=this.element.value!=="";this.copyToClipboard.text=this.element.value,this.copyToClipboard.hidden=!t,this.copyToClipboard.parentElement.classList.toggle("input-group",t)};this.element.addEventListener("input",e),this.element.addEventListener("change",e)}}}window.customElements.define("typo3-formengine-element-password",o);
