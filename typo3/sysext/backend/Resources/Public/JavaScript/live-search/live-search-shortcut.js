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
import{BroadcastMessage}from"@typo3/backend/broadcast-message.js";import BroadcastService from"@typo3/backend/broadcast-service.js";import RegularEvent from"@typo3/core/event/regular-event.js";import Modal from"@typo3/backend/modal.js";var ModifierKeys;!function(e){e.META="Meta",e.CTRL="Control"}(ModifierKeys||(ModifierKeys={}));class LiveSearchShortcut{constructor(){const e=navigator.platform.toLowerCase().startsWith("mac")?ModifierKeys.META:ModifierKeys.CTRL;new RegularEvent("keydown",(r=>{if(r.repeat)return;if((e===ModifierKeys.META&&r.metaKey||e===ModifierKeys.CTRL&&r.ctrlKey)&&["k","K"].includes(r.key)){if(Modal.currentModal)return;r.preventDefault(),document.dispatchEvent(new CustomEvent("typo3:live-search:trigger-open")),BroadcastService.post(new BroadcastMessage("live-search","trigger-open",{}))}})).bindTo(document)}}export default new LiveSearchShortcut;