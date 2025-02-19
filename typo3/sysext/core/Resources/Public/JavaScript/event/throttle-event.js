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
import o from"@typo3/core/event/regular-event.js";class i extends o{constructor(e,r,t){super(e,r),this.callback=this.throttle(r,t)}throttle(e,r){let t=!1;return function(...s){t||(e.apply(this,s),t=!0,setTimeout(()=>{t=!1,e.apply(this,s)},r))}}}export{i as default};
