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
var __importDefault=this&&this.__importDefault||function(t){return t&&t.__esModule?t:{default:t}};define(["require","exports","jquery","TYPO3/CMS/Backend/Input/Clearable"],(function(t,e,i){"use strict";i=__importDefault(i);return new class{constructor(){this.form=null,this.limitField=null,this.initialize()}initialize(){this.form=i.default('form[name="queryform"]'),this.limitField=i.default("#queryLimit"),this.form.on("click",".t3js-submit-click",t=>{t.preventDefault(),this.doSubmit()}),this.form.on("change",".t3js-submit-change",t=>{t.preventDefault(),this.doSubmit()}),this.form.on("click",'.t3js-limit-submit input[type="button"]',t=>{t.preventDefault(),this.setLimit(i.default(t.currentTarget).data("value")),this.doSubmit()}),this.form.on("click",".t3js-addfield",t=>{t.preventDefault();const e=i.default(t.currentTarget);this.addValueToField(e.data("field"),e.val())}),this.form.on("change","[data-assign-store-control-title]",t=>{const e=i.default(t.currentTarget),l=this.form.find('[name="storeControl[title]"]');"0"!==e.val()?l.val(e.find("option:selected").text()):l.val("")}),document.querySelectorAll('form[name="queryform"] .t3js-clearable').forEach(t=>t.clearable({onClear:()=>{this.doSubmit()}}))}doSubmit(){this.form.trigger("submit")}setLimit(t){this.limitField.val(t)}addValueToField(t,e){const i=this.form.find('[name="'+t+'"]'),l=i.val();i.val(l+","+e)}}}));