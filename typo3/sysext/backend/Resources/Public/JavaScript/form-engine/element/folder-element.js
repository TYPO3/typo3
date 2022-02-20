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
import{AbstractSortableSelectItems}from"@typo3/backend/form-engine/element/abstract-sortable-select-items.js";class FolderSortableSelectItems extends AbstractSortableSelectItems{registerEventHandler(e){this.registerSortableEventHandler(e)}}class FolderElement extends HTMLElement{constructor(){super(...arguments),this.recordField=null}connectedCallback(){this.recordField=this.querySelector("#"+(this.getAttribute("recordFieldId")||"")),this.recordField&&this.registerEventHandler()}registerEventHandler(){(new FolderSortableSelectItems).registerEventHandler(this.recordField)}}window.customElements.define("typo3-formengine-element-folder",FolderElement);