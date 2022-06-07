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
var __importDefault=this&&this.__importDefault||function(t){return t&&t.__esModule?t:{default:t}};define(["require","exports","jquery","./Severity"],(function(t,e,l,s){"use strict";l=__importDefault(l);return new class{constructor(){this.template=(0,l.default)('<div class="t3js-infobox callout callout-sm"><h4 class="callout-title"></h4><div class="callout-body"></div></div>')}render(t,e,l){let o=this.template.clone();return o.addClass("callout-"+s.getCssClass(t)),e&&o.find("h4").text(e),l?o.find(".callout-body").text(l):o.find(".callout-body").remove(),o}}}));