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
define(["require","exports","jquery","./LinkBrowser","TYPO3/CMS/Backend/LegacyTree"],function(n,e,i,t,r){"use strict";return new function(){var n=this;this.currentLink="",this.linkFile=function(n){n.preventDefault(),t.finalizeFunction(i(n.currentTarget).attr("href"))},this.linkCurrent=function(e){e.preventDefault(),t.finalizeFunction(n.currentLink)},r.noop(),i(function(){n.currentLink=i("body").data("currentLink"),i("a.t3js-fileLink").on("click",n.linkFile),i("input.t3js-linkCurrent").on("click",n.linkCurrent)})}});