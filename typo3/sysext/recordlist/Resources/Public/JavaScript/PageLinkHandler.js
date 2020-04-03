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
define(["require","exports","jquery","./LinkBrowser"],(function(t,n,i,e){"use strict";return new class{constructor(){this.currentLink="",this.linkPage=t=>{t.preventDefault(),e.finalizeFunction(i(t.currentTarget).attr("href"))},this.linkPageByTextfield=t=>{t.preventDefault();let n=i("#luid").val();if(!n)return;const r=parseInt(n,10);isNaN(r)||(n="t3://page?uid="+r),e.finalizeFunction(n)},this.linkCurrent=t=>{t.preventDefault(),e.finalizeFunction(this.currentLink)},i(()=>{this.currentLink=i("body").data("currentLink"),i("a.t3js-pageLink").on("click",this.linkPage),i("input.t3js-linkCurrent").on("click",this.linkCurrent),i("input.t3js-pageLink").on("click",this.linkPageByTextfield)})}}}));