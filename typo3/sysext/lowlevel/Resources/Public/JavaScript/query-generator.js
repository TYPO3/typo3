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
import $ from"jquery";import"@typo3/backend/input/clearable.js";import DateTimePicker from"@typo3/backend/date-time-picker.js";class QueryGenerator{constructor(){this.form=null,this.limitField=null,this.initialize()}initialize(){this.form=$('form[name="queryform"]'),this.limitField=$("#queryLimit"),this.form.on("click",".t3js-submit-click",t=>{t.preventDefault(),this.doSubmit()}),this.form.on("change",".t3js-submit-change",t=>{t.preventDefault(),this.doSubmit()}),this.form.on("click",'.t3js-limit-submit input[type="button"]',t=>{t.preventDefault(),this.setLimit($(t.currentTarget).data("value")),this.doSubmit()}),this.form.on("click",".t3js-addfield",t=>{t.preventDefault();const e=$(t.currentTarget);this.addValueToField(e.data("field"),e.val())}),this.form.on("change","[data-assign-store-control-title]",t=>{const e=$(t.currentTarget),i=this.form.find('[name="storeControl[title]"]');"0"!==e.val()?i.val(e.find("option:selected").text()):i.val("")}),document.querySelectorAll('form[name="queryform"] .t3js-clearable').forEach(t=>t.clearable({onClear:()=>{this.doSubmit()}})),document.querySelectorAll('form[name="queryform"] .t3js-datetimepicker').forEach(t=>DateTimePicker.initialize(t))}doSubmit(){this.form.trigger("submit")}setLimit(t){this.limitField.val(t)}addValueToField(t,e){const i=this.form.find('[name="'+t+'"]'),r=i.val();i.val(r+","+e)}}export default new QueryGenerator;