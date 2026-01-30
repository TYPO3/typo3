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
import{HotkeyRequestedEvent as o,HotkeyDispatchedEvent as r}from"@typo3/backend/hotkeys/events.js";class s{constructor(){this.registerEventHandler()}registerEventHandler(){document.addEventListener(o.eventName,t=>{const e=new r(t.keyboardEvent);for(const n of this.collectDocuments())if(n.dispatchEvent(e)===!1)break})}collectDocuments(){const t=[document];for(let e=0;e<window.frames.length;e++)try{t.push(window.frames[e].document)}catch{}return t}}var c=new s;export{c as default};
