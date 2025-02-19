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
import c from"@typo3/core/event/regular-event.js";class n extends c{constructor(t,l,e=250){super(t,l),this.callback=this.debounce(this.callback,e)}debounce(t,l){let e=null;return function(...u){clearTimeout(e),e=setTimeout(()=>{e=null,t.apply(this,u)},l)}}}export{n as default};
