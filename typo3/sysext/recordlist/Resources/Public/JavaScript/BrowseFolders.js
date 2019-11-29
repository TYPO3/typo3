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
define(["require","exports","jquery","./ElementBrowser","TYPO3/CMS/Backend/Modal","TYPO3/CMS/Backend/Severity"],(function(e,r,t,n,a,o){"use strict";return new class{constructor(){t(()=>{t("[data-folder-id]").on("click",e=>{e.preventDefault();const r=t(e.currentTarget),a=r.data("folderId"),o=1===parseInt(r.data("close"),10);n.insertElement("",a,"folder",a,a,"","","",o)}),t(".t3js-folderIdError").on("click",e=>{e.preventDefault(),a.confirm("",t(e.currentTarget).data("message"),o.error,[],[])})})}}}));