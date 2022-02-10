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
import $ from"jquery";var Selectors;!function(t){t.editIconSelector=".t3js-toggle",t.colorSelectSelector=".t3js-color-select",t.colorInputSelector=".t3js-color-input",t.formFieldsSelector=".tstemplate-constanteditor [data-form-update-fragment]"}(Selectors||(Selectors={}));class ConstantEditor{constructor(){this.updateFormFragment=t=>{const e=$(t.currentTarget).attr("data-form-update-fragment");let o=document.forms[0].action;-1!==o.indexOf("#")&&(o=o.substring(0,o.indexOf("#"))),document.forms[0].action=o+"#"+e},this.changeProperty=t=>{const e=$(t.currentTarget),o=e.attr("rel"),r=$("#defaultTS-"+o),c=$("#userTS-"+o),l=$("#check-"+o),n=e.data("bsToggle");"edit"===n?(r.hide(),c.show(),c.find("input").css({background:"#fdf8bd"}),l.prop("disabled",!1).prop("checked",!0)):"undo"===n&&(c.hide(),r.show(),l.val("").prop("disabled",!0))},this.updateColorFromSelect=t=>{const e=$(t.currentTarget);let o=e.attr("rel"),r=e.val();$("#input-"+o).val(r),$("#colorbox-"+o).css({background:r})},this.updateColorFromInput=t=>{const e=$(t.currentTarget);let o=e.attr("rel"),r=e.val();$("#colorbox-"+o).css({background:r}),$("#select-"+o).children().each((t,e)=>{e.selected=e.value===r})},$(document).on("click",Selectors.editIconSelector,this.changeProperty).on("change",Selectors.colorSelectSelector,this.updateColorFromSelect).on("blur",Selectors.colorInputSelector,this.updateColorFromInput).on("change",Selectors.formFieldsSelector,this.updateFormFragment)}}export default new ConstantEditor;