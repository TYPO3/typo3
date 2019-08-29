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
define(["require","exports","jquery"],function(e,t,n){"use strict";return new(function(){return function(){this.initialize=function(e,t){var s=n(e),i=s.prev(".input-group-icon");t=t||{},s.on("change",function(e){var t=n(e.target);i.html(s.find(":selected").data("icon"));var o=t.closest(".t3js-formengine-field-item").find(".t3js-forms-select-single-icons");o.find(".item.active").removeClass("active"),o.find('[data-select-index="'+t.prop("selectedIndex")+'"]').closest(".item").addClass("active")}),"function"==typeof t.onChange&&s.on("change",t.onChange),"function"==typeof t.onFocus&&s.on("focus",t.onFocus),s.closest(".form-control-wrap").find(".t3js-forms-select-single-icons a").on("click",function(e){var t=n(e.target),i=t.closest("[data-select-index]");return t.closest(".t3js-forms-select-single-icons").find(".item.active").removeClass("active"),s.prop("selectedIndex",i.data("selectIndex")).trigger("change"),i.closest(".item").addClass("active"),!1})}}}())});