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
import RegularEvent from"TYPO3/CMS/Core/Event/RegularEvent.js";class ThrottleEvent extends RegularEvent{constructor(t,e,r){super(t,e),this.callback=this.throttle(e,r)}throttle(t,e){let r=!1;return function(...l){r||(t.apply(this,l),r=!0,setTimeout(()=>{r=!1,t.apply(this,l)},e))}}}export default ThrottleEvent;