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
class RegularEvent{constructor(e,t){this.eventName=e,this.callback=t}bindTo(e){this.boundElement=e,e.addEventListener(this.eventName,this.callback)}delegateTo(e,t){this.boundElement=e,e.addEventListener(this.eventName,e=>{for(let a=e.target;a&&a!==this.boundElement;a=a.parentNode)if(a.matches(t)){this.callback.call(a,e,a);break}},!1)}release(){this.boundElement.removeEventListener(this.eventName,this.callback)}}export default RegularEvent;