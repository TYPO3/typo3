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
import RegularEvent from"@typo3/core/event/regular-event.js";class LinkBrowser{constructor(){this.parameters=JSON.parse(document.body.dataset.linkbrowserParameters||"{}"),this.linkAttributeFields=JSON.parse(document.body.dataset.linkbrowserAttributeFields||"{}"),new RegularEvent("click",(e=>{e.preventDefault(),this.finalizeFunction(document.body.dataset.linkbrowserCurrentLink)})).delegateTo(document,"button.t3js-linkCurrent"),new RegularEvent("change",this.loadTarget).delegateTo(document,".t3js-targetPreselect")}getLinkAttributeValues(){const e={};for(const t of this.linkAttributeFields.values()){const r=document.querySelector('[name="l'+t+'"]');null!==r&&(e[t]=r.value)}return e}loadTarget(){const e=document.querySelector(".t3js-linkTarget");null!==e&&(e.value=this.value,this.selectedIndex=0)}finalizeFunction(e){throw"The link browser requires the finalizeFunction to be set in order for "+e+" to be handled. Seems like you discovered a major bug."}}export default new LinkBrowser;