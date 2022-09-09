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
import{BroadcastMessage}from"@typo3/backend/broadcast-message.js";import BroadcastService from"@typo3/backend/broadcast-service.js";import RegularEvent from"@typo3/core/event/regular-event.js";class DoubleShiftTrigger{constructor(){this.shiftPressCounter=0,new RegularEvent("keydown",(e=>{e.repeat||("Shift"!==e.key?this.shiftPressCounter=0:(this.shiftPressCounter++,1===this.shiftPressCounter&&window.setTimeout((()=>{this.shiftPressCounter=0}),500),this.shiftPressCounter>=2&&(this.shiftPressCounter=0,document.dispatchEvent(new CustomEvent("live-search:trigger-open")),BroadcastService.post(new BroadcastMessage("live-search","trigger-open",{})))))})).bindTo(document)}}export default new DoubleShiftTrigger;