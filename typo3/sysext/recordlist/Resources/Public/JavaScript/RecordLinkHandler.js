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
define(["require","exports","jquery","./LinkBrowser"],function(t,i,e,n){"use strict";return new class{constructor(){this.currentLink="",this.identifier="",this.linkRecord=(t=>{t.preventDefault();const i=e(t.currentTarget).parents("span").data();n.finalizeFunction(this.identifier+i.uid)}),this.linkCurrent=(t=>{t.preventDefault(),n.finalizeFunction(this.currentLink)}),e(()=>{const t=e("body");this.currentLink=t.data("currentLink"),this.identifier=t.data("identifier");const i=document.getElementById("db_list-searchbox-toolbar");i.style.display="block",i.style.position="relative",e("[data-close]").on("click",this.linkRecord),e("input.t3js-linkCurrent").on("click",this.linkCurrent)})}}});