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
define(["require","exports","jquery"],function(t,a,e){"use strict";var n;return function(t){t.CUSTOM="custom"}(n||(n={})),function(){function t(t){var a=this;e(function(){a.registerEventHandler(t)})}return t.prototype.registerEventHandler=function(t){e(document).on("change",'.t3js-l10n-state-container input[type="radio"][name="'+t+'"]',function(t){var a=e(t.currentTarget),r=a.closest(".t3js-formengine-field-item").find("[data-formengine-input-name]");if(0!==r.length){var i=r.data("last-l10n-state")||!1,l=a.val();i&&l===i||(l===n.CUSTOM?(i&&a.attr("data-original-language-value",r.val()),r.removeAttr("disabled")):(i===n.CUSTOM&&a.closest(".t3js-l10n-state-container").find(".t3js-l10n-state-custom").attr("data-original-language-value",r.val()),r.attr("disabled","disabled")),r.val(a.attr("data-original-language-value")).trigger("change"),r.data("last-l10n-state",a.val()))}})},t}()});