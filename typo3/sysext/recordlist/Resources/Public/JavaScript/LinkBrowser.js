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
import $ from"jquery";class LinkBrowser{constructor(){this.urlParameters={},this.parameters={},this.additionalLinkAttributes={},this.loadTarget=t=>{const e=$(t.currentTarget);$(".t3js-linkTarget").val(e.val()),e.get(0).selectedIndex=0},$(()=>{const t=$("body").data();this.urlParameters=t.urlParameters,this.parameters=t.parameters,this.linkAttributeFields=t.linkAttributeFields,$(".t3js-targetPreselect").on("change",this.loadTarget),$("form.t3js-dummyform").on("submit",t=>{t.preventDefault()})})}getLinkAttributeValues(){const t={};return $.each(this.linkAttributeFields,(e,r)=>{const i=$('[name="l'+r+'"]').val();i&&(t[r]=i)}),$.extend(t,this.additionalLinkAttributes),t}encodeGetParameters(t,e,r){const i=[];for(const s of Object.entries(t)){const[t,n]=s,a=e?e+"["+t+"]":t;r.includes(a+"=")||i.push("object"==typeof n?this.encodeGetParameters(n,a,r):encodeURIComponent(a)+"="+encodeURIComponent(n))}return"&"+i.join("&")}setAdditionalLinkAttribute(t,e){this.additionalLinkAttributes[t]=e}finalizeFunction(t){throw"The link browser requires the finalizeFunction to be set. Seems like you discovered a major bug."}}export default new LinkBrowser;