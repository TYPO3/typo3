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
import i from"@typo3/backend/sortable-table.js";class o extends HTMLElement{constructor(){super();const e=document.createElement("slot");e.addEventListener("slotchange",()=>this.initializeWordList(e)),this.attachShadow({mode:"open"}).append(e)}connectedCallback(){const e=this.shadowRoot?.querySelector("slot");e&&this.initializeWordList(e)}initializeWordList(e){const t=e.assignedElements()[0]?.children[0]??null;if(t===null)throw new Error("Sortable table could not be initialized. Expected <table> child, but found none");if(t.localName!=="table")throw new Error(`Sortable table could not be initialized. Expected <table> child name, but found: <${t.localName}>`);new i(t)}}window.customElements.define("typo3-indexed-search-statistics",o);
