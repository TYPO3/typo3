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
define(["require","exports","jquery","./LinkBrowser"],function(n,i,t,e){"use strict";return new function(){var n=this;this.currentLink="",this.linkPage=function(n){n.preventDefault(),e.finalizeFunction(t(n.currentTarget).attr("href"))},this.linkPageByTextfield=function(n){n.preventDefault();var i=t("#luid").val();i&&e.finalizeFunction(i)},this.linkCurrent=function(i){i.preventDefault(),e.finalizeFunction(n.currentLink)},t(function(){n.currentLink=t("body").data("currentLink"),t("a.t3js-pageLink").on("click",n.linkPage),t("input.t3js-linkCurrent").on("click",n.linkCurrent),t("input.t3js-pageLink").on("click",n.linkPageByTextfield)})}});