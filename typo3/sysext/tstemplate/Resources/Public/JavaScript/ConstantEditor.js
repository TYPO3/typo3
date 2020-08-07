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
var __importDefault=this&&this.__importDefault||function(e){return e&&e.__esModule?e:{default:e}};define(["require","exports","jquery"],(function(e,t,o){"use strict";var r;o=__importDefault(o),function(e){e.editIconSelector=".t3js-toggle",e.colorSelectSelector=".t3js-color-select",e.colorInputSelector=".t3js-color-input"}(r||(r={}));return new class{constructor(){this.changeProperty=e=>{const t=o.default(e.currentTarget),r=t.attr("rel"),l=o.default("#defaultTS-"+r),c=o.default("#userTS-"+r),u=o.default("#check-"+r),a=t.data("toggle");"edit"===a?(l.hide(),c.show(),c.find("input").css({background:"#fdf8bd"}),u.prop("disabled",!1).prop("checked",!0)):"undo"===a&&(c.hide(),l.show(),u.val("").prop("disabled",!0))},this.updateColorFromSelect=e=>{const t=o.default(e.currentTarget);let r=t.attr("rel"),l=t.val();o.default("#input-"+r).val(l),o.default("#colorbox-"+r).css({background:l})},this.updateColorFromInput=e=>{const t=o.default(e.currentTarget);let r=t.attr("rel"),l=t.val();o.default("#colorbox-"+r).css({background:l}),o.default("#select-"+r).children().each((e,t)=>{t.selected=t.value===l})},o.default(document).on("click",r.editIconSelector,this.changeProperty).on("change",r.colorSelectSelector,this.updateColorFromSelect).on("blur",r.colorInputSelector,this.updateColorFromInput)}}}));