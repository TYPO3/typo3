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
class ConsumerScope{constructor(){this.consumers=[]}getConsumers(){return this.consumers}hasConsumer(s){return this.consumers.includes(s)}attach(s){this.hasConsumer(s)||this.consumers.push(s)}detach(s){this.consumers=this.consumers.filter((e=>e!==s))}async invoke(s){const e=[];this.consumers.forEach((o=>{const n=o.consume.call(o,s);n&&e.push(n)})),await Promise.all(e)}}export default new ConsumerScope;