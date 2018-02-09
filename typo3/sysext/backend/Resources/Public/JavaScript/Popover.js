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
define(["require","exports","jquery","bootstrap"],function(a,b,c){"use strict";var d=function(){function a(){this.DEFAULT_SELECTOR='[data-toggle="popover"]'}return a.prototype.initialize=function(a){a=a||this.DEFAULT_SELECTOR,c(a).popover()},a.prototype.popover=function(a){a.popover()},a.prototype.setOptions=function(a,b){b=b||{};var c=b.title||a.data("title")||"",d=b.content||a.data("content")||"";a.attr("data-original-title",c).attr("data-content",d).attr("data-placement","auto").popover(b)},a.prototype.setOption=function(a,b,c){a.data("bs.popover").options[b]=c},a.prototype.show=function(a){a.popover("show")},a.prototype.hide=function(a){a.popover("hide")},a.prototype.destroy=function(a){a.popover("destroy")},a.prototype.toggle=function(a){a.popover("toggle")},a}(),e=new d;return e.initialize(),TYPO3.Popover=e,e});