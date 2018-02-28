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
define(["require","exports","jquery","bootstrap"],function(t,o,e){"use strict";var p=new(function(){function t(){this.DEFAULT_SELECTOR='[data-toggle="popover"]'}return t.prototype.initialize=function(t){t=t||this.DEFAULT_SELECTOR,e(t).popover()},t.prototype.popover=function(t){t.popover()},t.prototype.setOptions=function(t,o){var e=(o=o||{}).title||t.data("title")||"",p=o.content||t.data("content")||"";t.attr("data-original-title",e).attr("data-content",p).attr("data-placement","auto").popover(o)},t.prototype.setOption=function(t,o,e){t.data("bs.popover").options[o]=e},t.prototype.show=function(t){t.popover("show")},t.prototype.hide=function(t){t.popover("hide")},t.prototype.destroy=function(t){t.popover("destroy")},t.prototype.toggle=function(t){t.popover("toggle")},t}());return p.initialize(),TYPO3.Popover=p,p});