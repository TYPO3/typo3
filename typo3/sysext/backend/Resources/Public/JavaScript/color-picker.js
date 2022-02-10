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
import $ from"jquery";import"jquery/minicolors.js";class ColorPicker{constructor(){this.selector=".t3js-color-picker"}initialize(){$(this.selector).minicolors({format:"hex",position:"bottom left",theme:"bootstrap"}),$(document).on("change",".t3js-colorpicker-value-trigger",t=>{const e=$(t.target);""!==e.val()&&(e.closest(".t3js-formengine-field-item").find(".t3js-color-picker").val(e.val()).trigger("paste"),e.val(""))}),$(document).on("blur",".t3js-color-picker",t=>{const e=$(t.target);e.closest(".t3js-formengine-field-item").find('input[type="hidden"]').val(e.val()),""===e.val()&&e.trigger("paste")})}}export default new ColorPicker;