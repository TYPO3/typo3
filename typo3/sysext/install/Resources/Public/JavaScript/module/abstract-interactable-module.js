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
import{topLevelModuleImport}from"@typo3/backend/utility/top-level-module-import.js";var Identifiers;!function(e){e.modalBody=".t3js-modal-body",e.modalContent=".t3js-module-content",e.modalFooter=".t3js-modal-footer"}(Identifiers||(Identifiers={}));export class AbstractInteractableModule{initialize(e){this.currentModal=e}getModalBody(){return this.findInModal(Identifiers.modalBody)}getModuleContent(){return this.findInModal(Identifiers.modalContent)}getModalFooter(){return this.findInModal(Identifiers.modalFooter)}findInModal(e){return this.currentModal.querySelector(e)}setModalButtonsState(e){this.getModalFooter()?.querySelectorAll("button").forEach((t=>{this.setModalButtonState(t,e)}))}setModalButtonState(e,t){e.classList.toggle("disabled",!t),e.disabled=!t}async loadModuleFrameAgnostic(e){window.location!==window.parent.location?await topLevelModuleImport(e):await import(e)}renderProgressBar(e,t,o){this.loadModuleFrameAgnostic("@typo3/backend/element/progress-bar-element.js");const r=(e=e||this.currentModal).ownerDocument.createElement("typo3-backend-progress-bar");return"object"==typeof t&&Object.keys(t).forEach((e=>{r[e]=t[e]})),"append"===o?e.append(r):"prepend"===o?e.prepend(r):e.replaceChildren(r),r}}