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
define(["require","exports","TYPO3/CMS/Backend/BroadcastMessage","TYPO3/CMS/Backend/Utility/MessageUtility","broadcastchannel"],(function(e,s,t,n){"use strict";class a{constructor(){this.channel=new BroadcastChannel("typo3")}get isListening(){return"function"==typeof this.channel.onmessage}static onMessage(e){if(!n.MessageUtility.verifyOrigin(e.origin))throw"Denied message sent by "+e.origin;const s=t.BroadcastMessage.fromData(e.data);document.dispatchEvent(s.createCustomEvent("typo3"))}listen(){this.isListening||(this.channel.onmessage=a.onMessage)}post(e){this.channel.postMessage(e)}}return new a}));