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
define(["require","exports","jquery","datatables"],function(s,e,a){"use strict";return class{constructor(){this.expandedUploadFormClass="transformed"}initializeEvents(){a(document).on("click",".t3js-upload",s=>{const e=a(s.currentTarget),t=a(".uploadForm");s.preventDefault(),e.hasClass(this.expandedUploadFormClass)?(t.stop().slideUp(),e.removeClass(this.expandedUploadFormClass)):(e.addClass(this.expandedUploadFormClass),t.stop().slideDown(),a.ajax({url:e.attr("href"),dataType:"html",success:s=>{t.html(s)}}))})}}});