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
import Notification from"@typo3/backend/notification.js";import RegularEvent from"@typo3/core/event/regular-event.js";var Selectors;!function(t){t.settingsContainerSelector=".t3js-linkvalidator-settings",t.actionButtonSelector=".t3js-linkvalidator-action-button"}(Selectors||(Selectors={}));class Linkvalidator{static toggleActionButtons(t){t.querySelector(Selectors.actionButtonSelector)?.toggleAttribute("disabled",!t.querySelectorAll('input[type="checkbox"]:checked').length)}constructor(){this.initializeEvents(),document.querySelectorAll(Selectors.settingsContainerSelector).forEach(t=>{Linkvalidator.toggleActionButtons(t)})}initializeEvents(){new RegularEvent("change",(t,e)=>{Linkvalidator.toggleActionButtons(e.closest(Selectors.settingsContainerSelector))}).delegateTo(document,[Selectors.settingsContainerSelector,'input[type="checkbox"]'].join(" ")),new RegularEvent("click",(t,e)=>{Notification.success(e.dataset.notificationMessage||"Event triggered","",2)}).delegateTo(document,Selectors.actionButtonSelector)}}export default new Linkvalidator;