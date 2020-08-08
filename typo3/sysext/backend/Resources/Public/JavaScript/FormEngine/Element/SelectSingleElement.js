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
var __importDefault=this&&this.__importDefault||function(e){return e&&e.__esModule?e:{default:e}};define(["require","exports","jquery"],(function(e,t,s){"use strict";s=__importDefault(s);return new class{constructor(){this.initialize=(e,t)=>{let n=s.default(e),i=n.prev(".input-group-icon");t=t||{},n.on("change",e=>{let t=s.default(e.target);i.html(n.find(":selected").data("icon"));let o=t.closest(".t3js-formengine-field-item").find(".t3js-forms-select-single-icons");o.find(".item.active").removeClass("active"),o.find('[data-select-index="'+t.prop("selectedIndex")+'"]').closest(".item").addClass("active")}),"function"==typeof t.onChange&&n.on("change",t.onChange),"function"==typeof t.onFocus&&n.on("focus",t.onFocus),n.closest(".form-control-wrap").find(".t3js-forms-select-single-icons a").on("click",e=>{let t=s.default(e.target),i=t.closest("[data-select-index]");return t.closest(".t3js-forms-select-single-icons").find(".item.active").removeClass("active"),n.prop("selectedIndex",i.data("selectIndex")).trigger("change"),i.closest(".item").addClass("active"),!1})}}}}));