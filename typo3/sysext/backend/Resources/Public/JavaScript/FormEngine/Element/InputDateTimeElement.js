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
define(["require","exports","jquery","TYPO3/CMS/Backend/FormEngine"],function(e,n,t,r){"use strict";return new(function(){function n(){var n=this;t(function(){n.registerEventHandler(),document.querySelectorAll(".t3js-datetimepicker").length&&e(["../../DateTimePicker"])})}return n.prototype.registerEventHandler=function(){t(document).on("formengine.dp.change",function(e,n){r.Validation.validate(),r.Validation.markFieldAsChanged(n),t(".module-docheader-bar .btn").removeClass("disabled").prop("disabled",!1)})},n}())});