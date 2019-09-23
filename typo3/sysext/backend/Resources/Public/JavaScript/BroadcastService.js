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
define(["require","exports","TYPO3/CMS/Backend/BroadcastMessage","TYPO3/CMS/Backend/Utility/MessageUtility","broadcastchannel"],function(e,t,s,n){"use strict";return new class{constructor(){this.channel=new BroadcastChannel("typo3")}listen(){this.channel.onmessage=(e=>{if(!n.MessageUtility.verifyOrigin(e.origin))throw"Denied message sent by "+e.origin;const t=s.BroadcastMessage.fromData(e.data);document.dispatchEvent(t.createCustomEvent("typo3"))})}post(e){this.channel.postMessage(e)}}});