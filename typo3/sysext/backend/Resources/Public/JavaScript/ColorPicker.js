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
define(["require","exports","jquery","TYPO3/CMS/Core/Contrib/jquery.minicolors"],function(e,t,o){"use strict";return new class{constructor(){this.selector=".t3js-color-picker"}initialize(){o(this.selector).minicolors({format:"hex",position:"bottom left",theme:"bootstrap"}),o(document).on("change",".t3js-colorpicker-value-trigger",e=>{const t=o(e.target);""!==t.val()&&(t.closest(".t3js-formengine-field-item").find(".t3js-color-picker").val(t.val()).trigger("paste"),t.val(""))}),o(document).on("blur",".t3js-color-picker",e=>{const t=o(e.target);t.closest(".t3js-formengine-field-item").find('input[type="hidden"]').val(t.val()),""===t.val()&&t.trigger("paste")})}}});