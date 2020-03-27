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
define(["require","exports","jquery","TYPO3/CMS/Core/Ajax/AjaxRequest"],(function(e,s,t,a){"use strict";return class{constructor(){this.expandedUploadFormClass="transformed"}initializeEvents(){t(document).on("click",".t3js-upload",e=>{const s=t(e.currentTarget),o=t(".extension-upload-form");e.preventDefault(),s.hasClass(this.expandedUploadFormClass)?(o.stop().slideUp(),s.removeClass(this.expandedUploadFormClass)):(s.addClass(this.expandedUploadFormClass),o.stop().slideDown(),new a(s.attr("href")).get().then(async e=>{o.find(".t3js-upload-form-target").html(await e.resolve())}))})}}}));