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
import n from"@typo3/core/event/regular-event.js";class o extends n{constructor(t,u,e=250){super(t,u),this.callback=this.debounce(this.callback,e)}debounce(t,u){let e;return function(...c){clearTimeout(e),e=setTimeout(()=>{e=void 0,t.apply(this,c)},u)}}}export{o as default};
