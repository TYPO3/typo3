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
import n from"@typo3/core/document-service.js";import{selector as i}from"@typo3/core/literals.js";class l extends HTMLElement{constructor(){super(...arguments),this.element=null,this.passwordPolicyInfo=null,this.passwordPolicySet=!1,this.copyToClipboard=null}async connectedCallback(){if(this.element!==null)return;const e=this.getAttribute("recordFieldId");e!==null&&(await n.ready(),this.element=this.querySelector(i`#${e}`),this.element&&(this.passwordPolicyInfo=this.querySelector(i`#password-policy-info-${this.element.id}`),this.passwordPolicySet=(this.getAttribute("passwordPolicy")||"")!=="",this.copyToClipboard=this.querySelector("typo3-copy-to-clipboard"),this.registerEventHandler()))}registerEventHandler(){this.passwordPolicySet&&this.passwordPolicyInfo!==null&&(this.element.addEventListener("focusin",()=>{this.passwordPolicyInfo.classList.remove("hidden")}),this.element.addEventListener("focusout",()=>{this.passwordPolicyInfo.classList.add("hidden")}));const e=this.copyToClipboard;if(e!==null){const t=this.element,s=()=>{const o=t.value!=="";e.text=t.value,e.hidden=!o,e.parentElement?.classList.toggle("input-group",o)};t.addEventListener("input",s),t.addEventListener("change",s)}}}window.customElements.define("typo3-formengine-element-password",l);
