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
var __importDefault=this&&this.__importDefault||function(e){return e&&e.__esModule?e:{default:e}};define(["require","exports","jquery","./Severity"],(function(e,t,s,r){"use strict";s=__importDefault(s);return new class{constructor(){this.template=(0,s.default)('<div class="t3js-message typo3-message alert"><h4></h4><p class="messageText"></p></div>')}render(e,t,s){let a=this.template.clone();return a.addClass("alert-"+r.getCssClass(e)),t&&a.find("h4").text(t),s?a.find(".messageText").text(s):a.find(".messageText").remove(),a}}}));