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
import{BroadcastMessage as n}from"@typo3/backend/broadcast-message.js";import{MessageUtility as a}from"@typo3/backend/utility/message-utility.js";class s{constructor(){this.channel=new BroadcastChannel("typo3")}get isListening(){return typeof this.channel.onmessage=="function"}static onMessage(e){if(!a.verifyOrigin(e.origin))throw"Denied message sent by "+e.origin;const t=n.fromData(e.data);document.dispatchEvent(t.createCustomEvent("typo3"))}listen(){this.isListening||(this.channel.onmessage=s.onMessage)}post(e){this.channel.postMessage(e)}}var i=new s;export{i as default};
