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
define(["require","exports","jquery","./Severity"],(function(e,s,t,r){"use strict";return new class{constructor(){this.template=t('<div class="t3js-message typo3-message alert"><h4></h4><p class="messageText"></p></div>')}render(e,s,t){let a=this.template.clone();return a.addClass("alert-"+r.getCssClass(e)),s&&a.find("h4").text(s),t?a.find(".messageText").text(t):a.find(".messageText").remove(),a}}}));