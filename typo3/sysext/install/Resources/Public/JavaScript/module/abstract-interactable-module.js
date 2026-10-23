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
import{topLevelModuleImport as l}from"@typo3/backend/utility/top-level-module-import.js";import{topWindow as s}from"@typo3/core/utility/top-window.js";var r;(function(a){a.modalBody=".t3js-modal-body",a.modalContent=".t3js-module-content",a.modalFooter=".t3js-modal-footer"})(r||(r={}));class i{initialize(t){this.currentModal=t}getModalBody(){return this.findInModal(r.modalBody)}getModuleContent(){return this.findInModal(r.modalContent)}getModalFooter(){return this.findInModal(r.modalFooter)}findInModal(t){return this.currentModal.querySelector(t)}setModalButtonsState(t){this.getModalFooter()?.querySelectorAll("button").forEach(e=>{this.setModalButtonState(e,t)})}setModalButtonState(t,e){t.classList.toggle("disabled",!e),t.disabled=!e}async loadModuleFrameAgnostic(t){s!==window?await l(t):await import(t)}renderProgressBar(t,e,d){this.loadModuleFrameAgnostic("@typo3/backend/element/progress-bar-element.js");const n=t??this.currentModal,o=n.ownerDocument.createElement("typo3-backend-progress-bar");return typeof e=="object"&&Object.assign(o,e),d==="append"?n.append(o):d==="prepend"?n.prepend(o):n.replaceChildren(o),o}}export{i as AbstractInteractableModule};
