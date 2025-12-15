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
import i from"@typo3/core/document-service.js";import e from"@typo3/core/event/regular-event.js";import{ScaffoldState as n,ToolbarToggleRequestEvent as o,SearchToggleRequestEvent as a}from"@typo3/backend/viewport/scaffold-state.js";class t{static initialize(){n.initialize(),t.initializeEvents()}static initializeEvents(){new e("click",()=>{document.dispatchEvent(new o)}).bindTo(document.querySelector(".t3js-topbar-button-toolbar")),new e("click",()=>{document.dispatchEvent(new a)}).bindTo(document.querySelector(".t3js-topbar-button-search"))}}i.ready().then(t.initialize);
