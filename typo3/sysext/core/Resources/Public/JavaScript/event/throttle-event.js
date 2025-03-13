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
import a from"@typo3/core/event/regular-event.js";class s extends a{constructor(n,l,t){super(n,l),this.callback=this.throttle(l,t)}throttle(n,l){let t=null,e=null;return function(...u){if(t=u,e!==null)return;const r=()=>{if(t===null){clearInterval(e),e=null;return}n.apply(this,t),t=null};r(),e=setInterval(r,l)}}}export{s as default};
