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
var i;(function(e){e.META="meta",e.CTRL="control",e.SHIFT="shift",e.ALT="alt"})(i||(i={}));class r{constructor(t,n,s,o,a){this.ctrl=t,this.meta=n,this.alt=s,this.shift=o,this.key=a}static fromEvent(t){return new r(t.ctrlKey,t.metaKey,t.altKey,t.shiftKey,t.key.toLowerCase())}static fromHotkey(t){const n=t.filter(s=>!Object.values(i).includes(s));if(n.length>1)throw new Error('Cannot create HotkeyStruct with more than one non-modifier key, "'+n.join("+")+'" given.');return new r(t.includes(i.CTRL),t.includes(i.META),t.includes(i.ALT),t.includes(i.SHIFT),n[0].toLowerCase())}hasAnyModifier(){return this.ctrl||this.meta||this.alt||this.shift}toString(){return JSON.stringify(this)}}export{r as HotkeyStruct};
