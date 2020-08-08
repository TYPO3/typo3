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
var __importDefault=this&&this.__importDefault||function(s){return s&&s.__esModule?s:{default:s}};define(["require","exports","jquery"],(function(s,e,t){"use strict";t=__importDefault(t);return new class{constructor(){this.consumers=[]}getConsumers(){return this.consumers}hasConsumer(s){return this.consumers.includes(s)}attach(s){this.hasConsumer(s)||this.consumers.push(s)}detach(s){this.consumers=this.consumers.filter(e=>e!==s)}invoke(s){const e=[];return this.consumers.forEach(t=>{const r=t.consume.call(t,s);r&&e.push(r)}),t.default.when.apply(t.default,e)}}}));