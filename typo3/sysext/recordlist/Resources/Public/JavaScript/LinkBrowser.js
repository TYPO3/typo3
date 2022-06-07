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
var __importDefault=this&&this.__importDefault||function(t){return t&&t.__esModule?t:{default:t}};define(["require","exports","jquery"],(function(t,e,r){"use strict";r=__importDefault(r);return new class{constructor(){this.urlParameters={},this.parameters={},this.additionalLinkAttributes={},this.loadTarget=t=>{const e=(0,r.default)(t.currentTarget);(0,r.default)(".t3js-linkTarget").val(e.val()),e.get(0).selectedIndex=0},(0,r.default)(()=>{const t=(0,r.default)("body").data();this.urlParameters=t.urlParameters,this.parameters=t.parameters,this.linkAttributeFields=t.linkAttributeFields,(0,r.default)(".t3js-targetPreselect").on("change",this.loadTarget),(0,r.default)("form.t3js-dummyform").on("submit",t=>{t.preventDefault()})})}getLinkAttributeValues(){const t={};return r.default.each(this.linkAttributeFields,(e,i)=>{const a=(0,r.default)('[name="l'+i+'"]').val();a&&(t[i]=a)}),r.default.extend(t,this.additionalLinkAttributes),t}encodeGetParameters(t,e,r){const i=[];for(const a of Object.entries(t)){const[t,s]=a,n=e?e+"["+t+"]":t;r.includes(n+"=")||i.push("object"==typeof s?this.encodeGetParameters(s,n,r):encodeURIComponent(n)+"="+encodeURIComponent(s))}return"&"+i.join("&")}setAdditionalLinkAttribute(t,e){this.additionalLinkAttributes[t]=e}finalizeFunction(t){throw"The link browser requires the finalizeFunction to be set. Seems like you discovered a major bug."}}}));