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
define(["require","exports","jquery"],(function(s,e,r){"use strict";return new class{constructor(){this.consumers=[]}getConsumers(){return this.consumers}hasConsumer(s){return this.consumers.includes(s)}attach(s){this.hasConsumer(s)||this.consumers.push(s)}detach(s){this.consumers=this.consumers.filter(e=>e!==s)}invoke(s){const e=[];return this.consumers.forEach(r=>{const n=r.consume.call(r,s);n&&e.push(n)}),r.when.apply(r,e)}}}));