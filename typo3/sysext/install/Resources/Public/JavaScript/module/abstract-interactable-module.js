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
import{topLevelModuleImport as d}from"@typo3/backend/utility/top-level-module-import.js";var n;(function(a){a.modalBody=".t3js-modal-body",a.modalContent=".t3js-module-content",a.modalFooter=".t3js-modal-footer"})(n||(n={}));class s{initialize(o){this.currentModal=o}getModalBody(){return this.findInModal(n.modalBody)}getModuleContent(){return this.findInModal(n.modalContent)}getModalFooter(){return this.findInModal(n.modalFooter)}findInModal(o){return this.currentModal.querySelector(o)}setModalButtonsState(o){this.getModalFooter()?.querySelectorAll("button").forEach(e=>{this.setModalButtonState(e,o)})}setModalButtonState(o,e){o.classList.toggle("disabled",!e),o.disabled=!e}async loadModuleFrameAgnostic(o){window.location!==window.parent.location?await d(o):await import(o)}renderProgressBar(o,e,l){this.loadModuleFrameAgnostic("@typo3/backend/element/progress-bar-element.js"),o=o||this.currentModal;const t=o.ownerDocument.createElement("typo3-backend-progress-bar");return typeof e=="object"&&Object.keys(e).forEach(r=>{t[r]=e[r]}),l==="append"?o.append(t):l==="prepend"?o.prepend(t):o.replaceChildren(t),t}}export{s as AbstractInteractableModule};
