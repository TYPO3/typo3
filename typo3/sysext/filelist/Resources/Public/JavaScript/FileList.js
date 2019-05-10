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
define(["require","exports","jquery","TYPO3/CMS/Backend/InfoWindow"],function(e,t,n,i){"use strict";return new(function(){function e(){n(function(){n("a.btn.filelist-file-info").click(function(t){t.preventDefault(),e.openFileInfoPopup(n(t.currentTarget).attr("data-identifier"))}),n("a.filelist-file-references").click(function(t){t.preventDefault(),e.openFileInfoPopup(n(t.currentTarget).attr("data-identifier"))}),n("a.btn.filelist-file-copy").click(function(e){e.preventDefault();var t=n(e.currentTarget).attr("href"),i=t?top.rawurlencode(t):top.rawurlencode(top.list_frame.document.location.pathname+top.list_frame.document.location.search);top.list_frame.location.href=t+"&redirect="+i})})}return e.openFileInfoPopup=function(e){i.showItem("_FILE",e)},e}())});