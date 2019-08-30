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
define(["require","exports","jquery","./LinkBrowser"],function(n,t,i,e){"use strict";return new class{constructor(){this.currentLink="",this.linkPage=(n=>{n.preventDefault(),e.finalizeFunction(i(n.currentTarget).attr("href"))}),this.linkPageByTextfield=(n=>{n.preventDefault();const t=i("#luid").val();t&&e.finalizeFunction(t)}),this.linkCurrent=(n=>{n.preventDefault(),e.finalizeFunction(this.currentLink)}),i(()=>{this.currentLink=i("body").data("currentLink"),i("a.t3js-pageLink").on("click",this.linkPage),i("input.t3js-linkCurrent").on("click",this.linkCurrent),i("input.t3js-pageLink").on("click",this.linkPageByTextfield)})}}});