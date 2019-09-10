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
define(["require","exports","jquery"],function(t,e,r){"use strict";return new class{constructor(){this.thisScriptUrl="",this.urlParameters={},this.parameters={},this.addOnParams="",this.additionalLinkAttributes={},this.loadTarget=(t=>{const e=r(t.currentTarget);r(".t3js-linkTarget").val(e.val()),e.get(0).selectedIndex=0}),r(()=>{const t=r("body").data();this.thisScriptUrl=t.thisScriptUrl,this.urlParameters=t.urlParameters,this.parameters=t.parameters,this.addOnParams=t.addOnParams,this.linkAttributeFields=t.linkAttributeFields,r(".t3js-targetPreselect").on("change",this.loadTarget),r("form.t3js-dummyform").on("submit",t=>{t.preventDefault()})})}getLinkAttributeValues(){const t={};return r.each(this.linkAttributeFields,(e,i)=>{const s=r('[name="l'+i+'"]').val();s&&(t[i]=s)}),r.extend(t,this.additionalLinkAttributes),t}encodeGetParameters(t,e,r){const i=[];for(const s of Object.entries(t)){const[t,n]=s,a=e?e+"["+t+"]":t;r.includes(a+"=")||i.push("object"==typeof n?this.encodeGetParameters(n,a,r):encodeURIComponent(a)+"="+encodeURIComponent(n))}return"&"+i.join("&")}setAdditionalLinkAttribute(t,e){this.additionalLinkAttributes[t]=e}finalizeFunction(t){throw"The link browser requires the finalizeFunction to be set. Seems like you discovered a major bug."}}});