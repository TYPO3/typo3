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
var __importDefault=this&&this.__importDefault||function(t){return t&&t.__esModule?t:{default:t}};define(["require","exports","jquery"],(function(t,e,o){"use strict";Object.defineProperty(e,"__esModule",{value:!0}),e.AbstractInteractableModule=void 0,o=__importDefault(o);e.AbstractInteractableModule=class{constructor(){this.selectorModalBody=".t3js-modal-body",this.selectorModalContent=".t3js-module-content",this.selectorModalFooter=".t3js-modal-footer"}getModalBody(){return this.findInModal(this.selectorModalBody)}getModuleContent(){return this.findInModal(this.selectorModalContent)}getModalFooter(){return this.findInModal(this.selectorModalFooter)}findInModal(t){return this.currentModal.find(t)}setModalButtonsState(t){this.getModalFooter().find("button").each((e,d)=>{this.setModalButtonState((0,o.default)(d),t)})}setModalButtonState(t,e){t.toggleClass("disabled",!e).prop("disabled",!e)}}}));