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
define(["require","exports"],function(t,e){"use strict";Object.defineProperty(e,"__esModule",{value:!0});var o=function(){function t(){this.selectorModalBody=".t3js-modal-body",this.selectorModalContent=".t3js-module-content"}return t.prototype.getModalBody=function(){return this.findInModal(this.selectorModalBody)},t.prototype.getModuleContent=function(){return this.findInModal(this.selectorModalContent)},t.prototype.findInModal=function(t){return this.currentModal.find(t)},t}();e.AbstractInteractableModule=o});