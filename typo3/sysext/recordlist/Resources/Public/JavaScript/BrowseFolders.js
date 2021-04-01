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
define(["require","exports","./ElementBrowser","TYPO3/CMS/Backend/Modal","TYPO3/CMS/Backend/Severity","TYPO3/CMS/Core/Event/RegularEvent"],(function(e,t,r,n,a,o){"use strict";return new class{constructor(){new o("click",(e,t)=>{e.preventDefault();const n=t.dataset.folderId;r.insertElement("",n,n,n,1===parseInt(t.dataset.close||"0",10))}).delegateTo(document,"[data-folder-id]"),new o("click",(e,t)=>{e.preventDefault(),n.confirm("",t.dataset.message,a.error,[],[])}).delegateTo(document,".t3js-folderIdError")}}}));