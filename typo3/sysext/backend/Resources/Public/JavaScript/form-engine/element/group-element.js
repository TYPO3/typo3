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
import{AbstractSortableSelectItems}from"@typo3/backend/form-engine/element/abstract-sortable-select-items.js";import DocumentService from"@typo3/core/document-service.js";import FormEngineSuggest from"@typo3/backend/form-engine-suggest.js";class GroupElement extends AbstractSortableSelectItems{constructor(e){super(),this.element=null,DocumentService.ready().then((()=>{this.element=document.getElementById(e),this.registerEventHandler(),this.registerSuggest()}))}registerEventHandler(){this.registerSortableEventHandler(this.element)}registerSuggest(){let e;null!==(e=this.element.closest(".t3js-formengine-field-item").querySelector(".t3-form-suggest"))&&new FormEngineSuggest(e)}}export default GroupElement;