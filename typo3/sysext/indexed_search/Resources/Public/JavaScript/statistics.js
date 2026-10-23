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
import i from"@typo3/backend/sortable-table.js";class o extends HTMLElement{constructor(){super();const t=document.createElement("slot");t.addEventListener("slotchange",()=>this.initializeWordList(t)),this.attachShadow({mode:"open"}).append(t)}connectedCallback(){const t=this.shadowRoot?.querySelector("slot");t&&this.initializeWordList(t)}initializeWordList(t){const e=t.assignedElements()[0]?.children[0]??null;if(e===null||e.tagName.toLowerCase()!=="table")throw new Error(`Sortable table could not be initialized. Expected <table> child name, but found: ${e}`);new i(e)}}window.customElements.define("typo3-indexed-search-statistics",o);
