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
import{BroadcastMessage}from"@typo3/backend/broadcast-message.js";import BroadcastService from"@typo3/backend/broadcast-service.js";import RegularEvent from"@typo3/core/event/regular-event.js";import Modal from"@typo3/backend/modal.js";var MODIFIER_KEYS;!function(e){e.META="Meta",e.CTRL="Control"}(MODIFIER_KEYS||(MODIFIER_KEYS={}));class LiveSearchShortcut{constructor(){const e=navigator.platform.toLowerCase().startsWith("mac")?MODIFIER_KEYS.META:MODIFIER_KEYS.CTRL;new RegularEvent("keydown",(t=>{if(t.repeat)return;if((e===MODIFIER_KEYS.META&&t.metaKey||e===MODIFIER_KEYS.CTRL&&t.ctrlKey)&&["k","K"].includes(t.key)){if(Modal.currentModal)return;t.preventDefault(),document.dispatchEvent(new CustomEvent("typo3:live-search:trigger-open")),BroadcastService.post(new BroadcastMessage("live-search","trigger-open",{}))}})).bindTo(document)}}export default new LiveSearchShortcut;