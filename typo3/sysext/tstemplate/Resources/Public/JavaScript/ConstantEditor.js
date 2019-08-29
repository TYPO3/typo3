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
define(["require","exports","jquery"],function(e,t,o){"use strict";var r;return function(e){e.editIconSelector=".t3js-toggle",e.colorSelectSelector=".t3js-color-select",e.colorInputSelector=".t3js-color-input"}(r||(r={})),new(function(){return function(){this.changeProperty=function(e){var t=o(e.currentTarget),r=t.attr("rel"),c=o("#defaultTS-"+r),n=o("#userTS-"+r),l=o("#check-"+r),u=t.data("toggle");"edit"===u?(c.hide(),n.show(),n.find("input").css({background:"#fdf8bd"}),l.prop("disabled",!1).prop("checked",!0)):"undo"===u&&(n.hide(),c.show(),l.val("").prop("disabled",!0))},this.updateColorFromSelect=function(e){var t=o(e.currentTarget),r=t.attr("rel"),c=t.val();o("#input-"+r).val(c),o("#colorbox-"+r).css({background:c})},this.updateColorFromInput=function(e){var t=o(e.currentTarget),r=t.attr("rel"),c=t.val();o("#colorbox-"+r).css({background:c}),o("#select-"+r).children().each(function(e,t){t.selected=t.value===c})},o(document).on("click",r.editIconSelector,this.changeProperty).on("change",r.colorSelectSelector,this.updateColorFromSelect).on("blur",r.colorInputSelector,this.updateColorFromInput)}}())});