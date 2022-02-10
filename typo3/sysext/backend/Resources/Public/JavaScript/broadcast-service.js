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
import"broadcastchannel.js";import{BroadcastMessage}from"@typo3/backend/broadcast-message.js";import{MessageUtility}from"@typo3/backend/utility/message-utility.js";class BroadcastService{constructor(){this.channel=new BroadcastChannel("typo3")}get isListening(){return"function"==typeof this.channel.onmessage}static onMessage(e){if(!MessageUtility.verifyOrigin(e.origin))throw"Denied message sent by "+e.origin;const t=BroadcastMessage.fromData(e.data);document.dispatchEvent(t.createCustomEvent("typo3"))}listen(){this.isListening||(this.channel.onmessage=BroadcastService.onMessage)}post(e){this.channel.postMessage(e)}}export default new BroadcastService;