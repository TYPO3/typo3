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
define(["require","exports","jquery","TYPO3/CMS/Core/Contrib/jquery.minicolors"],function(t,e,o){"use strict";return new(function(){function t(){this.selector=".t3js-color-picker"}return t.prototype.initialize=function(){o(this.selector).minicolors({format:"hex",position:"bottom left",theme:"bootstrap"}),o(document).on("change",".t3js-colorpicker-value-trigger",function(t){var e=o(t.target);""!==e.val()&&(e.closest(".t3js-formengine-field-item").find(".t3js-color-picker").val(e.val()).trigger("paste"),e.val(""))})},t}())});