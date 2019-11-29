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
define(["require","exports","jquery"],(function(e,t,o){"use strict";var r;!function(e){e.editIconSelector=".t3js-toggle",e.colorSelectSelector=".t3js-color-select",e.colorInputSelector=".t3js-color-input"}(r||(r={}));return new class{constructor(){this.changeProperty=e=>{const t=o(e.currentTarget),r=t.attr("rel"),c=o("#defaultTS-"+r),l=o("#userTS-"+r),n=o("#check-"+r),s=t.data("toggle");"edit"===s?(c.hide(),l.show(),l.find("input").css({background:"#fdf8bd"}),n.prop("disabled",!1).prop("checked",!0)):"undo"===s&&(l.hide(),c.show(),n.val("").prop("disabled",!0))},this.updateColorFromSelect=e=>{const t=o(e.currentTarget);let r=t.attr("rel"),c=t.val();o("#input-"+r).val(c),o("#colorbox-"+r).css({background:c})},this.updateColorFromInput=e=>{const t=o(e.currentTarget);let r=t.attr("rel"),c=t.val();o("#colorbox-"+r).css({background:c}),o("#select-"+r).children().each((e,t)=>{t.selected=t.value===c})},o(document).on("click",r.editIconSelector,this.changeProperty).on("change",r.colorSelectSelector,this.updateColorFromSelect).on("blur",r.colorInputSelector,this.updateColorFromInput)}}}));