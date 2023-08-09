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
"use strict";class PasswordElement extends HTMLElement{constructor(){super(...arguments),this.element=null,this.passwordPolicyInfo=null,this.passwordPolicySet=!1}connectedCallback(){this.element=this.querySelector("#"+(this.getAttribute("recordFieldId")||"")),this.passwordPolicyInfo=this.querySelector("#password-policy-info-"+this.element.id),this.passwordPolicySet=""!==(this.getAttribute("passwordPolicy")||""),this.element&&this.registerEventHandler()}registerEventHandler(){this.passwordPolicySet&&(this.element.addEventListener("focusin",(()=>{this.passwordPolicyInfo.classList.remove("hidden")})),this.element.addEventListener("focusout",(()=>{this.passwordPolicyInfo.classList.add("hidden")})))}}window.customElements.define("typo3-formengine-element-password",PasswordElement);