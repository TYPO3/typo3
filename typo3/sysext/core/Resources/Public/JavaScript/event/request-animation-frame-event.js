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
import i from"@typo3/core/event/regular-event.js";class n extends i{constructor(t,e){super(t,e),this.callback=this.req(this.callback)}req(t){let e=null;return(...a)=>{e&&window.cancelAnimationFrame(e),e=window.requestAnimationFrame(()=>{t.apply(this,a)})}}}export{n as default};
