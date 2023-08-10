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
import"@typo3/install/renderable/progress-bar.js";import{topLevelModuleImport}from"@typo3/backend/utility/top-level-module-import.js";export class AbstractInteractableModule{constructor(){this.selectorModalBody=".t3js-modal-body",this.selectorModalContent=".t3js-module-content",this.selectorModalFooter=".t3js-modal-footer"}initialize(t){this.currentModal=t}getModalBody(){return this.findInModal(this.selectorModalBody)}getModuleContent(){return this.findInModal(this.selectorModalContent)}getModalFooter(){return this.findInModal(this.selectorModalFooter)}findInModal(t){return this.currentModal.querySelector(t)}setModalButtonsState(t){this.getModalFooter()?.querySelectorAll("button").forEach((e=>{this.setModalButtonState(e,t)}))}setModalButtonState(t,e){t.classList.toggle("disabled",!e),t.disabled=!e}renderProgressBar(t,e,o){window.location!==window.parent.location&&topLevelModuleImport("@typo3/install/renderable/progress-bar.js");const r=(t=t||this.currentModal).ownerDocument.createElement("typo3-install-progress-bar");return"object"==typeof e&&Object.keys(e).forEach((t=>{r[t]=e[t]})),"append"===o?t.append(r):"prepend"===o?t.prepend(r):t.replaceChildren(r),r}}