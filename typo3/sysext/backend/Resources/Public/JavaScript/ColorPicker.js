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
define(["require","exports","jquery","TYPO3/CMS/Core/Contrib/jquery.minicolors"],function(a,b,c){"use strict";var d=function(){function a(){this.selector=".t3js-color-picker"}return a.prototype.initialize=function(){c(this.selector).minicolors({format:"hex",position:"bottom left",theme:"bootstrap"}),c(document).on("change",".t3js-colorpicker-value-trigger",function(a){var b=c(a.target);""!==b.val()&&(b.closest(".t3js-formengine-field-item").find(".t3js-color-picker").val(b.val()).trigger("paste"),b.val(""))})},a}();return new d});