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
define(["require","exports","jquery","./LinkBrowser","TYPO3/CMS/Backend/LegacyTree"],function(n,e,t,i,r){"use strict";return new class{constructor(){this.currentLink="",this.linkFile=(n=>{n.preventDefault(),i.finalizeFunction(t(n.currentTarget).attr("href"))}),this.linkCurrent=(n=>{n.preventDefault(),i.finalizeFunction(this.currentLink)}),r.noop(),t(()=>{this.currentLink=t("body").data("currentLink"),t("a.t3js-fileLink").on("click",this.linkFile),t("input.t3js-linkCurrent").on("click",this.linkCurrent)})}}});