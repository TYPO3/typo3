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
define(["require","exports","jquery","TYPO3/CMS/Backend/InfoWindow"],function(e,t,n,o){"use strict";class r{static openFileInfoPopup(e){o.showItem("_FILE",e)}constructor(){n(()=>{n("a.btn.filelist-file-info").click(e=>{e.preventDefault(),r.openFileInfoPopup(n(e.currentTarget).attr("data-identifier"))}),n("a.filelist-file-references").click(e=>{e.preventDefault(),r.openFileInfoPopup(n(e.currentTarget).attr("data-identifier"))}),n("a.btn.filelist-file-copy").click(e=>{e.preventDefault();const t=n(e.currentTarget).attr("href");let o=t?encodeURIComponent(t):encodeURIComponent(top.list_frame.document.location.pathname+top.list_frame.document.location.search);top.list_frame.location.href=t+"&redirect="+o})})}}return new r});