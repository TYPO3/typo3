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
var __importDefault=this&&this.__importDefault||function(t){return t&&t.__esModule?t:{default:t}};define(["require","exports","jquery","jquery/minicolors"],(function(t,e,r){"use strict";r=__importDefault(r);return new class{constructor(){this.selector=".t3js-color-picker"}initialize(){(0,r.default)(this.selector).minicolors({format:"hex",position:"bottom left",theme:"bootstrap"}),(0,r.default)(document).on("change",".t3js-colorpicker-value-trigger",t=>{const e=(0,r.default)(t.target);""!==e.val()&&(e.closest(".t3js-formengine-field-item").find(".t3js-color-picker").val(e.val()).trigger("paste"),e.val(""))}),(0,r.default)(document).on("blur",".t3js-color-picker",t=>{const e=(0,r.default)(t.target);e.closest(".t3js-formengine-field-item").find('input[type="hidden"]').val(e.val()),""===e.val()&&e.trigger("paste")})}}}));