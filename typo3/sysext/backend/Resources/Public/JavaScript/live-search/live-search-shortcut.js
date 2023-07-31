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
import{BroadcastMessage}from"@typo3/backend/broadcast-message.js";import BroadcastService from"@typo3/backend/broadcast-service.js";import Modal from"@typo3/backend/modal.js";import Hotkeys from"@typo3/backend/hotkeys.js";import DocumentService from"@typo3/core/document-service.js";class LiveSearchShortcut{constructor(){DocumentService.ready().then((()=>{Hotkeys.register([Hotkeys.normalizedCtrlModifierKey,"k"],(e=>{Modal.currentModal||(e.preventDefault(),document.dispatchEvent(new CustomEvent("typo3:live-search:trigger-open")),BroadcastService.post(new BroadcastMessage("live-search","trigger-open",{})))}),{allowOnEditables:!0})}))}}export default new LiveSearchShortcut;