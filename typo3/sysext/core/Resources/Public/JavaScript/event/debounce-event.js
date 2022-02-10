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
import RegularEvent from"@typo3/core/event/regular-event.js";class DebounceEvent extends RegularEvent{constructor(e,t,l=250,n=!1){super(e,t),this.callback=this.debounce(this.callback,l,n)}debounce(e,t,l){let n=null;return function(...u){const c=l&&!n;clearTimeout(n),c?(e.apply(this,u),n=setTimeout(()=>{n=null},t)):n=setTimeout(()=>{n=null,l||e.apply(this,u)},t)}}}export default DebounceEvent;