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
define(["require","exports","jquery","datatables","TYPO3/CMS/Backend/jquery.clearable"],function(e,s,a){"use strict";return class{constructor(){this.expandedUploadFormClass="transformed"}initializeEvents(){a(document).on("click",".t3js-upload",e=>{const s=a(e.currentTarget),t=a(".uploadForm");e.preventDefault(),s.hasClass(this.expandedUploadFormClass)?(t.stop().slideUp(),s.removeClass(this.expandedUploadFormClass)):(s.addClass(this.expandedUploadFormClass),t.stop().slideDown(),a.ajax({url:s.attr("href"),dataType:"html",success:e=>{t.html(e)}}))})}}});