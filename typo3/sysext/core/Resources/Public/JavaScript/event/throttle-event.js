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
import a from"@typo3/core/event/regular-event.js";class s extends a{constructor(n,e,t){super(n,e),this.callback=this.throttle(e,t)}throttle(n,e){let t=null,l=null;return function(...u){if(t=u,l!==null)return;const r=()=>{if(t===null){clearInterval(l??void 0),l=null;return}n.apply(this,t),t=null};r(),l=setInterval(r,e)}}}export{s as default};
