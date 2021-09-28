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
var __importDefault=this&&this.__importDefault||function(t){return t&&t.__esModule?t:{default:t}};define(["require","exports","TYPO3/CMS/Backend/Notification","TYPO3/CMS/Core/Event/RegularEvent"],(function(t,e,n,o){"use strict";var i;o=__importDefault(o),function(t){t.settingsContainerSelector=".t3js-linkvalidator-settings",t.actionButtonSelector=".t3js-linkvalidator-action-button"}(i||(i={}));class c{static toggleActionButtons(t){var e;null===(e=t.querySelector(i.actionButtonSelector))||void 0===e||e.toggleAttribute("disabled",!t.querySelectorAll('input[type="checkbox"]:checked').length)}constructor(){this.initializeEvents(),document.querySelectorAll(i.settingsContainerSelector).forEach(t=>{c.toggleActionButtons(t)})}initializeEvents(){new o.default("change",(t,e)=>{c.toggleActionButtons(e.closest(i.settingsContainerSelector))}).delegateTo(document,[i.settingsContainerSelector,'input[type="checkbox"]'].join(" ")),new o.default("click",(t,e)=>{n.success(e.dataset.notificationMessage||"Event triggered","",2)}).delegateTo(document,i.actionButtonSelector)}}return new c}));