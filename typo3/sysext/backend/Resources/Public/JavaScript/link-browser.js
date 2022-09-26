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
import DocumentService from"@typo3/core/document-service.js";import RegularEvent from"@typo3/core/event/regular-event.js";class LinkBrowser{constructor(){this.urlParameters={},this.parameters={},DocumentService.ready().then((()=>{this.urlParameters=JSON.parse(document.body.dataset.urlParameters||"{}"),this.parameters=JSON.parse(document.body.dataset.parameters||"{}"),this.linkAttributeFields=JSON.parse(document.body.dataset.linkAttributeFields||"{}"),new RegularEvent("change",this.loadTarget).delegateTo(document,".t3js-targetPreselect"),new RegularEvent("submit",(e=>e.preventDefault())).delegateTo(document,"form.t3js-dummyform")}))}getLinkAttributeValues(){const e={};for(const t of this.linkAttributeFields.values()){const r=document.querySelector('[name="l'+t+'"]');null!==r&&""!==r.value&&(e[t]=r.value)}return e}loadTarget(){const e=document.querySelector(".t3js-linkTarget");null!==e&&(e.value=this.value,this.selectedIndex=0)}finalizeFunction(e){throw"The link browser requires the finalizeFunction to be set. Seems like you discovered a major bug."}}export default new LinkBrowser;