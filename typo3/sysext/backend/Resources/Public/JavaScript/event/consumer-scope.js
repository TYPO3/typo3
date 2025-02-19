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
class t{constructor(){this.consumers=[]}getConsumers(){return this.consumers}hasConsumer(s){return this.consumers.includes(s)}attach(s){this.hasConsumer(s)||this.consumers.push(s)}detach(s){this.consumers=this.consumers.filter(e=>e!==s)}async invoke(s){const e=[];this.consumers.forEach(o=>{const r=o.consume.call(o,s);r&&e.push(r)}),await Promise.all(e)}}var c=new t;export{c as default};
