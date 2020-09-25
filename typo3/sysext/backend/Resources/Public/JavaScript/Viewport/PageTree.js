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
define(["require","exports"],(function(t,n){"use strict";return function(){function t(t){this.instance=null,this.instance=t}return t.prototype.refreshTree=function(){null!==this.instance&&this.instance.refreshOrFilterTree()},t.prototype.setTemporaryMountPoint=function(t){null!==this.instance&&this.instance.setTemporaryMountPoint(t)},t.prototype.unsetTemporaryMountPoint=function(){null!==this.instance&&this.instance.unsetTemporaryMountPoint()},t.prototype.selectNode=function(t){null!==this.instance&&this.instance.selectNode(t)},t}()}));