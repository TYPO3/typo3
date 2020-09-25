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
define(["require","exports","jquery"],(function(n,t,e){"use strict";return new(function(){function n(){this.consumers=[]}return n.prototype.getConsumers=function(){return this.consumers},n.prototype.hasConsumer=function(n){return-1!==this.consumers.indexOf(n)},n.prototype.attach=function(n){this.hasConsumer(n)||this.consumers.push(n)},n.prototype.detach=function(n){this.consumers=this.consumers.filter((function(t){return t!==n}))},n.prototype.invoke=function(n){var t=[];return this.consumers.forEach((function(e){var r=e.consume.call(e,n);r&&t.push(r)})),e.when.apply(e,t)},n}())}));