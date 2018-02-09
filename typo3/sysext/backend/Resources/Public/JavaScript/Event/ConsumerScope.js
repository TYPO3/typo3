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
define(["require","exports","jquery"],function(a,b,c){"use strict";var d=function(){function a(){this.consumers=[]}return a.prototype.getConsumers=function(){return this.consumers},a.prototype.hasConsumer=function(a){return this.consumers.indexOf(a)!==-1},a.prototype.attach=function(a){this.hasConsumer(a)||this.consumers.push(a)},a.prototype.detach=function(a){this.consumers=this.consumers.filter(function(b){return b!==a})},a.prototype.invoke=function(a){var b=[];return this.consumers.forEach(function(c){var d=c.consume.call(c,a);d&&b.push(d)}),c.when.apply(c,b)},a}();return new d});