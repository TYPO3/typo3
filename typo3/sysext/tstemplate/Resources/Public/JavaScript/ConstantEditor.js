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
var __importDefault=this&&this.__importDefault||function(t){return t&&t.__esModule?t:{default:t}};define(["require","exports","jquery"],(function(t,e,o){"use strict";var r;o=__importDefault(o),function(t){t.editIconSelector=".t3js-toggle",t.colorSelectSelector=".t3js-color-select",t.colorInputSelector=".t3js-color-input",t.formFieldsSelector=".tstemplate-constanteditor [data-form-update-fragment]"}(r||(r={}));return new class{constructor(){this.updateFormFragment=t=>{const e=(0,o.default)(t.currentTarget).attr("data-form-update-fragment");let r=document.forms[0].action;-1!==r.indexOf("#")&&(r=r.substring(0,r.indexOf("#"))),document.forms[0].action=r+"#"+e},this.changeProperty=t=>{const e=(0,o.default)(t.currentTarget),r=e.attr("rel"),l=(0,o.default)("#defaultTS-"+r),a=(0,o.default)("#userTS-"+r),c=(0,o.default)("#check-"+r),u=e.data("bsToggle");"edit"===u?(l.hide(),a.show(),a.find("input").css({background:"#fdf8bd"}),c.prop("disabled",!1).prop("checked",!0)):"undo"===u&&(a.hide(),l.show(),c.val("").prop("disabled",!0))},this.updateColorFromSelect=t=>{const e=(0,o.default)(t.currentTarget);let r=e.attr("rel"),l=e.val();(0,o.default)("#input-"+r).val(l),(0,o.default)("#colorbox-"+r).css({background:l})},this.updateColorFromInput=t=>{const e=(0,o.default)(t.currentTarget);let r=e.attr("rel"),l=e.val();(0,o.default)("#colorbox-"+r).css({background:l}),(0,o.default)("#select-"+r).children().each((t,e)=>{e.selected=e.value===l})},(0,o.default)(document).on("click",r.editIconSelector,this.changeProperty).on("change",r.colorSelectSelector,this.updateColorFromSelect).on("blur",r.colorInputSelector,this.updateColorFromInput).on("change",r.formFieldsSelector,this.updateFormFragment)}}}));