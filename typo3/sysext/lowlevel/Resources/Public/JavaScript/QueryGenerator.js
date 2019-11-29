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
define(["require","exports","jquery","TYPO3/CMS/Backend/Input/Clearable"],(function(t,i,e){"use strict";return new class{constructor(){this.form=null,this.limitField=null,this.initialize()}initialize(){this.form=e('form[name="queryform"]'),this.limitField=e("#queryLimit"),this.form.on("click",".t3js-submit-click",t=>{t.preventDefault(),this.doSubmit()}),this.form.on("change",".t3js-submit-change",t=>{t.preventDefault(),this.doSubmit()}),this.form.on("click",'.t3js-limit-submit input[type="button"]',t=>{t.preventDefault(),this.setLimit(e(t.currentTarget).data("value")),this.doSubmit()}),this.form.on("click",".t3js-addfield",t=>{t.preventDefault();const i=e(t.currentTarget);this.addValueToField(i.data("field"),i.val())}),document.querySelectorAll('form[name="queryform"] .t3js-clearable').forEach(t=>t.clearable({onClear:()=>{this.doSubmit()}}))}doSubmit(){this.form.submit()}setLimit(t){this.limitField.val(t)}addValueToField(t,i){const e=this.form.find('[name="'+t+'"]'),l=e.val();e.val(l+","+i)}}}));