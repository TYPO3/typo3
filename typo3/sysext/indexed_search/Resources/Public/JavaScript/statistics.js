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
import SortableTable from"@typo3/backend/sortable-table.js";class IndexedSearchStatistics extends HTMLElement{constructor(){super();const e=document.createElement("slot");e.addEventListener("slotchange",(()=>this.initializeWordList(e))),this.attachShadow({mode:"open"}).append(e)}connectedCallback(){this.initializeWordList(this.shadowRoot.querySelector("slot"))}initializeWordList(e){const t=e.assignedElements()[0].children[0]??null;if(null===t||"table"!==t.tagName.toLowerCase())throw new Error(`Sortable table could not be initialized. Expected <table> child name, but found: ${t}`);new SortableTable(t)}}window.customElements.define("typo3-indexed-search-statistics",IndexedSearchStatistics);