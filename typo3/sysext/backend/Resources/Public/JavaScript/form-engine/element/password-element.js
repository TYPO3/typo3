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
import s from"@typo3/core/document-service.js";import{selector as t}from"@typo3/core/literals.js";class i extends HTMLElement{constructor(){super(...arguments),this.element=null,this.passwordPolicyInfo=null,this.passwordPolicySet=!1}async connectedCallback(){const e=this.getAttribute("recordFieldId");e!==null&&(await s.ready(),this.element=this.querySelector(t`#${e}`),this.element&&(this.passwordPolicyInfo=this.querySelector(t`#password-policy-info-${this.element.id}`),this.passwordPolicySet=(this.getAttribute("passwordPolicy")||"")!=="",this.registerEventHandler()))}registerEventHandler(){this.passwordPolicySet&&this.passwordPolicyInfo!==null&&(this.element.addEventListener("focusin",()=>{this.passwordPolicyInfo.classList.remove("hidden")}),this.element.addEventListener("focusout",()=>{this.passwordPolicyInfo.classList.add("hidden")}))}}window.customElements.define("typo3-formengine-element-password",i);
