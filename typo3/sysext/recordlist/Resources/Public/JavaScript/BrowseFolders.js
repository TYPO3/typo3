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
var __importDefault=this&&this.__importDefault||function(e){return e&&e.__esModule?e:{default:e}};define(["require","exports","jquery","./ElementBrowser","TYPO3/CMS/Backend/Modal","TYPO3/CMS/Backend/Severity"],(function(e,t,r,a,l,n){"use strict";r=__importDefault(r);return new class{constructor(){r.default(()=>{r.default("[data-folder-id]").on("click",e=>{e.preventDefault();const t=r.default(e.currentTarget),l=t.data("folderId"),n=1===parseInt(t.data("close"),10);a.insertElement("",l,"folder",l,l,"","","",n)}),r.default(".t3js-folderIdError").on("click",e=>{e.preventDefault(),l.confirm("",r.default(e.currentTarget).data("message"),n.error,[],[])})})}}}));