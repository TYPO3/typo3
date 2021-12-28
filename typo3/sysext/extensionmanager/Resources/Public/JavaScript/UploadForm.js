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
var __importDefault=this&&this.__importDefault||function(e){return e&&e.__esModule?e:{default:e}};define(["require","exports","jquery","TYPO3/CMS/Core/Ajax/AjaxRequest"],(function(e,t,s,a){"use strict";s=__importDefault(s);return class{constructor(){this.expandedUploadFormClass="transformed"}initializeEvents(){(0,s.default)(document).on("click",".t3js-upload",e=>{const t=(0,s.default)(e.currentTarget),o=(0,s.default)(".extension-upload-form");e.preventDefault(),t.hasClass(this.expandedUploadFormClass)?(o.stop().slideUp(),t.removeClass(this.expandedUploadFormClass)):(t.addClass(this.expandedUploadFormClass),o.stop().slideDown(),new a(t.attr("href")).get().then(async e=>{o.find(".t3js-upload-form-target").html(await e.resolve())}))})}}}));