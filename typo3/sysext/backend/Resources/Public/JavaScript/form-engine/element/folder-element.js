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
import t from"@typo3/core/document-service.js";import{AbstractSortableSelectItems as l}from"@typo3/backend/form-engine/element/abstract-sortable-select-items.js";import{selector as n}from"@typo3/core/literals.js";class o extends l{registerEventHandler(e){this.registerSortableEventHandler(e)}}class s extends HTMLElement{constructor(){super(...arguments),this.recordField=null}async connectedCallback(){if(this.recordField!==null)return;const e=this.getAttribute("recordFieldId");e!==null&&(await t.ready(),this.recordField=this.querySelector(n`#${e}`),this.recordField&&this.registerEventHandler())}registerEventHandler(){new o().registerEventHandler(this.recordField)}}window.customElements.define("typo3-formengine-element-folder",s);
