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
define(["require","exports","jquery","./Severity"],function(e,t,s,r){"use strict";return new(function(){function e(){this.template=s('<div class="t3js-message typo3-message alert"><h4></h4><p class="messageText"></p></div>')}return e.prototype.render=function(e,t,s){var n=this.template.clone();return n.addClass("alert-"+r.getCssClass(e)),t&&n.find("h4").text(t),s?n.find(".messageText").text(s):n.find(".messageText").remove(),n},e}())});