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
define(["require","exports","jquery","./Severity"],function(t,e,l,o){"use strict";return new(function(){function t(){this.template=l('<div class="t3js-infobox callout callout-sm"><h4 class="callout-title"></h4><div class="callout-body"></div></div>')}return t.prototype.render=function(t,e,l){var i=this.template.clone();return i.addClass("callout-"+o.getCssClass(t)),e&&i.find("h4").text(e),l?i.find(".callout-body").text(l):i.find(".callout-body").remove(),i},t}())});