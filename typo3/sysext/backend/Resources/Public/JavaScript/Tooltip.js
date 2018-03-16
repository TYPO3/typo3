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
define(["require","exports","jquery","bootstrap"],function(t,o,i){"use strict";var e=new(function(){function t(){var t=this;i(function(){t.initialize('[data-toggle="tooltip"]')})}return t.prototype.initialize=function(t,o){o=o||{},i(t).tooltip(o)},t.prototype.show=function(t,o){t.attr("data-placement","auto").attr("data-title",o).tooltip("show")},t.prototype.hide=function(t){t.tooltip("hide")},t}());return TYPO3.Tooltip=e,e});