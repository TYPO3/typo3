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
define(["require","exports","jquery","./LinkBrowser"],function(n,i,t,e){"use strict";return new(function(){return function(){var n=this;this.currentLink="",this.identifier="",this.linkRecord=function(i){i.preventDefault();var r=t(i.currentTarget).parents("span").data();e.finalizeFunction(n.identifier+r.uid)},this.linkCurrent=function(i){i.preventDefault(),e.finalizeFunction(n.currentLink)},t(function(){var i=t("body");n.currentLink=i.data("currentLink"),n.identifier=i.data("identifier");var e=document.getElementById("db_list-searchbox-toolbar");e.style.display="block",e.style.position="relative",t("[data-close]").on("click",n.linkRecord),t("input.t3js-linkCurrent").on("click",n.linkCurrent)})}}())});