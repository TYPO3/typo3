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
define(["require","exports","jquery","datatables","TYPO3/CMS/Backend/jquery.clearable"],function(e,a,t){"use strict";return function(){function e(){this.expandedUploadFormClass="transformed"}return e.prototype.initializeEvents=function(){var e=this;t(document).on("click",".t3js-upload",function(a){var s=t(a.currentTarget),r=t(".uploadForm");a.preventDefault(),s.hasClass(e.expandedUploadFormClass)?(r.stop().slideUp(),s.removeClass(e.expandedUploadFormClass)):(s.addClass(e.expandedUploadFormClass),r.stop().slideDown(),t.ajax({url:s.attr("href"),dataType:"html",success:function(e){r.html(e)}}))})},e}()});