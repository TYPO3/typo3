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
import{topLevelModuleImport as l}from"@typo3/backend/utility/top-level-module-import.js";import{topWindow as s}from"@typo3/core/utility/top-window.js";var d;(function(n){n.modalBody=".t3js-modal-body",n.modalContent=".t3js-module-content",n.modalFooter=".t3js-modal-footer"})(d||(d={}));class i{initialize(o){this.currentModal=o}getModalBody(){return this.findInModal(d.modalBody)}getModuleContent(){return this.findInModal(d.modalContent)}getModalFooter(){return this.findInModal(d.modalFooter)}findInModal(o){return this.currentModal.querySelector(o)}setModalButtonsState(o){this.getModalFooter()?.querySelectorAll("button").forEach(e=>{this.setModalButtonState(e,o)})}setModalButtonState(o,e){o.classList.toggle("disabled",!e),o.disabled=!e}async loadModuleFrameAgnostic(o){s!==window?await l(o):await import(o)}renderProgressBar(o,e,r){this.loadModuleFrameAgnostic("@typo3/backend/element/progress-bar-element.js"),o=o||this.currentModal;const t=o.ownerDocument.createElement("typo3-backend-progress-bar");return typeof e=="object"&&Object.keys(e).forEach(a=>{t[a]=e[a]}),r==="append"?o.append(t):r==="prepend"?o.prepend(t):o.replaceChildren(t),t}}export{i as AbstractInteractableModule};
