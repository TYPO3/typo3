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
var __importDefault=this&&this.__importDefault||function(e){return e&&e.__esModule?e:{default:e}};define(["require","exports","nprogress","TYPO3/CMS/Core/Event/RegularEvent"],(function(e,t,r,n){"use strict";var o;n=__importDefault(n),function(e){e.actionsContainerSelector=".t3js-reference-index-actions"}(o||(o={}));return new class{constructor(){this.registerActionButtonEvents()}registerActionButtonEvents(){new n.default("click",(e,t)=>{r.configure({showSpinner:!1}),r.start(),Array.from(t.parentNode.querySelectorAll("button")).forEach(e=>{e.classList.add("disabled")})}).delegateTo(document.querySelector(o.actionsContainerSelector),"button")}}}));