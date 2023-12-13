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
import{selector}from"@typo3/core/literals.js";class PasswordElement extends HTMLElement{constructor(){super(...arguments),this.element=null,this.passwordPolicyInfo=null,this.passwordPolicySet=!1}connectedCallback(){const e=this.getAttribute("recordFieldId");null!==e&&(this.element=this.querySelector(selector`#${e}`),this.element&&(this.passwordPolicyInfo=this.querySelector(selector`#password-policy-info-${this.element.id}`),this.passwordPolicySet=""!==(this.getAttribute("passwordPolicy")||""),this.registerEventHandler()))}registerEventHandler(){this.passwordPolicySet&&null!==this.passwordPolicyInfo&&(this.element.addEventListener("focusin",(()=>{this.passwordPolicyInfo.classList.remove("hidden")})),this.element.addEventListener("focusout",(()=>{this.passwordPolicyInfo.classList.add("hidden")})))}}window.customElements.define("typo3-formengine-element-password",PasswordElement);