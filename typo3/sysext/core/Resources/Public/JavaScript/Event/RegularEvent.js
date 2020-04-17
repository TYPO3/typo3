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
define(["require","exports"],(function(e,t){"use strict";return class{constructor(e,t){this.eventName=e,this.callback=t}bindTo(e){this.boundElement=e,e.addEventListener(this.eventName,this.callback)}delegateTo(e,t){this.boundElement=e,e.addEventListener(this.eventName,e=>{for(let n=e.target;n&&n!==this.boundElement;n=n.parentNode)if(n.matches(t)){this.callback.call(n,e,n);break}},!1)}release(){this.boundElement.removeEventListener(this.eventName,this.callback)}}}));